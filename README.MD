[![Build Status](https://travis-ci.org/vielhuber/gtbabel.svg?branch=master)](https://travis-ci.org/vielhuber/gtbabel)

# 🦜 Gtbabel 🦜

Gtbabel automatically translates your HTML/PHP pages – server sided!

## Basic idea

-   Gtbabel extracts on every page load any page into logical paragraph tokens.
-   Static and dynamic content is deliberately treated the same.
-   All tokens are replaced (if available) by it's translation before rendered.
-   The tokens get dumped (if not available) into gettext, where they can be translated.

## Features

-   Lightweight: only ~2500 lines of code.
-   Embraces the power of [gettext](https://www.gnu.org/software/gettext/) (e.g. using contexts and template files).
-   Framework agnostic: works with nearly any PHP based cms or static site.
-   Fast: once all translations are available, Gtbabel reaches a throughput of ~4000 words/s.
-   Auto translation: use the power of [Google Translation API](https://cloud.google.com/translate/docs) or [Microsoft Translation API](https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api/) to auto translate your pages and links into any language.
-   Router included: spoof your request uri and let the magic happen, links are automatically converted (with slug collission detection).
-   Helper functions for current language and all languages available.
-   Basic seo considered: title tags, seo descriptions, html lang attribute, hreflang tags included.
-   RTL support: Adds a html dir attribute if language is rtl
-   WordPress plugin available.
-   Prepared for translation management.
-   Works seamlessly with caching/preloading plugins.
-   Besides plain html does also handle and translate xml (like in dynamically generated sitemaps) and json (like in ajax responses).
-   PHPUnit e2e tests available.

## Requirements

-   PHP >=7.2

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
    'lng_target' => null, // this gets automatically determined by the current url
    'lng_folder' => '/locales',
    'log_folder' => '/logs',
    'prefix_source_lng' => true,
    'redirect_root_domain' => 'browser', // 'browser'|'source' (only relevant if prefix_source_lng=true)
    'translate_text_nodes' => true,
    'translate_default_tag_nodes' => true,
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true,
    'debug_translations' => false, // surround outputted translations with "%|%" to see borders
    'auto_add_translations_to_gettext' => true,
    'auto_add_added_date_to_gettext' => true,
    'auto_set_discovered_strings_checked' => false,
    'only_show_checked_strings' => true,
    'auto_translation' => true,
    'auto_translation_service' => 'google', // google|microsoft
    'google_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // you can also use an array here to provide multiple keys at once
    'microsoft_translation_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', // you can also use an array here to provide multiple keys at once
    'stats_log' => true,
    'discovery_log' => false,
    'prevent_publish' => true,
    'prevent_publish_urls' => [
        '/path/in/source/lng/to/specific/page' => ['en', 'fr']
        // you can also use wildcards here
        '/slug1/*' => ['en'],
        '/slug1/*/slug2' => ['fr'],
         // disable whole languages
        '/*' => ['en','fr']
    ],
    'exclude_urls' => ['/backend'],
    'exclude_dom' => ['.notranslate', '.lngpicker'],
    'force_tokenize' => ['.force-tokenize'],
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

## Other usage

Gtbabel catches the content between `start()` and `stop()` with [output buffering](https://www.php.net/manual/de/function.ob-start.php).\
However, you can also use Gtbabel more directly:

```php
$gtbabel = new Gtbabel();

$gtbabel->translate('<p>Dies ist ein Test!</p>', [
    'lng_source' => 'de',
    'lng_target' => 'en',
    'google_translation_api_key' => '...'
]); // <p>This is a test!</p>

$gtbabel->tokenize('<p>Dies ist ein Test!</p>'); // [['string' => 'This is a test!', 'context' => null]]
```

## WordPress plugin

[wordpress.org/plugins/gtbabel](https://wordpress.org/plugins/gtbabel/)

You don't have to change any code in the frontend at all: If you already have functions like `__()` in your code, just leave them (since WordPress internally only knows about the source language); if not, don't add them. Gtbabel acts on the output (like on any other page).

The following features are included:

-   Configuration gui
-   Easy setup wizard
-   Auto translation (including sitemap parser)
-   Delete unused strings
-   Detect shared strings
-   Manual string translation (search and filter by url)
-   Translation services
-   Support for plugins like [Contact Form 7](https://de.wordpress.org/plugins/contact-form-7/)
-   Translate specific posts/pages or any other url
-   Control publish status of posts/pages (slug changes are auto-reflected in the `prevent_publish_urls`-option)

## Gettext

-   The final html gets parsed and is split up in reasonable strings.
-   All new strings are appended to a template pot-file.
-   If you disable auto translation:
    -   The strings in the pot-file can be translated by hand (just put your mo-files inside the locales folder).
-   If you enable auto translation:
    -   Po- and mo-files are automatically generated (and loaded on the next request).
    -   New strings are automatically appended.
    -   You can modify these auto generated files afterwards, modified strings won't be overwritten.

## JavaScript

Gtbabel itself is based on PHP and only works for static pages or pages rendered via PHP.\
The whole idea can be relatively easy implemented in js frameworks like Vue or React.\
To get translations in JavaScript from your PHP application, Gtbabel provides a neat helper function to hotload your translations in the header that works in every environment. Execute the following function before you include your js files:

```php
gtbabel_localize_js(['string', ['string', 'context']]);
```

Now you can access those strings easily inside JavaScript with:

```js
gtbabel__('string');
gtbabel__('string', 'context');
```

Also first check, if Gtbabel is available in JavaScript with `if (typeof gtbabel__ === 'function') { }`.

In WordPress environments Gtbabel gives you a backend interface to handle the strings more conveniently.\
Instead you can also can use [wp_localize_script](https://codex.wordpress.org/Function_Reference/wp_localize_script) (in conjunction with `gtbabel__`).

## Modified nodes

By default Gtbabel translates all text and tag nodes in the following contexts:

| selector                   | attribute     | context       |
| -------------------------- | ------------- | ------------- |
| `a`                        | `href`        | `slug`        |
| `form`                     | `action`      | `slug`        |
| `img`                      | `alt`         | ``            |
| `*`                        | `title`       | ``            |
| `input`                    | `placeholder` | ``            |
| `head title`               | `` | `title`  |
| `meta[name="description"]` | `content`     | `description` |

To disable text/tag node transformation, provide the options:

-   `'translate_text_nodes' => false`
-   `'translate_default_tag_nodes' => false`

You can add your own tag node transformations via the `include_dom` option.

Gtbabel automatically groups together reasonable parts.\
The following code gets converted to 1 token (not 3):

`<p>This is a <a href="#">link</a> inside a text.</p>`

If you want to influence that behaviour, use the `force_tokenize` option and provide the selector of the parent element in order to not tokenize its children.

If you want to influence that special tags should not be auto translated by the Google/Microsoft Translation API, use a special class:

`<p>Das ist das <span class="notranslate">Haus</span> vom Nikolaus</p>`

The part before and after the `span`-tag gets translated (and added to gettext).\
If you want to exclude a complete node of being translated (and not added to gettext), add a class with the value `notranslate` (or any other value defined in `exclude_dom`) to the parent node:

`<p class="notranslate">Das ist das <span>Haus</span> vom Nikolaus</p>`

Note that attributes of ignored nodes are also not translated (the `href`-attribute and the text `Link` does not get translated):

`<a href="/home" class="notranslate">Link</a>`

However, if you want the `href`-attribute to be modified, do something like

`<a href="/home"><span class="notranslate">Link</span></a>`

## HTML in gettext

Gtbabel preserves inline HTML-tags and leaves them inside your gettext strings.\
However, attributes are automatically stripped. So

`<a href="https://tld.com" class="foo" data-bar="baz">Hallo</a> Welt!`

gets converted to

`<a>Hallo</a> Welt!`

and that string is e.g. stored in a translation as

`<a>Hello</a> world!`

so that your translators are not confused with unnecessary clutter.\
The translated version has of course the attributes back again:

`<a href="https://tld.com" class="foo" data-bar="baz">Hello</a> world!`

However, if ordering is crucial, Gtbabel provides a mechanism to determine order (and duplication).\
So for example

`Das deutsche <a href="https://1.com">Brot</a> <a href="https://2.com">vermisse</a> ich am meisten.`

is stored in your gettext files as

`Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.`

If your translations don't reflect the original order, in gettext they include hints like

`I <a p="2">miss</a> German <a p="1">bread</a> the most.`

where "2" stands for the second tag of the original string.

The string then finally gets correctly translated to

`I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.`

## Router

The router automatically modifies the `$_SERVER['REQUEST_URI']` variable to catch translated urls.\
Unknown translations of urls are picked up from the current url and from links that are on the page.\
These urls are automatically added to gettext (ajax requested urls are excluded).

## JSON Responses

JSON responses are also automatically parsed by Gtbabel.\
Currently only string values with the key `message` are automatically translated.

## Translation management

If `only_show_checked_strings` is set to `true`, only translations with an [extracted comment](https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html) of `#. checked` are shown. This way you can build an approval system in your backend, where translators can check strings before they are published.

## Flow diagram

![Flow diagram](gtbabel.drawio.svg)

## Helper functions

Always surround these helper functions with `if( function_exists('...') ) { }`.

```php
gtbabel_current_lng() // 'en'
gtbabel_languages() // ['de','en']
gtbabel_default_language_codes() // ['de','en','fr','af','am','ar','az','be','bg','bn','bs','ca','ceb','co','cs','cy','da','el','eo','es','et','eu','fa','fi','ga','gd','gl','gu','ha','haw','he','hi','hmn','hr','ht','hu','hy','id','ig','is','it','ja','jv','ka','kk','km','kn','ko','ku','ky','la','lb','lo','lt','lv','mg','mi','mk','ml','mn','mr','ms','mt','my','ne','nl','no','ny','pa','pl','ps','pt','ro','ru','sd','si','sk','sl','sm','sn','so','sq','sr','su','sv','ta','te','tg','th','tr','uk','ur','uz','vi','xh','yi','yo','zh-cn','zh-tw','zu']
gtbabel_default_languages() // ['de' => 'Deutsch', 'en' => 'English', ...]
gtbabel_language_label('en') // 'English'
gtbabel_default_settings() // ['languages' => ['de', 'en', 'fr'], 'lng_folder' => '/locales', ...]
gtbabel_default_settings(['lng_folder' => '/foo']) // ['languages' => ['de', 'en', 'fr'], 'lng_folder' => '/foo', ...]
gtbabel_languagepicker() // [['code' => 'de', 'label' => 'Deutsch', 'url' => 'https://tld.com/de/nudel', 'active' => true], ['code' => 'en', 'label' => 'English', 'url' => 'https://tld.com/en/noodle', 'active' => false], ...]
gtbabel_string_check(true, 'Hello World', 'en', 'title');
gtbabel_localize_js(['string', ['string', 'context']]) // hotload translations inside your javascript files
gtbabel__('Hallo') // 'Hi there'
gtbabel__('blog', 'slug') // 'blog-en'
gtbabel__('Hi there', null, 'de', 'en') // 'Hello'
```

## Note on gtbabel\_\_()

Don't use `gtbabel__()` for echoing strings, just output them in the source language. Gtbabel will take care of the rest.

## Language codes

It is recommended to use iso language codes in lowercase. But you can use any language code you want (even i-klingon).\
If you use the auto translation feature for example with the help of the Google Transpation API, you must use iso language codes.

## Google Translation API

-   Go to [Google API Console](https://console.cloud.google.com/apis)
-   Create a new project
-   Marketplace > Enable "Cloud Translation API" (this requires you to setup a billing account)
-   APIs and services > API credentials > Add a new api key

## API usage costs

The translation apis of Google or Microsoft can be costly. Try to keep track of your current usage stats. Gtbabel helps you by tracking the total amount of translated chars (see the `stats_log` option). You can also provide multiple api keys, Gtbabel then distributes the calls uniformly.

## Caveats

-   The source language acts as a base for every other language. It's content must be always present (one cannot publish content only in a different language.
-   Gtbabel is good at translating whole pages. If you want to provide different content for different languages, Gtbabel might be the wrong tool (however you _can_ use `if( gtbabel_current_lng() === 'other-lng' ) { echo 'content in source language'; }` in your templates to achieve that).
-   Gtbabel is designed to translate every part of the page without code changes (however you can exclude translations with the `exclude_dom`-option). It is not designed for mainly translating only certain parts of the page.

## Credits

Greetings to my coworker, without whom this would not have been possible.
