<?php
/**
 * Plugin Name: Gtbabel
 * Plugin URI: https://github.com/vielhuber/gtbabel
 * Description: Instant server-side translation of any page.
 * Version: 5.2.9
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
        $this->addLastmodToSitemap();
        $this->addTopBarItem();
        $this->modifyGutenbergSidebar();
        $this->showWizardNotice();
        $this->translateLocalizeScript();
        $this->languagePickerWidget();
        $this->languagePickerShortcode();
        $this->languagePickerMenu();
        $this->disableAutoRedirect();
        $this->initUpdateCapabilities();
        $this->sendMailNotificationsSetupCron();
        $this->filterSpecificUrls();
        $this->autoTranslatePluginMails();
        $this->autoTranslateSearch();
        $this->startHook();
        $this->stopHook();
    }

    private function translateLocalizeScript()
    {
        add_action('wp_loaded', function () {
            if (is_admin() || in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'])) {
                return;
            }
            if ($this->gtbabel->settings->get('translate_wp_localize_script') !== true) {
                return;
            }
            if ($this->gtbabel->settings->get('translate_wp_localize_script_include') === null) {
                return;
            }
            $GLOBALS['wp_scripts'] = new gtbabel_localize_script();
        });
    }

    private function filterSpecificUrls()
    {
        foreach (['rest_url', 'wp_redirect'] as $names__value) {
            add_filter(
                $names__value,
                function ($url) {
                    if ($this->gtbabel->started === true) {
                        $url = $this->gtbabel->data->getUrlTranslationInLanguage(
                            $this->gtbabel->settings->getSourceLanguageCode(),
                            $this->gtbabel->data->getCurrentLanguageCode(),
                            $url
                        );
                    }
                    return $url;
                },
                PHP_INT_MAX
            );
        }
    }

    private function autoTranslatePluginMails()
    {
        $this->autoTranslateContactForm7Mails();
        $this->autoTranslateWPFormsFrontend();
        $this->autoTranslateMails();
    }

    private function autoTranslateContactForm7Mails()
    {
        add_action(
            'wpcf7_contact_form',
            function ($form) {
                if ($this->gtbabel->data->sourceLngIsRefererLng()) {
                    return;
                }
                $props = $form->get_properties();
                foreach (['mail', 'mail_2'] as $mails__value) {
                    if (isset($props[$mails__value]) && !empty($props[$mails__value])) {
                        $props[$mails__value]['subject'] = preg_replace(
                            '/(\[.+?\])/',
                            '',
                            $props[$mails__value]['subject']
                        );
                        $props[$mails__value]['body'] = preg_replace(
                            '/(<)(\[.+?\])(>)/',
                            '$2',
                            $props[$mails__value]['body']
                        );
                        $props[$mails__value]['body'] = preg_replace(
                            '/(\[.+?\])/',
                            '<span class="notranslate force-tokenize">$1</span>',
                            $props[$mails__value]['body']
                        );
                        $props[$mails__value]['use_html'] = true;
                    }
                }
                $form->set_properties($props);
            },
            99999
        );
    }

    private function autoTranslateWPFormsFrontend()
    {
        add_filter(
            'wpforms_frontend_strings',
            function ($data) {
                foreach ($data as $data__key => $data__value) {
                    if (strpos($data__key, 'val_') === 0 && $data__value != '') {
                        $data[$data__key] = $this->gtbabel->translate($data__value);
                    }
                }
                return $data;
            },
            PHP_INT_MAX
        );
    }

    private function autoTranslateMails()
    {
        add_filter(
            'wp_mail',
            function ($atts) {
                $atts['subject'] = $this->gtbabel->translate($atts['subject']);
                $atts['message'] = $this->gtbabel->translate($atts['message']);
                return $atts;
            },
            PHP_INT_MAX
        );
    }

    private function autoTranslateSearch()
    {
        $original_query = null;
        add_action('pre_get_posts', function ($query) {
            if (!$query->is_main_query() || is_admin() || !is_search()) {
                return;
            }
            global $original_query;
            $original_query = $query->get('s');
            $query->set(
                's',
                $this->gtbabel->translate(
                    $query->get('s'),
                    $this->gtbabel->settings->getSourceLanguageCode(),
                    $this->gtbabel->data->getCurrentLanguageCode()
                )
            );
        });
        // reset again (so that in the output on the page it's the original query)
        add_action('template_redirect', function ($query) {
            global $original_query;
            if ($original_query === null) {
                return;
            }
            global $wp_query;
            $wp_query->query_vars['s'] = $original_query;
        });
    }

    private function disableAutoRedirect()
    {
        // this is important in the following cases:
        // woocommerce storefront filtering pagination (paged=1 does not properly redirect)

        // possibility #1 (this is currently disabled)
        //remove_filter( 'template_redirect', 'redirect_canonical' );

        // possibility #2 (we want to intentionally throw a 404)
        add_filter('redirect_canonical', function ($redirect_url) {
            $url =
                'http' .
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
                '://' .
                $_SERVER['HTTP_HOST'] .
                $_SERVER['REQUEST_URI'];
            if (
                strpos(trim($redirect_url, '/'), trim($url, '/')) !== 0 &&
                strpos(trim($url, '/'), trim($redirect_url, '/')) !== 0
            ) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
            }
            return false;
        });
    }

    private function startHook()
    {
        // use a "faster" hook for translating dom changes
        if (isset($_GET['gtbabel_translate_part']) && $_GET['gtbabel_translate_part'] == '1' && isset($_POST['html'])) {
            add_action('plugins_loaded', function () {
                $this->setupConfig();
                if ($this->isFrontend()) {
                    $this->gtbabel->start();
                }
            });
        } else {
            add_action('after_setup_theme', function () {
                $this->setupConfig();
                if ($this->isFrontend()) {
                    $this->gtbabel->start();
                }
                if (isset($_GET['gtbabel_export'])) {
                    if ($_GET['gtbabel_export'] == 'po') {
                        $this->gtbabel->gettext->export();
                    }
                    if ($_GET['gtbabel_export'] == 'xlsx') {
                        $this->gtbabel->excel->export();
                    }
                }
            });
            add_action(
                'template_redirect',
                function () {
                    if ($this->isFrontend()) {
                        $this->gtbabel->data->addCurrentUrlToTranslations(true);
                    }
                },
                999999
            );
        }
    }

    private function stopHook()
    {
        add_action(
            'shutdown',
            function () {
                if ($this->isFrontend()) {
                    $this->gtbabel->stop();
                }
            },
            0
        );
    }

    private function setupConfig()
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

        if (is_user_logged_in()) {
            $settings['unchecked_strings'] = 'trans';
        }

        $settings['frontend_editor'] =
            is_user_logged_in() &&
            current_user_can('gtbabel__translation_frontendeditor') &&
            isset($_GET['gtbabel_frontend_editor']) &&
            $_GET['gtbabel_frontend_editor'] == '1';

        // url settings are stored in the wordpress database for migration purposes
        $url_settings = get_option('gtbabel_url_settings');
        if ($url_settings !== null && is_array($url_settings) && !empty($url_settings)) {
            foreach ($url_settings as $url_settings__value) {
                foreach ($settings['languages'] as $languages__key => $languages__value) {
                    if ($languages__value['code'] !== $url_settings__value['code']) {
                        continue;
                    }
                    if (isset($url_settings__value['url_base'])) {
                        $settings['languages'][$languages__key]['url_base'] = $url_settings__value['url_base'];
                    }
                    if (isset($url_settings__value['url_prefix'])) {
                        $settings['languages'][$languages__key]['url_prefix'] = $url_settings__value['url_prefix'];
                    }
                }
            }
        }

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

        $this->gtbabel->config($settings);

        // define wpml fallback constant
        if (!defined('ICL_LANGUAGE_CODE')) {
            define('ICL_LANGUAGE_CODE', $this->gtbabel->data->getCurrentLanguageCode());
        }

        // run update migrations
        $this->runUpdateMigrations();
    }

    private function isFrontend()
    {
        global $pagenow;
        return !is_admin() && $pagenow != 'wp-login.php' && $pagenow != 'wp-register.php';
    }

    private function isBackend()
    {
        return !$this->isFrontend();
    }

    private function reset()
    {
        delete_option('gtbabel_token');
        delete_option('gtbabel_url_settings');
        delete_option('gtbabel_plugin_version');
        $this->gtbabel->reset();
    }

    private function installHook()
    {
        register_activation_hook(__FILE__, function () {
            $this->setupPluginFileStoreFolder();
            $this->setDefaultSettingsToOption();
        });
    }

    private function runUpdateMigrations()
    {
        if ($this->isFrontend()) {
            return;
        }
        $version_prev = get_option('gtbabel_plugin_version');
        $version_next = get_file_data(__FILE__, ['Version' => 'Version'], false)['Version'];
        if ($version_prev === false || $version_prev === null || $version_prev === '') {
            $version_prev = $version_next;
        }
        // debug
        //$version_prev = '4.9.5';
        if ($version_next === $version_prev) {
            return;
        }
        $migrations = [
            '1.0.0' => function () {
                $this->gtbabel->log->generalLog('running update 1.0.0...');
                $this->renameSetting('example1', 'example2');
            }
        ];
        foreach ($migrations as $migrations__key => $migrations__value) {
            if (
                intval(str_replace('.', '', $migrations__key)) > intval(str_replace('.', '', $version_prev)) &&
                intval(str_replace('.', '', $migrations__key)) <= intval(str_replace('.', '', $version_next))
            ) {
                $migrations__value();
            }
        }
        update_option('gtbabel_plugin_version', $version_next);
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
                    $this->gtbabel->settings->get('prevent_publish_wp_new_posts') === true &&
                    (($post_before_status === 'auto-draft' && $post_after_status === 'publish') ||
                        ($post_before_status === 'draft' && $post_after_status === 'publish') ||
                        ($post_before_status === 'publish' && $post_after_status === 'draft'));
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
        add_action('admin_notices', function () {
            if ($this->getSetting('wizard_finished') === true) {
                return;
            }
            global $pagenow;
            if ($pagenow === 'admin.php' && $_GET['page'] === 'gtbabel-wizard') {
                return;
            }
            echo '<div class="notice notice-gtbabel-wizard is-dismissible">';
            echo '<p>' . __('Run the Gtbabel wizard in order to get started!', 'gtbabel-plugin') . '</p>';
            echo '<p>';
            echo '<a href="' . admin_url('admin.php?page=gtbabel-wizard') . '" class="button button-primary">';
            echo __('Start wizard', 'gtbabel-plugin');
            echo '</a>';
            echo '</p>';
            echo '</div>';
            echo "<script>
            jQuery(function($) {
                $(document).on('click', '.notice-gtbabel-wizard .notice-dismiss', function() {
                    $.ajax(ajaxurl, { type: 'POST', data: { action: 'dismiss_custom_notice' } } );
                });
            });
            </script>";
        });
        add_action('wp_ajax_dismiss_custom_notice', function () {
            $this->saveSetting('wizard_finished', true);
        });
    }

    private function addLastmodToSitemap()
    {
        add_filter(
            'wp_sitemaps_posts_entry',
            function ($entry, $post) {
                $entry['lastmod'] = $post->post_modified_gmt;
                return $entry;
            },
            10,
            2
        );
    }

    private function addTopBarItem()
    {
        add_action(
            'admin_bar_menu',
            function ($admin_bar) {
                if (is_admin()) {
                    return;
                }

                $lng = $this->gtbabel->data->sourceLngIsCurrentLng()
                    ? null
                    : $this->gtbabel->data->getCurrentLanguageCode();

                if ($lng !== null && !current_user_can('gtbabel__translate_' . $lng)) {
                    return;
                }

                echo '<style>#wpadminbar #wp-admin-bar-gtbabel-backend-editor .ab-icon:before { content: "\f11f"; top: 3px; }</style>';
                $html = '<span class="ab-icon"></span>' . __('Backend editor', 'gtbabel-plugin');
                $url = $this->gtbabel->host->getCurrentUrl();
                $admin_bar->add_menu([
                    'id' => 'gtbabel-backend-editor',
                    'parent' => null,
                    'group' => null,
                    'title' => $html,
                    'href' => admin_url('admin.php?page=gtbabel-trans&url=' . urlencode($url) . '&lng=' . $lng),
                    'meta' => ['target' => '_blank']
                ]);

                if (
                    !$this->gtbabel->data->sourceLngIsCurrentLng() &&
                    current_user_can('gtbabel__translation_frontendeditor')
                ) {
                    echo '<style>#wpadminbar #wp-admin-bar-gtbabel-frontend-editor .ab-icon:before { content: "\f11f"; top: 3px; }</style>';
                    $html = '<span class="ab-icon"></span>' . __('Frontend editor', 'gtbabel-plugin');
                    $admin_bar->add_menu([
                        'id' => 'gtbabel-frontend-editor',
                        'parent' => null,
                        'group' => null,
                        'title' => $html,
                        'href' =>
                            $url .
                            (isset($_GET['gtbabel_frontend_editor']) && $_GET['gtbabel_frontend_editor'] == '1'
                                ? ''
                                : (strpos($url, '?') ? '&' : '?') . 'gtbabel_frontend_editor=1'),
                        'meta' => []
                    ]);
                }
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
                        if (!current_user_can('gtbabel__translate_' . $languages__key)) {
                            continue;
                        }
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
            return $this->gtbabel->data->getLanguagePickerHtml();
        });
    }

    private function languagePickerMenu()
    {
        add_filter(
            'nav_menu_meta_box_object',
            function ($object) {
                add_meta_box(
                    'custom-menu-metabox',
                    __('Gtbabel language picker', 'gtbabel-plugin'),
                    function () {
                        global $nav_menu_selected_id;
                        echo '
                        <div id="gtbabel-slug-div">
                            <div id="tabs-panel-gtbabel-slug-all" class="tabs-panel tabs-panel-active">
                            <ul id="gtbabel-slug-checklist-pop" class="categorychecklist form-no-clear">
                            ' .
                            walk_nav_menu_tree(
                                array_map('wp_setup_nav_menu_item', [
                                    (object) [
                                        'ID' => 1,
                                        'object_id' => 1,
                                        'type_label' => '',
                                        'title' => __('Gtbabel language picker', 'gtbabel-plugin'),
                                        'url' => '#gtbabel_languagepicker',
                                        'type' => 'custom',
                                        'object' => 'gtbabel-slug-slug',
                                        'db_id' => 0,
                                        'menu_item_parent' => 0,
                                        'post_parent' => 0,
                                        'target' => '',
                                        'attr_title' => '',
                                        'description' => '',
                                        'classes' => [],
                                        'xfn' => ''
                                    ]
                                ]),
                                0,
                                (object) ['walker' => new \Walker_Nav_Menu_Checklist(false)]
                            ) .
                            '
                            </ul>
                            <p class="button-controls">
                                <span class="add-to-menu">
                                    <input type="submit" ' .
                            wp_nav_menu_disabled_check($nav_menu_selected_id, false) .
                            ' class="button-secondary submit-add-to-menu right" value="' .
                            __('Add to Menu', 'gtbabel-plugin') .
                            '" name="add-gtbabel-slug-menu-item" id="submit-gtbabel-slug-div" />
                                    <span class="spinner"></span>
                                </span>
                            </p>
                            </div>
                        </div>
                        ';
                    },
                    'nav-menus',
                    'side',
                    'default'
                );
                return $object;
            },
            10,
            1
        );
    }

    private function initBackend()
    {
        add_action('admin_menu', function () {
            $menus = [];

            $menu = add_menu_page(
                'Gtbabel',
                'Gtbabel',
                'gtbabel__edit_settings',
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
                'gtbabel__edit_settings',
                'gtbabel-settings'
            );

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Translations', 'gtbabel-plugin'),
                __('Translations', 'gtbabel-plugin'),
                'gtbabel__translation_list',
                'gtbabel-trans',
                function () {
                    $this->initBackendStringTranslation();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Actions', 'gtbabel-plugin'),
                __('Actions', 'gtbabel-plugin'),
                'gtbabel__edit_settings',
                'gtbabel-actions',
                function () {
                    $this->initBackendActions();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Language picker', 'gtbabel-plugin'),
                __('Language picker', 'gtbabel-plugin'),
                'gtbabel__edit_settings',
                'gtbabel-lngpicker',
                function () {
                    $this->initBackendLanguagePicker();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Export/import', 'gtbabel-plugin'),
                __('Export/import', 'gtbabel-plugin'),
                'gtbabel__edit_settings',
                'gtbabel-exportimport',
                function () {
                    $this->initBackendImportExport();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Permissions', 'gtbabel-plugin'),
                __('Permissions', 'gtbabel-plugin'),
                'gtbabel__edit_settings',
                'gtbabel-permissions',
                function () {
                    $this->initBackendPermissions();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Translation wizard', 'gtbabel-plugin'),
                __('Translation wizard', 'gtbabel-plugin'),
                'gtbabel__translation_assistant',
                'gtbabel-transwizard',
                function () {
                    $this->initBackendTranslationWizard();
                }
            );
            $menus[] = $submenu;

            $submenu = add_submenu_page(
                'gtbabel-settings',
                __('Setup wizard', 'gtbabel-plugin'),
                __('Setup wizard', 'gtbabel-plugin'),
                'gtbabel__edit_settings',
                'gtbabel-wizard',
                function () {
                    $this->initBackendWizard();
                }
            );
            $menus[] = $submenu;

            foreach ($menus as $menus__value) {
                add_action('admin_print_styles-' . $menus__value, function () {
                    wp_enqueue_style('gtbabel-css', plugins_url('assets/build/bundle.css', __FILE__));
                });
                add_action('admin_print_scripts-' . $menus__value, function () {
                    wp_enqueue_script('gtbabel-js', plugins_url('assets/build/bundle.js', __FILE__));
                });
            }
        });
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_media();
        });
    }

    private function initBackendSettings()
    {
        $this->checkToken();

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
                            'redirect_root_domain',
                            'basic_auth',
                            'translate_html',
                            'translate_html_include',
                            'translate_html_exclude',
                            'translate_html_force_tokenize',
                            'localize_js',
                            'localize_js_strings',
                            'detect_dom_changes',
                            'detect_dom_changes_include',
                            'translate_xml',
                            'translate_xml_include',
                            'translate_json',
                            'translate_json_include',
                            'translate_wp_localize_script',
                            'translate_wp_localize_script_include',
                            'prevent_publish_urls',
                            'prevent_publish_wp_new_posts',
                            'url_query_args',
                            'alt_lng_urls',
                            'exclude_urls_content',
                            'exclude_urls_slugs',
                            'html_lang_attribute',
                            'html_hreflang_tags',
                            'xml_hreflang_tags',
                            'auto_add_translations',
                            'unchecked_strings',
                            'wp_mail_notifications',
                            'auto_set_new_strings_checked',
                            'auto_translation',
                            'auto_translation_service',
                            'google_translation_api_key',
                            'microsoft_translation_api_key',
                            'deepl_translation_api_key',
                            'google_throttle_chars_per_month',
                            'microsoft_throttle_chars_per_month',
                            'deepl_throttle_chars_per_month',
                            'url_settings',
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
                    $settings = __::array_map_deep($settings, function ($settings__value, $settings__key) {
                        if (in_array($settings__key, ['localize_js_strings'])) {
                            return wp_kses_post($settings__value);
                        }
                        return sanitize_textarea_field($settings__value);
                    });

                    foreach (
                        [
                            'translate_html',
                            'translate_xml',
                            'translate_json',
                            'translate_wp_localize_script',
                            'prevent_publish_wp_new_posts',
                            'html_lang_attribute',
                            'html_hreflang_tags',
                            'xml_hreflang_tags',
                            'debug_translations',
                            'auto_add_translations',
                            'auto_set_new_strings_checked',
                            'auto_translation',
                            'localize_js',
                            'detect_dom_changes',
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
                            'google_translation_api_key',
                            'microsoft_translation_api_key',
                            'deepl_translation_api_key',
                            'exclude_urls_content',
                            'exclude_urls_slugs',
                            'translate_html_force_tokenize',
                            'translate_wp_localize_script_include',
                            'detect_dom_changes_include',
                            'localize_js_strings'
                        ]
                        as $repeater__value
                    ) {
                        $post_data = $settings[$repeater__value];
                        $settings[$repeater__value] = [];
                        if (!empty(@$post_data)) {
                            foreach ($post_data as $post_data__key => $post_data__value) {
                                if (@$post_data__value == '') {
                                    continue;
                                }
                                $settings[$repeater__value][] = $post_data__value;
                            }
                        }
                    }

                    if (@$settings['wp_mail_notifications'] === 'false') {
                        $settings['wp_mail_notifications'] = false;
                    }

                    $url_settings = [];
                    $post_data = $settings['url_settings'];
                    if (!empty(@$post_data['code'])) {
                        foreach ($post_data['code'] as $post_data__key => $post_data__value) {
                            if (@$post_data['code'][$post_data__key] == '') {
                                continue;
                            }
                            $url_settings[] = [
                                'code' => $post_data['code'][$post_data__key],
                                'url_base' => $post_data['url_base'][$post_data__key],
                                'url_prefix' => $post_data['url_prefix'][$post_data__key]
                            ];
                        }
                    }
                    if (!empty($url_settings)) {
                        update_option('gtbabel_url_settings', $url_settings);
                    } else {
                        delete_option('gtbabel_url_settings');
                    }
                    unset($settings['url_settings']);

                    $post_data = $settings['prevent_publish_urls'];
                    $settings['prevent_publish_urls'] = [];
                    if (!empty(@$post_data['url'])) {
                        foreach ($post_data['url'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['url'][$post_data__key] == '' &&
                                @$post_data['lngs'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['prevent_publish_urls'][$post_data['url'][$post_data__key]] = explode(
                                ',',
                                $post_data['lngs'][$post_data__key]
                            );
                        }
                    }

                    $post_data = $settings['translate_xml_include'];
                    $settings['translate_xml_include'] = [];
                    if (!empty(@$post_data['selector'])) {
                        foreach ($post_data['selector'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['selector'][$post_data__key] == '' &&
                                @$post_data['attribute'][$post_data__key] == '' &&
                                @$post_data['context'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['translate_xml_include'][] = [
                                'selector' => $post_data['selector'][$post_data__key],
                                'attribute' => $post_data['attribute'][$post_data__key],
                                'context' => $post_data['context'][$post_data__key]
                            ];
                        }
                    }

                    $post_data = $settings['auto_translation_service'];
                    $settings['auto_translation_service'] = [];
                    if (!empty(@$post_data['provider'])) {
                        foreach ($post_data['provider'] as $post_data__key => $post_data__value) {
                            if (@$post_data['provider'][$post_data__key] == '') {
                                continue;
                            }
                            $auto_translation_service_lng = null;
                            if (@$post_data['lng'][$post_data__key] != '') {
                                $auto_translation_service_lng = explode(',', $post_data['lng'][$post_data__key]);
                            }
                            $settings['auto_translation_service'][] = [
                                'provider' => $post_data['provider'][$post_data__key],
                                'lng' => $auto_translation_service_lng
                            ];
                        }
                    }

                    $post_data = $settings['url_query_args'];
                    $settings['url_query_args'] = [];
                    if (!empty(@$post_data['type'])) {
                        foreach ($post_data['type'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['type'][$post_data__key] == '' &&
                                @$post_data['selector'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['url_query_args'][] = [
                                'type' => $post_data['type'][$post_data__key],
                                'selector' => $post_data['selector'][$post_data__key]
                            ];
                        }
                    }

                    $post_data = $settings['translate_json_include'];
                    $settings['translate_json_include'] = [];
                    if (!empty(@$post_data['url'])) {
                        foreach ($post_data['url'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['url'][$post_data__key] == '' &&
                                @$post_data['keys'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['translate_json_include'][$post_data['url'][$post_data__key]] = explode(
                                ',',
                                $post_data['keys'][$post_data__key]
                            );
                        }
                    }

                    $post_data = $settings['alt_lng_urls'];
                    $settings['alt_lng_urls'] = [];
                    if (!empty(@$post_data['url'])) {
                        foreach ($post_data['url'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['url'][$post_data__key] == '' &&
                                @$post_data['lng'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['alt_lng_urls'][$post_data['url'][$post_data__key]] =
                                $post_data['lng'][$post_data__key];
                        }
                    }

                    $post_data = @$settings['hide_languages'];
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

                    $post_data = $settings['translate_html_include'];
                    $settings['translate_html_include'] = [];
                    if (!empty(@$post_data['selector'])) {
                        foreach ($post_data['selector'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['selector'][$post_data__key] == '' &&
                                @$post_data['attribute'][$post_data__key] == '' &&
                                @$post_data['context'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['translate_html_include'][] = [
                                'selector' => $post_data['selector'][$post_data__key],
                                'attribute' => $post_data['attribute'][$post_data__key],
                                'context' => $post_data['context'][$post_data__key]
                            ];
                        }
                    }

                    $post_data = $settings['translate_html_exclude'];
                    $settings['translate_html_exclude'] = [];
                    if (!empty(@$post_data['selector'])) {
                        foreach ($post_data['selector'] as $post_data__key => $post_data__value) {
                            if (
                                @$post_data['selector'][$post_data__key] == '' &&
                                @$post_data['attribute'][$post_data__key] == ''
                            ) {
                                continue;
                            }
                            $settings['translate_html_exclude'][] = [
                                'selector' => $post_data['selector'][$post_data__key],
                                'attribute' => $post_data['attribute'][$post_data__key]
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
                    foreach ($settings['languages'] as $languages__key => $languages__value) {
                        unset($settings['languages'][$languages__key]['url_base']);
                        unset($settings['languages'][$languages__key]['url_prefix']);
                    }

                    $this->saveSettings($settings);
                    $this->setupConfig();
                }
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

        echo '<div class="gtbabel gtbabel--settings wrap" action="' .
            admin_url('admin.php?page=gtbabel-settings') .
            '">';
        echo '<form class="gtbabel__form" method="post">';
        wp_nonce_field('gtbabel-settings');
        echo '<input type="hidden" name="gtbabel[wizard_finished]" value="' .
            (isset($settings['wizard_finished']) && $settings['wizard_finished'] == 1 ? 1 : 0) .
            '" />';
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
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
        echo '<option value="ip"' .
            ($settings['redirect_root_domain'] == 'ip' ? ' selected="selected"' : '') .
            '>' .
            __('IP based', 'gtbabel-plugin') .
            '</option>';
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_basic_auth" class="gtbabel__label">';
        echo __('Basic auth', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input placeholder="username:password" class="gtbabel__input" type="text" id="gtbabel_basic_auth" name="gtbabel[basic_auth]" value="' .
            $settings['basic_auth'] .
            '" />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_translate_html" class="gtbabel__label">';
        echo __('Translate html', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_translate_html" name="gtbabel[translate_html]" value="1"' .
            ($settings['translate_html'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Include dom nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['translate_html_include'])) {
            $settings['translate_html_include'] = [['selector' => '', 'attribute' => '', 'context' => '']];
        }
        foreach ($settings['translate_html_include'] as $translate_html_include__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-3">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_html_include][selector][]" value="' .
                esc_attr($translate_html_include__value['selector']) .
                '" placeholder="selector" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_html_include][attribute][]" value="' .
                esc_attr($translate_html_include__value['attribute']) .
                '" placeholder="attribute" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_html_include][context][]" value="' .
                esc_attr($translate_html_include__value['context']) .
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
        echo __('Exclude dom nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['translate_html_exclude'])) {
            $settings['translate_html_exclude'] = [['selector' => '', 'attribute' => '']];
        }
        foreach ($settings['translate_html_exclude'] as $translate_html_exclude__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_html_exclude][selector][]" value="' .
                esc_attr(@$translate_html_exclude__value['selector']) .
                '" placeholder="selector" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_html_exclude][attribute][]" value="' .
                esc_attr(@$translate_html_exclude__value['attribute']) .
                '" placeholder="attribute" />';
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
        echo '<label for="gtbabel_localize_js" class="gtbabel__label">';
        echo __('Provide strings in JavaScript', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_localize_js" name="gtbabel[localize_js]" value="1"' .
            ($settings['localize_js'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Strings in JavaScript', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['localize_js_strings'])) {
            $settings['localize_js_strings'] = [''];
        }
        foreach ($settings['localize_js_strings'] as $localize_js_strings__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[localize_js_strings][]" value="' .
                esc_html($localize_js_strings__value) .
                '" />';
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
        echo '<label for="gtbabel_detect_dom_changes" class="gtbabel__label">';
        echo __('Detect dom changes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_detect_dom_changes" name="gtbabel[detect_dom_changes]" value="1"' .
            ($settings['detect_dom_changes'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Detect dom changes in areas', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['detect_dom_changes_include'])) {
            $settings['detect_dom_changes_include'] = [''];
        }
        foreach ($settings['detect_dom_changes_include'] as $detect_dom_changes_include__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[detect_dom_changes_include][]" value="' .
                esc_attr($detect_dom_changes_include__value) .
                '" placeholder="selector" />';
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
        echo '<label for="gtbabel_translate_xml" class="gtbabel__label">';
        echo __('Translate xml', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_translate_xml" name="gtbabel[translate_xml]" value="1"' .
            ($settings['translate_xml'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Include xml nodes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['translate_xml_include'])) {
            $settings['translate_xml_include'] = [['selector' => '', 'attribute' => '', 'context' => '']];
        }
        foreach ($settings['translate_xml_include'] as $translate_xml_include__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-3">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_xml_include][selector][]" value="' .
                esc_attr($translate_xml_include__value['selector']) .
                '" placeholder="selector" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_xml_include][attribute][]" value="' .
                esc_attr($translate_xml_include__value['attribute']) .
                '" placeholder="attribute" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_xml_include][context][]" value="' .
                esc_attr($translate_xml_include__value['context']) .
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
        echo '<label for="gtbabel_translate_json" class="gtbabel__label">';
        echo __('Translate json', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_translate_json" name="gtbabel[translate_json]" value="1"' .
            ($settings['translate_json'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Translate json for urls and keys', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['translate_json_include'])) {
            $settings['translate_json_include'] = ['' => ['']];
        }
        foreach (
            $settings['translate_json_include']
            as $translate_json_include__key => $translate_json_include__value
        ) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_json_include][url][]" value="' .
                $translate_json_include__key .
                '" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_json_include][keys][]" value="' .
                implode(',', $translate_json_include__value) .
                '" />';
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
        echo '<label for="gtbabel_translate_wp_localize_script" class="gtbabel__label">';
        echo __('Translate wp_localize_script', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_translate_wp_localize_script" name="gtbabel[translate_wp_localize_script]" value="1"' .
            (@$settings['translate_wp_localize_script'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Translate wp_localize_script paths', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['translate_wp_localize_script_include'])) {
            $settings['translate_wp_localize_script_include'] = [''];
        }
        foreach ($settings['translate_wp_localize_script_include'] as $translate_wp_localize_script_include__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_wp_localize_script_include][]" value="' .
                esc_attr($translate_wp_localize_script_include__value) .
                '" placeholder="path" />';
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
        echo '<label for="gtbabel_xml_hreflang_tags" class="gtbabel__label">';
        echo __('Add xml hreflang tags', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_xml_hreflang_tags" name="gtbabel[xml_hreflang_tags]" value="1"' .
            ($settings['xml_hreflang_tags'] == '1' ? ' checked="checked"' : '') .
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
        echo '<label for="gtbabel_unchecked_strings" class="gtbabel__label">';
        echo __('Behaviour for unchecked strings', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_unchecked_strings" name="gtbabel[unchecked_strings]">';
        echo '<option value="trans"' .
            ($settings['unchecked_strings'] == 'trans' ? ' selected="selected"' : '') .
            '>' .
            __('Show translations', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="source"' .
            ($settings['unchecked_strings'] == 'source' ? ' selected="selected"' : '') .
            '>' .
            __('Show sources', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="hide"' .
            ($settings['unchecked_strings'] == 'hide' ? ' selected="selected"' : '') .
            '>' .
            __('Hide strings', 'gtbabel-plugin') .
            '</option>';
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_wp_mail_notifications" class="gtbabel__label">';
        echo __('Email notifications', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_wp_mail_notifications" name="gtbabel[wp_mail_notifications]">';
        echo '<option value="false"' .
            ($settings['wp_mail_notifications'] === false ? ' selected="selected"' : '') .
            '>' .
            __('Disable notifications', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="hourly"' .
            ($settings['wp_mail_notifications'] == 'hourly' ? ' selected="selected"' : '') .
            '>' .
            __('Send hourly', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="twicedaily"' .
            ($settings['wp_mail_notifications'] == 'twicedaily' ? ' selected="selected"' : '') .
            '>' .
            __('Send twice a day', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="daily"' .
            ($settings['wp_mail_notifications'] == 'daily' ? ' selected="selected"' : '') .
            '>' .
            __('Send daily', 'gtbabel-plugin') .
            '</option>';
        echo '<option value="weekly"' .
            ($settings['wp_mail_notifications'] == 'weekly' ? ' selected="selected"' : '') .
            '>' .
            __('Send weekly', 'gtbabel-plugin') .
            '</option>';
        echo '</select>';
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
        echo '<label class="gtbabel__label">';
        echo __('Translation service', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['auto_translation_service'])) {
            $settings['auto_translation_service'] = [['provider' => '', 'lng' => '']];
        }
        foreach ($settings['auto_translation_service'] as $auto_translation_service__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<select class="gtbabel__input gtbabel__input--select" name="gtbabel[auto_translation_service][provider][]">';
            echo '<option value="">&ndash;&ndash;</option>';
            foreach (
                [
                    'google' => __('Google', 'gtbabel-plugin'),
                    'microsoft' => __('Microsoft', 'gtbabel-plugin'),
                    'deepl' => __('DeepL', 'gtbabel-plugin')
                ]
                as $auto_translation_service_provider__key => $auto_translation_service_provider__value
            ) {
                echo '<option value="' .
                    $auto_translation_service_provider__key .
                    '"' .
                    (esc_attr(@$auto_translation_service__value['provider']) == $auto_translation_service_provider__key
                        ? ' selected="selected"'
                        : '') .
                    '>' .
                    $auto_translation_service_provider__value .
                    '</option>';
            }
            echo '</select>';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[auto_translation_service][lng][]" value="' .
                esc_attr(
                    @$auto_translation_service__value['lng'] != ''
                        ? implode(',', $auto_translation_service__value['lng'])
                        : ''
                ) .
                '" placeholder="lng" />';
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
        echo __('Google Translation API Key', 'gtbabel-plugin') .
            ' (<a href="https://console.cloud.google.com/apis/library/translate.googleapis.com" target="_blank">' .
            __('Link', 'gtbabel-plugin') .
            '</a>)';
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['google_translation_api_key'])) {
            $settings['google_translation_api_key'] = [''];
        }
        foreach ($settings['google_translation_api_key'] as $google_translation_api_key__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[google_translation_api_key][]" value="' .
                $google_translation_api_key__value .
                '" />';
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
        echo __('Microsoft Translation API Key', 'gtbabel-plugin') .
            ' (<a href="https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api" target="_blank">' .
            __('Link', 'gtbabel-plugin') .
            '</a>)';
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['microsoft_translation_api_key'])) {
            $settings['microsoft_translation_api_key'] = [''];
        }
        foreach ($settings['microsoft_translation_api_key'] as $microsoft_translation_api_key__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[microsoft_translation_api_key][]" value="' .
                $microsoft_translation_api_key__value .
                '" />';
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
        echo __('DeepL Translation API Key', 'gtbabel-plugin') .
            ' (<a href="https://www.deepl.com/pro#developer" target="_blank">' .
            __('Link', 'gtbabel-plugin') .
            '</a>)';
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['deepl_translation_api_key'])) {
            $settings['deepl_translation_api_key'] = [''];
        }
        foreach ($settings['deepl_translation_api_key'] as $deepl_translation_api_key__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[deepl_translation_api_key][]" value="' .
                $deepl_translation_api_key__value .
                '" />';
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
        echo '<label for="gtbabel_google_throttle_chars_per_month" class="gtbabel__label">';
        echo __('Google API throttling after chars per month', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input" type="text" id="gtbabel_google_throttle_chars_per_month" name="gtbabel[google_throttle_chars_per_month]" value="' .
            $settings['google_throttle_chars_per_month'] .
            '" />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_microsoft_throttle_chars_per_month" class="gtbabel__label">';
        echo __('Microsoft API throttling after chars per month', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input" type="text" id="gtbabel_microsoft_throttle_chars_per_month" name="gtbabel[microsoft_throttle_chars_per_month]" value="' .
            $settings['microsoft_throttle_chars_per_month'] .
            '" />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_deepl_throttle_chars_per_month" class="gtbabel__label">';
        echo __('DeepL API throttling after chars per month', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input" type="text" id="gtbabel_deepl_throttle_chars_per_month" name="gtbabel[deepl_throttle_chars_per_month]" value="' .
            $settings['deepl_throttle_chars_per_month'] .
            '" />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Prevent publish of pages', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['prevent_publish_urls'])) {
            $settings['prevent_publish_urls'] = ['' => ['']];
        }
        foreach ($settings['prevent_publish_urls'] as $prevent_publish_urls__key => $prevent_publish_urls__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[prevent_publish_urls][url][]" value="' .
                $prevent_publish_urls__key .
                '" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[prevent_publish_urls][lngs][]" value="' .
                implode(',', $prevent_publish_urls__value) .
                '" />';
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
        echo '<label for="gtbabel_prevent_publish_wp_new_posts" class="gtbabel__label">';
        echo __('Auto prevent publish of new posts', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" id="gtbabel_prevent_publish_wp_new_posts" name="gtbabel[prevent_publish_wp_new_posts]" value="1"' .
            (@$settings['prevent_publish_wp_new_posts'] == '1' ? ' checked="checked"' : '') .
            ' />';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label class="gtbabel__label">';
        echo __('Alternate language for main content', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['alt_lng_urls'])) {
            $settings['alt_lng_urls'] = ['' => ''];
        }
        foreach ($settings['alt_lng_urls'] as $alt_lng_urls__key => $alt_lng_urls__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[alt_lng_urls][url][]" value="' .
                $alt_lng_urls__key .
                '" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[alt_lng_urls][lng][]" value="' .
                $alt_lng_urls__value .
                '" />';
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
        echo __('URL query arguments', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['url_query_args'])) {
            $settings['url_query_args'] = [['type' => '', 'selector' => '']];
        }
        foreach ($settings['url_query_args'] as $url_query_args__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-2">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[url_query_args][type][]" value="' .
                esc_attr($url_query_args__value['type']) .
                '" placeholder="type" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[url_query_args][selector][]" value="' .
                esc_attr($url_query_args__value['selector']) .
                '" placeholder="selector" />';
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
        echo __('Disable translation of content for urls', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['exclude_urls_content'])) {
            $settings['exclude_urls_content'] = [''];
        }
        foreach ($settings['exclude_urls_content'] as $exclude_urls_content__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[exclude_urls_content][]" value="' .
                $exclude_urls_content__value .
                '" />';
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
        echo __('Disable translation of slugs for urls', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['exclude_urls_slugs'])) {
            $settings['exclude_urls_slugs'] = [''];
        }
        foreach ($settings['exclude_urls_slugs'] as $exclude_urls_slugs__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[exclude_urls_slugs][]" value="' .
                $exclude_urls_slugs__value .
                '" />';
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
        echo __('Force tokenize', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        if (empty(@$settings['translate_html_force_tokenize'])) {
            $settings['translate_html_force_tokenize'] = [''];
        }
        foreach ($settings['translate_html_force_tokenize'] as $translate_html_force_tokenize__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-1">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[translate_html_force_tokenize][]" value="' .
                esc_attr($translate_html_force_tokenize__value) .
                '" placeholder="selector" />';
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
        echo __('Language specific base urls and prefixes', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<div class="gtbabel__repeater">';
        echo '<ul class="gtbabel__repeater-list">';
        $url_settings = [];
        foreach ($this->gtbabel->settings->getSelectedLanguages() as $languages__value) {
            if (!isset($languages__value['url_base']) && !isset($languages__value['url_prefix'])) {
                continue;
            }
            $url_settings[] = [
                'code' => $languages__value['code'],
                'url_base' => $languages__value['url_base'],
                'url_prefix' => $languages__value['url_prefix']
            ];
        }
        if (empty($url_settings)) {
            $url_settings = [['code' => '', 'url_base' => '', 'url_prefix' => '']];
        }
        foreach ($url_settings as $url_settings__value) {
            echo '<li class="gtbabel__repeater-listitem gtbabel__repeater-listitem--count-3">';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[url_settings][code][]" value="' .
                $url_settings__value['code'] .
                '" placeholder="' .
                __('Language code', 'gtbabel-plugin') .
                '" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[url_settings][url_base][]" value="' .
                $url_settings__value['url_base'] .
                '" placeholder="' .
                __('Base URL (only if different from host)', 'gtbabel-plugin') .
                '" />';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[url_settings][url_prefix][]" value="' .
                $url_settings__value['url_prefix'] .
                '" placeholder="' .
                __('Prefix (leave empty if no prefix)', 'gtbabel-plugin') .
                '" />';
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

        echo '</form>';
        echo '</div>';
    }

    private function initBackendStringTranslationShowFile($str, $show_upload = true)
    {
        echo '<div class="gtbabel__file-info">';
        if ($str != '') {
            $url = '';
            if (mb_strpos($str, 'http') !== 0) {
                $url .= $this->gtbabel->host->getCurrentHost() . '/';
            }
            $url .= $str;
            if (preg_match('/.+\.(jpg|jpeg|png|gif|svg)$/i', $str)) {
                echo '<img class="gtbabel__file-info-img" src="' . $url . '" alt="" />';
            }
            echo '<a class="button button-secondary button-small gtbabel__file-info-link" target="_blank" href="' .
                $url .
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
        $this->checkToken();

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

        [$urls, $time] = $this->preloadAllUrlsForBackendTranslations($url, $lng);

        $data = $this->gtbabel->data->getGroupedTranslationsFromDatabase(
            $lng,
            $url === null,
            $urls,
            $time,
            isset($_GET['s']) && $_GET['s'] != '' ? htmlspecialchars_decode(esc_html(stripslashes($_GET['s']))) : null,
            isset($_GET['context']) && $_GET['context'] != ''
                ? ($_GET['context'] === '-'
                    ? ''
                    : $_GET['context'])
                : null,
            @$_GET['shared'],
            @$_GET['checked'],
            $this->getBackendPaginationCount($lng),
            (@$_GET['p'] != '' ? intval($_GET['p']) - 1 : 0) * $this->getBackendPaginationCount($lng)
        );

        $translations = $data['data'];

        $pagination = $this->initBackendPagination($data['count'], $lng);

        echo '<div class="gtbabel gtbabel--trans wrap">';
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
        echo $message;
        echo '<h2 class="gtbabel__subtitle">' . __('String translations', 'gtbabel-plugin') . '</h2>';

        echo '<div class="gtbabel__transmeta">';
        if ($url !== null) {
            echo '<p class="gtbabel__transmeta-mainlink">';
            if ($lng === null || $this->gtbabel->settings->getSourceLanguageCode() === $lng) {
                $link_public = $url;
            } else {
                $link_public = $this->gtbabel->data->getUrlTranslationInLanguage(
                    $this->gtbabel->host->getLanguageCodeFromUrl($url),
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
                        $this->gtbabel->host->getLanguageCodeFromUrl($url),
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
            if (!current_user_can('gtbabel__translate_' . $lng__key)) {
                continue;
            }
            echo '<li class="gtbabel__transmeta-listitem">';
            if ($lng === null || $lng !== $lng__key) {
                $lng_link = 'admin.php?page=gtbabel-trans&lng=' . $lng__key;
                if ($post_id !== null) {
                    $lng_link .= '&post_id=' . $post_id;
                } elseif ($url !== null) {
                    $lng_link .=
                        '&url=' .
                        $this->gtbabel->data->getUrlTranslationInLanguage(
                            $this->gtbabel->host->getLanguageCodeFromUrl($url),
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
            (isset($_GET['s']) ? htmlentities(stripslashes($_GET['s'])) : '') .
            '" placeholder="' .
            __('Search term', 'gtbabel-plugin') .
            '" />';
        echo '<select class="gtbabel__input gtbabel__input--select" name="context">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach ($this->gtbabel->data->getDistinctContexts() as $context__value) {
            if ($context__value == '') {
                $context__value = '-';
            }
            echo '<option value="' .
                $context__value .
                '"' .
                (isset($_GET['context']) && $_GET['context'] === $context__value ? ' selected="selected"' : '') .
                '>' .
                ($context__value == '-' ? __('No context', 'gtbabel-plugin') : $context__value) .
                '</option>';
        }
        echo '</select>';
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
                if (
                    $this->gtbabel->settings->getSourceLanguageCode() !== $languages__key &&
                    !current_user_can('gtbabel__translate_' . $languages__key)
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
                    if (
                        $this->gtbabel->settings->getSourceLanguageCode() !== $languages__key &&
                        !current_user_can('gtbabel__translate_' . $languages__key)
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
                            $languages__key !== $translations__value['lng_source'] &&
                                preg_match(
                                    '/.+\.(jpg|jpeg|png|gif|svg)$/i',
                                    $translations__value[$translations__value['lng_source']]
                                )
                        );
                    }
                    $discovered_url = null;
                    if ($languages__key !== $translations__value['lng_source']) {
                        if (@$translations__value[$languages__key . '_discovered_last_url'] != '') {
                            $discovered_url = $translations__value[$languages__key . '_discovered_last_url'];
                        }
                    } else {
                        if (@$translations__value['discovered_last_url_orig'] != '') {
                            $discovered_url = $translations__value['discovered_last_url_orig'];
                        }
                    }
                    if ($discovered_url !== null) {
                        echo '<a class="gtbabel__discovered-last-url-link" href="' .
                            get_bloginfo('url') .
                            '' .
                            $discovered_url .
                            '" title="' .
                            get_bloginfo('url') .
                            '' .
                            $discovered_url .
                            '" target="_blank">🔗</a>';
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

    private function initBackendTranslationWizard()
    {
        $this->checkToken();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['save_translation'])) {
                check_admin_referer('gtbabel-transwizard');
                if (!empty(@$_POST['gtbabel'])) {
                    // remove slashes
                    $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);
                    // sanitize
                    $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function ($settings__value) {
                        return wp_kses_post($settings__value);
                    });
                    foreach ($_POST['gtbabel'] as $post__key => $post__value) {
                        $input_data = __::decode_data($post__key);
                        $this->gtbabel->data->editTranslation(
                            $input_data['str'],
                            $input_data['context'],
                            $input_data['lng_source'],
                            $input_data['lng'],
                            $post__value,
                            true
                        );
                    }
                }
            }
        }

        $lng = isset($_GET['lng']) && $_GET['lng'] != '' ? sanitize_textarea_field($_GET['lng']) : null;

        echo '<div class="gtbabel gtbabel--transwizard wrap">';
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
        echo '<h2 class="gtbabel__subtitle">' . __('Translation wizard', 'gtbabel-plugin') . '</h2>';
        if ($lng === null) {
            $languages = $this->gtbabel->settings->getSelectedLanguageCodesLabels();
            if (!empty($languages)) {
                echo '<ul class="gtbabel__transwizard-languages">';
                foreach ($languages as $languages__key => $languages__value) {
                    if (!current_user_can('gtbabel__translate_' . $languages__key)) {
                        continue;
                    }
                    $count = $this->gtbabel->data->getTranslationCountFromDatabase($languages__key, false);
                    echo '<li class="gtbabel__transwizard-language' .
                        ($count === 0 ? ' gtbabel__transwizard-language--disabled' : '') .
                        '">';
                    echo '<a class="gtbabel__transwizard-language-link" href="' .
                        admin_url('admin.php?page=gtbabel-transwizard&lng=' . $languages__key) .
                        '">';
                    echo '<span class="gtbabel__transwizard-language-linktext">' . $languages__value . '</span>';
                    echo '<span class="gtbabel__transwizard-language-count' .
                        ($count > 0 ? ' gtbabel__transwizard-language-count--highlight wp-ui-highlight' : '') .
                        '">' .
                        $count .
                        '</span>';
                    echo '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }
        } else {
            $translations = $this->gtbabel->data->getGroupedTranslationsFromDatabase(
                $lng,
                true,
                null,
                null,
                null,
                null,
                null,
                false,
                1,
                0
            );
            if (!empty($translations['count'] > 0)) {
                $translation = $translations['data'][0];
                echo '<form class="gtbabel__form" method="post" action="' .
                    admin_url('admin.php?page=gtbabel-transwizard&lng=' . $lng) .
                    '">';
                wp_nonce_field('gtbabel-transwizard');

                echo '<p class="gtbabel__paragraph">';
                echo sprintf(
                    _n('%s translation left.', '%s translations left.', $translations['count'], 'gtbabel-plugin'),
                    $translations['count']
                );
                echo '</p>';

                echo '<div class="gtbabel__transwizard-card">';
                if (@$translation['context'] != '') {
                    echo '<div class="gtbabel__transwizard-card-context">' . $translation['context'] . '</div>';
                }

                if (@$translation[$lng . '_discovered_last_url'] != '') {
                    echo '<a class="gtbabel__transwizard-card-discovered-last-url" href="' .
                        get_bloginfo('url') .
                        '' .
                        $translation[$lng . '_discovered_last_url'] .
                        '" title="' .
                        get_bloginfo('url') .
                        '' .
                        $translation[$lng . '_discovered_last_url'] .
                        '" target="_blank">🔗</a>';
                }

                echo '<textarea readonly="readonly" class="gtbabel__input gtbabel__input--textarea gtbabel__transwizard-card-textarea gtbabel__transwizard-card-source gtbabel__wysiwyg-textarea">' .
                    $translation[$translation['lng_source']] .
                    '</textarea>';

                echo '<textarea required="required" class="gtbabel__input gtbabel__input--textarea gtbabel__transwizard-card-textarea gtbabel__transwizard-card-target gtbabel__wysiwyg-textarea" name="gtbabel[' .
                    __::encode_data([
                        'str' => $translation[$translation['lng_source']],
                        'context' => $translation['context'],
                        'lng_source' => $translation['lng_source'],
                        'lng' => $lng
                    ]) .
                    ']">' .
                    $translation[$lng] .
                    '</textarea>';
                echo '<input class="gtbabel__submit button button-primary gtbabel__transwizard-card-button" name="save_translation" value="' .
                    __('Next', 'gtbabel-plugin') .
                    '" type="submit" />';
                echo '</div>';
                echo '</form>';
            } else {
                echo '<div class="gtbabel__transwizard-done">';
                echo '<img class="gtbabel__transwizard-done-image" src="' .
                    plugin_dir_url(__FILE__) .
                    'assets/images/done.gif" alt="" />';
                echo '<p class="gtbabel__paragraph gtbabel__transwizard-done-text">' .
                    __('All done!', 'gtbabel-plugin') .
                    '</p>';
                echo '<a class="button button-primary" href="' . admin_url('admin.php?page=gtbabel-transwizard') . '">';
                echo __('To the overview', 'gtbabel-plugin');
                echo '</a>';
                echo '</div>';
            }
        }

        echo '</div>';
    }

    private function initBackendActions()
    {
        $this->checkToken();

        $message = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['bulk_change'])) {
                if (
                    isset($_POST['bulk_change_action']) &&
                    $_POST['bulk_change_action'] != '' &&
                    isset($_POST['bulk_change_language']) &&
                    $_POST['bulk_change_language'] != '' &&
                    isset($_POST['bulk_change_status']) &&
                    $_POST['bulk_change_status'] != ''
                ) {
                    $this->gtbabel->data->bulkEdit(
                        sanitize_text_field($_POST['bulk_change_action']),
                        $_POST['bulk_change_language'] === '*'
                            ? null
                            : sanitize_text_field($_POST['bulk_change_language']),
                        $_POST['bulk_change_status'] === '*'
                            ? null
                            : ($_POST['bulk_change_status'] === 'unchecked'
                                ? 0
                                : 1)
                    );
                }
            }

            if (isset($_POST['translate_missing'])) {
                $this->gtbabel->data->translateMissing();
            }

            if (isset($_POST['reset_settings'])) {
                $this->deleteSettings();
                $this->setDefaultSettingsToOption();
                $this->setupConfig();
            }

            if (isset($_POST['reset_translations'])) {
                $this->reset();
            }

            $message =
                '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                __('Successfully edited', 'gtbabel-plugin') .
                '</p></div>';
        }

        echo '<div class="gtbabel gtbabel--actions wrap">';
        echo '<form class="gtbabel__form" method="post" action="' . admin_url('admin.php?page=gtbabel-actions') . '">';
        wp_nonce_field('gtbabel-actions');
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
        echo $message;

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

        $this->initBackendAutoTranslate('page=gtbabel-actions');

        echo '<div class="gtbabel__stats-log">';
        echo '<h2 class="gtbabel__subtitle">' . __('Translation api usage stats', 'gtbabel-plugin') . '</h2>';
        echo $this->showStatsLog();
        echo '</div>';

        echo '<h2 class="gtbabel__subtitle">' . __('Correct translations', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">' .
            __(
                'Automatically corrects your (automatically) translated strings with publically available translations.',
                'gtbabel-plugin'
            ) .
            '</p>';
        echo '<ul class="gtbabel__fields">';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_grab_url" class="gtbabel__label">';
        echo __('URL in source language', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input" id="gtbabel_auto_grab_url" type="text" value="" placeholder="https://" />';
        echo '</div>';
        echo '</li>';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_auto_grab_dry_run" class="gtbabel__label">';
        echo __('Dry run', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input class="gtbabel__input gtbabel__input--checkbox" id="gtbabel_auto_grab_dry_run" type="checkbox" checked="checked" value="1" />';
        echo '</div>';
        echo '</li>';
        echo '</ul>';

        $this->initBackendAutoGrab('page=gtbabel-actions');

        echo '<h2 class="gtbabel__subtitle">' . __('Complete missing translations', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">' .
            __('Translates already discovered strings, where the translation is not yet done.', 'gtbabel-plugin') .
            '</p>';
        echo '<input class="gtbabel__submit button button-secondary" name="translate_missing" value="' .
            __('Translate', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '<h2 class="gtbabel__subtitle">' . __('Bulk changes', 'gtbabel-plugin') . '</h2>';
        echo '<ul class="gtbabel__fields">';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_bulk_change_language" class="gtbabel__label">';
        echo __('Language', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_bulk_change_language" name="bulk_change_language">';
        echo '<option value="">&ndash;&ndash;</option>';
        echo '<option value="*">' . __('All languages', 'gtbabel-plugin') . '</option>';
        foreach (
            $this->gtbabel->settings->getSelectedLanguageCodesLabelsWithoutSource()
            as $languages__key => $languages__value
        ) {
            echo '<option value="' . $languages__key . '">' . $languages__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_bulk_change_status" class="gtbabel__label">';
        echo __('Status', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_bulk_change_status" name="bulk_change_status">';
        echo '<option value="">&ndash;&ndash;</option>';
        echo '<option value="*">' . __('All statuses', 'gtbabel-plugin') . '</option>';
        foreach (
            ['checked' => __('Checked', 'gtbabel-plugin'), 'unchecked' => __('Unchecked', 'gtbabel-plugin')]
            as $statuses__key => $statuses__value
        ) {
            echo '<option value="' . $statuses__key . '">' . $statuses__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';
        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_bulk_change_action" class="gtbabel__label">';
        echo __('Action', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select class="gtbabel__input gtbabel__input--select" id="gtbabel_bulk_change_action" name="bulk_change_action">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach (
            [
                'delete' => __('Delete', 'gtbabel-plugin'),
                'uncheck' => __('Set to unchecked', 'gtbabel-plugin'),
                'check' => __('Set to checked', 'gtbabel-plugin')
            ]
            as $actions__key => $actions__value
        ) {
            echo '<option value="' . $actions__key . '">' . $actions__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';
        echo '</ul>';
        echo '<input class="gtbabel__submit button button-secondary" name="bulk_change" value="' .
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

    private function initBackendImportExport()
    {
        $this->checkToken();

        $message = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (
                isset($_POST['import']) &&
                @$_POST['gtbabel']['lng_source'] != '' &&
                @$_POST['gtbabel']['lng_target'] != '' &&
                @$_POST['gtbabel']['type'] != '' &&
                @$_FILES['gtbabel']['name']['file'] != ''
            ) {
                check_admin_referer('gtbabel-exportimport');
                // remove slashes
                $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);
                $_POST['gtbabel'] = __::array_map_deep($_POST['gtbabel'], function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });
                $_FILES['gtbabel'] = __::array_map_deep($_FILES['gtbabel'], function ($settings__value) {
                    return sanitize_textarea_field($settings__value);
                });

                $extension = strtolower(end(explode('.', $_FILES['gtbabel']['name']['file'])));
                $allowed_extension = [
                    'po' => ['application/octet-stream'],
                    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                ];
                if (
                    array_key_exists($extension, $allowed_extension) &&
                    in_array($_FILES['gtbabel']['type']['file'], $allowed_extension[$extension]) &&
                    $_FILES['gtbabel']['size']['file'] < 4000 * 1024 &&
                    $_FILES['gtbabel']['error']['file'] == 0
                ) {
                    $this->gtbabel->{$_POST['gtbabel']['type'] === 'po' ? 'gettext' : 'excel'}->import(
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

        echo '<div class="gtbabel gtbabel--exportimport wrap">';
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
        echo $message;

        echo '<h2 class="gtbabel__subtitle">' . __('Export', 'gtbabel-plugin') . '</h2>';

        echo '<form class="gtbabel__form" method="get" action="' . admin_url('admin.php') . '">';
        echo '<input type="hidden" name="page" value="gtbabel-exportimport" />';
        echo '<ul class="gtbabel__fields">';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_export_type" class="gtbabel__label">';
        echo __('Format', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select required="required" class="gtbabel__input gtbabel__input--select" id="gtbabel_export_type" name="gtbabel_export">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach (
            ['po' => __('Gettext (*.po)', 'gtbabel-plugin'), 'xlsx' => __('Excel (*.xlsx)', 'gtbabel-plugin')]
            as $types__key => $types__value
        ) {
            echo '<option value="' . $types__key . '">' . $types__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '</ul>';

        echo '<input class="gtbabel__submit button button-secondary" name="export" value="' .
            __('Export', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '</form>';

        echo '<h2 class="gtbabel__subtitle">' . __('Import', 'gtbabel-plugin') . '</h2>';

        echo '<form enctype="multipart/form-data" class="gtbabel__form" method="post" action="' .
            admin_url('admin.php?page=gtbabel-exportimport') .
            '">';
        wp_nonce_field('gtbabel-exportimport');
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
        echo '<label for="gtbabel_import_type" class="gtbabel__label">';
        echo __('Format', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<select required="required" class="gtbabel__input gtbabel__input--select" id="gtbabel_import_type" name="gtbabel[type]">';
        echo '<option value="">&ndash;&ndash;</option>';
        foreach (
            ['po' => __('Gettext (*.po)', 'gtbabel-plugin'), 'xlsx' => __('Excel (*.xlsx)', 'gtbabel-plugin')]
            as $types__key => $types__value
        ) {
            echo '<option value="' . $types__key . '">' . $types__value . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</li>';

        echo '<li class="gtbabel__field">';
        echo '<label for="gtbabel_file" class="gtbabel__label">';
        echo __('File', 'gtbabel-plugin');
        echo '</label>';
        echo '<div class="gtbabel__inputbox">';
        echo '<input required="required" class="gtbabel__input gtbabel__input--file" type="file" name="gtbabel[file]" id="gtbabel_file" accept=".po,.xlsx" />';
        echo '</div>';
        echo '</li>';
        echo '</ul>';

        echo '<input class="gtbabel__submit button button-secondary" name="import" value="' .
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

    private function initBackendPermissions()
    {
        $this->checkToken();

        $message = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['save_permissions'])) {
                check_admin_referer('gtbabel-permissions');
                if (!empty($_POST['permissions'])) {
                    foreach ($_POST['permissions'] as $permissions__key => $permissions__value) {
                        $role = get_role($permissions__key);
                        foreach ($permissions__value as $permissions__value__key => $permissions__value__value) {
                            if ($permissions__value__value == '1') {
                                $role->add_cap($permissions__value__key);
                            } else {
                                $role->remove_cap($permissions__value__key);
                            }
                        }
                    }
                }
            }
            $message =
                '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                __('Successfully edited', 'gtbabel-plugin') .
                '</p></div>';
        }

        echo '<div class="gtbabel gtbabel--permissions wrap">';
        echo '<form class="gtbabel__form" method="post" action="' .
            admin_url('admin.php?page=gtbabel-permissions') .
            '">';
        wp_nonce_field('gtbabel-permissions');
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
        echo $message;
        echo '<h2 class="gtbabel__subtitle">' . __('Permissions', 'gtbabel-plugin') . '</h2>';

        $roles = get_editable_roles();
        $capabilities = $this->getAvailableCapabilities();
        echo '<table class="gtbabel__table">';
        echo '<thead class="gtbabel__table-head">';
        echo '<tr class="gtbabel__table-row">';
        echo '<td class="gtbabel__table-cell">';
        echo '</td>';
        foreach ($roles as $roles__value) {
            echo '<td class="gtbabel__table-cell">';
            echo _x($roles__value['name'], 'User role');
            echo '</td>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody class="gtbabel__table-body">';
        foreach ($capabilities as $capabilities__key => $capabilities__value) {
            echo '<tr class="gtbabel__table-row">';
            echo '<td class="gtbabel__table-cell">';
            echo $capabilities__value;
            echo '</td>';
            foreach ($roles as $roles__key => $roles__value) {
                echo '<td class="gtbabel__table-cell">';
                echo '<input type="checkbox" class="gtbabel__input gtbabel__input--checkbox gtbabel__input--submit-unchecked" name="permissions[' .
                    $roles__key .
                    '][' .
                    $capabilities__key .
                    ']" ' .
                    ($roles__key === 'administrator' && $capabilities__key !== 'gtbabel__email_notifications'
                        ? 'disabled="disabled"'
                        : '') .
                    ' ' .
                    (array_key_exists($capabilities__key, $roles__value['capabilities']) &&
                    $roles__value['capabilities'][$capabilities__key] == '1'
                        ? 'checked="checked"'
                        : '') .
                    ' value="1" />';
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        echo '<input class="gtbabel__submit button button-primary" name="save_permissions" value="' .
            __('Save', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '</form>';
        echo '</div>';
    }

    private function initBackendLanguagePicker()
    {
        $this->checkToken();

        echo '<div class="gtbabel gtbabel--lngpicker wrap">';
        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';
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
                '<a target="_blank" href="' . admin_url('widgets.php') . '">',
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

        echo '<h2 class="gtbabel__subtitle">' . __('Menu', 'gtbabel-plugin') . '</h2>';
        echo '<p class="gtbabel__paragraph">';
        echo sprintf(
            __('Simply add the %sLanguage picker menu item%s to one of your menus.', 'gtbabel-plugin'),
            '<a target="_blank" href="' . admin_url('nav-menus.php') . '">',
            '</a>'
        );
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
        $this->checkToken();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['save_step'])) {
                $settings = [];

                if (isset($_POST['gtbabel'])) {
                    // remove slashes
                    $_POST['gtbabel'] = stripslashes_deep($_POST['gtbabel']);

                    // whitelist
                    foreach (['languages', 'google_translation_api_key'] as $fields__value) {
                        if (!isset($_POST['gtbabel'][$fields__value])) {
                            continue;
                        }
                        $settings[$fields__value] = $_POST['gtbabel'][$fields__value];
                    }
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

                $this->setupConfig();
            }
        }

        $settings = $this->getSettings();

        echo '<div class="gtbabel gtbabel--wizard">';

        echo '<h1 class="gtbabel__title">🌐 Gtbabel 🌐</h1>';

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
                    ? @$settings['google_translation_api_key'][0]
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

            echo '<div class="gtbabel__stats-log">';
            echo $this->showStatsLog('google');
            echo '</div>';

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
            if ($this->gtbabel->data->getTranslationCountFromDatabase() > 0) {
                echo '<a class="button button-primary" href="' . admin_url('admin.php?page=gtbabel-trans') . '">';
                echo __('Translated strings', 'gtbabel-plugin');
                echo '</a>';
            } else {
                echo '<a class="button button-primary" href="' . admin_url('admin.php?page=gtbabel-settings') . '">';
                echo __('To the settings', 'gtbabel-plugin');
                echo '</a>';
            }
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

    private function preloadAllUrlsForBackendTranslations($url, $lng)
    {
        if ($url !== null) {
            $urls = [];
            $urls[] = $url;
            $time = $this->gtbabel->utils->getCurrentTime();
            $this->fetch($this->buildFetchUrl($url));
            // subsequent urls are now available (we need to refresh the current session)
            $this->setupConfig();
            foreach ($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource() as $lngs__value) {
                if ($lng !== null && $lngs__value !== $lng) {
                    continue;
                }
                $url_trans = $this->gtbabel->data->getUrlTranslationInLanguage(
                    $this->gtbabel->host->getLanguageCodeFromUrl($url),
                    $lngs__value,
                    $url
                );
                $this->fetch($this->buildFetchUrl($url_trans));
                $urls[] = $url_trans;
            }
            // restart again
            $this->setupConfig();
            return [$urls, $time];
        }
        return [null, null];
    }

    private function buildTranslationFormUrl($p)
    {
        return admin_url(
            'admin.php?page=gtbabel-trans&p=' .
                $p .
                (isset($_GET['s']) && $_GET['s'] !== '' ? '&s=' . htmlentities(stripslashes($_GET['s'])) : '') .
                (isset($_GET['post_id']) && $_GET['post_id'] !== '' ? '&post_id=' . intval($_GET['post_id']) : '') .
                (isset($_GET['lng']) && $_GET['lng'] !== '' ? '&lng=' . sanitize_textarea_field($_GET['lng']) : '') .
                (isset($_GET['url']) && $_GET['url'] !== '' ? '&url=' . esc_url($_GET['url']) : '') .
                (isset($_GET['context']) && $_GET['context'] !== ''
                    ? '&context=' . sanitize_textarea_field($_GET['context'])
                    : '') .
                (isset($_GET['shared']) && $_GET['shared'] !== ''
                    ? '&shared=' . sanitize_textarea_field($_GET['shared'])
                    : '') .
                (isset($_GET['checked']) && $_GET['checked'] !== ''
                    ? '&checked=' . sanitize_textarea_field($_GET['checked'])
                    : '')
        );
    }

    private function getBackendPaginationCount($lng)
    {
        if ($lng !== null) {
            $shown_cols = 1;
        } else {
            $shown_cols = count($this->gtbabel->settings->getSelectedLanguageCodesWithoutSource());
        }
        return round(100 / pow($shown_cols, 1 / 3));
    }

    private function initBackendPagination($count, $lng)
    {
        $pagination = (object) [];
        $pagination->per_page = $this->getBackendPaginationCount($lng);
        $pagination->count = $count;
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
        if ($this->gtbabel->settings->get('basic_auth') !== null) {
            $url = str_replace('http://', 'http://' . $this->gtbabel->settings->get('basic_auth') . '@', $url);
            $url = str_replace('https://', 'https://' . $this->gtbabel->settings->get('basic_auth') . '@', $url);
        }
        $url .= implode('&', $args);
        return $url;
    }

    private function getAllPublicUrlsForSite()
    {
        $urls = [];

        // try yoast, native, plugin
        foreach (['sitemap_index.xml', 'wp-sitemap.xml', 'sitemap.xml'] as $targets__value) {
            if (empty($urls)) {
                $sitemap_url = $this->buildFetchUrl(
                    get_bloginfo('url') . '/' . $targets__value,
                    true,
                    false,
                    false,
                    false,
                    'source'
                );
                $urls = __::extract_urls_from_sitemap($sitemap_url);
            }
        }

        // another approach (get all posts; this is disabled, because every wp installation should provide a sitemap by default now)
        /*
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
        */

        if (empty($urls)) {
            return $urls;
        }

        $urls = array_filter($urls, function ($urls__value) {
            return strpos($urls__value, $this->gtbabel->host->getCurrentHost()) !== false;
        });

        // intentionally throw in a 404 page (so that content there [except the slug] is translated as well)
        $urls[] = get_bloginfo('url') . '/gtbabel-force-404/';

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
        return $response;
    }

    private function showStatsLog($service = null)
    {
        $data = $this->gtbabel->data->statsGetTranslatedCharsByService();
        if (empty($data)) {
            echo '<p>' . __('No translations available.', 'gtbabel-plugin') . '</p>';
            return;
        }
        echo '<ul>';
        foreach ($data as $data__value) {
            if ($service !== null && $data__value['service'] !== $service) {
                continue;
            }
            echo '<li>';
            echo $data__value['label'] . ': ';
            echo $data__value['length'];
            echo ' ';
            echo __('Characters', 'gtbabel-plugin');
            echo ' (~' . number_format($data__value['costs'], 2, ',', '.') . ' €)';
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
            if (empty($urls)) {
                echo '<span style="color:red;font-weight:bold;">';
                echo __('An error occured. Is your website accessible?', 'gtbabel-plugin');
                echo '<br/>';
                echo sprintf(
                    __('If your site is password protected, populate the %sbasic auth option%s.', 'gtbabel-plugin'),
                    '<a href="' . admin_url('admin.php?page=gtbabel-settings') . '">',
                    '</a>'
                );
                echo '</span>';
                die();
            }
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
                $this->setupConfig();
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
                'assets/images/loading.gif" width="64" height="64" alt="" />';
        }

        echo '</div>';
    }

    private function initBackendAutoGrab($page)
    {
        $chunk_size = 1;

        echo '<a data-loading-text="' .
            __('Loading', 'gtbabel-plugin') .
            '..." data-error-text="' .
            __('An error occurred', 'gtbabel-plugin') .
            '" data-href="' .
            admin_url('admin.php?' . $page . '&gtbabel_auto_grab=1') .
            '" href="#" class="gtbabel__submit gtbabel__submit--auto-grab button button-secondary">' .
            __('Correct', 'gtbabel-plugin') .
            '</a>';

        if (@$_GET['gtbabel_auto_grab'] != '1') {
            return;
        }

        if (@$_GET['gtbabel_auto_grab_url'] == '') {
            return;
        }

        $url = esc_url_raw($_GET['gtbabel_auto_grab_url']);

        $chunk = 0;
        if (@$_GET['gtbabel_auto_grab_chunk'] != '') {
            $chunk = intval($_GET['gtbabel_auto_grab_chunk']);
        }

        $dry_run = false;
        if (@$_GET['gtbabel_auto_grab_dry_run'] == '1') {
            $dry_run = true;
        }

        $time = null;
        if (__::x(@$_GET['gtbabel_auto_grab_time'])) {
            $time = $_GET['gtbabel_auto_grab_time'];
        } else {
            $time = $this->gtbabel->utils->getCurrentTime();
        }

        echo '<div class="gtbabel__auto-grab">';

        $sitemap_cache = get_transient('gtbabel_auto_grab_sitemap_cache');
        if ($sitemap_cache === false) {
            $sitemap_cache = [];
        }

        $return = $this->gtbabel->grab($url, $chunk, $dry_run, $sitemap_cache);

        set_transient('gtbabel_auto_grab_sitemap_cache', $return['sitemap']);

        echo $return['foreign_url'] . '<br/>';
        if (!empty($return['replacements'])) {
            foreach ($return['replacements'] as $replacements__value) {
                echo sprintf(
                    __('Replacing "%s" with "%s"...', 'gtbabel-plugin'),
                    $replacements__value[0],
                    $replacements__value[1]
                ) . '<br/>';
            }
            echo sprintf(
                _n(
                    'Made %s replacement...',
                    'Made %s replacements...',
                    count($return['replacements']),
                    'gtbabel-plugin'
                ),
                count($return['replacements'])
            ) . '<br/>';
        } else {
            echo __('Made no replacements...', 'gtbabel-plugin') . '<br/>';
        }

        // progress
        if ($return['count'] > 0) {
            $progress = ($chunk_size * $chunk + $chunk_size) / $return['count'];
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
        }

        // if finished
        if ($chunk_size * $chunk + $chunk_size > $return['count'] - 1) {
            echo __('Finished', 'gtbabel-plugin');
        }

        // next
        else {
            $redirect_url = admin_url(
                'admin.php?' .
                    $page .
                    '&gtbabel_auto_grab=1&gtbabel_auto_grab_chunk=' .
                    ($chunk + 1) .
                    (__::x($url) ? '&gtbabel_auto_grab_url=' . $url : '') .
                    ($dry_run === true ? '&gtbabel_auto_grab_dry_run=1' : '') .
                    (__::x($time) ? '&gtbabel_auto_grab_time=' . $time : '')
            );
            echo '<a href="' . $redirect_url . '" class="gtbabel__auto-grab-next"></a>';
            echo '<img class="gtbabel__auto-grab-loading" src="' .
                plugin_dir_url(__FILE__) .
                'assets/images/loading.gif" width="64" height="64" alt="" />';
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

    private function unsetSetting($key)
    {
        $settings = $this->getSettings();
        if (array_key_exists($key, $settings)) {
            unset($settings[$key]);
        }
        $this->saveSettings($settings);
    }

    private function renameSetting($key_old, $key_new)
    {
        $value = $this->getSetting($key_old);
        $this->saveSetting($key_new, $value);
        $this->unsetSetting($key_old);
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
                    'hreflang_code' => 'de',
                    'google_translation_code' => 'de',
                    'microsoft_translation_code' => 'de',
                    'deepl_translation_code' => 'de'
                ],
                [
                    'code' => 'en',
                    'label' => 'English',
                    'hreflang_code' => 'en',
                    'google_translation_code' => 'en',
                    'microsoft_translation_code' => 'en',
                    'deepl_translation_code' => 'en'
                ]
            ];
            if (!in_array($lng_source, ['de', 'en'])) {
                $languages[] = $this->gtbabel->settings->getLanguageDataForCode($lng_source) ?? [
                    'code' => $lng_source,
                    'label' => $lng_source,
                    'hreflang_code' => $lng_source,
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
                    'translate_json_include' => [
                        '?wc-ajax=*' => ['fragments.*', 'messages', 'redirect'], // woocommerce
                        'wp-json' => ['message'] // contact form 7
                    ],
                    'translate_wp_localize_script_include' => ['wc_*.locale.*', 'wc_*.i18n_*', 'wc_*.cart_url'], // woocommerce
                    'exclude_urls_content' => [
                        'wp-admin',
                        'feed',
                        'embed',
                        'wp-login.php',
                        'wp-register.php',
                        'wp-cron.php',
                        'wp-comments-post.php'
                    ],
                    'exclude_urls_slugs' => ['wp-json'],
                    'translate_html_exclude' => [
                        ['selector' => '.notranslate'],
                        ['selector' => '[data-context]', 'attribute' => 'data-context'],
                        ['selector' => '.lngpicker'],
                        ['selector' => '.xdebug-error'],
                        ['selector' => '#wpadminbar'],
                        ['selector' => '#comments .comment-content'],
                        ['selector' => '/html/body//address/br/parent::address'],
                        ['selector' => '.woocommerce-order-overview__email']
                    ]
                ])
            );
        }
    }

    private function initUpdateCapabilities()
    {
        // this is run on every page load in backend
        // running it only on plugin activation / deactivation is problematic (due to changes of lngs etc)
        // load time is irrelevant (0.0001s)
        add_action('admin_init', function () {
            $roles = get_editable_roles();
            $caps = array_keys($this->getAvailableCapabilities());
            foreach ($roles as $roles__key => $roles__value) {
                $roles_value_capabilities = array_filter(array_keys($roles__value['capabilities']), function ($a) {
                    return strpos($a, 'gtbabel__') === 0;
                });
                // remove caps, that are not existing anymore
                foreach ($roles_value_capabilities as $roles_value_capabilities__value) {
                    if (!in_array($roles_value_capabilities__value, $caps)) {
                        get_role($roles__key)->remove_cap($roles_value_capabilities__value);
                    }
                }
                // add all caps to admin role (so they are visible in plugins like User Role Editor)
                if ($roles__key === 'administrator') {
                    foreach (array_diff($caps, array_keys($roles__value['capabilities'])) as $caps__value) {
                        if ($caps__value === 'gtbabel__email_notifications') {
                            continue;
                        }
                        get_role($roles__key)->add_cap($caps__value);
                    }
                }
            }
        });
    }

    private function getAvailableCapabilities()
    {
        $caps = [];
        $caps['gtbabel__edit_settings'] = __('Edit settings', 'gtbabel-plugin');
        $caps['gtbabel__translation_list'] = __('Use translation list', 'gtbabel-plugin');
        $caps['gtbabel__translation_assistant'] = __('Use translation assistant', 'gtbabel-plugin');
        $caps['gtbabel__translation_frontendeditor'] = __('Use translation frontend editor', 'gtbabel-plugin');
        foreach ($this->gtbabel->settings->getSelectedLanguageCodesLabels() as $languages__key => $languages__value) {
            $caps['gtbabel__translate_' . $languages__key] = sprintf(
                __('Translate language %s', 'gtbabel-plugin'),
                '<strong>' . $languages__value . '</strong>'
            );
        }
        $caps['gtbabel__email_notifications'] = __('Receive email notifications', 'gtbabel-plugin');
        return $caps;
    }

    private function sendMailNotificationsSetupCron()
    {
        add_action('init', function () {
            $task = 'gtbabel_mail_notifications';
            $scheduled = wp_next_scheduled($task);
            $frequency = $this->gtbabel->settings->get('wp_mail_notifications');
            if ($scheduled === false && $frequency === false) {
                return;
            }
            // actual function
            add_action($task, function () {
                $this->sendMailNotificationsRun();
            });
            // on plugin disable
            register_deactivation_hook(__FILE__, function () {
                wp_clear_scheduled_hook($task);
            });
            // deregister (if settings changed)
            if ($scheduled !== false && $scheduled !== $frequency) {
                wp_unschedule_event($scheduled, $task);
            }
            // register
            if ($frequency !== false && !wp_next_scheduled($task)) {
                wp_schedule_event(strtotime(date('Y-m-d H:00:00', strtotime('now + 1 hour'))), $frequency, $task);
            }
        });
    }

    private function sendMailNotificationsRun()
    {
        $users = get_users();
        if (!empty($users)) {
            foreach ($users as $users__value) {
                if (!$users__value->has_cap('gtbabel__email_notifications')) {
                    continue;
                }
                // dynamically set users backend language
                switch_to_locale(get_user_locale($users__value->ID));
                // determine timestamp after which strings should be looked up
                $gtbabel__email_notifications_discovered_last_time = get_user_meta(
                    $users__value->ID,
                    'gtbabel__email_notifications_discovered_last_time',
                    true
                );
                if ($gtbabel__email_notifications_discovered_last_time != '') {
                    $discovered_last_time = $gtbabel__email_notifications_discovered_last_time;
                } else {
                    $discovered_last_time = null;
                }
                update_user_meta(
                    $users__value->ID,
                    'gtbabel__email_notifications_discovered_last_time',
                    date('Y-m-d H:i:s', strtotime('now'))
                );
                // true if mail should be send
                $match = false;
                // build body
                $body = '';
                $body .= '<p>';
                $body .= sprintf(__('Hi %s!', 'gtbabel-plugin'), $users__value->display_name);
                $body .= '</p>';
                $body .= '<p>';
                $body .= __('There are new unchecked translations available:', 'gtbabel-plugin');
                $body .= '</p>';
                $languages = $this->gtbabel->settings->getSelectedLanguageCodesLabels();
                if (!empty($languages)) {
                    $body .= '<ul>';
                    foreach ($languages as $languages__key => $languages__value) {
                        if (!$users__value->has_cap('gtbabel__translate_' . $languages__key)) {
                            continue;
                        }
                        $count = $this->gtbabel->data->getTranslationCountFromDatabase(
                            $languages__key,
                            false,
                            $discovered_last_time
                        );
                        if ($count > 0) {
                            $match = true;
                            $body .= '<li>';
                            $body .= $languages__value . ': ' . $count . PHP_EOL;
                            $body .= '</li>';
                        }
                    }
                    $body .= '</ul>';
                }
                $body .= '<p>';
                $body .= __('Please check and revise these translations.', 'gtbabel-plugin');
                $body .= '</p>';
                $body .= '<p>';
                $body .=
                    '<a href="' .
                    admin_url('admin.php?page=gtbabel-transwizard') .
                    '">' .
                    __('Open translation wizard', 'gtbabel-plugin') .
                    '</a>';
                $body .= '</p>';
                if ($match === true) {
                    wp_mail(
                        $users__value->user_email,
                        __('Unchecked translations available', 'gtbabel-plugin'),
                        $body,
                        ['Content-Type: text/html; charset=UTF-8']
                    );
                }
            }
        }
    }

    private function checkToken()
    {
        // store
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['save_token']) && @$_POST['token'] != '') {
                check_admin_referer('gtbabel-token');
                update_option('gtbabel_token', sanitize_text_field($_POST['token']));
            }
        }

        // weak check
        if (get_option('gtbabel_token') === base64_decode('bmltcm9k')) {
            return true;
        }

        // input
        echo '<div class="gtbabel gtbabel--token wrap">';
        echo '<form class="gtbabel__form" method="post">';
        wp_nonce_field('gtbabel-token');
        echo '<input type="password" name="token" class="gtbabel__input" value="" placeholder="' .
            __('Developer token', 'gtbabel-plugin') .
            '" />';
        echo '<input class="gtbabel__submit button button-primary" name="save_token" value="' .
            __('Save', 'gtbabel-plugin') .
            '" type="submit" />';

        echo '</form>';
        echo '</div>';
        die();
    }
}

class gtbabel_lngpicker_widget extends \WP_Widget
{
    function __construct()
    {
        parent::__construct('gtbabel_lngpicker_widget', __('Gtbabel language picker', 'gtbabel-plugin'), [
            'description' => __('The language picker of Gtbabel.', 'gtbabel-plugin')
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

class gtbabel_localize_script extends \WP_Scripts
{
    function localize($handle, $object_name, $l10n)
    {
        global $gtbabel;
        if ($gtbabel !== null) {
            $localize = $gtbabel->settings->get('translate_wp_localize_script_include');
            foreach ($localize as $localize__value) {
                if (
                    $localize__value === '*' ||
                    preg_match(
                        '/' . str_replace('\*', '.*', preg_quote(explode('.', $localize__value)[0])) . '/',
                        $object_name
                    )
                ) {
                    $l10n = __::array_map_deep($l10n, function ($value, $key, $key_chain) use (
                        $object_name,
                        $localize__value,
                        $gtbabel
                    ) {
                        $path = $object_name . '.' . implode('.', $key_chain);
                        if (
                            $localize__value === '*' ||
                            preg_match('/' . str_replace('\*', '.*', preg_quote($localize__value)) . '/', $path)
                        ) {
                            if (is_string($value)) {
                                $value = $gtbabel->translate($value);
                            }
                        }
                        return $value;
                    });
                }
            }
        }
        return parent::localize($handle, $object_name, $l10n);
    }
}

// this object is passed by reference, therefore globally available (e.g. in helpers.php)
$gtbabel = new Gtbabel();
new GtbabelWordPress($gtbabel);
