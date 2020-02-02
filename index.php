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
    'debug_mode' => false,
    'auto_translation' => true,
    'auto_translation_service' => 'google',
    'google_translation_api_key' => getenv('GOOGLE_TRANSLATION_API_KEY'),
    'microsoft_translation_api_key' => getenv('MICROSOFT_TRANSLATION_API_KEY'),
    'exclude_urls' => null,
    'html_lang_attribute' => false,
    'html_hreflang_tags' => false
]);

$gtbabel->reset();

require_once 'demo/simple/1.html';

$gtbabel->stop();
