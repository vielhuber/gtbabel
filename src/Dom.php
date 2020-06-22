<?php
namespace vielhuber\gtbabel;

class Dom
{
    public $DOMDocument;
    public $DOMXpath;

    public $excluded_nodes;
    public $force_tokenize;
    public $group_cache;

    public $utils;
    public $data;
    public $host;
    public $settings;
    public $log;

    function __construct(
        Utils $utils = null,
        Data $data = null,
        Host $host = null,
        Settings $settings = null,
        Log $log = null
    ) {
        $this->utils = $utils ?: new Utils();
        $this->data = $data ?: new Data();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->log = $log ?: new Log();
    }

    function preloadExcludedNodes()
    {
        /* excluded nodes are nodes that are excluded beforehand and contain also attributes of nodes that are already translated */
        $this->excluded_nodes = [];
        if ($this->settings->get('exclude_dom') !== null) {
            foreach ($this->settings->get('exclude_dom') as $exclude__value) {
                $nodes = $this->DOMXpath->query($this->transformSelectorToXpath($exclude__value));
                foreach ($nodes as $nodes__value) {
                    $this->addToExcludedNodes($nodes__value, '*');
                    foreach ($this->getChildrenOfNodeIncludingWhitespace($nodes__value) as $nodes__value__value) {
                        $this->addToExcludedNodes($nodes__value__value, '*');
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

    function nodeIsExcluded($node, $attr)
    {
        if (array_key_exists($this->getIdOfNode($node), $this->excluded_nodes)) {
            return in_array('*', $this->excluded_nodes[$this->getIdOfNode($node)]) ||
                in_array($attr, $this->excluded_nodes[$this->getIdOfNode($node)]);
        }
        return false;
    }

    function preloadForceTokenize()
    {
        $this->force_tokenize = [];
        if ($this->settings->get('force_tokenize') !== null) {
            foreach ($this->settings->get('force_tokenize') as $tokenize__value) {
                $nodes = $this->DOMXpath->query($this->transformSelectorToXpath($tokenize__value));
                foreach ($nodes as $nodes__value) {
                    $this->force_tokenize[] = $this->getIdOfNode($nodes__value);
                }
            }
        }
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

    function modifyNodes()
    {
        if ($this->data->sourceLngIsCurrentLng() && $this->settings->get('prefix_source_lng') === false) {
            return;
        }

        $include = [];

        $include = array_merge($include, $this->settings->get('include_dom'));

        if ($this->settings->get('translate_default_tag_nodes') === true) {
            $include = array_merge($include, [
                [
                    'selector' => '/html/body//text()',
                    'attribute' => null,
                    'context' => null
                ],
                [
                    'selector' => '/html/body//a[starts-with(@href, \'mailto:\')]',
                    'attribute' => 'href',
                    'context' => 'email'
                ],
                [
                    'selector' => '/html/body//a[@href]',
                    'attribute' => 'href',
                    'context' => 'slug|file'
                ],
                [
                    'selector' => '/html/body//form[@action]',
                    'attribute' => 'action',
                    'context' => 'slug'
                ],
                [
                    'selector' => '/html/body//img[@alt]',
                    'attribute' => 'alt',
                    'context' => null
                ],
                [
                    'selector' => '/html/body//*[@title]',
                    'attribute' => 'title',
                    'context' => null
                ],
                [
                    'selector' => '/html/body//input[@placeholder]',
                    'attribute' => 'placeholder',
                    'context' => null
                ],
                [
                    'selector' => '/html/head//title',
                    'attribute' => null,
                    'context' => 'title'
                ],
                [
                    'selector' => '/html/head//meta[@name="description"][@content]',
                    'attribute' => 'content',
                    'context' => 'description'
                ],
                [
                    'selector' => '/html/body//img[@src]',
                    'attribute' => 'src',
                    'context' => 'file'
                ],
                [
                    'selector' => '/html/body//*[@style]',
                    'attribute' => 'style',
                    'context' => 'file'
                ],
                [
                    'selector' => '/html/body//@*[starts-with(name(), \'data-\')]/parent::*', // data-*
                    'attribute' => '(?! data-context=)(?: (data-.+?)="([^"]+?)")', // data-* (except data-context)
                    'context' => null
                ],
                [
                    'selector' => '/html/body//*[@label]',
                    'attribute' => 'label',
                    'context' => null
                ],
                [
                    'selector' => '/html/body//@*[contains(name(), \'text\')]/parent::*', // *text*
                    'attribute' => '(?! data-context=)(?: ([a-zA-Z-]*?text[a-zA-Z-]*?)="([^"]+?)")', // *text* (except data-context)
                    'context' => null
                ]
            ]);
        }

        foreach ($include as $include__value) {
            $xpath = $this->transformSelectorToXpath($include__value['selector']);
            $nodes = $this->DOMXpath->query($xpath);
            if (strpos($include__value['selector'], 'text()') !== false) {
                $nodes = $this->getGroupsForTextNodes($nodes);
            }

            if (!empty($nodes)) {
                foreach ($nodes as $nodes__value) {
                    $content = [];
                    if ($this->isTextNode($nodes__value)) {
                        $content[] = ['key' => null, 'value' => $nodes__value->nodeValue, 'type' => 'text'];
                    } else {
                        if (@$include__value['attribute'] != '') {
                            // regex
                            if (strpos($include__value['attribute'], '(') !== false) {
                                $opening_tag = $this->getOuterHtml($nodes__value);
                                $opening_tag = substr($opening_tag, 0, strpos($opening_tag, '>') + 1);
                                preg_match_all(
                                    '/' . $include__value['attribute'] . '/',
                                    $opening_tag,
                                    $matches,
                                    PREG_SET_ORDER
                                );
                                if (empty($matches)) {
                                    continue;
                                }
                                foreach ($matches as $matches__value) {
                                    $content[] = [
                                        'key' => $matches__value[1],
                                        'value' => $matches__value[2],
                                        'type' => 'attribute'
                                    ];
                                }
                            } else {
                                $content[] = [
                                    'key' => $include__value['attribute'],
                                    'value' => $nodes__value->getAttribute($include__value['attribute']),
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
                                $str_without_lb = $this->data->removeLineBreaks($str_with_lb);

                                $trans = $this->data->prepareTranslationAndAddDynamicallyIfNeeded(
                                    $str_without_lb,
                                    $this->data->getCurrentLanguageCode(),
                                    $context
                                );

                                if ($trans === null) {
                                    continue;
                                }

                                $trans = $this->data->reintroduceLineBreaks($trans, $str_without_lb, $str_with_lb);

                                if ($this->isTextNode($nodes__value)) {
                                    $nodes__value->nodeValue = $trans;
                                    $this->addToExcludedNodes($nodes__value, 'text()');
                                } else {
                                    if ($content__value['type'] === 'attribute') {
                                        $nodes__value->setAttribute($content__value['key'], $trans);
                                        $this->addToExcludedNodes($nodes__value, $content__value['key']);
                                    } else {
                                        $this->setInnerHtml($nodes__value, $trans);
                                        $this->addToExcludedNodes($nodes__value, 'text()');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function modifyContent($content)
    {
        if ($this->utils->getContentType($content) === 'html') {
            $content = $this->modifyHtml($content);
        } elseif ($this->utils->getContentType($content) === 'json') {
            $content = $this->modifyJson($content);
        }
        return $content;
    }

    function modifyHtml($html)
    {
        $this->setupDomDocument($html);
        $this->setLangTags();
        $this->setRtlAttr();
        $this->preloadExcludedNodes();
        $this->preloadForceTokenize();
        $this->modifyNodes();
        $html = $this->finishDomDocument($html);
        return $html;
    }

    function setupDomDocument($html)
    {
        $this->DOMDocument = new \DOMDocument();
        // if the html source doesn't contain a valid utf8 header, domdocument interprets is as iso
        // we circumvent this with mb_convert_encoding
        //@$this->DOMDocument->loadHTML($html);
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        if (mb_strpos($html, '</head>') !== false) {
            $html = str_replace(
                '</head>',
                '<!--remove--><meta http-equiv="Content-type" content="text/html; charset=utf-8" /><!--/remove--></head>',
                $html
            );
        } elseif (mb_strpos($html, '<body') !== false) {
            $html = str_replace(
                '<body',
                '<!--remove--><head><meta http-equiv="content-type" content="text/html;charset=utf-8" /></head><!--/remove--><body',
                $html
            );
        } else {
            $html =
                '<!--remove--><head><meta http-equiv="content-type" content="text/html;charset=utf-8" /></head><!--/remove-->' .
                $html;
        }
        @$this->DOMDocument->loadHTML($html);
        $this->DOMXpath = new \DOMXpath($this->DOMDocument);
    }

    function finishDomDocument($htmlOriginal)
    {
        $htmlModified = $this->DOMDocument->saveHTML();
        if (
            mb_strpos($htmlModified, '<!--remove-->') !== false &&
            mb_strpos($htmlModified, '<!--/remove-->') !== false
        ) {
            $htmlModified =
                mb_substr($htmlModified, 0, mb_strpos($htmlModified, '<!--remove-->')) .
                mb_substr($htmlModified, mb_strpos($htmlModified, '<!--/remove-->') + mb_strlen('<!--/remove-->'));
        }
        // if domdocument added previously a default header, we squish that
        if (
            mb_stripos($htmlOriginal, '<!DOCTYPE') !== 0 &&
            mb_stripos($htmlOriginal, '<html') !== 0 &&
            mb_stripos($htmlModified, '<body>') !== false &&
            mb_stripos($htmlModified, '</body>') !== false
        ) {
            $pos1 = mb_strpos($htmlModified, '<body>') + mb_strlen('<body>');
            $pos2 = mb_strpos($htmlModified, '</body>');
            $htmlModified = mb_substr($htmlModified, $pos1, $pos2 - $pos1);
            if (mb_stripos($htmlOriginal, '<p') !== 0 && mb_stripos($htmlModified, '<p') === 0) {
                $pos1 = mb_strpos($htmlModified, '<p>') + mb_strlen('<p>');
                $pos2 = mb_strpos($htmlModified, '</p>');
                $htmlModified = mb_substr($htmlModified, $pos1, $pos2 - $pos1);
            }
        }
        return $htmlModified;
    }

    function setLangTags()
    {
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }

        if ($this->settings->get('html_lang_attribute') === true) {
            $html_node = $this->DOMXpath->query('/html')[0];
            if ($html_node !== null) {
                $html_node->setAttribute('lang', $this->data->getCurrentLanguageCode());
            }
        }

        if ($this->settings->get('html_hreflang_tags') === true) {
            $head_node = $this->DOMXpath->query('/html/head')[0];
            if ($head_node !== null) {
                $data = $this->data->getLanguagePickerData();
                foreach ($data as $data__value) {
                    $tag = $this->DOMDocument->createElement('link', '');
                    $tag->setAttribute('rel', 'alternate');
                    $tag->setAttribute('hreflang', $data__value['code']);
                    $tag->setAttribute('href', $data__value['url']);
                    $head_node->appendChild($tag);
                }
            }
        }
    }

    function setRtlAttr()
    {
        if ($this->settings->isLanguageDirectionRtl($this->data->getCurrentLanguageCode())) {
            $html_node = $this->DOMXpath->query('/html')[0];
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
        return $xpath;
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

    function isInnerTagNode($node)
    {
        if (@$node->tagName == '') {
            return false;
        }
        return in_array($node->tagName, ['a', 'br', 'strong', 'b', 'small', 'i', 'span']);
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
            $inner .= $node->ownerDocument->saveXML($child);
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

    function getChildrenCountRecursivelyOfNodeTagsOnly($node)
    {
        return $this->DOMXpath->evaluate('count(.//*)', $node);
    }

    function getChildrenCountOfNode($node)
    {
        return $this->DOMXpath->evaluate('count(./node()[normalize-space()])', $node);
    }

    function getChildrenOfNode($node)
    {
        return $this->DOMXpath->query('.//node()[normalize-space()]', $node);
    }

    function getChildrenOfNodeIncludingWhitespace($node)
    {
        return $this->DOMXpath->query('.//node()', $node);
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

            // if the tokenization is forced
            if (in_array($this->getIdOfNode($parent), $this->force_tokenize)) {
                $this->group_cache[$this->getIdOfNode($parent)] = true;
            }

            // try to tokenize based on children
            foreach ($this->getChildrenOfNode($parent) as $nodes__value) {
                // exclude empty text nodes
                if ($this->isEmptyTextNode($nodes__value)) {
                    continue;
                }
                if (
                    !(
                        $this->isTextNode($nodes__value) ||
                        ($this->isInnerTagNode($nodes__value) &&
                            $this->getChildrenCountRecursivelyOfNodeTagsOnly($nodes__value) <= 2)
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

    function outputJsLocalizationHelper($translated_strings)
    {
        if (!empty($translated_strings)) {
            $translated_strings_json = [];
            foreach ($translated_strings as $translated_strings__value) {
                if (!is_array($translated_strings__value)) {
                    $context = '';
                    $orig = $translated_strings__value;
                    $trans = $this->data->getTranslationInForeignLngAndAddDynamicallyIfNeeded(
                        $translated_strings__value
                    );
                } else {
                    $context = $translated_strings__value[1];
                    $orig = $translated_strings__value[0];
                    $trans = $this->data->getTranslationInForeignLngAndAddDynamicallyIfNeeded(
                        $translated_strings__value[0],
                        null,
                        null,
                        $translated_strings__value[1]
                    );
                }
                $orig = str_replace("\r", '', $orig);
                $trans = str_replace("\r", '', $trans);
                // those chars must be escaped in a json encoded string
                $to_escape = ['\\', "\f", "\n", "\r", "\t", "\v", "\""];
                foreach ($to_escape as $to_escape__value) {
                    $orig = addcslashes($orig, $to_escape__value);
                    $trans = addcslashes($trans, $to_escape__value);
                }
                $translated_strings_json[$context][$orig] = $trans;
            }
            echo '<script data-type="translated-strings">';
            echo 'var translated_strings = JSON.parse(\'' .
                json_encode($translated_strings_json, JSON_HEX_APOS) .
                '\');';
            echo 'function gtbabel__(string, context = \'\') { if( translated_strings[context][string] !== undefined ) { return translated_strings[context][string]; } return string; }';
            echo '</script>';
        }
    }

    function modifyJson($json)
    {
        if ($this->data->sourceLngIsCurrentLng() && $this->settings->get('prefix_source_lng') === false) {
            return $json;
        }
        $json = json_decode($json);
        $this->traverseJson($json);
        $json = json_encode($json);
        return $json;
    }

    function traverseJson(&$json)
    {
        if (is_array($json) || is_object($json) || $json instanceof \Traversable) {
            foreach ($json as $json__key => &$json__value) {
                if (is_array($json__value) || is_object($json__value) || $json__value instanceof \Traversable) {
                    $this->traverseJson($json__value);
                } elseif (is_string($json__value)) {
                    $trans = $this->data->prepareTranslationAndAddDynamicallyIfNeeded(
                        $json__value,
                        $this->data->getCurrentLanguageCode(),
                        null
                    );
                    if ($trans === null) {
                        continue;
                    }
                    $json__value = $trans;
                }
            }
        }
    }
}
