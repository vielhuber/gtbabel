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
    'exclude_urls' => null
]);

require_once 'demo/simple/16.html';

$gtbabel->stop();
