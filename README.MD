# 🦜 gtbabel 🦜

gtbabel automatically translates your html/php pages – server sided!

# WORK IN PROGRESS

# WARNING: this is experimental

## basic idea

-   gtbabel extracts on every page load any page into logical paragraph tokens.
-   static and dynamic content is deliberately treated the same.
-   all tokens are replaced (if available) by it's translation before rendered.
-   the tokens get dumped (if not available) into gettext, where they can be translated.

## features

-   lightweight: only ~500 lines of code
-   embraces the full power of [gettext](https://www.gnu.org/software/gettext/)
-   framework agnostic: works with nearly any php based cms or static sites
-   fast: once all translations are available, gtbabel reaches a throughput of XXX words / second
-   auto translation: use the power of the [Google Translation API](https://cloud.google.com/translate/docs) to auto translate your pages and links into any language
-   router included: spoof your request uri and let the magic happen, links are automatically converted
-   helper functions for current language and all languages available
-   basic seo considered: html lang tag, hreflang tags included
-   wordpress plugin available: configure your settings easily in your wp backend
-   works seamlessly with caching/preloading plugins
-   phpunit tests available

## installation

install once with [composer](https://getcomposer.org/):

```
composer require vielhuber/gtbabel
```

then add this to your files:

```php
require __DIR__ . '/vendor/autoload.php';
use vielhuber\gtbabel\gtbabel;
```

## usage

```php
$gtbabel = new gtbabel();

$gtbabel->start([
    'google_api_key' => getenv('GOOGLE_API_KEY'),
    'auto_translation' => false,
    'lng_folder' => '/locales',
    'lng_source' => 'de',
    'lng_target' => 'en',
    'prefix_source_lng' => false,
    'exclude_urls' => ['/backend'],
    'exclude_dom' => ['.lngpicker'],
    'include' => ['.search-field' => 'value'],
    'languages' => ['de', 'en', 'fr']
]);

// any static or dynamic content
require_once 'template.html';

$gtbabel->stop();
```

## wordpress adapter

[de.wordpress.org/plugins/gtbabel](https://de.wordpress.org/plugins/gtbabel/)

## javascript

gtbabel itself is based on php and only works for static pages or pages rendered via php.\
the idea can be relatively easy implemented in js frameworks like Vue or React.

# router

the router automatically modifies the \$\_SERVER['REQUEST_URI'] variable to catch translated urls.\
unknown translations of urls are picked up from the current url and from links that are on the page.

# helper functions

```php
gtbabel_current_lng() // 'en'
gtbabel_languages() // ['de','en']
gtbabel_languagepicker()
/*
[
    ['lng' => 'de', 'url' => 'https://tld.com/de/nudel', 'active' => true],
    ['lng' => 'en', 'url' => 'https://tld.com/en/noodle', 'active' => false]
]
*/
gtbabel_get_translation('hello', 'fr', 'en') // 'bonjour'
```

# language codes

it is recommended to use iso language codes in lowercase.
but you can use any language code you want (even i-klingon).
if you use the auto translation feature with the help of Google Transpation API, you must use iso language codes.

# credits

shout out to [Steffen Wenzel](https://www.zeile1.com/) to make this work.\
