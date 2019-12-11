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
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$gtbabel = new gtbabel();

add_action('after_setup_theme', function () use ($gtbabel) {
    $gtbabel->start([
        'GOOGLE_API_KEY' => getenv('GOOGLE_API_KEY'),
        'LNG_FOLDER' => 'wp-content/plugins/gtbabel/locales',
        'LNG_SOURCE' => 'DE',
        'LNG_TARGET' => null, // auto
        'PREFIX_DEFAULT_LANG' => false,
        'LANGUAGES' => ['DE', 'EN', 'FR']
    ]);
});

add_action(
    'shutdown',
    function () use ($gtbabel) {
        $gtbabel->stop();
    },
    0
);
