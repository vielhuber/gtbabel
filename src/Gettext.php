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
    public $tags;
    public $log;
    public $publish;

    function __construct(
        Utils $utils = null,
        Host $host = null,
        Settings $settings = null,
        Tags $tags = null,
        Log $log = null,
        Publish $publish = null
    ) {
        $this->utils = $utils ?: new Utils();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->tags = $tags ?: new Tags();
        $this->log = $log ?: new Log();
        $this->publish = $publish ?: new Publish();
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
            $this->gettext_pot_cache[$context][$gettext__value->getOriginal()] = null;
        }

        // po
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            $this->gettext_save_counter['po'][$languages__value] = false;
            $this->gettext_cache[$languages__value] = [];
            $this->gettext_cache_reverse[$languages__value] = [];
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                $this->gettext[$languages__value] = Translations::create('gtbabel');
            } else {
                $this->gettext[$languages__value] = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            }
            foreach ($this->gettext[$languages__value]->getTranslations() as $gettext__value) {
                $context = $gettext__value->getContext() ?? '';
                $this->gettext_cache[$languages__value][$context][
                    $gettext__value->getOriginal()
                ] = $gettext__value->getTranslation();
                $this->gettext_cache_reverse[$languages__value][$context][
                    $gettext__value->getTranslation()
                ] = $gettext__value->getOriginal();
            }
        }
    }

    function generateGettextFiles()
    {
        if ($this->settings->get('auto_add_translations_to_gettext') === false) {
            return;
        }

        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();

        if ($this->gettext_save_counter['pot'] === true) {
            $poGenerator->generateFile($this->gettext_pot, $this->getLngFilename('pot', '_template'));
        }

        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if ($this->gettext_save_counter['po'][$languages__value] === false) {
                continue;
            }
            $poGenerator->generateFile(
                $this->gettext[$languages__value],
                $this->getLngFilename('po', $languages__value)
            );
            $moGenerator->generateFile(
                $this->gettext[$languages__value],
                $this->getLngFilename('mo', $languages__value)
            );
        }
    }

    function convertPoToMo($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }
        $loader = new PoLoader();
        $translations = $loader->loadFile($filename);
        $generator = new MoGenerator();
        $generator->generateFile($translations, str_replace('.po', '.mo', $filename));
        return true;
    }

    function getExistingTranslationFromCache($str, $lng, $context = null)
    {
        // track discovery
        $this->log->discoveryLogAdd($this->host->getCurrentUrlWithArgs(), $str, $context);

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

    function getAllTranslationsFromFiles()
    {
        $data = [];
        $poLoader = new PoLoader();
        $moLoader = new MoLoader();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $data;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        foreach ($pot->getTranslations() as $gettext__value) {
            $data[$this->getTranslationHash($gettext__value)] = [
                'orig' => $gettext__value->getOriginal(),
                'context' => $gettext__value->getContext() ?? '',
                'translations' => []
            ];
        }
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if (!file_exists($this->getLngFilename('mo', $languages__value))) {
                continue;
            }
            $mo = $moLoader->loadFile($this->getLngFilename('mo', $languages__value));
            foreach ($mo->getTranslations() as $gettext__value) {
                $data[$this->getTranslationHash($gettext__value)]['translations'][
                    $languages__value
                ] = $gettext__value->getTranslation();
            }
        }
        uasort($data, function ($a, $b) {
            if ($a['context'] != $b['context']) {
                return strcmp($a['context'], $b['context']);
            }
            return strcmp($a['orig'], $b['orig']);
        });
        return $data;
    }

    function getTranslationHash($gettext)
    {
        return md5($gettext->getOriginal() . '#' . ($gettext->getContext() ?? ''));
    }

    function editTranslationFromFiles($hash, $str, $lng)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('po', $lng))) {
            return $success;
        }
        $po = $poLoader->loadFile($this->getLngFilename('po', $lng));
        foreach ($po->getTranslations() as $gettext__value) {
            if ($this->getTranslationHash($gettext__value) !== $hash) {
                continue;
            }
            $gettext__value->translate($str);
            $success = true;
        }
        if ($success === true) {
            $poGenerator->generateFile($po, $this->getLngFilename('po', $lng));
            $moGenerator->generateFile($po, $this->getLngFilename('mo', $lng));
        }
        return $success;
    }

    function deleteTranslationFromFiles($hash)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $success;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        $to_remove = null;
        foreach ($pot->getTranslations() as $gettext__value) {
            if ($this->getTranslationHash($gettext__value) !== $hash) {
                continue;
            }
            $to_remove = $gettext__value;
            break;
        }
        if ($to_remove !== null) {
            $pot->remove($to_remove);
            $poGenerator->generateFile($pot, $this->getLngFilename('pot', '_template'));
            $success = true;
        }

        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                continue;
            }
            $po = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            $to_remove = null;
            foreach ($po->getTranslations() as $gettext__value) {
                if ($this->getTranslationHash($gettext__value) !== $hash) {
                    continue;
                }
                $to_remove = $gettext__value;
                break;
            }
            if ($to_remove !== null) {
                $po->remove($to_remove);
                $poGenerator->generateFile($po, $this->getLngFilename('po', $languages__value));
                $moGenerator->generateFile($po, $this->getLngFilename('mo', $languages__value));
                $success = true;
            }
        }

        return $success;
    }

    function deleteUnusedTranslations($since_date)
    {
        $deleted = 0;

        $discovery_strings = array_map(function ($a) {
            return $a['string'] . '#' . $a['context'];
        }, $this->log->discoveryLogGet($since_date));

        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $deleted;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        $to_remove = null;
        foreach ($pot->getTranslations() as $gettext__value) {
            if (in_array($gettext__value->getOriginal() . '#' . $gettext__value->getContext(), $discovery_strings)) {
                continue;
            }
            $to_remove = $gettext__value;
            break;
        }
        if ($to_remove !== null) {
            $pot->remove($to_remove);
            $poGenerator->generateFile($pot, $this->getLngFilename('pot', '_template'));
            $deleted++;
        }

        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                continue;
            }
            $po = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            $to_remove = null;
            foreach ($po->getTranslations() as $gettext__value) {
                if (
                    in_array($gettext__value->getOriginal() . '#' . $gettext__value->getContext(), $discovery_strings)
                ) {
                    continue;
                }
                $to_remove = $gettext__value;
                break;
            }
            if ($to_remove !== null) {
                $po->remove($to_remove);
                $poGenerator->generateFile($po, $this->getLngFilename('po', $languages__value));
                $moGenerator->generateFile($po, $this->getLngFilename('mo', $languages__value));
                $deleted++;
            }
        }

        return $deleted;
    }

    function addStringToPotFileAndToCache($str, $context, $comment = null)
    {
        $translation = Translation::create($context, $str);
        $translation->translate('');
        if ($this->settings->get('auto_add_added_date_to_gettext') === true) {
            $translation->getExtractedComments()->add('added: ' . date('Y-m-d H:i:s'));
        }
        if ($comment !== null) {
            $translation->getComments()->add($comment);
        }
        $this->gettext_pot->add($translation);
        $this->gettext_pot_cache[$context][$str] = null;
        $this->gettext_save_counter['pot'] = true;
    }

    function addTranslationToPoFileAndToCache($orig, $trans, $lng, $context = null, $comment = null)
    {
        if ($lng === $this->settings->getSourceLng() || empty(@$this->gettext[$lng])) {
            return;
        }
        $translation = Translation::create($context, $orig);
        $translation->translate($trans);
        if ($this->settings->get('auto_add_added_date_to_gettext') === true) {
            $translation->getExtractedComments()->add('added: ' . date('Y-m-d H:i:s'));
        }
        if ($comment !== null) {
            $translation->getComments()->add($comment);
        }
        $this->gettext[$lng]->add($translation);
        $this->gettext_cache[$lng][$context ?? ''][$orig] = $trans;
        $this->gettext_cache_reverse[$lng][$context ?? ''][$trans] = $orig;
        $this->gettext_save_counter['po'][$lng] = true;
    }

    function getLngFolder()
    {
        return $this->utils->getDocRoot() . '/' . trim($this->settings->get('lng_folder'), '/');
    }

    function getLngFilename($type, $lng)
    {
        return $this->getLngFolder() . '/' . $lng . '.' . $type;
    }

    function getLngFolderPublic()
    {
        return $this->host->getCurrentHost() . '/' . trim($this->settings->get('lng_folder'), '/');
    }

    function getLngFilenamePublic($type, $lng)
    {
        return $this->getLngFolderPublic() . '/' . $lng . '.' . $type;
    }

    function resetTranslations()
    {
        $files = glob($this->getLngFolder() . '/*'); // get all file names
        foreach ($files as $files__value) {
            if (is_file($files__value)) {
                if (
                    mb_strpos($files__value, '.pot') !== false ||
                    mb_strpos($files__value, '.po') !== false ||
                    mb_strpos($files__value, '.mo') !== false
                ) {
                    @unlink($files__value);
                }
            }
        }
    }

    function createLngFolderIfNotExists()
    {
        if (!is_dir($this->getLngFolder())) {
            mkdir($this->getLngFolder(), 0777, true);
        }
    }

    function getCurrentPrefix()
    {
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if (mb_strpos($this->host->getCurrentPath(), '/' . $languages__value) === 0) {
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
        return $this->getCurrentPrefix() ?? $this->settings->getSourceLng();
    }

    function getBrowserLng()
    {
        if (@$_SERVER['HTTP_ACCEPT_LANGUAGE'] != '') {
            foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
                if (mb_strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $languages__value) === 0) {
                    return $languages__value;
                }
            }
        }
        return $this->settings->getSourceLng();
    }

    function getPrefixFromUrl($url)
    {
        $path = str_replace($this->host->getCurrentHost(), '', $url);
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if (mb_strpos($path, '/' . $languages__value) === 0) {
                return $languages__value;
            }
        }
        return null;
    }

    function getLngFromUrl($url)
    {
        return $this->getPrefixFromUrl($url) ?? $this->settings->getSourceLng();
    }

    function getLanguagePickerData()
    {
        $data = [];
        foreach ($this->settings->getSelectedLanguages() as $languages__key => $languages__value) {
            $url = $this->getUrlTranslationInLanguage($languages__key);
            if (
                $this->publish->isActive() &&
                $this->publish->isPrevented($this->host->getCurrentUrl(), $languages__key)
            ) {
                continue;
            }
            $data[] = [
                'code' => $languages__key,
                'label' => $languages__value,
                'url' => $url,
                'active' => rtrim($url, '/') === rtrim($this->host->getCurrentUrlWithArgs(), '/')
            ];
        }
        return $data;
    }

    function sourceLngIsCurrentLng()
    {
        if ($this->getCurrentLng() === $this->settings->getSourceLng()) {
            return true;
        }
        return false;
    }

    function prepareTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        // auto determine context
        if ($context === null || $context == '') {
            if (mb_strpos($orig, 'http') === 0 && mb_strpos($orig, ' ') === false) {
                $context = 'slug';
            }
        }

        if ($context === 'slug') {
            $trans = $this->getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($orig, $lng, true);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'title') {
            foreach (['|', '·', '•', '>', '-', '–', '—', ':', '*', '⋆', '~', '«', '»', '<'] as $delimiters__value) {
                $orig = str_replace(' ', ' ', $orig); // replace hidden &nbsp; chars
                if (mb_strpos($orig, ' ' . $delimiters__value . ' ') !== false) {
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

    function getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, $translate)
    {
        if ($link === null || trim($link) === '') {
            return $link;
        }
        if (mb_strpos(trim($link, '/'), '#') === 0) {
            return $link;
        }
        if (mb_strpos(trim($link, '/'), '?') === 0) {
            return $link;
        }
        if (mb_strpos(trim($link, '/'), '&') === 0) {
            return $link;
        }
        $is_absolute_link = mb_strpos($link, $this->host->getCurrentHost()) === 0;
        if (mb_strpos($link, 'http') !== false && $is_absolute_link === false) {
            return $link;
        }
        if (mb_strpos($link, 'http') === false && mb_strpos($link, ':') !== false) {
            return $link;
        }
        $link = str_replace(
            [$this->host->getCurrentHost() . '/' . $this->settings->getSourceLng(), $this->host->getCurrentHost()],
            '',
            $link
        );

        if ($translate === true) {
            $url_parts = explode('/', $link);
            foreach ($url_parts as $url_parts__key => $url_parts__value) {
                if ($this->stringShouldNotBeTranslated($url_parts__value, 'slug')) {
                    continue;
                }
                $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamicallyIfNeeded(
                    $url_parts__value,
                    $lng,
                    'slug'
                );
            }
            $link = implode('/', $url_parts);
        }
        $link = (mb_strpos($link, '/') === 0 ? '/' : '') . $lng . '/' . ltrim($link, '/');
        if ($is_absolute_link === true) {
            $link = rtrim($this->host->getCurrentHost(), '/') . '/' . ltrim($link, '/');
        }
        return $link;
    }

    function getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        /*
        $orig
        - <a href="https://tld.com" class="foo" data-bar="baz">Hallo</a> Welt!
        - Das deutsche <a href="https://1.com">Brot</a> <a href="https://2.com">vermisse</a> ich am meisten.
        - <a class="notranslate foo">Hallo</a> Welt!

        $origWithoutAttributes
        - <a>Hallo</a> Welt!
        - Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.
        - <a class="notranslate">Hallo</a> Welt!

        $origWithIds
        - <a href="https://tld.com" class="foo" data-bar="baz" p="1">Hallo</a> Welt!
        - Das deutsche <a href="https://1.com" p="1">Brot</a> <a href="https://2.com" p="2">vermisse</a> ich am meisten.
        - <a class="notranslate foo" p="1">Hallo</a> Welt!

        $transWithIds
        - <a href="https://tld.com" class="foo" data-bar="baz" p="1">Hello</a> world!
        - I <a href="https://2.com" p="2">miss</a> German <a href="https://1.com" p="1">bread</a> the most.
        - <a class="notranslate foo" p="1">Hallo</a> world!

        $transWithoutAttributes
        - <a>Hello</a> world!
        - I <a p="2">miss</a> German <a p="1">bread</a> the most.
        - <a class="notranslate">Hallo</a> world!

        $trans
        - <a href="https://tld.com" class="foo" data-bar="baz">Hello</a> world!
        - I <a href="https://2.com">miss</a> German <a href="https://1.com">bread</a> the most.
        - <a class="notranslate foo">Hallo</a> world!
        */

        [$origWithoutAttributes, $mappingTable] = $this->tags->removeAttributesAndSaveMapping($orig);

        $transWithoutAttributes = $this->getExistingTranslationFromCache($origWithoutAttributes, $lng, $context);

        if ($transWithoutAttributes === false) {
            $origWithIds = $this->tags->addIds($orig);
            $transWithIds = $this->autoTranslateString($origWithIds, $lng, $context);
            $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($transWithIds);
            $this->addStringToPotFileAndToCache($origWithoutAttributes, $context);
            $this->addTranslationToPoFileAndToCache($origWithoutAttributes, $transWithoutAttributes, $lng, $context);
        }

        $trans = $this->tags->addAttributesAndRemoveIds($transWithoutAttributes, $mappingTable);

        return $trans;
    }

    function autoTranslateString($orig, $to_lng, $context = null, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLng();
        }

        $trans = null;

        if ($this->settings->get('auto_translation') === true) {
            if ($this->settings->get('auto_translation_service') === 'google') {
                $api_key = $this->settings->get('google_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                $trans = __translate_google($orig, $from_lng, $to_lng, $api_key);
                $this->log->apiStatsIncrease('google', mb_strlen($orig));
            } elseif ($this->settings->get('auto_translation_service') === 'microsoft') {
                $api_key = $this->settings->get('microsoft_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                $trans = __translate_microsoft($orig, $from_lng, $to_lng, $api_key);
                $this->log->apiStatsIncrease('microsoft', mb_strlen($orig));
            }
            if ($context === 'slug') {
                $trans = $this->utils->slugify($trans, $orig, $to_lng);
            }
        }

        // this does apply if auto_translation is either false or failed (due to wrong api key)
        if ($trans === null) {
            $trans = $this->translateStringMock($orig, $to_lng, $context, $from_lng);
        }

        if ($this->settings->get('debug_translations') === true) {
            if ($context !== 'slug') {
                $trans = '%|%' . $trans . '%|%';
            }
        }

        return $trans;
    }

    function removeLineBreaks($orig)
    {
        $str = $orig;
        $str = trim($str);
        $str = str_replace(['&#13;', "\r"], '', $str); // replace nasty carriage returns \r
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

    function reintroduceLineBreaks($str, $orig_withoutlb, $orig_with_lb)
    {
        $pos_lb_begin = 0;
        while (mb_substr($orig_with_lb, $pos_lb_begin, 1) !== mb_substr($orig_withoutlb, 0, 1)) {
            $pos_lb_begin++;
        }
        $pos_lb_end = mb_strlen($orig_with_lb) - 1;
        while (
            mb_substr($orig_with_lb, $pos_lb_end, 1) !== mb_substr($orig_withoutlb, mb_strlen($orig_withoutlb) - 1, 1)
        ) {
            $pos_lb_end--;
        }
        $str = mb_substr($orig_with_lb, 0, $pos_lb_begin) . $str . mb_substr($orig_with_lb, $pos_lb_end + 1);
        return $str;
    }

    function translateStringMock($str, $to_lng, $context = null, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLng();
        }
        if ($context === 'slug') {
            $pos = mb_strlen($str) - mb_strlen('-' . $from_lng);
            if (mb_strrpos($str, '-' . $from_lng) === $pos) {
                $str = mb_substr($str, 0, $pos);
            }
            if ($to_lng === $this->settings->getSourceLng()) {
                return $str;
            }
            return $str . '-' . $to_lng;
        }
        return $str . '-' . $to_lng;
    }

    function stringShouldNotBeTranslated($str, $context = null)
    {
        if ($str === null || $str === true || $str === false || $str === '') {
            return true;
        }
        $str = trim($str);
        $str = trim($str, '"');
        $str = trim($str, '\'');
        if ($str == '') {
            return true;
        }
        // numbers
        if (is_numeric($str)) {
            return true;
        }
        if (preg_match('/[a-zA-Z]/', $str) !== 1) {
            return true;
        }
        // email adresses
        if (substr_count($str, '@') === 1 && substr_count($str, '.') === 1) {
            return true;
        }
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if ($languages__value === trim(mb_strtolower($str))) {
                return true;
            }
        }
        if ($context === 'slug') {
            if (mb_strpos(trim($str, '/'), '#') === 0) {
                return true;
            }
            if (mb_strpos(trim($str, '/'), '?') === 0) {
                return true;
            }
            if (mb_strpos(trim($str, '/'), '&') === 0) {
                return true;
            }
        }
        // detect paths to php scripts
        if (mb_strpos($str, ' ') === false && mb_strpos($str, '.php') !== false) {
            return true;
        }
        // detect print_r outputs
        if (
            mb_strpos($str, '(') === 0 &&
            mb_strrpos($str, ')') === mb_strlen($str) - 1 &&
            mb_strpos($str, '=') !== false
        ) {
            return true;
        }
        // detect mathjax/latex
        if (mb_strpos($str, '$$') === 0 && mb_strrpos($str, '$$') === mb_strlen($str) - 2) {
            return true;
        }
        if (mb_strpos($str, '\\(') === 0 && mb_strrpos($str, '\\)') === mb_strlen($str) - 2) {
            return true;
        }
        return false;
    }

    function getUrlTranslationInLanguage($lng, $url = null)
    {
        $path = null;
        if ($url !== null) {
            $path = str_replace($this->host->getCurrentHost(), '', $url);
        } else {
            $path = $this->host->getCurrentPathWithArgs();
        }
        return trim(
            trim($this->host->getCurrentHost(), '/') .
                '/' .
                trim($this->getPathTranslationInLanguage($lng, false, $path), '/'),
            '/'
        ) . (mb_strpos($path, '?') === false ? '/' : '');
    }

    function getTranslationInForeignLng($str, $to_lng, $from_lng = null, $context = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->getCurrentLng();
        }
        if ($from_lng === $this->settings->getSourceLng()) {
            $str_in_source_lng = $str;
        } else {
            $str_in_source_lng = $this->getExistingTranslationReverseFromCache($str, $from_lng, $context); // str in source lng
        }
        if ($str_in_source_lng === false) {
            return false;
        }
        if ($to_lng === $this->settings->getSourceLng()) {
            return $str_in_source_lng;
        }
        if ($this->stringShouldNotBeTranslated($str_in_source_lng, $context)) {
            return $str_in_source_lng;
        }
        $trans = $this->getExistingTranslationFromCache($str_in_source_lng, $to_lng, $context);
        return $trans;
    }

    function getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng = null,
        $from_lng = null,
        $context = null
    ) {
        if ($to_lng === null) {
            $to_lng = $this->getCurrentLng();
        }
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLng();
        }
        $trans = $this->getTranslationInForeignLng($str, $to_lng, $from_lng, $context);
        if ($trans === false) {
            if ($from_lng === $this->settings->getSourceLng()) {
                $str_in_source = $str;
            } else {
                $str_in_source = $this->autoTranslateString($str, $this->settings->getSourceLng(), $context, $from_lng);
            }
            $trans = $this->autoTranslateString($str_in_source, $to_lng, $context, $from_lng);
            $this->addStringToPotFileAndToCache($str_in_source, $context);
            $this->addTranslationToPoFileAndToCache($str_in_source, $str, $from_lng, $context);
            $this->addTranslationToPoFileAndToCache($str_in_source, $trans, $to_lng, $context);
        }
        return $trans;
    }

    function getPathTranslationInLanguage($lng, $always_remove_prefix = false, $path = null)
    {
        if ($path === null) {
            $path = $this->host->getCurrentPathWithArgs();
        }
        if ($this->getCurrentLng() === $lng) {
            return $path;
        }
        $path_parts = explode('/', $path);
        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if ($path_parts[$path_parts__key] == '') {
                unset($path_parts[$path_parts__key]);
            }
        }
        $path_parts = array_values($path_parts);

        // prefix
        if (
            $always_remove_prefix === true ||
            ($this->settings->getSourceLng() === $lng && $this->settings->get('prefix_source_lng') === false)
        ) {
            if (@$path_parts[0] === $this->getCurrentLng()) {
                unset($path_parts[0]);
            }
        } else {
            if (@$path_parts[0] === $this->getCurrentLng()) {
                $path_parts[0] = $lng;
            } else {
                array_unshift($path_parts, $lng);
            }
        }

        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if (in_array($path_parts__value, $this->settings->getSelectedLanguageCodes())) {
                continue;
            }
            $trans = $this->getTranslationInForeignLng($path_parts__value, $lng, null, 'slug');
            // links are discovered gradually by gtbabel:
            // if one goes directly to a translated page that is not linked from the homepage,
            // gtbabel cannot figure out it's source
            // the following line is a convenience method when auto translation is disabled
            if ($trans === false && $this->settings->get('auto_translation') === false) {
                $trans = $this->autoTranslateString($path_parts__value, $lng, 'slug', $this->getCurrentLng());
            }
            if ($trans !== false) {
                $path_parts[$path_parts__key] = $trans;
            }
        }
        $path = implode('/', $path_parts);
        return $path;
    }

    function addCurrentUrlToTranslations()
    {
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        if ($this->host->isAjaxRequest()) {
            return;
        }
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            $this->prepareTranslationAndAddDynamicallyIfNeeded($this->host->getCurrentUrl(), $languages__value, 'slug');
        }
    }
}
