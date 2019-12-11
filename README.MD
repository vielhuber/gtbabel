# 📗 gtbabel 📗

gtbabel offers a simple translation strategy for html/php page.

...

## basic idea

-   gtbabel extracts any page into logical paragraph tokens.
-   static and dynamic content is deliberately treated the same.
-   these tokens are replaced (if available) by it's translation before rendered.
-   these tokens get dumped (if not available) into gettext, where they can be translated.

## features

-   lightweight: only ~500 lines of code
-   embraces the full power of [gettext](https://www.gnu.org/software/gettext/)
-   framework agnostic: works with nearly any php based cms or static sites
-   fast: once all translations are available, gtbabel reaches a throughput of XXX words / second
-   auto translation: use the power of the [Google Translation API](https://cloud.google.com/translate/docs) to auto translate your pages into any language
-   router included: spoof your request uri and let the magic happen, links are automatically converted
-   helper functions for current language and all languages available
-   basic seo considered: html lang tag, hreflang tags, ... included
-   wordpress plugin included: configure your settings easily in your wp backend

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
    'GOOGLE_API_KEY' => getenv('GOOGLE_API_KEY'),
    'LNG_FOLDER' => 'locales',
    'LNG_SOURCE' => 'DE',
    'LNG_TARGET' => 'EN',
    'PREFIX_DEFAULT_LANG' => false,
    'LANGUAGES' => ['DE', 'EN', 'FR']
]);

// any static or dynamic content
require_once 'template.html';

$gtbabel->stop();
```

## wordpress adapter

[de.wordpress.org/plugins/gtbabel](https://de.wordpress.org/plugins/gtbabel/)

## javascript

gtbabel itself is based on php and only works for static pages or pages rendered via php.
but the idea can be relatively easy ported to js frameworks like Vue or React.
