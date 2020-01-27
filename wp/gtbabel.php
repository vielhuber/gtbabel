<?php
require_once __DIR__ . '/../vendor/autoload.php';
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

    private function installHook()
    {
        register_activation_hook(__FILE__, function () {
            $this->setDefaultSettingsToOption();
        });
    }

    private function uninstallHook()
    {
        register_uninstall_hook(__FILE__, 'gtbabel_uninstall');
        function gtbabel_uninstall()
        {
            delete_option('gtbabel_settings');
        }
    }

    private function localize()
    {
        add_action('plugins_loaded', function () {
            load_plugin_textdomain('gtbabel-plugin', false, 'gtbabel/wp/languages');
        });
    }

    private function setDefaultSettingsToOption()
    {
        if (get_option('gtbabel_settings') === false) {
            add_option(
                'gtbabel_settings',
                gtbabel_default_settings([
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
                    echo '<div class="gtbabel wrap">';
                    echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
                    echo '<h2 class="gtbabel__subtitle">' . __('Settings', 'gtbabel-plugin') . '</h2>';
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        if (isset($_POST['save_settings'])) {
                            $settings = @$_POST['gtbabel'];
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
                            gtbabel_reset();
                            $this->setDefaultSettingsToOption();
                            $this->start();
                        }
                        echo '<div class="gtbabel__notice notice notice-success is-dismissible"><p>' .
                            __('Successfully edited', 'gtbabel-plugin') .
                            '</p></div>';
                    }
                    $settings = get_option('gtbabel_settings');
                    echo '<form class="gtbabel__form" method="post" action="' .
                        admin_url('admin.php?page=gtbabel') .
                        '">';
                    echo '<ul class="gtbabel__fields">';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Languages', 'gtbabel-plugin') . '</span>';
                    foreach (gtbabel_default_languages() as $languages__value) {
                        echo '<label>';
                        echo mb_strtoupper($languages__value);
                        echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[languages][' .
                            $languages__value .
                            ']"' .
                            (in_array($languages__value, $settings['languages']) == '1' ? ' checked="checked"' : '') .
                            ' value="1" />';
                        echo '</label>';
                    }
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Language folder', 'gtbabel-plugin') . '</span>';
                    echo '<input class="gtbabel__input" type="text" name="gtbabel[lng_folder]" value="' .
                        $settings['lng_folder'] .
                        '" />';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Source language', 'gtbabel-plugin') . '</span>';
                    echo '<select class="gtbabel__input" name="gtbabel[lng_source]">';
                    echo '<option value=""></option>';
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

                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' .
                        __('Prefix source language urls', 'gtbabel-plugin') .
                        '</span>';
                    echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[prefix_source_lng]" value="1"' .
                        ($settings['prefix_source_lng'] == '1' ? ' checked="checked"' : '') .
                        ' />';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Translate text nodes', 'gtbabel-plugin') . '</span>';
                    echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[translate_text_nodes]" value="1"' .
                        ($settings['translate_text_nodes'] == '1' ? ' checked="checked"' : '') .
                        ' />';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' .
                        __('Translate additional nodes', 'gtbabel-plugin') .
                        '</span>';
                    echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[translate_default_tag_nodes]" value="1"' .
                        ($settings['translate_default_tag_nodes'] == '1' ? ' checked="checked"' : '') .
                        ' />';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' .
                        __('Enable automatic translation', 'gtbabel-plugin') .
                        '</span>';
                    echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[auto_translation]" value="1"' .
                        ($settings['auto_translation'] == '1' ? ' checked="checked"' : '') .
                        ' />';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Translation service', 'gtbabel-plugin') . '</span>';
                    echo '<select class="gtbabel__input" name="gtbabel[auto_translation_service]">';
                    echo '<option value=""></option>';
                    echo '<option value="google"' .
                        ($settings['auto_translation_service'] == 'google' ? ' selected="selected"' : '') .
                        '>Google</option>';
                    echo '</select>';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' .
                        __('Google Translation API Key', 'gtbabel-plugin') .
                        ' (<a href="https://console.cloud.google.com/apis/library/translate.googleapis.com" target="_blank">' .
                        __('Link', 'gtbabel-plugin') .
                        '</a>)</span>';
                    echo '<input class="gtbabel__input" type="text" name="gtbabel[google_translation_api_key]" value="' .
                        $settings['google_translation_api_key'] .
                        '" />';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Exclude urls', 'gtbabel-plugin') . '</span>';
                    echo '<textarea class="gtbabel__input" name="gtbabel[exclude_urls]">' .
                        implode(PHP_EOL, $settings['exclude_urls']) .
                        '</textarea>';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Exclude dom nodes', 'gtbabel-plugin') . '</span>';
                    echo '<textarea class="gtbabel__input" name="gtbabel[exclude_dom]">' .
                        implode(PHP_EOL, $settings['exclude_dom']) .
                        '</textarea>';
                    echo '</label>';
                    echo '</li>';

                    echo '<li class="gtbabel__field">';
                    echo '<label class="gtbabel__label-wrapper">';
                    echo '<span class="gtbabel__label">' . __('Include dom nodes', 'gtbabel-plugin') . '</span>';

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
                        echo '<a href="#" class="gtbabel__repeater-remove">' . __('Remove', 'gtbabel-plugin') . '</a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                    echo '<a href="#" class="gtbabel__repeater-add">' . __('Add', 'gtbabel-plugin') . '</a>';
                    echo '</div>';

                    echo '</label>';
                    echo '</li>';

                    echo '</ul>';
                    echo '<input class="gtbabel__submit button button-primary" name="save_settings" value="' .
                        __('Save', 'gtbabel-plugin') .
                        '" type="submit" />';

                    echo '<h2 class="gtbabel__subtitle">' . __('Reset settings', 'gtbabel-plugin') . '</h2>';
                    echo '<input class="gtbabel__submit button button-primary" name="reset_settings" value="' .
                        __('Reset', 'gtbabel-plugin') .
                        '" type="submit" />';
                    echo '</form>';
                    echo '</div>';
                },
                'dashicons-admin-site',
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

$gtbabel = new Gtbabel();
new GtbabelWordPress($gtbabel);
