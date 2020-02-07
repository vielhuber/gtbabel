# 🦜 Gtbabel 🦜

Gtbabel automatically translates your html/php pages – server sided!

# WORK IN PROGRESS /// WARNING: THIS IS EXPERIMENTAL

## basic idea

-   extracts on every page load any page into logical paragraph tokens.
-   static and dynamic content is deliberately treated the same.
-   all tokens are replaced (if available) by it's translation before rendered.
-   the tokens get dumped (if not available) into gettext, where they can be translated.

## features

-   lightweight: only ~1500 lines of code
-   embraces the power of [gettext](https://www.gnu.org/software/gettext/) (e.g. using contexts and template files)
-   framework agnostic: works with nearly any php based cms or static site
-   fast: once all translations are available, gtbabel reaches a throughput of ~4000 words/s
-   auto translation: use the power of [Google Translation API](https://cloud.google.com/translate/docs) or [Microsoft Azure Translation API](https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api/) to auto translate your pages and links into any language
-   router included: spoof your request uri and let the magic happen, links are automatically converted
-   helper functions for current language and all languages available
-   basic seo considered: title tags, seo descriptions, html lang attribute, hreflang tags included
-   wordpress plugin available: configure your settings easily in your wp backend
-   works seamlessly with caching/preloading plugins
-   phpunit e2e tests available

## installation

install once with [composer](https://getcomposer.org/):

```
composer require vielhuber/gtbabel
```

then add this to your files:

```php
require __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\Gtbabel;
```

## usage

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

## wordpress plugin

[de.wordpress.org/plugins/gtbabel](https://de.wordpress.org/plugins/gtbabel/)

## gettext

-   the final html gets parsed and is split up in reasonable strings.
-   all new strings are appended to a template pot-file
-   if you disable auto translation:
    -   the strings in the pot-file can be translated by hand (just put your mo-files inside the locales folder)
-   if you enable auto translation:
    -   po- and mo-files are automatically generated (and loaded on the next request)
    -   new strings are automatically appended
    -   you can modify these auto generated files afterwards, modified strings won't be overwritten

## javascript

Gtbabel itself is based on php and only works for static pages or pages rendered via php.\
the idea can be relatively easy implemented in js frameworks like Vue or React.

## modified nodes

by default Gtbabel translates all text nodes and the following tag nodes:

-   `<a>` (attribute: href)
-   `<form>` (attribute: action)
-   `<img>` (attribute: alt)
-   `<input>` (attribute: placeholder)
-   `<title>`
-   `<meta name="description">` (attribute: content)

to disable text/tag node transformation, provide the options:

-   `'translate_text_nodes' => false`
-   `'translate_default_tag_nodes' => false`

you can add your own tag node transformations via the `include` option.

## router

the router automatically modifies the `$_SERVER['REQUEST_URI']` variable to catch translated urls.\
unknown translations of urls are picked up from the current url and from links that are on the page.

## helper functions

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

## language codes

it is recommended to use iso language codes in lowercase.
but you can use any language code you want (even i-klingon).
if you use the auto translation feature for example with the help of the Google Transpation API, you must use iso language codes.

## credits

greetings to my coworker, without whom this would not have been possible.
