# 🦜 Gtbabel 🦜

Gtbabel automatically translates your html/php pages – server sided!

# WORK IN PROGRESS /// WARNING: THIS IS EXPERIMENTAL

## Basic idea

-   Extracts on every page load any page into logical paragraph tokens.
-   Static and dynamic content is deliberately treated the same.
-   All tokens are replaced (if available) by it's translation before rendered.
-   The tokens get dumped (if not available) into gettext, where they can be translated.

## Features

-   Lightweight: only ~1500 lines of code
-   Embraces the power of [gettext](https://www.gnu.org/software/gettext/) (e.g. using contexts and template files)
-   Framework agnostic: works with nearly any php based cms or static site
-   Fast: once all translations are available, Gtbabel reaches a throughput of ~4000 words/s
-   Auto translation: use the power of [Google Translation API](https://cloud.google.com/translate/docs) or [Microsoft Azure Translation API](https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api/) to auto translate your pages and links into any language
-   Router included: spoof your request uri and let the magic happen, links are automatically converted
-   Helper functions for current language and all languages available
-   Basic seo considered: title tags, seo descriptions, html lang attribute, hreflang tags included
-   WordPress plugin available: configure your settings easily in your wp backend
-   Works seamlessly with caching/preloading plugins
-   PHPUnit e2e tests available

## Installation

Install once with [composer](https://getcomposer.org/):

```
composer require vielhuber/gtbabel
```

Then add this to your files:

```php
require __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\Gtbabel;
```

## Usage

```php
$gtbabel = new Gtbabel();

$gtbabel->start();

// start with advanced settings
$gtbabel->start([
    'languages' => ['de', 'en', 'fr'],
    'lng_source' => 'de',
    'lng_target' => 'en',
    'lng_folder' => '/locales',
    'prefix_source_lng' => false,
    'translate_text_nodes' => true,
    'translate_default_tag_nodes' => true,
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true,
    'debug_translations' => false, // surround outputted translations with "%|%" to see borders
    'auto_add_translations_to_gettext' => true,
    'auto_translation' => true,
    'auto_translation_service' => 'google', // google|microsoft
    'google_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'microsoft_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'exclude_urls' => ['/backend'],
    'exclude_dom' => ['.lngpicker'],
    'include_dom' => [
        [
            'selector' => '.search-submit',
            'attribute' => 'value'
        ],
        [
            'selector' => '.js-link',
            'attribute' => 'alt-href',
            'context' => 'slug'
        ]
    ]
]);

// any static or dynamic content
require_once 'template.html';

$gtbabel->stop();
```

## WordPress plugin

[de.wordpress.org/plugins/gtbabel](https://de.wordpress.org/plugins/gtbabel/)

## Gettext

-   The final html gets parsed and is split up in reasonable strings.
-   All new strings are appended to a template pot-file
-   If you disable auto translation:
    -   The strings in the pot-file can be translated by hand (just put your mo-files inside the locales folder)
-   If you enable auto translation:
    -   Po- and mo-files are automatically generated (and loaded on the next request)
    -   New strings are automatically appended
    -   You can modify these auto generated files afterwards, modified strings won't be overwritten

## JavaScript

Gtbabel itself is based on php and only works for static pages or pages rendered via php.\
The whole idea can be relatively easy implemented in js frameworks like Vue or React.\
To get translations in javascript from your php application, use ideas like [this](https://gist.github.com/vielhuber/f6294c31c3259fe2572dc557f584704f) or if inside WordPress [this](https://codex.wordpress.org/Function_Reference/wp_localize_script).

## Modified nodes

By default Gtbabel translates all text nodes and the following tag nodes:

-   `<a>` (attribute: href)
-   `<form>` (attribute: action)
-   `<img>` (attribute: alt)
-   `<input>` (attribute: placeholder)
-   `<title>`
-   `<meta name="description">` (attribute: content)

To disable text/tag node transformation, provide the options:

-   `'translate_text_nodes' => false`
-   `'translate_default_tag_nodes' => false`

You can add your own tag node transformations via the `include` option.

## Router

The router automatically modifies the `$_SERVER['REQUEST_URI']` variable to catch translated urls.\
Unknown translations of urls are picked up from the current url and from links that are on the page.

## Helper functions

```php
gtbabel_current_lng() // 'en'
gtbabel_languages() // ['de','en']
gtbabel_default_languages() // ['de','en','fr','af','am','ar','az','be','bg','bn','bs','ca','ceb','co','cs','cy','da','el','eo','es','et','eu','fa','fi','fy','ga','gd','gl','gu','ha','haw','he','hi','hmn','hr','ht','hu','hy','id','ig','is','it','ja','jw','ka','kk','km','kn','ko','ku','ky','la','lb','lo','lt','lv','mg','mi','mk','ml','mn','mr','ms','mt','my','ne','nl','no','ny','pa','pl','ps','pt','ro','ru','sd','si','sk','sl','sm','sn','so','sq','sr','st','su','sv','sw','ta','te','tg','th','tl','tr','uk','ur','uz','vi','xh','yi','yo','zh-cn','zh-tw','zu']
gtbabel_default_settings() // ['languages' => ['de', 'en', 'fr'], 'lng_folder' => '/locales', ...]
gtbabel_default_settings(['lng_folder' => '/foo']) // ['languages' => ['de', 'en', 'fr'], 'lng_folder' => '/foo', ...]
gtbabel_languagepicker() // [['lng' => 'de', 'url' => 'https://tld.com/de/nudel', 'active' => true], ['lng' => 'en', 'url' => 'https://tld.com/en/noodle', 'active' => false], ...]
gtbabel__('Hallo') // 'Hi there'
gtbabel__('Hi there', 'de', 'en') // 'Hello'
```

## Note on gtbabel\_\_()

Don't use `gtbabel__()` for echoing strings, just output them in the source language. Gtbabel will take care of the rest.

## Language codes

It is recommended to use iso language codes in lowercase. But you can use any language code you want (even i-klingon).\
If you use the auto translation feature for example with the help of the Google Transpation API, you must use iso language codes.

## Credits

Greetings to my coworker, without whom this would not have been possible.
