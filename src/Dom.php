<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;

class Dom
{
    public $DOMDocument;
    public $DOMXPath;
    public $excluded_nodes;
    public $alt_lng_areas;
    public $force_tokenize;
    public $group_cache;
    public $localize_js_script;

    function __construct(
        Utils $utils = null,
        Data $data = null,
        Host $host = null,
        Settings $settings = null,
        Tags $tags = null,
        Log $log = null,
        DomFactory $domfactory = null
    ) {
        $this->utils = $utils ?: new Utils();
        $this->data = $data ?: new Data();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->tags = $tags ?: new Tags();
        $this->log = $log ?: new Log();
        $this->domfactory = $domfactory ?: new DomFactory();
    }

    function preloadExcludedNodes()
    {
        /* excluded nodes are nodes that are excluded beforehand and contain also attributes of nodes that are already translated */
        $this->excluded_nodes = [];
        if ($this->settings->get('translate_html_exclude') !== null) {
            foreach ($this->settings->get('translate_html_exclude') as $exclude__value) {
                $nodes = $this->DOMXPath->query($this->transformSelectorToXpath($exclude__value['selector']));
                foreach ($nodes as $nodes__value) {
                    if (!isset($exclude__value['attribute']) || $exclude__value['attribute'] == '') {
                        $this->addToExcludedNodes($nodes__value, '*');
                        foreach ($this->getChildrenOfNodeIncludingWhitespace($nodes__value) as $nodes__value__value) {
                            $this->addToExcludedNodes($nodes__value__value, '*');
                        }
                    } else {
                        $this->addToExcludedNodes($nodes__value, $exclude__value['attribute']);
                    }
                }
            }
        }
        /* always exclude detect_dom_changes_include (if not requested by ajax), so that this is not translated twice */
        if (!isset($_GET['gtbabel_translate_part'])) {
            if ($this->settings->get('detect_dom_changes_include') !== null) {
                foreach ($this->settings->get('detect_dom_changes_include') as $exclude__value) {
                    $nodes = $this->DOMXPath->query($this->transformSelectorToXpath($exclude__value['selector']));
                    foreach ($nodes as $nodes__value) {
                        $this->addToExcludedNodes($nodes__value, '*');
                        foreach ($this->getChildrenOfNodeIncludingWhitespace($nodes__value) as $nodes__value__value) {
                            $this->addToExcludedNodes($nodes__value__value, '*');
                        }
                    }
                }
            }
        }
    }

    function addToExcludedNodes($node, $attr)
    {
        if (!array_key_exists($this->getIdOfNode($node), $this->excluded_nodes)) {
            $this->excluded_nodes[$this->getIdOfNode($node)] = [];
        }
        $this->excluded_nodes[$this->getIdOfNode($node)][] = $attr;
    }

    function nodeIsExcluded($node, $attr = '*')
    {
        if (array_key_exists($this->getIdOfNode($node), $this->excluded_nodes)) {
            foreach ($this->excluded_nodes[$this->getIdOfNode($node)] as $excluded_nodes__value) {
                if ($excluded_nodes__value === '*') {
                    return true;
                }
                if ($excluded_nodes__value === $attr) {
                    return true;
                }
                // accept wildcards
                if (strpos($excluded_nodes__value, '*') !== false) {
                    if (preg_match('/' . str_replace('*', '.*', $excluded_nodes__value) . '/', $attr)) {
                        return true;
                    }
                }
            }
            return false;
        }
        return false;
    }

    function addNoTranslateClassToExcludedChildren($node)
    {
        if ($this->isElementNode($node)) {
            foreach ($this->getChildrenOfNodeIncludingWhitespace($node) as $nodes__value) {
                if ($this->isElementNode($nodes__value)) {
                    if ($this->nodeIsExcluded($nodes__value, '*')) {
                        $class = $nodes__value->getAttribute('class');
                        if (strpos($class, 'notranslate') === false) {
                            $class = trim($class . ' notranslate');
                            $nodes__value->setAttribute('class', $class);
                        }
                    }
                }
            }
        }
    }

    function preloadForceTokenize()
    {
        $this->force_tokenize = [];
        if ($this->settings->get('translate_html_force_tokenize') !== null) {
            foreach ($this->settings->get('translate_html_force_tokenize') as $tokenize__value) {
                $nodes = $this->DOMXPath->query($this->transformSelectorToXpath($tokenize__value['selector']));
                foreach ($nodes as $nodes__value) {
                    $this->force_tokenize[] = $this->getIdOfNode($nodes__value);
                }
            }
        }
    }

    function nodeIsForcedTokenized($node)
    {
        return in_array($this->getIdOfNode($node), $this->force_tokenize);
    }

    function removeHiddenBlocks()
    {
        $nodes = $this->DOMXPath->query(
            '/html/body//*[contains(concat(" ", normalize-space(@class), " "), " hide-block")]'
        );
        if (count($nodes) === 0) {
            return;
        }
        $lng_current = $this->data->getCurrentLanguageCode();
        // preload alt lngs without modifying attributes (so that we can call it later again)
        // if we call it before, we have problems because the ids of the nodes changes due to path changes
        $this->preloadAltLngAreas(false);
        $to_remove = [];
        foreach ($nodes as $nodes__value) {
            if ($this->getAltLngForElement($nodes__value) !== null) {
                $lng_source = $this->getAltLngForElement($nodes__value);
            } else {
                $lng_source = $this->settings->getSourceLanguageCode();
            }
            $class = $nodes__value->getAttribute('class');
            $class = explode(' ', $class);
            $class = array_map(function ($classes__value) {
                return trim($classes__value);
            }, $class);
            $class = array_filter($class, function ($classes__value) {
                return strpos($classes__value, 'hide-block') === 0;
            });
            foreach ($class as $classes__value) {
                if (
                    $classes__value === 'hide-block' ||
                    ($classes__value === 'hide-block-source' && $lng_current === $lng_source) ||
                    ($classes__value === 'hide-block-target' && $lng_current !== $lng_source) ||
                    preg_match('/^hide-block(-[a-zA-Z]+)*-' . $lng_current . '(-[a-zA-Z]+)*$/', $classes__value)
                ) {
                    $to_remove[] = $nodes__value;
                    break;
                }
            }
        }
        // remove it afterwards (because otherwise the paths in between get mixed and getAltLngForElement does not work)
        foreach ($to_remove as $to_remove__value) {
            $to_remove__value->parentNode->removeChild($to_remove__value);
        }
    }

    function preloadAltLngAreas($modify = true)
    {
        $this->alt_lng_areas = [];
        $nodes = $this->DOMXPath->query('/html//*[@lang]');
        foreach ($nodes as $nodes__value) {
            $lng = $nodes__value->getAttribute('lang');
            if ($modify === true) {
                $nodes__value->setAttribute('lang', $this->data->getCurrentLanguageCode());
            }
            if (!in_array($lng, $this->settings->getSelectedLanguageCodes())) {
                continue;
            }
            $this->alt_lng_areas[$this->getIdOfNode($nodes__value)] = $lng;
            foreach ($this->getChildrenOfNodeIncludingWhitespace($nodes__value) as $nodes__value__value) {
                $this->alt_lng_areas[$this->getIdOfNode($nodes__value__value)] = $lng;
            }
        }
    }

    function getAltLngForElement($node)
    {
        if (array_key_exists($this->getIdOfNode($node), $this->alt_lng_areas)) {
            return $this->alt_lng_areas[$this->getIdOfNode($node)];
        }
        return null;
    }

    function getGroupsForTextNodes($textnodes)
    {
        $groups = [];
        $to_delete = [];
        foreach ($textnodes as $textnodes__value) {
            if ($this->nodeIsExcluded($textnodes__value, 'text()')) {
                continue;
            }
            if ($this->data->stringShouldNotBeTranslated($textnodes__value->nodeValue)) {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'script') {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'style') {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'pre') {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'code') {
                continue;
            }
            if (array_key_exists($this->getIdOfNode($textnodes__value), $to_delete)) {
                continue;
            }
            $group = $this->getNearestLogicalGroup($textnodes__value);
            if (array_key_exists($this->getIdOfNode($group), $to_delete)) {
                continue;
            }
            $groups[] = $group;
            $children = $this->getChildrenOfNode($group);
            foreach ($children as $children__value) {
                $to_delete[$this->getIdOfNode($children__value)] = true;
            }
        }
        foreach ($groups as $groups__key => $groups__value) {
            if (array_key_exists($this->getIdOfNode($groups__value), $to_delete)) {
                unset($groups[$groups__key]);
            }
        }
        $groups = array_values($groups);
        return $groups;
    }

    function modifyHtmlNodes($mode)
    {
        $include = $this->settings->get('translate_html_include');

        foreach ($include as $include__value) {
            $xpath = $this->transformSelectorToXpath($include__value['selector']);
            $nodes = $this->DOMXPath->query($xpath);
            if (strpos($include__value['selector'], 'text()') !== false) {
                $nodes = $this->getGroupsForTextNodes($nodes);
            }

            if (count($nodes) > 0) {
                foreach ($nodes as $nodes__value) {
                    $this->addNoTranslateClassToExcludedChildren($nodes__value);
                    $content = [];
                    // this is important:
                    // if you fetch the variable of a text node with nodeValue (or even textContent) and also getAttribute
                    // the content is automatically is encoded (what we usually don't want)
                    // we use htmlspecialchars to revert that
                    if ($this->isTextNode($nodes__value)) {
                        $content[] = [
                            'key' => null,
                            'value' => htmlspecialchars($nodes__value->nodeValue),
                            'type' => 'text'
                        ];
                    } else {
                        if (@$include__value['attribute'] != '') {
                            // wildcards
                            if (
                                strpos($include__value['attribute'], '*') !== false ||
                                strpos($include__value['attribute'], '|') !== false
                            ) {
                                $opening_tag = $this->getOuterHtml($nodes__value);
                                $opening_tag = substr($opening_tag, 0, strpos($opening_tag, '>') + 1);
                                foreach (['"', '\''] as $quote__value) {
                                    $regex =
                                        '/' .
                                        '(?:(?: |\r\n|\r|\n)(' .
                                        str_replace('*', '[a-zA-Z-_:]*?', $include__value['attribute']) .
                                        ')=' .
                                        $quote__value .
                                        '([^' .
                                        $quote__value .
                                        ']*?)' .
                                        $quote__value .
                                        ')' .
                                        '/';
                                    preg_match_all($regex, $opening_tag, $matches, PREG_SET_ORDER);
                                    if (empty($matches)) {
                                        continue;
                                    }
                                    foreach ($matches as $matches__value) {
                                        if ($matches__value[1] == '' || $matches__value[2] == '') {
                                            continue;
                                        }
                                        $content[] = [
                                            'key' => $matches__value[1],
                                            'value' => $matches__value[2],
                                            'type' => 'attribute'
                                        ];
                                    }
                                }
                            } else {
                                $content[] = [
                                    'key' => $include__value['attribute'],
                                    'value' => htmlspecialchars(
                                        $nodes__value->getAttribute($include__value['attribute'])
                                    ),
                                    'type' => 'attribute'
                                ];
                            }
                        } else {
                            $content[] = [
                                'key' => null,
                                'value' => $this->getInnerHtml($nodes__value),
                                'type' => 'text'
                            ];
                        }
                    }

                    if (!empty($content)) {
                        foreach ($content as $content__value) {
                            if ($content__value['value'] != '') {
                                if (
                                    $this->nodeIsExcluded(
                                        $nodes__value,
                                        $content__value['type'] === 'attribute' ? $content__value['key'] : 'text()'
                                    )
                                ) {
                                    continue;
                                }

                                if ($this->getAltLngForElement($nodes__value) !== null) {
                                    $lng_source = $this->getAltLngForElement($nodes__value);
                                } else {
                                    $lng_source = $this->settings->getSourceLanguageCode();
                                }

                                $lng_target = $this->data->getCurrentLanguageCode();

                                $context = null;
                                if (isset($include__value['context']) && $include__value['context'] != '') {
                                    $context = $include__value['context'];
                                } elseif (
                                    $this->isTextNode($nodes__value) &&
                                    $nodes__value->parentNode->getAttribute('data-context') != ''
                                ) {
                                    $context = $nodes__value->parentNode->getAttribute('data-context');
                                }

                                $str_with_lb = $content__value['value'];

                                $str_without_lb = $this->data->removeLineBreaksAndPrepareString($str_with_lb);

                                $meta = [];

                                // recursively detect json
                                // also make sure strings like "false" don't get caught
                                if (
                                    preg_match('/^({|\[).+/', $str_without_lb) &&
                                    __::string_is_json(htmlspecialchars_decode($str_without_lb))
                                ) {
                                    $trans = $this->domfactory->modifyContentFactory(
                                        htmlspecialchars_decode($str_without_lb),
                                        'translate'
                                    );
                                } else {
                                    $trans = $this->data->prepareTranslationAndAddDynamicallyIfNeeded(
                                        $str_without_lb,
                                        $lng_source,
                                        $lng_target,
                                        $context,
                                        $meta
                                    );
                                }

                                if ($trans === null) {
                                    continue;
                                }

                                $trans = $this->data->reintroduceOuterLineBreaks($trans, $str_without_lb, $str_with_lb);

                                if ($this->isTextNode($nodes__value)) {
                                    // this is important: domdocument set strings with encoded html chars
                                    // for text nodes as plain text (and not html)
                                    // we therefore use the parent node and set the node value accordingly
                                    $nodes__value->nodeValue = html_entity_decode($trans, ENT_QUOTES, 'UTF-8');
                                    $this->addToExcludedNodes($nodes__value, 'text()');
                                } else {
                                    if ($content__value['type'] === 'attribute') {
                                        // sometimes there are malformed chars; catch errors
                                        try {
                                            // be aware: setAttribute automatically decodes the value
                                            // we get already a decoded string, which we need to revert to a encoded one beforehand
                                            $nodes__value->setAttribute(
                                                $content__value['key'],
                                                htmlspecialchars_decode($trans)
                                            );
                                        } catch (\Exception $e) {
                                        }
                                        $this->addToExcludedNodes($nodes__value, $content__value['key']);
                                    } else {
                                        $this->setInnerHtml($nodes__value, $trans);
                                        $this->addToExcludedNodes($nodes__value, 'text()');
                                    }
                                }

                                // populate meta data into dom (only in buffer mode!)
                                if (
                                    $this->settings->get('frontend_editor') === true &&
                                    $mode === 'buffer' &&
                                    !empty($meta)
                                ) {
                                    $meta_target = $this->isElementNode($nodes__value)
                                        ? $nodes__value
                                        : $nodes__value->parentNode;
                                    if ($meta_target !== null) {
                                        if ($meta_target->getAttribute('data-gtbabel-meta') != '') {
                                            $meta_existing = $meta_target->getAttribute('data-gtbabel-meta');
                                            $meta = array_merge($meta, unserialize(base64_decode($meta_existing)));
                                            $meta = __::array_unique($meta);
                                        }
                                        $meta_encrypted = base64_encode(serialize($meta));
                                        $meta_target->setAttribute('data-gtbabel-meta', $meta_encrypted);
                                        $meta_target->setAttribute(
                                            'data-gtbabel-meta-key',
                                            base64_encode(serialize($this->getIdOfNode($meta_target)))
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function modifyXmlNodes()
    {
        foreach ($this->settings->get('translate_xml_include') as $include__value) {
            $xpath = $include__value['selector'];
            $nodes = $this->DOMXPath->query($xpath);
            if (count($nodes) > 0) {
                foreach ($nodes as $nodes__value) {
                    $content = [];
                    $content[] = [
                        'key' => null,
                        'value' => $this->getInnerHtml($nodes__value),
                        'type' => 'text'
                    ];
                    if (!empty($content)) {
                        foreach ($content as $content__value) {
                            if ($content__value['value'] != '') {
                                $lng_source = $this->settings->getSourceLanguageCode();
                                $lng_target = $this->data->getCurrentLanguageCode();
                                $context = null;
                                if (isset($include__value['context']) && $include__value['context'] != '') {
                                    $context = $include__value['context'];
                                }
                                $str_with_lb = $content__value['value'];
                                $str_without_lb = $this->data->removeLineBreaksAndPrepareString($str_with_lb);
                                $trans = $this->data->prepareTranslationAndAddDynamicallyIfNeeded(
                                    $str_without_lb,
                                    $lng_source,
                                    $lng_target,
                                    $context
                                );
                                if ($trans === null) {
                                    continue;
                                }
                                $trans = $this->data->reintroduceOuterLineBreaks($trans, $str_without_lb, $str_with_lb);
                                $this->setInnerHtml($nodes__value, $trans);
                            }
                        }
                    }
                }
            }
        }
    }

    function modifyContent($content, $mode)
    {
        if ($content == '') {
            return $content;
        }
        $content = $this->modifyHtml($content, $mode);
        $content = $this->modifyXml($content, $mode);
        $content = $this->modifyJson($content, $mode);
        return $content;
    }

    function modifyHtml($html, $mode)
    {
        $content_type = $this->utils->guessContentType($html);
        if (!in_array($content_type, ['plain', 'html'])) {
            return $html;
        }
        if ($this->settings->get('translate_html') !== true) {
            return $html;
        }
        $this->setupDomDocument($html);
        if ($mode === 'buffer') {
            $this->setHtmlLangTags();
            $this->setRtlAttr();
            $this->doWordPressSpecificsBefore();
            $this->detectDomChangesFrontend();
            $this->frontendEditorFrontend();
            $this->localizeJsPrepare();
            $this->localizeJsInject();
            $this->injectLanguagePickerIntoMenu();
            $this->showSimpleLanguagePicker();
            $this->showFrontendEditorLinks();
        }
        $this->removeHiddenBlocks();
        $this->preloadAltLngAreas();
        $this->preloadExcludedNodes();
        $this->preloadForceTokenize();
        $this->modifyHtmlNodes($mode);
        if ($mode === 'buffer') {
            $this->doWordPressSpecificsAfter();
        }
        $html = $this->finishDomDocument();
        // DomDocument always encodes characters (meaning we receive plain text links with &amp; inside, which is bad for plain text)
        if ($content_type === 'plain') {
            $html = htmlspecialchars_decode($html);
        }
        return $html;
    }

    function modifyXml($xml, $mode)
    {
        if ($this->utils->guessContentType($xml) !== 'xml') {
            return $xml;
        }
        if ($this->settings->get('translate_xml') !== true) {
            return $xml;
        }
        if ($this->settings->get('translate_xml_include') === null) {
            return $xml;
        }
        $this->setupDomDocument($xml);
        $this->modifyXmlNodes();
        $this->setXmlLangTags();
        $xml = $this->finishDomDocument();
        return $xml;
    }

    function setupDomDocument($html)
    {
        $this->DOMDocument = __::str_to_dom($html);
        $this->DOMXPath = new \DOMXPath($this->DOMDocument);
    }

    function finishDomDocument()
    {
        return __::dom_to_str($this->DOMDocument);
    }

    function setHtmlLangTags()
    {
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }

        if ($this->settings->get('html_lang_attribute') === true) {
            $html_node = $this->DOMXPath->query('/html')[0];
            if ($html_node !== null) {
                $html_node->setAttribute('lang', $this->data->getCurrentLanguageCode());
            }
        }

        if ($this->settings->get('html_hreflang_tags') === true) {
            $head_node = $this->DOMXPath->query('/html/head')[0];
            if ($head_node !== null) {
                $data = $this->data->getLanguagePickerData(false);
                foreach ($data as $data__value) {
                    if ($data__value['hreflang'] === null) {
                        continue;
                    }
                    $tag = $this->DOMDocument->createElement('link', '');
                    $tag->setAttribute('rel', 'alternate');
                    $tag->setAttribute('hreflang', $data__value['hreflang']);
                    $tag->setAttribute('href', $data__value['url']);
                    $head_node->appendChild($tag);
                }
                foreach ($data as $data__value) {
                    if ($data__value['code'] !== $this->settings->getSourceLanguageCode()) {
                        continue;
                    }
                    $tag = $this->DOMDocument->createElement('link', '');
                    $tag->setAttribute('rel', 'alternate');
                    $tag->setAttribute('hreflang', 'x-default');
                    $tag->setAttribute('href', $data__value['url']);
                    $head_node->appendChild($tag);
                }
            }
        }
    }

    function setXmlLangTags()
    {
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        if ($this->settings->get('xml_hreflang_tags') !== true) {
            return;
        }

        /* notes:
        - https://developers.google.com/search/docs/advanced/crawling/localized-versions?hl=de&visit_id=637489903664231830-3049723367&rd=2#methods-for-indicating-your-alternate-pages
        - https://webmasters.stackexchange.com/questions/74118/is-a-different-sitemap-per-language-ok-how-do-i-tell-google-about-them
        */
        if (count($this->DOMDocument->getElementsByTagName('sitemap')) > 0) {
            $to_append = [];
            $preload = [];
            foreach ($this->DOMDocument->getElementsByTagName('sitemap') as $nodes__value) {
                $preload[] = [
                    'node' => $nodes__value,
                    'data' => $this->data->getLanguagePickerData(
                        false,
                        $this->DOMXPath->query('.//*[name()=\'loc\']', $nodes__value)[0]->nodeValue
                    )
                ];
            }
            foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
                foreach ($preload as $preload__value) {
                    if ($languages__value === $this->data->getCurrentLanguageCode()) {
                        continue;
                    }
                    foreach ($preload__value['data'] as $data__value) {
                        if ($data__value['hreflang'] === null) {
                            continue;
                        }
                        if ($data__value['code'] !== $languages__value) {
                            continue;
                        }
                        $tag = $preload__value['node']->cloneNode(true);
                        $loc = $this->DOMXPath->query('.//*[name()=\'loc\']', $tag)[0];
                        $loc->nodeValue = $data__value['url'];
                        $to_append[] = $tag;
                    }
                }
            }
            if (!empty($to_append)) {
                if (count($this->DOMDocument->getElementsByTagName('sitemapindex')) > 0) {
                    $parent = $this->DOMDocument->getElementsByTagName('sitemapindex')[0];
                    foreach ($to_append as $to_append__value) {
                        $parent->appendChild($to_append__value);
                    }
                }
            }
        } else {
            $nodes = $this->DOMXPath->query('//*[name()=\'loc\']');
            if (count($nodes) > 0) {
                foreach ($nodes as $nodes__value) {
                    $data = $this->data->getLanguagePickerData(false, $nodes__value->nodeValue);
                    foreach ($data as $data__value) {
                        if ($data__value['hreflang'] === null) {
                            continue;
                        }
                        $tag = $this->DOMDocument->createElement('xhtml:link', '');
                        $tag->setAttribute('rel', 'alternate');
                        $tag->setAttribute('hreflang', $data__value['hreflang']);
                        $tag->setAttribute('href', $data__value['url']);
                        if ($nodes__value->nextSibling === null) {
                            $nodes__value->parentNode->appendChild($tag);
                        } else {
                            $nodes__value->parentNode->insertBefore($tag, $nodes__value->nextSibling);
                        }
                    }
                }
            }
            $nodes = $this->DOMXPath->query('/*');
            if (count($nodes) > 0) {
                foreach ($nodes as $nodes__value) {
                    if ($nodes__value->getAttribute('xmlns') != '') {
                        $nodes__value->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
                    }
                }
            }
        }
    }

    function setRtlAttr()
    {
        if ($this->settings->isLanguageDirectionRtl($this->data->getCurrentLanguageCode())) {
            $html_node = $this->DOMXPath->query('/html')[0];
            if ($html_node !== null) {
                $html_node->setAttribute('dir', 'rtl');
            }
        }
    }

    function transformSelectorToXpath($selector)
    {
        if (strpos($selector, '/') === 0 || strpos($selector, './') === 0) {
            return $selector;
        }

        $xpath = './/';

        $parts = explode(' ', $selector);
        foreach ($parts as $parts__key => $parts__value) {
            // [placeholder] => *[placeholder]
            if (
                mb_strpos($parts__value, '[') === 0 &&
                mb_strrpos($parts__value, ']') === mb_strlen($parts__value) - 1
            ) {
                $parts__value = '*' . $parts__value;
            }
            // input[placeholder] => input[@placeholder]
            if (mb_strpos($parts__value, '[') !== false && mb_strpos($parts__value, '@') === false) {
                $parts__value = str_replace('[', '[@', $parts__value);
            }
            // .foo => *[contains(concat(" ", normalize-space(@class), " "), " foo ")]
            if (mb_strpos($parts__value, '.') !== false) {
                $parts__value_parts = explode('.', $parts__value);
                foreach ($parts__value_parts as $parts__value_parts__key => $parts__value_parts__value) {
                    if ($parts__value_parts__key === 0 && $parts__value_parts__value === '') {
                        $parts__value_parts[$parts__value_parts__key] = '*';
                    }
                    if ($parts__value_parts__key > 0) {
                        $parts__value_parts[$parts__value_parts__key] =
                            '[contains(concat(" ", normalize-space(@class), " "), " ' .
                            $parts__value_parts__value .
                            ' ")]';
                    }
                }
                $parts__value = implode('', $parts__value_parts);
            }
            // #foo => *[@id="foo"]
            if (mb_strpos($parts__value, '#') === 0) {
                $parts__value = '*[@id="' . str_replace('#', '', $parts__value) . '"]';
            }
            $parts[$parts__key] = $parts__value;
        }
        $xpath .= implode('//', $parts);
        // div + div => div/following::div
        if (mb_strpos($xpath, '//+//') !== false) {
            $xpath = str_replace('//+//', '/following::', $xpath);
        }
        // div > div => div / div
        if (mb_strpos($xpath, '//>//') !== false) {
            $xpath = str_replace('//>//', ' / ', $xpath);
        }
        return $xpath;
    }

    function isElementNode($node)
    {
        return @$node->nodeType === 1;
    }

    function isTextNode($node)
    {
        return @$node->nodeName === '#text';
    }

    function isEmptyTextNode($node)
    {
        return @$node->nodeName === '#text' && trim(@$node->nodeValue) == '';
    }

    function getIdOfNode($node)
    {
        return $node->getNodePath();
    }

    function getTagNameOfNode($node)
    {
        if ($node === null) {
            return '';
        }
        if (@$node->tagName == '') {
            return '';
        }
        return $node->tagName;
    }

    function isInnerTagNode($node)
    {
        if (@$node->tagName == '') {
            return false;
        }
        return in_array($node->tagName, ['a', 'br', 'strong', 'b', 'u', 'small', 'i', 'em', 'span', 'sup', 'sub']);
    }

    function getOuterHtml($node)
    {
        $doc = new \DOMDocument();
        $doc->appendChild($doc->importNode($node, true));
        return $doc->saveHTML();
    }

    function getInnerHtml($node)
    {
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $node->ownerDocument->saveHTML($child);
        }
        return $inner;
    }

    function setInnerHtml($node, $value)
    {
        for ($x = $node->childNodes->length - 1; $x >= 0; $x--) {
            $node->removeChild($node->childNodes->item($x));
        }
        if ($value != '') {
            $f = $node->ownerDocument->createDocumentFragment();
            $result = @$f->appendXML($value);
            if ($result) {
                if ($f->hasChildNodes()) {
                    $node->appendChild($f);
                }
            } else {
                $f = new \DOMDocument();
                $value = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');
                $result = @$f->loadHTML('<htmlfragment>' . $value . '</htmlfragment>');
                if ($result) {
                    $import = $f->getElementsByTagName('htmlfragment')->item(0);
                    foreach ($import->childNodes as $child) {
                        $importedNode = $node->ownerDocument->importNode($child, true);
                        $node->appendChild($importedNode);
                    }
                } else {
                }
            }
        }
    }

    function replaceNodeWithString($node, $str)
    {
        $str = mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
        $tmp = new \DOMDocument();
        @$tmp->loadHTML($str, LIBXML_HTML_NOIMPLIED);
        $repl = $this->DOMDocument->importNode($tmp->documentElement, true);
        $node->parentNode->replaceChild($repl, $node);
    }

    function addClass($node, $new_class)
    {
        $class = $node->getAttribute('class');
        if ($class == '') {
            $class = $new_class;
        } else {
            $class .= ' ' . $new_class;
        }
        $node->setAttribute('class', $class);
    }

    function insertAfter($node, $newNode)
    {
        if ($node->nextSibling === null) {
            $node->parentNode->appendChild($newNode);
        } else {
            $node->parentNode->insertBefore($newNode, $node->nextSibling);
        }
    }

    function stringToNode($str)
    {
        $str = mb_convert_encoding($str, 'HTML-ENTITIES', 'UTF-8');
        $tmp = new \DOMDocument();
        @$tmp->loadHTML($str, LIBXML_HTML_NOIMPLIED);
        $node = $this->DOMDocument->importNode($tmp->documentElement, true);
        return $node;
    }

    function getChildrenCountRecursivelyOfNodeTagsOnly($node)
    {
        return $this->DOMXPath->evaluate('count(.//*)', $node);
    }

    function getSiblingCountOfNonTextNode($node)
    {
        return $this->DOMXPath->evaluate('count(./../*|./../text()[normalize-space()])', $node) - 1;
    }

    function getNodeSiblingCountOfNonTextNode($node)
    {
        return $this->DOMXPath->evaluate('count(./../*)', $node) - 1;
    }

    function getBrSiblingCountOfNonTextNode($node)
    {
        return $this->DOMXPath->evaluate('count(./../br)', $node);
    }

    function getTextSiblingCountOfNonTextNode($node)
    {
        return $this->DOMXPath->evaluate('count(./../text()[normalize-space()])', $node);
    }

    function getTextSiblingCountOfNonTextNodeWithMoreChars($node, $length = 0)
    {
        return $this->DOMXPath->evaluate(
            'count(./../text()[normalize-space()][string-length(normalize-space(.)) > ' . $length . '])',
            $node
        );
    }

    function getTextSiblingCountOfNonTextNodeWithLessChars($node, $length = 0)
    {
        return $this->DOMXPath->evaluate(
            'count(./../text()[normalize-space()][string-length(normalize-space(.)) < ' . $length . '])',
            $node
        );
    }

    function getChildrenCountOfNode($node)
    {
        return $this->DOMXPath->evaluate('count(./*|./text()[normalize-space()])', $node);
    }

    function getChildrenOfNode($node)
    {
        return $this->DOMXPath->query('.//*|.//text()[normalize-space()]', $node);
    }

    function getLastChildrenOfNode($node)
    {
        $children = $this->DOMXPath->query('(.//*|.//text()[normalize-space()])[last()]', $node);
        if ($children->length === 0) {
            return null;
        }
        return $children[0];
    }

    function getPreviousSiblingOfNode($node)
    {
        $sibling = $this->DOMXPath->query(
            '(./preceding-sibling::*|./preceding-sibling::text()[normalize-space()])[1]',
            $node
        );
        if ($sibling->length === 0) {
            return null;
        }
        return $sibling[0];
    }

    function getNextSiblingOfNode($node)
    {
        $sibling = $this->DOMXPath->query(
            '(./following-sibling::*|./following-sibling::text()[normalize-space()])[1]',
            $node
        );
        if ($sibling->length === 0) {
            return null;
        }
        return $sibling[0];
    }

    function getChildrenOfNodeIncludingWhitespace($node)
    {
        return $this->DOMXPath->query('.//node()', $node);
    }

    function nodeContentBeginsWith($node, $char)
    {
        if ($node === null) {
            return false;
        }
        if ($node->nodeValue == '') {
            return false;
        }
        return mb_substr(trim($node->nodeValue), 0, 1) == $char;
    }

    function nodeContentEndsWith($node, $char)
    {
        if ($node === null) {
            return false;
        }
        if ($node->nodeValue == '') {
            return false;
        }
        return mb_substr(trim($node->nodeValue), -1) == $char;
    }

    function getParentNodeWithMoreThanOneChildren($node)
    {
        $cur = $node;
        $level = 0;
        $max_level = 10;
        while ($this->getChildrenCountOfNode($cur) <= 1) {
            $cur = $cur->parentNode;
            if ($cur === null) {
                return $node;
            }
            $level++;
            if ($level >= $max_level) {
                return $node;
            }
        }
        return $cur;
    }

    function getNearestLogicalGroup($node)
    {
        if ($this->group_cache === null) {
            $this->group_cache = [];
        }
        $parent = $this->getParentNodeWithMoreThanOneChildren($node);
        if (!array_key_exists($this->getIdOfNode($parent), $this->group_cache)) {
            $this->group_cache[$this->getIdOfNode($parent)] = false;

            // if the tokenization is forced on parent
            if ($this->nodeIsForcedTokenized($parent)) {
                $this->group_cache[$this->getIdOfNode($parent)] = true;
            }

            // try to tokenize based on children
            foreach ($this->getChildrenOfNode($parent) as $nodes__value) {
                // exclude empty text nodes
                if ($this->isEmptyTextNode($nodes__value)) {
                    continue;
                }
                // if the tokenization is forced on any child
                if ($this->nodeIsForcedTokenized($nodes__value)) {
                    $this->group_cache[$this->getIdOfNode($parent)] = true;
                    break;
                }
                // if the unprefixed/suffixed version of a text node is a single dom node
                if ($this->isTextNode($nodes__value)) {
                    $unprefixed_suffixed = $this->tags->removePrefixSuffix(
                        $this->getInnerHtml($nodes__value->parentNode)
                    )[0];
                    if (preg_match('/^<([a-zA-Z][a-zA-Z0-9]*)[^>]*>(.*?)<\/\1>$/', $unprefixed_suffixed)) {
                        // sanity check: don't cach "a) <span>foo</span> bar <span>baz</span>"
                        $tmp = $this->DOMDocument->createElement('div', '');
                        $this->setInnerHtml($tmp, $unprefixed_suffixed);
                        if ($this->getChildrenCountOfNode($tmp) === 1) {
                            $this->group_cache[$this->getIdOfNode($parent)] = true;
                            break;
                        }
                    }
                }
                /*
                this is the most important part of the tokenization pattern:
                return parent node (and don't tokenize), if
                    (
                        it is a text node
                    )
                    OR
                    (
                        it is an inner tag node (span, br, ...)
                        AND
                        it has less than 2 children
                        AND 
                        (
                            it has no siblings
                            OR
                            it has 1 or more text node siblings longer than 1 char
                        )
                        AND NOT
                        (
                            (
                                its content ends with ":"
                                OR
                                its next sibling content begins with ":"
                                OR
                                its last children is a <br>
                                OR
                                its next sibling is a <br>
                            )
                            AND
                            (                                
                                it has 0 or 1 text node sibling
                                OR
                                it has 2 or more text node siblings less than 2 chars
                                OR
                                it has only br siblings
                            )
                        )
                    )
                */
                if (
                    !(
                        $this->isTextNode($nodes__value) ||
                        ($this->isInnerTagNode($nodes__value) &&
                            $this->getChildrenCountRecursivelyOfNodeTagsOnly($nodes__value) <= 2 &&
                            ($this->getSiblingCountOfNonTextNode($nodes__value) == 0 ||
                                $this->getTextSiblingCountOfNonTextNodeWithMoreChars($nodes__value, 1) > 0) &&
                            !(
                                ($this->nodeContentEndsWith($nodes__value, ':') ||
                                    $this->nodeContentBeginsWith($this->getNextSiblingOfNode($nodes__value), ':') ||
                                    $this->getTagNameOfNode($this->getLastChildrenOfNode($nodes__value)) === 'br' ||
                                    $this->getTagNameOfNode($this->getNextSiblingOfNode($nodes__value)) === 'br') &&
                                ($this->getTextSiblingCountOfNonTextNode($nodes__value) <= 1 ||
                                    $this->getTextSiblingCountOfNonTextNodeWithLessChars($nodes__value, 2) >= 2 ||
                                    $this->getNodeSiblingCountOfNonTextNode($nodes__value) ==
                                        $this->getBrSiblingCountOfNonTextNode($nodes__value))
                            ))
                    )
                ) {
                    $this->group_cache[$this->getIdOfNode($parent)] = true;
                    break;
                }
            }
        }
        if ($this->group_cache[$this->getIdOfNode($parent)] === true) {
            return $node;
        }
        return $parent;
    }

    function modifyJson($json, $mode)
    {
        if ($this->utils->guessContentType($json) !== 'json') {
            return $json;
        }
        if ($this->settings->get('translate_json') !== true) {
            return $json;
        }
        if ($this->settings->get('translate_json_include') === null) {
            return $json;
        }
        $keys = [];
        $url = $this->host->getCurrentUrlWithArgsConverted();
        foreach ($this->settings->get('translate_json_include') as $translate_json_include__value) {
            $regex =
                '/^(.+\/)?' .
                str_replace('\*', '.*', preg_quote(trim($translate_json_include__value['url'], '/'), '/')) .
                '(\/.+)?$/';
            if (preg_match($regex, trim($url, '/'))) {
                $keys = $translate_json_include__value['selector'];
                break;
            }
        }
        if (empty($keys)) {
            return $json;
        }
        $json = json_decode($json);
        $json = __::array_map_deep($json, function ($value, $key, $key_chain) use ($keys, $mode) {
            $match = false;
            foreach ($keys as $keys__value) {
                $regex = '/' . str_replace('\*', '(.+)', preg_quote($keys__value)) . '/';
                if (preg_match($regex, implode('.', $key_chain))) {
                    $match = true;
                    break;
                }
            }
            if ($match === true) {
                $trans = $this->domfactory->modifyContentFactory($value, 'translate');
                if ($trans !== null) {
                    $value = $trans;
                }
            }
            return $value;
        });
        $json = json_encode($json);
        return $json;
    }

    function detectDomChangesFrontend()
    {
        if ($this->settings->get('detect_dom_changes') !== true) {
            return;
        }
        if (
            $this->settings->get('detect_dom_changes_include') === null ||
            empty($this->settings->get('detect_dom_changes_include'))
        ) {
            return;
        }
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        if ($this->data->sourceLngIsCurrentLng()) {
            return;
        }
        $head = $this->DOMXPath->query('/html/head')[0];
        if ($head === null) {
            return;
        }
        $tag = $this->DOMDocument->createElement('script', '');
        $tag->setAttribute('data-type', 'gtbabel-detect-dom-changes');
        $script = '';
        $detect_dom_changes_include = [];
        foreach ($this->settings->get('detect_dom_changes_include') as $detect_dom_changes_include__value) {
            $detect_dom_changes_include__value['selector'] = str_replace(
                "\r",
                '',
                $detect_dom_changes_include__value['selector']
            );
            $to_escape = ['\\', "\f", "\n", "\r", "\t", "\v", "\""];
            foreach ($to_escape as $to_escape__value) {
                $detect_dom_changes_include__value['selector'] = addcslashes(
                    $detect_dom_changes_include__value['selector'],
                    $to_escape__value
                );
            }
            $detect_dom_changes_include[] = $detect_dom_changes_include__value['selector'];
        }
        $script .=
            'var gtbabel_detect_dom_changes_include = JSON.parse(\'' .
            json_encode($detect_dom_changes_include, JSON_HEX_APOS) .
            '\');';
        $script .= file_get_contents(dirname(__DIR__) . '/components/detectdomchanges/build/bundle.js');
        $tag->textContent = $script;
        $head->insertBefore($tag, $head->firstChild);
        $tag = $this->DOMDocument->createElement('style', '');
        $tag->setAttribute('data-type', 'gtbabel-detect-dom-changes');
        $tag->textContent = '
            [data-gtbabel-hide], [data-gtbabel-hide] *, [data-gtbabel-hide] *:before, [data-gtbabel-hide] *:after {
                color:transparent !important;
            }
        ';
        $head->insertBefore($tag, $head->firstChild);
    }

    function frontendEditorFrontend()
    {
        if ($this->settings->get('frontend_editor') !== true) {
            return;
        }
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        if ($this->data->sourceLngIsCurrentLng()) {
            return;
        }
        $head = $this->DOMXPath->query('/html/head')[0];
        if ($head === null) {
            return;
        }
        $tag = $this->DOMDocument->createElement('script', '');
        $tag->setAttribute('data-type', 'gtbabel-frontend-editor');
        $tag->textContent = file_get_contents(dirname(__DIR__) . '/components/frontendeditor/build/bundle.js');
        $head->appendChild($tag);
        $tag = $this->DOMDocument->createElement('style', '');
        $tag->setAttribute('data-type', 'gtbabel-frontend-editor');
        $tag->textContent = file_get_contents(dirname(__DIR__) . '/components/frontendeditor/build/bundle.css');
        $head->appendChild($tag);
    }

    function doWordPressSpecificsBefore()
    {
        if (!$this->utils->isWordPress()) {
            return;
        }
        // woocommerce card cache
        // woocommerce uses a nasty cache in session storage
        // this causes the card on the top right of "storefront" to have a wrong language after switching languages
        // this fixes this behaviour
        $wc_card_fragments = $this->DOMXPath->query('/html/body//*[@id="wc-cart-fragments-js-extra"]');
        if ($wc_card_fragments->length > 0) {
            foreach ($wc_card_fragments as $wc_card_fragments__value) {
                $wc_card_fragments__value->textContent =
                    $wc_card_fragments__value->textContent .
                    "
                        if ( typeof wc_cart_fragments_params !== 'undefined' && typeof wc_cart_fragments_params.fragment_name !== 'undefined' ) {
                            window.sessionStorage.removeItem(wc_cart_fragments_params.fragment_name);
                        }
                    ";
            }
        }
        // woocommerce personal data in emails
        $wc_personal_intro_in_mail = $this->DOMXPath->query('/html/body//*[@id="body_content_inner"]/p[1]');
        if ($wc_personal_intro_in_mail->length > 0) {
            foreach ($wc_personal_intro_in_mail as $wc_personal_intro_in_mail__value) {
                $wc_personal_intro_in_mail__value->parentNode->removeChild($wc_personal_intro_in_mail__value);
            }
        }

        // alt lng: handle title / meta description
        if (is_singular()) {
            $id = get_the_ID();
            if (__::x($id)) {
                $alt_lng = get_post_meta($id, 'gtbabel_alt_lng', true);
                if (__::x($alt_lng)) {
                    $html_node = $this->DOMXPath->query('/html/head//title')[0];
                    if ($html_node !== null) {
                        $html_node->setAttribute('class', 'notranslate');
                    }
                    $html_node = $this->DOMXPath->query('/html/head//meta[@name="description"][@content]')[0];
                    if ($html_node !== null) {
                        $html_node->setAttribute('lang', $alt_lng);
                    }
                }
            }
        }

        // preserve search term in title
        // note that we cannot use the concept of stopwords here (because you can crash the whole page when reading stopwords from $_GET)
        if (is_search() && get_search_query() != '') {
            $html_node = $this->DOMXPath->query('/html/head//title')[0];
            if ($html_node !== null) {
                $html_node->textContent = __::str_replace_last(get_search_query(), '{1}', $html_node->textContent);
            }
        }
    }

    function doWordPressSpecificsAfter()
    {
        if (!$this->utils->isWordPress()) {
            return;
        }

        // restore search term in title
        if (is_search() && get_search_query() != '') {
            $html_node = $this->DOMXPath->query('/html/head//title')[0];
            if ($html_node !== null) {
                $html_node->textContent = str_replace('{1}', get_search_query(), $html_node->textContent);
            }
        }
    }

    function localizeJsPrepare()
    {
        if ($this->settings->get('localize_js') !== true) {
            return;
        }
        if (
            $this->settings->get('localize_js_strings') === null ||
            empty($this->settings->get('localize_js_strings'))
        ) {
            return;
        }
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        $translated_strings_json = [];
        if (!$this->data->sourceLngIsCurrentLng()) {
            foreach ($this->settings->get('localize_js_strings') as $localize_js_strings__value) {
                $string = $localize_js_strings__value;
                $trans = $this->domfactory->modifyContentFactory($string, 'translate');
                if ($trans === null) {
                    continue;
                }
                $string = str_replace("\r", '', $string);
                $trans = str_replace("\r", '', $trans);
                // those chars must be escaped in a json encoded string
                $to_escape = ['\\', "\f", "\n", "\r", "\t", "\v", "\""];
                foreach ($to_escape as $to_escape__value) {
                    $string = addcslashes($string, $to_escape__value);
                    $trans = addcslashes($trans, $to_escape__value);
                }
                $translated_strings_json[$string] = $trans;
            }
        }

        $script = '';
        $script .=
            'var gtbabel_translated_strings = JSON.parse(\'' .
            json_encode($translated_strings_json, JSON_HEX_APOS) .
            '\');';
        $script .=
            'function gtbabel__(string) { if( gtbabel_translated_strings[string] !== undefined ) { return gtbabel_translated_strings[string]; } return string; }';

        $this->localize_js_script = $script;
    }

    function localizeJsInject()
    {
        if ($this->localize_js_script === null) {
            return;
        }
        $head = $this->DOMXPath->query('/html/head')[0];
        if ($head === null) {
            return;
        }
        $tag = $this->DOMDocument->createElement('script', '');
        $tag->setAttribute('data-type', 'gtbabel-translated-strings');
        $tag->textContent = $this->localize_js_script;
        $head->insertBefore($tag, $head->firstChild);
    }

    function injectLanguagePickerIntoMenu()
    {
        $nodes = $this->DOMXPath->query('/html/body//a[@href="#gtbabel_languagepicker"]');
        if (count($nodes) > 0) {
            foreach ($nodes as $nodes__value) {
                $nodes__value->setAttribute('onclick', 'return false;');
                $child_class = $nodes__value->getAttribute('class');
                $this->addClass($nodes__value, 'notranslate');
                $this->addClass($nodes__value->parentNode, 'menu-item-has-children');
                $nodes__value->nodeValue = $this->data->getCurrentLanguageLabel();
                $this->insertAfter(
                    $nodes__value,
                    $this->stringToNode(
                        $this->data->getLanguagePickerHtml(
                            true,
                            null,
                            true,
                            'lngpicker sub-menu',
                            false,
                            false,
                            $child_class
                        )
                    )
                );
            }
        }
    }

    function showSimpleLanguagePicker()
    {
        if ($this->settings->get('show_language_picker') !== true) {
            return;
        }
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        $head = $this->DOMXPath->query('/html/head')[0];
        $body = $this->DOMXPath->query('/html/body')[0];
        if ($head === null || $body === null) {
            return;
        }
        $body->appendChild(
            $this->stringToNode(
                $this->data->getLanguagePickerHtml(
                    true,
                    null,
                    false,
                    'gtbabel-simple-lngpicker notranslate',
                    true,
                    true
                )
            )
        );
        $tag = $this->DOMDocument->createElement('style', '');
        $tag->setAttribute('data-type', 'gtbabel-simple-lngpicker');
        $tag->textContent = file_get_contents(dirname(__DIR__) . '/components/languagepicker/build/bundle.css');
        $head->appendChild($tag);
    }

    function showFrontendEditorLinks()
    {
        if ($this->settings->get('show_frontend_editor_links') !== true) {
            return;
        }
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        // hide on elementor backend
        if ($this->utils->isWordPress() && isset($_GET['elementor-preview']) && $_GET['elementor-preview'] != '') {
            return;
        }
        $head = $this->DOMXPath->query('/html/head')[0];
        $body = $this->DOMXPath->query('/html/body')[0];
        if ($head === null || $body === null) {
            return;
        }
        $lng = $this->data->sourceLngIsCurrentLng() ? null : $this->data->getCurrentLanguageCode();
        if ($lng !== null && $this->utils->isWordPress() && !current_user_can('gtbabel__translate_' . $lng)) {
            return;
        }
        $url = $this->host->getCurrentUrl();
        $html = '';
        $html .= '<ul class="gtbabel-frontend-editor-links notranslate">';
        $html .= '<li class="gtbabel-frontend-editor-links__item">';
        $html .=
            '<a class="gtbabel-frontend-editor-links__link" href="' .
            $url .
            ($this->settings->get('frontend_editor') === true
                ? ''
                : (strpos($url, '?') ? '&' : '?') . 'gtbabel_frontend_editor=1') .
            '">' .
            ($this->settings->get('frontend_editor') === false ? '✏️' : '❌') .
            '</a>';
        $html .= '</li>';
        if ($this->utils->isWordPress()) {
            $html .= '<li class="gtbabel-frontend-editor-links__item">';
            $html .=
                '<a class="gtbabel-frontend-editor-links__link" href="' .
                admin_url('admin.php?page=gtbabel-trans&url=' . urlencode($url) . '&lng=' . $lng . '&shared=0') .
                '" target="_blank">🌐</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $body->appendChild($this->stringToNode($html));
        $tag = $this->DOMDocument->createElement('style', '');
        $tag->setAttribute('data-type', 'gtbabel-frontend-editor-links');
        $tag->textContent = file_get_contents(dirname(__DIR__) . '/components/frontendeditorlinks/build/bundle.css');
        $head->appendChild($tag);
    }
}
