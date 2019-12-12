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
    'lng_source' => 'DE',
    'lng_target' => 'EN',
    'prefix_default_lang' => false,
    'languages' => ['DE', 'EN', 'FR'],
    'exclude' => null
]);

require_once 'tpl/complex/1.html';

$gtbabel->stop();
