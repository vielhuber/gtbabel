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
        'google_api_key' => getenv('GOOGLE_API_KEY'),
        'auto_translation' => false,
        'lng_folder' => '/wp-content/plugins/gtbabel/locales',
        'lng_source' => 'de',
        'lng_target' => null, // auto
        'prefix_source_lng' => false,
        'exclude_urls' => ['/wp-admin', 'wp-login.php', 'wp-cron.php'],
        'exclude_dom' => ['.lngpicker'],
        'include' => [
            [
                'selector' => '.search-submit',
                'attribute' => 'value'
            ],
            [
                'selector' => '.js-link',
                'attribute' => 'alt-href',
                'context' => 'slug'
            ]
        ],
        'languages' => [
            'de',
            'en',
            'fr',
            'af',
            'am',
            'ar',
            'az',
            'be',
            'bg',
            'bn',
            'bs',
            'ca',
            'ceb',
            'co',
            'cs',
            'cy',
            'da',
            'el',
            'eo',
            'es',
            'et',
            'eu',
            'fa',
            'fi',
            'fy',
            'ga',
            'gd',
            'gl',
            'gu',
            'ha',
            'haw',
            'he',
            'hi',
            'hmn',
            'hr',
            'ht',
            'hu',
            'hy',
            'id',
            'ig',
            'is',
            'it',
            'ja',
            'jw',
            'ka',
            'kk',
            'km',
            'kn',
            'ko',
            'ku',
            'ky',
            'la',
            'lb',
            'lo',
            'lt',
            'lv',
            'mg',
            'mi',
            'mk',
            'ml',
            'mn',
            'mr',
            'ms',
            'mt',
            'my',
            'ne',
            'nl',
            'no',
            'ny',
            'pa',
            'pl',
            'ps',
            'pt',
            'ro',
            'ru',
            'sd',
            'si',
            'sk',
            'sl',
            'sm',
            'sn',
            'so',
            'sq',
            'sr',
            'st',
            'su',
            'sv',
            'sw',
            'ta',
            'te',
            'tg',
            'th',
            'tl',
            'tr',
            'uk',
            'ur',
            'uz',
            'vi',
            'xh',
            'yi',
            'yo',
            'zh-cn',
            'zh-tw',
            'zu'
        ]
    ]);
});

add_action(
    'shutdown',
    function () use ($gtbabel) {
        $gtbabel->stop();
    },
    0
);
