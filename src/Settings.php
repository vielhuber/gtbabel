<?php
namespace vielhuber\gtbabel;

class Settings
{
    public $args;

    function set($args = [])
    {
        $args = $this->getDefaultSettings($args);
        $args = (object) $args;
        $this->args = $args;
    }

    function get($prop)
    {
        return $this->args->{$prop};
    }

    function shouldBeResetted()
    {
        if (@$_GET['gtbabel_reset'] == 1) {
            return true;
        }
        return false;
    }

    function getDefaultSettings($args = [])
    {
        $default_args = [
            'languages' => gtbabel_default_languages(),
            'lng_folder' => '/locales',
            'lng_source' => 'de',
            'lng_target' => null,
            'prefix_source_lng' => true,
            'translate_text_nodes' => true,
            'translate_default_tag_nodes' => true,
            'debug_mode' => false,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'exclude_urls' => ['/backend'],
            'exclude_dom' => ['.lngpicker'],
            'include' => [
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
        foreach ($args as $args__key => $args__value) {
            $default_args[$args__key] = $args__value;
        }
        return $default_args;
    }
}
