[![Build Status](https://travis-ci.com/vielhuber/gtbabel.svg?branch=master)](https://travis-ci.com/vielhuber/gtbabel)

# 🌐 Gtbabel 🌐

Gtbabel automatically translates your HTML/PHP pages – server sided!

-   [Basic idea](#basic-idea)
-   [Features](#features)
-   [Requirements](#requirements)
-   [Installation](#installation)
-   [Usage](#usage)
-   [Configuration](#configuration)
-   [Other usage](#other-usage)
-   [WordPress plugin](#wordpress-plugin)
    -   [Permissions](#permissions)
    -   [Email notifications](#email-notifications)
-   [Database and Gettext](#database-and-gettext)
-   [JavaScript](#javascript)
-   [Modified nodes](#modified-nodes)
-   [HTML](#html)
-   [Ambiguous translations and plural forms](#ambiguous-translations-and-plural-forms)
-   [Multiple source languages](#multiple-source-languages)
-   [Hiding content](#hiding-content)
-   [Router](#router)
-   [Domain schema](#domain-schema)
-   [Dealing with assets](#dealing-with-assets)
-   [JSON Responses](#json-responses)
-   [Translation management](#translation-management)
-   [Flow diagram](#flow-diagram)
-   [Language picker](#language-picker)
-   [Helper functions](#helper-functions)
-   [Echoing strings](#echoing-strings)
-   [Dynamic data](#dynamic-data)
    -   [Search](#search)
    -   [URL query arguments](#url-query-arguments)
    -   [Mails](#mails)
    -   [Comments](#comments)
-   [Language codes](#language-codes)
-   [Google Translation API](#google-translation-api)
-   [API usage costs](#api-usage-costs)
-   [Custom translation providers](#custom-translation-providers)
-   [Caveats](#caveats)
-   [Credits](#credits)

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
-   Custom providers: Connect custom translation APIs
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
-   Provide stopwords to prevent translations for specific words.
-   Entirely hide languages to prepare new translations beforehand.
-   DOM change detection: Observe specific page parts and translate dynamic content.
-   Auto throttling api translation for cost control.
-   Custom domain support (path based, subdomain based, top level domain based).
-   Frontend editor included.

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
            'deepl_translation_code' => 'de',
            'hidden' => false
        ],
        [
            'code' => 'en',
            'label' => 'English',
            'rtl' => false,
            'hreflang_code' => 'en',
            'google_translation_code' => 'en',
            'microsoft_translation_code' => 'en',
            'deepl_translation_code' => 'en',
            'hidden' => false
        ],
        [
            'code' => 'fr',
            'label' => 'Français',
            'rtl' => false,
            'hreflang_code' => 'fr',
            'google_translation_code' => 'fr',
            'microsoft_translation_code' => 'fr',
            'deepl_translation_code' => 'fr',
            'hidden' => false
        ],
        [
            'code' => 'af',
            'label' => 'Afrikaans',
            'rtl' => false,
            'hreflang_code' => 'af',
            'google_translation_code' => 'af',
            'microsoft_translation_code' => 'af',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'am',
            'label' => 'አማርኛ',
            'rtl' => false,
            'hreflang_code' => 'am',
            'google_translation_code' => 'am',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ar',
            'label' => 'العربية',
            'rtl' => true,
            'hreflang_code' => 'ar',
            'google_translation_code' => 'ar',
            'microsoft_translation_code' => 'ar',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'az',
            'label' => 'Azərbaycan',
            'rtl' => false,
            'hreflang_code' => 'az',
            'google_translation_code' => 'az',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'be',
            'label' => 'беларускі',
            'rtl' => false,
            'hreflang_code' => 'be',
            'google_translation_code' => 'be',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'bg',
            'label' => 'български',
            'rtl' => false,
            'hreflang_code' => 'bg',
            'google_translation_code' => 'bg',
            'microsoft_translation_code' => 'bg',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'bn',
            'label' => 'বাঙালির',
            'rtl' => false,
            'hreflang_code' => 'bn',
            'google_translation_code' => 'bn',
            'microsoft_translation_code' => 'bn',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'bs',
            'label' => 'Bosanski',
            'rtl' => false,
            'hreflang_code' => 'bs',
            'google_translation_code' => 'bs',
            'microsoft_translation_code' => 'bs',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ca',
            'label' => 'Català',
            'rtl' => false,
            'hreflang_code' => 'ca',
            'google_translation_code' => 'ca',
            'microsoft_translation_code' => 'ca',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ceb',
            'label' => 'Cebuano',
            'rtl' => false,
            'hreflang_code' => null,
            'google_translation_code' => 'ceb',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'co',
            'label' => 'Corsican',
            'rtl' => false,
            'hreflang_code' => 'co',
            'google_translation_code' => 'co',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'cs',
            'label' => 'Český',
            'rtl' => false,
            'hreflang_code' => 'cs',
            'google_translation_code' => 'cs',
            'microsoft_translation_code' => 'cs',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'cy',
            'label' => 'Cymraeg',
            'rtl' => false,
            'hreflang_code' => 'cy',
            'google_translation_code' => 'cy',
            'microsoft_translation_code' => 'cy',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'da',
            'label' => 'Dansk',
            'rtl' => false,
            'hreflang_code' => 'da',
            'google_translation_code' => 'da',
            'microsoft_translation_code' => 'da',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'el',
            'label' => 'ελληνικά',
            'rtl' => false,
            'hreflang_code' => 'el',
            'google_translation_code' => 'el',
            'microsoft_translation_code' => 'el',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'eo',
            'label' => 'Esperanto',
            'rtl' => false,
            'hreflang_code' => 'eo',
            'google_translation_code' => 'eo',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'es',
            'label' => 'Español',
            'rtl' => false,
            'hreflang_code' => 'es',
            'google_translation_code' => 'es',
            'microsoft_translation_code' => 'es',
            'deepl_translation_code' => 'es',
            'hidden' => false
        ],
        [
            'code' => 'et',
            'label' => 'Eesti',
            'rtl' => false,
            'hreflang_code' => 'et',
            'google_translation_code' => 'et',
            'microsoft_translation_code' => 'et',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'eu',
            'label' => 'Euskal',
            'rtl' => false,
            'hreflang_code' => 'eu',
            'google_translation_code' => 'eu',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'fa',
            'label' => 'فارسی',
            'rtl' => true,
            'hreflang_code' => 'fa',
            'google_translation_code' => 'fa',
            'microsoft_translation_code' => 'fa',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'fi',
            'label' => 'Suomalainen',
            'rtl' => false,
            'hreflang_code' => 'fi',
            'google_translation_code' => 'fi',
            'microsoft_translation_code' => 'fi',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ga',
            'label' => 'Gaeilge',
            'rtl' => false,
            'hreflang_code' => 'ga',
            'google_translation_code' => 'ga',
            'microsoft_translation_code' => 'ga',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'gd',
            'label' => 'Gàidhlig',
            'rtl' => false,
            'hreflang_code' => 'gd',
            'google_translation_code' => 'gd',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'gl',
            'label' => 'Galego',
            'rtl' => false,
            'hreflang_code' => 'gl',
            'google_translation_code' => 'gl',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'gu',
            'label' => 'ગુજરાતી',
            'rtl' => false,
            'hreflang_code' => 'gu',
            'google_translation_code' => 'gu',
            'microsoft_translation_code' => 'gu',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ha',
            'label' => 'Hausa',
            'rtl' => true,
            'hreflang_code' => 'ha',
            'google_translation_code' => 'ha',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'haw',
            'label' => 'Hawaiian',
            'rtl' => false,
            'hreflang_code' => null,
            'google_translation_code' => 'haw',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'he',
            'label' => 'עברי',
            'rtl' => true,
            'hreflang_code' => 'he',
            'google_translation_code' => 'he',
            'microsoft_translation_code' => 'he',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'hi',
            'label' => 'हिन्दी',
            'rtl' => false,
            'hreflang_code' => 'hi',
            'google_translation_code' => 'hi',
            'microsoft_translation_code' => 'hi',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'hmn',
            'label' => 'Hmong',
            'rtl' => false,
            'hreflang_code' => null,
            'google_translation_code' => 'hmn',
            'microsoft_translation_code' => 'mww',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'hr',
            'label' => 'Hrvatski',
            'rtl' => false,
            'hreflang_code' => 'hr',
            'google_translation_code' => 'hr',
            'microsoft_translation_code' => 'hr',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ht',
            'label' => 'Kreyòl',
            'rtl' => false,
            'hreflang_code' => 'ht',
            'google_translation_code' => 'ht',
            'microsoft_translation_code' => 'ht',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'hu',
            'label' => 'Magyar',
            'rtl' => false,
            'hreflang_code' => 'hu',
            'google_translation_code' => 'hu',
            'microsoft_translation_code' => 'hu',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'hy',
            'label' => 'հայերեն',
            'rtl' => false,
            'hreflang_code' => 'hy',
            'google_translation_code' => 'hy',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'id',
            'label' => 'Indonesia',
            'rtl' => false,
            'hreflang_code' => 'id',
            'google_translation_code' => 'id',
            'microsoft_translation_code' => 'id',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ig',
            'label' => 'Igbo',
            'rtl' => false,
            'hreflang_code' => 'ig',
            'google_translation_code' => 'ig',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'is',
            'label' => 'Icelandic',
            'rtl' => false,
            'hreflang_code' => 'is',
            'google_translation_code' => 'is',
            'microsoft_translation_code' => 'is',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'it',
            'label' => 'Italiano',
            'rtl' => false,
            'hreflang_code' => 'it',
            'google_translation_code' => 'it',
            'microsoft_translation_code' => 'it',
            'deepl_translation_code' => 'it',
            'hidden' => false
        ],
        [
            'code' => 'ja',
            'label' => '日本の',
            'rtl' => false,
            'hreflang_code' => 'ja',
            'google_translation_code' => 'ja',
            'microsoft_translation_code' => 'ja',
            'deepl_translation_code' => 'ja',
            'hidden' => false
        ],
        [
            'code' => 'jv',
            'label' => 'Jawa',
            'rtl' => false,
            'hreflang_code' => 'jv',
            'google_translation_code' => 'jv',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ka',
            'label' => 'ქართული',
            'rtl' => false,
            'hreflang_code' => 'ka',
            'google_translation_code' => 'ka',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'kk',
            'label' => 'Қазақ',
            'rtl' => false,
            'hreflang_code' => 'kk',
            'google_translation_code' => 'kk',
            'microsoft_translation_code' => 'kk',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'km',
            'label' => 'ខ្មែរ',
            'rtl' => false,
            'hreflang_code' => 'km',
            'google_translation_code' => 'km',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'kn',
            'label' => 'ಕನ್ನಡ',
            'rtl' => false,
            'hreflang_code' => 'kn',
            'google_translation_code' => 'kn',
            'microsoft_translation_code' => 'kn',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ko',
            'label' => '한국의',
            'rtl' => false,
            'hreflang_code' => 'ko',
            'google_translation_code' => 'ko',
            'microsoft_translation_code' => 'ko',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ku',
            'label' => 'Kurdî',
            'rtl' => true,
            'hreflang_code' => 'ku',
            'google_translation_code' => 'ku',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ky',
            'label' => 'Кыргыз',
            'rtl' => false,
            'hreflang_code' => 'ky',
            'google_translation_code' => 'ky',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'la',
            'label' => 'Latine',
            'rtl' => false,
            'hreflang_code' => 'la',
            'google_translation_code' => 'la',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'lb',
            'label' => 'Lëtzebuergesch',
            'rtl' => false,
            'hreflang_code' => 'lb',
            'google_translation_code' => 'lb',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'lo',
            'label' => 'ລາວ',
            'rtl' => false,
            'hreflang_code' => 'lo',
            'google_translation_code' => 'lo',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'lt',
            'label' => 'Lietuvos',
            'rtl' => false,
            'hreflang_code' => 'lt',
            'google_translation_code' => 'lt',
            'microsoft_translation_code' => 'lt',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'lv',
            'label' => 'Latvijas',
            'rtl' => false,
            'hreflang_code' => 'lv',
            'google_translation_code' => 'lv',
            'microsoft_translation_code' => 'lv',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'mg',
            'label' => 'Malagasy',
            'rtl' => false,
            'hreflang_code' => 'mg',
            'google_translation_code' => 'mg',
            'microsoft_translation_code' => 'mg',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'mi',
            'label' => 'Maori',
            'rtl' => false,
            'hreflang_code' => 'mi',
            'google_translation_code' => 'mi',
            'microsoft_translation_code' => 'mi',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'mk',
            'label' => 'македонски',
            'rtl' => false,
            'hreflang_code' => 'mk',
            'google_translation_code' => 'mk',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ml',
            'label' => 'മലയാളം',
            'rtl' => false,
            'hreflang_code' => 'ml',
            'google_translation_code' => 'ml',
            'microsoft_translation_code' => 'ml',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'mn',
            'label' => 'Монгол',
            'rtl' => false,
            'hreflang_code' => 'mn',
            'google_translation_code' => 'mn',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'mr',
            'label' => 'मराठी',
            'rtl' => false,
            'hreflang_code' => 'mr',
            'google_translation_code' => 'mr',
            'microsoft_translation_code' => 'mr',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ms',
            'label' => 'Malay',
            'rtl' => false,
            'hreflang_code' => 'ms',
            'google_translation_code' => 'ms',
            'microsoft_translation_code' => 'ms',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'mt',
            'label' => 'Malti',
            'rtl' => false,
            'hreflang_code' => 'mt',
            'google_translation_code' => 'mt',
            'microsoft_translation_code' => 'mt',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'my',
            'label' => 'မြန်မာ',
            'rtl' => false,
            'hreflang_code' => 'my',
            'google_translation_code' => 'my',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ne',
            'label' => 'नेपाली',
            'rtl' => false,
            'hreflang_code' => 'ne',
            'google_translation_code' => 'ne',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'nl',
            'label' => 'Nederlands',
            'rtl' => false,
            'hreflang_code' => 'nl',
            'google_translation_code' => 'nl',
            'microsoft_translation_code' => 'nl',
            'deepl_translation_code' => 'nl',
            'hidden' => false
        ],
        [
            'code' => 'no',
            'label' => 'Norsk',
            'rtl' => false,
            'hreflang_code' => 'no',
            'google_translation_code' => 'no',
            'microsoft_translation_code' => 'nb',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ny',
            'label' => 'Nyanja',
            'rtl' => false,
            'hreflang_code' => 'ny',
            'google_translation_code' => 'ny',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'pa',
            'label' => 'ਪੰਜਾਬੀ',
            'rtl' => false,
            'hreflang_code' => 'pa',
            'google_translation_code' => 'pa',
            'microsoft_translation_code' => 'pa',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'pl',
            'label' => 'Polski',
            'rtl' => false,
            'hreflang_code' => 'pl',
            'google_translation_code' => 'pl',
            'microsoft_translation_code' => 'pl',
            'deepl_translation_code' => 'pl',
            'hidden' => false
        ],
        [
            'code' => 'ps',
            'label' => 'پښتو',
            'rtl' => true,
            'hreflang_code' => 'ps',
            'google_translation_code' => 'ps',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'pt-br',
            'label' => 'Português (Brasil)',
            'rtl' => false,
            'hreflang_code' => 'pt',
            'google_translation_code' => 'pt',
            'microsoft_translation_code' => 'pt-br',
            'deepl_translation_code' => 'pt',
            'hidden' => false
        ],
        [
            'code' => 'pt-pt',
            'label' => 'Português (Portugal)',
            'rtl' => false,
            'hreflang_code' => 'pt',
            'google_translation_code' => 'pt',
            'microsoft_translation_code' => 'pt-pt',
            'deepl_translation_code' => 'pt',
            'hidden' => false
        ],
        [
            'code' => 'ro',
            'label' => 'Românesc',
            'rtl' => false,
            'hreflang_code' => 'ro',
            'google_translation_code' => 'ro',
            'microsoft_translation_code' => 'ro',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ru',
            'label' => 'Русский',
            'rtl' => false,
            'hreflang_code' => 'ru',
            'google_translation_code' => 'ru',
            'microsoft_translation_code' => 'ru',
            'deepl_translation_code' => 'ru',
            'hidden' => false
        ],
        [
            'code' => 'sd',
            'label' => 'سنڌي',
            'rtl' => false,
            'hreflang_code' => 'sd',
            'google_translation_code' => 'sd',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'si',
            'label' => 'සිංහලයන්',
            'rtl' => false,
            'hreflang_code' => 'si',
            'google_translation_code' => 'si',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sk',
            'label' => 'Slovenský',
            'rtl' => false,
            'hreflang_code' => 'sk',
            'google_translation_code' => 'sk',
            'microsoft_translation_code' => 'sk',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sl',
            'label' => 'Slovenski',
            'rtl' => false,
            'hreflang_code' => 'sl',
            'google_translation_code' => 'sl',
            'microsoft_translation_code' => 'sl',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sm',
            'label' => 'Samoa',
            'rtl' => false,
            'hreflang_code' => 'sm',
            'google_translation_code' => 'sm',
            'microsoft_translation_code' => 'sm',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sn',
            'label' => 'Shona',
            'rtl' => false,
            'hreflang_code' => 'sn',
            'google_translation_code' => 'sn',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'so',
            'label' => 'Soomaali',
            'rtl' => false,
            'hreflang_code' => 'so',
            'google_translation_code' => 'so',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sq',
            'label' => 'Shqiptar',
            'rtl' => false,
            'hreflang_code' => 'sq',
            'google_translation_code' => 'sq',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sr-cy',
            'label' => 'Српски (ћирилица)',
            'rtl' => false,
            'hreflang_code' => 'sr',
            'google_translation_code' => 'sr',
            'microsoft_translation_code' => 'sr-Cyrl',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sr-la',
            'label' => 'Српски (латински)',
            'rtl' => false,
            'hreflang_code' => 'sr',
            'google_translation_code' => 'sr',
            'microsoft_translation_code' => 'sr-Latn',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'su',
            'label' => 'Sunda',
            'rtl' => false,
            'hreflang_code' => 'su',
            'google_translation_code' => 'su',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'sv',
            'label' => 'Svenska',
            'rtl' => false,
            'hreflang_code' => 'sv',
            'google_translation_code' => 'sv',
            'microsoft_translation_code' => 'sv',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ta',
            'label' => 'தமிழ்',
            'rtl' => false,
            'hreflang_code' => 'ta',
            'google_translation_code' => 'ta',
            'microsoft_translation_code' => 'ta',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'te',
            'label' => 'Telugu',
            'rtl' => false,
            'hreflang_code' => 'te',
            'google_translation_code' => 'te',
            'microsoft_translation_code' => 'te',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'tg',
            'label' => 'Тоҷикистон',
            'rtl' => false,
            'hreflang_code' => 'tg',
            'google_translation_code' => 'tg',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'th',
            'label' => 'ไทย',
            'rtl' => false,
            'hreflang_code' => 'th',
            'google_translation_code' => 'th',
            'microsoft_translation_code' => 'th',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'tr',
            'label' => 'Türk',
            'rtl' => false,
            'hreflang_code' => 'tr',
            'google_translation_code' => 'tr',
            'microsoft_translation_code' => 'tr',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'uk',
            'label' => 'Український',
            'rtl' => false,
            'hreflang_code' => 'uk',
            'google_translation_code' => 'uk',
            'microsoft_translation_code' => 'uk',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'ur',
            'label' => 'اردو',
            'rtl' => true,
            'hreflang_code' => 'ur',
            'google_translation_code' => 'ur',
            'microsoft_translation_code' => 'ur',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'uz',
            'label' => 'O\'zbekiston',
            'rtl' => false,
            'hreflang_code' => 'uz',
            'google_translation_code' => 'uz',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'vi',
            'label' => 'Tiếng việt',
            'rtl' => false,
            'hreflang_code' => 'vi',
            'google_translation_code' => 'vi',
            'microsoft_translation_code' => 'vi',
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'xh',
            'label' => 'IsiXhosa',
            'rtl' => false,
            'hreflang_code' => 'xh',
            'google_translation_code' => 'xh',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'yi',
            'label' => 'ייִדיש',
            'rtl' => true,
            'hreflang_code' => 'yi',
            'google_translation_code' => 'yi',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'yo',
            'label' => 'Yoruba',
            'rtl' => false,
            'hreflang_code' => 'yo',
            'google_translation_code' => 'yo',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
        ],
        [
            'code' => 'zh-cn',
            'label' => '中文（简体）',
            'rtl' => false,
            'hreflang_code' => 'zh-cn',
            'google_translation_code' => 'zh-cn',
            'microsoft_translation_code' => 'zh-Hans',
            'deepl_translation_code' => 'zh',
            'hidden' => false
        ],
        [
            'code' => 'zh-tw',
            'label' => '中文（繁體）',
            'rtl' => false,
            'hreflang_code' => 'zh-tw',
            'google_translation_code' => 'zh-tw',
            'microsoft_translation_code' => 'zh-Hant',
            'deepl_translation_code' => 'zh',
            'hidden' => false
        ],
        [
            'code' => 'zu',
            'label' => 'Zulu',
            'rtl' => false,
            'hreflang_code' => 'zu',
            'google_translation_code' => 'zu',
            'microsoft_translation_code' => null,
            'deepl_translation_code' => null,
            'hidden' => false
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
    'redirect_root_domain' => 'browser', // browser|source|ip
    'basic_auth' => null, // provide basic auth (in the format "username:password") if you use Gtbabel in a password protected environment
    'translate_html' => true,
    'translate_html_include' => [
        [
            'selector' => '/html/body//text()',
            'attribute' => null,
            'context' => null,
            'comment' => 'Text nodes'
        ],
        [
            'selector' => '/html/body//a[starts-with(@href, \'mailto:\')]',
            'attribute' => 'href',
            'context' => 'email',
            'comment' => 'Email links'
        ],
        [
            'selector' => '/html/body//a[@href]',
            'attribute' => 'href',
            'context' => 'slug|file|url',
            'comment' => 'Links'
        ],
        [
            'selector' => '/html/body//form[@action]',
            'attribute' => 'action',
            'context' => 'slug|file|url',
            'comment' => 'Form actions'
        ],
        [
            'selector' => '/html/body//iframe[@src]',
            'attribute' => 'src',
            'context' => 'slug|file|url',
            'comment' => 'Iframe content'
        ],
        [
            'selector' => '/html/body//img[@alt]',
            'attribute' => 'alt',
            'context' => null,
            'comment' => 'Alt tags'
        ],
        [
            'selector' => '/html/body//*[@title]',
            'attribute' => 'title',
            'context' => null,
            'comment' => 'Title attributes'
        ],
        [
            'selector' => '/html/body//*[@placeholder]',
            'attribute' => 'placeholder',
            'context' => null,
            'comment' => 'Input placeholders'
        ],
        [
            'selector' => '/html/body//input[@type="submit"][@value]',
            'attribute' => 'value',
            'context' => null,
            'comment' => 'Submit values'
        ],
        [
            'selector' => '/html/body//input[@type="reset"][@value]',
            'attribute' => 'value',
            'context' => null,
            'comment' => 'Reset values'
        ],
        [
            'selector' => '/html/head//title',
            'attribute' => null,
            'context' => 'title',
            'comment' => 'Page title'
        ],
        [
            'selector' => '/html/head//meta[@name="description"][@content]',
            'attribute' => 'content',
            'context' => 'description',
            'comment' => 'Page description'
        ],
        [
            'selector' => '/html/head//link[@rel="canonical"][@href]',
            'attribute' => 'href',
            'context' => 'slug',
            'comment' => 'Canonical tags'
        ],
        [
            'selector' => '/html/body//img[@src]',
            'attribute' => 'src',
            'context' => 'file',
            'comment' => 'Image urls'
        ],
        [
            'selector' => '/html/body//img[@srcset]',
            'attribute' => 'srcset',
            'context' => 'file',
            'comment' => 'Image srcset urls'
        ],
        [
            'selector' => '/html/body//*[contains(@style, "url(")]',
            'attribute' => 'style',
            'context' => 'file',
            'comment' => 'Background images'
        ],
        [
            'selector' => '/html/body//*[@label]',
            'attribute' => 'label',
            'context' => null,
            'comment' => 'Labels'
        ],
        [
            'selector' => '/html/body//@*[contains(name(), \'text\')]/parent::*', // *text*
            'attribute' => '*text*', // *text*
            'context' => null,
            'comment' => 'Text attributes'
        ],
        [
            'selector' => '.example-link', // css selectors are also supported
            'attribute' => 'alt-href|*foo*', // wildcards and | supported
            'context' => 'slug|file|url'
        ]
    ],
    'translate_html_exclude' => [
        ['selector' => '.notranslate', 'comment' => 'Default class'],
        ['selector' => '[data-context]', 'attribute' => 'data-context', 'comment' => 'Data context attributes'],
        ['selector' => '.lngpicker', 'comment' => 'Language picker'],
        ['selector' => '.xdebug-error', 'comment' => 'Xdebug errors'],
        ['selector' => '.example1', 'attribute' => 'data-text', 'comment' => 'Example'],
        ['selector' => '.example2', 'attribute' => 'data-*', 'comment' => 'Example']
    ],
    'translate_html_force_tokenize' => [['selector' => '.force-tokenize', 'comment' => 'Default class']],
    'localize_js' => false,
    'localize_js_strings' => ['Schließen', '/blog'],
    'detect_dom_changes' => false,
    'detect_dom_changes_include' => [
        ['selector' => '.top-button', 'comment' => 'Top button'],
        ['selector' => '.swal-overlay', 'comment' => 'SweetAlert']
    ],
    'translate_xml' => true,
    'translate_xml_include' => [
        [
            'selector' => '//*[name()=\'loc\']',
            'attribute' => null,
            'context' => 'slug',
            'comment' => 'Sitemap links'
        ]
    ],
    'translate_json' => true,
    'translate_json_include' => [
        ['url' => '/path/in/source/lng/to/specific/page', 'selector' => ['key'], 'comment' => 'Example'],
        [
            'url' => 'wp-json/v1/*/endpoint',
            'selector' => ['key', 'nested.key', 'key.with.*.wildcard'],
            'comment' => 'Example'
        ]
    ],
    'translate_wp_localize_script' => true,
    'translate_wp_localize_script_include' => [
        ['selector' => 'key1_*.key2.*', 'comment' => 'Example'],
        ['selector' => 'key3_*.key4', 'comment' => 'Example']
    ],
    'prevent_publish_wp_new_posts' => false,
    'url_query_args' => [
        [
            'selector' => '*',
            'type' => 'keep',
            'comment' => 'Keep everything'
        ],
        [
            'selector' => 'nonce',
            'type' => 'discard',
            'comment' => 'Discard nonces'
        ]
        /*
        // if you want to translate strings (be careful with dynamic content)
        [
            'selector' => 's',
            'type' => 'translate',
            'comment' => 'Dynamic search term translation'
        ]
        */
    ],
    'exclude_urls_content' => [['url' => 'backend', 'comment' => 'Backend']],
    'exclude_urls_slugs' => [['url' => 'api/v1.0', 'comment' => 'API']],
    'exclude_stopwords' => ['Some specific string to exclude'],
    'html_lang_attribute' => true,
    'html_hreflang_tags' => true,
    'xml_hreflang_tags' => true,
    'show_language_picker' => false,
    'show_frontend_editor_links' => false, // common wordpress configuration: 'show_frontend_editor_links' => user_is_logged_in()
    'debug_translations' => false, // surround outputted translations with "%|%" to see borders
    'auto_add_translations' => true,
    'auto_set_new_strings_checked' => false,
    'auto_set_discovered_strings_checked' => false,
    'unchecked_strings' => 'trans', // trans|source|hide
    'auto_translation' => true,
    'auto_translation_service' => [
        [
            'provider' => 'google', // google|microsoft|deepl or any other custom name
            'api_keys' => [], // use a string or array of strings to provide multiple keys at once
            'throttle_chars_per_month' => 1000000,
            'lng' => null, // ['en', 'fr'] populate if provider should act only on one or multiple specific languages
            'label' => null, // custom label
            'api_url' => null, // if you use your own custom translation provider, add here a custom GET api endpoint
            'disabled' => false
        ]
    ],
    'discovery_log' => false,
    'frontend_editor' => false, // common wordpress configuration: 'frontend_editor' => user_is_logged_in()
    'wp_mail_notifications' => false
];
```

## Other usage

Gtbabel catches the content between `start()` and `stop()` with [output buffering](https://www.php.net/manual/de/function.ob-start.php).\
However, you can also use Gtbabel more directly:

```php
$gtbabel = new Gtbabel();

$gtbabel->config([...]);

$gtbabel->translate('<p>Dies ist ein Test!</p>'); // <p>This is a test!</p>
$gtbabel->translate('datenschutz', 'en', 'de', 'slug'); // data-protection

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
-   Fine grained control publish status of posts/pages
-   Possibility to replace files per language via the media library
-   Multisite support
-   Flexible permission system based on capabilities
-   Prevents multiple entries of different thumbnail sizes

### Permissions

Gtbabel for WordPress uses the following capabilities:

-   `gtbabel__edit_settings`: capability to edit settings
-   `gtbabel__email_notifications`: capability to receive email notifications
-   `gtbabel__translation_list`: capability to use the list of translations
-   `gtbabel__translation_assistant`: capability to use the translation assistant
-   `gtbabel__translation_frontendeditor`: capability to use the frontend editor
-   `gtbabel__translate_xx`: capability to translate the language with code `xx`

By default, administrators have all capabilities (except `gtbabel__email_notifications`).\
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

### Email notifications

With the `wp_mail_notifications` option you can instruct Gtbabel to send regular status e-mails to all translators (that have both the `gtbabel__translation_assistant` and `gtbabel__email_notifications` role):

-   `false`: No status e-mails are sent
-   `'hourly'`: Status e-mails are sent once every hour
-   `'twicedaily'`: Status e-mails are sent twice a day
-   `'daily'`: Status e-mails are sent once every day
-   `'weekly'`: Status e-mails are sent once every week

Make sure that [WP-Cron](https://developer.wordpress.org/plugins/cron/) is enabled and running regularly and mails can be sent properly from your server.

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

| css selector                    | text | attribute        | context                 |
| ------------------------------- | ---- | ---------------- | ----------------------- |
| `body *`                        | ✓    | ✗                | `auto`                  |
| `body a`                        | ✗    | `href^="mailto"` | `email`                 |
| `body a`                        | ✗    | `href`           | `'slug'\|'file'\|'url'` |
| `body form`                     | ✗    | `action`         | `'slug'\|'file'\|'url'` |
| `iframe`                        | ✗    | `src`            | `'slug'\|'file'\|'url'` |
| `body img`                      | ✗    | `alt`            | `auto`                  |
| `body *`                        | ✗    | `title`          | `auto`                  |
| `body *`                        | ✗    | `placeholder`    | `auto`                  |
| `body input[type="submit"]`     | ✗    | `value`          | `auto`                  |
| `body input[type="reset"]`      | ✗    | `value`          | `auto`                  |
| `head title`                    | ✓    | ✗                | `'title'`               |
| `head meta[name="description"]` | ✗    | `content`        | `'description'`         |
| `head link[rel="canonical"]`    | ✗    | `href`           | `'slug'`                |
| `body img`                      | ✗    | `src`            | `'file'`                |
| `body img`                      | ✗    | `srcset`         | `'file'`                |
| `body *`                        | ✗    | `style`          | `'file'`                |
| `body *`                        | ✗    | `label`          | `auto`                  |
| `body *`                        | ✗    | `*text*`         | `auto`                  |

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

If you want to influence that behaviour, use the `translate_html_force_tokenize` option and provide the selector of the parent element in order to not tokenize its children.

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

`I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.`.

Note that in the following example, these hints are not necessary (Gtbabel can match the tags despite a different order):

`Das deutsche <a href="https://1.com">Brot</a> <span>vermisse</span> ich am meisten.`

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
        <div>Inhalt auf Französisch.</div>
        <p>Einige andere Inhalte in Englisch.</p>
    </body>
</html>
```

In WordPress there is a convenient way of defining a different source language\
for whole posts or pages or even on block level.

## Hiding content

Besides providing content in different source languages, you can hide specific dom nodes entirely:

```html
<ul>
    <li>I am rendered everywhere</li>
    <li class="hide-block">I am missing everywhere (including source language)</li>
    <li class="hide-block-target">I am rendered only in the source language</li>
    <li class="hide-block-source">I am missing only in the source language</li>
    <li class="hide-block-en-fr">
        I am missing specifically in the languages `en` and `fr` and rendered everywhere else
    </li>
</ul>
```

Note that _rendered_ means completely missing from the DOM.

This is (in conjunction with the css classes `notranslate` to prevent translation and `force-tokenize` to force tokenization and the `lang`-attribute) an attempt to get around the intended limitation of Gtbabel that all content is always translated into all languages.

In WordPress you individually can configure in which languages a specific page should be translated. An exclusive view for logged-in users is also possible – this allows you to prepare translations in quietly before they are available to the public.

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

In order to not prefix your source language (which is often common), just provide a special rule for it\
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

And if you don\'t even use pretty links, leave all prefixes empty (the url query argument `lang` decides then):

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
        'url_prefix' => ''
    ]
    /* ... */,
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

With the `unchecked_strings` option you can control what happens with unchecked strings:

-   `trans`: Show translation (which still needs to be checked)
-   `source`: Show source (show the string in the source language)
-   `hide`: Hide string (completely hide string until its checked)

This way you can build an approval system in your backend, where translators can check strings before they are published.

One common approach is to dynamically override this setting if a user is logged in:

```php
/* ... */
'unchecked_strings' => is_user_logged_in() ? 'trans' : 'hide'
/* ... */
```

## Flow diagram

![Flow diagram](gtbabel.drawio.svg)

## Language picker

To output a language picker, just use this snippet:

```php
if (function_exists('gtbabel_languagepicker')) {
    echo '<ul class="lngpicker">';
    foreach (gtbabel_languagepicker() as $val) {
        echo '<li>';
        echo '<a href="' . $val['url'] . '"' . ($val['active'] ? ' class="active"' : '') . '>';
        echo $val['label'];
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

## Echoing strings

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

However, you can also get a translation in a specific context:

```php
gtbabel__('datenschutz', null, null, 'slug'); // 'data-protection'
```

## Dynamic data

Gtbabel translates your frontend, json responses and watches dom changes.\
Nevertheless, one has to manually take care of how multilingual input data should be further processed.

### Search

Doing search on translated content is hard, but can be addressed with different approaches:

#### Approach 1: Translating the search term

A quite effective and easy approach is to translate all incoming search queries into your source language\
before actually querying your content (provided in source language):

```php
$s = gtbabel__($s, gtbabel_source_lng(), gtbabel_current_lng(), 'search_term');
```

As you can see, such search queries get the special context `search_term`
so they can be filtered out or garbage collected later on.

The caveat here is that you essentially translate all incoming queries,
so be aware of that and use the `throttle_chars_per_month` option.

The results are also not accurate, because previously unseen queries are always translated ad-hoc.
Also it does not work for content in multiple source languages.

#### Approach 2: Searching in parsed html

Another approach is to search generated cached html pages.\
Since Gtbabel does not cache pages, you have to have your caching system running.

Translated pages can be basically treated like pages in source language,\
since they are both just html, which can be searched.

Also evaluate if you can try to use the [Google Programmable Search Engine](https://developers.google.com/custom-search), which follows the same idea.

#### Approach 3: Building a custom search index

In this approach you build a search index for every object\
you want to include into the search results.

You collect and permanently store all strings in the source language,\
translate them (once) by Gtbabel and also permanently store these translations.

This way you can combine the best of the above approaches,\
although it involves the largest integration effort.

#### Approach 4: Searching in translations

One approach is to use the search term (not in source language) and\
search inside the translated content instead.

You have to modify your search queries manually – in SQL this could look like this:

Before:

```sql
SELECT posts.id, posts.col1, posts.col2
FROM posts
WHERE posts.col1 = 'English search term' OR posts.col2 = 'English search term';
```

After:

```sql
SELECT posts.id, posts.col1, posts.col2
FROM posts
LEFT JOIN translations ON translations.lng_target = 'en' AND (translations.str = wp_posts.col1 OR translations.str = wp_posts.col2)
GROUP BY posts.ID
HAVING FIND_IN_SET('English search term', GROUP_CONCAT(translations.trans));
```

This idea can be expanded significantly (e.g. by using regex patterns).\
Note that this also works for content in different source languages.\
In WordPress this approach is implemented and works automatically – providing full text search on all meta fields.

### URL query arguments

As for url query arguments, you have fine grained control over whether arguments should be kept, translated or discarded via the `url_query_args`-option.

Be aware: If you set a key to `translate`, external content potentially gets automatically translated.

Furthermore url hashes (beginning with `#`) are always kept.

Note that query arguments for email links get automatically translated:

`<a href="mailto:?subject=Dies%20ist%20ein%20Test&amp;body=Das%20funktioniert">E-Mail senden</a>`

gets transformed to

`<a href="mailto:?subject=This%20is%20a%20test&amp;body=That%20work%27s">Send email</a>`

### Mails

If you send mails from the backend, ensure to translate the subject and body properly:

```php
$body = gtbabel__($body);
$message = gtbabel__($message);
```

If the mails consist of personal data, make sure to send these emails as html and properly exclude dynamic content:

```php
// before
$message = 'Hello John Doe,';
// after
$message = 'Hello <span class="notranslate force-tokenize">John Doe</span>,';
```

Gtbabel automatically translates all mails sent in WordPress and has built in support for\
popular plugins like [Contact Form 7](https://de.wordpress.org/plugins/contact-form-7/) and [WooCommerce](https://de.wordpress.org/plugins/woocommerce/) to ensure the above guidelines.

### Comments

If you offer a comment function on your website, just save the incoming comments in the input language (that can be determined via `gtbabel_current_lng()` or `gtbabel_referer_lng()`). Then output just those comments that match your current input language or even auto translate all of them using the `lang`-tag (see **Multiple source languages**).

## Language codes

It is recommended to use iso language codes in lowercase.\
But you can use any language code you want (even [i-klingon](https://en.wikipedia.org/wiki/Klingon_language)).\
For every language, you should provide language codes that are used for auto translation. If a language is not supported in your translation service, use `null`.\
Country/region codes (like "BR" in "pt_BR") should be used in the (unique) `code`-attribute with a dash: `pt-br`. Gtbabel by default does not distinct between English (British) and English (American), however you can simply add a new language or instruct Gtbabel to use a specific language code (e.g. for automatic translation).\
Hreflang codes must be in [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes), so some languages don't have an official hreflang code in the settings array.

## Google Translation API

-   Go to [Google API Console](https://console.cloud.google.com/apis)
-   Create a new project
-   Marketplace > Enable "Cloud Translation API" (this requires you to setup a billing account)
-   APIs and services > API credentials > Add a new api key

## API usage costs

The translation apis of Google, Microsoft or DeepL can be costly. Try to keep track of your current usage stats. Gtbabel helps you by tracking the total amount of translated chars. You can also provide multiple api keys, Gtbabel then distributes the calls uniformly.

You can throttle the amount of chars with the option `throttle_chars_per_month`.

## Custom translation providers

If you don't want to use the big three in translation, you can specify a special API URL in the `auto_translation_service` field instead. An example entry would look like this:

```php
[
    'provider' => 'custom_provider',
    'api_keys' => ['1337'],
    'throttle_chars_per_month' => 1000000,
    'lng' => null,
    'label' => 'My custom provider',
    'api_url' => 'https://tld.com/api/?str=%str%&from=%lng_source%&to=%lng_target%&api_key=%api_key%'
    'disabled' => false
]
```

Gtbabel then fires a GET-request to that url and fills in the placeholders automatically.\
It can then accept and process common JSON request responses.

## Caveats

-   Gtbabel is good at translating whole pages. If you want to provide different content for different languages, Gtbabel might be the wrong tool (however you _can_ use `if( gtbabel_current_lng() === 'other-lng' ) { echo 'content in source language'; }` and/or the concept of **Multiple source languages** in your templates to achieve that).
-   The source language acts as a base for every other language (however, you _can_ mark parts of a page with a different source language).
-   Gtbabel is designed to translate every part of the page without code changes (however you can exclude translations with the `translate_html_exclude`-option).

## Credits

Greetings to my coworker, without whom this project would not have been initiated.
