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
    'debug_mode' => true,
    'auto_translation' => false,
    'auto_translation_service' => 'google',
    'google_translation_api_key' => getenv('GOOGLE_TRANSLATION_API_KEY'),
    'microsoft_translation_api_key' => getenv('MICROSOFT_TRANSLATION_API_KEY'),
    'exclude_urls' => null,
    'languages' => ['de', 'en', 'fr'],
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true
]);

$gtbabel->reset();

require_once 'tests/files/in/20.html';

$gtbabel->stop();
