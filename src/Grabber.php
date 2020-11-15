<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;

class Grabber
{
    function __construct(Settings $settings = null, Utils $utils = null, Log $log = null, Data $data = null)
    {
        $this->settings = $settings ?: new Settings();
        $this->utils = $utils ?: new Utils();
        $this->log = $log ?: new Log();
        $this->data = $data ?: new Data();
    }

    function parseSitemap($main_url, $skip, $sitemap_cache = [])
    {
        if (empty($sitemap_cache)) {
            $sitemap_urls = __::extract_urls_from_sitemap(rtrim($main_url, '/') . '/wp-sitemap.xml');
            if (empty($sitemap_urls)) {
                $sitemap_urls = __::extract_urls_from_sitemap(rtrim($main_url, '/') . '/sitemap_index.xml');
            }
        } else {
            $sitemap_urls = $sitemap_cache;
        }
        if (empty($sitemap_urls) || $skip >= count($sitemap_urls)) {
            return null;
        }
        return [$sitemap_urls[$skip], $sitemap_urls];
    }
    function getLngFromHtml($html)
    {
        $DOMDocument = __::str_to_dom($html);
        $DOMXPath = new \DOMXPath($DOMDocument);
        $nodes = $DOMXPath->query('/html');
        if (count($nodes) > 0) {
            foreach ($nodes as $nodes__value) {
                $attr = $nodes__value->getAttribute('lang');
                if ($attr != '') {
                    return $attr;
                }
            }
        }
        return $this->settings->getSourceLanguageCode();
    }

    function getForeignUrlFromHrefLang($html, $lng)
    {
        $DOMDocument = __::str_to_dom($html);
        $DOMXPath = new \DOMXPath($DOMDocument);
        $nodes = $DOMXPath->query('/html/head/link[@hreflang="' . $lng . '"]');
        if (count($nodes) > 0) {
            foreach ($nodes as $nodes__value) {
                $href = $nodes__value->getAttribute('href');
                if ($href != '') {
                    return $href;
                }
            }
        }
        return null;
    }

    function buildCompareData($tokens, $existing, $lng_source)
    {
        $compare = [];
        foreach ($tokens['trans'] as $tokens__key => $tokens__value) {
            foreach ($tokens__value as $tokens__value__key => $tokens__value__value) {
                if ($tokens__value__value['lng_source'] !== $lng_source) {
                    continue;
                }
                if ($tokens__value__value['lng_target'] !== $tokens__key) {
                    continue;
                }
                $key = md5($tokens__value__value['str'] . $tokens__value__value['context']);
                if (!array_key_exists($key, $compare)) {
                    $compare[$key] = [
                        $lng_source => $tokens__value__value['str'],
                        'context' => $tokens__value__value['context']
                    ];
                }
                // get trans from current db
                $trans = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // use long string that has no similarity
                foreach ($existing as $existing__value) {
                    if (
                        array_key_exists($lng_source, $existing__value) &&
                        $existing__value[$lng_source] == $tokens__value__value['str'] &&
                        $existing__value['context'] == $tokens__value__value['context'] &&
                        array_key_exists($tokens__key, $existing__value)
                    ) {
                        $trans = $existing__value[$tokens__key];
                        break;
                    }
                }
                $compare[$key][$tokens__key . '_trans'] = $trans;
                $compare[$key][$tokens__key . '_trans_pos'] = $tokens__value__key;
                $compare[$key][$tokens__key . '_live'] = '';
                $compare[$key][$tokens__key . '_live_pos'] = 0;
                $compare[$key][$tokens__key . '_similar_text'] = 0;
                $compare[$key][$tokens__key . '_dist'] = 0;
                $compare[$key][$tokens__key . '_similarity'] = 0;
            }
        }
        foreach ($tokens['live'] as $tokens__key => $tokens__value) {
            foreach ($tokens__value as $tokens__value__key => $tokens__value__value) {
                if ($tokens__value__value['lng_source'] !== $tokens__key) {
                    continue;
                }
                if ($tokens__value__value['lng_target'] !== $lng_source) {
                    continue;
                }
                foreach ($compare as $compare__key => $compare__value) {
                    if ($compare__value['context'] != $tokens__value__value['context']) {
                        continue;
                    }
                    // 1st factor: text similarity
                    $cmp1 = $tokens__value__value['str'];
                    $cmp2 = $compare__value[$tokens__key . '_trans'];
                    // strip tags
                    $cmp1 = strip_tags($cmp1);
                    $cmp2 = strip_tags($cmp2);
                    // encode entities
                    $cmp1 = html_entity_decode($cmp1);
                    $cmp2 = html_entity_decode($cmp2);
                    // replace all non word strings
                    $cmp1 = preg_replace('/[^\p{L}]/u', '', $cmp1);
                    $cmp2 = preg_replace('/[^\p{L}]/u', '', $cmp2);
                    // sometimes the source does not get split up but the target does
                    // try to fix that
                    if (
                        (strpos($cmp1, $cmp2) !== false && mb_strlen($cmp1) >= mb_strlen($cmp2)) ||
                        (strpos($cmp2, $cmp1) !== false && mb_strlen($cmp2) >= mb_strlen($cmp1))
                    ) {
                        $cmp1 = $cmp2;
                    }
                    similar_text($cmp1, $cmp2, $similar_text);
                    // 2nd factor: text position
                    $dist = abs($compare__value[$tokens__key . '_trans_pos'] - $tokens__value__key);
                    // cumulate
                    $similarity = $similar_text;
                    if ($similar_text < 100) {
                        $similarity -= $dist >= 1 ? pow($dist, 1.3) : 0;
                    }
                    if ($similarity > $compare__value[$tokens__key . '_similarity']) {
                        $compare[$compare__key][$tokens__key . '_dist'] = $dist;
                        $compare[$compare__key][$tokens__key . '_similar_text'] = $similar_text;
                        $compare[$compare__key][$tokens__key . '_similarity'] = $similarity;
                        $compare[$compare__key][$tokens__key . '_live'] = $tokens__value__value['str'];
                        $compare[$compare__key][$tokens__key . '_live_pos'] = $tokens__value__key;
                    }
                }
            }
        }
        return $compare;
    }

    function modifyAppropriateTranslations($compare, $languages, $lng_source, $dry_run = false)
    {
        $replacements = [];
        foreach ($compare as $compare__value) {
            foreach ($languages as $languages__value) {
                if (
                    $compare__value[$languages__value . '_similarity'] > 85 &&
                    $compare__value[$languages__value . '_similarity'] < 100
                ) {
                    $replacements[] = [
                        $compare__value[$languages__value . '_trans'],
                        $compare__value[$languages__value . '_live']
                    ];
                    if ($dry_run === false) {
                        $this->data->editTranslation(
                            $compare__value[$lng_source],
                            $compare__value['context'],
                            $lng_source,
                            $languages__value,
                            $compare__value[$languages__value . '_live']
                        );
                    }
                }
            }
        }
        return $replacements;
    }
}
