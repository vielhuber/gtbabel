<?php
require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\Gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$gtbabel = new Gtbabel();

$gtbabel->start([
    'lng_target' => 'en',
    'prefix_source_lng' => false,
    'redirect_root_domain' => 'browser',
    'debug_translations' => true,
    'auto_add_translations_to_gettext' => false,
    'auto_add_added_date_to_gettext' => true,
    'auto_set_discovered_strings_checked' => false,
    'only_show_checked_strings' => true,
    'auto_translation' => true,
    'auto_translation_service' => 'google',
    'google_translation_api_key' => getenv('GOOGLE_TRANSLATION_API_KEY'),
    'microsoft_translation_api_key' => getenv('MICROSOFT_TRANSLATION_API_KEY'),
    'exclude_urls' => null,
    'languages' => ['de', 'en', 'fr'],
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true
]);

$gtbabel->reset();

require_once 'tests/files/in/2.html';

$gtbabel->stop();
