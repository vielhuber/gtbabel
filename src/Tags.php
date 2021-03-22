<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;

class Tags
{
    function __construct(Utils $utils = null, Settings $settings = null, Log $log = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->settings = $settings ?: new Settings();
        $this->log = $log ?: new Log();
    }

    function catchOpeningTags($str)
    {
        if ($this->utils->guessContentType($str) === 'json') {
            return [];
        }
        preg_match_all('/<[a-zA-Z]+(>|.*?[^?]>)/', $str, $matches);
        if (empty($matches[0])) {
            return [];
        }
        $return = [];
        $ids_count = [];
        foreach ($matches[0] as $matches__value) {
            $tag = mb_substr(
                $matches__value,
                1,
                mb_strpos($matches__value, ' ') !== false
                    ? mb_strpos($matches__value, ' ') - 1
                    : mb_strpos($matches__value, '>') - 1
            );
            if (!array_key_exists($tag, $ids_count)) {
                $ids_count[$tag] = 0;
            }
            $ids_count[$tag]++;
            $id = $ids_count[$tag];
            $return[] = [
                'value' => $matches__value,
                'tag' => $tag,
                'id' => $id
            ];
        }
        return $return;
    }

    function catchInlineLinks($str)
    {
        preg_match_all(
            '(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s\<;,]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s\<;,]{2,})',
            $str,
            $match
        );
        if (empty($match[0])) {
            return [];
        }
        // strip last dot (this was too tricky with regex)
        $match[0] = array_map(function ($a) {
            if (mb_strrpos($a, '.') === mb_strlen($a) - 1) {
                $a = mb_substr($a, 0, -1);
            }
            return $a;
        }, $match[0]);
        return $match[0];
    }

    function catchStopwords($str)
    {
        $exclude_stopwords = $this->settings->get('exclude_stopwords');
        if ($exclude_stopwords === null || $exclude_stopwords == '' || empty($exclude_stopwords)) {
            return [];
        }
        preg_match_all('(' . implode('|', $exclude_stopwords) . ')', $str, $match);
        if (empty($match[0])) {
            return [];
        }
        return $match[0];
    }

    function catchInlineLinksStopwordsPlaceholders($str)
    {
        preg_match_all('({[0-9]+})', $str, $match);
        if (empty($match[0])) {
            return [];
        }
        return $match[0];
    }

    function addIds($str)
    {
        foreach ($this->catchOpeningTags($str) as $matches__value) {
            // consider tags like <br/>
            $pos = mb_strrpos($matches__value['value'], '/>');
            $shift = true;
            if ($pos === false) {
                $pos = mb_strrpos($matches__value['value'], '>');
                $shift = false;
            }
            $new =
                mb_substr($matches__value['value'], 0, $pos) .
                ' p="' .
                $matches__value['id'] .
                '"' .
                ($shift === true ? ' ' : '') .
                mb_substr($matches__value['value'], $pos);
            $str = __::str_replace_first($matches__value['value'], $new, $str);
        }
        return $str;
    }

    function removeAttributesExceptIrregularIds($str)
    {
        foreach ($this->catchOpeningTags($str) as $matches__value) {
            $pos_end = mb_strrpos($matches__value['value'], '>');
            if (mb_strpos($matches__value['value'], ' ') !== false) {
                $pos_begin = mb_strpos($matches__value['value'], ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes_cur = mb_substr($matches__value['value'], $pos_begin, $pos_end - $pos_begin);
            $has_notranslate_attribute = false;
            $attributes = explode(' ', trim($attributes_cur));
            foreach ($attributes as $attributes__key => $attributes__value) {
                if (
                    strpos($attributes__value, 'class="') !== false &&
                    strpos($attributes__value, 'notranslate') !== false
                ) {
                    $has_notranslate_attribute = true;
                }
                if (
                    strpos($attributes__value, 'p="') === 0 &&
                    $attributes__value !== 'p="' . $matches__value['id'] . '"'
                ) {
                    continue;
                }
                unset($attributes[$attributes__key]);
            }
            if ($has_notranslate_attribute === true) {
                $attributes[] = 'class="notranslate"';
            }
            if (!empty($attributes)) {
                $attributes = ' ' . implode(' ', $attributes);
            } else {
                $attributes = '';
            }
            $new = str_replace($attributes_cur, $attributes, $matches__value['value']);
            $str = __::str_replace_first($matches__value['value'], $new, $str);
        }
        return $str;
    }

    function removeAttributes($str)
    {
        $mappingTableTags = [];
        foreach ($this->catchOpeningTags($str) as $matches__value) {
            $pos_end = mb_strrpos($matches__value['value'], '>');
            if (mb_strpos($matches__value['value'], ' ') !== false) {
                $pos_begin = mb_strpos($matches__value['value'], ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes = mb_substr($matches__value['value'], $pos_begin, $pos_end - $pos_begin);
            $mappingTableTags[$matches__value['tag']][$matches__value['id']] = trim($attributes);
            $has_notranslate_attribute = false;
            if (preg_match('/class="[^"]*?notranslate[^"]*?"/', $attributes)) {
                $has_notranslate_attribute = true;
            }
            $replacement = '';
            if ($has_notranslate_attribute === true) {
                $replacement = ' class="notranslate"';
            }
            $new = str_replace($attributes, $replacement, $matches__value['value']);
            $str = __::str_replace_first($matches__value['value'], $new, $str);
        }
        return [$str, $mappingTableTags];
    }

    function removeInlineLinksAndStopwords($str)
    {
        $mappingTableInlineLinks = [];
        $mappingTableStopwords = [];
        $shift = 0;
        foreach ($this->catchInlineLinks($str) as $matches__key => $matches__value) {
            $id = $shift + $matches__key + 1;
            $mappingTableInlineLinks[$id] = $matches__value;
            $str = __::str_replace_first($matches__value, '{' . $id . '}', $str);
        }
        $shift = count($mappingTableInlineLinks);
        foreach ($this->catchStopwords($str) as $matches__key => $matches__value) {
            $id = $shift + $matches__key + 1;
            $mappingTableStopwords[$id] = $matches__value;
            $str = __::str_replace_first($matches__value, '{' . $id . '}', $str);
        }
        return [$str, $mappingTableInlineLinks, $mappingTableStopwords];
    }

    function removePrefixSuffix($str)
    {
        $prefix = '';
        $suffix = '';
        $prefix_pattern = '';
        $prefix_pattern .= '^<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>((\*|-|–|\||:|\+|•|●|,|I| | )*)<\/\1>( | )*'; // <span>*</span> etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^<br+\b[^>]*\/?>( | )*'; // <br/> etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^(\*|-|–|\||:|\+|•|●|\/|,|\.)( | )+'; // * and space etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^(\*|–|:|•|●)'; // * etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^("|“)( | )+'; // split quote
        $prefix_pattern .= '|';
        $prefix_pattern .= '^((\d|[a-z])\))( | )+'; // 1) 2) 3) a) b) c) etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^(\.\.\.|…|&hellip;)( | )*'; // ...
        $suffix_pattern = '';
        $suffix_pattern .= '( | )*<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>((\*|-|–|\||:|\+|•|●|,|I| | )*)<\/\2>$'; // <span>*</span> etc.
        $suffix_pattern .= '|';
        $suffix_pattern .= '( | )*<br+\b[^>]*\/?>$'; // <br/> etc.
        $suffix_pattern .= '|';
        $suffix_pattern .= '( | )*(\*|-|–|\||:|•|●|\/|,)$'; // * etc.
        $suffix_pattern .= '|';
        $suffix_pattern .= '( | )+("|„)$'; // split quote
        $suffix_pattern .= '|';
        $suffix_pattern .= '( | )*(\.\.\.|…|&hellip;)$'; // ...
        $suffix_pattern .= '|';
        $suffix_pattern .= '( | )*(: \d+)$'; // : ZAHL
        $prefix_matches = [0 => ['']];
        $suffix_matches = [0 => ['']];
        foreach (['prefix', 'suffix'] as $types__value) {
            while (!empty(${$types__value . '_matches'}[0])) {
                if (${$types__value . '_matches'}[0][0] != '') {
                    ${$types__value} .= ${$types__value . '_matches'}[0][0];
                    if ($types__value === 'prefix') {
                        $str = mb_substr($str, mb_strlen(${$types__value . '_matches'}[0][0]));
                    }
                    if ($types__value === 'suffix') {
                        $str = mb_substr($str, 0, -mb_strlen(${$types__value . '_matches'}[0][0]));
                    }
                }
                preg_match_all('/' . ${$types__value . '_pattern'} . '/', $str, ${$types__value . '_matches'});
            }
        }
        foreach (
            [['(', ')'], ['[', ']'], ['"', '"'], ['&quot;', '&quot;'], ['„', '“'], ['&bdquo;', '&ldquo;']]
            as $surrounder__value
        ) {
            if (
                substr_count($str, $surrounder__value[0]) ===
                    ($surrounder__value[0] === $surrounder__value[1] ? 2 : 1) &&
                substr_count($str, $surrounder__value[1]) ===
                    ($surrounder__value[0] === $surrounder__value[1] ? 2 : 1) &&
                $surrounder__value[0] .
                    trim($str, $surrounder__value[0] . $surrounder__value[1]) .
                    $surrounder__value[1] ===
                    $str
            ) {
                $str = trim($str, $surrounder__value[0] . $surrounder__value[1]);
                $prefix .= $surrounder__value[0];
                $suffix .= $surrounder__value[1];
            }
        }
        return [$str, ['prefix' => $prefix, 'suffix' => $suffix]];
    }

    function addPrefixSuffix($str, $data)
    {
        return $data['prefix'] . $str . $data['suffix'];
    }

    function addAttributesAndRemoveIds($str, $mappingTableTags)
    {
        foreach ($this->catchOpeningTags($str) as $matches__value) {
            $new = $matches__value['value'];
            $pos_end = mb_strrpos($matches__value['value'], '>');
            if (mb_strpos($matches__value['value'], ' ') !== false) {
                $pos_begin = mb_strpos($matches__value['value'], ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes = mb_substr($matches__value['value'], $pos_begin, $pos_end - $pos_begin);
            foreach (explode(' ', $attributes) as $attributes__value) {
                if (mb_strpos($attributes__value, 'p="') !== false) {
                    $matches__value['id'] = str_replace(['p="', '"'], '', $attributes__value);
                }
            }

            // remove all attributes (id and class="notranslate")
            $new = str_replace($attributes, '', $new);

            // restore attributes
            if (
                array_key_exists($matches__value['tag'], $mappingTableTags) &&
                array_key_exists($matches__value['id'], $mappingTableTags[$matches__value['tag']])
            ) {
                $attributes_restored = $mappingTableTags[$matches__value['tag']][$matches__value['id']];
                $pos = mb_strrpos($new, '>');
                $new = mb_substr($new, 0, $pos) . ' ' . $attributes_restored . mb_substr($new, $pos);
            }

            $str = __::str_replace_first($matches__value['value'], $new, $str);
        }
        return $str;
    }

    function addInlineLinksAndStopwords($str, $mappingTableInlineLinks, $mappingTableStopwords)
    {
        foreach ($this->catchInlineLinksStopwordsPlaceholders($str) as $matches__value) {
            $id = str_replace(['{', '}'], '', $matches__value);
            if (array_key_exists($id, $mappingTableInlineLinks)) {
                $link = $mappingTableInlineLinks[$id];
            } elseif (array_key_exists($id, $mappingTableStopwords)) {
                $link = $mappingTableStopwords[$id];
            } else {
                continue;
            }
            $str = __::str_replace_first($matches__value, $link, $str);
        }
        return $str;
    }
}
