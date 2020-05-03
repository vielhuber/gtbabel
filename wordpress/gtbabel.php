<?php
/**
 * Plugin Name: Gtbabel
 * Plugin URI: https://github.com/vielhuber/gtbabel
 * Description: Instant server-side translation of any page.
 * Version: 2.9.7
 * Author: David Vielhuber
 * Author URI: https://vielhuber.de
 * License: free
 */
if (file_exists(__DIR__ . '/vendor/scoper-autoload.php')) {
    require_once __DIR__ . '/vendor/scoper-autoload.php';
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}
use vielhuber\gtbabel\Gtbabel;
use vielhuber\stringhelper\__;

class GtbabelWordPress
{
    private $gtbabel;

    public function __construct($gtbabel)
    {
        $this->gtbabel = $gtbabel;
        $this->installHook();
        $this->localizePlugin();
        $this->initBackend();
        $this->triggerPreventPublish();
        $this->addGutenbergSidebar();
        $this->showWizardNotice();
        $this->languagePickerWidget();
        $this->languagePickerShortcode();
        $this->disableAutoRedirect();
        $this->localizeJs();
        $this->setDefaultSettingsToOption();
        $this->startHook();
        $this->stopHook();
        $this->bindPotDownload();
    }

    private function disableAutoRedirect()
    {
        remove_action('template_redirect', 'redirect_canonical');
    }

    private function localizeJs()
    {
        $settings = get_option('gtbabel_settings');
        if (!empty($settings['localize_js'])) {
            add_action(
                'wp_head',
                function () use ($settings) {
                    $this->gtbabel->dom->outputJsLocalizationHelper($settings['localize_js']);
                },
                -1
            );
        }
    }

    private function startHook()
    {
        add_action('after_setup_theme', function () {
            $this->start();
        });
    }

    private function stopHook()
    {
        add_action(
            'shutdown',
            function () {
                $this->stop();
            },
            0
        );
    }

    private function start()
    {
        $settings = get_option('gtbabel_settings');
        // dynamically changed settings
        $settings['prevent_publish'] = !is_user_logged_in();
        $this->gtbabel->start($settings);
    }

    private function stop()
    {
        $this->gtbabel->stop();
    }

    private function reset()
    {
        $this->gtbabel->reset();
    }

    private function installHook()
    {
        register_activation_hook(__FILE__, function () {
            $this->setDefaultSettingsToOption();
        });
    }

    private function localizePlugin()
    {
        add_action('plugins_loaded', function () {
            load_plugin_textdomain('gtbabel-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
        });
    }

    private function bindPotDownload()
    {
        add_action('after_setup_theme', function () {
            if (isset($_GET['download']) && $_GET['download'] === 'pot') {
                $filename = $this->gtbabel->gettext->getLngFilename('pot', '_template');
                header('Content-Type: application/octet-stream');
                header('Content-Transfer-Encoding: Binary');
                header('Content-disposition: attachment; filename="' . basename($filename) . '"');
                readfile($filename);
                die();
            }
        });
    }

    private function setDefaultSettingsToOption()
    {
        if (get_option('gtbabel_settings') === false || get_option('gtbabel_settings') == '') {
            $lng_source = mb_strtolower(mb_substr(get_locale(), 0, 2));
            $languages = ['de', 'en'];
            if (!in_array($lng_source, $languages)) {
                $languages[] = $lng_source;
            }
            delete_option('gtbabel_settings'); // this is needed, because sometimes the option exists (with the value '')
            add_option(
                'gtbabel_settings',
                $this->gtbabel->settings->setupSettings([
                    'languages' => $languages,
                    'lng_source' => $lng_source,
                    'lng_folder' => $this->getPluginFileStorePath() . '/locales',
                    'log_folder' => $this->getPluginFileStorePath() . '/logs',
                    'exclude_urls' => ['/wp-admin', 'wp-login.php', 'wp-cron.php', 'wp-comments-post.php'],
                    'exclude_dom' => ['.notranslate', '.lngpicker', '#wpadminbar']
                ])
            );
        }
    }

    private function getPluginFileStorePath()
    {
        return '/' .
            trim(str_replace($this->gtbabel->utils->getDocRoot(), '', wp_upload_dir()['basedir']), '/') .
            '/gtbabel';
    }

    private function triggerPreventPublish()
    {
        add_action(
            'post_updated',
            function ($post_ID, $post_after, $post_before) {
                $post_before_status = get_post_status($post_before);
                $post_after_status = get_post_status($post_after);
                $post_before_url = get_permalink($post_before);
                $post_after_url = get_permalink($post_after);

                $trigger1 = $post_after_status === 'trash';
                $trigger2 =
                    ($post_before_status === 'auto-draft' && $post_after_status === 'publish') ||
                    ($post_before_status === 'draft' && $post_after_status === 'publish') ||
                    ($post_before_status === 'publish' && $post_after_status === 'draft');
                $trigger3 = $post_before_url != $post_after_url;

                // remove trashed
                if ($trigger1) {
                    $this->gtbabel->publish->edit($post_after_url, []);
                }
                // by default any post has prevent publish on for all languages
                if ($trigger2) {
                    $this->gtbabel->publish->edit(
                        $post_after_url,
                        $this->gtbabel->settings->getSelectedLanguageCodesWithoutSource()
                    );
                }
                // if a slug is changed, preserve settings in prevent publish
                if ($trigger3) {
                    $this->gtbabel->publish->change($post_before_url, $post_after_url);
                }
                // if a slug is changed, change old url to new in discovery log
                if ($trigger3) {
                    $this->gtbabel->log->discoveryLogChangeUrl(
                        $this->getNoCacheUrl($post_before_url),
                        $this->getNoCacheUrl($post_after_url)
                    );
                }

                if ($trigger1 || $trigger2 || $trigger3) {
                    $this->changeSetting('prevent_publish_urls', $this->gtbabel->settings->get('prevent_publish_urls'));
                }
            },
            10,
            3
        );
    }

    private function showWizardNotice()
    {
        if ($this->getSetting('wizard_finished') === true) {
            return;
        }
        add_action('admin_notices', function () {
            global $pagenow;
            if ($pagenow === 'admin.php' && $_GET['page'] === 'gtbabel-wizard') {
                return;
            }
            echo '<div class="notice">';
            echo '<p>' . __('Run the Gtbabel wizard in order to get started!', 'gtbabel-plugin') . '</p>';
            echo '<p>';
            echo '<a href="' . admin_url('admin.php?page=gtbabel-wizard') . '" class="button button-primary">';
            echo __('Start wizard', 'gtbabel-plugin');
            echo '</a>';
            echo '</p>';
            echo '</div>';
        });
    }

    private function addGutenbergSidebar()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'gtbabel-trans-links',
                __('Translations', 'gtbabel-plugin'),
                function ($post) {
                    echo '<ul>';
                    foreach (
                        $this->gtbabel->settings->getSelectedLanguagesWithoutSource()
                        as $languages__key => $languages__value
                    ) {
                        echo '<li><a href="' .
                            admin_url('admin.php?page=gtbabel-trans&post_id=' . $post->ID) .
                            '&lng=' .
                            $languages__key .
                            '&shared=0">' .
                            $languages__value .
                            '</a></li>';
                    }
                    echo '</ul>';
                },
                ['post', 'page'],
                'side',
                'high'
            );
        });
    }

    private function languagePickerWidget()
    {
        add_action('widgets_init', function () {
            register_widget(new gtbabel_lngpicker_widget());
        });
    }
    private function languagePickerShortcode()
    {
        add_shortcode('gtbabel_languagepicker', function () {
            $html = '';
            $html .= '<ul class="lngpicker">';
            foreach ($this->gtbabel->gettext->getLanguagePickerData() as $languagepicker__value) {
                $html .= '<li>';
                $html .=
                    '<a href="' .
                    $languagepicker__value['url'] .
                    '"' .
                    ($languagepicker__value['active'] ? ' class="active"' : '') .
                    '>';
                $html .= $languagepicker__value['label'];
                $html .= '</a>';
                $html .= '</li>';
            }
            $html .= '</ul>';
            return $html;
        });
    }

    private function initBackend()
    {
        add_action('admin_menu', function () {
            $menus = [];

            $menu = add_menu_page(
                'Gtbabel',
                'Gtbabel',
                'manage_options',
                'gtbabel-settings',
                function () {
                    $this->initBackendSettings();
                },
                'dashicons-admin-site-alt3',
                100
            );
            $menus[] = $menu;

            add_submenu_page(
                'gtbabel-settings',
                __('Settings', 'gtbabel-plugin'),
                __('Settings', 'gtbabel-plugin'),
                'manage_options',
                'gtbabel-settings'
            );

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('String translations', 'gtbabel-plugin'),
                __('String translations', 'gtbabel-plugin'),
                'manage_options',
                'gtbabel-trans',
                function () {
                    $this->initBackendStringTranslation();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Language picker', 'gtbabel-plugin'),
                __('Language picker', 'gtbabel-plugin'),
                'manage_options',
                'gtbabel-lngpicker',
                function () {
                    $this->initBackendLanguagePicker();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Translation services', 'gtbabel-plugin'),
                __('Translation services', 'gtbabel-plugin'),
                'manage_options',
                'gtbabel-services',
                function () {
                    $this->initBackendTranslationServices();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Setup wizard', 'gtbabel-plugin'),
                __('Setup wizard', 'gtbabel-plugin'),
                'manage_options',
                'gtbabel-wizard',
                function () {
                    $this->initBackendWizard();
                }
            );
            $menus[] = $submenu;

            foreach ($menus as $menus__value) {
                add_action('admin_print_styles-' . $menus__value, function () {
                    wp_enqueue_style('gtbabel-css', plugins_url('gtbabel.css', __FILE__));
                });
                add_action('admin_print_scripts-' . $menus__value, function () {
                    wp_enqueue_script('gtbabel-js', plugins_url('gtbabel.js', __FILE__));
                });
            }
        });
    }

    private function initBackendSettings()
    {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['save_settings'])) {
                check_admin_referer('gtbabel-settings');
                if (!empty($_POST['gtbabel'])) {
                    $settings = [];

                    // whitelist
                    foreach (
                        [
                            'languages',
                            'lng_source',
                            'lng_folder',
                            'log_folder',
                            'debug_translations',
                            'hide_languages',
                            'prefix_source_lng',
                            'redirect_root_domain',
                            'translate_text_nodes',
                            'translate_default_tag_nodes',
                            'html_lang_attribute',
                            'html_hreflang_tags',
                            'auto_add_translations_to_gettext',
                            'auto_translation',
                            'auto_translation_service',
                            'google_translation_api_key',
                            'microsoft_translation_api_key',
                            'stats_log',
                            'prevent_publish_urls',
                            'exclude_urls',
                            'exclude_dom',
                            'force_tokenize',
                            'include_dom',
                            'localize_js',
                            'wizard_finished'
                        ]
                        as $fields__value
                    ) {
                        if (!isset($_POST['gtbabel'][$fields__value])) {
                            continue;
                        }
                        $settings[$fields__value] = $_POST['gtbabel'][$fields__value];
                    }

                    // sanitize
                    $settings = __::array_map_deep($settings, function ($settings__value) {
                        return sanitize_textarea_field($settings__value);
                    });

                    foreach (
                        [
                            'prefix_source_lng',
                            'translate_text_nodes',
                            'translate_default_tag_nodes',
                            'html_lang_attribute',
                            'html_hreflang_tags',
                            'debug_translations',
                            'auto_add_translations_to_gettext',
                            'auto_translation',
                            'stats_log',
                            'wizard_finished'
                        ]
                        as $checkbox__value
                    ) {
                        if (@$settings[$checkbox__value] == '1') {
                            $settings[$checkbox__value] = true;
                        } else {
                            $settings[$checkbox__value] = false;
                        }
                    }
                    foreach (
                        [
                            'exclude_urls',
                            'exclude_dom',
                            'force_tokenize',
                            'google_translation_api_key',
                            'microsoft_translation_api_key'
                        ]
                        as $exclude__value
                    ) {
                        $post_data = $settings[$exclude__value];
                        $settings[$exclude__value] = [];
                        if ($post_data != '') {
                            foreach (explode(PHP_EOL, $post_data) as $post_data__value) {
                                $settings[$exclude__value][] = trim($post_data__value);
                            }
                        }
                    }

                    $post_data = $settings['prevent_publish_urls'];
                    $settings['prevent_publish_urls'] = [];
                    if ($post_data != '') {
                        foreach (explode(PHP_EOL, $post_data) as $post_data__value) {
                            $post_data__value_parts = explode(':', $post_data__value);
                            if (!empty($post_data__value_parts)) {
                                $settings['prevent_publish_urls'][$post_data__value_parts[0]] = explode(
                                    ',',
                                    __::trim_whitespace($post_data__value_parts[1])
                                );
                            }
                        }
                    }

                    $post_data = $settings['hide_languages'];
                    if (array_key_exists('/*', $settings['prevent_publish_urls'])) {
                        unset($settings['prevent_publish_urls']['/*']);
                    }
                    if (!empty($post_data)) {
                        $settings['prevent_publish_urls']['/*'] = [];
                        foreach ($post_data as $post_data__key => $post_data__value) {
                            foreach (
                                $settings['prevent_publish_urls']
                                as $prevent_publish_urls__key => $prevent_publish_urls__value
                            ) {
                                if ($prevent_publish_urls__key !== '/*') {
                                    continue;
                                }
                                if (!in_array($post_data__key, $prevent_publish_urls__value)) {
                                    $settings['prevent_publish_urls'][$prevent_publish_urls__key][] = $post_data__key;
                                }
                            }
                        }
                    }
                    unset($settings['hide_languages']);

                    $post_data = $settings['include_dom'];
                    $settings['include_dom'] = [];
                    if (!empty(@$post_data['selector'])) {
                        foreach ($post_data['selector'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['selector'][$post_data__key] == '' &&
                                @$post_data['attribute'][$post_data__key] == '' &&
                                @$post_data['context'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['include_dom'][] = [
                                'selector' => $post_data['selector'][$post_data__key],
                                'attribute' => $post_data['attribute'][$post_data__key],
                                'context' => $post_data['context'][$post_data__key]
                            ];
                        }
                    }

                    $post_data = $settings['localize_js'];
                    $settings['localize_js'] = [];
                    if (!empty(@$post_data['string'])) {
                        foreach ($post_data['string'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['string'][$post_data__key] == '' &&
                                @$post_data['context'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['localize_js'][] = [
                                $post_data['string'][$post_data__key],
                                $post_data['context'][$post_data__key] != ''
                                    ? $post_data['context'][$post_data__key]
                                    : null
                            ];
                        }
                    }

                    $settings['languages'][$settings['lng_source']] = '1';
                    $settings['languages'] = array_keys($settings['languages']);

                    update_option('gtbabel_settings', $settings);
                    // refresh gtbabel with new options
                    $this->start();
                }
            }

            if (isset($_POST['reset_settings'])) {
                delete_option('gtbabel_settings');
                $this->setDefaultSettingsToOption();
                $this->start();
            }

            if (isset($_POST['reset_translations'])) {
                $this->reset();
            }

            $message =
                '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                __('Successfully edited', 'gtbabel-plugin') .
                '</p></div>';
        }

        $settings = get_option('gtbabel_settings');

        echo '<div class="gtbabel gtbabel--settings wrap">';
        echo '<form class="gtbabel__form" method="post" action="' . admin_url('admin.php?page=gtbabel-settings') . '">';
        wp_nonce_field('gtbabel-settings');
        echo '<input type="hidden" name="gtbabel[wizard_finished]" value="' .
            (isset($settings['wizard_finished']) && $settings['wizard_finished'] == 1 ? 1 : 0) .
            '" />';
        echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
        echo $message;
        echo '<h2 class="gtbabel__subtitle">' . __('Settings', 'gtbabel-plugin') . '</h2>';
        echo '<ul class="gtbabel__fields">';
        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Languages', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<ul class="gtbabel__languagelist">';
        foreach ($this->gtbabel->settings->getDefaultLanguages() as $languages__key => $languages__value) {
            echo '<li class="gtbabel__languagelist-item">';
            echo '<label class="gtbabel__languagelist-label">';
            echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[languages][' .
                $languages__key .
                ']"' .
                (in_array($languages__key, $settings['languages']) == '1' ? ' checked="checked"' : '') .
                ' value="1"' .
                ($settings['lng_source'] === $languages__key ? ' disabled="disabled"' : '') .
                ' />';
            echo '<span class="gtbabel__languagelist-label-inner">' . $languages__value . '</span>';
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_lng_source" class="gtbabel__label">';
        echo __('Source language', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_lng_source" name="gtbabel[lng_source]">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach ($this->gtbabel->settings->getDefaultLanguages() as $languages__key => $languages__value) {
            echo '<option value="' .
                $languages__key .
                '"' .
                ($settings['lng_source'] == $languages__key ? ' selected="selected"' : '') .
                '>' .
                $languages__value .
                '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_lng_folder" class="gtbabel__label">';
        echo __('Language folder', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input" type="text" id="gtbabel_lng_folder" name="gtbabel[lng_folder]" value="' .
            $settings['lng_folder'] .
            '" />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_log_folder" class="gtbabel__label">';
        echo __('Log folder', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input" type="text" id="gtbabel_log_folder" name="gtbabel[log_folder]" value="' .
            $settings['log_folder'] .
            '" />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_debug_translations" class="gtbabel__label">';
        echo __('Enable debug mode', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_debug_translations" name="gtbabel[debug_translations]" value="1"' .
            ($settings['debug_translations'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_hide_languages" class="gtbabel__label">';
        echo __('Hide languages', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<ul class="gtbabel__languagelist">';
        foreach (
            $this->gtbabel->settings->getSelectedLanguagesWithoutSource()
            as $languages__key => $languages__value
        ) {
            $checked = false;
            if (!empty($settings['prevent_publish_urls'])) {
                foreach (
                    $settings['prevent_publish_urls']
                    as $prevent_publish_urls__key => $prevent_publish_urls__value
                ) {
                    if (
                        $prevent_publish_urls__key === '/*' &&
                        in_array($languages__key, $prevent_publish_urls__value)
                    ) {
                        $checked = true;
                        break;
                    }
                }
            }
            echo '<li class="gtbabel__languagelist-item">';
            echo '<label class="gtbabel__languagelist-label">';
            echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[hide_languages][' .
                $languages__key .
                ']"' .
                ($checked === true ? ' checked="checked"' : '') .
                ' value="1" />';
            echo '<span class="gtbabel__languagelist-label-inner">' . $languages__value . '</span>';
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_prefix_source_lng" class="gtbabel__label">';
        echo __('Prefix source language urls', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_prefix_source_lng" name="gtbabel[prefix_source_lng]" value="1"' .
            ($settings['prefix_source_lng'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_redirect_root_domain" class="gtbabel__label">';
        echo __('Redirect root domain to', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_redirect_root_domain" name="gtbabel[redirect_root_domain]">';
        echo '<option value="browser"' .
            ($settings['redirect_root_domain'] == 'browser' ? ' selected="selected"' : '') .
            '>' .
            __('Browser language', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="source"' .
            ($settings['redirect_root_domain'] == 'source' ? ' selected="selected"' : '') .
            '>' .
            __('Source language', 'gtbabel-plugin') .
            '</option>';
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_translate_text_nodes" class="gtbabel__label">';
        echo __('Translate text nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_translate_text_nodes" name="gtbabel[translate_text_nodes]" value="1"' .
            ($settings['translate_text_nodes'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_translate_default_tag_nodes" class="gtbabel__label">';
        echo __('Translate additional nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_translate_default_tag_nodes" name="gtbabel[translate_default_tag_nodes]" value="1"' .
            ($settings['translate_default_tag_nodes'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_html_lang_attribute" class="gtbabel__label">';
        echo __('Set html lang attribute', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_html_lang_attribute" name="gtbabel[html_lang_attribute]" value="1"' .
            ($settings['html_lang_attribute'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_html_hreflang_tags" class="gtbabel__label">';
        echo __('Add html hreflang tags', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_html_hreflang_tags" name="gtbabel[html_hreflang_tags]" value="1"' .
            ($settings['html_hreflang_tags'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_add_translations_to_gettext" class="gtbabel__label">';
        echo __('Auto add translations to gettext', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_auto_add_translations_to_gettext" name="gtbabel[auto_add_translations_to_gettext]" value="1"' .
            ($settings['auto_add_translations_to_gettext'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_translation" class="gtbabel__label">';
        echo __('Enable automatic translation', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_auto_translation" name="gtbabel[auto_translation]" value="1"' .
            ($settings['auto_translation'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_translation_service" class="gtbabel__label">';
        echo __('Translation service', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_auto_translation_service" name="gtbabel[auto_translation_service]">';
        echo '<option value="">&ndash;&ndash;</option>';
        echo '<option value="google"' .
            ($settings['auto_translation_service'] == 'google' ? ' selected="selected"' : '') .
            '>Google</option>';
        echo '<option value="microsoft"' .
            ($settings['auto_translation_service'] == 'microsoft' ? ' selected="selected"' : '') .
            '>Microsoft</option>';
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_google_translation_api_key" class="gtbabel__label">';
        echo __('Google Translation API Key', 'gtbabel-plugin') .
            ' (<a href="https://console.cloud.google.com/apis/library/translate.googleapis.com" target="_blank">' .
            __('Link', 'gtbabel-plugin') .
            '</a>)';
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_google_translation_api_key" name="gtbabel[google_translation_api_key]">' .
            (is_array($settings['google_translation_api_key'])
                ? implode(PHP_EOL, $settings['google_translation_api_key'])
                : $settings['google_translation_api_key']) .
            '</textarea>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_microsoft_translation_api_key" class="gtbabel__label">';
        echo __('Microsoft Translation API Key', 'gtbabel-plugin') .
            ' (<a href="https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api" target="_blank">' .
            __('Link', 'gtbabel-plugin') .
            '</a>)';
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_microsoft_translation_api_key" name="gtbabel[microsoft_translation_api_key]">' .
            (is_array($settings['microsoft_translation_api_key'])
                ? implode(PHP_EOL, $settings['microsoft_translation_api_key'])
                : $settings['microsoft_translation_api_key']) .
            '</textarea>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_stats_log" class="gtbabel__label">';
        echo __('Enable translation api usage stats', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_stats_log" name="gtbabel[stats_log]" value="1"' .
            ($settings['stats_log'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_prevent_publish_urls" class="gtbabel__label">';
        echo __('Prevent publish of pages', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_prevent_publish_urls" name="gtbabel[prevent_publish_urls]">';
        if (!empty($settings['prevent_publish_urls'])) {
            echo implode(
                PHP_EOL,
                array_map(
                    function ($prevent_publish_urls__value, $prevent_publish_urls__key) {
                        return $prevent_publish_urls__key . ':' . implode(',', $prevent_publish_urls__value);
                    },
                    $settings['prevent_publish_urls'],
                    array_keys($settings['prevent_publish_urls'])
                )
            );
        }
        echo '</textarea>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_exclude_urls" class="gtbabel__label">';
        echo __('Exclude urls', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_exclude_urls" name="gtbabel[exclude_urls]">' .
            implode(PHP_EOL, $settings['exclude_urls']) .
            '</textarea>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_exclude_dom" class="gtbabel__label">';
        echo __('Exclude dom nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_exclude_dom" name="gtbabel[exclude_dom]">' .
            implode(PHP_EOL, $settings['exclude_dom']) .
            '</textarea>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_force_tokenize" class="gtbabel__label">';
        echo __('Force tokenize', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_force_tokenize" name="gtbabel[force_tokenize]">' .
            implode(PHP_EOL, $settings['force_tokenize']) .
            '</textarea>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Include dom nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['include_dom'])) {
            $settings['include_dom'] = [['selector' => '', 'attribute' => '', 'context' => '']];
        }
        foreach ($settings['include_dom'] as $include_dom__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-3">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[include_dom][selector][]" value="' .
                $include_dom__value['selector'] .
                '" placeholder="selector" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[include_dom][attribute][]" value="' .
                $include_dom__value['attribute'] .
                '" placeholder="attribute" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[include_dom][context][]" value="' .
                $include_dom__value['context'] .
                '" placeholder="context" />';
            echo '<a href="#" class="gtbabel__repeater-remove button button-secondary">' .
                __('Remove', 'gtbabel-plugin') .
                '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<a href="#" class="gtbabel__repeater-add button button-secondary">' .
            __('Add', 'gtbabel-plugin') .
            '</a>';
        echo '</div>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Provide strings in JavaScript', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['localize_js'])) {
            $settings['localize_js'] = [['', '']];
        }
        foreach ($settings['localize_js'] as $localize_js__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[localize_js][string][]" value="' .
                $localize_js__value[0] .
                '" placeholder="string" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[localize_js][context][]" value="' .
                $localize_js__value[1] .
                '" placeholder="context" />';
            echo '<a href="#" class="gtbabel__repeater-remove button button-secondary">' .
                __('Remove', 'gtbabel-plugin') .
                '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<a href="#" class="gtbabel__repeater-add button button-secondary">' .
            __('Add', 'gtbabel-plugin') .
            '</a>';
        echo '</div>';
        echo '</div>';
        echo '</li>';

        echo '</ul>';

        echo '<input class="gtbabel__submit button button-primary" name="save_settings" value="' .
            __('Save', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '<h2 class="gtbabel__subtitle">' . __('Translate complete website', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">' . __('Only new strings are translated.', 'gtbabel-plugin') . '</p>';
        echo '<ul class="gtbabel__fields">';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_delete_unused" class="gtbabel__label">';
        echo __('Delete unused translations', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" id="gtbabel_delete_unused" type="checkbox" value="1" />';
        echo '</div>';
        echo '</li>';
        echo '</ul>';

        $this->initBackendAutoTranslate('page=gtbabel-settings');

        if ($settings['stats_log'] == '1') {
            echo '<div class="gtbabel__stats-log">';
            echo '<h2 class="gtbabel__subtitle">' . __('Translation api usage stats', 'gtbabel-plugin') . '</h2>';
            echo $this->showStatsLog();
            echo '</div>';
        }

        echo '<h2 class="gtbabel__subtitle">' . __('Reset settings', 'gtbabel-plugin') . '</h2>';
        echo '<input data-question="' .
            __('Please enter REMOVE to confirm!', 'gtbabel-plugin') .
            '" class="gtbabel__submit gtbabel__submit--reset button button-secondary" name="reset_settings" value="' .
            __('Reset', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '<h2 class="gtbabel__subtitle">' . __('Reset translations', 'gtbabel-plugin') . '</h2>';
        echo '<input data-question="' .
            __('Please enter REMOVE to confirm!', 'gtbabel-plugin') .
            '" class="gtbabel__submit gtbabel__submit--reset button button-secondary" name="reset_translations" value="' .
            __('Reset', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '</form>';
        echo '</div>';
    }

    private function initBackendStringTranslation()
    {
        $message = '';

        $url = null;
        $post_id = null;
        $lng = null;
        if (isset($_GET['post_id']) && $_GET['post_id'] != '' && is_numeric($_GET['post_id'])) {
            $post_id = intval($_GET['post_id']);
            $url = get_permalink($post_id);
        }
        if (isset($_GET['url']) && $_GET['url'] != '') {
            $url = esc_url($_GET['url']);
        }
        if (isset($_GET['lng']) && $_GET['lng'] != '') {
            $lng = sanitize_textarea_field($_GET['lng']);
        }
        $source_url_published = null;
        if ($url !== null) {
            $source_url_published = $this->isUrlPublished($url);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['translations_submit'])) {
                check_admin_referer('gtbabel-trans-save-translations');
                if (!empty(@$_POST['gtbabel'])) {
                    // sanitize
                    $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function (
                        $settings__value,
                        $settings__key,
                        $key_chain
                    ) {
                        if (in_array('str', $key_chain)) {
                            return wp_kses_post($settings__value);
                        }
                        return sanitize_textarea_field($settings__value);
                    });

                    foreach ($_POST['gtbabel'] as $post__key => $post__value) {
                        if (!empty(@$post__value['translations'])) {
                            foreach ($post__value['translations'] as $translations__key => $translations__value) {
                                $this->gtbabel->gettext->editTranslationFromFiles(
                                    $post__key,
                                    $translations__key,
                                    @$translations__value['str'],
                                    @$translations__value['checked']
                                );
                            }
                        }
                        if (@$post__value['delete'] == '1') {
                            $this->gtbabel->gettext->deleteTranslationFromFiles($post__key);
                        }
                        if (array_key_exists('shared', $post__value)) {
                            $this->gtbabel->gettext->editSharedValueFromFiles($post__key, $post__value['shared']);
                        }
                    }
                }
                if (isset($_POST['translations_submit']['publish'])) {
                    if (isset($_POST['translations_submit']['publish'][1])) {
                        $this->gtbabel->publish->publish($url, $lng);
                    } else {
                        $this->gtbabel->publish->unpublish($url, $lng);
                    }
                    $this->changeSetting('prevent_publish_urls', $this->gtbabel->settings->get('prevent_publish_urls'));
                }
            }
            if (isset($_POST['save_publish'])) {
                check_admin_referer('gtbabel-trans-save-publish');
                if (!empty($_POST['gtbabel'])) {
                    // sanitize
                    $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function ($settings__value) {
                        return sanitize_textarea_field($settings__value);
                    });
                    foreach ($_POST['gtbabel'] as $post__key => $post__value) {
                        $url = $post__key;
                        $lngs = [];
                        foreach ($post__value as $post__value__key => $post__value__value) {
                            if ($post__value__value == '1') {
                                $lngs[] = $post__value__key;
                            }
                        }
                        $this->gtbabel->publish->edit($url, $lngs);
                    }
                    $this->changeSetting('prevent_publish_urls', $this->gtbabel->settings->get('prevent_publish_urls'));
                }
            }
            $message =
                '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                __('Successfully edited', 'gtbabel-plugin') .
                '</p></div>';
        }

        $translations = $this->initBackendTranslations($url, $lng);

        $pagination = $this->initBackendPagination($translations);

        if ($pagination->count > 0) {
            $translations = array_slice(
                $translations,
                ($pagination->cur - 1) * $pagination->per_page,
                $pagination->per_page
            );
        }

        echo '<div class="gtbabel gtbabel--trans wrap">';
        echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
        echo $message;
        echo '<h2 class="gtbabel__subtitle">' . __('String translations', 'gtbabel-plugin') . '</h2>';

        if ($url !== null && $lng !== null && $post_id !== null) {
            echo '<div class="gtbabel__transmeta">';

            echo '<p class="gtbabel__transmeta-mainlink">';
            if ($this->gtbabel->settings->getSourceLanguageCode() === $lng) {
                $link_public = $url;
            } else {
                $link_public = $this->gtbabel->gettext->getUrlTranslationInLanguage($lng, $url);
            }
            echo '<a href="' . $link_public . '" target="_blank">' . $link_public . '</a>';
            echo '</p>';

            echo '<ul class="gtbabel__transmeta-list">';
            foreach ($this->gtbabel->settings->getSelectedLanguages() as $lng__key => $lng__value) {
                if ($this->gtbabel->settings->getSourceLanguageCode() === $lng__key) {
                    $link_edit = get_edit_post_link($post_id);
                } else {
                    $link_edit = admin_url(
                        'admin.php?page=gtbabel-trans&post_id=' . $post_id . '&lng=' . $lng__key . '&shared=0'
                    );
                }
                echo '<li class="gtbabel__transmeta-listitem">';
                if ($lng !== $lng__key) {
                    echo '<a class="gtbabel__transmeta-listitem-link" href="' . $link_edit . '">';
                }
                echo $lng__value;
                if ($lng !== $lng__key) {
                    echo '</a>';
                }
                echo '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        echo '<div class="gtbabel__search' . ($post_id !== null ? ' gtbabel__search--post-id' : '') . '">';
        echo '<form class="gtbabel__form" method="get" action="' . admin_url('admin.php') . '">';
        echo '<input type="hidden" name="page" value="gtbabel-trans" />';
        echo '<input type="hidden" name="p" value="1" />';
        echo '<input type="hidden" name="lng" value="' . ($lng !== null ? $lng : '') . '" />';
        echo '<input type="hidden" name="post_id" value="' . ($post_id !== null ? $post_id : '') . '" />';
        echo '<input class="gtbabel__input" type="text" name="url" value="' .
            ($url !== null ? $url : '') .
            '" placeholder="' .
            __('URL in source language', 'gtbabel-plugin') .
            '" />';
        echo '<input class="gtbabel__input" type="text" name="s" value="' .
            (isset($_GET['s']) ? wp_kses_post($_GET['s']) : '') .
            '" placeholder="' .
            __('Search term', 'gtbabel-plugin') .
            '" />';
        echo '<select class="gtbabel__input gtbabel__input--select" name="checked">';
        echo '<option value="">&ndash;&ndash;</option>';
        echo '<option value="0"' .
            (isset($_GET['checked']) && $_GET['checked'] == '0' ? ' selected="selected"' : '') .
            '>' .
            __('Not checked', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="1"' .
            (isset($_GET['checked']) && $_GET['checked'] == '1' ? ' selected="selected"' : '') .
            '>' .
            __('Checked', 'gtbabel-plugin') .
            '</option>';
        echo '</select>';
        echo '<select class="gtbabel__input gtbabel__input--select" name="shared">';
        echo '<option value="">&ndash;&ndash;</option>';
        echo '<option value="0"' .
            (isset($_GET['shared']) && $_GET['shared'] == '0' ? ' selected="selected"' : '') .
            '>' .
            __('Not shared', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="1"' .
            (isset($_GET['shared']) && $_GET['shared'] == '1' ? ' selected="selected"' : '') .
            '>' .
            __('Shared', 'gtbabel-plugin') .
            '</option>';
        echo '</select>';
        echo '<input class="gtbabel__submit button button-secondary" value="' .
            __('Search', 'gtbabel-plugin') .
            '" type="submit" />';
        echo '</form>';
        echo '</div>';

        if (!empty($translations)) {
            echo '<p class="gtbabel__paragraph">' .
                __('Translations available', 'gtbabel-plugin') .
                ': ' .
                $pagination->count .
                '</p>';

            echo '<form class="gtbabel__form" method="post" action="' .
                $this->buildTranslationFormUrl($pagination->cur) .
                '">';
            wp_nonce_field('gtbabel-trans-save-translations');

            echo '<table class="gtbabel__table">';
            echo '<thead class="gtbabel__table-head">';
            echo '<tr class="gtbabel__table-row">';
            echo '<td class="gtbabel__table-cell">' . $this->gtbabel->settings->getSourceLanguageLabel() . '</td>';
            foreach (
                $this->gtbabel->settings->getSelectedLanguagesWithoutSource()
                as $languages__key => $languages__value
            ) {
                if ($lng !== null && $lng !== $languages__key) {
                    continue;
                }
                echo '<td class="gtbabel__table-cell">' . $languages__value . '</td>';
            }
            echo '<td class="gtbabel__table-cell">' . __('Context', 'gtbabel-plugin') . '</td>';
            echo '<td class="gtbabel__table-cell">' . __('Shared', 'gtbabel-plugin') . '</td>';
            echo '<td class="gtbabel__table-cell">' . __('Delete', 'gtbabel-plugin') . '</td>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody class="gtbabel__table-body">';
            foreach ($translations as $translations__key => $translations__value) {
                echo '<tr class="gtbabel__table-row">';
                echo '<td class="gtbabel__table-cell">';
                echo '<textarea class="gtbabel__input gtbabel__input--textarea" name="gtbabel[' .
                    $translations__key .
                    '][orig]" disabled="disabled">' .
                    $translations__value['orig'] .
                    '</textarea>';
                echo '</td>';
                foreach (
                    $this->gtbabel->settings->getSelectedLanguagesWithoutSource()
                    as $languages__key => $languages__value
                ) {
                    if ($lng !== null && $lng !== $languages__key) {
                        continue;
                    }
                    echo '<td class="gtbabel__table-cell">';
                    echo '<input title="' .
                        __('String checked', 'gtbabel-plugin') .
                        '" class="gtbabel__input gtbabel__input--checkbox gtbabel__input--on-change gtbabel__input--submit-unchecked gtbabel__input--check-translation" type="checkbox" data-name="gtbabel[' .
                        $translations__key .
                        '][translations][' .
                        $languages__key .
                        '][checked]" value="1"' .
                        ($translations__value['translations'][$languages__key]['checked'] == '1'
                            ? ' checked="checked"'
                            : '') .
                        ' />';
                    echo '<textarea class="gtbabel__input gtbabel__input--textarea gtbabel__input--on-change" data-name="gtbabel[' .
                        $translations__key .
                        '][translations][' .
                        $languages__key .
                        '][str]">' .
                        $translations__value['translations'][$languages__key]['str'] .
                        '</textarea>';
                    echo '</td>';
                }
                echo '<td class="gtbabel__table-cell">';
                echo '<textarea class="gtbabel__input gtbabel__input--textarea" name="gtbabel[' .
                    $translations__key .
                    '][context]" disabled="disabled">' .
                    $translations__value['context'] .
                    '</textarea>';
                echo '</td>';
                echo '<td class="gtbabel__table-cell">';
                echo '<input disabled="disabled" class="gtbabel__input gtbabel__input--checkbox gtbabel__input--on-change gtbabel__input--submit-unchecked" type="checkbox" data-name="gtbabel[' .
                    $translations__key .
                    '][shared]" value="1"' .
                    ($translations__value['shared'] == '1' ? ' checked="checked"' : '') .
                    ' />';
                echo '</td>';
                echo '<td class="gtbabel__table-cell">';
                echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[' .
                    $translations__key .
                    '][delete]" value="1" />';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            echo $pagination->html;

            if ($url !== null && $lng !== null && $source_url_published === true) {
                if ($this->gtbabel->publish->isPrevented($url, $lng, false) === true) {
                    echo '<input class="gtbabel__submit button button-secondary" name="translations_submit[save]" value="' .
                        __('Save', 'gtbabel-plugin') .
                        '" type="submit" />';
                    echo '<input class="gtbabel__submit button button-primary" name="translations_submit[publish][1]" value="' .
                        __('Publish', 'gtbabel-plugin') .
                        '" type="submit" />';
                } else {
                    echo '<input class="gtbabel__submit button button-secondary" name="translations_submit[publish][0]" value="' .
                        __('Set to draft', 'gtbabel-plugin') .
                        '" type="submit" />';
                    echo '<input class="gtbabel__submit button button-primary" name="translations_submit[save]" value="' .
                        __('Save', 'gtbabel-plugin') .
                        '" type="submit" />';
                }
            } else {
                echo '<input class="gtbabel__submit button button-primary" name="translations_submit[save]" value="' .
                    __('Save', 'gtbabel-plugin') .
                    '" type="submit" />';
            }

            echo '</form>';
        } else {
            echo '<p>' . __('No translations available.', 'gtbabel-plugin') . '</p>';
        }

        echo '</div>';
    }

    private function initBackendTranslationServices()
    {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['upload_file'])) {
                check_admin_referer('gtbabel-services-upload-file');
                $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });
                $_FILES['gtbabel'] = __::array_map_deep($_FILES['gtbabel'], function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });
                if (@$_POST['gtbabel']['language'] != '' && @$_FILES['gtbabel']['name']['file'] != '') {
                    $extension = strtolower(end(explode('.', $_FILES['gtbabel']['name']['file'])));
                    $allowed_extension = [
                        'po' => ['application/octet-stream']
                    ];
                    if (
                        array_key_exists($extension, $allowed_extension) &&
                        in_array($_FILES['gtbabel']['type']['file'], $allowed_extension[$extension]) &&
                        $_FILES['gtbabel']['size']['file'] < 4000 * 1024 &&
                        $_FILES['gtbabel']['error']['file'] == 0
                    ) {
                        move_uploaded_file(
                            $_FILES['gtbabel']['tmp_name']['file'],
                            $this->gtbabel->gettext->getLngFilename('po', $_POST['gtbabel']['language'])
                        );
                        $this->gtbabel->gettext->convertPoToMo(
                            $this->gtbabel->gettext->getLngFilename('po', $_POST['gtbabel']['language'])
                        );
                        $message =
                            '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                            __('Successfully uploaded', 'gtbabel-plugin') .
                            '</p></div>';
                    } else {
                        $message =
                            '<div class="gtbabel__notice notice notice-error is-dismissible"><p>' .
                            __('An error occurred', 'gtbabel-plugin') .
                            '</p></div>';
                    }
                }
            }
        }

        echo '<div class="gtbabel gtbabel--services wrap">';
        echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
        echo $message;
        echo '<h2 class="gtbabel__subtitle">' . __('Translation services', 'gtbabel-plugin') . '</h2>';

        echo '<ol class="gtbabel__list">';
        echo '<li class="gtbabel__listitem">';
        echo sprintf(
            __('Register and login at %sICanLocalize%s.', 'gtbabel-plugin'),
            '<a href="https://www.icanlocalize.com" target="_blank">',
            '</a>'
        );
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo sprintf(
            __(
                'Create a new %sSoftware localization project%s and pick the same original / target languages as in Gtbabel.',
                'gtbabel-plugin'
            ),
            '<a href="https://www.icanlocalize.com/text_resources/new" target="_blank">',
            '</a>'
        );
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo sprintf(
            __('Upload your current .pot-Template file, which can be downloaded %shere%s.', 'gtbabel-plugin'),
            '<a href="' . admin_url('admin.php?page=gtbabel-services&download=pot') . '">',
            '</a>'
        );
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo __('Add all strings for translation.', 'gtbabel-plugin');
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo __('Review and place the translation order.', 'gtbabel-plugin');
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo __('Wait for the job to finish and get back individual .po-files.', 'gtbabel-plugin');
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo __('Reupload the .po-files via the following upload-form.', 'gtbabel-plugin');
        echo '</li>';
        echo '</ol>';

        echo '<h2 class="gtbabel__subtitle">' . __('File upload', 'gtbabel-plugin') . '</h2>';

        echo '<form enctype="multipart/form-data" class="gtbabel__form" method="post" action="' .
            admin_url('admin.php?page=gtbabel-services') .
            '">';
        wp_nonce_field('gtbabel-services-upload-file');
        echo '<ul class="gtbabel__fields">';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_language" class="gtbabel__label">';
        echo __('Language', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select required="required" class="gtbabel__input gtbabel__input--select" id="gtbabel_language" name="gtbabel[language]">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach (
            $this->gtbabel->settings->getSelectedLanguagesWithoutSource()
            as $languages__key => $languages__value
        ) {
            echo '<option value="' . $languages__key . '">' . $languages__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_file" class="gtbabel__label">';
        echo __('.po-File', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input required="required" class="gtbabel__input gtbabel__input--file" type="file" name="gtbabel[file]" id="gtbabel_file" accept=".po" />';
        echo '</div>';
        echo '</li>';
        echo '</ul>';

        echo '<input class="gtbabel__submit button button-primary" name="upload_file" value="' .
            __('Upload', 'gtbabel-plugin') .
            '" type="submit" />';
        echo '</form>';
        echo '</div>';
    }

    private function initBackendLanguagePicker()
    {
        echo '<div class="gtbabel gtbabel--lngpicker wrap">';
        echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
        echo $this->initBackendLanguagePickerContent();
        echo '</div>';
    }

    private function initBackendLanguagePickerContent()
    {
        echo '<p class="gtbabel__paragraph">';
        echo __(
            'Essentially, there are 3 different ways of adding a language picker to your website.',
            'gtbabel-plugin'
        );
        echo '</p>';

        echo '<h2 class="gtbabel__subtitle">' . __('Widget', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">';
        if (count(wp_get_sidebars_widgets()) > 1) {
            echo sprintf(
                __('Simply add the %sLanguage picker widget%s to one of your sidebars.', 'gtbabel-plugin'),
                '<a href="' . admin_url('widgets.php') . '">',
                '</a>'
            );
        } else {
            echo __(
                'Your theme does not have any sidebars. Register one first in order to use the Language picker widget.',
                'gtbabel-plugin'
            );
        }
        echo '</p>';

        echo '<h2 class="gtbabel__subtitle">' . __('Shortcode', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">';
        echo sprintf(__('Just add %s to your code.', 'gtbabel-plugin'), '<code>[gtbabel_languagepicker]</code>');
        echo '</p>';

        echo '<h2 class="gtbabel__subtitle">' . __('Template', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">';
        echo __('If you need more control, use the following php-code:', 'gtbabel-plugin');
        echo '</p>';
        echo '<code class="gtbabel__code">';
        $code = <<<'EOD'
if(function_exists('gtbabel_languagepicker')) {
    echo '<ul class="lngpicker">';
    foreach(gtbabel_languagepicker() as $languagepicker__value) {
        echo '<li>';
            echo '<a href="'.$languagepicker__value['url'].'"'.($languagepicker__value['active']?' class="active"':'').'>';
                echo $languagepicker__value['label'];
            echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
}
EOD;
        echo htmlentities($code);
        echo '</code>';
    }

    private function getBackendWizardStep()
    {
        $step = 1;
        if (isset($_GET['step']) && is_numeric($_GET['step'])) {
            $step = intval($_GET['step']);
        }
        return $step;
    }

    private function initBackendWizard()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['save_step'])) {
                $settings = [];

                // whitelist
                foreach (['languages', 'google_translation_api_key'] as $fields__value) {
                    if (!isset($_POST['gtbabel'][$fields__value])) {
                        continue;
                    }
                    $settings[$fields__value] = $_POST['gtbabel'][$fields__value];
                }

                // sanitize
                $settings = __::array_map_deep($settings, function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });

                // make changes
                if ($this->getBackendWizardStep() === 2) {
                    check_admin_referer('gtbabel-wizard-step-1');
                    $settings['languages'][$this->getSetting('lng_source')] = '1';
                    $settings['languages'] = array_keys($settings['languages']);
                    $this->changeSetting('languages', $settings['languages']);
                }
                if ($this->getBackendWizardStep() === 3) {
                    check_admin_referer('gtbabel-wizard-step-2');
                    $existing = $this->getSetting('google_translation_api_key');
                    $existing[0] = $settings['google_translation_api_key'];
                    $this->changeSetting('google_translation_api_key', $existing);
                    $this->changeSetting('auto_translation', true);
                }
                if ($this->getBackendWizardStep() === 5) {
                    check_admin_referer('gtbabel-wizard-step-4');
                    $this->changeSetting('wizard_finished', true);
                }

                // restart
                $this->start();
            }
        }

        $settings = get_option('gtbabel_settings');

        echo '<div class="gtbabel gtbabel--wizard">';

        echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';

        // progressbar
        echo '<div class="gtbabel__progress">';
        echo '<div class="gtbabel__progress-inner" style="background-color:' .
            $this->getUserBackendThemeBackgroundColor() .
            ';width:' .
            round((($this->getBackendWizardStep() - 1) / 4) * 100) .
            '%;"></div>';
        echo '</div>';

        // 1
        if ($this->getBackendWizardStep() === 1) {
            echo '<form class="gtbabel__form" method="post" action="' .
                admin_url('admin.php?page=gtbabel-wizard&step=2') .
                '">';
            wp_nonce_field('gtbabel-wizard-step-1');
            echo '<div class="gtbabel__wizard-step">';
            echo '<h2 class="gtbabel__wizard-steptitle">' . __('Choose languages', 'gtbabel-plugin') . '</h2>';
            echo '<ul class="gtbabel__languagelist">';
            foreach ($this->gtbabel->settings->getDefaultLanguages() as $languages__key => $languages__value) {
                echo '<li class="gtbabel__languagelist-item">';
                echo '<label class="gtbabel__languagelist-label">';
                echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[languages][' .
                    $languages__key .
                    ']"' .
                    (in_array($languages__key, $settings['languages']) == '1' ? ' checked="checked"' : '') .
                    ' value="1"' .
                    ($settings['lng_source'] === $languages__key ? ' disabled="disabled"' : '') .
                    ' />';
                echo '<span class="gtbabel__languagelist-label-inner">' . $languages__value . '</span>';
                echo '</label>';
                echo '</li>';
            }
            echo '</ul>';
            echo '<div class="gtbabel__wizard-buttons">';
            echo '<input class="gtbabel__submit button button-primary" name="save_step" value="' .
                __('Next', 'gtbabel-plugin') .
                '" type="submit" />';
            echo '</div>';
            echo '</div>';
            echo '</form>';
        }

        // 2
        if ($this->getBackendWizardStep() === 2) {
            echo '<form class="gtbabel__form" method="post" action="' .
                admin_url('admin.php?page=gtbabel-wizard&step=3') .
                '">';
            wp_nonce_field('gtbabel-wizard-step-2');
            echo '<div class="gtbabel__wizard-step">';
            echo '<h2 class="gtbabel__wizard-steptitle">' . __('Connect Google Translate', 'gtbabel-plugin') . '</h2>';
            echo '<ol class="gtbabel__list">';
            echo '<li class="gtbabel__listitem">';
            echo sprintf(
                __('Go to %sGoogle API Console%s', 'gtbabel-plugin'),
                '<a href="https://console.cloud.google.com/apis" target="_blank">',
                '</a>'
            );
            echo '</li>';
            echo '<li class="gtbabel__listitem">';
            echo __('Create a new project', 'gtbabel-plugin');
            echo '</li>';
            echo '<li class="gtbabel__listitem">';
            echo __(
                'Marketplace > Enable "Cloud Translation API" (this requires you to setup a billing account)',
                'gtbabel-plugin'
            );
            echo '</li>';
            echo '<li class="gtbabel__listitem">';
            echo __('APIs and services > API credentials > Add a new api key', 'gtbabel-plugin');
            echo '</li>';
            echo '</ol>';
            echo '<input required="required" placeholder="' .
                __('Your Google Translation API Key', 'gtbabel-plugin') .
                '" class="gtbabel__input gtbabel__input--big" type="text" id="gtbabel_google_translation_api_key" name="gtbabel[google_translation_api_key]" value="' .
                (is_array($settings['google_translation_api_key'])
                    ? $settings['google_translation_api_key'][0]
                    : $settings['google_translation_api_key']) .
                '" />';
            echo '<div class="gtbabel__wizard-buttons">';
            echo '<a class="button button-secondary" href="' .
                admin_url('admin.php?page=gtbabel-wizard&step=1') .
                '">' .
                __('Back', 'gtbabel-plugin') .
                '</a>';
            echo '<input class="gtbabel__submit button button-primary" name="save_step" value="' .
                __('Next', 'gtbabel-plugin') .
                '" type="submit" />';
            echo '</div>';
            echo '</div>';
            echo '</form>';
        }

        // 3
        if ($this->getBackendWizardStep() === 3) {
            echo '<form class="gtbabel__form" method="post" action="' .
                admin_url('admin.php?page=gtbabel-wizard&step=4') .
                '">';
            wp_nonce_field('gtbabel-wizard-step-3');
            echo '<div class="gtbabel__wizard-step gtbabel__wizard-step--center">';
            echo '<h2 class="gtbabel__wizard-steptitle">' . __('Translate your content', 'gtbabel-plugin') . '</h2>';
            echo '<p class="gtbabel__paragraph">';
            echo __(
                'It\'s time to translate all of your existing content (you can skip this step – this can be done later at any time).',
                'gtbabel-plugin'
            );
            echo '</p>';

            $this->initBackendAutoTranslate('page=gtbabel-wizard&step=3');

            if ($settings['stats_log'] == '1') {
                echo '<div class="gtbabel__stats-log">';
                echo $this->showStatsLog('google');
                echo '</div>';
            }
            echo '<div class="gtbabel__wizard-buttons">';
            echo '<a class="button button-secondary" href="' .
                admin_url('admin.php?page=gtbabel-wizard&step=2') .
                '">' .
                __('Back', 'gtbabel-plugin') .
                '</a>';
            echo '<input class="gtbabel__submit button button-primary" name="save_step" value="' .
                __('Next', 'gtbabel-plugin') .
                '" type="submit" />';
            echo '</div>';
            echo '</div>';
            echo '</form>';
        }

        // 4
        if ($this->getBackendWizardStep() === 4) {
            echo '<form class="gtbabel__form" method="post" action="' .
                admin_url('admin.php?page=gtbabel-wizard&step=5') .
                '">';
            wp_nonce_field('gtbabel-wizard-step-4');
            echo '<div class="gtbabel__wizard-step">';
            echo '<h2 class="gtbabel__wizard-steptitle">' . __('Add a language picker', 'gtbabel-plugin') . '</h2>';
            echo $this->initBackendLanguagePickerContent();
            echo '<div class="gtbabel__wizard-buttons">';
            echo '<a class="button button-secondary" href="' .
                admin_url('admin.php?page=gtbabel-wizard&step=3') .
                '">' .
                __('Back', 'gtbabel-plugin') .
                '</a>';
            echo '<input class="gtbabel__submit button button-primary" name="save_step" value="' .
                __('Finish', 'gtbabel-plugin') .
                '" type="submit" />';
            echo '</div>';
            echo '</div>';
            echo '</form>';
        }

        // 5
        if ($this->getBackendWizardStep() === 5) {
            echo '<div class="gtbabel__wizard-step">';
            echo '<h2 class="gtbabel__wizard-steptitle">' . __('Well done', 'gtbabel-plugin') . '</h2>';
            echo '<img class="gtbabel__finish-image" src="' .
                plugin_dir_url(__FILE__) .
                'assets/images/finish.gif" alt="" />';
            echo '<div class="gtbabel__wizard-buttons">';
            echo '<a class="button button-primary" href="' . admin_url('admin.php?page=gtbabel-trans') . '">';
            echo __('Translated strings', 'gtbabel-plugin');
            echo '</a>';
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
    }

    private function getUserBackendThemeBackgroundColor()
    {
        global $_wp_admin_css_colors;
        return $_wp_admin_css_colors[get_user_option('admin_color')]->colors[2];
    }

    private function initBackendTranslations($url, $lng)
    {
        $translations = [];

        if ($url !== null) {
            $this->changeSetting('discovery_log', true);
            $urls = [];
            $urls[] = $this->getNoCacheUrl($url);
            $since_time = microtime(true);
            $this->fetch($this->getNoCacheUrl($url));
            // subsequent urls are now available (we need to refresh the current session)
            $this->start();
            foreach ($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource() as $lngs__value) {
                if ($lng !== null && $lngs__value !== $lng) {
                    continue;
                }
                $url_trans = $this->gtbabel->gettext->getUrlTranslationInLanguage($lngs__value, $url);
                $this->fetch($this->getNoCacheUrl($url_trans));
                $urls[] = $this->getNoCacheUrl($url_trans);
            }
            // restart again
            $this->start();
            $discovery_strings = $this->gtbabel->log->discoveryLogGet($since_time, $urls, false);
            $discovery_strings_map = [];
            foreach ($discovery_strings as $discovery_strings__key => $discovery_strings__value) {
                $discovery_strings_map[
                    md5($discovery_strings__value['string'] . '#' . ($discovery_strings__value['context'] ?? ''))
                ] = $discovery_strings__key;
            }
            // now auto set shared values
            // we now do a cool trick here: we fetch the homepage first to feed discovery url
            $home_url = get_home_url();
            $this->fetch($this->getNoCacheUrl($home_url));
            $this->start();
            foreach ($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource() as $lngs__value) {
                if ($lng !== null && $lngs__value !== $lng) {
                    continue;
                }
                $this->fetch(
                    $this->getNoCacheUrl($this->gtbabel->gettext->getUrlTranslationInLanguage($lngs__value, $home_url))
                );
            }
            $this->start();
            $this->gtbabel->gettext->autoEditSharedValues($discovery_strings);
            $this->changeSetting('discovery_log', false);
        }

        $translations = $this->gtbabel->gettext->getAllTranslationsFromFiles($lng, $url === null);

        // filter
        if ($url !== null) {
            foreach ($translations as $translations__key => $translations__value) {
                if (!array_key_exists($translations__key, $discovery_strings_map)) {
                    unset($translations[$translations__key]);
                }
            }
        }
        if (@$_GET['s'] != '') {
            foreach ($translations as $translations__key => $translations__value) {
                if (mb_stripos($translations__value['orig'], wp_kses_post($_GET['s'])) === false) {
                    unset($translations[$translations__key]);
                }
            }
        }
        if (@$_GET['shared'] != '') {
            foreach ($translations as $translations__key => $translations__value) {
                if (
                    ($_GET['shared'] == '0' && $translations__value['shared'] === true) ||
                    ($_GET['shared'] == '1' && $translations__value['shared'] !== true)
                ) {
                    unset($translations[$translations__key]);
                }
            }
        }
        if (@$_GET['checked'] != '') {
            foreach ($translations as $translations__key => $translations__value) {
                $all_checked = true;
                foreach ($translations__value['translations'] as $translations__value__value) {
                    if ($translations__value__value['checked'] !== true) {
                        $all_checked = false;
                        break;
                    }
                }
                if (
                    ($all_checked === true && $_GET['checked'] == '0') ||
                    ($all_checked !== true && $_GET['checked'] == '1')
                ) {
                    unset($translations[$translations__key]);
                }
            }
        }

        return $translations;
    }

    private function buildTranslationFormUrl($p)
    {
        return admin_url(
            'admin.php?page=gtbabel-trans&p=' .
                $p .
                (isset($_GET['s']) && $_GET['s'] !== '' ? '&s=' . wp_kses_post($_GET['s']) : '') .
                (isset($_GET['post_id']) && $_GET['post_id'] !== '' ? '&post_id=' . intval($_GET['post_id']) : '') .
                (isset($_GET['lng']) && $_GET['lng'] !== '' ? '&lng=' . sanitize_textarea_field($_GET['lng']) : '') .
                (isset($_GET['url']) && $_GET['url'] !== '' ? '&url=' . esc_url($_GET['url']) : '') .
                (isset($_GET['shared']) && $_GET['shared'] !== ''
                    ? '&shared=' . sanitize_textarea_field($_GET['shared'])
                    : '') .
                (isset($_GET['checked']) && $_GET['checked'] !== ''
                    ? '&checked=' . sanitize_textarea_field($_GET['checked'])
                    : '')
        );
    }

    private function initBackendPagination($translations)
    {
        $pagination = (object) [];
        $pagination->per_page = 100;
        $pagination->count = count($translations);
        $pagination->cur = @$_GET['p'] != '' ? intval($_GET['p']) : 1;
        $pagination->max = ceil($pagination->count / $pagination->per_page);
        $pagination->html = '';
        if ($pagination->max > 1) {
            $pagination->html .= '<ul class="gtbabel__pagination">';
            for ($p = 1; $p <= $pagination->max; $p++) {
                $pagination->html .= '<li class="gtbabel__pagination-item">';
                if ($pagination->cur !== $p) {
                    $pagination->html .=
                        '<a class="gtbabel__pagination-link" href="' .
                        $this->buildTranslationFormUrl($p) .
                        '">' .
                        $p .
                        '</a>';
                } else {
                    $pagination->html .= '<span class="gtbabel__pagination-cur">' . $p . '</span>';
                }
                $pagination->html .= '</li>';
            }
            $pagination->html .= '</ul>';
        }
        return $pagination;
    }

    private function changeSetting($key, $value)
    {
        $settings = get_option('gtbabel_settings');
        if ($settings === false) {
            $settings = [];
        }
        $settings[$key] = $value;
        update_option('gtbabel_settings', $settings);
    }

    private function getSetting($key)
    {
        $settings = get_option('gtbabel_settings');
        if ($settings === false) {
            return null;
        }
        if (!array_key_exists($key, $settings)) {
            return null;
        }
        return $settings[$key];
    }

    private function isUrlPublished($url)
    {
        /*
        - first we try to get the status via url_to_postid()
        - if that doesn't succeed, we try to get the status via fetch
        */
        $published = true;
        $post = url_to_postid($url);
        if ($post == 0) {
            $response = $this->fetch($url, false);
            if ($response->status == 404) {
                $published = false;
            }
        } else {
            if (get_post_status($post) !== 'publish') {
                $published = false;
            }
        }
        return $published;
    }

    private function getNoCacheUrl($url)
    {
        $url .= mb_strpos($url, '?') === false ? '?' : '&';
        $url .= 'no_cache=1';
        return $url;
    }

    private function fetch($url, $with_current_session = true)
    {
        return __::curl(
            $url,
            null,
            'GET',
            null,
            false,
            false,
            60,
            null,
            $with_current_session === true ? $_COOKIE : null
        );
    }

    private function showStatsLog($service = null)
    {
        echo '<ul>';
        foreach (
            ['google' => 'Google Translation API', 'microsoft' => 'Microsoft Translation API']
            as $service__key => $service__value
        ) {
            if ($service !== null && $service__key !== $service) {
                continue;
            }
            echo '<li>';
            echo $service__value . ': ';
            $cur = $this->gtbabel->log->statsLogGet($service__key);
            echo $cur;
            echo ' ';
            echo __('Characters', 'gtbabel-plugin');
            $costs = 0;
            if ($service__key === 'google') {
                $costs = $cur * (20 / 1000000) * 0.92;
            }
            if ($service__value === 'microsoft') {
                $costs = $cur * (8.433 / 1000000);
            }
            echo ' (~' . number_format(round($costs, 2), 2, ',', '.') . ' €)';
            echo '</li>';
        }
        echo '</ul>';
    }

    private function initBackendAutoTranslate($page)
    {
        $chunk_size = 1;

        echo '<a data-loading-text="' .
            __('Loading', 'gtbabel-plugin') .
            '..." data-error-text="' .
            __('An error occurred', 'gtbabel-plugin') .
            '" data-href="' .
            admin_url('admin.php?' . $page . '&gtbabel_auto_translate=1') .
            '" href="#" class="gtbabel__submit gtbabel__submit--auto-translate button button-secondary">' .
            __('Translate', 'gtbabel-plugin') .
            '</a>';

        if (@$_GET['gtbabel_auto_translate'] != '1') {
            return;
        }

        $chunk = 0;
        if (@$_GET['gtbabel_auto_translate_chunk'] != '') {
            $chunk = intval($_GET['gtbabel_auto_translate_chunk']);
        }
        $delete_unused = false;
        if (@$_GET['gtbabel_delete_unused'] == '1') {
            $delete_unused = true;
        }
        $since_time = null;
        if (__::x(@$_GET['gtbabel_since_time'])) {
            $since_time = floatval($_GET['gtbabel_since_time']);
        } else {
            $since_time = microtime(true);
        }

        echo '<div class="gtbabel__auto-translate">';

        // build general queue
        $queue = [];
        $urls = [];
        $query = new \WP_Query(['post_type' => 'any', 'posts_per_page' => '-1', 'post_status' => 'publish']);
        while ($query->have_posts()) {
            $query->the_post();
            $url = get_permalink();
            $urls[] = $url;
        }
        $query = new \WP_Term_Query(['hide_empty' => false]);
        if (!empty($query->terms)) {
            foreach ($query->terms as $terms__value) {
                $url = get_term_link($terms__value);
                // exclude non-public
                if (strpos($url, '?') !== false) {
                    continue;
                }
                $urls[] = $url;
            }
        }
        foreach ($urls as $urls__value) {
            $queue[] = ['url' => $urls__value, 'convert_to_lng' => null, 'refresh_after' => true];
            foreach ($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource() as $lngs__value) {
                $queue[] = ['url' => $urls__value, 'convert_to_lng' => $lngs__value, 'refresh_after' => false];
            }
        }

        // always enable discovery log (also if delete unused is set to false in order to get more logs)
        $this->changeSetting('discovery_log', true);

        // do next chunk
        for ($i = $chunk_size * $chunk; $i < $chunk_size * $chunk + $chunk_size; $i++) {
            if (!isset($queue[$i])) {
                break;
            }
            // this is important, that we fetch the url in the source language first (this calls addCurrentUrlToTranslations())
            // if we call the source url, the translated urls are generated
            // important: the main fetch happened in a different session (the current session does not know of the translated slugs yet)
            // therefore we refresh gtbabel after every main url
            $url = $queue[$i]['url'];
            if ($queue[$i]['convert_to_lng'] !== null) {
                $url = $this->gtbabel->gettext->getUrlTranslationInLanguage($queue[$i]['convert_to_lng'], $url);
            }

            // append a pseudo get parameter, so that frontend cache plugins don't work
            $response = $this->fetch($this->getNoCacheUrl($url));
            $this->gtbabel->log->generalLog($response);

            echo __('Loading', 'gtbabel-plugin');
            echo '... ' . $url . '<br/>';

            if ($queue[$i]['refresh_after'] === true) {
                $this->start();
            }
        }

        // progress
        $progress = ($chunk_size * $chunk + $chunk_size) / count($queue);
        if ($progress > 1) {
            $progress = 1;
        }
        $progress *= 100;
        $progress = round($progress, 2);
        echo '<strong>';
        echo __('Duration', 'gtbabel-plugin');
        echo ': ' . gmdate('H:i:s', round(microtime(true) - $since_time));
        echo '<br/>';
        echo __('Estimated time remaining', 'gtbabel-plugin');
        echo ': ' . gmdate('H:i:s', ((microtime(true) - $since_time) / $progress) * (100 - $progress));
        echo '<br/>';
        echo __('Progress', 'gtbabel-plugin');
        echo ': ' . number_format($progress, 2, ',', '') . '%';
        echo '</strong>';
        echo '<br/>';

        // disable discovery log
        $this->changeSetting('discovery_log', false);

        // if finished
        if ($chunk_size * $chunk + $chunk_size > count($queue) - 1) {
            $this->gtbabel->gettext->autoEditSharedValues();

            $this->gtbabel->log->generalLog(
                'calling $this->gtbabel->gettext->deleteUnusedTranslations(' . $since_time . ')'
            );
            if ($delete_unused === true) {
                $deleted = $this->gtbabel->gettext->deleteUnusedTranslations($since_time);
                echo __('Deleted strings', 'gtbabel-plugin') . ': ' . $deleted;
                echo '<br/>';
            }

            echo __('Finished', 'gtbabel-plugin');
        }

        // next
        else {
            $redirect_url = admin_url(
                'admin.php?' .
                    $page .
                    '&gtbabel_auto_translate=1&gtbabel_auto_translate_chunk=' .
                    ($chunk + 1) .
                    ($delete_unused === true ? '&gtbabel_delete_unused=1' : '') .
                    (__::x($since_time) ? '&gtbabel_since_time=' . $since_time : '')
            );
            echo '<a href="' . $redirect_url . '" class="gtbabel__auto-translate-next"></a>';
            echo '<img class="gtbabel__auto-translate-loading" src="' .
                plugin_dir_url(__FILE__) .
                'assets/images/loading.gif" alt="" />';
        }

        echo '</div>';
    }
}

class gtbabel_lngpicker_widget extends \WP_Widget
{
    function __construct()
    {
        parent::__construct('gtbabel_lngpicker_widget', __('Language picker', 'gtbabel-plugin'), [
            'description' => __('A language picker of Gtbabel.', 'gtbabel-plugin')
        ]);
    }
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        if (function_exists('gtbabel_languagepicker')) {
            echo '<ul class="lngpicker">';
            foreach (gtbabel_languagepicker() as $languagepicker__value) {
                echo '<li>';
                echo '<a href="' .
                    $languagepicker__value['url'] .
                    '"' .
                    ($languagepicker__value['active'] ? ' class="active"' : '') .
                    '>';
                echo $languagepicker__value['label'];
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }
        echo $args['after_widget'];
    }
    public function form($instance)
    {
        $title = '';
        if (isset($instance['title'])) {
            $title = $instance['title'];
        }
        echo '<p>';
        echo '<label for="' . $this->get_field_id('title') . '">' . __('Title', 'gtbabel-plugin') . ':</label>';
        echo '<input class="widefat" id="' .
            $this->get_field_id('title') .
            '" name="' .
            $this->get_field_name('title') .
            '" type="text" value="' .
            esc_attr($title) .
            '" />';
        echo '</p>';
    }
    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

$gtbabel = new Gtbabel();
new GtbabelWordPress($gtbabel);
