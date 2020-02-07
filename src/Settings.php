<?php
namespace vielhuber\gtbabel;

class Settings
{
    public $args;

    function set($args = [])
    {
        $args = $this->setupSettings($args);
        $args = (object) $args;
        $this->args = $args;
    }

    function get($prop)
    {
        return $this->args->{$prop};
    }

    function setupSettings($args = [])
    {
        $default_args = [
            'languages' => gtbabel_default_languages(),
            'lng_source' => 'de',
            'lng_target' => null,
            'lng_folder' => '/locales',
            'prefix_source_lng' => true,
            'translate_text_nodes' => true,
            'translate_default_tag_nodes' => true,
            'html_lang_attribute' => true,
            'html_hreflang_tags' => true,
            'debug_translations' => false,
            'auto_add_translations_to_gettext' => false,
            'auto_translation' => true,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'microsoft_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'exclude_urls' => ['/backend'],
            'exclude_dom' => ['.lngpicker'],
            'include_dom' => [
                [
                    'selector' => '.search-submit',
                    'attribute' => 'value'
                ],
                [
                    'selector' => '.js-link',
                    'attribute' => 'alt-href',
                    'context' => 'slug'
                ]
            ]
        ];
        if (!empty($args)) {
            foreach ($args as $args__key => $args__value) {
                if ($args__value === '1') {
                    $args__value = true;
                }
                if ($args__value === '0') {
                    $args__value = false;
                }
                $default_args[$args__key] = $args__value;
            }
        }
        return $default_args;
    }
}
