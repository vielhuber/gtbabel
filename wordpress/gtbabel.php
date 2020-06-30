<?php
/**
 * Plugin Name: Gtbabel
 * Plugin URI: https://github.com/vielhuber/gtbabel
 * Description: Instant server-side translation of any page.
 * Version: 3.7.2
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
        $this->triggerAltLngUrls();
        $this->addTopBarItem();
        $this->modifyGutenbergSidebar();
        $this->showWizardNotice();
        $this->languagePickerWidget();
        $this->languagePickerShortcode();
        $this->disableAutoRedirect();
        $this->localizeJs();
        $this->startHook();
        $this->stopHook();
    }

    private function disableAutoRedirect()
    {
        add_filter('redirect_canonical', function ($redirect_url) {
            $url =
                'http' .
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
                '://' .
                $_SERVER['HTTP_HOST'] .
                $_SERVER['REQUEST_URI'];
            if (trim($redirect_url, '/') !== trim($url, '/')) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            }
            return false;
        });
    }

    private function localizeJs()
    {
        $settings = $this->getSettings();
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
            if (isset($_GET['export']) && $_GET['export'] == '1') {
                $this->gtbabel->gettext->export();
            }
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
        $settings = $this->getSettings();

        // dynamically changed settings
        global $wpdb;
        $settings['database'] = [
            'type' => 'mysql',
            'host' => \DB_HOST,
            'username' => \DB_USER,
            'password' => \DB_PASSWORD,
            'database' => \DB_NAME,
            'port' => 3306,
            'table' => $wpdb->prefix . 'translations'
        ];
        $settings['prevent_publish'] = !is_user_logged_in();

        // settings that can be changed via url
        foreach (
            ['discovery_log', 'auto_set_discovered_strings_checked', 'auto_add_translations', 'redirect_root_domain']
            as $parameters__value
        ) {
            if (isset($_GET['gtbabel_' . $parameters__value]) && $_GET['gtbabel_' . $parameters__value] != '') {
                $settings[$parameters__value] =
                    $_GET['gtbabel_' . $parameters__value] == '1' ? true : $_GET['gtbabel_' . $parameters__value];
            }
        }

        $this->gtbabel->start($settings);

        // define wpml fallback constant
        if (!defined('ICL_LANGUAGE_CODE')) {
            define('ICL_LANGUAGE_CODE', $this->gtbabel->data->getCurrentLanguageCode());
        }
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
            $this->setupPluginFileStoreFolder();
            $this->setDefaultSettingsToOption();
        });
    }

    private function localizePlugin()
    {
        add_action('plugins_loaded', function () {
            load_plugin_textdomain('gtbabel-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
        });
    }

    private function getPluginFileStorePathRelative()
    {
        return '/' .
            trim(str_replace($this->gtbabel->utils->getDocRoot(), '', wp_upload_dir()['basedir']), '/') .
            '/gtbabel';
    }

    private function getPluginFileStorePathAbsolute()
    {
        return rtrim(wp_upload_dir()['basedir'], '/') . '/gtbabel';
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

                if ($trigger1 || $trigger2 || $trigger3) {
                    $this->saveSetting('prevent_publish_urls', $this->gtbabel->settings->get('prevent_publish_urls'));
                }
            },
            10,
            3
        );
    }

    private function triggerAltLngUrls()
    {
        add_action(
            'post_updated',
            function ($post_ID, $post_after, $post_before) {
                $post_before_url = get_permalink($post_before);
                $post_after_url = get_permalink($post_after);
                if ($post_before_url != $post_after_url) {
                    $this->gtbabel->altlng->change($post_before_url, $post_after_url);
                    $this->saveSetting('alt_lng_urls', $this->gtbabel->settings->get('alt_lng_urls'));
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

    private function addTopBarItem()
    {
        add_action(
            'admin_bar_menu',
            function ($admin_bar) {
                if (is_admin()) {
                    return;
                }
                echo '<style>#wpadminbar #wp-admin-bar-gtbabel-translate .ab-icon:before { content: "\f11f"; top: 3px; }</style>';
                $html = '<span class="ab-icon"></span>' . __('Translate now', 'gtbabel-plugin');
                $url = $this->gtbabel->host->getCurrentUrl();
                $lng = $this->gtbabel->data->sourceLngIsCurrentLng()
                    ? null
                    : $this->gtbabel->data->getCurrentLanguageCode();

                $admin_bar->add_menu([
                    'id' => 'gtbabel-translate',
                    'parent' => null,
                    'group' => null,
                    'title' => $html,
                    'href' => admin_url('admin.php?page=gtbabel-trans&url=' . urlencode($url) . '&lng=' . $lng),
                    'meta' => ['target' => '_blank']
                ]);
            },
            500
        );
    }

    private function modifyGutenbergSidebar()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'gtbabel-trans-links',
                __('Translations', 'gtbabel-plugin'),
                function ($post) {
                    echo '<ul>';
                    foreach (
                        $this->gtbabel->settings->getSelectedLanguageCodesLabelsWithoutSource()
                        as $languages__key => $languages__value
                    ) {
                        echo '<li><a href="' .
                            admin_url('admin.php?page=gtbabel-trans&post_id=' . $post->ID) .
                            '&lng=' .
                            $languages__key .
                            '">' .
                            $languages__value .
                            '</a></li>';
                    }
                    echo '</ul>';
                },
                ['post', 'page'],
                'side',
                'high'
            );
            add_meta_box(
                'gtbabel-trans-lng-source',
                __('Source language', 'gtbabel-plugin'),
                function ($post) {
                    echo "<script>
                    document.addEventListener('DOMContentLoaded', () => {
                        document.querySelector('.gtbabel_edit_alt_lng_urls').addEventListener('change', (e) =>
                        {
                            let data = new URLSearchParams();
                            data.append('edit_alt_lng_urls', 1);
                            data.append('lng', e.currentTarget.value);
                            data.append('p', e.currentTarget.getAttribute('data-post-id'));
	                        fetch(e.currentTarget.getAttribute('data-url'), { method: 'POST', body: data }).then(v=>v).catch(v=>v).then(data => {}); 
                            e.preventDefault();
                        }); 
                    });
                    </script>";
                    echo '<small>';
                    echo __('Notice: The slug must be always in the general source language.', 'gtbabel-plugin');
                    echo '</small><br/>';
                    echo '<select data-post-id="' .
                        $post->ID .
                        '" data-url="' .
                        admin_url('admin.php?page=gtbabel-settings') .
                        '" class="gtbabel_edit_alt_lng_urls">';
                    foreach (
                        $this->gtbabel->settings->getSelectedLanguageCodesLabels()
                        as $languages__key => $languages__value
                    ) {
                        echo '<option' .
                            ($this->gtbabel->altlng->get(get_permalink($post->ID)) === $languages__key
                                ? ' selected="selected"'
                                : '') .
                            ' value="' .
                            $languages__key .
                            '">' .
                            $languages__value .
                            '</option>';
                    }
                    echo '</select>';
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
            foreach ($this->gtbabel->data->getLanguagePickerData() as $languagepicker__value) {
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
                __('GNU gettext', 'gtbabel-plugin'),
                __('GNU gettext', 'gtbabel-plugin'),
                'manage_options',
                'gtbabel-gettext',
                function () {
                    $this->initBackendGettext();
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
                    wp_enqueue_style('gtbabel-css', plugins_url('assets/css/style.css', __FILE__));
                });
                add_action('admin_print_scripts-' . $menus__value, function () {
                    wp_enqueue_script('gtbabel-js', plugins_url('assets/js/script.js', __FILE__));
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

                    // remove slashes
                    $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);

                    // whitelist
                    foreach (
                        [
                            'languages',
                            'lng_source',
                            'log_folder',
                            'debug_translations',
                            'hide_languages',
                            'prefix_lng_source',
                            'redirect_root_domain',
                            'translate_default_tag_nodes',
                            'html_lang_attribute',
                            'html_hreflang_tags',
                            'auto_add_translations',
                            'only_show_checked_strings',
                            'auto_set_new_strings_checked',
                            'auto_translation',
                            'auto_translation_service',
                            'google_translation_api_key',
                            'microsoft_translation_api_key',
                            'deepl_translation_api_key',
                            'stats_log',
                            'prevent_publish_urls',
                            'alt_lng_urls',
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
                            'prefix_lng_source',
                            'translate_default_tag_nodes',
                            'html_lang_attribute',
                            'html_hreflang_tags',
                            'debug_translations',
                            'auto_add_translations',
                            'only_show_checked_strings',
                            'auto_set_new_strings_checked',
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
                            'microsoft_translation_api_key',
                            'deepl_translation_api_key'
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

                    $post_data = $settings['alt_lng_urls'];
                    $settings['alt_lng_urls'] = [];
                    if ($post_data != '') {
                        foreach (explode(PHP_EOL, $post_data) as $post_data__value) {
                            $post_data__value_parts = explode(':', $post_data__value);
                            if (!empty($post_data__value_parts)) {
                                $settings['alt_lng_urls'][$post_data__value_parts[0]] = __::trim_whitespace(
                                    $post_data__value_parts[1]
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

                    if (!isset($settings['languages'])) {
                        $settings = array_merge(['languages' => []], $settings);
                    }
                    $settings['languages'] = array_map(function ($settings__value) {
                        return __::decode_data($settings__value);
                    }, $settings['languages']);
                    array_unshift(
                        $settings['languages'],
                        $this->gtbabel->settings->getLanguageDataForCode($settings['lng_source'])
                    );

                    $this->saveSettings($settings);
                    // refresh gtbabel with new options
                    $this->start();
                }
            }

            if (isset($_POST['check_all_strings'])) {
                $this->gtbabel->data->setAllStringsToChecked();
            }

            if (isset($_POST['reset_settings'])) {
                $this->deleteSettings();
                $this->setDefaultSettingsToOption();
                $this->start();
            }

            if (isset($_POST['reset_translations'])) {
                $this->reset();
            }

            if (isset($_POST['edit_alt_lng_urls'])) {
                $this->gtbabel->altlng->edit(
                    get_permalink(sanitize_textarea_field($_POST['p'])),
                    sanitize_textarea_field($_POST['lng'])
                );
                $this->saveSetting('alt_lng_urls', $this->gtbabel->settings->get('alt_lng_urls'));
            }

            $message =
                '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                __('Successfully edited', 'gtbabel-plugin') .
                '</p></div>';
        }

        $settings = $this->getSettings();

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
        foreach ($this->gtbabel->settings->getDefaultLanguages() as $languages__value) {
            echo '<li class="gtbabel__languagelist-item">';
            echo '<label class="gtbabel__languagelist-label">';
            echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[languages][]"' .
                (!empty(
                    array_filter($settings['languages'], function ($settings__value) use ($languages__value) {
                        return $settings__value['code'] === $languages__value['code'];
                    })
                )
                    ? ' checked="checked"'
                    : '') .
                ' value="' .
                __::encode_data($languages__value) .
                '"' .
                ($settings['lng_source'] === $languages__value['code'] ? ' disabled="disabled"' : '') .
                ' />';
            echo '<span class="gtbabel__languagelist-label-inner">' . $languages__value['label'] . '</span>';
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
        foreach ($this->gtbabel->settings->getDefaultLanguages() as $languages__value) {
            echo '<option value="' .
                $languages__value['code'] .
                '"' .
                ($settings['lng_source'] == $languages__value['code'] ? ' selected="selected"' : '') .
                '>' .
                $languages__value['label'] .
                '</option>';
        }
        echo '</select>';
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
            $this->gtbabel->settings->getSelectedLanguageCodesLabelsWithoutSource()
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
        echo '<label for="gtbabel_prefix_lng_source" class="gtbabel__label">';
        echo __('Prefix source language urls', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_prefix_lng_source" name="gtbabel[prefix_lng_source]" value="1"' .
            ($settings['prefix_lng_source'] == '1' ? ' checked="checked"' : '') .
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
        echo '<label for="gtbabel_auto_add_translations" class="gtbabel__label">';
        echo __('Auto add translations', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_auto_add_translations" name="gtbabel[auto_add_translations]" value="1"' .
            ($settings['auto_add_translations'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_only_show_checked_strings" class="gtbabel__label">';
        echo __('Only show checked strings', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_only_show_checked_strings" name="gtbabel[only_show_checked_strings]" value="1"' .
            ($settings['only_show_checked_strings'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_set_new_strings_checked" class="gtbabel__label">';
        echo __('Auto set new strings to checked', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_auto_set_new_strings_checked" name="gtbabel[auto_set_new_strings_checked]" value="1"' .
            ($settings['auto_set_new_strings_checked'] == '1' ? ' checked="checked"' : '') .
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
        echo '<option value="deepl"' .
            ($settings['auto_translation_service'] == 'deepl' ? ' selected="selected"' : '') .
            '>DeepL</option>';
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
        echo '<label for="gtbabel_deepl_translation_api_key" class="gtbabel__label">';
        echo __('DeepL Translation API Key', 'gtbabel-plugin') .
            ' (<a href="https://www.deepl.com/pro#developer" target="_blank">' .
            __('Link', 'gtbabel-plugin') .
            '</a>)';
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_deepl_translation_api_key" name="gtbabel[deepl_translation_api_key]">' .
            (is_array($settings['deepl_translation_api_key'])
                ? implode(PHP_EOL, $settings['deepl_translation_api_key'])
                : $settings['deepl_translation_api_key']) .
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
        echo '<label for="gtbabel_alt_lng_urls" class="gtbabel__label">';
        echo __('Alternate language for main content', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<textarea class="gtbabel__input gtbabel__input--textarea" id="gtbabel_alt_lng_urls" name="gtbabel[alt_lng_urls]">';
        if (!empty($settings['alt_lng_urls'])) {
            echo implode(
                PHP_EOL,
                array_map(
                    function ($alt_lng_urls__value, $alt_lng_urls__key) {
                        return $alt_lng_urls__key . ':' . $alt_lng_urls__value;
                    },
                    $settings['alt_lng_urls'],
                    array_keys($settings['alt_lng_urls'])
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
        echo '<input class="gtbabel__input gtbabel__input--checkbox" id="gtbabel_delete_unused" type="checkbox" checked="checked" value="1" />';
        echo '</div>';
        echo '</li>';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_set_discovered_strings_checked" class="gtbabel__label">';
        echo __('Auto set discovered strings to checked', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" id="gtbabel_auto_set_discovered_strings_checked" type="checkbox" checked="checked" value="1" />';
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

        echo '<h2 class="gtbabel__subtitle">' . __('Set all strings to checked', 'gtbabel-plugin') . '</h2>';
        echo '<input class="gtbabel__submit button button-secondary" name="check_all_strings" value="' .
            __('Save', 'gtbabel-plugin') .
            '" type="submit" />';

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

    private function initBackendStringTranslationShowFile($str, $show_upload = true)
    {
        echo '<div class="gtbabel__file-info">';
        if ($str != '') {
            if (preg_match('/.+\.(jpg|jpeg|png|gif|svg)$/i', $str)) {
                echo '<img class="gtbabel__file-info-img" src="' .
                    $this->gtbabel->host->getCurrentHost() .
                    '/' .
                    $str .
                    '" alt="" />';
            }
            echo '<a class="button button-secondary button-small gtbabel__file-info-link" target="_blank" href="' .
                $this->gtbabel->host->getCurrentHost() .
                '/' .
                $str .
                '">' .
                __('Open file', 'gtbabel-plugin') .
                '</a>';
        }
        if ($show_upload === true) {
            echo '<a class="button button-secondary button-small gtbabel__file-info-upload" href="#">' .
                __('Upload file', 'gtbabel-plugin') .
                '</a>';
        }
        echo '</div>';
    }

    private function initBackendStringTranslation()
    {
        $message = '';

        $url = null;
        $post_id = null;
        $lng = null;
        $source_url_published = null;
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
        if ($url !== null) {
            $source_url_published = $this->isUrlPublished($url);
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['translations_submit'])) {
                check_admin_referer('gtbabel-trans-save-translations');
                if (!empty(@$_POST['gtbabel'])) {
                    // remove slashes
                    $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);
                    // sanitize
                    $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function (
                        $settings__value,
                        $settings__key
                    ) {
                        if (__::decode_data($settings__key)['field'] === 'trans') {
                            return wp_kses_post($settings__value);
                        }
                        return sanitize_textarea_field($settings__value);
                    });

                    foreach ($_POST['gtbabel'] as $post__key => $post__value) {
                        $input_data = __::decode_data($post__key);
                        if (@$input_data['field'] === 'trans') {
                            $this->gtbabel->data->editTranslation(
                                $input_data['str'],
                                $input_data['context'],
                                $input_data['lng_source'],
                                $input_data['lng'],
                                isset($post__value) ? $post__value : false,
                                null
                            );
                        }
                        if (@$input_data['field'] === 'checked') {
                            $this->gtbabel->data->editTranslation(
                                $input_data['str'],
                                $input_data['context'],
                                $input_data['lng_source'],
                                $input_data['lng'],
                                null,
                                isset($post__value) && $post__value == '1'
                                    ? true
                                    : (isset($post__value) && $post__value == '0'
                                        ? false
                                        : null)
                            );
                        }
                        if (@$input_data['field'] === 'delete' && $post__value == '1') {
                            $this->gtbabel->data->deleteStringFromDatabase(
                                $input_data['str'],
                                $input_data['context'],
                                $input_data['lng_source']
                            );
                        }
                    }
                }
                if (isset($_POST['translations_submit']['publish'])) {
                    if (isset($_POST['translations_submit']['publish'][1])) {
                        $this->gtbabel->publish->publish($url, $lng);
                    } else {
                        $this->gtbabel->publish->unpublish($url, $lng);
                    }
                    $this->saveSetting('prevent_publish_urls', $this->gtbabel->settings->get('prevent_publish_urls'));
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
                    $this->saveSetting('prevent_publish_urls', $this->gtbabel->settings->get('prevent_publish_urls'));
                }
            }
            $message =
                '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                __('Successfully edited', 'gtbabel-plugin') .
                '</p></div>';
        }

        $translations = $this->initBackendTranslations($url, $lng);

        $pagination = $this->initBackendPagination($translations, $lng);

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

        echo '<div class="gtbabel__transmeta">';
        if ($url !== null) {
            echo '<p class="gtbabel__transmeta-mainlink">';
            if ($lng === null || $this->gtbabel->settings->getSourceLanguageCode() === $lng) {
                $link_public = $url;
            } else {
                $link_public = $this->gtbabel->data->getUrlTranslationInLanguage(
                    $this->gtbabel->data->getLngFromUrl($url),
                    $lng,
                    $url
                );
            }
            echo '<a href="' . $link_public . '" target="_blank">' . $link_public . '</a>';
            echo '</p>';
        }
        echo '<ul class="gtbabel__transmeta-list">';
        echo '<li class="gtbabel__transmeta-listitem">';
        if ($lng !== null) {
            $lng_link = 'admin.php?page=gtbabel-trans';
            if ($post_id !== null) {
                $lng_link .= '&post_id=' . $post_id;
            } elseif ($url !== null) {
                $lng_link .=
                    '&url=' .
                    $this->gtbabel->data->getUrlTranslationInLanguage(
                        $this->gtbabel->data->getLngFromUrl($url),
                        $this->gtbabel->settings->getSourceLanguageCode(),
                        $url
                    );
            }
            echo '<a class="gtbabel__transmeta-listitem-link" href="' . admin_url($lng_link) . '">';
        }
        echo __('All languages', 'gtbabel-plugin');
        if ($lng !== null) {
            echo '</a>';
        }
        echo '</li>';
        if ($post_id !== null) {
            echo '<li class="gtbabel__transmeta-listitem">';
            echo '<a class="gtbabel__transmeta-listitem-link" href="' . get_edit_post_link($post_id) . '">';
            echo $this->gtbabel->settings->getSourceLanguageLabel();
            echo '</a>';
            echo '</li>';
        }
        foreach ($this->gtbabel->settings->getSelectedLanguageCodesLabelsWithoutSource() as $lng__key => $lng__value) {
            echo '<li class="gtbabel__transmeta-listitem">';
            if ($lng === null || $lng !== $lng__key) {
                $lng_link = 'admin.php?page=gtbabel-trans&lng=' . $lng__key;
                if ($post_id !== null) {
                    $lng_link .= '&post_id=' . $post_id;
                } elseif ($url !== null) {
                    $lng_link .=
                        '&url=' .
                        $this->gtbabel->data->getUrlTranslationInLanguage(
                            $this->gtbabel->data->getLngFromUrl($url),
                            $lng__key,
                            $url
                        );
                }
                echo '<a class="gtbabel__transmeta-listitem-link" href="' . admin_url($lng_link) . '">';
            }
            echo $lng__value;
            if ($lng === null || $lng !== $lng__key) {
                echo '</a>';
            }
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="gtbabel__search">';
        echo '<form class="gtbabel__form" method="get" action="' . admin_url('admin.php') . '">';
        echo '<input type="hidden" name="page" value="gtbabel-trans" />';
        echo '<input type="hidden" name="p" value="1" />';
        echo '<input type="hidden" name="lng" value="' . ($lng !== null ? $lng : '') . '" />';
        echo '<input type="hidden" name="post_id" value="' . ($post_id !== null ? $post_id : '') . '" />';
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
            foreach (
                $this->gtbabel->settings->getSelectedLanguageCodesLabels()
                as $languages__key => $languages__value
            ) {
                if (
                    $lng !== null &&
                    $lng !== $languages__key &&
                    $this->gtbabel->settings->getSourceLanguageCode() !== $languages__key
                ) {
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
            foreach ($translations as $translations__value) {
                echo '<tr class="gtbabel__table-row">';
                foreach (
                    $this->gtbabel->settings->getSelectedLanguageCodesLabels()
                    as $languages__key => $languages__value
                ) {
                    if (
                        $lng !== null &&
                        $lng !== $languages__key &&
                        $this->gtbabel->settings->getSourceLanguageCode() !== $languages__key
                    ) {
                        continue;
                    }
                    echo '<td class="gtbabel__table-cell">';
                    if (
                        $languages__key !== $translations__value['lng_source'] &&
                        @$translations__value[$languages__key] != ''
                    ) {
                        echo '<input title="' .
                            __('String checked', 'gtbabel-plugin') .
                            '" class="gtbabel__input gtbabel__input--checkbox gtbabel__input--on-change gtbabel__input--submit-unchecked gtbabel__input--check-translation" type="checkbox" data-name="gtbabel[' .
                            __::encode_data([
                                'str' => $translations__value[$translations__value['lng_source']],
                                'context' => $translations__value['context'],
                                'lng_source' => $translations__value['lng_source'],
                                'lng' => $languages__key,
                                'field' => 'checked'
                            ]) .
                            ']" value="1"' .
                            (@$translations__value[$languages__key . '_checked'] == '1' ? ' checked="checked"' : '') .
                            ' />';
                    }
                    echo '<textarea' .
                        ($languages__key === $translations__value['lng_source'] ? ' disabled="disabled"' : '') .
                        ' class="gtbabel__input gtbabel__input--textarea gtbabel__input--on-change" data-name="gtbabel[' .
                        __::encode_data([
                            'str' => $translations__value[$translations__value['lng_source']],
                            'context' => $translations__value['context'],
                            'lng_source' => $translations__value['lng_source'],
                            'lng' => $languages__key,
                            'field' => 'trans'
                        ]) .
                        ']">' .
                        $translations__value[$languages__key] .
                        '</textarea>';
                    if ($translations__value['context'] === 'file') {
                        $this->initBackendStringTranslationShowFile(
                            $translations__value[$languages__key],
                            $languages__key !== $translations__value['lng_source']
                        );
                    }
                    echo '</td>';
                }
                echo '<td class="gtbabel__table-cell">';
                echo '<textarea class="gtbabel__input gtbabel__input--textarea" disabled="disabled">' .
                    $translations__value['context'] .
                    '</textarea>';
                echo '</td>';
                echo '<td class="gtbabel__table-cell">';
                echo '<input disabled="disabled" class="gtbabel__input gtbabel__input--checkbox gtbabel__input--on-change gtbabel__input--submit-unchecked" type="checkbox" value="1"' .
                    (@$translations__value['shared'] == '1' ? ' checked="checked"' : '') .
                    ' />';
                echo '</td>';
                echo '<td class="gtbabel__table-cell">';
                echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[' .
                    __::encode_data([
                        'str' => $translations__value[$translations__value['lng_source']],
                        'context' => $translations__value['context'],
                        'lng_source' => $translations__value['lng_source'],
                        'field' => 'delete'
                    ]) .
                    ']" value="1" />';
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

    private function initBackendGettext()
    {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['upload_file'])) {
                check_admin_referer('gtbabel-gettext-import');
                // remove slashes
                $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);
                $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });
                $_FILES['gtbabel'] = __::array_map_deep($_FILES['gtbabel'], function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });
                if (
                    @$_POST['gtbabel']['lng_source'] != '' &&
                    @$_POST['gtbabel']['lng_target'] != '' &&
                    @$_FILES['gtbabel']['name']['file'] != ''
                ) {
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
                        $this->gtbabel->gettext->import(
                            $_FILES['gtbabel']['tmp_name']['file'],
                            $_POST['gtbabel']['lng_source'],
                            $_POST['gtbabel']['lng_target']
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

        echo '<div class="gtbabel gtbabel--gettext wrap">';
        echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
        echo $message;

        echo '<h2 class="gtbabel__subtitle">' . __('Export translations', 'gtbabel-plugin') . '</h2>';
        echo '<a class="button button-primary" href="' .
            admin_url('admin.php?page=gtbabel-gettext&export=1') .
            '">' .
            __('Export', 'gtbabel-plugin') .
            '</a>';

        echo '<h2 class="gtbabel__subtitle">' . __('Import translations', 'gtbabel-plugin') . '</h2>';

        echo '<form enctype="multipart/form-data" class="gtbabel__form" method="post" action="' .
            admin_url('admin.php?page=gtbabel-gettext') .
            '">';
        wp_nonce_field('gtbabel-gettext-import');
        echo '<ul class="gtbabel__fields">';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_lng_source" class="gtbabel__label">';
        echo __('Source language', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select required="required" class="gtbabel__input gtbabel__input--select" id="gtbabel_lng_source" name="gtbabel[lng_source]">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach ($this->gtbabel->settings->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            echo '<option value="' . $languages__key . '">' . $languages__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_lng_target" class="gtbabel__label">';
        echo __('Target language', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select required="required" class="gtbabel__input gtbabel__input--select" id="gtbabel_lng_target" name="gtbabel[lng_target]">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach ($this->gtbabel->settings->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            echo '<option value="' . $languages__key . '">' . $languages__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_file" class="gtbabel__label">';
        echo __('*.po-file', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input required="required" class="gtbabel__input gtbabel__input--file" type="file" name="gtbabel[file]" id="gtbabel_file" accept=".po" />';
        echo '</div>';
        echo '</li>';
        echo '</ul>';

        echo '<input class="gtbabel__submit button button-primary" name="upload_file" value="' .
            __('Import', 'gtbabel-plugin') .
            '" type="submit" />';
        echo '</form>';

        echo '<h2 class="gtbabel__subtitle">' . __('Use a translation service', 'gtbabel-plugin') . '</h2>';

        echo '<ol class="gtbabel__list">';
        echo '<li class="gtbabel__listitem">';
        echo sprintf(
            __('Register and login at %sICanLocalize%s.', 'gtbabel-plugin'),
            '<a href="https://www.icanlocalize.com" target="_blank">',
            '</a>'
        );
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo __(
            'Create a new Software localization project and pick the same original / target languages as in Gtbabel.',
            'gtbabel-plugin'
        );
        echo '</li>';
        echo '<li class="gtbabel__listitem">';
        echo __('Upload your .pot-template file, which can be exported above.', 'gtbabel-plugin');
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
        echo __('Reupload the .po-files via the import above.', 'gtbabel-plugin');
        echo '</li>';
        echo '</ol>';

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

                // remove slashes
                $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);

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
                    if (!isset($settings['languages'])) {
                        $settings = array_merge(['languages' => []], $settings);
                    }
                    $settings['languages'] = array_map(function ($settings__value) {
                        return __::decode_data($settings__value);
                    }, $settings['languages']);
                    array_unshift(
                        $settings['languages'],
                        $this->gtbabel->settings->getLanguageDataForCode($this->getSetting('lng_source'))
                    );
                    $this->saveSetting('languages', $settings['languages']);
                }
                if ($this->getBackendWizardStep() === 3) {
                    check_admin_referer('gtbabel-wizard-step-2');
                    $existing = $this->getSetting('google_translation_api_key');
                    $existing[0] = $settings['google_translation_api_key'];
                    $this->saveSetting('google_translation_api_key', $existing);
                    $this->saveSetting('auto_translation', true);
                }
                if ($this->getBackendWizardStep() === 5) {
                    check_admin_referer('gtbabel-wizard-step-4');
                    $this->saveSetting('wizard_finished', true);
                }

                // restart
                $this->start();
            }
        }

        $settings = $this->getSettings();

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
            foreach ($this->gtbabel->settings->getDefaultLanguages() as $languages__value) {
                echo '<li class="gtbabel__languagelist-item">';
                echo '<label class="gtbabel__languagelist-label">';
                echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[languages][]"' .
                    (!empty(
                        array_filter($settings['languages'], function ($settings__value) use ($languages__value) {
                            return $settings__value['code'] === $languages__value['code'];
                        })
                    )
                        ? ' checked="checked"'
                        : '') .
                    ' value="' .
                    __::encode_data($languages__value) .
                    '"' .
                    ($settings['lng_source'] === $languages__value['code'] ? ' disabled="disabled"' : '') .
                    ' />';
                echo '<span class="gtbabel__languagelist-label-inner">' . $languages__value['label'] . '</span>';
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
            $urls = [];
            $urls[] = $url;
            $time = $this->gtbabel->utils->getCurrentTime();
            $this->fetch($this->buildFetchUrl($url));
            // subsequent urls are now available (we need to refresh the current session)
            $this->start();
            foreach ($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource() as $lngs__value) {
                if ($lng !== null && $lngs__value !== $lng) {
                    continue;
                }
                $url_trans = $this->gtbabel->data->getUrlTranslationInLanguage(
                    $this->gtbabel->data->getLngFromUrl($url),
                    $lngs__value,
                    $url
                );
                $this->fetch($this->buildFetchUrl($url_trans));
                $urls[] = $url_trans;
            }
            // restart again
            $this->start();
            $discovery_strings = $this->gtbabel->data->discoveryLogGetAfter($time, $urls, false);
            $discovery_strings_index = array_map(function ($discovery_strings__value) {
                return __::encode_data([$discovery_strings__value['str'], $discovery_strings__value['context']]);
            }, $discovery_strings);
        }

        $translations = $this->gtbabel->data->getGroupedTranslationsFromDatabaseNEW(null, $url === null);

        // filter
        if ($url !== null) {
            foreach ($translations as $translations__key => $translations__value) {
                if (
                    !in_array(
                        __::encode_data([
                            $translations__value[$translations__value['lng_source']],
                            $translations__value['context']
                        ]),
                        $discovery_strings_index
                    )
                ) {
                    unset($translations[$translations__key]);
                }
            }
        }
        if (@$_GET['s'] != '') {
            foreach ($translations as $translations__key => $translations__value) {
                $found = false;
                foreach ($this->gtbabel->settings->getSelectedLanguageCodes() as $languages__value) {
                    if (
                        @$translations__value[$languages__value] != '' &&
                        mb_stripos($translations__value[$languages__value], wp_kses_post($_GET['s'])) !== false
                    ) {
                        $found = true;
                        break;
                    }
                }
                if ($found === false) {
                    unset($translations[$translations__key]);
                }
            }
        }
        if (@$_GET['shared'] != '') {
            foreach ($translations as $translations__key => $translations__value) {
                if (
                    ($_GET['shared'] == '0' && $translations__value['shared'] == '1') ||
                    ($_GET['shared'] == '1' && $translations__value['shared'] != '1')
                ) {
                    unset($translations[$translations__key]);
                }
            }
        }
        if (@$_GET['checked'] != '') {
            foreach ($translations as $translations__key => $translations__value) {
                $all_checked = true;
                foreach ($translations__value as $translations__value__key => $translations__value__value) {
                    if (strpos($translations__value__key, 'checked') === false) {
                        continue;
                    }
                    if ($translations__value__value != '1') {
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

    private function initBackendPagination($translations, $lng)
    {
        $pagination = (object) [];

        if ($lng !== null) {
            $shown_cols = 1;
        } else {
            $shown_cols = count($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource());
        }
        $pagination->per_page = round(100 / pow($shown_cols, 1 / 3));

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

    private function buildFetchUrl(
        $url,
        $bypass_cache = true,
        $discovery_log = true,
        $auto_set_discovered_strings_checked = false,
        $auto_add_translations = true,
        $redirect_root_domain = 'source'
    ) {
        if (
            $bypass_cache === true ||
            $discovery_log === true ||
            $auto_set_discovered_strings_checked === true ||
            $auto_add_translations === true
        ) {
            $url .= mb_strpos($url, '?') === false ? '?' : '&';
        }
        $args = [];
        if ($bypass_cache === true) {
            $args[] = 'gtbabel_no_cache=1';
        }
        if ($discovery_log === true) {
            $args[] = 'gtbabel_discovery_log=1';
        }
        if ($auto_set_discovered_strings_checked === true) {
            $args[] = 'gtbabel_auto_set_discovered_strings_checked=1';
        }
        if ($auto_add_translations === true) {
            $args[] = 'gtbabel_auto_add_translations=1';
        }
        if ($redirect_root_domain !== null) {
            $args[] = 'gtbabel_redirect_root_domain=' . $redirect_root_domain;
        }
        $url .= implode('&', $args);
        return $url;
    }

    private function getAllPublicUrlsForSite()
    {
        $urls = [];

        // approach 1 (parse sitemap; this also works for dynamically generated sitemaps like yoast)
        $sitemap_url = get_bloginfo('url') . '/sitemap_index.xml';
        $urls = __::extract_urls_from_sitemap($sitemap_url);

        // approach 2 (get all posts)
        if (empty($urls)) {
            $urls[] = get_bloginfo('url');
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
        }

        $urls = array_filter($urls, function ($urls__value) {
            return strpos($urls__value, $this->gtbabel->host->getCurrentHost()) !== false;
        });

        $urls = array_unique($urls);

        sort($urls);

        return $urls;
    }

    private function fetch($url, $with_current_session = true)
    {
        $response = __::curl(
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
        //$this->gtbabel->log->generalLog($response);
        return $response;
    }

    private function showStatsLog($service = null)
    {
        echo '<ul>';
        foreach (
            [
                'google' => 'Google Translation API',
                'microsoft' => 'Microsoft Translation API',
                'deepl' => 'DeepL Translation API'
            ]
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
            if ($service__value === 'deepl') {
                $costs = $cur * (20 / 1000000);
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
        $auto_set_discovered_strings_checked = false;
        if (@$_GET['gtbabel_auto_set_discovered_strings_checked'] == '1') {
            $auto_set_discovered_strings_checked = true;
        }
        $time = null;
        if (__::x(@$_GET['gtbabel_time'])) {
            $time = $_GET['gtbabel_time'];
        } else {
            $time = $this->gtbabel->utils->getCurrentTime();
        }

        echo '<div class="gtbabel__auto-translate">';

        // reset shared values (they are set subsequently when discovery_log=1)
        if ($chunk === 0) {
            $this->gtbabel->data->resetSharedValues();
        }

        // build general queue
        $queue = [];
        if ($chunk === 0 || get_transient('gtbabel_public_urls') === false) {
            $urls = $this->getAllPublicUrlsForSite();
            set_transient('gtbabel_public_urls', $urls);
        } else {
            $urls = get_transient('gtbabel_public_urls');
        }

        foreach ($urls as $urls__value) {
            $queue[] = ['url' => $urls__value, 'convert_to_lng' => null, 'refresh_after' => true];
            foreach ($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource() as $lngs__value) {
                $queue[] = ['url' => $urls__value, 'convert_to_lng' => $lngs__value, 'refresh_after' => false];
            }
        }

        // do next chunk
        for ($i = $chunk_size * $chunk; $i < $chunk_size * $chunk + $chunk_size; $i++) {
            if (!isset($queue[$i])) {
                break;
            }
            // this is important, that we fetch the url in the source language first (this calls addCurrentUrlToTranslations())
            // if we call the source url, the translated urls are generated (only if show checked is true)
            // important: the main fetch happened in a different session (the current session does not know of the translated slugs yet)
            // therefore we refresh gtbabel after every main url
            $url = $queue[$i]['url'];
            if ($queue[$i]['convert_to_lng'] !== null) {
                $url = $this->gtbabel->data->getUrlTranslationInLanguage(
                    $this->gtbabel->settings->getSourceLanguageCode(),
                    $queue[$i]['convert_to_lng'],
                    $url
                );
            }

            $this->fetch(
                $this->buildFetchUrl(
                    $url,
                    true, // bypass caching
                    true, // general_log
                    $auto_set_discovered_strings_checked,
                    true, // auto_add_translations
                    'source' // redirect_root_domain
                )
            );

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
        echo ': ' . gmdate('H:i:s', strtotime('now') - strtotime($time));
        echo '<br/>';
        echo __('Estimated time remaining', 'gtbabel-plugin');
        echo ': ' . gmdate('H:i:s', ((strtotime('now') - strtotime($time)) / $progress) * (100 - $progress));
        echo '<br/>';
        echo __('Progress', 'gtbabel-plugin');
        echo ': ' . number_format($progress, 2, ',', '') . '%';
        echo '</strong>';
        echo '<br/>';

        // if finished
        if ($chunk_size * $chunk + $chunk_size > count($queue) - 1) {
            if ($delete_unused === true) {
                $deleted = $this->gtbabel->data->discoveryLogDeleteBefore($time);
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
                    ($auto_set_discovered_strings_checked === true
                        ? '&gtbabel_auto_set_discovered_strings_checked=1'
                        : '') .
                    (__::x($time) ? '&gtbabel_time=' . $time : '')
            );
            echo '<a href="' . $redirect_url . '" class="gtbabel__auto-translate-next"></a>';
            echo '<img class="gtbabel__auto-translate-loading" src="' .
                plugin_dir_url(__FILE__) .
                'assets/images/loading.gif" alt="" />';
        }

        echo '</div>';
    }

    private function getSettingsFilename()
    {
        return $this->getPluginFileStorePathAbsolute() . '/settings/settings.json';
    }

    private function getSettings()
    {
        if (!file_exists($this->getSettingsFilename())) {
            return [];
        }
        $settings = json_decode(@file_get_contents($this->getSettingsFilename()), true);
        if (
            $settings === true ||
            $settings === false ||
            $settings === null ||
            $settings == '' ||
            !is_array($settings)
        ) {
            return [];
        }
        return $settings;
    }

    private function getSetting($key)
    {
        $settings = $this->getSettings();
        if (empty($settings)) {
            return null;
        }
        if (!array_key_exists($key, $settings)) {
            return null;
        }
        return $settings[$key];
    }

    private function saveSettings($settings)
    {
        file_put_contents($this->getSettingsFilename(), json_encode($settings, \JSON_PRETTY_PRINT));
    }

    private function saveSetting($key, $value)
    {
        $settings = $this->getSettings();
        $settings[$key] = $value;
        $this->saveSettings($settings);
    }

    private function deleteSettings()
    {
        @unlink($this->getSettingsFilename());
    }

    private function setupPluginFileStoreFolder()
    {
        if (!is_dir($this->getPluginFileStorePathAbsolute())) {
            mkdir($this->getPluginFileStorePathAbsolute(), 0777, true);
        }
        if (!is_dir($this->getPluginFileStorePathAbsolute() . '/settings')) {
            mkdir($this->getPluginFileStorePathAbsolute() . '/settings', 0777, true);
        }
        if (!file_exists($this->getPluginFileStorePathAbsolute() . '/settings/.htaccess')) {
            file_put_contents($this->getPluginFileStorePathAbsolute() . '/settings/.htaccess', 'Deny from all');
        }
    }

    private function setDefaultSettingsToOption()
    {
        if (empty($this->getSettings())) {
            $lng_source = mb_strtolower(mb_substr(get_locale(), 0, 2));
            $languages = [
                [
                    'code' => 'de',
                    'label' => 'Deutsch',
                    'google_translation_code' => 'de',
                    'microsoft_translation_code' => 'de',
                    'deepl_translation_code' => 'de'
                ],
                [
                    'code' => 'en',
                    'label' => 'English',
                    'google_translation_code' => 'en',
                    'microsoft_translation_code' => 'en',
                    'deepl_translation_code' => 'en'
                ]
            ];
            if (!in_array($lng_source, ['de', 'en'])) {
                $languages[] = $this->gtbabel->settings->getLanguageDataForCode($lng_source) ?? [
                    'code' => $lng_source,
                    'label' => $lng_source,
                    'google_translation_code' => $lng_source,
                    'microsoft_translation_code' => $lng_source,
                    'deepl_translation_code' => $lng_source
                ];
            }
            $this->saveSettings(
                $this->gtbabel->settings->setupSettings([
                    'languages' => $languages,
                    'lng_source' => $lng_source,
                    'log_folder' => $this->getPluginFileStorePathRelative() . '/logs',
                    'exclude_urls' => ['/wp-admin', 'wp-login.php', 'wp-cron.php', 'wp-comments-post.php'],
                    'exclude_dom' => ['.notranslate', '.lngpicker', '#wpadminbar']
                ])
            );
        }
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
