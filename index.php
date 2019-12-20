<?php
require_once __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$gtbabel = new gtbabel();

$gtbabel->start([
    'google_api_key' => getenv('GOOGLE_API_KEY'),
    'auto_translation' => false,
    'lng_folder' => '/locales',
    'lng_source' => 'de',
    'lng_target' => 'en',
    'prefix_source_lng' => false,
    'translate_text_nodes' => true,
    'translate_default_tag_nodes' => true,
    'exclude_urls' => null,
    'exclude_dom' => ['.lngpicker'],
    'include' => [
        [
            'selector' => '.search-submit',
            'attribute' => 'value'
        ],
        [
            'selector' => '.js-link',
            'attribute' => 'alt-href',
            'context' => 'slug'
        ]
    ],
    'languages' => ['de', 'en', 'fr']
]);

require_once 'demo/simple/1.html';

$gtbabel->stop();
