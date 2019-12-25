<?php
namespace vielhuber\gtbabel;

class Dom
{
    public $DOMDocument;
    public $DOMXpath;

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
                foreach (
                    $parts__value_parts
                    as $parts__value_parts__key => $parts__value_parts__value
                ) {
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

    function getIdOfNode($node)
    {
        return $node->getNodePath();
    }

    function isInnerTagNode($node)
    {
        if (@$node->tagName == '') {
            return false;
        }
        return in_array($node->tagName, ['a', 'br', 'strong', 'b', 'small', 'i']);
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
        return $this->DOMXpath->evaluate('count(./node())', $node);
    }

    function getChildrenOfNode($node)
    {
        return $this->DOMXpath->query('.//node()', $node);
    }

    function getParentNodeWithMoreThanOneChildren($node)
    {
        $cur = $node;
        $level = 0;
        $max_level = 11;
        while ($this->getChildrenCountOfNode($cur) <= 1) {
            $cur = $cur->parentNode;
            if ($cur === null) {
                break;
            }
            $level++;
            if ($level >= $max_level) {
                break;
            }
        }
        return $cur;
    }

    function getNearestLogicalGroup($node)
    {
        $parent = $this->getParentNodeWithMoreThanOneChildren($node);
        if (!array_key_exists($this->getIdOfNode($parent), $this->group_cache)) {
            $this->group_cache[$this->getIdOfNode($parent)] = false;
            foreach ($this->getChildrenOfNode($parent) as $nodes__value) {
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
