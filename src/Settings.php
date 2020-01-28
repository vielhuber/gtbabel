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
            'debug_mode' => false,
            'auto_translation' => true,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
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
