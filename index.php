<?php
require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$gtbabel = new gtbabel();

$gtbabel->start([
    'google_api_key' => getenv('GOOGLE_API_KEY'),
    'lng_folder' => '/locales',
    'lng_source' => 'de',
    'lng_target' => 'en',
    'prefix_source_lng' => false,
    'exclude' => null,
    'languages' => ['de', 'en', 'fr']
]);

require_once 'demo/complex/7.php';

$gtbabel->stop();
