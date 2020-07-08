<?php
require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\Gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$gtbabel = new Gtbabel();

$gtbabel->start([
    'lng_target' => 'en',
    'prefix_lng_source' => false,
    'redirect_root_domain' => 'browser',
    'debug_translations' => true,
    'auto_add_translations' => false,
    'auto_set_new_strings_checked' => false,
    'auto_set_discovered_strings_checked' => false,
    'only_show_checked_strings' => false,
    'auto_translation' => true,
    'auto_translation_service' => 'google',
    'google_translation_api_key' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
    'microsoft_translation_api_key' => @$_SERVER['MICROSOFT_TRANSLATION_API_KEY'],
    'deepl_translation_api_key' => @$_SERVER['DEEPL_TRANSLATION_API_KEY'],
    'google_throttle_chars_per_month' => 1000000,
    'microsoft_throttle_chars_per_month' => 1000000,
    'deepl_throttle_chars_per_month' => 1000000,
    'exclude_urls' => null,
    'languages' => [
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
    ],
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true
]);

$gtbabel->reset();

require_once 'tests/files/in/2.html';

$gtbabel->stop();
