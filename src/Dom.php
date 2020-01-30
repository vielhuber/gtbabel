<?php
namespace vielhuber\gtbabel;

class Dom
{
    public $DOMDocument;
    public $DOMXpath;

    public $excluded_nodes;
    public $group_cache;

    public $utils;
    public $gettext;
    public $host;
    public $settings;

    function __construct(Utils $utils = null, Gettext $gettext = null, Host $host = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->gettext = $gettext ?: new Gettext();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
    }

    function preloadExcludedNodes()
    {
        $this->excluded_nodes = [];
        if ($this->settings->get('exclude_dom') !== null) {
            foreach ($this->settings->get('exclude_dom') as $exclude__value) {
                $nodes = $this->DOMXpath->query($this->transformSelectorToXpath($exclude__value));
                foreach ($nodes as $nodes__value) {
                    $this->excluded_nodes[$this->getIdOfNode($nodes__value)] = true;
                    foreach ($this->getChildrenOfNode($nodes__value) as $nodes__value__value) {
                        $this->excluded_nodes[$this->getIdOfNode($nodes__value__value)] = true;
                    }
                }
            }
        }
    }

    function modifyTextNodes()
    {
        if ($this->settings->get('translate_text_nodes') === false) {
            return;
        }
        if ($this->gettext->sourceLngIsCurrentLng()) {
            return;
        }

        $groups = [];

        $to_delete = [];
        $textnodes = $this->DOMXpath->query('/html/body//text()');
        foreach ($textnodes as $textnodes__value) {
            if (array_key_exists($this->getIdOfNode($textnodes__value), $this->excluded_nodes)) {
                continue;
            }
            if ($this->gettext->stringShouldNotBeTranslated($textnodes__value->nodeValue)) {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'script') {
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

        foreach ($groups as $groups__value) {
            if ($this->isTextNode($groups__value)) {
                $originalText = $groups__value->nodeValue;
            } else {
                $originalText = $this->getInnerHtml($groups__value);
            }

            $originalText = $this->gettext->formatTextFromTextNode($originalText);

            [$originalTextWithPlaceholders, $mappingTable] = $this->gettext->placeholderConversionIn($originalText);

            $translatedTextWithPlaceholders = $this->gettext->prepareTranslationAndAddDynamicallyIfNeeded(
                $originalTextWithPlaceholders,
                $this->gettext->getCurrentLng()
            );

            $translatedText = $this->gettext->placeholderConversionOut($translatedTextWithPlaceholders, $mappingTable);

            if ($this->isTextNode($groups__value)) {
                $groups__value->nodeValue = $translatedText;
            } else {
                $this->setInnerHtml($groups__value, $translatedText);
            }
        }
    }

    function modifyTagNodes()
    {
        if ($this->gettext->sourceLngIsCurrentLng() && $this->settings->get('prefix_source_lng') === false) {
            return;
        }

        $include = [];

        if ($this->settings->get('translate_default_tag_nodes') === true) {
            $include = array_merge($include, [
                [
                    'selector' => 'a',
                    'attribute' => 'href',
                    'context' => 'slug'
                ],
                [
                    'selector' => 'form',
                    'attribute' => 'action',
                    'context' => 'slug'
                ],
                [
                    'selector' => 'img',
                    'attribute' => 'alt',
                    'context' => null
                ],
                [
                    'selector' => 'input',
                    'attribute' => 'placeholder',
                    'context' => null
                ],
                [
                    'selector' => 'head title',
                    'attribute' => null,
                    'context' => 'title'
                ],
                [
                    'selector' => 'head meta[name="description"]',
                    'attribute' => 'content',
                    'context' => null
                ]
            ]);
        }

        $include = array_merge($include, $this->settings->get('include_dom'));

        foreach ($include as $include__value) {
            $nodes = $this->DOMXpath->query($this->transformSelectorToXpath($include__value['selector']));
            if (!empty($nodes)) {
                foreach ($nodes as $nodes__value) {
                    if (array_key_exists($this->getIdOfNode($nodes__value), $this->excluded_nodes)) {
                        continue;
                    }
                    if (@$include__value['attribute'] != '') {
                        $value = $nodes__value->getAttribute($include__value['attribute']);
                    } else {
                        $value = $nodes__value->nodeValue;
                    }
                    if ($value != '') {
                        $context = null;
                        if (@$include__value['context'] != '') {
                            $context = $include__value['context'];
                        }
                        if ($include__value['selector'] === 'a' && $include__value['attribute'] === 'href') {
                            $context = 'slug';
                        }
                        if (strpos($value, $this->host->getCurrentHost()) === 0) {
                            $context = 'slug';
                        }

                        if ($context === 'slug' && $this->host->urlIsExcluded($value)) {
                            continue;
                        }

                        if ($this->gettext->sourceLngIsCurrentLng()) {
                            if ($context === 'slug') {
                                if ($value === null || trim($value) === '' || strpos($value, '#') === 0) {
                                    continue;
                                }
                                $is_absolute_link = strpos($value, $this->host->getCurrentHost()) === 0;
                                if (strpos($value, 'http') !== false && $is_absolute_link === false) {
                                    continue;
                                }
                                if (strpos($value, 'http') === false && strpos($value, ':') !== false) {
                                    continue;
                                }
                                $value = str_replace(
                                    [
                                        $this->host->getCurrentHost() . '/' . $this->gettext->getSourceLng(),
                                        $this->host->getCurrentHost()
                                    ],
                                    '',
                                    $value
                                );
                                $value = '/' . $this->gettext->getCurrentLng() . '' . $value;
                                if ($is_absolute_link === true) {
                                    $value = $this->host->getCurrentHost() . $value;
                                }
                                $trans = $value;
                            } else {
                                continue;
                            }
                        } else {
                            $trans = $this->gettext->prepareTranslationAndAddDynamicallyIfNeeded(
                                $value,
                                $this->gettext->getCurrentLng(),
                                $context
                            );
                        }

                        if (@$include__value['attribute'] != '') {
                            $nodes__value->setAttribute($include__value['attribute'], $trans);
                        } else {
                            $nodes__value->nodeValue = $trans;
                        }
                    }
                }
            }
        }
    }

    function modifyHtml($html)
    {
        $this->setupDomDocument($html);
        $this->setLangTags();
        $this->preloadExcludedNodes();
        $this->modifyTextNodes();
        $this->modifyTagNodes();
        return $this->DOMDocument->saveHTML();
    }

    function setupDomDocument($html)
    {
        $this->DOMDocument = new \DOMDocument();

        // if the html source doesn't contain a valid utf8 header, domdocument interprets is as iso
        // we circumvent this with mb_convert_encoding
        //@$this->DOMDocument->loadHTML($html);
        @$this->DOMDocument->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $this->DOMXpath = new \DOMXpath($this->DOMDocument);
    }

    function setLangTags()
    {
        if ($this->settings->get('html_lang_attribute') === true) {
            $html_node = $this->DOMXpath->query('/html')[0];
            if ($html_node !== null) {
                $html_node->setAttribute('lang', $this->gettext->getCurrentLng());
            }
        }

        if ($this->settings->get('html_hreflang_tags') === true) {
            $head_node = $this->DOMXpath->query('/html/head')[0];
            if ($head_node !== null) {
                $data = $this->gettext->getLanguagePickerData();
                foreach ($data as $data__value) {
                    $tag = $this->DOMDocument->createElement('link', '');
                    $tag->setAttribute('rel', 'alternate');
                    $tag->setAttribute('hreflang', $data__value['lng']);
                    $tag->setAttribute('href', $data__value['url']);
                    $head_node->appendChild($tag);
                }
            }
        }
    }

    function transformSelectorToXpath($selector)
    {
        $xpath = './/';

        $parts = explode(' ', $selector);
        foreach ($parts as $parts__key => $parts__value) {
            // input[placeholder] => input[@placeholder]
            if (strpos($parts__value, '[') !== false) {
                $parts__value = str_replace('[', '[@', $parts__value);
            }
            // .foo => *[contains(concat(" ", normalize-space(@class), " "), " foo ")]
            if (strpos($parts__value, '.') !== false) {
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
}
