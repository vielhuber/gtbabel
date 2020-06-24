<?php
namespace vielhuber\gtbabel;

class Settings
{
    public $args;

    function setup($args = [])
    {
        $args = $this->setupArgs($args);
        $args = $this->setupSettings($args);
        $args = $this->setupCachedSettings($args);
        $args = (object) $args;
        $this->args = $args;
    }

    function set($prop, $value)
    {
        $this->args->{$prop} = $value;
    }

    function get($prop)
    {
        if ($this->args === null) {
            return null;
        }
        return $this->args->{$prop};
    }

    function setupArgs($args)
    {
        if ($args === null || $args === true || $args === false || $args == '') {
            return [];
        }
        if (is_array($args)) {
            return $args;
        }
        if (is_string($args) && file_exists($args)) {
            $arr = json_decode(file_Get_contents($args), true);
            if ($arr === true || $arr === false || $arr === null || $arr == '' || !is_array($arr)) {
                return [];
            }
            return $arr;
        }
    }

    function setupSettings($args = [])
    {
        $default_args = [
            'languages' => $this->getDefaultLanguages(),
            'lng_source' => 'de',
            'lng_target' => null,
            'database' => [
                'type' => 'sqlite',
                'filename' => 'data.db',
                'table' => 'translations'
            ],
            'log_folder' => '/logs',
            'prefix_source_lng' => true,
            'redirect_root_domain' => 'browser',
            'translate_default_tag_nodes' => true,
            'html_lang_attribute' => true,
            'html_hreflang_tags' => true,
            'debug_translations' => false,
            'auto_add_translations' => true,
            'auto_set_new_strings_checked' => false,
            'auto_set_discovered_strings_checked' => false,
            'only_show_checked_strings' => true,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => [],
            'microsoft_translation_api_key' => [],
            'deepl_translation_api_key' => [],
            'stats_log' => true,
            'discovery_log' => false,
            'prevent_publish' => true,
            'prevent_publish_urls' => [
                '/path/in/source/lng/to/specific/page' => ['en', 'fr'],
                '/slug1/*' => ['en'],
                '/slug1/*/slug2' => ['fr']
                //'/*' => ['en', 'fr'] // disable whole languages
            ],
            'exclude_urls' => ['/backend'],
            'exclude_dom' => ['.notranslate', '.lngpicker'],
            'force_tokenize' => ['.force-tokenize'],
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

    function setupCachedSettings($args)
    {
        $args['languages_codes'] = array_map(function ($languages__value) {
            return $languages__value['code'];
        }, $args['languages']);
        return $args;
    }

    function getDefaultLanguages()
    {
        // https://cloud.google.com/translate/docs/languages?hl=de
        $data = [
            ['code' => 'de', 'label' => 'Deutsch', 'rtl' => false],
            ['code' => 'en', 'label' => 'English', 'rtl' => false],
            ['code' => 'fr', 'label' => 'Français', 'rtl' => false],
            ['code' => 'af', 'label' => 'Afrikaans', 'rtl' => false],
            ['code' => 'am', 'label' => 'አማርኛ', 'rtl' => false],
            ['code' => 'ar', 'label' => 'العربية', 'rtl' => true],
            ['code' => 'az', 'label' => 'Azərbaycan', 'rtl' => false],
            ['code' => 'be', 'label' => 'беларускі', 'rtl' => false],
            ['code' => 'bg', 'label' => 'български', 'rtl' => false],
            ['code' => 'bn', 'label' => 'বাঙালির', 'rtl' => false],
            ['code' => 'bs', 'label' => 'Bosanski', 'rtl' => false],
            ['code' => 'ca', 'label' => 'Català', 'rtl' => false],
            ['code' => 'ceb', 'label' => 'Cebuano', 'rtl' => false],
            ['code' => 'co', 'label' => 'Corsican', 'rtl' => false],
            ['code' => 'cs', 'label' => 'Český', 'rtl' => false],
            ['code' => 'cy', 'label' => 'Cymraeg', 'rtl' => false],
            ['code' => 'da', 'label' => 'Dansk', 'rtl' => false],
            ['code' => 'el', 'label' => 'ελληνικά', 'rtl' => false],
            ['code' => 'eo', 'label' => 'Esperanto', 'rtl' => false],
            ['code' => 'es', 'label' => 'Español', 'rtl' => false],
            ['code' => 'et', 'label' => 'Eesti', 'rtl' => false],
            ['code' => 'eu', 'label' => 'Euskal', 'rtl' => false],
            ['code' => 'fa', 'label' => 'فارسی', 'rtl' => true],
            ['code' => 'fi', 'label' => 'Suomalainen', 'rtl' => false],
            ['code' => 'ga', 'label' => 'Gaeilge', 'rtl' => false],
            ['code' => 'gd', 'label' => 'Gàidhlig', 'rtl' => false],
            ['code' => 'gl', 'label' => 'Galego', 'rtl' => false],
            ['code' => 'gu', 'label' => 'ગુજરાતી', 'rtl' => false],
            ['code' => 'ha', 'label' => 'Hausa', 'rtl' => true],
            ['code' => 'haw', 'label' => 'Hawaiian', 'rtl' => false],
            ['code' => 'he', 'label' => 'עברי', 'rtl' => true],
            ['code' => 'hi', 'label' => 'हिन्दी', 'rtl' => false],
            ['code' => 'hmn', 'label' => 'Hmong', 'rtl' => false],
            ['code' => 'hr', 'label' => 'Hrvatski', 'rtl' => false],
            ['code' => 'ht', 'label' => 'Kreyòl', 'rtl' => false],
            ['code' => 'hu', 'label' => 'Magyar', 'rtl' => false],
            ['code' => 'hy', 'label' => 'հայերեն', 'rtl' => false],
            ['code' => 'id', 'label' => 'Indonesia', 'rtl' => false],
            ['code' => 'ig', 'label' => 'Igbo', 'rtl' => false],
            ['code' => 'is', 'label' => 'Icelandic', 'rtl' => false],
            ['code' => 'it', 'label' => 'Italiano', 'rtl' => false],
            ['code' => 'ja', 'label' => '日本の', 'rtl' => false],
            ['code' => 'jv', 'label' => 'Jawa', 'rtl' => false],
            ['code' => 'ka', 'label' => 'ქართული', 'rtl' => false],
            ['code' => 'kk', 'label' => 'Қазақ', 'rtl' => false],
            ['code' => 'km', 'label' => 'ខ្មែរ', 'rtl' => false],
            ['code' => 'kn', 'label' => 'ಕನ್ನಡ', 'rtl' => false],
            ['code' => 'ko', 'label' => '한국의', 'rtl' => false],
            ['code' => 'ku', 'label' => 'Kurdî', 'rtl' => true],
            ['code' => 'ky', 'label' => 'Кыргыз', 'rtl' => false],
            ['code' => 'la', 'label' => 'Latine', 'rtl' => false],
            ['code' => 'lb', 'label' => 'Lëtzebuergesch', 'rtl' => false],
            ['code' => 'lo', 'label' => 'ລາວ', 'rtl' => false],
            ['code' => 'lt', 'label' => 'Lietuvos', 'rtl' => false],
            ['code' => 'lv', 'label' => 'Latvijas', 'rtl' => false],
            ['code' => 'mg', 'label' => 'Malagasy', 'rtl' => false],
            ['code' => 'mi', 'label' => 'Maori', 'rtl' => false],
            ['code' => 'mk', 'label' => 'македонски', 'rtl' => false],
            ['code' => 'ml', 'label' => 'മലയാളം', 'rtl' => false],
            ['code' => 'mn', 'label' => 'Монгол', 'rtl' => false],
            ['code' => 'mr', 'label' => 'मराठी', 'rtl' => false],
            ['code' => 'ms', 'label' => 'Malay', 'rtl' => false],
            ['code' => 'mt', 'label' => 'Malti', 'rtl' => false],
            ['code' => 'my', 'label' => 'မြန်မာ', 'rtl' => false],
            ['code' => 'ne', 'label' => 'नेपाली', 'rtl' => false],
            ['code' => 'nl', 'label' => 'Nederlands', 'rtl' => false],
            ['code' => 'no', 'label' => 'Norsk', 'rtl' => false],
            ['code' => 'ny', 'label' => 'Nyanja', 'rtl' => false],
            ['code' => 'pa', 'label' => 'ਪੰਜਾਬੀ', 'rtl' => false],
            ['code' => 'pl', 'label' => 'Polski', 'rtl' => false],
            ['code' => 'ps', 'label' => 'پښتو', 'rtl' => true],
            ['code' => 'pt', 'label' => 'Português', 'rtl' => false],
            ['code' => 'ro', 'label' => 'Românesc', 'rtl' => false],
            ['code' => 'ru', 'label' => 'Русский', 'rtl' => false],
            ['code' => 'sd', 'label' => 'سنڌي', 'rtl' => false],
            ['code' => 'si', 'label' => 'සිංහලයන්', 'rtl' => false],
            ['code' => 'sk', 'label' => 'Slovenský', 'rtl' => false],
            ['code' => 'sl', 'label' => 'Slovenski', 'rtl' => false],
            ['code' => 'sm', 'label' => 'Samoa', 'rtl' => false],
            ['code' => 'sn', 'label' => 'Shona', 'rtl' => false],
            ['code' => 'so', 'label' => 'Soomaali', 'rtl' => false],
            ['code' => 'sq', 'label' => 'Shqiptar', 'rtl' => false],
            ['code' => 'sr', 'label' => 'Српски', 'rtl' => false],
            ['code' => 'su', 'label' => 'Sunda', 'rtl' => false],
            ['code' => 'sv', 'label' => 'Svenska', 'rtl' => false],
            ['code' => 'ta', 'label' => 'தமிழ்', 'rtl' => false],
            ['code' => 'te', 'label' => 'Telugu', 'rtl' => false],
            ['code' => 'tg', 'label' => 'Тоҷикистон', 'rtl' => false],
            ['code' => 'th', 'label' => 'ไทย', 'rtl' => false],
            ['code' => 'tr', 'label' => 'Türk', 'rtl' => false],
            ['code' => 'uk', 'label' => 'Український', 'rtl' => false],
            ['code' => 'ur', 'label' => 'اردو', 'rtl' => true],
            ['code' => 'uz', 'label' => 'O\'zbekiston', 'rtl' => false],
            ['code' => 'vi', 'label' => 'Tiếng việt', 'rtl' => false],
            ['code' => 'xh', 'label' => 'IsiXhosa', 'rtl' => false],
            ['code' => 'yi', 'label' => 'ייִדיש', 'rtl' => true],
            ['code' => 'yo', 'label' => 'Yoruba', 'rtl' => false],
            ['code' => 'zh-cn', 'label' => '中文（简体）', 'rtl' => false],
            ['code' => 'zh-tw', 'label' => '中文（繁體）', 'rtl' => false],
            ['code' => 'zu', 'label' => 'Zulu', 'rtl' => false]
        ];
        // if this already set (this is not the case on init, but we don't need the ordering)
        if ($this->getSourceLanguageCode() !== null) {
            $source_lng = $this->getSourceLanguageCode();
            usort($data, function ($a, $b) use ($source_lng) {
                if ($source_lng != '') {
                    if ($a['code'] === $source_lng) {
                        return -1;
                    }
                    if ($b['code'] === $source_lng) {
                        return 1;
                    }
                }
                return strnatcmp($a['label'], $b['label']);
            });
        }
        return $data;
    }

    function isLanguageDirectionRtl($lng)
    {
        return !empty(
            array_filter($this->getSelectedLanguages(), function ($languages__value) use ($lng) {
                return $languages__value['code'] == $lng &&
                    isset($languages__value['rtl']) &&
                    $languages__value['rtl'] === true;
            })
        );
    }

    function getDefaultLanguageCodes()
    {
        return array_map(function ($languages__value) {
            return $languages__value['code'];
        }, $this->getDefaultLanguages());
    }

    function getDefaultLanguageLabels()
    {
        return array_map(function ($languages__value) {
            return $languages__value['label'];
        }, $this->getDefaultLanguages());
    }

    function getLabelForLanguageCode($code)
    {
        $languages = $this->getDefaultLanguages();
        foreach ($languages as $languages__value) {
            if ($languages__value['code'] === $code) {
                return $languages__value['label'];
            }
        }
        return '';
    }

    function getLanguageDataForCode($code)
    {
        $languages = $this->getDefaultLanguages();
        foreach ($languages as $languages__value) {
            if ($languages__value['code'] === $code) {
                return $languages__value;
            }
        }
        return null;
    }

    function getSelectedLanguages()
    {
        return $this->get('languages');
    }

    function getSelectedLanguageCodes()
    {
        // be careful, this function gets called >100 times
        // therefore we use the cached arg "languages_codes"
        return $this->get('languages_codes');
    }

    function getSelectedLanguageCodesLabels()
    {
        $return = [];
        // use order of default languages
        $selected = $this->getSelectedLanguageCodes();
        $data = $this->getDefaultLanguageCodes();
        foreach ($data as $data__key => $data__value) {
            if (!in_array($data__value, $selected)) {
                unset($data[$data__key]);
            }
        }
        $data = array_values($data);
        foreach ($data as $data__value) {
            $return[$data__value] = $this->getLabelForLanguageCode($data__value);
        }
        return $return;
    }

    function getSelectedLanguagesCodesLabelsWithoutSource()
    {
        $lng = [];
        foreach ($this->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            if ($languages__key === $this->getSourceLanguageCode()) {
                continue;
            }
            $lng[$languages__key] = $languages__value;
        }
        return $lng;
    }

    function getSourceLanguageCode()
    {
        return $this->get('lng_source');
    }

    function getSourceLanguageLabel()
    {
        return $this->getLabelForLanguageCode($this->getSourceLanguageCode());
    }

    function getSelectedLanguageCodesWithoutSource()
    {
        $lng = [];
        foreach ($this->getSelectedLanguageCodes() as $languages__value) {
            if ($languages__value === $this->getSourceLanguageCode()) {
                continue;
            }
            $lng[] = $languages__value;
        }
        return $lng;
    }
}
