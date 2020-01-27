<?php
namespace vielhuber\gtbabel;

use Gettext\Generator\PoGenerator;
use Gettext\Generator\MoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Loader\MoLoader;
use Gettext\Translations;
use Gettext\Translation;

class Gettext
{
    public $gettext;
    public $gettext_cache;
    public $gettext_cache_reverse;
    public $gettext_pot;
    public $gettext_pot_cache;
    public $gettext_save_counter;

    public $utils;
    public $host;
    public $settings;

    function __construct(Utils $utils = null, Host $host = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
    }

    function preloadGettextInCache()
    {
        $this->gettext = [];
        $this->gettext_cache = [];
        $this->gettext_cache_reverse = [];
        $this->gettext_pot = [];
        $this->gettext_pot_cache = [];
        $this->gettext_save_counter = [];

        $poLoader = new PoLoader();
        $moLoader = new MoLoader();

        // pot
        $filename = $this->getLngFilename('pot', '_template');
        $this->gettext_save_counter['pot'] = false;
        if (!file_exists($filename)) {
            $this->gettext_pot = Translations::create('gtbabel');
        } else {
            $this->gettext_pot = $poLoader->loadFile($filename);
        }
        foreach ($this->gettext_pot->getTranslations() as $gettext__value) {
            $context = $gettext__value->getContext() ?? '';
            $str = $gettext__value->getOriginal();
            $this->gettext_pot_cache[$context][$str] = null;
        }

        // po
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $this->gettext_save_counter['po'][$languages__value] = false;
            $this->gettext_cache[$languages__value] = [];
            $this->gettext_cache_reverse[$languages__value] = [];
            if (!file_exists($this->getLngFilename('mo', $languages__value))) {
                $this->gettext[$languages__value] = Translations::create('gtbabel');
            } else {
                $this->gettext[$languages__value] = $moLoader->loadFile($this->getLngFilename('mo', $languages__value));
            }
            foreach ($this->gettext[$languages__value]->getTranslations() as $gettext__value) {
                $context = $gettext__value->getContext() ?? '';
                $this->gettext_cache[$languages__value][$context][$gettext__value->getOriginal()] = $gettext__value->getTranslation();
                $this->gettext_cache_reverse[$languages__value][$context][$gettext__value->getTranslation()] = $gettext__value->getOriginal();
            }
        }
    }

    function generateGettextFiles()
    {
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();

        if ($this->gettext_save_counter['pot'] === true) {
            $poGenerator->generateFile($this->gettext_pot, $this->getLngFilename('pot', '_template'));
        }

        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            if ($this->gettext_save_counter['po'][$languages__value] === false) {
                continue;
            }
            $poGenerator->generateFile($this->gettext[$languages__value], $this->getLngFilename('po', $languages__value));
            $moGenerator->generateFile($this->gettext[$languages__value], $this->getLngFilename('mo', $languages__value));
        }
    }

    function getExistingTranslationFromCache($str, $lng, $context = null)
    {
        if (
            $str === '' ||
            $str === null ||
            $this->gettext_cache[$lng] === null ||
            !array_key_exists($context ?? '', $this->gettext_cache[$lng]) ||
            !array_key_exists($str, $this->gettext_cache[$lng][$context ?? '']) ||
            $this->gettext_cache[$lng][$context ?? ''][$str] === ''
        ) {
            return false;
        }
        return $this->gettext_cache[$lng][$context ?? ''][$str];
    }

    function getExistingTranslationReverseFromCache($str, $lng, $context = null)
    {
        if (
            $str === '' ||
            $str === null ||
            $this->gettext_cache_reverse[$lng] === null ||
            !array_key_exists($context ?? '', $this->gettext_cache_reverse[$lng]) ||
            !array_key_exists($str, $this->gettext_cache_reverse[$lng][$context ?? '']) ||
            $this->gettext_cache_reverse[$lng][$context ?? ''][$str] === ''
        ) {
            return false;
        }
        return $this->gettext_cache_reverse[$lng][$context ?? ''][$str];
    }

    function addStringToPotFileAndToCache($str, $context)
    {
        $translation = Translation::create($context, $str);
        $translation->translate('');
        $this->gettext_pot->add($translation);
        $this->gettext_pot_cache[$context][$str] = null;
        $this->gettext_save_counter['pot'] = true;
    }

    function addTranslationToPoFileAndToCache($orig, $trans, $lng, $context = null)
    {
        $translation = Translation::create($context, $orig);
        $translation->translate($trans);
        $this->gettext[$lng]->add($translation);
        $this->gettext_cache[$lng][$context ?? ''][$orig] = $trans;
        $this->gettext_cache_reverse[$lng][$context ?? ''][$trans] = $orig;
        $this->gettext_save_counter['po'][$lng] = true;
    }

    function getLngFolder()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . trim($this->settings->get('lng_folder'), '/');
    }

    function getLngFilename($type, $lng)
    {
        return $this->getLngFolder() . '/' . $lng . '.' . $type;
    }

    function deletePotPoMoFiles()
    {
        $files = glob($this->getLngFolder() . '/*'); // get all file names
        foreach ($files as $files__value) {
            if (is_file($files__value)) {
                if (strpos($files__value, '.pot') !== false || strpos($files__value, '.po') !== false || strpos($files__value, '.mo') !== false) {
                    @unlink($files__value);
                }
            }
        }
    }

    function reset()
    {
        $this->deletePotPoMoFiles();
    }

    function createLngFolderIfNotExists()
    {
        if (!is_dir($this->getLngFolder())) {
            mkdir($this->getLngFolder(), 0777, true);
        }
    }

    function getLanguages()
    {
        return $this->settings->get('languages');
    }

    function getDefaultLanguages()
    {
        // https://cloud.google.com/translate/docs/languages?hl=de
        return [
            'de',
            'en',
            'fr',
            'af',
            'am',
            'ar',
            'az',
            'be',
            'bg',
            'bn',
            'bs',
            'ca',
            'ceb',
            'co',
            'cs',
            'cy',
            'da',
            'el',
            'eo',
            'es',
            'et',
            'eu',
            'fa',
            'fi',
            'fy',
            'ga',
            'gd',
            'gl',
            'gu',
            'ha',
            'haw',
            'he',
            'hi',
            'hmn',
            'hr',
            'ht',
            'hu',
            'hy',
            'id',
            'ig',
            'is',
            'it',
            'ja',
            'jw',
            'ka',
            'kk',
            'km',
            'kn',
            'ko',
            'ku',
            'ky',
            'la',
            'lb',
            'lo',
            'lt',
            'lv',
            'mg',
            'mi',
            'mk',
            'ml',
            'mn',
            'mr',
            'ms',
            'mt',
            'my',
            'ne',
            'nl',
            'no',
            'ny',
            'pa',
            'pl',
            'ps',
            'pt',
            'ro',
            'ru',
            'sd',
            'si',
            'sk',
            'sl',
            'sm',
            'sn',
            'so',
            'sq',
            'sr',
            'st',
            'su',
            'sv',
            'sw',
            'ta',
            'te',
            'tg',
            'th',
            'tl',
            'tr',
            'uk',
            'ur',
            'uz',
            'vi',
            'xh',
            'yi',
            'yo',
            'zh-cn',
            'zh-tw',
            'zu'
        ];
    }

    function getLanguagesWithoutSource()
    {
        $lng = [];
        foreach ($this->getLanguages() as $languages__value) {
            if ($languages__value === $this->getSourceLng()) {
                continue;
            }
            $lng[] = $languages__value;
        }
        return $lng;
    }

    function getCurrentPrefix()
    {
        foreach ($this->getLanguages() as $languages__value) {
            if (strpos($this->host->getCurrentPath(), '/' . $languages__value) === 0) {
                return $languages__value;
            }
        }
        return null;
    }

    function getCurrentLng()
    {
        if ($this->settings->get('lng_target') !== null) {
            return $this->settings->get('lng_target');
        }
        return $this->getCurrentPrefix() ?? $this->settings->get('lng_source');
    }

    function getLanguagePickerData()
    {
        $data = [];
        foreach ($this->getLanguages() as $languages__value) {
            $url = $this->getCurrentUrlTranslationsInLanguage($languages__value);
            $data[] = [
                'lng' => $languages__value,
                'url' => $url,
                'active' => rtrim($url, '/') === rtrim($this->host->getCurrentUrl(), '/')
            ];
        }
        return $data;
    }

    function sourceLngIsCurrentLng()
    {
        if ($this->getCurrentLng() === $this->getSourceLng()) {
            return true;
        }
        return false;
    }

    function getSourceLng()
    {
        return $this->settings->get('lng_source');
    }

    function prepareTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        if ($context === 'slug') {
            $link = $orig;
            if ($link === null || trim($link) === '') {
                return $link;
            }
            if (strpos(trim($link, '/'), '#') === 0) {
                return $link;
            }
            $is_absolute_link = strpos($link, $this->host->getCurrentHost()) === 0;
            if (strpos($link, 'http') !== false && $is_absolute_link === false) {
                return $link;
            }
            if (strpos($link, 'http') === false && strpos($link, ':') !== false) {
                return $link;
            }
            $link = str_replace([$this->host->getCurrentHost() . '/' . $this->getSourceLng(), $this->host->getCurrentHost()], '', $link);
            $url_parts = explode('/', $link);
            foreach ($url_parts as $url_parts__key => $url_parts__value) {
                if ($this->stringShouldNotBeTranslated($url_parts__value, 'slug')) {
                    continue;
                }
                $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamicallyIfNeeded($url_parts__value, $lng, 'slug');
            }
            $link = implode('/', $url_parts);
            $link = '/' . $lng . '' . $link;
            if ($is_absolute_link === true) {
                $link = $this->host->getCurrentHost() . $link;
            }
            return $link;
        }
        if ($context === 'title') {
            foreach (['-', '–', '—', ':', '·', '•', '*', '⋆', '|', '~', '«', '»', '<', '>'] as $delimiters__value) {
                if (strpos($orig, ' ' . $delimiters__value . ' ') !== false) {
                    $orig_parts = explode(' ' . $delimiters__value . ' ', $orig);
                    foreach ($orig_parts as $orig_parts__key => $orig_parts__value) {
                        $trans = $this->getTranslationAndAddDynamicallyIfNeeded($orig_parts__value, $lng, $context);
                        $orig_parts[$orig_parts__key] = $trans;
                    }
                    $trans = implode(' ' . $delimiters__value . ' ', $orig_parts);
                    return $trans;
                }
            }
        }
        return $this->getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context);
    }

    function getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        $trans = $this->getExistingTranslationFromCache($orig, $lng, $context);
        if ($trans === false) {
            $this->addStringToPotFileAndToCache($orig, $context);
            $trans = $this->autoTranslateString($orig, $lng, $context);
            if ($this->settings->get('auto_translation') === true) {
                $this->addTranslationToPoFileAndToCache($orig, $trans, $lng, $context);
            }
        }
        return $trans;
    }

    function autoTranslateString($orig, $to_lng, $context = null, $from_lng = null)
    {
        if ($this->settings->get('auto_translation') === true && $this->settings->get('auto_translation_service') === 'google') {
            $trans = $this->translateStringWithGoogle($orig, $to_lng, $context, $from_lng);
        } else {
            $trans = $this->translateStringMock($orig, $to_lng, $context, $from_lng);
        }
        return $trans;
    }

    function placeholderConversionIn($str)
    {
        $mappingTable = [];
        preg_match_all('/<[a-zA-Z](.*?[^?])?>|<\/[^<>]*>/', $str, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $matches__value) {
                $pos_begin = 1;
                $pos_end = strrpos($matches__value, '>');
                foreach (['/', ' '] as $alt__value) {
                    $pos_end_ = strpos($matches__value, $alt__value, $pos_begin + 1);
                    if ($pos_end_ !== false && $pos_end_ < $pos_end) {
                        $pos_end = $pos_end_;
                    }
                }
                $placeholder = '<' . substr($matches__value, $pos_begin, $pos_end - $pos_begin) . '>';
                $str = $this->utils->strReplaceFirst($matches__value, $placeholder, $str);
                $mappingTable[] = [$placeholder, $matches__value];
            }
        }
        return [$str, $mappingTable];
    }

    function placeholderConversionOut($str, $mappingTable)
    {
        foreach ($mappingTable as $mappingTable__value) {
            $str = $this->utils->strReplaceFirst($mappingTable__value[0], $mappingTable__value[1], $str);
        }
        return $str;
    }

    function formatTextFromTextNode($str)
    {
        $str = trim($str);
        $str = str_replace('&#13;', '', $str); // replace nasty carriage returns \r
        $parts = explode(PHP_EOL, $str);
        foreach ($parts as $parts__key => $parts__value) {
            if (trim($parts__value) == '') {
                unset($parts[$parts__key]);
            } else {
                $parts[$parts__key] = trim($parts__value);
            }
        }
        $str = implode(' ', $parts);
        return $str;
    }

    function translateStringMock($str, $to_lng, $context = null, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->getSourceLng();
        }
        if ($context === 'slug') {
            $pos = mb_strlen($str) - mb_strlen('-' . $from_lng);
            if (strrpos($str, '-' . $from_lng) === $pos) {
                $str = substr($str, 0, $pos);
            }
            if ($to_lng === $this->getSourceLng()) {
                return $str;
            }
            return $str . '-' . $to_lng;
        }
        if ($this->settings->get('debug_mode') === true) {
            return '%|%' . $str . '%|%' . $to_lng . '%|%';
        }
        return $str . '-' . $to_lng;
    }

    function translateStringWithGoogle($str, $to_lng, $context = null, $from_lng = null)
    {
        $apiKey = $this->settings->get('google_translation_api_key');
        $url =
            'https://www.googleapis.com/language/translate/v2?key=' .
            $apiKey .
            '&q=' .
            rawurlencode($str) .
            '&source=' .
            ($from_lng === null ? $this->getSourceLng() : $from_lng) .
            '&target=' .
            $to_lng;
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        $responseDecoded = json_decode($response, true);
        curl_close($handle);
        if (@$responseDecoded['data']['translations'][0]['translatedText'] != '') {
            $trans = $responseDecoded['data']['translations'][0]['translatedText'];
        } else {
            $trans = $str;
        }

        // the api returns some characters in their html characters form (e.g. "'" is returned as "&#39;")
        // we want to store the real values
        $trans = html_entity_decode($trans, ENT_QUOTES);

        // uppercase
        // the google translation api does a very bad job at keeping uppercased words at the beginning
        // we fix this here
        if ($this->utils->firstCharIsUppercase($str) && !$this->utils->firstCharIsUppercase($trans)) {
            $trans = $this->utils->setFirstCharUppercase($trans);
        }

        // slugify
        if ($context === 'slug') {
            $trans = $this->utils->slugify($trans, $str, $to_lng);
        }

        return $trans;
    }

    function stringShouldNotBeTranslated($str, $context = null)
    {
        $str = trim($str);
        $str = trim($str, '"');
        $str = trim($str, '\'');
        if ($str == '') {
            return true;
        }
        if (is_numeric($str)) {
            return true;
        }
        if (mb_strlen($str) === 1 && preg_match('/[^a-zA-Z]/', $str)) {
            return true;
        }
        foreach ($this->getLanguages() as $languages__value) {
            if ($languages__value === trim(strtolower($str))) {
                return true;
            }
        }
        if ($context === 'slug' && strpos($str, '#') === 0) {
            return true;
        }
        // detect paths to php scripts
        if (strpos($str, ' ') === false && strpos($str, '.php') !== false) {
            return true;
        }
        // detect print_r outputs
        if (strpos($str, '(') === 0 && strrpos($str, ')') === mb_strlen($str) - 1 && strpos($str, '=') !== false) {
            return true;
        }
        return false;
    }

    function getCurrentUrlTranslationsInLanguage($lng)
    {
        return trim(trim($this->host->getCurrentHost(), '/') . '/' . trim($this->getCurrentPathTranslationsInLanguage($lng, false), '/'), '/') . '/';
    }

    function getTranslationInForeignLng($str, $to_lng, $from_lng = null, $context = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->getCurrentLng();
        }
        if ($from_lng === $this->getSourceLng()) {
            $str_in_source_lng = $str;
        } else {
            $str_in_source_lng = $this->getExistingTranslationReverseFromCache($str, $from_lng, $context); // str in source lng
        }
        if ($str_in_source_lng === false) {
            return false;
        }
        if ($to_lng === $this->getSourceLng()) {
            return $str_in_source_lng;
        }
        $trans = $this->getExistingTranslationFromCache($str_in_source_lng, $to_lng, $context);
        return $trans;
    }

    function getTranslationInForeignLngAndAddDynamicallyIfNeeded($str, $to_lng = null, $from_lng = null, $context = null)
    {
        if ($to_lng === null) {
            $to_lng = $this->getCurrentLng();
        }
        if ($from_lng === null) {
            $from_lng = $this->getSourceLng();
        }
        $trans = $this->getTranslationInForeignLng($str, $to_lng, $from_lng, $context);
        if ($trans === false) {
            $str_in_source = $this->autoTranslateString($str, $this->getSourceLng(), $context, $from_lng);
            $this->addStringToPotFileAndToCache($str_in_source, $context);
            $trans = $this->autoTranslateString($str, $to_lng, $context);
            if ($this->settings->get('auto_translation') === true) {
                $this->addTranslationToPoFileAndToCache($str_in_source, $str, $from_lng, $context);
                $this->addTranslationToPoFileAndToCache($str_in_source, $trans, $to_lng, $context);
            }
        }
        return $trans;
    }

    function getCurrentPathTranslationsInLanguage($lng, $always_remove_prefix = false)
    {
        $url = $this->host->getCurrentPath();
        if ($this->getCurrentLng() === $lng) {
            return $url;
        }
        $url_parts = explode('/', $url);
        foreach ($url_parts as $url_parts__key => $url_parts__value) {
            if ($url_parts[$url_parts__key] == '') {
                unset($url_parts[$url_parts__key]);
            }
        }
        $url_parts = array_values($url_parts);

        // prefix
        if ($always_remove_prefix === true || ($this->getSourceLng() === $lng && $this->settings->get('prefix_source_lng') === false)) {
            if (@$url_parts[0] === $this->getCurrentLng()) {
                unset($url_parts[0]);
            }
        } else {
            if (@$url_parts[0] === $this->getCurrentLng()) {
                $url_parts[0] = $lng;
            } else {
                array_unshift($url_parts, $lng);
            }
        }

        foreach ($url_parts as $url_parts__key => $url_parts__value) {
            if (in_array($url_parts__value, $this->getLanguages())) {
                continue;
            }
            $trans = $this->getTranslationInForeignLng($url_parts__value, $lng, null, 'slug');
            // if translation is not yet available, also provide default translation (in case auto translation is disabled)
            if ($trans === false && $this->settings->get('auto_translation') === false) {
                $trans = $this->autoTranslateString($url_parts__value, $lng, 'slug', $this->getCurrentLng());
            }
            if ($trans !== false) {
                $url_parts[$url_parts__key] = $trans;
            }
        }
        $url = implode('/', $url_parts);
        return $url;
    }

    function addCurrentUrlToTranslations()
    {
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $this->prepareTranslationAndAddDynamicallyIfNeeded($this->host->getCurrentUrl(), $languages__value, 'slug');
        }
    }
}
