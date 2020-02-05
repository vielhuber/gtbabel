<?php
/**
 * Plugin Name: Gtbabel
 * Plugin URI: https://github.com/vielhuber/gtbabel
 * Description: Instant server-side translation of any page.
 * Version: 1.1.2
 * Author: David Vielhuber
 * Author URI: https://vielhuber.de
 * License: free
 */
namespace GtbabelWordPress;

if (file_exists(__DIR__ . '/vendor/scoper-autoload.php')) {
    require_once __DIR__ . '/vendor/scoper-autoload.php';
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}
use vielhuber\gtbabel\Gtbabel;

class GtbabelWordPress
{
    private $gtbabel;

    public function __construct($gtbabel)
    {
        $this->gtbabel = $gtbabel;
        $this->installHook();
        $this->uninstallHook();
        $this->localize();
        $this->initBackend();
        $this->disableAutoRedirect();
        $this->setDefaultSettingsToOption();
        $this->startHook();
        $this->stopHook();
    }

    private function disableAutoRedirect()
    {
        remove_action('template_redirect', 'redirect_canonical');
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
        $this->gtbabel->start(get_option('gtbabel_settings'));
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

    private function uninstallHook()
    {
        register_uninstall_hook(__FILE__, 'gtbabel_uninstall');
    }

    private function localize()
    {
        add_action('plugins_loaded', function () {
            $jo = load_plugin_textdomain('gtbabel-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
        });
    }

    private function setDefaultSettingsToOption()
    {
        if (get_option('gtbabel_settings') === false || get_option('gtbabel_settings') == '') {
            $lng_source = mb_strtolower(substr(get_locale(), 0, 2));
            $languages = ['de', 'en'];
            if (!in_array($lng_source, $languages)) {
                $languages[] = $lng_source;
            }
            delete_option('gtbabel_settings'); // this is needed, because sometimes the option exists (with the value '')
            add_option(
                'gtbabel_settings',
                gtbabel_default_settings([
                    'languages' => $languages,
                    'lng_source' => $lng_source,
                    'lng_folder' => '/wp-content/plugins/gtbabel/locales',
                    'exclude_urls' => ['/wp-admin', 'wp-login.php', 'wp-cron.php', 'wp-comments-post.php']
                ])
            );
        }
    }

    private function initBackend()
    {
        add_action('admin_menu', function () {
            $menu = add_menu_page(
                'Gtbabel',
                'Gtbabel',
                'manage_options',
                'gtbabel',
                function () {
                    $message = '';
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        if (isset($_POST['save_settings'])) {
                            $settings = @$_POST['gtbabel'];
                            foreach (
                                [
                                    'prefix_source_lng',
                                    'translate_text_nodes',
                                    'translate_default_tag_nodes',
                                    'html_lang_attribute',
                                    'html_hreflang_tags',
                                    'auto_translation'
                                ]
                                as $checkbox__value
                            ) {
                                if (@$settings[$checkbox__value] == '1') {
                                    $settings[$checkbox__value] = true;
                                } else {
                                    $settings[$checkbox__value] = false;
                                }
                            }
                            foreach (['exclude_urls', 'exclude_dom'] as $exclude__value) {
                                $post_data = $settings[$exclude__value];
                                $settings[$exclude__value] = [];
                                if (@$settings[$exclude__value] != '') {
                                    foreach (explode(PHP_EOL, $post_data) as $post_data__value) {
                                        $settings[$exclude__value][] = trim($post_data__value);
                                    }
                                }
                            }
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
                            $settings['languages'] = array_keys($settings['languages']);
                            update_option('gtbabel_settings', $settings);
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

                    echo '<div class="gtbabel wrap">';
                    echo '<form class="gtbabel__form" method="post" action="' .
                        admin_url('admin.php?page=gtbabel') .
                        '">';
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
                    foreach (gtbabel_default_languages() as $languages__value) {
                        echo '<li class="gtbabel__languagelist-item">';
                        echo '<label>';
                        echo '<input class="gtbabel__input gtbabel__input--checkbox" type="checkbox" name="gtbabel[languages][' .
                            $languages__value .
                            ']"' .
                            (in_array($languages__value, $settings['languages']) == '1' ? ' checked="checked"' : '') .
                            ' value="1" />';
                        echo mb_strtoupper($languages__value);
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
                    foreach (gtbabel_default_languages() as $languages__value) {
                        echo '<option value="' .
                            $languages__value .
                            '"' .
                            ($settings['lng_source'] == $languages__value ? ' selected="selected"' : '') .
                            '>' .
                            mb_strtoupper($languages__value) .
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
                    echo '<input class="gtbabel__input" type="text" id="gtbabel_google_translation_api_key" name="gtbabel[google_translation_api_key]" value="' .
                        $settings['google_translation_api_key'] .
                        '" />';
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
                    echo '<input class="gtbabel__input" type="text" id="gtbabel_microsoft_translation_api_key" name="gtbabel[microsoft_translation_api_key]" value="' .
                        $settings['microsoft_translation_api_key'] .
                        '" />';
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
                        echo '<li class="gtbabel__repeater-listitem">';
                        echo '<input class="gtbabel__input" type="text" name="gtbabel[include_dom][selector][]" value="' .
                            $include_dom__value['selector'] .
                            '" />';
                        echo '<input class="gtbabel__input" type="text" name="gtbabel[include_dom][attribute][]" value="' .
                            $include_dom__value['attribute'] .
                            '" />';
                        echo '<input class="gtbabel__input" type="text" name="gtbabel[include_dom][context][]" value="' .
                            $include_dom__value['context'] .
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

                    echo '<h2 class="gtbabel__subtitle">' . __('Reset settings', 'gtbabel-plugin') . '</h2>';
                    echo '<input class="gtbabel__submit button button-secondary" name="reset_settings" value="' .
                        __('Reset', 'gtbabel-plugin') .
                        '" type="submit" />';

                    echo '<h2 class="gtbabel__subtitle">' . __('Reset translations', 'gtbabel-plugin') . '</h2>';
                    echo '<input class="gtbabel__submit button button-secondary" name="reset_translations" value="' .
                        __('Reset', 'gtbabel-plugin') .
                        '" type="submit" />';
                    echo '</form>';
                    echo '</div>';
                },
                'dashicons-admin-site-alt3',
                100
            );
            add_action('admin_print_styles-' . $menu, function () {
                wp_enqueue_style('gtbabel-css', plugins_url('gtbabel.css', __FILE__));
            });
            add_action('admin_print_scripts-' . $menu, function () {
                wp_enqueue_script('gtbabel-js', plugins_url('gtbabel.js', __FILE__));
            });
        });
    }
}

function gtbabel_uninstall()
{
    delete_option('gtbabel_settings');
}

$gtbabel = new Gtbabel();
new GtbabelWordPress($gtbabel);
