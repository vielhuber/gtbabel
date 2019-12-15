<?php
namespace vielhuber\gtbabel;

use Gettext\Loader\PoLoader;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Gettext\Translations;

class gtbabel
{
    private $args = null;

    private $original_request_uri = null;

    private $auto_translation = false;

    private $html = null;
    private $translations = null;
    private $translations_cache = null;
    private $DOMDocument = null;
    private $DOMXpath = null;

    public function start($args = [])
    {
        $this->initArgs($args);
        if ($this->currentUrlIsExcluded()) {
            return;
        }
        $this->createLngFolderIfNotExists();
        if ($this->shouldBeResetted() === true) {
            $this->deletePoMoFiles();
        }
        $this->createPoFilesIfNotExists();
        $this->preloadTranslationsInCache();
        $this->addCurrentUrlToTranslations();
        if (!$this->sourceLngIsCurrentLng() && !$this->currentUrlIsExcluded()) {
            $this->initMagicRouter();
            ob_start();
        }
    }

    public function stop()
    {
        if (!$this->sourceLngIsCurrentLng() && !$this->currentUrlIsExcluded()) {
            $html = ob_get_contents();
            $html = $this->translate($html);
            ob_end_clean();
            echo $html;
        }
        $this->generatePoAndMoFilesFromNewTranslations();
    }

    public function getLanguages()
    {
        return $this->args->languages;
    }

    public function getLanguagesWithoutSource()
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

    public function getCurrentLng()
    {
        if ($this->args->lng_target !== null) {
            return $this->args->lng_target;
        }
        foreach ($this->getLanguages() as $languages__value) {
            if (strpos($this->original_request_uri, '/' . $languages__value . '/') === 0) {
                return $languages__value;
            }
        }
        return $this->args->lng_source;
    }

    public function getLanguagePickerData()
    {
        $data = [];
        foreach ($this->getLanguages() as $languages__value) {
            $data[] = [
                'lng' => $languages__value,
                'url' => $this->getCurrentUrlTranslationsInLanguage($languages__value),
                'active' => false
            ];
        }
        return $data;
    }

    private function shouldBeResetted()
    {
        if (@$_GET['reset'] == 1) {
            return true;
        }
        return false;
    }

    private function initArgs($args)
    {
        $this->args = (object) $args;
        $this->original_request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // store without get parameters
    }

    private function sourceLngIsCurrentLng()
    {
        if ($this->getCurrentLng() === $this->getSourceLng()) {
            return true;
        }
        return false;
    }
    private function currentUrlIsExcluded()
    {
        if ($this->args->exclude !== null && is_array($this->args->exclude)) {
            foreach ($this->args->exclude as $exclude__value) {
                if (
                    strpos(trim($this->original_request_uri, '/'), trim($exclude__value, '/')) === 0
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    private function translate($html)
    {
        $this->html = $html;
        $this->modifyHtml();
        return $this->html;
    }

    private function getSourceLng()
    {
        return $this->args->lng_source;
    }

    private function getLngFolder()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . trim($this->args->lng_folder, '/');
    }

    private function getLngFilename($type, $lng)
    {
        return $this->getLngFolder() . '/' . $lng . '.' . $type;
    }

    private function deletePoMoFiles()
    {
        $files = glob($this->getLngFolder() . '/*'); // get all file names
        foreach ($files as $files__value) {
            if (is_file($files__value)) {
                if (
                    strpos($files__value, '.po') !== false ||
                    strpos($files__value, '.mo') !== false
                ) {
                    @unlink($files__value);
                }
            }
        }
    }

    private function createLngFolderIfNotExists()
    {
        if (!is_dir($this->getLngFolder())) {
            mkdir($this->getLngFolder(), 0777, true);
        }
    }

    private function createPoFilesIfNotExists()
    {
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $filename = $this->getLngFilename('po', $languages__value);
            if (!file_exists($filename)) {
                file_put_contents(
                    $filename,
                    '# gtbabel
msgid ""
msgstr ""
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=n != 1;\n"
"Language: ' .
                        $languages__value .
                        '\n"'
                );
            }
        }
    }

    private function preloadTranslationsInCache()
    {
        $this->translations = [];
        $this->translations_cache = [];
        $poLoader = new PoLoader();
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $this->translations_cache[$languages__value] = [];
            $this->translations[$languages__value] = $poLoader->loadFile(
                $this->getLngFilename('po', $languages__value)
            );
            foreach (
                $this->translations[$languages__value]->getTranslations()
                as $translations__value
            ) {
                $this->translations_cache[$languages__value][
                    $translations__value->getOriginal()
                ] = $translations__value->getTranslation();
            }
        }
    }

    private function modifyHtml()
    {
        $this->setupDomDocument();
        $this->modifyTextNodes();
        $this->modifyLinks();
        $this->html = $this->DOMDocument->saveHTML();
    }

    private function setupDomDocument()
    {
        $this->DOMDocument = new \DOMDocument();

        // if the html source doesn't contain a valid utf8 header, domdocument interprets is as iso
        // we circumvent this with mb_convert_encoding
        //@$this->DOMDocument->loadHTML($this->html);
        @$this->DOMDocument->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'));
        $this->DOMXpath = new \DOMXpath($this->DOMDocument);
    }

    private function modifyTextNodes()
    {
        $groups = [];

        // index all nodes (for later sorting out duplicates)
        $nodes = $this->DOMXpath->query('/html/body//node()');
        $nodes_id = 1;
        foreach ($nodes as $nodes__value) {
            $nodes__value->id = $nodes_id;
            $nodes_id++;
        }

        $to_delete = [];
        $textnodes = $this->DOMXpath->query('/html/body//text()');
        foreach ($textnodes as $textnodes__value) {
            if ($this->stringShouldNotBeTranslated($textnodes__value->nodeValue)) {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'script') {
                continue;
            }
            if (array_key_exists($textnodes__value->id, $to_delete)) {
                continue;
            }
            $group = $this->getNearestLogicalGroup($textnodes__value);
            if (array_key_exists($group->id, $to_delete)) {
                continue;
            }
            $groups[] = $group;
            $children = $this->getChildrenOfNode($group);
            foreach ($children as $children__value) {
                $to_delete[$children__value->id] = true;
            }
        }
        foreach ($groups as $groups__key => $groups__value) {
            if (array_key_exists($groups__value->id, $to_delete)) {
                unset($groups[$groups__key]);
            }
        }
        $groups = array_values($groups);

        foreach ($groups as $groups__value) {
            if ($this->isTextNode($groups__value)) {
                $originalText = $groups__value->nodeValue;
            } else {
                $originalText = $this->getInnerHtml($groups__value);
            }

            $originalText = $this->formatText($originalText);

            [$originalTextWithPlaceholders, $mappingTable] = $this->placeholderConversionIn(
                $originalText
            );

            $translatedTextWithPlaceholders = $this->getTranslationAndAddDynamicallyIfNeeded(
                $originalTextWithPlaceholders,
                $this->getCurrentLng()
            );

            $translatedText = $this->placeholderConversionOut(
                $translatedTextWithPlaceholders,
                $mappingTable
            );

            if ($this->isTextNode($groups__value)) {
                $groups__value->nodeValue = $translatedText;
            } else {
                $this->setInnerHtml($groups__value, $translatedText);
            }
        }
    }

    private function getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $comment = null)
    {
        $trans = $this->getExistingTranslationFromCache($orig, $lng);
        if ($trans === false) {
            $trans = $this->addTranslationToGettext($orig, $lng, $comment);
        }
        return $trans;
    }

    private function addTranslationToGettext($orig, $lng, $comment = null)
    {
        if ($this->auto_translation === false) {
            $trans = $this->translateStringMock($orig, $lng, $comment);
        } else {
            $trans = $this->translateStringWithGoogle($orig, $lng, $comment);
        }
        $this->createNewTranslation($orig, $trans, $lng, $comment);
        return $trans;
    }

    private function placeholderConversionIn($str)
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
                $placeholder =
                    '<' . substr($matches__value, $pos_begin, $pos_end - $pos_begin) . '>';
                $str = $this->str_replace_first($matches__value, $placeholder, $str);
                $mappingTable[] = [$placeholder, $matches__value];
            }
        }
        return [$str, $mappingTable];
    }

    private function placeholderConversionOut($str, $mappingTable)
    {
        foreach ($mappingTable as $mappingTable__value) {
            $str = $this->str_replace_first($mappingTable__value[0], $mappingTable__value[1], $str);
        }
        return $str;
    }

    private function str_replace_first($search, $replace, $str)
    {
        $newstring = $str;
        $pos = strpos($str, $search);
        if ($pos !== false) {
            $newstring = substr_replace($str, $replace, $pos, strlen($search));
        }
        return $newstring;
    }

    private function findAllOccurences($haystack, $needle)
    {
        $positions = [];
        $pos_last = 0;
        while (($pos_last = strpos($haystack, $needle, $pos_last)) !== false) {
            $positions[] = $pos_last;
            $pos_last = $pos_last + strlen($needle);
        }
        return $positions;
    }

    private function formatText($str)
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

    private function isTextNode($node)
    {
        return @$node->nodeName === '#text';
    }

    private function isInnerTagNode($node)
    {
        if (@$node->tagName == '') {
            return false;
        }
        return in_array($node->tagName, ['a', 'br', 'strong', 'b', 'small']);
    }

    private function sortOutGroupsInGroups($groups)
    {
        $levels_max = 2;
        $to_delete = [];
        foreach ($groups as $groups__key1 => $groups__value1) {
            $is_inside_another_group = false;
            $cur = $groups__value1;
            $level = 0;
            while ($cur->parentNode !== null) {
                foreach ($groups as $groups__key2 => $groups__value2) {
                    if ($groups__key1 === $groups__key2) {
                        continue;
                    }
                    if ($this->nodesAreEqual($groups__value2, $cur->parentNode)) {
                        $is_inside_another_group = true;
                        break 2;
                    }
                }
                $cur = $cur->parentNode;
                $level++;
                if ($level > $levels_max) {
                    break;
                }
            }
            if ($is_inside_another_group === true) {
                $to_delete[] = $groups__key1;
            }
        }
        foreach ($to_delete as $to_delete__value) {
            unset($groups[$to_delete__value]);
        }
        $groups = array_values($groups);
        return $groups;
    }

    private function getInnerHtml($node)
    {
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $node->ownerDocument->saveXML($child);
        }
        return $inner;
    }

    private function setInnerHtml($node, $value)
    {
        for ($x = $node->childNodes->length - 1; $x >= 0; $x--) {
            $node->removeChild($node->childNodes->item($x));
        }
        if ($value != '') {
            $f = $node->ownerDocument->createDocumentFragment();
            $result = @$f->appendXML($value);
            if ($result) {
                if ($f->hasChildNodes()) {
                    $node->appendChild($f);
                }
            } else {
                $f = new \DOMDocument();
                $value = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');
                $result = @$f->loadHTML('<htmlfragment>' . $value . '</htmlfragment>');
                if ($result) {
                    $import = $f->getElementsByTagName('htmlfragment')->item(0);
                    foreach ($import->childNodes as $child) {
                        $importedNode = $node->ownerDocument->importNode($child, true);
                        $node->appendChild($importedNode);
                    }
                } else {
                }
            }
        }
    }

    private function sortOutDuplicates($groups)
    {
        $unique = [];
        foreach ($groups as $groups__value) {
            $unique[$groups__value->id] = $groups__value;
        }
        return array_values($unique);
    }

    private function nodesAreEqual($node1, $node2)
    {
        return $node1->id === $node2->id;
    }

    private function getChildrenCountOfNodeTagsOnly($node)
    {
        return $this->DOMXpath->evaluate('count(.//*)', $node);
    }

    private function getChildrenOfNode($node)
    {
        return $this->DOMXpath->query('.//node()', $node);
    }

    private function getNearestLogicalGroup($node)
    {
        /* TODO: Make the empty(array_filter) much more effective */

        //$this->lb();
        $parent = $node->parentNode;

        /*
        foo <=
        <a href="#">bar</a>
        */
        if (
            empty(
                array_filter($this->getSiblingsAndOneSelf($node), function ($nodes__value) {
                    return !(
                        $this->isTextNode($nodes__value) ||
                        ($this->isInnerTagNode($nodes__value) &&
                            $this->getChildrenCountOfNodeTagsOnly($nodes__value) <= 2)
                    );
                })
            )
        ) {
            return $parent;
        }
        /*
        <span>foo</span> <=
        <a href="#">bar</a>
        */
        if ($this->isInnerTagNode($parent)) {
            if (
                empty(
                    array_filter($this->getSiblingsAndOneSelf($parent), function ($nodes__value) {
                        return !(
                            $this->isTextNode($nodes__value) ||
                            ($this->isInnerTagNode($nodes__value) &&
                                $this->getChildrenCountOfNodeTagsOnly($nodes__value) <= 2)
                        );
                    })
                )
            ) {
                return $parent->parentNode;
            }
        }
        /*
        foo
        bar
        */
        //$this->le();
        //die();
        return $node;
    }

    private function getSiblingsAndOneSelf($node)
    {
        $siblings = [];
        $cur = $node;
        while ($cur->previousSibling !== null) {
            $siblings[] = $cur;
            $cur = $cur->previousSibling;
        }
        $cur = $node;
        while ($cur->nextSibling !== null) {
            $siblings[] = $cur;
            $cur = $cur->nextSibling;
        }
        return $siblings;
    }

    private function generatePoAndMoFilesFromNewTranslations()
    {
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $poGenerator->generateFile(
                $this->translations[$languages__value],
                $this->getLngFilename('po', $languages__value)
            );
            $moGenerator->generateFile(
                $this->translations[$languages__value],
                $this->getLngFilename('mo', $languages__value)
            );
        }
    }

    private function createNewTranslation($orig, $translated, $lng, $comment = null)
    {
        $translation = Translation::create(null, $orig);
        $translation->translate($translated);
        if ($comment !== null) {
            $translation->getComments()->add($comment);
        }
        $this->translations[$lng]->add($translation);
    }

    private function getExistingTranslationFromCache($str, $lng)
    {
        if (!array_key_exists($str, $this->translations_cache[$lng])) {
            return false;
        }
        return $this->translations_cache[$lng][$str];
    }

    private function translateStringMock($str, $lng, $comment = null)
    {
        if ($comment === 'slug') {
            return $str . '-' . $lng;
        }
        return '%|%' . $str . '%|%' . $lng . '%|%';
    }

    private function translateStringWithGoogle($str, $lng, $comment = null)
    {
        $apiKey = $this->args->google_api_key;
        $url =
            'https://www.googleapis.com/language/translate/v2?key=' .
            $apiKey .
            '&q=' .
            rawurlencode($str) .
            '&source=' .
            $this->args->lng_source .
            '&target=' .
            $lng;
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        $responseDecoded = json_decode($response, true);
        curl_close($handle);
        if (@$responseDecoded['data']['translations'][0]['translatedText'] != '') {
            $return = $responseDecoded['data']['translations'][0]['translatedText'];
        } else {
            $return = $str;
        }
        return $return;
    }

    private function modifyLinks()
    {
        $links = $this->DOMXpath->query('/html/body//a');
        foreach ($links as $links__value) {
            $link = $links__value->getAttribute('href');
            if ($link === null || trim($link) === '') {
                continue;
            }
            if (strpos($link, '#') === 0) {
                continue;
            }
            $is_absolute_link = strpos($link, $this->getCurrentHost()) === 0;
            if (strpos($link, 'http') !== false && $is_absolute_link === false) {
                continue;
            }
            if (strpos($link, 'http') === false && strpos($link, ':') !== false) {
                continue;
            }
            $link = str_replace($this->getCurrentHost(), '', $link);
            $url_parts = explode('/', $link);
            foreach ($url_parts as $url_parts__key => $url_parts__value) {
                if ($this->stringShouldNotBeTranslated($url_parts__value)) {
                    continue;
                }
                $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamicallyIfNeeded(
                    $url_parts__value,
                    $this->getCurrentLng(),
                    'slug'
                );
            }
            $link = implode('/', $url_parts);
            $link = '/' . $this->getCurrentLng() . '' . $link;
            if ($is_absolute_link === true) {
                $link = $this->getCurrentHost() . $link;
            }
            $links__value->setAttribute('href', $link);
        }
    }

    private function getCurrentHost()
    {
        return 'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'];
    }

    private function stringShouldNotBeTranslated($str)
    {
        if (trim($str) == '') {
            return true;
        }
        if (is_numeric(trim($str))) {
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
        return false;
    }

    private function initMagicRouter()
    {
        /*
        $url_parts = $this->original_request_uri;
        $url_parts = explode('/', $url_parts);
        foreach ($url_parts as $url_parts__key => $url_parts__value) {
            if ($this->stringShouldNotBeTranslated($url_parts__value)) {
                continue;
            }
            $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamicallyIfNeeded(
                $url_parts__value,
                $this->getCurrentLng(),
                'slug'
            );
        }
        if ($this->original_request_uri === '/en/sample-page/') {
            $this->original_request_uri = '/sample-page/';
        }
        */
    }

    private function addCurrentUrlToTranslations()
    {
        if ($this->getCurrentLng() !== $this->getSourceLng()) {
            return;
        }
        $url_parts = $this->original_request_uri;
        $url_parts = explode('/', $url_parts);
        foreach ($url_parts as $url_parts__value) {
            if ($this->stringShouldNotBeTranslated($url_parts__value)) {
                continue;
            }
            foreach ($this->getLanguagesWithoutSource() as $languages__value) {
                $this->getTranslationAndAddDynamicallyIfNeeded(
                    $url_parts__value,
                    $languages__value,
                    'slug'
                );
            }
        }
    }

    private function getCurrentUrlTranslationsInLanguage($lng)
    {
        $url = $this->original_request_uri;
        // if root
        // if prefixed root
        // if subpath

        return 'TODO3';
    }

    private function lb($message = '')
    {
        if (!isset($GLOBALS['performance'])) {
            $GLOBALS['performance'] = [];
        }
        $GLOBALS['performance'][] = ['time' => microtime(true), 'message' => $message];
    }

    private function le()
    {
        $this->log(
            'script ' .
                $GLOBALS['performance'][count($GLOBALS['performance']) - 1]['message'] .
                ' execution time: ' .
                number_format(
                    microtime(true) -
                        $GLOBALS['performance'][count($GLOBALS['performance']) - 1]['time'],
                    5
                ) .
                ' seconds'
        );
        unset($GLOBALS['performance'][count($GLOBALS['performance']) - 1]);
        $GLOBALS['performance'] = array_values($GLOBALS['performance']);
    }

    private function log($msg)
    {
        $filename = $_SERVER['DOCUMENT_ROOT'] . '/log.txt';
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }
        file_put_contents($filename, $msg . PHP_EOL . @file_get_contents($filename));
    }
}
