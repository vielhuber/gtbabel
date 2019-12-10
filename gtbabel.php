<?php
/**
 * Plugin Name:  gtbabel
 * Plugin URI: https://github.com/vielhuber/gtbabel
 * Description: Instant server-side translation of any page.
 * Version: 1.0
 * Author: David Vielhuber
 * Author URI: https://vielhuber.de
 * License: free
 */

require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\gtbabel;
use Dotenv\Dotenv;

add_action('after_setup_theme', function () {
    /* router */
    if ($_SERVER['REQUEST_URI'] === '/rotze/') {
        $_SERVER['REQUEST_URI'] = '/sample-page/';
    }
    /* begin */
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY'));
    define('LNG_FOLDER', $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/gtbabel/locales');
    define('LNG_SOURCE', 'DE');
    define('LNG_TARGET', 'EN');
    define('PREFIX_DEFAULT_LANG', false);
    define('LANGUAGES', ['DE', 'EN', 'FR']);
    ob_start();
});

add_action(
    'shutdown',
    function () {
        /* end */
        $html = ob_get_contents();
        $gtbabel = new gtbabel();
        $html = $gtbabel->translate($html);
        ob_end_clean();
        echo $html;
    },
    0
);
