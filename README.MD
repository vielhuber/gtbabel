[![Build Status](https://travis-ci.com/vielhuber/gtbabel.svg?branch=master)](https://travis-ci.com/vielhuber/gtbabel)

# 🌐 Gtbabel 🌐

Gtbabel automatically translates your HTML/PHP pages – server sided!

## Basic idea

-   Gtbabel extracts on every page load any page into logical paragraph tokens.
-   Static and dynamic content is deliberately treated the same.
-   All tokens are replaced (if available) by it's translation before rendered.
-   The tokens get dumped (if not available), where they can be translated.

## Features

-   Lightweight: only ~3500 lines of code.
-   Framework agnostic: works with nearly any PHP based cms or static site.
-   Fast: once all translations are available, Gtbabel reaches a throughput of ~4000 words/s.
-   Auto translation: use the power of [Google Translation API](https://cloud.google.com/translate/docs), [Microsoft Translation API](https://azure.microsoft.com/de-de/services/cognitive-services/translator-text-api/) or [DeepL](https://www.deepl.com) to auto translate your pages and links into any language.
-   Router included: spoof your request uri and let the magic happen, links are automatically converted (with slug collission detection).
-   Helper functions for current language and all languages available.
-   Basic seo considered: title tags, seo descriptions, html lang attribute, hreflang tags included.
-   RTL support: Adds a html dir attribute if language is rtl.
-   WordPress plugin available.
-   Prepared for translation management.
-   Works seamlessly with caching/preloading plugins.
-   Besides plain html does also handle and translate xml (like in dynamically generated sitemaps) and json (like in ajax responses).
-   PHPUnit e2e tests available.
-   Exports and imports directly to [gettext](https://www.gnu.org/software/gettext/) (using extracted comments and template files).
-   Multiple source languages supported concurrently.
-   DOM change detection: Observe specific page parts and translate dynamic content.
-   Auto throttling api translation for cost control.
-   Custom domain support (path based, subdomain based, top level domain based)

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

// any static or dynamic content
require_once 'template.html';

$gtbabel->stop();
```

## Configuration

If you don't provide an initial configuration, Gtbabel starts with reasonable default settings.\
You also can provide settings directly as an array or pass a path to a json file to overwrite (parts) of the default settings:

```php
$gtbabel = new Gtbabel();

// set settings from php
$gtbabel->config(['languages' => [['code' => 'de', 'label' => 'Deutsch'], ['code' => 'en', 'label' => 'English']], ...]);

// set settings from json
$gtbabel->config('settings.json');
```

The default configuration is:

```php
[
    'languages' => [
        [
            'code' => 'de',
            'label' => 'Deutsch',
            'rtl' => false,
            'hreflang_code' => 'de',
            'google_translation_code' => 'de',
            'microsoft_translation_code' => 'de',
            'deepl_translation_code' => 'de'
        ],
        [
            'code' => 'en',
            'label' => 'English',
            'rtl' => false,
            'hreflang_code' => 'en',
            'google_translation_code' => 'en',
            'microsoft_translation_code' => 'en',
            'deepl_translation_code' => 'en'
        ],
        [
            'code' => 'fr',
            'label' => 'Français',
            'rtl' => false,
            'hreflang_code' => 'fr',
            'google_translation_code' => 'fr',
            'microsoft_translation_code' => 'fr',
            'deepl_translation_code' => 'fr'
        ],
        [
            'code' => 'af',
            'label' => 'Afrikaans',
            'rtl' => false,
            'hreflang_code' => 'af',
            'google_translation_code' => 'af',
            'microsoft_translation_code' => 'af',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'am',
            'label' => 'አማርኛ',
            'rtl' => false,
            'hreflang_code' => 'am',
            'google_translation_code' => 'am',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ar',
            'label' => 'العربية',
            'rtl' => true,
            'hreflang_code' => 'ar',
            'google_translation_code' => 'ar',
            'microsoft_translation_code' => 'ar',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'az',
            'label' => 'Azərbaycan',
            'rtl' => false,
            'hreflang_code' => 'az',
            'google_translation_code' => 'az',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'be',
            'label' => 'беларускі',
            'rtl' => false,
            'hreflang_code' => 'be',
            'google_translation_code' => 'be',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'bg',
            'label' => 'български',
            'rtl' => false,
            'hreflang_code' => 'bg',
            'google_translation_code' => 'bg',
            'microsoft_translation_code' => 'bg',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'bn',
            'label' => 'বাঙালির',
            'rtl' => false,
            'hreflang_code' => 'bn',
            'google_translation_code' => 'bn',
            'microsoft_translation_code' => 'bn',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'bs',
            'label' => 'Bosanski',
            'rtl' => false,
            'hreflang_code' => 'bs',
            'google_translation_code' => 'bs',
            'microsoft_translation_code' => 'bs',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ca',
            'label' => 'Català',
            'rtl' => false,
            'hreflang_code' => 'ca',
            'google_translation_code' => 'ca',
            'microsoft_translation_code' => 'ca',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ceb',
            'label' => 'Cebuano',
            'rtl' => false,
            'hreflang_code' => null,
            'google_translation_code' => 'ceb',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'co',
            'label' => 'Corsican',
            'rtl' => false,
            'hreflang_code' => 'co',
            'google_translation_code' => 'co',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'cs',
            'label' => 'Český',
            'rtl' => false,
            'hreflang_code' => 'cs',
            'google_translation_code' => 'cs',
            'microsoft_translation_code' => 'cs',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'cy',
            'label' => 'Cymraeg',
            'rtl' => false,
            'hreflang_code' => 'cy',
            'google_translation_code' => 'cy',
            'microsoft_translation_code' => 'cy',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'da',
            'label' => 'Dansk',
            'rtl' => false,
            'hreflang_code' => 'da',
            'google_translation_code' => 'da',
            'microsoft_translation_code' => 'da',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'el',
            'label' => 'ελληνικά',
            'rtl' => false,
            'hreflang_code' => 'el',
            'google_translation_code' => 'el',
            'microsoft_translation_code' => 'el',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'eo',
            'label' => 'Esperanto',
            'rtl' => false,
            'hreflang_code' => 'eo',
            'google_translation_code' => 'eo',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'es',
            'label' => 'Español',
            'rtl' => false,
            'hreflang_code' => 'es',
            'google_translation_code' => 'es',
            'microsoft_translation_code' => 'es',
            'deepl_translation_code' => 'es'
        ],
        [
            'code' => 'et',
            'label' => 'Eesti',
            'rtl' => false,
            'hreflang_code' => 'et',
            'google_translation_code' => 'et',
            'microsoft_translation_code' => 'et',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'eu',
            'label' => 'Euskal',
            'rtl' => false,
            'hreflang_code' => 'eu',
            'google_translation_code' => 'eu',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'fa',
            'label' => 'فارسی',
            'rtl' => true,
            'hreflang_code' => 'fa',
            'google_translation_code' => 'fa',
            'microsoft_translation_code' => 'fa',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'fi',
            'label' => 'Suomalainen',
            'rtl' => false,
            'hreflang_code' => 'fi',
            'google_translation_code' => 'fi',
            'microsoft_translation_code' => 'fi',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ga',
            'label' => 'Gaeilge',
            'rtl' => false,
            'hreflang_code' => 'ga',
            'google_translation_code' => 'ga',
            'microsoft_translation_code' => 'ga',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'gd',
            'label' => 'Gàidhlig',
            'rtl' => false,
            'hreflang_code' => 'gd',
            'google_translation_code' => 'gd',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'gl',
            'label' => 'Galego',
            'rtl' => false,
            'hreflang_code' => 'gl',
            'google_translation_code' => 'gl',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'gu',
            'label' => 'ગુજરાતી',
            'rtl' => false,
            'hreflang_code' => 'gu',
            'google_translation_code' => 'gu',
            'microsoft_translation_code' => 'gu',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ha',
            'label' => 'Hausa',
            'rtl' => true,
            'hreflang_code' => 'ha',
            'google_translation_code' => 'ha',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'haw',
            'label' => 'Hawaiian',
            'rtl' => false,
            'hreflang_code' => null,
            'google_translation_code' => 'haw',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'he',
            'label' => 'עברי',
            'rtl' => true,
            'hreflang_code' => 'he',
            'google_translation_code' => 'he',
            'microsoft_translation_code' => 'he',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'hi',
            'label' => 'हिन्दी',
            'rtl' => false,
            'hreflang_code' => 'hi',
            'google_translation_code' => 'hi',
            'microsoft_translation_code' => 'hi',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'hmn',
            'label' => 'Hmong',
            'rtl' => false,
            'hreflang_code' => null,
            'google_translation_code' => 'hmn',
            'microsoft_translation_code' => 'mww',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'hr',
            'label' => 'Hrvatski',
            'rtl' => false,
            'hreflang_code' => 'hr',
            'google_translation_code' => 'hr',
            'microsoft_translation_code' => 'hr',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ht',
            'label' => 'Kreyòl',
            'rtl' => false,
            'hreflang_code' => 'ht',
            'google_translation_code' => 'ht',
            'microsoft_translation_code' => 'ht',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'hu',
            'label' => 'Magyar',
            'rtl' => false,
            'hreflang_code' => 'hu',
            'google_translation_code' => 'hu',
            'microsoft_translation_code' => 'hu',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'hy',
            'label' => 'հայերեն',
            'rtl' => false,
            'hreflang_code' => 'hy',
            'google_translation_code' => 'hy',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'id',
            'label' => 'Indonesia',
            'rtl' => false,
            'hreflang_code' => 'id',
            'google_translation_code' => 'id',
            'microsoft_translation_code' => 'id',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ig',
            'label' => 'Igbo',
            'rtl' => false,
            'hreflang_code' => 'ig',
            'google_translation_code' => 'ig',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'is',
            'label' => 'Icelandic',
            'rtl' => false,
            'hreflang_code' => 'is',
            'google_translation_code' => 'is',
            'microsoft_translation_code' => 'is',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'it',
            'label' => 'Italiano',
            'rtl' => false,
            'hreflang_code' => 'it',
            'google_translation_code' => 'it',
            'microsoft_translation_code' => 'it',
            'deepl_translation_code' => 'it'
        ],
        [
            'code' => 'ja',
            'label' => '日本の',
            'rtl' => false,
            'hreflang_code' => 'ja',
            'google_translation_code' => 'ja',
            'microsoft_translation_code' => 'ja',
            'deepl_translation_code' => 'ja'
        ],
        [
            'code' => 'jv',
            'label' => 'Jawa',
            'rtl' => false,
            'hreflang_code' => 'jv',
            'google_translation_code' => 'jv',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ka',
            'label' => 'ქართული',
            'rtl' => false,
            'hreflang_code' => 'ka',
            'google_translation_code' => 'ka',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'kk',
            'label' => 'Қазақ',
            'rtl' => false,
            'hreflang_code' => 'kk',
            'google_translation_code' => 'kk',
            'microsoft_translation_code' => 'kk',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'km',
            'label' => 'ខ្មែរ',
            'rtl' => false,
            'hreflang_code' => 'km',
            'google_translation_code' => 'km',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'kn',
            'label' => 'ಕನ್ನಡ',
            'rtl' => false,
            'hreflang_code' => 'kn',
            'google_translation_code' => 'kn',
            'microsoft_translation_code' => 'kn',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ko',
            'label' => '한국의',
            'rtl' => false,
            'hreflang_code' => 'ko',
            'google_translation_code' => 'ko',
            'microsoft_translation_code' => 'ko',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ku',
            'label' => 'Kurdî',
            'rtl' => true,
            'hreflang_code' => 'ku',
            'google_translation_code' => 'ku',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ky',
            'label' => 'Кыргыз',
            'rtl' => false,
            'hreflang_code' => 'ky',
            'google_translation_code' => 'ky',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'la',
            'label' => 'Latine',
            'rtl' => false,
            'hreflang_code' => 'la',
            'google_translation_code' => 'la',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'lb',
            'label' => 'Lëtzebuergesch',
            'rtl' => false,
            'hreflang_code' => 'lb',
            'google_translation_code' => 'lb',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'lo',
            'label' => 'ລາວ',
            'rtl' => false,
            'hreflang_code' => 'lo',
            'google_translation_code' => 'lo',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'lt',
            'label' => 'Lietuvos',
            'rtl' => false,
            'hreflang_code' => 'lt',
            'google_translation_code' => 'lt',
            'microsoft_translation_code' => 'lt',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'lv',
            'label' => 'Latvijas',
            'rtl' => false,
            'hreflang_code' => 'lv',
            'google_translation_code' => 'lv',
            'microsoft_translation_code' => 'lv',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'mg',
            'label' => 'Malagasy',
            'rtl' => false,
            'hreflang_code' => 'mg',
            'google_translation_code' => 'mg',
            'microsoft_translation_code' => 'mg',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'mi',
            'label' => 'Maori',
            'rtl' => false,
            'hreflang_code' => 'mi',
            'google_translation_code' => 'mi',
            'microsoft_translation_code' => 'mi',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'mk',
            'label' => 'македонски',
            'rtl' => false,
            'hreflang_code' => 'mk',
            'google_translation_code' => 'mk',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ml',
            'label' => 'മലയാളം',
            'rtl' => false,
            'hreflang_code' => 'ml',
            'google_translation_code' => 'ml',
            'microsoft_translation_code' => 'ml',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'mn',
            'label' => 'Монгол',
            'rtl' => false,
            'hreflang_code' => 'mn',
            'google_translation_code' => 'mn',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'mr',
            'label' => 'मराठी',
            'rtl' => false,
            'hreflang_code' => 'mr',
            'google_translation_code' => 'mr',
            'microsoft_translation_code' => 'mr',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ms',
            'label' => 'Malay',
            'rtl' => false,
            'hreflang_code' => 'ms',
            'google_translation_code' => 'ms',
            'microsoft_translation_code' => 'ms',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'mt',
            'label' => 'Malti',
            'rtl' => false,
            'hreflang_code' => 'mt',
            'google_translation_code' => 'mt',
            'microsoft_translation_code' => 'mt',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'my',
            'label' => 'မြန်မာ',
            'rtl' => false,
            'hreflang_code' => 'my',
            'google_translation_code' => 'my',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ne',
            'label' => 'नेपाली',
            'rtl' => false,
            'hreflang_code' => 'ne',
            'google_translation_code' => 'ne',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'nl',
            'label' => 'Nederlands',
            'rtl' => false,
            'hreflang_code' => 'nl',
            'google_translation_code' => 'nl',
            'microsoft_translation_code' => 'nl',
            'deepl_translation_code' => 'nl'
        ],
        [
            'code' => 'no',
            'label' => 'Norsk',
            'rtl' => false,
            'hreflang_code' => 'no',
            'google_translation_code' => 'no',
            'microsoft_translation_code' => 'nb',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ny',
            'label' => 'Nyanja',
            'rtl' => false,
            'hreflang_code' => 'ny',
            'google_translation_code' => 'ny',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'pa',
            'label' => 'ਪੰਜਾਬੀ',
            'rtl' => false,
            'hreflang_code' => 'pa',
            'google_translation_code' => 'pa',
            'microsoft_translation_code' => 'pa',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'pl',
            'label' => 'Polski',
            'rtl' => false,
            'hreflang_code' => 'pl',
            'google_translation_code' => 'pl',
            'microsoft_translation_code' => 'pl',
            'deepl_translation_code' => 'pl'
        ],
        [
            'code' => 'ps',
            'label' => 'پښتو',
            'rtl' => true,
            'hreflang_code' => 'ps',
            'google_translation_code' => 'ps',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'pt-br',
            'label' => 'Português (Brasil)',
            'rtl' => false,
            'hreflang_code' => 'pt',
            'google_translation_code' => 'pt',
            'microsoft_translation_code' => 'pt-br',
            'deepl_translation_code' => 'pt'
        ],
        [
            'code' => 'pt-pt',
            'label' => 'Português (Portugal)',
            'rtl' => false,
            'hreflang_code' => 'pt',
            'google_translation_code' => 'pt',
            'microsoft_translation_code' => 'pt-pt',
            'deepl_translation_code' => 'pt'
        ],
        [
            'code' => 'ro',
            'label' => 'Românesc',
            'rtl' => false,
            'hreflang_code' => 'ro',
            'google_translation_code' => 'ro',
            'microsoft_translation_code' => 'ro',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ru',
            'label' => 'Русский',
            'rtl' => false,
            'hreflang_code' => 'ru',
            'google_translation_code' => 'ru',
            'microsoft_translation_code' => 'ru',
            'deepl_translation_code' => 'ru'
        ],
        [
            'code' => 'sd',
            'label' => 'سنڌي',
            'rtl' => false,
            'hreflang_code' => 'sd',
            'google_translation_code' => 'sd',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'si',
            'label' => 'සිංහලයන්',
            'rtl' => false,
            'hreflang_code' => 'si',
            'google_translation_code' => 'si',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sk',
            'label' => 'Slovenský',
            'rtl' => false,
            'hreflang_code' => 'sk',
            'google_translation_code' => 'sk',
            'microsoft_translation_code' => 'sk',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sl',
            'label' => 'Slovenski',
            'rtl' => false,
            'hreflang_code' => 'sl',
            'google_translation_code' => 'sl',
            'microsoft_translation_code' => 'sl',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sm',
            'label' => 'Samoa',
            'rtl' => false,
            'hreflang_code' => 'sm',
            'google_translation_code' => 'sm',
            'microsoft_translation_code' => 'sm',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sn',
            'label' => 'Shona',
            'rtl' => false,
            'hreflang_code' => 'sn',
            'google_translation_code' => 'sn',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'so',
            'label' => 'Soomaali',
            'rtl' => false,
            'hreflang_code' => 'so',
            'google_translation_code' => 'so',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sq',
            'label' => 'Shqiptar',
            'rtl' => false,
            'hreflang_code' => 'sq',
            'google_translation_code' => 'sq',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sr-cy',
            'label' => 'Српски (ћирилица)',
            'rtl' => false,
            'hreflang_code' => 'sr',
            'google_translation_code' => 'sr',
            'microsoft_translation_code' => 'sr-Cyrl',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sr-la',
            'label' => 'Српски (латински)',
            'rtl' => false,
            'hreflang_code' => 'sr',
            'google_translation_code' => 'sr',
            'microsoft_translation_code' => 'sr-Latn',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'su',
            'label' => 'Sunda',
            'rtl' => false,
            'hreflang_code' => 'su',
            'google_translation_code' => 'su',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'sv',
            'label' => 'Svenska',
            'rtl' => false,
            'hreflang_code' => 'sv',
            'google_translation_code' => 'sv',
            'microsoft_translation_code' => 'sv',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ta',
            'label' => 'தமிழ்',
            'rtl' => false,
            'hreflang_code' => 'ta',
            'google_translation_code' => 'ta',
            'microsoft_translation_code' => 'ta',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'te',
            'label' => 'Telugu',
            'rtl' => false,
            'hreflang_code' => 'te',
            'google_translation_code' => 'te',
            'microsoft_translation_code' => 'te',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'tg',
            'label' => 'Тоҷикистон',
            'rtl' => false,
            'hreflang_code' => 'tg',
            'google_translation_code' => 'tg',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'th',
            'label' => 'ไทย',
            'rtl' => false,
            'hreflang_code' => 'th',
            'google_translation_code' => 'th',
            'microsoft_translation_code' => 'th',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'tr',
            'label' => 'Türk',
            'rtl' => false,
            'hreflang_code' => 'tr',
            'google_translation_code' => 'tr',
            'microsoft_translation_code' => 'tr',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'uk',
            'label' => 'Український',
            'rtl' => false,
            'hreflang_code' => 'uk',
            'google_translation_code' => 'uk',
            'microsoft_translation_code' => 'uk',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'ur',
            'label' => 'اردو',
            'rtl' => true,
            'hreflang_code' => 'ur',
            'google_translation_code' => 'ur',
            'microsoft_translation_code' => 'ur',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'uz',
            'label' => 'O\'zbekiston',
            'rtl' => false,
            'hreflang_code' => 'uz',
            'google_translation_code' => 'uz',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'vi',
            'label' => 'Tiếng việt',
            'rtl' => false,
            'hreflang_code' => 'vi',
            'google_translation_code' => 'vi',
            'microsoft_translation_code' => 'vi',
            'deepl_translation_code' => null
        ],
        [
            'code' => 'xh',
            'label' => 'IsiXhosa',
            'rtl' => false,
            'hreflang_code' => 'xh',
            'google_translation_code' => 'xh',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'yi',
            'label' => 'ייִדיש',
            'rtl' => true,
            'hreflang_code' => 'yi',
            'google_translation_code' => 'yi',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'yo',
            'label' => 'Yoruba',
            'rtl' => false,
            'hreflang_code' => 'yo',
            'google_translation_code' => 'yo',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ],
        [
            'code' => 'zh-cn',
            'label' => '中文（简体）',
            'rtl' => false,
            'hreflang_code' => 'zh-cn',
            'google_translation_code' => 'zh-cn',
            'microsoft_translation_code' => 'zh-Hans',
            'deepl_translation_code' => 'zh'
        ],
        [
            'code' => 'zh-tw',
            'label' => '中文（繁體）',
            'rtl' => false,
            'hreflang_code' => 'zh-tw',
            'google_translation_code' => 'zh-tw',
            'microsoft_translation_code' => 'zh-Hant',
            'deepl_translation_code' => 'zh'
        ],
        [
            'code' => 'zu',
            'label' => 'Zulu',
            'rtl' => false,
            'hreflang_code' => 'zu',
            'google_translation_code' => 'zu',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null
        ]
    ],
    'lng_source' => 'de',
    'lng_target' => null, // this gets automatically determined by the current url; you can set this manually (e.g. in a non-host based environment)
    'database' => [
        'type' => 'sqlite',
        'filename' => 'data.db',
        'table' => 'translations'
        // mysql/pgsql is also supported
        /*
        'type' => 'mysql',
        'host' => '127.0.0.1',
        'username' => 'xxxxxx',
        'password' => 'xxxxxx',
        'database' => 'xxxxxx',
        'port' => 3306,
        'table' => 'translations'
        */
    ],
    'log_folder' => '/logs',
    'redirect_root_domain' => 'browser',
    'basic_auth' => null, // provide basic auth (in the format "username:password") if you use Gtbabel in a password protected environment
    'translate_html' => true,
    'translate_xml' => true,
    'translate_xml_include' => [
        [
            'selector' => '//*[name()=\'loc\']',
            'attribute' => null,
            'context' => 'slug'
        ]
    ],
    'translate_json' => true,
    'translate_json_include' => [
        '/path/in/source/lng/to/specific/page' => ['key'],
        'wp-json/v1/*/endpoint' => ['key', 'nested.key', 'key.with.*.wildcard']
    ],
    'translate_wp_localize_script' => true,
    'translate_wp_localize_script_include' => ['wc_*.locale.*', 'wc_*.i18n_*', 'wc_*.cart_url'],
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true,
    'xml_hreflang_tags' => true,
    'debug_translations' => false, // surround outputted translations with "%|%" to see borders
    'auto_add_translations' => true,
    'auto_set_new_strings_checked' => false,
    'auto_set_discovered_strings_checked' => false,
    'only_show_checked_strings' => false,
    'auto_translation' => true,
    'auto_translation_service' => 'google', // google|microsoft|deepl
    'google_translation_api_key' => [], // use a string or array of strings to provide multiple keys at once
    'microsoft_translation_api_key' => [], // use a string or array of strings to provide multiple keys at once
    'deepl_translation_api_key' => [], // use a string or array of strings to provide multiple keys at once
    'google_throttle_chars_per_month' => 1000000,
    'microsoft_throttle_chars_per_month' => 1000000,
    'deepl_throttle_chars_per_month' => 1000000,
    'discovery_log' => false,
    'prevent_publish' => true,
    'prevent_publish_urls' => [
        '/path/in/source/lng/to/specific/page' => ['en', 'fr'],
        '/slug1/*' => ['en'], // you can also use wildcards here
        '/slug1/*/slug2' => ['fr']
        //'/*' => ['en', 'fr'] // disable whole languages
    ],
    'alt_lng_urls' => [
        '/path/in/source/lng/to/specific/page' => 'en'
    ],
    'exclude_urls_content' => ['backend'],
    'exclude_urls_slugs' => ['api/v1.0'],
    'translate_html_exclude' => [
        ['selector' => '.notranslate'],
        ['selector' => '[data-context]', 'attribute' => 'data-context'],
        ['selector' => '.lngpicker'],
        ['selector' => '.xdebug-error'],
        ['selector' => '.example1', 'attribute' => 'data-text'],
        ['selector' => '.example2', 'attribute' => 'data-*']
    ],
    'force_tokenize' => ['.force-tokenize'],
    'translate_html_include' => [
        [
            'selector' => '/html/body//text()',
            'attribute' => null,
            'context' => null
        ],
        [
            'selector' => '/html/body//a[starts-with(@href, \'mailto:\')]',
            'attribute' => 'href',
            'context' => 'email'
        ],
        [
            'selector' => '/html/body//a[@href]',
            'attribute' => 'href',
            'context' => 'slug|file'
        ],
        [
            'selector' => '/html/body//form[@action]',
            'attribute' => 'action',
            'context' => 'slug'
        ],
        [
            'selector' => '/html/body//img[@alt]',
            'attribute' => 'alt',
            'context' => null
        ],
        [
            'selector' => '/html/body//*[@title]',
            'attribute' => 'title',
            'context' => null
        ],
        [
            'selector' => '/html/body//*[@placeholder]',
            'attribute' => 'placeholder',
            'context' => null
        ],
        [
            'selector' => '/html/body//input[@type="submit"][@value]',
            'attribute' => 'value',
            'context' => null
        ],
        [
            'selector' => '/html/body//input[@type="reset"][@value]',
            'attribute' => 'value',
            'context' => null
        ],
        [
            'selector' => '/html/head//title',
            'attribute' => null,
            'context' => 'title'
        ],
        [
            'selector' => '/html/head//meta[@name="description"][@content]',
            'attribute' => 'content',
            'context' => 'description'
        ],
        [
            'selector' => '/html/head//link[@rel="canonical"][@href]',
            'attribute' => 'href',
            'context' => 'slug'
        ],
        [
            'selector' => '/html/body//img[@src]',
            'attribute' => 'src',
            'context' => 'file'
        ],
        [
            'selector' => '/html/body//*[contains(@style, "url(")]',
            'attribute' => 'style',
            'context' => 'file'
        ],
        [
            'selector' => '/html/body//*[@label]',
            'attribute' => 'label',
            'context' => null
        ],
        [
            'selector' => '/html/body//@*[contains(name(), \'text\')]/parent::*', // *text*
            'attribute' => '*text*', // *text*
            'context' => null
        ],
        [
            'selector' => '#payment .place-order .button', // woocommerce
            'attribute' => 'data-value|value',
            'context' => null
        ],
        [
            'selector' => '.example-link', // css selectors are also supported
            'attribute' => 'alt-href|*foo*', // wildcards and | supported
            'context' => 'slug'
        ]
    ],
    'localize_js' => true,
    'localize_js_strings' => ['Schließen', '/blog'],
    'detect_dom_changes' => true,
    'detect_dom_changes_include' => ['.top-button', '.swal-overlay', '.wpcf7-response-output', '.wpcf7-not-valid-tip']
];
```

## Other usage

Gtbabel catches the content between `start()` and `stop()` with [output buffering](https://www.php.net/manual/de/function.ob-start.php).\
However, you can also use Gtbabel more directly:

```php
$gtbabel = new Gtbabel();

$gtbabel->config([...]);

$gtbabel->translate('<p>Dies ist ein Test!</p>'); // <p>This is a test!</p>

$gtbabel->tokenize('<p>Dies ist ein Test!</p>'); // [['string' => 'This is a test!', 'context' => null]]
```

## WordPress plugin

[wordpress.org/plugins/gtbabel](https://wordpress.org/plugins/gtbabel/)

You don't have to change any code in the frontend at all: If you already have functions like `__()` in your code, just either remove them or simply leave them (since WordPress internally only knows about the source language and `__()` has no effect); if not, don't add them. Gtbabel acts on the output (like on any other page).

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
-   Possibility to replace files per language via the media library
-   Multisite support
-   Flexible permission system based on capabilities

### Permissions

Gtbabel for WordPress uses the following capabilities:

-   `gtbabel__edit_settings`: capability to edit settings
-   `gtbabel__translation_list`: capability to use the list of translations
-   `gtbabel__translation_assistant`: capability to use the translation assistant
-   `gtbabel__translate_xx`: capability to translate the language with code `xx`

By default, administrators have all capabilities.\
You can add these capabilities to your WordPress user roles individually

-   in your WordPress backend under Gtbabel > Permissions,
-   with plugins like [User Role Editor](https://de.wordpress.org/plugins/user-role-editor/),
-   by using custom code.

The following example gives the role "Editor" translation permissions for the languages English and Français in the translation assistant:

```php
// only run this once
add_action('admin_init', function () {
    get_role('editor')->add_cap('gtbabel__translation_assistant');
    get_role('editor')->add_cap('gtbabel__translate_en');
    get_role('editor')->add_cap('gtbabel__translate_fr');
});
```

## Database and Gettext

-   The final html gets parsed and is split up in reasonable strings.
-   Gtbabel operates on a database layer (e.g. sqlite) to ensure fast load times.
-   The database structure reflects the structure of [GNU gettext](https://www.gnu.org/software/gettext/).
-   You can export and import the data as po or even and xlsx files at any time.

## JavaScript

Gtbabel itself is based on PHP and works for static pages or pages rendered via PHP.

However, if you enable `detect_dom_changes`, Gtbabel monitors dom changes and translates suitable parts.

To get translations directly in JavaScript, Gtbabel also provides a small helper function to hotload your translations in the header that works in every environment. For this purpose the option `localize_js_strings` must be filled with content. You then can access those strings easily inside JavaScript with:

```js
if (typeof gtbabel__ === 'function') {
    gtbabel__('Registered string');
}
```

If you are on WordPress, you might already use the [wp_localize_script](https://developer.wordpress.org/reference/functions/wp_localize_script/) function. Keep doing that and simply use `gtbabel__()` via PHP to translate the strings you want to be provided in JavaScript:

```php
if (function_exists('gtbabel__')) {
    wp_localize_script('script', 'strings', [
        'baseurl' => gtbabel__(get_bloginfo('url')),
        'lng' => gtbabel_current_lng(),
        'example' => gtbabel__('Dies ist ein Test')
    ]);
}
```

Another way of doing this is using the `translate_wp_localize_script` option: Populate `translate_wp_localize_script_include` with key chained paths to (possibly nested) strings and Gtbabel translates whem automatically.

## Modified nodes

By default Gtbabel translates all text and tag nodes with the following reasonable default rules:

| css selector                    | text | attribute        | context          |
| ------------------------------- | ---- | ---------------- | ---------------- |
| `body *`                        | ✓    | ✗                | `auto`           |
| `body a`                        | ✗    | `href^="mailto"` | `email`          |
| `body a`                        | ✗    | `href`           | `'slug'\|'file'` |
| `body form`                     | ✗    | `action`         | `'slug'`         |
| `body img`                      | ✗    | `alt`            | `auto`           |
| `body *`                        | ✗    | `title`          | `auto`           |
| `body *`                        | ✗    | `placeholder`    | `auto`           |
| `body input[type="submit"]`     | ✗    | `value`          | `auto`           |
| `body input[type="reset"]`      | ✗    | `value`          | `auto`           |
| `head title`                    | ✓    | ✗                | `'title'`        |
| `head meta[name="description"]` | ✗    | `content`        | `'description'`  |
| `head link[rel="canonical"]`    | ✗    | `href`           | `'slug'`         |
| `body img`                      | ✗    | `src`            | `'file'`         |
| `body *`                        | ✗    | `style`          | `'file'`         |
| `body *`                        | ✗    | `label`          | `auto`           |
| `body *`                        | ✗    | `*text*`         | `auto`           |

You can modify or add new tag node transformations via the `translate_html_include` option.

For example if you want to translate all data attributes, add this rule:

```php
[
    'selector' => '/html/body//@*[starts-with(name(), \'data-\')]/parent::*',
    'attribute' => 'data-*',
    'context' => null
];
```

Or if you want to translate a specific data attribute (and want to use css selectors instead of xpath), you can use:

```php
[
    'selector' => '.foo[data-bar]',
    'attribute' => 'data-bar',
    'context' => null
];
```

Be aware that the rules are processed sequentially and attribute names are never transformed twice.

Gtbabel automatically groups together reasonable parts.\
The following code gets converted to 1 token (not 3):

`<p>This is a <a href="#">link</a> inside a text.</p>`

If you want to influence that behaviour, use the `force_tokenize` option and provide the selector of the parent element in order to not tokenize its children.

If you want to influence that special tags should not be auto translated by the Google/Microsoft/DeepL Translation API, use a special class:

`<p>Das ist das <span class="notranslate">Haus</span> vom Nikolaus</p>`

The part before and after the `span`-tag gets translated (and added to the database).\
If you want to exclude a complete node of being translated (and not added to the database), add a class with the value `notranslate` (or any other value defined in `translate_html_exclude`) to the parent node:

`<p class="notranslate">Das ist das <span>Haus</span> vom Nikolaus</p>`

Note that attributes of ignored nodes are also not translated (the `href`-attribute and the text `Link` does not get translated):

`<a href="/home" class="notranslate">Link</a>`

However, if you want the `href`-attribute to be modified, do something like

`<a href="/home"><span class="notranslate">Link</span></a>`

## HTML

Gtbabel preserves inline HTML-tags and leaves them inside your translations.\
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

is stored in the database as

`Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.`

If your translations don't reflect the original order, in the database they include hints like

`I <a p="2">miss</a> German <a p="1">bread</a> the most.`

where "2" stands for the second tag of the original string.

The string then finally gets correctly translated to

`I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.`

## Ambiguous translations and plural forms

Consider the following example:

```html
<ul>
    <li>Bank</li>
    <!-- bank -->
    <li>Bank</li>
    <!-- bench -->
    <li>Schlüssel</li>
    <!-- key (singular) -->
    <li>Schlüssel</li>
    <!-- keys (plural) -->
</ul>
```

Normally Gtbabel would create 2 translations from this and cannot distinguish between ambiguous translations and singular/plural forms.

In order to get that use the special `data-context`-attribute to force sensible contexts:

```html
<ul>
    <li data-context="finance">Bank</li>
    <!-- bank -->
    <li data-context="furniture">Bank</li>
    <!-- bench -->
    <li data-context="singular">Schlüssel</li>
    <!-- key (singular) -->
    <li data-context="plural">Schlüssel</li>
    <!-- keys (plural) -->
</ul>
```

This way you can provide different translations.

## Multiple source languages

Gtbabel is not restricted to use one single source language for the whole content.\
In fact, parts of the content can be marked language-specific:

```html
<!DOCTYPE html>
<html lang="en">
    <body>
        <p>Some content in english.</p>
        <div lang="fr">Contenu en français.</div>
        <p>Some other content in english.</p>
    </body>
</html>
```

This all gets translated correctly to the target language (in our case `de`):

```html
<!DOCTYPE html>
<html lang="de">
    <body>
        <p>Einige Inhalte in Englisch.</p>
        <div lang="de">Inhalt auf Französisch.</div>
        <p>Einige andere Inhalte in Englisch.</p>
    </body>
</html>
```

If a page consists mainly of a block in another language, you can easily set the `lang`-tags using the `gtbabel_alt_lng` function:

```php
gtbabel_alt_lng() // current url
gtbabel_alt_lng('https://tld.com/some-page') // specific url (in source language)
```

You can provide those settings beforehand via the `alt_lng_urls` option.

## Router

The router automatically modifies the `$_SERVER['REQUEST_URI']` variable to catch translated urls.\
Unknown translations of urls are picked up from the current url and from links that are on the page.\
These urls are automatically added to the database (ajax requested urls are excluded).

## Domain schema

Gtbabel by default uses a path based approach for your url structure.\
That means: The language code is always appended to your main url:

`https://www.tld.com/de/`

You can change this behaviour in any way you like.\
Just extend the `languages`-option with `url_base` and `url_prefix`.\
The following configuration is the same as if no option isset:

```php
'languages' => [
    [
        'code' => 'de',
        /* ... */
        'url_base' => 'https://www.tld.com'
        'url_prefix' => 'de'
    ],
    [
        'code' => 'en',
        /* ... */
        'url_base' => 'https://www.tld.com'
        'url_prefix' => 'en'
    ]
    /* ... */
]
```

In order to not prefix your source language (which is common), just provide a special rule for it\
(see we didn't specify `url_base` and any option for the other languages, because Gtbabel still uses the default there):

```php
'languages' => [
    [
        'code' => 'de',
        /* ... */
        'url_prefix' => ''
    ],
    [
        'code' => 'en',
        /* ... */
    ]
    /* ... */
]
```

With this technique we can use custom language codes in our urls:

```php
'languages' => [
    [
        'code' => 'de',
        /* ... */
        'url_prefix' => 'deutsch'
    ],
    [
        'code' => 'en',
        /* ... */
        'url_prefix' => 'english'
    ]
    /* ... */
]
```

Or we can easily setup a subdomain based approach:

```php
'languages' => [
    [
        'code' => 'de',
        /* ... */
        'url_base' => 'https://de.tld.com'
        'url_prefix' => ''
    ],
    [
        'code' => 'en',
        /* ... */
        'url_base' => 'https://en.tld.com'
        'url_prefix' => ''
    ]
    /* ... */
]
```

Even a multidomain based approach is possible:

```php
'languages' => [
    [
        'code' => 'de',
        /* ... */
        'url_base' => 'https://www.tld.de'
        'url_prefix' => ''
    ],
    [
        'code' => 'en',
        /* ... */
        'url_base' => 'https://www.tld.com'
        'url_prefix' => ''
    ]
    /* ... */
]
```

If you have hosted your website on a subpath, you should provide something like:

```php
'languages' => [
    [
        'code' => 'de',
        /* ... */
        'url_base' => 'https://www.tld.com/some/sub/path'
        'url_prefix' => 'de'
    ],
    [
        'code' => 'en',
        /* ... */
        'url_base' => 'https://www.tld.com/some/sub/path'
        'url_prefix' => 'en'
    ]
    /* ... */
]
```

The configuration is totally up to you (you can even mix approaches).\
You only have to make sure that all top level domains point to the same root path.

## Dealing with assets

Although Gtbabel leaves static files untouched, it adds them to the template file (with context `file`). This gives you the possibility to output images, downloads and other media individually per language.

## JSON Responses

JSON responses can also be translated by Gtbabel when setting `translate_json` to true and populating `translate_json_include`.\
If your json endpoint is not targeted via a language specific url, you can get the language via `gtbabel_referer_lng()`.

## Translation management

If `only_show_checked_strings` is set to `true`, only translations that are checked are shown. This way you can build an approval system in your backend, where translators can check strings before they are published.

## Flow diagram

![Flow diagram](gtbabel.drawio.svg)

## Language picker

To output a language picker, just use this snippet:

```php
if (function_exists('gtbabel_languagepicker')) {
    echo '<ul class="lngpicker">';
    foreach (gtbabel_languagepicker() as $languagepicker__value) {
        echo '<li>';
        echo '<a href="' .
            $languagepicker__value['url'] .
            '"' .
            ($languagepicker__value['active'] ? ' class="active"' : '') .
            '>';
        echo $languagepicker__value['label'];
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
}
```

Make sure that the container class is excluded from your translations.

## Helper functions

Always surround these helper functions with `if( function_exists('...') ) { }`.

```php
gtbabel_current_lng() // 'en'
gtbabel_source_lng() // 'de'
gtbabel_languages() // ['de','en']
gtbabel_default_language_codes() // ['de','en','fr','af','am','ar','az','be','bg','bn','bs','ca','ceb','co','cs','cy','da','el','eo','es','et','eu','fa','fi','ga','gd','gl','gu','ha','haw','he','hi','hmn','hr','ht','hu','hy','id','ig','is','it','ja','jv','ka','kk','km','kn','ko','ku','ky','la','lb','lo','lt','lv','mg','mi','mk','ml','mn','mr','ms','mt','my','ne','nl','no','ny','pa','pl','ps','pt','ro','ru','sd','si','sk','sl','sm','sn','so','sq','sr','su','sv','ta','te','tg','th','tr','uk','ur','uz','vi','xh','yi','yo','zh-cn','zh-tw','zu']
gtbabel_default_languages() // [['code' => 'de', 'label' => 'Deutsch'], ['code' => 'en', 'label' => 'English'], ...]
gtbabel_language_label('en') // 'English'
gtbabel_default_settings() // ['languages' => [['code' => 'de', 'label' => 'Deutsch'], ['code' => 'en', 'label' => 'English'], ...], 'log_folder' => '/logs', ...]
gtbabel_default_settings(['log_folder' => '/foo']) // ['languages' => [['code' => 'de', 'label' => 'Deutsch'], ['code' => 'en', 'label' => 'English'], ...], 'log_folder' => '/foo', ...]
gtbabel_referer_lng() // 'de'
gtbabel_languagepicker() // [['code' => 'de', 'label' => 'Deutsch', 'url' => 'https://tld.com/de/nudel', 'active' => true], ['code' => 'en', 'label' => 'English', 'url' => 'https://tld.com/en/noodle', 'active' => false], ...]
gtbabel__('Hallo') // 'Hi there'
```

## Notes on gtbabel\_\_()

Don't use `gtbabel__()` in your templates for echoing strings, just output them in the source language. Gtbabel will take care of the rest. However, there are some places (e.g. in your backend logic), where you need to translate strings. This is what this function is intended for.

You also can set the target and source language manually to get some specific translation. Because translations are not injective, the following two calls create two database entries (with different source languages):

```php
gtbabel__('Hallo', 'en', 'de') // 'Hi there'
gtbabel__('Hi there', 'de', 'en') // 'Hallo'
```

You can provide any html to `gtbabel__()`:

```php
gtbabel__('<p>Hallo <a href="/datenschutz">Datenschutz</a>!</p>'); // '<p>Hello <a href="/en/data-protection">data protection</a>!</p>'
```

The function is smart enough to detect urls or even root relative urls and returns appropriate translations:

```php
gtbabel__('/datenschutz'); // '/en/data-protection'
```

## Dealing with form data

Gtbabel translates your frontend, json responses and watches dom changes.\
Nevertheless, one has to manually take care of how multilingual input data should be further processed.

### Search

If you need to translate queries to your source language (for example in searches), just do this:

```php
$s = gtbabel__($s, gtbabel_source_lng(), gtbabel_current_lng());
```

In WordPress for example, you can modify the search query like this:

```php
add_action('pre_get_posts', function ($query) {
    if (!$query->is_main_query() || is_admin() || !is_search()) {
        return;
    }
    $query->set('s', gtbabel__($query->get('s'), gtbabel_source_lng(), gtbabel_current_lng()));
});
```

### Contact forms

Prepare your email templates only in your source language and before sending, translate them on the fly with something like:

```php
$template = gtbabel__($template, gtbabel_referer_lng(), gtbabel_source_lng());
```

In WordPress, Contact Form 7 is a popular contact form plugin. Gtbabel has built in support for translating its email templates.

### Comments

If you offer a comment function on your website, just save the incoming comments in the input language (that can be determined via `gtbabel_current_lng()` or `gtbabel_referer_lng()`). Then output just those comments that match your current input language or even auto translate all of them using the `lang`-tag (see **Multiple source languages**).

## Language codes

It is recommended to use iso language codes in lowercase. But you can use any language code you want (even i-klingon).\
For every language, you should provide language codes that are used for auto translation. If a language is not supported in your translation service, use `null`.\
Country codes (like "BR" in "pt_BR") should be used in the (unique) `code`-attribute with a dash: `pt-br`.\
Hreflang codes must be in [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes), so some languages don't have an official hreflang code in the settings array.

## Google Translation API

-   Go to [Google API Console](https://console.cloud.google.com/apis)
-   Create a new project
-   Marketplace > Enable "Cloud Translation API" (this requires you to setup a billing account)
-   APIs and services > API credentials > Add a new api key

## API usage costs

The translation apis of Google, Microsoft or DeepL can be costly. Try to keep track of your current usage stats. Gtbabel helps you by tracking the total amount of translated chars. You can also provide multiple api keys, Gtbabel then distributes the calls uniformly.

You can throttle the amount of chars with the options `google_throttle_chars_per_month`, `microsoft_throttle_chars_per_month` and `deepl_throttle_chars_per_month`.

## Caveats

-   Gtbabel is good at translating whole pages. If you want to provide different content for different languages, Gtbabel might be the wrong tool (however you _can_ use `if( gtbabel_current_lng() === 'other-lng' ) { echo 'content in source language'; }` and/or the concept of **Multiple source languages** in your templates to achieve that).
-   The source language acts as a base for every other language (however, you _can_ mark parts of a page with a different source language).
-   Gtbabel is designed to translate every part of the page without code changes (however you can exclude translations with the `translate_html_exclude`-option).

## Credits

Greetings to my coworker, without whom this project would not have been initiated.
