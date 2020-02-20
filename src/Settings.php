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
        if ($this->args === null) {
            return null;
        }
        return $this->args->{$prop};
    }

    function setupSettings($args = [])
    {
        $default_args = [
            'languages' => $this->getDefaultLanguageCodes(),
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
            'api_stats' => true,
            'api_stats_filename' => '/api_stats.txt',
            'exclude_urls' => ['/backend'],
            'exclude_dom' => ['.lngpicker'],
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

    function getDefaultLanguages()
    {
        // https://cloud.google.com/translate/docs/languages?hl=de
        $data = [
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Français',
            'af' => 'Afrikaans',
            'am' => 'አማርኛ',
            'ar' => 'العربية',
            'az' => 'Azərbaycan',
            'be' => 'беларускі',
            'bg' => 'български',
            'bn' => 'বাঙালির',
            'bs' => 'Bosanski',
            'ca' => 'Català',
            'ceb' => 'Cebuano',
            'co' => 'Corsican',
            'cs' => 'Český',
            'cy' => 'Cymraeg',
            'da' => 'Dansk',
            'el' => 'ελληνικά',
            'eo' => 'Esperanto',
            'es' => 'Español',
            'et' => 'Eesti',
            'eu' => 'Euskal',
            'fa' => 'فارسی',
            'fi' => 'Suomalainen',
            'ga' => 'Gaeilge',
            'gd' => 'Gàidhlig',
            'gl' => 'Galego',
            'gu' => 'ગુજરાતી',
            'ha' => 'Hausa',
            'haw' => 'Hawaiian',
            'he' => 'עברי',
            'hi' => 'हिन्दी',
            'hmn' => 'Hmong',
            'hr' => 'Hrvatski',
            'ht' => 'Kreyòl',
            'hu' => 'Magyar',
            'hy' => 'հայերեն',
            'id' => 'Indonesia',
            'ig' => 'Igbo',
            'is' => 'Icelandic',
            'it' => 'Italiano',
            'ja' => '日本の',
            'jv' => 'Jawa',
            'ka' => 'ქართული',
            'kk' => 'Қазақ',
            'km' => 'ខ្មែរ',
            'kn' => 'ಕನ್ನಡ',
            'ko' => '한국의',
            'ku' => 'Kurdî',
            'ky' => 'Кыргыз',
            'la' => 'Latine',
            'lb' => 'Lëtzebuergesch',
            'lo' => 'ລາວ',
            'lt' => 'Lietuvos',
            'lv' => 'Latvijas',
            'mg' => 'Malagasy',
            'mi' => 'Maori',
            'mk' => 'македонски',
            'ml' => 'മലയാളം',
            'mn' => 'Монгол',
            'mr' => 'मराठी',
            'ms' => 'Malay',
            'mt' => 'Malti',
            'my' => 'မြန်မာ',
            'ne' => 'नेपाली',
            'nl' => 'Nederlands',
            'no' => 'Norsk',
            'ny' => 'Nyanja',
            'pa' => 'ਪੰਜਾਬੀ',
            'pl' => 'Polski',
            'ps' => 'پښتو',
            'pt' => 'Português',
            'ro' => 'Românesc',
            'ru' => 'Русский',
            'sd' => 'سنڌي',
            'si' => 'සිංහලයන්',
            'sk' => 'Slovenský',
            'sl' => 'Slovenski',
            'sm' => 'Samoa',
            'sn' => 'Shona',
            'so' => 'Soomaali',
            'sq' => 'Shqiptar',
            'sr' => 'Српски',
            'su' => 'Sunda',
            'sv' => 'Svenska',
            'ta' => 'தமிழ்',
            'te' => 'Telugu',
            'tg' => 'Тоҷикистон',
            'th' => 'ไทย',
            'tr' => 'Türk',
            'uk' => 'Український',
            'ur' => 'اردو',
            'uz' => 'O\'zbekiston',
            'vi' => 'Tiếng việt',
            'xh' => 'IsiXhosa',
            'yi' => 'ייִדיש',
            'yo' => 'Yoruba',
            'zh-cn' => '中文（简体）',
            'zh-tw' => '中文（繁體）',
            'zu' => 'Zulu'
        ];
        // if this already set (this is not the case on init, but we don't need the ordering)
        if ($this->getSourceLng() !== null) {
            $source_lng = $this->getSourceLng();
            uksort($data, function ($a, $b) use ($data, $source_lng) {
                if ($source_lng != '') {
                    if ($a === $source_lng) {
                        return -1;
                    }
                    if ($b === $source_lng) {
                        return 1;
                    }
                }
                return strnatcmp($data[$a], $data[$b]);
            });
        }
        return $data;
    }

    function getDefaultLanguageCodes()
    {
        return array_keys($this->getDefaultLanguages());
    }

    function getDefaultLanguageLabels()
    {
        return array_values($this->getDefaultLanguages());
    }

    function getLabelForLanguageCode($code)
    {
        $data = $this->getDefaultLanguages();
        if (!array_key_exists($code, $data)) {
            return '';
        }
        return $data[$code];
    }

    function getSelectedLanguageCodes()
    {
        // ordering here is irrelevant (be careful, this function gets called >100 times)
        return $this->get('languages');
    }

    function getSelectedLanguages()
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

    function getSelectedLanguagesWithoutSource()
    {
        $lng = [];
        foreach ($this->getSelectedLanguages() as $languages__key => $languages__value) {
            if ($languages__key === $this->getSourceLng()) {
                continue;
            }
            $lng[$languages__key] = $languages__value;
        }
        return $lng;
    }

    function getSourceLng()
    {
        return $this->get('lng_source');
    }

    function getSelectedLanguageCodesWithoutSource()
    {
        $lng = [];
        foreach ($this->getSelectedLanguageCodes() as $languages__value) {
            if ($languages__value === $this->getSourceLng()) {
                continue;
            }
            $lng[] = $languages__value;
        }
        return $lng;
    }
}
