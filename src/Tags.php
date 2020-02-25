<?php
namespace vielhuber\gtbabel;

class Tags
{
    public $utils;
    public $settings;

    function __construct(Utils $utils = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->settings = $settings ?: new Settings();
    }

    function catchOpeningTags($str)
    {
        if ($this->utils->getContentType($str) !== 'html') {
            return [];
        }
        preg_match_all('/<[a-zA-Z]+(>|.*?[^?]>)/', $str, $matches);
        if (empty($matches[0])) {
            return [];
        }
        return $matches[0];
    }

    function addIds($str)
    {
        foreach ($this->catchOpeningTags($str) as $matches__key => $matches__value) {
            $id = $matches__key + 1;
            $pos = mb_strrpos($matches__value, '>');
            $new = mb_substr($matches__value, 0, $pos) . ' p="' . $id . '"' . mb_substr($matches__value, $pos);
            $str = __str_replace_first($matches__value, $new, $str);
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
            $has_no_translate_attribute = false;
            $attributes = explode(' ', trim($attributes_cur));
            foreach ($attributes as $attributes__key => $attributes__value) {
                if (
                    strpos($attributes__value, 'class="') !== false &&
                    strpos($attributes__value, 'notranslate') !== false
                ) {
                    $has_no_translate_attribute = true;
                }
                if (strpos($attributes__value, 'p="') === 0 && $attributes__value !== 'p="' . $id . '"') {
                    continue;
                }
                unset($attributes[$attributes__key]);
            }
            if ($has_no_translate_attribute === true) {
                $attributes[] = 'class="notranslate"';
            }
            if (!empty($attributes)) {
                $attributes = ' ' . implode(' ', $attributes);
            } else {
                $attributes = '';
            }
            $new = str_replace($attributes_cur, $attributes, $matches__value);
            $str = __str_replace_first($matches__value, $new, $str);
        }
        return $str;
    }

    function removeAttributesAndSaveMapping($str)
    {
        $mappingTable = [];
        foreach ($this->catchOpeningTags($str) as $matches__key => $matches__value) {
            $id = $matches__key + 1;
            $pos_end = mb_strrpos($matches__value, '>');
            if (mb_strpos($matches__value, ' ') !== false) {
                $pos_begin = mb_strpos($matches__value, ' ');
            } else {
                $pos_begin = $pos_end;
            }
            $attributes = mb_substr($matches__value, $pos_begin, $pos_end - $pos_begin);
            $mappingTable[$id] = trim($attributes);
            $has_no_translate_attribute = false;
            foreach (explode(' ', $attributes) as $attributes__value) {
                if (
                    strpos($attributes__value, 'class="') !== false &&
                    strpos($attributes__value, 'notranslate') !== false
                ) {
                    $has_no_translate_attribute = true;
                }
            }
            $replacement = '';
            if ($has_no_translate_attribute === true) {
                $replacement = ' class="notranslate"';
            }
            $new = str_replace($attributes, $replacement, $matches__value);
            $str = __str_replace_first($matches__value, $new, $str);
        }
        return [$str, $mappingTable];
    }

    function addAttributesAndRemoveIds($str, $mappingTable)
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
            if (array_key_exists($id, $mappingTable)) {
                $attributes_restored = $mappingTable[$id];
                $pos = mb_strrpos($new, '>');
                $new = mb_substr($new, 0, $pos) . ' ' . $attributes_restored . mb_substr($new, $pos);
            }

            $str = __str_replace_first($matches__value, $new, $str);
        }
        return $str;
    }
}
