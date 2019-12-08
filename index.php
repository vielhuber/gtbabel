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
    public $debug = true;

    public $html = null;
    public $translations = null;
    public $translations_cache = null;
    public $DOMDocument = null;
    public $DOMXpath = null;

    function translate($html)
    {
        $this->html = $html;
        if ($this->debug === true) {
            $this->resetPoFiles();
        }
        $this->preloadTranslationsInCache();
        $this->modifyHtml();
        return $this->html;
    }

    function resetPoFiles()
    {
        @unlink('locales/gtbabel.po');
        @touch('locales/gtbabel.po');
        file_put_contents(
            'locales/gtbabel.po',
            '# gpbabel
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

    function preloadTranslationsInCache()
    {
        $this->translations = Translations::create('gtbabel');
        $this->translations_cache = [];
        $poLoader = new PoLoader();
        $this->translations = $poLoader->loadFile('locales/gtbabel.po');
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
            $originalText = $this->trimEachLine($originalText);
            //$originalText = trim($originalText);

            $translatedText = $this->getExistingTranslationFromCache($originalText);
            if ($translatedText === false) {
                if ($this->debug === true) {
                    $translatedText = $this->translateStringMock($originalText);
                } else {
                    $translatedText = $this->translateStringWithGoogle($originalText);
                }
                $this->createNewTranslation($originalText, $translatedText);
            }

            if ($this->isTextNode($groups__value)) {
                $groups__value->nodeValue = $translatedText;
            } else {
                $this->setInnerHtml($groups__value, $translatedText);
            }
        }
    }

    function trimEachLine($str)
    {
        $str = trim($str);
        $str = str_replace('&#13;', '', $str); // replace nasty carriage returns \r
        $parts = explode(PHP_EOL, $str);
        foreach ($parts as $parts__key => $parts__value) {
            $parts[$parts__key] = trim($parts__value);
        }
        $str = implode(PHP_EOL, $parts);
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
        $poGenerator->generateFile($this->translations, 'locales/gtbabel.po');
        $moGenerator = new MoGenerator();
        $moGenerator->generateFile($this->translations, 'locales/gtbabel.mo');
    }

    function createNewTranslation($orig, $translated)
    {
        $translation = Translation::create(null, $orig);
        $translation->translate($translated);
        $this->translations->add($translation);
    }

    function getExistingTranslationFromCache($str)
    {
        if (!array_key_exists($str, $this->translations_cache)) {
            var_dump($str);
            var_dump($this->translations_cache);
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
        return mt_rand(100, 200);
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
        $return .= mt_rand(100, 999);
        return $return;
    }
}

/* start */
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY'));
define('LNG_SOURCE', 'DE');
define('LNG_TARGET', 'EN');
ob_start();
/* main app */
require_once 'tpl/simple5.html';
/* end */
$html = ob_get_contents();
$gtbabel = new gtbabel();
$html = $gtbabel->translate($html);
ob_end_clean();
echo $html;
