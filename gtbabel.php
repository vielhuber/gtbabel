<?php
/**
 * Plugin Name: Gtbabel
 * Plugin URI: https://github.com/vielhuber/gtbabel
 * Description: Instant server-side translation of any page.
 * Version: 1.0
 * Author: David Vielhuber
 * Author URI: https://vielhuber.de
 * License: free
 */

require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\Gtbabel;

class GtbabelWordPress
{
    private $gtbabel;

    public function __construct($gtbabel)
    {
        $this->gtbabel = $gtbabel;
        $this->installHook();
        $this->uninstallHook();
        $this->initBackend();
        $this->disableAutoRedirect();
        $this->start();
        $this->stop();
    }

    private function disableAutoRedirect()
    {
        remove_action('template_redirect', 'redirect_canonical');
    }

    private function start()
    {
        add_action('after_setup_theme', function () {
            $this->gtbabel->start(get_option('gtbabel_settings'));
        });
    }

    private function stop()
    {
        add_action(
            'shutdown',
            function () {
                $this->gtbabel->stop();
            },
            0
        );
    }

    private function installHook()
    {
        register_activation_hook(__FILE__, function () {
            add_option(
                'gtbabel_settings',
                gtbabel_default_settings([
                    'lng_folder' => '/wp-content/plugins/gtbabel/locales',
                    'exclude_urls' => ['/wp-admin', 'wp-login.php', 'wp-cron.php', 'wp-comments-post.php']
                ])
            );
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

    private function initBackend()
    {
        add_action('admin_menu', function () {
            add_menu_page(
                'Gtbabel',
                'Gtbabel',
                'manage_options',
                'gtbabel',
                function () {
                    ?>
            <style>
                .gtbabel__label-wrapper {
                    display: flex;
                    align-items: flex-start;
                    align-content: flex-start;
                }
                .gtbabel__label {
                    line-height: 2;
                    flex: 0 1 10%;
                }
                .gtbabel__input {
                    flex: 0 1 90%;
                }
            </style>
            <script>
                console.log('OK');
            </script>
            <?php
            echo '<div class="gtbabel wrap">';
            echo '<h1 class="gtbabel__title">🦜 Gtbabel 🦜</h1>';
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
                $settings = @$_POST['gtbabel'];
                $settings['exclude_urls'] = explode(PHP_EOL, $settings['exclude_urls']);
                $settings['exclude_dom'] = explode(PHP_EOL, $settings['exclude_dom']);
                $settings['languages'] = array_keys($settings['languages']);
                update_option('gtbabel_settings', $settings);
                echo '<div class="gtbabel__notice notice notice-success is-dismissible"><p>Erfolgreich editiert</p></div>';
            }
            $settings = get_option('gtbabel_settings');
            echo '<form class="gtbabel__form" method="post" action="' . admin_url('admin.php?page=gtbabel') . '">';
            echo '<ul class="gtbabel__fields">';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Sprachen</span>';
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
            echo '<span class="gtbabel__label">Sprachordner</span>';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[lng_folder]" value="' . $settings['lng_folder'] . '" />';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Quellsprache</span>';
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
            echo '<span class="gtbabel__label">Quellsprachenprefix</span>';
            echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[prefix_source_lng]" value="1"' .
                ($settings['prefix_source_lng'] == '1' ? ' checked="checked"' : '') .
                ' />';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Text übersetzen</span>';
            echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[translate_text_nodes]" value="1"' .
                ($settings['translate_text_nodes'] == '1' ? ' checked="checked"' : '') .
                ' />';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Weitere Elemente übersetzen</span>';
            echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[translate_default_tag_nodes]" value="1"' .
                ($settings['translate_default_tag_nodes'] == '1' ? ' checked="checked"' : '') .
                ' />';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Automatischer Übersetzungsdienst</span>';
            echo '<input class="gtbabel__input" type="checkbox" name="gtbabel[auto_translation]" value="1"' .
                ($settings['auto_translation'] == '1' ? ' checked="checked"' : '') .
                ' />';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Automatische Übersetzung</span>';
            echo '<select class="gtbabel__input" name="gtbabel[auto_translation_service]">';
            echo '<option value=""></option>';
            echo '<option value="google"' . ($settings['auto_translation_service'] == 'google' ? ' selected="selected"' : '') . '>Google</option>';
            echo '</select>';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">Google Translation API Key (<a href="https://console.cloud.google.com/apis/library/translate.googleapis.com" target="_blank">Link</a>)</span>';
            echo '<input class="gtbabel__input" type="text" name="gtbabel[google_translation_api_key]" value="' .
                $settings['google_translation_api_key'] .
                '" />';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">URLs ausschließen</span>';
            echo '<textarea class="gtbabel__input" name="gtbabel[exclude_urls]">' . implode(PHP_EOL, $settings['exclude_urls']) . '</textarea>';
            echo '</label>';
            echo '</li>';

            echo '<li class="gtbabel__field">';
            echo '<label class="gtbabel__label-wrapper">';
            echo '<span class="gtbabel__label">DOM-Elemente ausschließen</span>';
            echo '<textarea class="gtbabel__input" name="gtbabel[exclude_dom]">' . implode(PHP_EOL, $settings['exclude_dom']) . '</textarea>';
            echo '</label>';
            echo '</li>';

            echo '</ul>';
            echo '<input class="gtbabel__submit button button-primary" name="submit" value="Speichern" type="submit" />';
            echo '</form>';
            echo '</div>';
                },
                'dashicons-admin-site',
                100
            );
        });
    }
}

$gtbabel = new Gtbabel();
new GtbabelWordPress($gtbabel);
