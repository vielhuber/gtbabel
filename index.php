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
    'prefix_default_lang' => false,
    'languages' => ['de', 'en', 'fr'],
    'exclude' => null
]);

require_once 'demo/complex/2.php';

$gtbabel->stop();
