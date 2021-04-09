<?php
namespace vielhuber\gtbabel;

class Settings
{
    public $args;

    function __construct(Utils $utils = null)
    {
        $this->utils = $utils ?: new Utils();
    }

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
        if (!array_key_exists($prop, $this->args)) {
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

    function getDefaultSettings()
    {
        $default_settings = [
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
            'basic_auth' => null,
            'translate_html' => true,
            'translate_html_include' => $this->getDefaultTranslateHtmlInclude(),
            'translate_html_exclude' => [
                ['selector' => '.notranslate', 'comment' => 'Default class'],
                ['selector' => '[data-context]', 'attribute' => 'data-context', 'comment' => 'Data context attributes'],
                ['selector' => '.lngpicker', 'comment' => 'Language picker'],
                ['selector' => '.xdebug-error', 'comment' => 'Xdebug errors'],
                [
                    'selector' => '.example1',
                    'attribute' => 'data-text',
                    'attribute' => 'data-text',
                    'comment' => 'Example'
                ],
                ['selector' => '.example2', 'attribute' => 'data-*', 'comment' => 'Example']
            ],
            'translate_html_force_tokenize' => [['selector' => '.force-tokenize', 'comment' => 'Default class']],
            'localize_js' => false,
            'localize_js_strings' => ['Schließen', '/blog'],
            'detect_dom_changes' => false,
            'detect_dom_changes_include' => [
                ['selector' => '.top-button', 'comment' => 'Top button'],
                ['selector' => '.swal-overlay', 'comment' => 'SweetAlert']
            ],
            'translate_xml' => true,
            'translate_xml_include' => [
                [
                    'selector' => '//*[name()=\'loc\']',
                    'attribute' => null,
                    'context' => 'slug',
                    'comment' => 'Sitemap links'
                ]
            ],
            'translate_json' => true,
            'translate_json_include' => [
                ['url' => '/path/in/source/lng/to/specific/page', 'selector' => ['key'], 'comment' => 'Example'],
                [
                    'url' => 'wp-json/v1/*/endpoint',
                    'selector' => ['key', 'nested.key', 'key.with.*.wildcard'],
                    'comment' => 'Example'
                ]
            ],
            'translate_wp_localize_script' => true,
            'translate_wp_localize_script_include' => [
                ['selector' => 'key1_*.key2.*', 'comment' => 'Example'],
                ['selector' => 'key3_*.key4', 'comment' => 'Example']
            ],
            'prevent_publish_wp_new_posts' => false,
            'url_query_args' => [
                [
                    'selector' => '*',
                    'type' => 'keep',
                    'comment' => 'Keep everything'
                ],
                [
                    'selector' => 'nonce',
                    'type' => 'discard',
                    'comment' => 'Discard nonces'
                ]
            ],
            'exclude_urls_content' => [['url' => 'backend', 'comment' => 'Backend']],
            'exclude_urls_slugs' => [['url' => 'api/v1.0', 'comment' => 'API']],
            'exclude_stopwords' => ['Some specific string to exclude'],
            'html_lang_attribute' => true,
            'html_hreflang_tags' => true,
            'xml_hreflang_tags' => true,
            'show_language_picker' => false,
            'show_frontend_editor_links' => false,
            'debug_translations' => false,
            'auto_add_translations' => true,
            'auto_set_new_strings_checked' => false,
            'auto_set_discovered_strings_checked' => false,
            'unchecked_strings' => 'trans',
            'auto_translation' => false,
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => null,
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ],
            'discovery_log' => false,
            'frontend_editor' => false,
            'wp_mail_notifications' => false
        ];

        if ($this->utils->isWordPress()) {
            $default_settings['translate_html_include'] = array_filter(
                $default_settings['translate_html_include'],
                function ($a) {
                    return $a['selector'] != '.example-link';
                }
            );
            $default_settings['translate_html_include'][] = [
                'selector' => '#payment .place-order .button',
                'attribute' => 'data-value|value',
                'context' => null,
                'comment' => 'WooCommerce'
            ];
            $default_settings['translate_html_include'][] = [
                'selector' => '.elementor-widget-video',
                'attribute' => 'data-settings',
                'context' => null,
                'comment' => 'Elementor videos'
            ];
            $default_settings['lng_source'] = mb_strtolower(mb_substr(get_locale(), 0, 2));
            $default_settings['languages'] = [
                [
                    'code' => 'de',
                    'label' => 'Deutsch',
                    'hreflang_code' => 'de',
                    'google_translation_code' => 'de',
                    'microsoft_translation_code' => 'de',
                    'deepl_translation_code' => 'de',
                    'hidden' => false
                ],
                [
                    'code' => 'en',
                    'label' => 'English',
                    'hreflang_code' => 'en',
                    'google_translation_code' => 'en',
                    'microsoft_translation_code' => 'en',
                    'deepl_translation_code' => 'en',
                    'hidden' => false
                ]
            ];
            if (!in_array($default_settings['lng_source'], ['de', 'en'])) {
                $default_settings['languages'][] = $this->gtbabel->settings->getLanguageDataForCode(
                    $default_settings['lng_source']
                ) ?? [
                    'code' => $default_settings['lng_source'],
                    'label' => $default_settings['lng_source'],
                    'hreflang_code' => $default_settings['lng_source'],
                    'google_translation_code' => $default_settings['lng_source'],
                    'microsoft_translation_code' => $default_settings['lng_source'],
                    'deepl_translation_code' => $default_settings['lng_source'],
                    'hidden' => false
                ];
            }
            $default_settings['log_folder'] = $this->utils->getWordPressPluginFileStorePathRelative() . '/logs';
            $default_settings['localize_js_strings'] = [];
            $default_settings['translate_json_include'] = [
                [
                    'url' => '?wc-ajax=*',
                    'selector' => ['fragments.*', 'messages', 'redirect'],
                    'comment' => 'WooCommerce'
                ],
                ['url' => 'wp-json', 'selector' => ['message'], 'comment' => 'Contact Form 7'],
                ['url' => '*', 'selector' => ['youtube_url'], 'comment' => 'Elementor videos']
            ];
            $default_settings['translate_wp_localize_script_include'] = [
                ['selector' => 'wc_*.locale.*', 'comment' => 'WooCommerce'],
                ['selector' => 'wc_*.i18n_*', 'comment' => 'WooCommerce'],
                ['selector' => 'wc_*.cart_url', 'comment' => 'WooCommerce']
            ];
            $default_settings['exclude_urls_content'] = [
                ['url' => 'wp-admin', 'comment' => 'WordPress'],
                ['url' => 'feed', 'comment' => 'WordPress'],
                ['url' => 'embed', 'comment' => 'WordPress'],
                ['url' => 'wp-login.php', 'comment' => 'WordPress'],
                ['url' => 'wp-register.php', 'comment' => 'WordPress'],
                ['url' => 'wp-cron.php', 'comment' => 'WordPress'],
                ['url' => 'wp-comments-post.php', 'comment' => 'WordPress']
            ];
            $default_settings['exclude_urls_slugs'] = [['url' => 'wp-json', 'comment' => 'WordPress']];
            $default_settings['exclude_stopwords'] = [];
            $default_settings['translate_html_exclude'] = [
                ['selector' => '.notranslate', 'comment' => 'Default class'],
                ['selector' => '[data-context]', 'attribute' => 'data-context', 'comment' => 'Data context attributes'],
                ['selector' => '.lngpicker', 'comment' => 'Language picker'],
                ['selector' => '.xdebug-error', 'comment' => 'Xdebug errors'],
                ['selector' => '#wpadminbar', 'attribute' => 'data-text', 'comment' => 'WordPress adminbar'],
                ['selector' => '#comments .comment-content', 'comment' => 'WordPress comments'],
                ['selector' => '.page-title .search-term', 'comment' => 'WordPress search term'],
                ['selector' => '.screen-reader-text', 'comment' => 'WordPress screen reader text'],
                ['selector' => '/html/body//address/br/parent::address', 'comment' => 'WooCommerce addresses'],
                ['selector' => '.woocommerce-order-overview__email', 'comment' => 'WooCommerce order email']
            ];
            $default_settings['translate_html_force_tokenize'] = array_merge(
                $default_settings['translate_html_force_tokenize'],
                [
                    ['selector' => '.page-title .search-term', 'comment' => 'WordPress'],
                    ['selector' => '.screen-reader-text', 'comment' => 'WordPress']
                ]
            );
            $default_settings['auto_translation'] = null; // undefined
            $default_settings['auto_translation_service'] = [];
            $default_settings['show_frontend_editor_links'] = true;
        }
        return $default_settings;
    }

    function valuesAreEqual($a1, $a2)
    {
        if (is_array($a1)) {
            // filter out comments (they should change more often)
            $a1 = array_filter(
                $a1,
                function ($key) {
                    return $key !== 'comment';
                },
                ARRAY_FILTER_USE_KEY
            );
            $a2 = array_filter(
                $a2,
                function ($key) {
                    return $key !== 'comment';
                },
                ARRAY_FILTER_USE_KEY
            );
            if (
                json_encode(
                    array_filter(
                        call_user_func(function ($a) {
                            ksort($a);
                            return $a;
                        }, $a1),
                        function ($a) {
                            return $a != '';
                        }
                    )
                ) ==
                json_encode(
                    array_filter(
                        call_user_func(function ($a) {
                            ksort($a);
                            return $a;
                        }, $a2),
                        function ($a) {
                            return $a != '';
                        }
                    )
                )
            ) {
                return true;
            }
        } else {
            if ($a1 == $a2) {
                return true;
            }
        }
        return false;
    }

    function getAllSettingsIncludingDefaultForKey($key, $settings)
    {
        $return = [];
        $default_settings = $this->getDefaultSettings();
        foreach ($default_settings as $default_settings__key => $default_settings__value) {
            if ($default_settings__key !== $key) {
                continue;
            }
            foreach ($default_settings__value as $default_settings__value__value) {
                $missing = true;
                foreach ($settings as $settings__key => $settings__value) {
                    if ($settings__key !== $key) {
                        continue;
                    }
                    if (!empty($settings__value)) {
                        foreach ($settings__value as $settings__value__value) {
                            if ($this->valuesAreEqual($default_settings__value__value, $settings__value__value)) {
                                $missing = false;
                                break 2;
                            }
                        }
                    }
                }
                $return[] = ['value' => $default_settings__value__value, 'missing' => $missing, 'default' => true];
            }
        }
        foreach ($settings as $settings__key => $settings__value) {
            if ($settings__key !== $key) {
                continue;
            }
            if (!empty($settings__value)) {
                foreach ($settings__value as $settings__value__value) {
                    $default = false;
                    foreach ($default_settings as $default_settings__key => $default_settings__value) {
                        if ($default_settings__key !== $key) {
                            continue;
                        }
                        foreach ($default_settings__value as $default_settings__value__value) {
                            if ($this->valuesAreEqual($settings__value__value, $default_settings__value__value)) {
                                $default = true;
                                break 2;
                            }
                        }
                    }
                    if ($default === true) {
                        continue;
                    }
                    $return[] = ['value' => $settings__value__value, 'missing' => false, 'default' => false];
                }
            }
        }
        return $return;
    }

    function setupSettings($args = [])
    {
        $default_settings = $this->getDefaultSettings();
        if (!empty($args)) {
            foreach ($args as $args__key => $args__value) {
                if ($args__value === '1') {
                    $args__value = true;
                }
                if ($args__value === '0') {
                    $args__value = false;
                }
                $default_settings[$args__key] = $args__value;
            }
        }
        return $default_settings;
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

    function getDefaultTranslateHtmlInclude()
    {
        return [
            [
                'selector' => '/html/body//text()',
                'attribute' => null,
                'context' => null,
                'comment' => 'Text nodes'
            ],
            [
                'selector' => '/html/body//a[starts-with(@href, \'mailto:\')]',
                'attribute' => 'href',
                'context' => 'email',
                'comment' => 'Email links'
            ],
            [
                'selector' => '/html/body//a[@href]',
                'attribute' => 'href',
                'context' => 'slug|file|url',
                'comment' => 'Links'
            ],
            [
                'selector' => '/html/body//form[@action]',
                'attribute' => 'action',
                'context' => 'slug|file|url',
                'comment' => 'Form actions'
            ],
            [
                'selector' => '/html/body//iframe[@src]',
                'attribute' => 'src',
                'context' => 'slug|file|url',
                'comment' => 'Iframe content'
            ],
            [
                'selector' => '/html/body//img[@alt]',
                'attribute' => 'alt',
                'context' => null,
                'comment' => 'Alt tags'
            ],
            [
                'selector' => '/html/body//*[@title]',
                'attribute' => 'title',
                'context' => null,
                'comment' => 'Title attributes'
            ],
            [
                'selector' => '/html/body//*[@placeholder]',
                'attribute' => 'placeholder',
                'context' => null,
                'comment' => 'Input placeholders'
            ],
            [
                'selector' => '/html/body//input[@type="submit"][@value]',
                'attribute' => 'value',
                'context' => null,
                'comment' => 'Submit values'
            ],
            [
                'selector' => '/html/body//input[@type="reset"][@value]',
                'attribute' => 'value',
                'context' => null,
                'comment' => 'Reset values'
            ],
            [
                'selector' => '/html/head//title',
                'attribute' => null,
                'context' => 'title',
                'comment' => 'Page title'
            ],
            [
                'selector' => '/html/head//meta[@name="description"][@content]',
                'attribute' => 'content',
                'context' => 'description',
                'comment' => 'Page description'
            ],
            [
                'selector' => '/html/head//link[@rel="canonical"][@href]',
                'attribute' => 'href',
                'context' => 'slug',
                'comment' => 'Canonical tags'
            ],
            [
                'selector' => '/html/body//img[@src]',
                'attribute' => 'src',
                'context' => 'file',
                'comment' => 'Image urls'
            ],
            [
                'selector' => '/html/body//img[@srcset]',
                'attribute' => 'srcset',
                'context' => 'file',
                'comment' => 'Image srcset urls'
            ],
            [
                'selector' => '/html/body//*[contains(@style, "url(")]',
                'attribute' => 'style',
                'context' => 'file',
                'comment' => 'Background images'
            ],
            [
                'selector' => '/html/body//*[@label]',
                'attribute' => 'label',
                'context' => null,
                'comment' => 'Labels'
            ],
            [
                'selector' => '/html/body//@*[contains(name(), \'text\')]/parent::*',
                'attribute' => '*text*',
                'context' => null,
                'comment' => 'Text attributes'
            ],
            [
                'selector' => '.example-link',
                'attribute' => 'alt-href|*foo*',
                'context' => 'slug|file|url'
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
                'hreflang_code' => 'de',
                'google_translation_code' => 'de',
                'microsoft_translation_code' => 'de',
                'deepl_translation_code' => 'de',
                'hidden' => false
            ],
            [
                'code' => 'en',
                'label' => 'English',
                'rtl' => false,
                'hreflang_code' => 'en',
                'google_translation_code' => 'en',
                'microsoft_translation_code' => 'en',
                'deepl_translation_code' => 'en',
                'hidden' => false
            ],
            [
                'code' => 'fr',
                'label' => 'Français',
                'rtl' => false,
                'hreflang_code' => 'fr',
                'google_translation_code' => 'fr',
                'microsoft_translation_code' => 'fr',
                'deepl_translation_code' => 'fr',
                'hidden' => false
            ],
            [
                'code' => 'af',
                'label' => 'Afrikaans',
                'rtl' => false,
                'hreflang_code' => 'af',
                'google_translation_code' => 'af',
                'microsoft_translation_code' => 'af',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'am',
                'label' => 'አማርኛ',
                'rtl' => false,
                'hreflang_code' => 'am',
                'google_translation_code' => 'am',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ar',
                'label' => 'العربية',
                'rtl' => true,
                'hreflang_code' => 'ar',
                'google_translation_code' => 'ar',
                'microsoft_translation_code' => 'ar',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'az',
                'label' => 'Azərbaycan',
                'rtl' => false,
                'hreflang_code' => 'az',
                'google_translation_code' => 'az',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'be',
                'label' => 'беларускі',
                'rtl' => false,
                'hreflang_code' => 'be',
                'google_translation_code' => 'be',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'bg',
                'label' => 'български',
                'rtl' => false,
                'hreflang_code' => 'bg',
                'google_translation_code' => 'bg',
                'microsoft_translation_code' => 'bg',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'bn',
                'label' => 'বাঙালির',
                'rtl' => false,
                'hreflang_code' => 'bn',
                'google_translation_code' => 'bn',
                'microsoft_translation_code' => 'bn',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'bs',
                'label' => 'Bosanski',
                'rtl' => false,
                'hreflang_code' => 'bs',
                'google_translation_code' => 'bs',
                'microsoft_translation_code' => 'bs',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ca',
                'label' => 'Català',
                'rtl' => false,
                'hreflang_code' => 'ca',
                'google_translation_code' => 'ca',
                'microsoft_translation_code' => 'ca',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ceb',
                'label' => 'Cebuano',
                'rtl' => false,
                'hreflang_code' => null,
                'google_translation_code' => 'ceb',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'co',
                'label' => 'Corsican',
                'rtl' => false,
                'hreflang_code' => 'co',
                'google_translation_code' => 'co',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'cs',
                'label' => 'Český',
                'rtl' => false,
                'hreflang_code' => 'cs',
                'google_translation_code' => 'cs',
                'microsoft_translation_code' => 'cs',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'cy',
                'label' => 'Cymraeg',
                'rtl' => false,
                'hreflang_code' => 'cy',
                'google_translation_code' => 'cy',
                'microsoft_translation_code' => 'cy',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'da',
                'label' => 'Dansk',
                'rtl' => false,
                'hreflang_code' => 'da',
                'google_translation_code' => 'da',
                'microsoft_translation_code' => 'da',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'el',
                'label' => 'ελληνικά',
                'rtl' => false,
                'hreflang_code' => 'el',
                'google_translation_code' => 'el',
                'microsoft_translation_code' => 'el',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'eo',
                'label' => 'Esperanto',
                'rtl' => false,
                'hreflang_code' => 'eo',
                'google_translation_code' => 'eo',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'es',
                'label' => 'Español',
                'rtl' => false,
                'hreflang_code' => 'es',
                'google_translation_code' => 'es',
                'microsoft_translation_code' => 'es',
                'deepl_translation_code' => 'es',
                'hidden' => false
            ],
            [
                'code' => 'et',
                'label' => 'Eesti',
                'rtl' => false,
                'hreflang_code' => 'et',
                'google_translation_code' => 'et',
                'microsoft_translation_code' => 'et',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'eu',
                'label' => 'Euskal',
                'rtl' => false,
                'hreflang_code' => 'eu',
                'google_translation_code' => 'eu',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'fa',
                'label' => 'فارسی',
                'rtl' => true,
                'hreflang_code' => 'fa',
                'google_translation_code' => 'fa',
                'microsoft_translation_code' => 'fa',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'fi',
                'label' => 'Suomalainen',
                'rtl' => false,
                'hreflang_code' => 'fi',
                'google_translation_code' => 'fi',
                'microsoft_translation_code' => 'fi',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ga',
                'label' => 'Gaeilge',
                'rtl' => false,
                'hreflang_code' => 'ga',
                'google_translation_code' => 'ga',
                'microsoft_translation_code' => 'ga',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'gd',
                'label' => 'Gàidhlig',
                'rtl' => false,
                'hreflang_code' => 'gd',
                'google_translation_code' => 'gd',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'gl',
                'label' => 'Galego',
                'rtl' => false,
                'hreflang_code' => 'gl',
                'google_translation_code' => 'gl',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'gu',
                'label' => 'ગુજરાતી',
                'rtl' => false,
                'hreflang_code' => 'gu',
                'google_translation_code' => 'gu',
                'microsoft_translation_code' => 'gu',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ha',
                'label' => 'Hausa',
                'rtl' => true,
                'hreflang_code' => 'ha',
                'google_translation_code' => 'ha',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'haw',
                'label' => 'Hawaiian',
                'rtl' => false,
                'hreflang_code' => null,
                'google_translation_code' => 'haw',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'he',
                'label' => 'עברי',
                'rtl' => true,
                'hreflang_code' => 'he',
                'google_translation_code' => 'he',
                'microsoft_translation_code' => 'he',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'hi',
                'label' => 'हिन्दी',
                'rtl' => false,
                'hreflang_code' => 'hi',
                'google_translation_code' => 'hi',
                'microsoft_translation_code' => 'hi',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'hmn',
                'label' => 'Hmong',
                'rtl' => false,
                'hreflang_code' => null,
                'google_translation_code' => 'hmn',
                'microsoft_translation_code' => 'mww',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'hr',
                'label' => 'Hrvatski',
                'rtl' => false,
                'hreflang_code' => 'hr',
                'google_translation_code' => 'hr',
                'microsoft_translation_code' => 'hr',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ht',
                'label' => 'Kreyòl',
                'rtl' => false,
                'hreflang_code' => 'ht',
                'google_translation_code' => 'ht',
                'microsoft_translation_code' => 'ht',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'hu',
                'label' => 'Magyar',
                'rtl' => false,
                'hreflang_code' => 'hu',
                'google_translation_code' => 'hu',
                'microsoft_translation_code' => 'hu',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'hy',
                'label' => 'հայերեն',
                'rtl' => false,
                'hreflang_code' => 'hy',
                'google_translation_code' => 'hy',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'id',
                'label' => 'Indonesia',
                'rtl' => false,
                'hreflang_code' => 'id',
                'google_translation_code' => 'id',
                'microsoft_translation_code' => 'id',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ig',
                'label' => 'Igbo',
                'rtl' => false,
                'hreflang_code' => 'ig',
                'google_translation_code' => 'ig',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'is',
                'label' => 'Icelandic',
                'rtl' => false,
                'hreflang_code' => 'is',
                'google_translation_code' => 'is',
                'microsoft_translation_code' => 'is',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'it',
                'label' => 'Italiano',
                'rtl' => false,
                'hreflang_code' => 'it',
                'google_translation_code' => 'it',
                'microsoft_translation_code' => 'it',
                'deepl_translation_code' => 'it',
                'hidden' => false
            ],
            [
                'code' => 'ja',
                'label' => '日本の',
                'rtl' => false,
                'hreflang_code' => 'ja',
                'google_translation_code' => 'ja',
                'microsoft_translation_code' => 'ja',
                'deepl_translation_code' => 'ja',
                'hidden' => false
            ],
            [
                'code' => 'jv',
                'label' => 'Jawa',
                'rtl' => false,
                'hreflang_code' => 'jv',
                'google_translation_code' => 'jv',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ka',
                'label' => 'ქართული',
                'rtl' => false,
                'hreflang_code' => 'ka',
                'google_translation_code' => 'ka',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'kk',
                'label' => 'Қазақ',
                'rtl' => false,
                'hreflang_code' => 'kk',
                'google_translation_code' => 'kk',
                'microsoft_translation_code' => 'kk',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'km',
                'label' => 'ខ្មែរ',
                'rtl' => false,
                'hreflang_code' => 'km',
                'google_translation_code' => 'km',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'kn',
                'label' => 'ಕನ್ನಡ',
                'rtl' => false,
                'hreflang_code' => 'kn',
                'google_translation_code' => 'kn',
                'microsoft_translation_code' => 'kn',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ko',
                'label' => '한국의',
                'rtl' => false,
                'hreflang_code' => 'ko',
                'google_translation_code' => 'ko',
                'microsoft_translation_code' => 'ko',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ku',
                'label' => 'Kurdî',
                'rtl' => true,
                'hreflang_code' => 'ku',
                'google_translation_code' => 'ku',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ky',
                'label' => 'Кыргыз',
                'rtl' => false,
                'hreflang_code' => 'ky',
                'google_translation_code' => 'ky',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'la',
                'label' => 'Latine',
                'rtl' => false,
                'hreflang_code' => 'la',
                'google_translation_code' => 'la',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'lb',
                'label' => 'Lëtzebuergesch',
                'rtl' => false,
                'hreflang_code' => 'lb',
                'google_translation_code' => 'lb',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'lo',
                'label' => 'ລາວ',
                'rtl' => false,
                'hreflang_code' => 'lo',
                'google_translation_code' => 'lo',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'lt',
                'label' => 'Lietuvos',
                'rtl' => false,
                'hreflang_code' => 'lt',
                'google_translation_code' => 'lt',
                'microsoft_translation_code' => 'lt',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'lv',
                'label' => 'Latvijas',
                'rtl' => false,
                'hreflang_code' => 'lv',
                'google_translation_code' => 'lv',
                'microsoft_translation_code' => 'lv',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'mg',
                'label' => 'Malagasy',
                'rtl' => false,
                'hreflang_code' => 'mg',
                'google_translation_code' => 'mg',
                'microsoft_translation_code' => 'mg',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'mi',
                'label' => 'Maori',
                'rtl' => false,
                'hreflang_code' => 'mi',
                'google_translation_code' => 'mi',
                'microsoft_translation_code' => 'mi',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'mk',
                'label' => 'македонски',
                'rtl' => false,
                'hreflang_code' => 'mk',
                'google_translation_code' => 'mk',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ml',
                'label' => 'മലയാളം',
                'rtl' => false,
                'hreflang_code' => 'ml',
                'google_translation_code' => 'ml',
                'microsoft_translation_code' => 'ml',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'mn',
                'label' => 'Монгол',
                'rtl' => false,
                'hreflang_code' => 'mn',
                'google_translation_code' => 'mn',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'mr',
                'label' => 'मराठी',
                'rtl' => false,
                'hreflang_code' => 'mr',
                'google_translation_code' => 'mr',
                'microsoft_translation_code' => 'mr',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ms',
                'label' => 'Malay',
                'rtl' => false,
                'hreflang_code' => 'ms',
                'google_translation_code' => 'ms',
                'microsoft_translation_code' => 'ms',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'mt',
                'label' => 'Malti',
                'rtl' => false,
                'hreflang_code' => 'mt',
                'google_translation_code' => 'mt',
                'microsoft_translation_code' => 'mt',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'my',
                'label' => 'မြန်မာ',
                'rtl' => false,
                'hreflang_code' => 'my',
                'google_translation_code' => 'my',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ne',
                'label' => 'नेपाली',
                'rtl' => false,
                'hreflang_code' => 'ne',
                'google_translation_code' => 'ne',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'nl',
                'label' => 'Nederlands',
                'rtl' => false,
                'hreflang_code' => 'nl',
                'google_translation_code' => 'nl',
                'microsoft_translation_code' => 'nl',
                'deepl_translation_code' => 'nl',
                'hidden' => false
            ],
            [
                'code' => 'no',
                'label' => 'Norsk',
                'rtl' => false,
                'hreflang_code' => 'no',
                'google_translation_code' => 'no',
                'microsoft_translation_code' => 'nb',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ny',
                'label' => 'Nyanja',
                'rtl' => false,
                'hreflang_code' => 'ny',
                'google_translation_code' => 'ny',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'pa',
                'label' => 'ਪੰਜਾਬੀ',
                'rtl' => false,
                'hreflang_code' => 'pa',
                'google_translation_code' => 'pa',
                'microsoft_translation_code' => 'pa',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'pl',
                'label' => 'Polski',
                'rtl' => false,
                'hreflang_code' => 'pl',
                'google_translation_code' => 'pl',
                'microsoft_translation_code' => 'pl',
                'deepl_translation_code' => 'pl',
                'hidden' => false
            ],
            [
                'code' => 'ps',
                'label' => 'پښتو',
                'rtl' => true,
                'hreflang_code' => 'ps',
                'google_translation_code' => 'ps',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'pt-br',
                'label' => 'Português (Brasil)',
                'rtl' => false,
                'hreflang_code' => 'pt',
                'google_translation_code' => 'pt',
                'microsoft_translation_code' => 'pt-br',
                'deepl_translation_code' => 'pt',
                'hidden' => false
            ],
            [
                'code' => 'pt-pt',
                'label' => 'Português (Portugal)',
                'rtl' => false,
                'hreflang_code' => 'pt',
                'google_translation_code' => 'pt',
                'microsoft_translation_code' => 'pt-pt',
                'deepl_translation_code' => 'pt',
                'hidden' => false
            ],
            [
                'code' => 'ro',
                'label' => 'Românesc',
                'rtl' => false,
                'hreflang_code' => 'ro',
                'google_translation_code' => 'ro',
                'microsoft_translation_code' => 'ro',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ru',
                'label' => 'Русский',
                'rtl' => false,
                'hreflang_code' => 'ru',
                'google_translation_code' => 'ru',
                'microsoft_translation_code' => 'ru',
                'deepl_translation_code' => 'ru',
                'hidden' => false
            ],
            [
                'code' => 'sd',
                'label' => 'سنڌي',
                'rtl' => false,
                'hreflang_code' => 'sd',
                'google_translation_code' => 'sd',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'si',
                'label' => 'සිංහලයන්',
                'rtl' => false,
                'hreflang_code' => 'si',
                'google_translation_code' => 'si',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sk',
                'label' => 'Slovenský',
                'rtl' => false,
                'hreflang_code' => 'sk',
                'google_translation_code' => 'sk',
                'microsoft_translation_code' => 'sk',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sl',
                'label' => 'Slovenski',
                'rtl' => false,
                'hreflang_code' => 'sl',
                'google_translation_code' => 'sl',
                'microsoft_translation_code' => 'sl',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sm',
                'label' => 'Samoa',
                'rtl' => false,
                'hreflang_code' => 'sm',
                'google_translation_code' => 'sm',
                'microsoft_translation_code' => 'sm',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sn',
                'label' => 'Shona',
                'rtl' => false,
                'hreflang_code' => 'sn',
                'google_translation_code' => 'sn',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'so',
                'label' => 'Soomaali',
                'rtl' => false,
                'hreflang_code' => 'so',
                'google_translation_code' => 'so',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sq',
                'label' => 'Shqiptar',
                'rtl' => false,
                'hreflang_code' => 'sq',
                'google_translation_code' => 'sq',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sr-cy',
                'label' => 'Српски (ћирилица)',
                'rtl' => false,
                'hreflang_code' => 'sr',
                'google_translation_code' => 'sr',
                'microsoft_translation_code' => 'sr-Cyrl',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sr-la',
                'label' => 'Српски (латински)',
                'rtl' => false,
                'hreflang_code' => 'sr',
                'google_translation_code' => 'sr',
                'microsoft_translation_code' => 'sr-Latn',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'su',
                'label' => 'Sunda',
                'rtl' => false,
                'hreflang_code' => 'su',
                'google_translation_code' => 'su',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'sv',
                'label' => 'Svenska',
                'rtl' => false,
                'hreflang_code' => 'sv',
                'google_translation_code' => 'sv',
                'microsoft_translation_code' => 'sv',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ta',
                'label' => 'தமிழ்',
                'rtl' => false,
                'hreflang_code' => 'ta',
                'google_translation_code' => 'ta',
                'microsoft_translation_code' => 'ta',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'te',
                'label' => 'Telugu',
                'rtl' => false,
                'hreflang_code' => 'te',
                'google_translation_code' => 'te',
                'microsoft_translation_code' => 'te',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'tg',
                'label' => 'Тоҷикистон',
                'rtl' => false,
                'hreflang_code' => 'tg',
                'google_translation_code' => 'tg',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'th',
                'label' => 'ไทย',
                'rtl' => false,
                'hreflang_code' => 'th',
                'google_translation_code' => 'th',
                'microsoft_translation_code' => 'th',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'tr',
                'label' => 'Türk',
                'rtl' => false,
                'hreflang_code' => 'tr',
                'google_translation_code' => 'tr',
                'microsoft_translation_code' => 'tr',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'uk',
                'label' => 'Український',
                'rtl' => false,
                'hreflang_code' => 'uk',
                'google_translation_code' => 'uk',
                'microsoft_translation_code' => 'uk',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'ur',
                'label' => 'اردو',
                'rtl' => true,
                'hreflang_code' => 'ur',
                'google_translation_code' => 'ur',
                'microsoft_translation_code' => 'ur',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'uz',
                'label' => 'O\'zbekiston',
                'rtl' => false,
                'hreflang_code' => 'uz',
                'google_translation_code' => 'uz',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'vi',
                'label' => 'Tiếng việt',
                'rtl' => false,
                'hreflang_code' => 'vi',
                'google_translation_code' => 'vi',
                'microsoft_translation_code' => 'vi',
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'xh',
                'label' => 'IsiXhosa',
                'rtl' => false,
                'hreflang_code' => 'xh',
                'google_translation_code' => 'xh',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'yi',
                'label' => 'ייִדיש',
                'rtl' => true,
                'hreflang_code' => 'yi',
                'google_translation_code' => 'yi',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'yo',
                'label' => 'Yoruba',
                'rtl' => false,
                'hreflang_code' => 'yo',
                'google_translation_code' => 'yo',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
            ],
            [
                'code' => 'zh-cn',
                'label' => '中文（简体）',
                'rtl' => false,
                'hreflang_code' => 'zh-cn',
                'google_translation_code' => 'zh-cn',
                'microsoft_translation_code' => 'zh-Hans',
                'deepl_translation_code' => 'zh',
                'hidden' => false
            ],
            [
                'code' => 'zh-tw',
                'label' => '中文（繁體）',
                'rtl' => false,
                'hreflang_code' => 'zh-tw',
                'google_translation_code' => 'zh-tw',
                'microsoft_translation_code' => 'zh-Hant',
                'deepl_translation_code' => 'zh',
                'hidden' => false
            ],
            [
                'code' => 'zu',
                'label' => 'Zulu',
                'rtl' => false,
                'hreflang_code' => 'zu',
                'google_translation_code' => 'zu',
                'microsoft_translation_code' => null,
                'deepl_translation_code' => null,
                'hidden' => false
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

    function isLanguageHidden($lng)
    {
        if ($this->utils->isWordPress() && is_user_logged_in()) {
            return false;
        }
        return @$this->getLanguageDataForCode($lng)['hidden'] === true;
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

    function getAutoTranslationService($lng)
    {
        $services = $this->get('auto_translation_service');
        if (is_array($services) && !empty($services)) {
            shuffle($services);
            foreach ($services as $services__value) {
                if (
                    (!isset($services__value['disabled']) || $services__value['disabled'] != '1') &&
                    (!isset($services__value['lng']) ||
                        (is_string($services__value['lng']) &&
                            ($services__value['lng'] === null ||
                                $services__value['lng'] === '' ||
                                $services__value['lng'] === '*' ||
                                $services__value['lng'] === $lng)) ||
                        (is_array($services__value['lng']) &&
                            (in_array($lng, $services__value['lng']) || in_array('*', $services__value['lng']))))
                ) {
                    return $services__value['provider'];
                }
            }
        }
        return null;
    }

    function getAutoTranslationServiceData($service)
    {
        $services = $this->get('auto_translation_service');
        if (is_array($services) && !empty($services)) {
            foreach ($services as $services__value) {
                if ($services__value['provider'] === $service) {
                    return $services__value;
                }
            }
        }
        return null;
    }

    function getHreflangCodeForLanguage($lng)
    {
        $data = $this->getLanguageDataForCode($lng);
        // if nothing is set, pretend lng code (we can set null and show that the service does not provide that language)
        if (!array_key_exists('hreflang_code', $data)) {
            return $lng;
        }
        return $this->getLanguageDataForCode($lng)['hreflang_code'];
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

    function getSelectedLanguageCodesLabelsWithSourceAtLast()
    {
        $lng = [];
        foreach ($this->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            if ($languages__key === $this->getSourceLanguageCode()) {
                continue;
            }
            $lng[$languages__key] = $languages__value;
        }
        $lng[$this->getSourceLanguageCode()] = $this->getSourceLanguageLabel();
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

    function getSelectedLanguagesWithoutSource()
    {
        $lng = [];
        foreach ($this->getSelectedLanguages() as $languages__value) {
            if ($languages__value['code'] === $this->getSourceLanguageCode()) {
                continue;
            }
            $lng[] = $languages__value;
        }
        return $lng;
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
