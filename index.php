<?php
require_once __DIR__ . '/vendor/autoload.php';
use Gettext\Loader\PoLoader;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Gettext\Translations;
use Dotenv\Dotenv;

class gtbabel
{
    public $reset = true;
    public $auto_translation = false;

    public $html = null;
    public $translations = null;
    public $translations_cache = null;
    public $DOMDocument = null;
    public $DOMXpath = null;

    function translate($html)
    {
        $this->html = $html;
        if ($this->reset === true) {
            $this->deletePoFiles();
        }
        $this->createPoFilesIfNotExists();
        $this->preloadTranslationsInCache();
        $this->modifyHtml();
        return $this->html;
    }

    function getLngFilename($type)
    {
        return LNG_FOLDER . '/' . mb_strtolower(LNG_TARGET) . '.' . $type;
    }

    function deletePoFiles()
    {
        @unlink($this->getLngFilename('po'));
        @unlink($this->getLngFilename('po'));
    }

    function createPoFilesIfNotExists()
    {
        if (!is_dir(LNG_FOLDER)) {
            mkdir(LNG_FOLDER, 0777, true);
        }
        $filename = $this->getLngFilename('po');
        if (!file_exists($filename)) {
            touch($filename);
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
                    LNG_TARGET .
                    '\n"'
            );
        }
    }

    function preloadTranslationsInCache()
    {
        $this->translations = Translations::create('gtbabel');
        $this->translations_cache = [];
        $poLoader = new PoLoader();
        $this->translations = $poLoader->loadFile($this->getLngFilename('po'));
        foreach ($this->translations->getTranslations() as $translations__value) {
            $this->translations_cache[
                $translations__value->getOriginal()
            ] = $translations__value->getTranslation();
        }
    }

    function modifyHtml()
    {
        $this->setupDomDocument();
        $this->modifyTextNodes();
        $this->modifyLinks();
        $this->generatePoAndMoFilesFromNewTranslations();
        $this->html = $this->DOMDocument->saveHTML();
    }

    function setupDomDocument()
    {
        $this->DOMDocument = new DOMDocument();

        // if the html source doesn't contain a valid utf8 header, domdocument interprets is as iso
        // we circumvent this with mb_convert_encoding
        //@$this->DOMDocument->loadHTML($this->html);
        @$this->DOMDocument->loadHTML(mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8'));
        $this->DOMXpath = new DOMXpath($this->DOMDocument);
    }

    function modifyTextNodes()
    {
        $groups = [];

        $textnodes = $this->DOMXpath->query('/html/body//text()');
        foreach ($textnodes as $textnodes__value) {
            if (trim($textnodes__value->nodeValue) == '') {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'script') {
                continue;
            }
            $group = $this->getNearestLogicalGroup($textnodes__value);
            $groups[] = $group;
        }

        $groups = $this->sortOutGroupsInGroups($groups);
        $groups = $this->sortOutDuplicates($groups);

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

            $translatedTextWithPlaceholders = $this->getTranslationAndAddDynamically(
                $originalTextWithPlaceholders
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

    function getTranslationAndAddDynamically($orig, $comment = null)
    {
        $trans = $this->getExistingTranslationFromCache($orig);
        if ($trans === false) {
            if ($this->auto_translation === false) {
                $trans = $this->translateStringMock($orig);
            } else {
                $trans = $this->translateStringWithGoogle($orig);
            }
            $this->createNewTranslation($orig, $trans, $comment);
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
                $placeholder =
                    '<' . substr($matches__value, $pos_begin, $pos_end - $pos_begin) . '>';
                $str = $this->str_replace_first($matches__value, $placeholder, $str);
                $mappingTable[] = [$placeholder, $matches__value];
            }
        }
        return [$str, $mappingTable];
    }

    function placeholderConversionOut($str, $mappingTable)
    {
        foreach ($mappingTable as $mappingTable__value) {
            $str = $this->str_replace_first($mappingTable__value[0], $mappingTable__value[1], $str);
        }
        return $str;
    }

    function str_replace_first($search, $replace, $str)
    {
        $newstring = $str;
        $pos = strpos($str, $search);
        if ($pos !== false) {
            $newstring = substr_replace($str, $replace, $pos, strlen($search));
        }
        return $newstring;
    }

    function findAllOccurences($haystack, $needle)
    {
        $positions = [];
        $pos_last = 0;
        while (($pos_last = strpos($haystack, $needle, $pos_last)) !== false) {
            $positions[] = $pos_last;
            $pos_last = $pos_last + strlen($needle);
        }
        return $positions;
    }

    function formatText($str)
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

    function isTextNode($node)
    {
        return @$node->nodeName === '#text';
    }

    function isInnerTagNode($node)
    {
        if (@$node->tagName == '') {
            return false;
        }
        return in_array($node->tagName, ['a', 'br', 'strong', 'b']);
    }

    function sortOutGroupsInGroups($groups)
    {
        $to_delete = [];
        foreach ($groups as $groups__key1 => $groups__value1) {
            $is_inside_another_group = false;
            $cur = $groups__value1;
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

    function getInnerHtml($node)
    {
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $node->ownerDocument->saveXML($child);
        }
        return $inner;
    }

    function setInnerHtml($node, $value)
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
                $f = new DOMDocument();
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

    function sortOutDuplicates($groups)
    {
        $unique = [];
        foreach ($groups as $groups__value) {
            $unique[print_r($groups__value, true)] = $groups__value;
        }
        return array_values($unique);
    }

    function nodesAreEqual($node1, $node2)
    {
        return print_r($node1, true) === print_r($node2, true);
    }

    function getNearestLogicalGroup($node)
    {
        if (
            empty(
                array_filter($this->getSiblingsAndOneSelf($node), function ($nodes__value) {
                    return !$this->isInnerTagNode($nodes__value) &&
                        !$this->isTextNode($nodes__value);
                })
            )
        ) {
            return $node->parentNode;
        }
        if ($this->isInnerTagNode($node->parentNode)) {
            if (
                empty(
                    array_filter($this->getSiblingsAndOneSelf($node->parentNode), function (
                        $nodes__value
                    ) {
                        return !$this->isInnerTagNode($nodes__value) &&
                            !$this->isTextNode($nodes__value);
                    })
                )
            ) {
                return $node->parentNode->parentNode;
            }
        }
        return $node;
    }

    function getSiblingsAndOneSelf($node)
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

    function generatePoAndMoFilesFromNewTranslations()
    {
        $poGenerator = new PoGenerator();
        $poGenerator->generateFile($this->translations, $this->getLngFilename('po'));
        $moGenerator = new MoGenerator();
        $moGenerator->generateFile($this->translations, $this->getLngFilename('mo'));
    }

    function createNewTranslation($orig, $translated, $comment = null)
    {
        $translation = Translation::create(null, $orig);
        $translation->translate($translated);
        if ($comment !== null) {
            $translation->getComments()->add($comment);
        }
        $this->translations->add($translation);
    }

    function getExistingTranslationFromCache($str)
    {
        if (!array_key_exists($str, $this->translations_cache)) {
            return false;
        }
        return $this->translations_cache[$str];
    }

    function translateStringMock($str)
    {
        return '%|%' . $str . '%|%';
    }

    function translateStringWithGoogle($str)
    {
        $apiKey = GOOGLE_API_KEY;
        $url =
            'https://www.googleapis.com/language/translate/v2?key=' .
            $apiKey .
            '&q=' .
            rawurlencode($str) .
            '&source=' .
            strtolower(LNG_SOURCE) .
            '&target=' .
            strtolower(LNG_TARGET);
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

    function modifyLinks()
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
                if (trim($url_parts__value) == '') {
                    continue;
                }
                $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamically(
                    $url_parts__value,
                    'link'
                );
            }
            $link = implode('/', $url_parts);
            if ($is_absolute_link === true) {
                $link = $this->getCurrentHost() . $link;
            }
            $links__value->setAttribute('href', $link);
        }
    }

    function getCurrentHost()
    {
        return 'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'];
    }
}

/* start */
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY'));
define('LNG_FOLDER', 'locales');
define('LNG_SOURCE', 'DE');
define('LNG_TARGET', 'EN');
define('PREFIX_DEFAULT_LANG', false);
define('LANGUAGES', ['DE', 'EN', 'FR']);
ob_start();
/* main app */
require_once 'tpl/complex/1.html';
/* end */
$html = ob_get_contents();
$gtbabel = new gtbabel();
$html = $gtbabel->translate($html);
ob_end_clean();
echo $html;
