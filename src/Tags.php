<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;

class Tags
{
    public $utils;

    function __construct(Utils $utils = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
    }

    function catchOpeningTags($str)
    {
        if ($this->utils->getContentType($str) === 'json') {
            return [];
        }
        preg_match_all('/<[a-zA-Z]+(>|.*?[^?]>)/', $str, $matches);
        if (empty($matches[0])) {
            return [];
        }
        return $matches[0];
    }

    function catchInlineLinks($str)
    {
        preg_match_all(
            '(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s\<]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s\<]{2,})',
            $str,
            $match
        );
        if (empty($match[0])) {
            return [];
        }
        return $match[0];
    }

    function catchInlineLinksPlaceholders($str)
    {
        preg_match_all('({[0-9]+})', $str, $match);
        if (empty($match[0])) {
            return [];
        }
        return $match[0];
    }

    function addIds($str)
    {
        foreach ($this->catchOpeningTags($str) as $matches__key => $matches__value) {
            $id = $matches__key + 1;
            // consider tags like <br/>
            $pos = mb_strrpos($matches__value, '/>');
            $shift = true;
            if ($pos === false) {
                $pos = mb_strrpos($matches__value, '>');
                $shift = false;
            }
            $new =
                mb_substr($matches__value, 0, $pos) .
                ' p="' .
                $id .
                '"' .
                ($shift === true ? ' ' : '') .
                mb_substr($matches__value, $pos);
            $str = __::str_replace_first($matches__value, $new, $str);
        }
        return $str;
    }

    function removeAttributesExceptIrregularIds($str)
    {
        foreach ($this->catchOpeningTags($str) as $matches__key => $matches__value) {
            $id = $matches__key + 1;
            $pos_end = mb_strrpos($matches__value, '>');
            if (mb_strpos($matches__value, ' ') !== false) {
                $pos_begin = mb_strpos($matches__value, ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes_cur = mb_substr($matches__value, $pos_begin, $pos_end - $pos_begin);
            $has_notranslate_attribute = false;
            $attributes = explode(' ', trim($attributes_cur));
            foreach ($attributes as $attributes__key => $attributes__value) {
                if (
                    strpos($attributes__value, 'class="') !== false &&
                    strpos($attributes__value, 'notranslate') !== false
                ) {
                    $has_notranslate_attribute = true;
                }
                if (strpos($attributes__value, 'p="') === 0 && $attributes__value !== 'p="' . $id . '"') {
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
            $new = str_replace($attributes_cur, $attributes, $matches__value);
            $str = __::str_replace_first($matches__value, $new, $str);
        }
        return $str;
    }

    function removeAttributes($str)
    {
        $mappingTableTags = [];
        foreach ($this->catchOpeningTags($str) as $matches__key => $matches__value) {
            $id = $matches__key + 1;
            $pos_end = mb_strrpos($matches__value, '>');
            if (mb_strpos($matches__value, ' ') !== false) {
                $pos_begin = mb_strpos($matches__value, ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes = mb_substr($matches__value, $pos_begin, $pos_end - $pos_begin);
            $mappingTableTags[$id] = trim($attributes);
            $has_notranslate_attribute = false;
            if (preg_match('/class="[^"]*?notranslate[^"]*?"/', $attributes)) {
                $has_notranslate_attribute = true;
            }
            $replacement = '';
            if ($has_notranslate_attribute === true) {
                $replacement = ' class="notranslate"';
            }
            $new = str_replace($attributes, $replacement, $matches__value);
            $str = __::str_replace_first($matches__value, $new, $str);
        }
        return [$str, $mappingTableTags];
    }

    function removeInlineLinks($str)
    {
        $mappingTableInlineLinks = [];
        foreach ($this->catchInlineLinks($str) as $matches__key => $matches__value) {
            $id = $matches__key + 1;
            $mappingTableInlineLinks[$id] = $matches__value;
            $str = __::str_replace_first($matches__value, '{' . $id . '}', $str);
        }
        return [$str, $mappingTableInlineLinks];
    }

    function removePrefixSuffix($str)
    {
        $prefix = '';
        $suffix = '';
        $prefix_pattern = '';
        $prefix_pattern .= '^<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>((\*|-|–|\||:|\+|•|●|,|I| | )*)<\/\1>( *)'; // <span>*</span> etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^<br+\b[^>]*\/?>( *)'; // <br/> etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^(\*|-|–|\||:|\+|•|●)( +)'; // * and space etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^(\*|–|:|•|●)'; // * etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^((\d|[a-z])\))( +)'; // 1) 2) 3) a) b) c) etc.
        $prefix_pattern .= '|';
        $prefix_pattern .= '^(\.\.\.|…)( *)'; // ...
        $suffix_pattern = '';
        $suffix_pattern .= ' *<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>((\*|-|–|\||:|\+|•|●|,|I| | )*)<\/\1>$'; // <span>*</span> etc.
        $suffix_pattern .= '|';
        $suffix_pattern .= '( *)<br+\b[^>]*\/?>$'; // <br/> etc.
        $suffix_pattern .= '|';
        $suffix_pattern .= '( *)(\*|-|–|\||:|•|●)$'; // * etc.
        $suffix_pattern .= '|';
        $suffix_pattern .= '( *)(\.\.\.|…)$'; // ...
        $suffix_pattern .= '|';
        $suffix_pattern .= '( *)(: \d+)$'; // : ZAHL
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
        foreach ($this->catchOpeningTags($str) as $matches__key => $matches__value) {
            $new = $matches__value;

            // get id
            $id = $matches__key + 1;
            $pos_end = mb_strrpos($matches__value, '>');
            if (mb_strpos($matches__value, ' ') !== false) {
                $pos_begin = mb_strpos($matches__value, ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes = mb_substr($matches__value, $pos_begin, $pos_end - $pos_begin);
            foreach (explode(' ', $attributes) as $attributes__value) {
                if (mb_strpos($attributes__value, 'p="') !== false) {
                    $id = str_replace(['p="', '"'], '', $attributes__value);
                }
            }

            // remove all attributes (id and class="notranslate")
            $new = str_replace($attributes, '', $new);

            // restore attributes
            if (array_key_exists($id, $mappingTableTags)) {
                $attributes_restored = $mappingTableTags[$id];
                $pos = mb_strrpos($new, '>');
                $new = mb_substr($new, 0, $pos) . ' ' . $attributes_restored . mb_substr($new, $pos);
            }

            $str = __::str_replace_first($matches__value, $new, $str);
        }
        return $str;
    }

    function addInlineLinks($str, $mappingTableInlineLinks)
    {
        foreach ($this->catchInlineLinksPlaceholders($str) as $matches__value) {
            $id = str_replace(['{', '}'], '', $matches__value);
            if (!array_key_exists($id, $mappingTableInlineLinks)) {
                continue;
            }
            $link = $mappingTableInlineLinks[$id];
            $str = __::str_replace_first($matches__value, $link, $str);
        }
        return $str;
    }
}
