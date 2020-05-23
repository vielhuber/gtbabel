<?php
namespace vielhuber\gtbabel;

use Gettext\Generator\PoGenerator;
use Gettext\Generator\MoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Loader\MoLoader;
use Gettext\Translations;
use Gettext\Translation;
use Gettext\Merge;

use vielhuber\stringhelper\__;

class Gettext
{
    public $gettext;
    public $gettext_cache;
    public $gettext_cache_reverse;
    public $gettext_checked_strings;
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
        $this->gettext_checked_strings = [];
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
            clearstatcache();
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
            $this->gettext_checked_strings[$languages__value] = [];
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                $this->gettext[$languages__value] = Translations::create('gtbabel');
            } else {
                $this->gettext[$languages__value] = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
                clearstatcache();
            }
            foreach ($this->gettext[$languages__value]->getTranslations() as $gettext__value) {
                $context = $gettext__value->getContext() ?? '';
                $this->gettext_cache[$languages__value][$context][
                    $gettext__value->getOriginal()
                ] = $gettext__value->getTranslation();
                $this->gettext_cache_reverse[$languages__value][$context][
                    $gettext__value->getTranslation()
                ] = $gettext__value->getOriginal();
                $this->gettext_checked_strings[$languages__value][$context][
                    $gettext__value->getOriginal()
                ] = $this->translationHasComment($gettext__value, 'checked');
            }
        }
    }

    function generateGettextFiles()
    {
        if ($this->settings->get('auto_add_translations_to_gettext') === false) {
            return;
        }

        /*
        we don't simply use generateFile on the whole string we have read at the beginning of the request
        another request could potentially be in between this request we lose its contents:

        No problem
        A_begin         
        |
        A_end

                B_begin
                |
                B_end

        Problem (we lose changes of request B)
        A_begin         
        |
        |       B_begin
        |       |
        |       B_end
        |
        A_end

        Problem (we lose changes of request A)
        A_begin         
        |
        |       B_begin
        |       |
        A_end   |
                |
                B_end

        To overcome this issue, we save "securely" by merging the translations with the current
        version of the file (that could be potentially changed in the meantime)
        */

        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        $poLoader = new PoLoader();

        if ($this->gettext_save_counter['pot'] === true) {
            // merge
            if (file_exists($this->getLngFilename('pot', '_template'))) {
                $this->gettext_pot = $poLoader
                    ->loadFile($this->getLngFilename('pot', '_template'))
                    ->mergeWith($this->gettext_pot);
                clearstatcache();
            }
            $poGenerator->generateFile($this->gettext_pot, $this->getLngFilename('pot', '_template'));
            clearstatcache();
        }

        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if ($this->gettext_save_counter['po'][$languages__value] === false) {
                continue;
            }
            // merge
            if (file_exists($this->getLngFilename('po', $languages__value))) {
                $this->gettext[$languages__value] = $poLoader
                    ->loadFile($this->getLngFilename('po', $languages__value))
                    ->mergeWith($this->gettext[$languages__value]);
                clearstatcache();
            }
            $poGenerator->generateFile(
                $this->gettext[$languages__value],
                $this->getLngFilename('po', $languages__value)
            );
            clearstatcache();
            $moGenerator->generateFile(
                $this->gettext[$languages__value],
                $this->getLngFilename('mo', $languages__value)
            );
            clearstatcache();
        }
    }

    function convertPoToMo($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }
        $loader = new PoLoader();
        $translations = $loader->loadFile($filename);
        clearstatcache();
        $generator = new MoGenerator();
        $generator->generateFile($translations, str_replace('.po', '.mo', $filename));
        clearstatcache();
        return true;
    }

    function getExistingTranslationFromCache($str, $lng, $context = null)
    {
        // track discovery
        $this->log->discoveryLogAdd(
            $this->host->getCurrentUrlWithArgs(),
            $this->host->getCurrentUrlWithArgsConverted(),
            $str,
            $context,
            $lng
        );
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

    function getAllTranslationsFromFiles($lng = null, $order_by_string = true)
    {
        $data = [];
        $poLoader = new PoLoader();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $data;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        clearstatcache();
        $order = 0;
        foreach ($pot->getTranslations() as $gettext__value) {
            $data[$this->getIdFromStringAndContext($gettext__value)] = [
                'orig' => $gettext__value->getOriginal(),
                'context' => $gettext__value->getContext() ?? '',
                'shared' => $this->translationHasComment($gettext__value, 'shared'),
                'translations' => [],
                'order' => ++$order
            ];
        }
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if ($lng !== null && $lng !== $languages__value) {
                continue;
            }
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                continue;
            }
            $po = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            clearstatcache();
            foreach ($po->getTranslations() as $gettext__value) {
                $data[$this->getIdFromStringAndContext($gettext__value)]['translations'][$languages__value] = [
                    'str' => $gettext__value->getTranslation(),
                    'checked' => $this->translationHasComment($gettext__value, 'checked')
                ];
            }
        }
        uasort($data, function ($a, $b) use ($order_by_string) {
            /*
            order_by_string = true (url is not set)
                context
                orig
            order_by_string = false (url is set)
                shared
                context
                order
            */
            if ($order_by_string === false) {
                $a['shared'] = $a['shared'] === true ? 1 : 0;
                $b['shared'] = $b['shared'] === true ? 1 : 0;
                if ($a['shared'] !== $b['shared']) {
                    return $a['shared'] < $b['shared'] ? -1 : 1;
                }
            }
            if ($a['context'] != $b['context']) {
                if ($a['context'] == '') {
                    return -1;
                }
                if ($b['context'] == '') {
                    return 1;
                }
                if ($a['context'] === 'slug') {
                    return -1;
                }
                if ($b['context'] === 'slug') {
                    return 1;
                }
                if ($a['context'] === 'title') {
                    return -1;
                }
                if ($b['context'] === 'title') {
                    return 1;
                }
                if ($a['context'] === 'description') {
                    return -1;
                }
                if ($b['context'] === 'description') {
                    return 1;
                }
                return strnatcasecmp($a['context'], $b['context']);
            }
            if ($order_by_string === true) {
                return strnatcasecmp($a['orig'], $b['orig']);
            } else {
                return strcmp($a['order'], $b['order']);
            }
        });
        return $data;
    }

    function getIdFromStringAndContext($gettext, $context = false)
    {
        if ($context === false) {
            $string = $gettext->getOriginal();
            $context = $gettext->getContext();
        } else {
            $string = $gettext;
            $context = $context;
        }
        return base64_encode(serialize([$string, $context ?? '']));
    }

    function getStringAndContextFromId($id)
    {
        return unserialize(base64_decode($id));
    }

    function translationHasComment($gettext, $comment)
    {
        foreach ($gettext->getExtractedComments() as $comments__value) {
            if ($comments__value === $comment) {
                return true;
            }
        }
        return false;
    }

    function deleteCommentFromTranslation($gettext, $comment)
    {
        $gettext->getExtractedComments()->delete($comment);
    }

    function addCommentToTranslation($gettext, $comment)
    {
        $gettext->getExtractedComments()->add($comment);
    }

    function editTranslation($orig, $context, $lng, $str = false, $checked = null)
    {
        return $this->editTranslationById($this->getIdFromStringAndContext($orig, $context), $lng, $str, $checked);
    }

    function editTranslationById($id, $lng, $str = false, $checked = null)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('po', $lng))) {
            return $success;
        }
        $po = $poLoader->loadFile($this->getLngFilename('po', $lng));
        clearstatcache();

        // slug collission detection
        if ($str != '') {
            $collission = true;
            $counter = 2;
            while ($collission === true) {
                $collission = false;
                foreach ($po->getTranslations() as $gettext__value) {
                    if ($gettext__value->getContext() === 'slug' && $gettext__value->getTranslation() === $str) {
                        if ($counter > 2) {
                            $str = mb_substr($str, 0, mb_strrpos($str, '-'));
                        }
                        $str .= '-' . $counter;
                        $counter++;
                        $collission = true;
                    }
                }
            }
        }

        // get existing
        $gettext = null;
        foreach ($po->getTranslations() as $gettext__value) {
            if ($this->getIdFromStringAndContext($gettext__value) !== $id) {
                continue;
            }
            $gettext = $gettext__value;
            break;
        }

        if ($gettext !== null) {
            // delete
            if ($str !== false && ($str === null || $str === '')) {
                //$this->log->generalLog('removing ' . $gettext->getOriginal());
                $po->remove($gettext);
            }
            // update
            else {
                if ($str !== false) {
                    //$this->log->generalLog('updating translation ' . $gettext->getOriginal());
                    $gettext->translate($str);
                }
                if ($checked !== null) {
                    //$this->log->generalLog('updating checked ' . $gettext->getOriginal());
                    if ($checked === true) {
                        $this->addCommentToTranslation($gettext, 'checked');
                    }
                    if ($checked === false) {
                        $this->deleteCommentFromTranslation($gettext, 'checked');
                    }
                }
            }
            $success = true;
        }

        // create
        else {
            [$orig, $context] = $this->getStringAndContextFromId($id);
            $translation = Translation::create($context, $orig);
            $translation->translate($str);
            //$this->log->generalLog('creating ' . $translation->getOriginal());
            if ($this->settings->get('auto_add_added_date_to_gettext') === true) {
                $this->addCommentToTranslation($translation, 'added: ' . date('Y-m-d H:i:s'));
            }
            if ($checked !== false) {
                $this->addCommentToTranslation($translation, 'checked');
            }
            $po->add($translation);
            $success = true;
        }

        if ($success === true) {
            $poGenerator->generateFile($po, $this->getLngFilename('po', $lng));
            clearstatcache();
            $moGenerator->generateFile($po, $this->getLngFilename('mo', $lng));
            clearstatcache();
        }
        return $success;
    }

    function setCheckedToAllStringsFromFiles()
    {
        $success = true;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                continue;
            }
            $po = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            clearstatcache();
            foreach ($po->getTranslations() as $gettext__value) {
                $this->addCommentToTranslation($gettext__value, 'checked');
                $success_this = true;
            }
            if ($success_this === true) {
                $poGenerator->generateFile($po, $this->getLngFilename('po', $languages__value));
                clearstatcache();
                $moGenerator->generateFile($po, $this->getLngFilename('mo', $languages__value));
                clearstatcache();
            } else {
                $success = false;
            }
        }
        return $success;
    }

    function editCheckedValue($orig, $context, $lng, $checked)
    {
        return $this->editCheckedValueById($this->getIdFromStringAndContext($orig, $context), $lng, $checked);
    }

    function editCheckedValueById($id, $lng, $checked)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('po', $lng))) {
            return $success;
        }
        $po = $poLoader->loadFile($this->getLngFilename('po', $lng));
        clearstatcache();
        foreach ($po->getTranslations() as $gettext__value) {
            if ($this->getIdFromStringAndContext($gettext__value) !== $id) {
                continue;
            }
            if ($checked === true) {
                $this->addCommentToTranslation($gettext__value, 'checked');
            }
            if ($checked === false) {
                $this->deleteCommentFromTranslation($gettext__value, 'checked');
            }
            $success = true;
        }
        if ($success === true) {
            $poGenerator->generateFile($po, $this->getLngFilename('po', $lng));
            clearstatcache();
            $moGenerator->generateFile($po, $this->getLngFilename('mo', $lng));
            clearstatcache();
        }
        return $success;
    }

    function setAllDiscoveredStringsToChecked()
    {
        if ($this->settings->get('auto_set_discovered_strings_checked') !== true) {
            return;
        }
        if ($this->log->discovery_log_to_save === null) {
            return;
        }
        foreach ($this->log->discovery_log_to_save as $discovery_log_to_save__value) {
            $translation = $this->gettext[$discovery_log_to_save__value['lng']]->find(
                $discovery_log_to_save__value['context'],
                $discovery_log_to_save__value['string']
            );
            if ($translation !== null) {
                $this->addCommentToTranslation($translation, 'checked');
                $this->gettext_save_counter['po'][$discovery_log_to_save__value['lng']] = true;
            }
        }
    }

    function editSharedValue($orig, $context, $shared)
    {
        return $this->editSharedValueById($this->getIdFromStringAndContext($orig, $context), $shared);
    }

    function editSharedValueById($id, $shared)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $success;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        clearstatcache();
        foreach ($pot->getTranslations() as $gettext__value) {
            if ($this->getIdFromStringAndContext($gettext__value) !== $id) {
                continue;
            }
            if ($shared === true) {
                $this->addCommentToTranslation($gettext__value, 'shared');
            }
            if ($shared === false) {
                $this->deleteCommentFromTranslation($gettext__value, 'shared');
            }
            $success = true;
        }
        if ($success === true) {
            $poGenerator->generateFile($pot, $this->getLngFilename('pot', '_template'));
            clearstatcache();
        }
        return $success;
    }

    function autoEditSharedValues($strings = null)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $success;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        clearstatcache();
        $data = [];

        $filename = $this->log->discoveryLogFilename();
        if (!file_exists($filename)) {
            return $success;
        }
        $db = new \PDO('sqlite:' . $filename);
        $query = '';
        $query .= '
            SELECT string, context, COUNT(string) as count FROM (
                SELECT string, context, url_orig FROM logs';
        $args = [];
        if ($strings !== null && !empty($strings)) {
            $query .= ' WHERE string IN (' . str_repeat('?,', count($strings) - 1) . '?)';
            $args = array_merge(
                $args,
                array_map(function ($strings__value) {
                    return $strings__value['string'];
                }, $strings)
            );
        }
        $query .= '
                GROUP BY string, context, url_orig
            ) as t GROUP BY string, context
        ';
        $statement = $db->prepare($query);
        $statement->execute($args);
        $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $results__value) {
            $data[$this->getIdFromStringAndContext($results__value['string'], $results__value['context'])] =
                $results__value['count'] > 1;
        }

        foreach ($pot->getTranslations() as $gettext__value) {
            $id = $this->getIdFromStringAndContext($gettext__value);
            if (!array_key_exists($id, $data)) {
                continue;
            }
            if ($data[$id] === true) {
                $this->addCommentToTranslation($gettext__value, 'shared');
            } else {
                $this->deleteCommentFromTranslation($gettext__value, 'shared');
            }
            $success = true;
        }
        if ($success === true) {
            $poGenerator->generateFile($pot, $this->getLngFilename('pot', '_template'));
            clearstatcache();
        }
        return $success;
    }

    function deleteStringFromGettext($orig, $context)
    {
        return $this->deleteStringFromGettextById($this->getIdFromStringAndContext($orig, $context));
    }

    function deleteStringFromGettextById($id)
    {
        $success = false;
        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $success;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        clearstatcache();
        $to_remove = null;
        foreach ($pot->getTranslations() as $gettext__value) {
            if ($this->getIdFromStringAndContext($gettext__value) !== $id) {
                continue;
            }
            $to_remove = $gettext__value;
            break;
        }
        if ($to_remove !== null) {
            $pot->remove($to_remove);
            $poGenerator->generateFile($pot, $this->getLngFilename('pot', '_template'));
            clearstatcache();
            $success = true;
        }

        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                continue;
            }
            $po = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            clearstatcache();
            $to_remove = null;
            foreach ($po->getTranslations() as $gettext__value) {
                if ($this->getIdFromStringAndContext($gettext__value) !== $id) {
                    continue;
                }
                $to_remove = $gettext__value;
                break;
            }
            if ($to_remove !== null) {
                $po->remove($to_remove);
                $poGenerator->generateFile($po, $this->getLngFilename('po', $languages__value));
                clearstatcache();
                $moGenerator->generateFile($po, $this->getLngFilename('mo', $languages__value));
                clearstatcache();
                $success = true;
            }
            $po = null;
        }
        return $success;
    }

    function deleteUnusedTranslations($since_time)
    {
        $deleted = 0;
        $discovery_strings = array_map(function ($a) {
            return $a['string'] . '#' . $a['context'];
        }, $this->log->discoveryLogGet($since_time));

        $poLoader = new PoLoader();
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        if (!file_exists($this->getLngFilename('pot', '_template'))) {
            return $deleted;
        }
        $pot = $poLoader->loadFile($this->getLngFilename('pot', '_template'));
        clearstatcache();
        $to_remove = [];
        foreach ($pot->getTranslations() as $gettext__value) {
            if (in_array($gettext__value->getOriginal() . '#' . $gettext__value->getContext(), $discovery_strings)) {
                continue;
            }
            //$this->log->generalLog('removing ' . $gettext__value->getOriginal() . '#' . $gettext__value->getContext());
            $to_remove[] = $gettext__value;
        }
        if (!empty($to_remove)) {
            foreach ($to_remove as $to_remove__value) {
                $pot->remove($to_remove__value);
                $deleted++;
            }
            $poGenerator->generateFile($pot, $this->getLngFilename('pot', '_template'));
            clearstatcache();
        }

        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            if (!file_exists($this->getLngFilename('po', $languages__value))) {
                continue;
            }
            $po = $poLoader->loadFile($this->getLngFilename('po', $languages__value));
            clearstatcache();
            $to_remove = [];
            foreach ($po->getTranslations() as $gettext__value) {
                if (
                    in_array($gettext__value->getOriginal() . '#' . $gettext__value->getContext(), $discovery_strings)
                ) {
                    continue;
                }
                //$this->log->generalLog('removing ' . $gettext__value->getOriginal() . '#' . $gettext__value->getContext());
                $to_remove[] = $gettext__value;
            }
            if (!empty($to_remove)) {
                foreach ($to_remove as $to_remove__value) {
                    $po->remove($to_remove__value);
                    $deleted++;
                }
                $poGenerator->generateFile($po, $this->getLngFilename('po', $languages__value));
                clearstatcache();
                $moGenerator->generateFile($po, $this->getLngFilename('mo', $languages__value));
                clearstatcache();
            }
        }
        return $deleted;
    }

    function addStringToPotFileAndToCache($str, $context, $comment = null)
    {
        $translation = Translation::create($context, $str);
        $translation->translate('');
        if ($this->settings->get('auto_add_added_date_to_gettext') === true) {
            $this->addCommentToTranslation($translation, 'added: ' . date('Y-m-d H:i:s'));
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
        if ($lng === $this->settings->getSourceLanguageCode() || empty(@$this->gettext[$lng])) {
            return;
        }
        $translation = Translation::create($context, $orig);
        $translation->translate($trans);
        if ($this->settings->get('auto_add_added_date_to_gettext') === true) {
            $this->addCommentToTranslation($translation, 'added: ' . date('Y-m-d H:i:s'));
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

    function setupLngFolder()
    {
        if (!is_dir($this->getLngFolder())) {
            mkdir($this->getLngFolder(), 0777, true);
        }
        if (!file_exists($this->getLngFolder() . '/.htaccess')) {
            file_put_contents($this->getLngFolder() . '/.htaccess', 'Deny from all');
        }
    }

    function getCurrentPrefix()
    {
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if (
                $this->host->getCurrentPath() === $languages__value ||
                mb_strpos($this->host->getCurrentPath(), '/' . $languages__value . '/') === 0
            ) {
                return $languages__value;
            }
        }
        return null;
    }

    function getCurrentLanguageCode()
    {
        if ($this->settings->get('lng_target') !== null) {
            return $this->settings->get('lng_target');
        }
        return $this->getCurrentPrefix() ?? $this->settings->getSourceLanguageCode();
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
        return $this->settings->getSourceLanguageCode();
    }

    function getPrefixFromUrl($url)
    {
        $path = str_replace($this->host->getCurrentHost(), '', $url);
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if ($path === $languages__value || mb_strpos($path, '/' . $languages__value . '/') === 0) {
                return $languages__value;
            }
        }
        return null;
    }

    function getLngFromUrl($url)
    {
        return $this->getPrefixFromUrl($url) ?? $this->settings->getSourceLanguageCode();
    }

    function getLanguagePickerData()
    {
        $data = [];
        foreach ($this->settings->getSelectedLanguages() as $languages__key => $languages__value) {
            if (!$this->host->responseCodeIsSuccessful()) {
                continue;
            }
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
        if ($this->getCurrentLanguageCode() === $this->settings->getSourceLanguageCode()) {
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
            $trans = $this->getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($orig, $lng);
            if ($trans === null) {
                return $orig;
            }
            return $trans;
        }
        if ($context === 'file') {
            $trans = $this->getTranslationOfFileAndAddDynamicallyIfNeeded($orig, $lng);
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

    function addPrefixToLink($link, $lng)
    {
        return $this->addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, false);
    }

    function getTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng)
    {
        return $this->addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, true);
    }

    function addPrefixToLinkAndGetTranslationOfLinkHrefAndAddDynamicallyIfNeeded($link, $lng, $translate)
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

        // replace host/lng
        foreach (
            [
                $this->host->getCurrentHost() . '/' . $this->settings->getSourceLanguageCode(),
                $this->host->getCurrentHost(),
                '/' . $this->settings->getSourceLanguageCode(),
                $this->settings->getSourceLanguageCode()
            ]
            as $begin__value
        ) {
            if (mb_strpos($link, $begin__value) === 0) {
                $link = str_replace($begin__value, '', $link);
            }
        }

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

    function getTranslationOfFileAndAddDynamicallyIfNeeded($orig, $lng)
    {
        $urls = [];
        // extract urls from style tag
        if (strpos($orig, 'url(') !== false) {
            preg_match_all('/url\((.+?)\)/', $orig, $matches);
            foreach ($matches[1] as $matches__value) {
                $urls[] = trim(trim($matches__value, '\''), '"');
            }
        } else {
            $urls[] = $orig;
        }
        foreach ($urls as $urls__value) {
            if ($urls__value === '/beispiel-bilddatei14.jpg') {
                var_dump($urls__value);
                die();
            }
            // always submit relative urls
            foreach (
                [
                    $this->host->getCurrentHost() . '/' . $this->settings->getSourceLanguageCode(),
                    $this->host->getCurrentHost(),
                    '/' . $this->settings->getSourceLanguageCode(),
                    $this->settings->getSourceLanguageCode()
                ]
                as $begin__value
            ) {
                if (strpos($urls__value, $begin__value) === 0) {
                    $urls__value = str_replace($begin__value, '', $urls__value);
                }
            }
            $urls__value = trim($urls__value, '/');
            // skip external files
            if (strpos($urls__value, 'http') === 0 && strpos($urls__value, $this->host->getCurrentHost()) === false) {
                continue;
            }
            if ($this->stringShouldNotBeTranslated($urls__value, 'file')) {
                continue;
            }
            $trans = $this->getExistingTranslationFromCache($urls__value, $lng, 'file');
            if ($trans === false) {
                $this->addStringToPotFileAndToCache($urls__value, 'file');
            } elseif ($this->stringIsChecked($urls__value, $lng, 'file')) {
                $orig = str_replace($urls__value, $trans, $orig);
            }
        }
        return $orig;
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
            if ($transWithIds !== null) {
                $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($transWithIds);
                $this->addStringToPotFileAndToCache($origWithoutAttributes, $context);
                $this->addTranslationToPoFileAndToCache(
                    $origWithoutAttributes,
                    $transWithoutAttributes,
                    $lng,
                    $context
                );
            } else {
                $transWithoutAttributes = $this->tags->removeAttributesExceptIrregularIds($origWithIds);
            }
        }

        $trans = $this->tags->addAttributesAndRemoveIds($transWithoutAttributes, $mappingTable);

        if (!$this->stringIsChecked($origWithoutAttributes, $lng, $context)) {
            return $origWithoutAttributes;
        }

        return $trans;
    }

    function autoTranslateString($orig, $to_lng, $context = null, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLanguageCode();
        }

        $trans = null;

        if ($this->settings->get('auto_translation') === true) {
            if ($this->settings->get('auto_translation_service') === 'google') {
                $api_key = $this->settings->get('google_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                $trans = null;
                // sometimes google translation api has some hickups (especially in latin); we overcome this by trying it again
                $tries = 0;
                while ($tries < 10) {
                    try {
                        $trans = __::translate_google($orig, $from_lng, $to_lng, $api_key);
                        //$this->log->generalLog(['SUCCESSFUL TRANSLATION', $orig, $from_lng, $to_lng, $api_key, $trans]);
                        break;
                    } catch (\Throwable $t) {
                        //$this->log->generalLog(['FAILED TRANSLATION (TRIES: ' . $tries . ')',$t->getMessage(),$orig,$from_lng,$to_lng,$api_key,$trans]);
                        if (strpos($t->getMessage(), 'PERMISSION_DENIED') !== false) {
                            break;
                        }
                        sleep(1);
                        $tries++;
                    }
                }
                if ($trans === null || $trans === '') {
                    return null;
                }
                $this->log->statsLogIncrease('google', mb_strlen($orig));
            } elseif ($this->settings->get('auto_translation_service') === 'microsoft') {
                $api_key = $this->settings->get('microsoft_translation_api_key');
                if (is_array($api_key)) {
                    $api_key = $api_key[array_rand($api_key)];
                }
                $trans = __::translate_microsoft($orig, $from_lng, $to_lng, $api_key);
                if ($trans === null || $trans === '') {
                    return null;
                }
                $this->log->statsLogIncrease('microsoft', mb_strlen($orig));
            }
            if ($context === 'slug') {
                $trans = $this->utils->slugify($trans, $orig, $to_lng);
            }
        } else {
            $trans = $this->translateStringMock($orig, $to_lng, $context, $from_lng);
        }

        // slug collission detection
        if ($context === 'slug') {
            $counter = 2;
            while ($this->getExistingTranslationReverseFromCache($trans, $to_lng, $context) !== false) {
                if ($counter > 2) {
                    $trans = mb_substr($trans, 0, mb_strrpos($trans, '-'));
                }
                $trans .= '-' . $counter;
                $counter++;
            }
        }

        if ($this->settings->get('debug_translations') === true) {
            if ($context !== 'slug') {
                $trans = '%|%' . $trans . '%|%';
            }
        }

        if ($trans === '') {
            return null;
        }

        return $trans;
    }

    function removeLineBreaks($orig)
    {
        $str = $orig;
        $str = trim($str);
        $str = str_replace(['&#13;', "\r"], '', $str); // replace nasty carriage returns \r
        $str = preg_replace('/[\t]+/', ' ', $str); // replace multiple tab spaces with one tab space
        $parts = explode(PHP_EOL, $str);
        foreach ($parts as $parts__key => $parts__value) {
            $parts__value = trim($parts__value);
            if ($parts__value == '') {
                unset($parts[$parts__key]);
            } else {
                $parts[$parts__key] = $parts__value;
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
            $from_lng = $this->settings->getSourceLanguageCode();
        }
        if ($context === 'slug') {
            $pos = mb_strlen($str) - mb_strlen('-' . $from_lng);
            if (mb_strrpos($str, '-' . $from_lng) === $pos) {
                $str = mb_substr($str, 0, $pos);
            }
            if ($to_lng === $this->settings->getSourceLanguageCode()) {
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
        if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        // lng codes
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
            // static files like big-image.jpg
            if (preg_match('/.+\.[a-zA-Z\d]+$/', $str)) {
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

    function stringIsChecked($str, $lng, $context = null)
    {
        if ($this->settings->get('only_show_checked_strings') !== true) {
            return true;
        }
        if ($lng === $this->settings->getSourceLanguageCode()) {
            return true;
        }
        if (
            $str === '' ||
            $str === null ||
            $this->gettext_checked_strings[$lng] === null ||
            !array_key_exists($context ?? '', $this->gettext_checked_strings[$lng]) ||
            !array_key_exists($str, $this->gettext_checked_strings[$lng][$context ?? '']) ||
            $this->gettext_checked_strings[$lng][$context ?? ''][$str] != '1'
        ) {
            return false;
        }
        return true;
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
        $data = [
            'trans' => false,
            'str_in_source_lng' => false,
            'checked_from' => true,
            'checked_to' => true
        ];
        if ($from_lng === $this->settings->getSourceLanguageCode()) {
            $data['str_in_source_lng'] = $str;
        } else {
            $data['str_in_source_lng'] = $this->getExistingTranslationReverseFromCache($str, $from_lng, $context); // str in source lng
        }
        if ($data['str_in_source_lng'] === false) {
            return $data;
        }
        if (
            $to_lng === $this->settings->getSourceLanguageCode() ||
            $this->stringShouldNotBeTranslated($data['str_in_source_lng'], $context)
        ) {
            $data['trans'] = $data['str_in_source_lng'];
            return $data;
        }
        $data['checked_from'] = $this->stringIsChecked($data['str_in_source_lng'], $from_lng, $context);
        $data['checked_to'] = $this->stringIsChecked($data['str_in_source_lng'], $to_lng, $context);
        $data['trans'] = $this->getExistingTranslationFromCache($data['str_in_source_lng'], $to_lng, $context);
        return $data;
    }

    function getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng = null,
        $from_lng = null,
        $context = null
    ) {
        if ($to_lng === null) {
            $to_lng = $this->getCurrentLanguageCode();
        }
        if ($from_lng === null) {
            $from_lng = $this->settings->getSourceLanguageCode();
        }
        $data = $this->getTranslationInForeignLng($str, $to_lng, $from_lng, $context);
        $trans = $data['trans'];
        if ($trans === false) {
            if ($from_lng === $this->settings->getSourceLanguageCode()) {
                $str_in_source = $str;
            } else {
                $str_in_source = $this->autoTranslateString(
                    $str,
                    $this->settings->getSourceLanguageCode(),
                    $context,
                    $from_lng
                );
            }
            $trans = $this->autoTranslateString($str_in_source, $to_lng, $context, $from_lng);
            if ($trans !== null) {
                $this->addStringToPotFileAndToCache($str_in_source, $context);
                $this->addTranslationToPoFileAndToCache($str_in_source, $str, $from_lng, $context);
                $this->addTranslationToPoFileAndToCache($str_in_source, $trans, $to_lng, $context);
            } else {
                $trans = $str;
            }
        }
        if ($data['checked_from'] === false || $data['checked_to'] === false) {
            return $str;
        }
        return $trans;
    }

    function getPathTranslationInLanguage($lng, $always_remove_prefix = false, $path = null)
    {
        if ($path === null) {
            $path = $this->host->getCurrentPathWithArgs();
        }
        if ($this->getCurrentLanguageCode() === $lng) {
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
            ($this->settings->getSourceLanguageCode() === $lng && $this->settings->get('prefix_source_lng') === false)
        ) {
            if (@$path_parts[0] === $this->getCurrentLanguageCode()) {
                unset($path_parts[0]);
            }
        } else {
            if (@$path_parts[0] === $this->getCurrentLanguageCode()) {
                $path_parts[0] = $lng;
            } else {
                array_unshift($path_parts, $lng);
            }
        }

        foreach ($path_parts as $path_parts__key => $path_parts__value) {
            if (in_array($path_parts__value, $this->settings->getSelectedLanguageCodes())) {
                continue;
            }
            $data = $this->getTranslationInForeignLng(
                $path_parts__value,
                $lng,
                $this->getCurrentLanguageCode(),
                'slug'
            );
            if ($this->settings->get('only_show_checked_strings') === true) {
                // no string has been found in general (unchecked or checked)
                // this is always the case, if you are on a unchecked url (like /en/impressum)
                // and try to translate that e.g. from english to french
                if ($data['trans'] === false) {
                    $data = $this->getTranslationInForeignLng(
                        $path_parts__value,
                        $lng,
                        $this->settings->getSourceLanguageCode(),
                        'slug'
                    );
                }
                if ($data['checked_from'] === false && $data['checked_to'] === false) {
                    $trans = false;
                } elseif ($data['checked_from'] === true && $data['checked_to'] === false) {
                    $trans = $data['str_in_source_lng'];
                } elseif ($data['checked_from'] === false && $data['checked_to'] === true) {
                    $trans = false;
                } elseif ($data['checked_from'] === true && $data['checked_to'] === true) {
                    $trans = $data['trans'];
                }
            } else {
                $trans = $data['trans'];
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
        if (!$this->host->responseCodeIsSuccessful()) {
            return;
        }
        foreach ($this->settings->getSelectedLanguageCodesWithoutSource() as $languages__value) {
            $this->prepareTranslationAndAddDynamicallyIfNeeded($this->host->getCurrentUrl(), $languages__value, 'slug');
        }
    }
}
