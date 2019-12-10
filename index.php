<?php
/*
require_once 'gtbabel.php';
$gtbabel = new gtbabel();
$gtbabel->start([
    'GOOGLE_API_KEY' => getenv('GOOGLE_API_KEY'),
    'LNG_FOLDER' => 'locales',
    'LNG_SOURCE' => 'DE',
    'LNG_TARGET' => 'EN',
    'PREFIX_DEFAULT_LANG' => false,
    'LANGUAGES' => ['DE', 'EN', 'FR']
]);
$gtbabel->stop();
*/

require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\gtbabel;
use Dotenv\Dotenv;

// start
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY'));
define('LNG_FOLDER', 'locales');
define('LNG_SOURCE', 'DE');
define('LNG_TARGET', 'EN');
define('PREFIX_DEFAULT_LANG', false);
define('LANGUAGES', ['DE', 'EN', 'FR']);
ob_start();

// main app
require_once 'tpl/complex/1.html';

// end
$html = ob_get_contents();
$gtbabel = new gtbabel();
$html = $gtbabel->translate($html);
ob_end_clean();
echo $html;
