<?php
require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$gtbabel = new gtbabel();

$gtbabel->start([
    'GOOGLE_API_KEY' => getenv('GOOGLE_API_KEY'),
    'LNG_FOLDER' => 'locales',
    'LNG_SOURCE' => 'DE',
    'LNG_TARGET' => 'EN',
    'PREFIX_DEFAULT_LANG' => false,
    'LANGUAGES' => ['DE', 'EN', 'FR']
]);

require_once 'tpl/complex/1.html';

$gtbabel->stop();
