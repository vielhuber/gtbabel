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
        $args = $args;
        $this->args = $args;
    }

    function set($prop, $value)
    {
        $this->args[$prop] = $value;
    }

    function get($prop)
    {
        if ($this->args === null) {
            return null;
        }
        return $this->args[$prop];
    }

    function setupArgs($args)
    {
        if ($args === null || $args === true || $args === false || $args == '') {
            return [];
        }
        if (is_array($args)) {
            return $args;
        }
        if (is_object($args)) {
            return (array) $args;
        }
        if (is_string($args) && file_exists($args)) {
            $arr = json_decode(file_get_contents($args), true);
            if ($arr === true || $arr === false || $arr === null || $arr == '' || !is_array($arr)) {
                return [];
            }
            return $arr;
        }
    }

    function getSettings()
    {
        return $this->args;
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
            'redirect_root_domain' => 'browser',
            'translate_html' => true,
            'translate_xml' => true,
            'translate_xml_include' => [
                [
                    'selector' => '//*[name()=\'loc\']',
                    'attribute' => null,
                    'context' => 'slug'
                ]
            ],
            'translate_json' => true,
            'translate_json_include' => [
                '/path/in/source/lng/to/specific/page' => ['key'],
                'wp-json/v1/*/endpoint' => ['key', 'nested.key', 'key.with.*.wildcard']
            ],
            'html_lang_attribute' => true,
            'html_hreflang_tags' => true,
            'xml_hreflang_tags' => true,
            'debug_translations' => false,
            'auto_add_translations' => true,
            'auto_set_new_strings_checked' => false,
            'auto_set_discovered_strings_checked' => false,
            'only_show_checked_strings' => false,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => [],
            'microsoft_translation_api_key' => [],
            'deepl_translation_api_key' => [],
            'google_throttle_chars_per_month' => 1000000,
            'microsoft_throttle_chars_per_month' => 1000000,
            'deepl_throttle_chars_per_month' => 1000000,
            'discovery_log' => false,
            'prevent_publish' => true,
            'prevent_publish_urls' => [
                '/path/in/source/lng/to/specific/page' => ['en', 'fr'],
                '/slug1/*' => ['en'],
                '/slug1/*/slug2' => ['fr']
            ],
            'alt_lng_urls' => [
                '/path/in/source/lng/to/specific/page' => 'en'
            ],
            'exclude_urls_content' => ['backend'],
            'exclude_urls_slugs' => ['api/v1.0'],
            'exclude_dom' => [
                ['selector' => '.notranslate'],
                ['selector' => '[data-context]', 'attribute' => 'data-context'],
                ['selector' => '.lngpicker'],
                ['selector' => '.xdebug-error'],
                ['selector' => '.example1', 'attribute' => 'data-text'],
                ['selector' => '.example2', 'attribute' => 'data-*']
            ],
            'force_tokenize' => ['.force-tokenize'],
            'include_dom' => $this->getDefaultIncludeDom(),
            'localize_js' => true,
            'localize_js_strings' => ['Schließen', '/blog'],
            'detect_dom_changes' => true,
            'detect_dom_changes_include' => [
                '.top-button',
                '.swal-overlay',
                '.wpcf7-response-output',
                '.wpcf7-not-valid-tip'
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
        $args['languages_keyed'] = [];
        foreach ($args['languages'] as $languages__value) {
            $args['languages_keyed'][$languages__value['code']] = $languages__value;
        }
        return $args;
    }

    function getDefaultIncludeDom()
    {
        return [
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
                'selector' => '/html/body//*[@placeholder]',
                'attribute' => 'placeholder',
                'context' => null
            ],
            [
                'selector' => '/html/body//input[@type="submit"][@value]',
                'attribute' => 'value',
                'context' => null
            ],
            [
                'selector' => '/html/body//input[@type="reset"][@value]',
                'attribute' => 'value',
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
                'selector' => '/html/head//link[@rel="canonical"][@href]',
                'attribute' => 'href',
                'context' => 'slug'
            ],
            [
                'selector' => '/html/body//img[@src]',
                'attribute' => 'src',
                'context' => 'file'
            ],
            [
                'selector' => '/html/body//*[contains(@style, "url(")]',
                'attribute' => 'style',
                'context' => 'file'
            ],
            [
                'selector' => '/html/body//*[@label]',
                'attribute' => 'label',
                'context' => null
            ],
            [
                'selector' => '/html/body//@*[starts-with(name(), \'data-\')]/parent::*',
                'attribute' => 'data-*',
                'context' => null
            ],
            [
                'selector' => '/html/body//@*[contains(name(), \'text\')]/parent::*',
                'attribute' => '*text*',
                'context' => null
            ],
            [
                'selector' => '.example-link',
                'attribute' => 'alt-href|*foo*',
                'context' => 'slug'
            ]
        ];
    }

    function getDefaultLanguages()
    {
        // https://cloud.google.com/translate/docs/languages?hl=de
        // https://docs.microsoft.com/de-de/azure/cognitive-services/translator/language-support
        // https://www.deepl.com/docs-api/translating-text/
        $data = [
            [
                'code' => 'de',
                'label' => 'Deutsch',
                'rtl' => false,
                'google_translation_code' => 'de',
                'microsoft_translation_code' => 'de',
                'deepl_translation_code' => 'de'
            ],
            [
                'code' => 'en',
                'label' => 'English',
                'rtl' => false,
                'google_translation_code' => 'en',
                'microsoft_translation_code' => 'en',
                'deepl_translation_code' => 'en'
            ],
            [
                'code' => 'fr',
                'label' => 'Français',
                'rtl' => false,
                'google_translation_code' => 'fr',
                'microsoft_translation_code' => 'fr',
                'deepl_translation_code' => 'fr'
            ],
            [
                'code' => 'af',
                'label' => 'Afrikaans',
                'rtl' => false,
                'google_translation_code' => 'af',
                'microsoft_translation_code' => 'af',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'am',
                'label' => 'አማርኛ',
                'rtl' => false,
                'google_translation_code' => 'am',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ar',
                'label' => 'العربية',
                'rtl' => true,
                'google_translation_code' => 'ar',
                'microsoft_translation_code' => 'ar',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'az',
                'label' => 'Azərbaycan',
                'rtl' => false,
                'google_translation_code' => 'az',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'be',
                'label' => 'беларускі',
                'rtl' => false,
                'google_translation_code' => 'be',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'bg',
                'label' => 'български',
                'rtl' => false,
                'google_translation_code' => 'bg',
                'microsoft_translation_code' => 'bg',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'bn',
                'label' => 'বাঙালির',
                'rtl' => false,
                'google_translation_code' => 'bn',
                'microsoft_translation_code' => 'bn',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'bs',
                'label' => 'Bosanski',
                'rtl' => false,
                'google_translation_code' => 'bs',
                'microsoft_translation_code' => 'bs',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ca',
                'label' => 'Català',
                'rtl' => false,
                'google_translation_code' => 'ca',
                'microsoft_translation_code' => 'ca',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ceb',
                'label' => 'Cebuano',
                'rtl' => false,
                'google_translation_code' => 'ceb',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'co',
                'label' => 'Corsican',
                'rtl' => false,
                'google_translation_code' => 'co',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'cs',
                'label' => 'Český',
                'rtl' => false,
                'google_translation_code' => 'cs',
                'microsoft_translation_code' => 'cs',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'cy',
                'label' => 'Cymraeg',
                'rtl' => false,
                'google_translation_code' => 'cy',
                'microsoft_translation_code' => 'cy',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'da',
                'label' => 'Dansk',
                'rtl' => false,
                'google_translation_code' => 'da',
                'microsoft_translation_code' => 'da',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'el',
                'label' => 'ελληνικά',
                'rtl' => false,
                'google_translation_code' => 'el',
                'microsoft_translation_code' => 'el',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'eo',
                'label' => 'Esperanto',
                'rtl' => false,
                'google_translation_code' => 'eo',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'es',
                'label' => 'Español',
                'rtl' => false,
                'google_translation_code' => 'es',
                'microsoft_translation_code' => 'es',
                'deepl_translation_code' => 'es'
            ],
            [
                'code' => 'et',
                'label' => 'Eesti',
                'rtl' => false,
                'google_translation_code' => 'et',
                'microsoft_translation_code' => 'et',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'eu',
                'label' => 'Euskal',
                'rtl' => false,
                'google_translation_code' => 'eu',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'fa',
                'label' => 'فارسی',
                'rtl' => true,
                'google_translation_code' => 'fa',
                'microsoft_translation_code' => 'fa',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'fi',
                'label' => 'Suomalainen',
                'rtl' => false,
                'google_translation_code' => 'fi',
                'microsoft_translation_code' => 'fi',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ga',
                'label' => 'Gaeilge',
                'rtl' => false,
                'google_translation_code' => 'ga',
                'microsoft_translation_code' => 'ga',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'gd',
                'label' => 'Gàidhlig',
                'rtl' => false,
                'google_translation_code' => 'gd',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'gl',
                'label' => 'Galego',
                'rtl' => false,
                'google_translation_code' => 'gl',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'gu',
                'label' => 'ગુજરાતી',
                'rtl' => false,
                'google_translation_code' => 'gu',
                'microsoft_translation_code' => 'gu',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ha',
                'label' => 'Hausa',
                'rtl' => true,
                'google_translation_code' => 'ha',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'haw',
                'label' => 'Hawaiian',
                'rtl' => false,
                'google_translation_code' => 'haw',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'he',
                'label' => 'עברי',
                'rtl' => true,
                'google_translation_code' => 'he',
                'microsoft_translation_code' => 'he',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'hi',
                'label' => 'हिन्दी',
                'rtl' => false,
                'google_translation_code' => 'hi',
                'microsoft_translation_code' => 'hi',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'hmn',
                'label' => 'Hmong',
                'rtl' => false,
                'google_translation_code' => 'hmn',
                'microsoft_translation_code' => 'mww',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'hr',
                'label' => 'Hrvatski',
                'rtl' => false,
                'google_translation_code' => 'hr',
                'microsoft_translation_code' => 'hr',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ht',
                'label' => 'Kreyòl',
                'rtl' => false,
                'google_translation_code' => 'ht',
                'microsoft_translation_code' => 'ht',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'hu',
                'label' => 'Magyar',
                'rtl' => false,
                'google_translation_code' => 'hu',
                'microsoft_translation_code' => 'hu',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'hy',
                'label' => 'հայերեն',
                'rtl' => false,
                'google_translation_code' => 'hy',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'id',
                'label' => 'Indonesia',
                'rtl' => false,
                'google_translation_code' => 'id',
                'microsoft_translation_code' => 'id',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ig',
                'label' => 'Igbo',
                'rtl' => false,
                'google_translation_code' => 'ig',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'is',
                'label' => 'Icelandic',
                'rtl' => false,
                'google_translation_code' => 'is',
                'microsoft_translation_code' => 'is',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'it',
                'label' => 'Italiano',
                'rtl' => false,
                'google_translation_code' => 'it',
                'microsoft_translation_code' => 'it',
                'deepl_translation_code' => 'it'
            ],
            [
                'code' => 'ja',
                'label' => '日本の',
                'rtl' => false,
                'google_translation_code' => 'ja',
                'microsoft_translation_code' => 'ja',
                'deepl_translation_code' => 'ja'
            ],
            [
                'code' => 'jv',
                'label' => 'Jawa',
                'rtl' => false,
                'google_translation_code' => 'jv',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ka',
                'label' => 'ქართული',
                'rtl' => false,
                'google_translation_code' => 'ka',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'kk',
                'label' => 'Қазақ',
                'rtl' => false,
                'google_translation_code' => 'kk',
                'microsoft_translation_code' => 'kk',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'km',
                'label' => 'ខ្មែរ',
                'rtl' => false,
                'google_translation_code' => 'km',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'kn',
                'label' => 'ಕನ್ನಡ',
                'rtl' => false,
                'google_translation_code' => 'kn',
                'microsoft_translation_code' => 'kn',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ko',
                'label' => '한국의',
                'rtl' => false,
                'google_translation_code' => 'ko',
                'microsoft_translation_code' => 'ko',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ku',
                'label' => 'Kurdî',
                'rtl' => true,
                'google_translation_code' => 'ku',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ky',
                'label' => 'Кыргыз',
                'rtl' => false,
                'google_translation_code' => 'ky',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'la',
                'label' => 'Latine',
                'rtl' => false,
                'google_translation_code' => 'la',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'lb',
                'label' => 'Lëtzebuergesch',
                'rtl' => false,
                'google_translation_code' => 'lb',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'lo',
                'label' => 'ລາວ',
                'rtl' => false,
                'google_translation_code' => 'lo',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'lt',
                'label' => 'Lietuvos',
                'rtl' => false,
                'google_translation_code' => 'lt',
                'microsoft_translation_code' => 'lt',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'lv',
                'label' => 'Latvijas',
                'rtl' => false,
                'google_translation_code' => 'lv',
                'microsoft_translation_code' => 'lv',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'mg',
                'label' => 'Malagasy',
                'rtl' => false,
                'google_translation_code' => 'mg',
                'microsoft_translation_code' => 'mg',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'mi',
                'label' => 'Maori',
                'rtl' => false,
                'google_translation_code' => 'mi',
                'microsoft_translation_code' => 'mi',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'mk',
                'label' => 'македонски',
                'rtl' => false,
                'google_translation_code' => 'mk',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ml',
                'label' => 'മലയാളം',
                'rtl' => false,
                'google_translation_code' => 'ml',
                'microsoft_translation_code' => 'ml',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'mn',
                'label' => 'Монгол',
                'rtl' => false,
                'google_translation_code' => 'mn',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'mr',
                'label' => 'मराठी',
                'rtl' => false,
                'google_translation_code' => 'mr',
                'microsoft_translation_code' => 'mr',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ms',
                'label' => 'Malay',
                'rtl' => false,
                'google_translation_code' => 'ms',
                'microsoft_translation_code' => 'ms',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'mt',
                'label' => 'Malti',
                'rtl' => false,
                'google_translation_code' => 'mt',
                'microsoft_translation_code' => 'mt',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'my',
                'label' => 'မြန်မာ',
                'rtl' => false,
                'google_translation_code' => 'my',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ne',
                'label' => 'नेपाली',
                'rtl' => false,
                'google_translation_code' => 'ne',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'nl',
                'label' => 'Nederlands',
                'rtl' => false,
                'google_translation_code' => 'nl',
                'microsoft_translation_code' => 'nl',
                'deepl_translation_code' => 'nl'
            ],
            [
                'code' => 'no',
                'label' => 'Norsk',
                'rtl' => false,
                'google_translation_code' => 'no',
                'microsoft_translation_code' => 'nb',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ny',
                'label' => 'Nyanja',
                'rtl' => false,
                'google_translation_code' => 'ny',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'pa',
                'label' => 'ਪੰਜਾਬੀ',
                'rtl' => false,
                'google_translation_code' => 'pa',
                'microsoft_translation_code' => 'pa',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'pl',
                'label' => 'Polski',
                'rtl' => false,
                'google_translation_code' => 'pl',
                'microsoft_translation_code' => 'pl',
                'deepl_translation_code' => 'pl'
            ],
            [
                'code' => 'ps',
                'label' => 'پښتو',
                'rtl' => true,
                'google_translation_code' => 'ps',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'pt-br',
                'label' => 'Português (Brasil)',
                'rtl' => false,
                'google_translation_code' => 'pt',
                'microsoft_translation_code' => 'pt-br',
                'deepl_translation_code' => 'pt'
            ],
            [
                'code' => 'pt-pt',
                'label' => 'Português (Portugal)',
                'rtl' => false,
                'google_translation_code' => 'pt',
                'microsoft_translation_code' => 'pt-pt',
                'deepl_translation_code' => 'pt'
            ],
            [
                'code' => 'ro',
                'label' => 'Românesc',
                'rtl' => false,
                'google_translation_code' => 'ro',
                'microsoft_translation_code' => 'ro',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ru',
                'label' => 'Русский',
                'rtl' => false,
                'google_translation_code' => 'ru',
                'microsoft_translation_code' => 'ru',
                'deepl_translation_code' => 'ru'
            ],
            [
                'code' => 'sd',
                'label' => 'سنڌي',
                'rtl' => false,
                'google_translation_code' => 'sd',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'si',
                'label' => 'සිංහලයන්',
                'rtl' => false,
                'google_translation_code' => 'si',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sk',
                'label' => 'Slovenský',
                'rtl' => false,
                'google_translation_code' => 'sk',
                'microsoft_translation_code' => 'sk',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sl',
                'label' => 'Slovenski',
                'rtl' => false,
                'google_translation_code' => 'sl',
                'microsoft_translation_code' => 'sl',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sm',
                'label' => 'Samoa',
                'rtl' => false,
                'google_translation_code' => 'sm',
                'microsoft_translation_code' => 'sm',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sn',
                'label' => 'Shona',
                'rtl' => false,
                'google_translation_code' => 'sn',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'so',
                'label' => 'Soomaali',
                'rtl' => false,
                'google_translation_code' => 'so',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sq',
                'label' => 'Shqiptar',
                'rtl' => false,
                'google_translation_code' => 'sq',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sr-cy',
                'label' => 'Српски (ћирилица)',
                'rtl' => false,
                'google_translation_code' => 'sr',
                'microsoft_translation_code' => 'sr-Cyrl',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sr-la',
                'label' => 'Српски (латински)',
                'rtl' => false,
                'google_translation_code' => 'sr',
                'microsoft_translation_code' => 'sr-Latn',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'su',
                'label' => 'Sunda',
                'rtl' => false,
                'google_translation_code' => 'su',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'sv',
                'label' => 'Svenska',
                'rtl' => false,
                'google_translation_code' => 'sv',
                'microsoft_translation_code' => 'sv',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ta',
                'label' => 'தமிழ்',
                'rtl' => false,
                'google_translation_code' => 'ta',
                'microsoft_translation_code' => 'ta',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'te',
                'label' => 'Telugu',
                'rtl' => false,
                'google_translation_code' => 'te',
                'microsoft_translation_code' => 'te',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'tg',
                'label' => 'Тоҷикистон',
                'rtl' => false,
                'google_translation_code' => 'tg',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'th',
                'label' => 'ไทย',
                'rtl' => false,
                'google_translation_code' => 'th',
                'microsoft_translation_code' => 'th',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'tr',
                'label' => 'Türk',
                'rtl' => false,
                'google_translation_code' => 'tr',
                'microsoft_translation_code' => 'tr',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'uk',
                'label' => 'Український',
                'rtl' => false,
                'google_translation_code' => 'uk',
                'microsoft_translation_code' => 'uk',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'ur',
                'label' => 'اردو',
                'rtl' => true,
                'google_translation_code' => 'ur',
                'microsoft_translation_code' => 'ur',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'uz',
                'label' => 'O\'zbekiston',
                'rtl' => false,
                'google_translation_code' => 'uz',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'vi',
                'label' => 'Tiếng việt',
                'rtl' => false,
                'google_translation_code' => 'vi',
                'microsoft_translation_code' => 'vi',
                'deepl_translation_code' => null
            ],
            [
                'code' => 'xh',
                'label' => 'IsiXhosa',
                'rtl' => false,
                'google_translation_code' => 'xh',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'yi',
                'label' => 'ייִדיש',
                'rtl' => true,
                'google_translation_code' => 'yi',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'yo',
                'label' => 'Yoruba',
                'rtl' => false,
                'google_translation_code' => 'yo',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ],
            [
                'code' => 'zh-cn',
                'label' => '中文（简体）',
                'rtl' => false,
                'google_translation_code' => 'zh-cn',
                'microsoft_translation_code' => 'zh-Hans',
                'deepl_translation_code' => 'zh'
            ],
            [
                'code' => 'zh-tw',
                'label' => '中文（繁體）',
                'rtl' => false,
                'google_translation_code' => 'zh-tw',
                'microsoft_translation_code' => 'zh-Hant',
                'deepl_translation_code' => 'zh'
            ],
            [
                'code' => 'zu',
                'label' => 'Zulu',
                'rtl' => false,
                'google_translation_code' => 'zu',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null
            ]
        ];
        // if this already set (this is not the case on init, but we don't need the ordering)
        if ($this->getSourceLanguageCode() !== null) {
            $lng_source = $this->getSourceLanguageCode();
            usort($data, function ($a, $b) use ($lng_source) {
                if ($lng_source != '') {
                    if ($a['code'] === $lng_source) {
                        return -1;
                    }
                    if ($b['code'] === $lng_source) {
                        return 1;
                    }
                }
                return strnatcmp($a['label'], $b['label']);
            });
        }
        return $data;
    }

    function getLanguageDataForCode($lng)
    {
        return @$this->get('languages_keyed')[$lng] ?? null;
    }

    function isLanguageDirectionRtl($lng)
    {
        return @$this->getLanguageDataForCode($lng)['rtl'] === true;
    }

    function getApiLngCodeForService($service, $lng)
    {
        $data = $this->getLanguageDataForCode($lng);
        // if nothing is set, pretend lng code (we can set null and show that the service does not provide that language)
        if (!array_key_exists($service . '_translation_code', $data)) {
            return $lng;
        }
        return $this->getLanguageDataForCode($lng)[$service . '_translation_code'];
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

    function getLabelForLanguageCode($lng)
    {
        return @$this->getLanguageDataForCode($lng)['label'] ?? '';
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

    function getSelectedLanguageCodesLabelsWithoutSource()
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
