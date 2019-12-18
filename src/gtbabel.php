<?php
namespace vielhuber\gtbabel;

use Gettext\Loader\PoLoader;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Cocur\Slugify\Slugify;

class gtbabel
{
    private $args = null;

    private $original_path = null;
    private $original_path_with_args = null;
    private $original_path_args = null;
    private $original_url = null;
    private $original_host = null;

    private $html = null;
    private $translations = null;
    private $translations_cache = null;
    private $translations_cache_reverse = null;
    private $DOMDocument = null;
    private $DOMXpath = null;

    private $group_cache = null;
    private $excluded_nodes = null;

    public function start($args = [])
    {
        $this->lb();
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
        }
        ob_start();
    }

    public function stop()
    {
        if ($this->currentUrlIsExcluded()) {
            return;
        }

        $this->html = ob_get_contents();
        $this->modifyHtml();
        ob_end_clean();
        echo $this->html;

        $this->generatePoAndMoFilesFromNewTranslations();
        $this->le();
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
            if (strpos($this->getCurrentPath(), '/' . $languages__value . '/') === 0) {
                return $languages__value;
            }
        }
        return $this->args->lng_source;
    }

    public function getLanguagePickerData()
    {
        $data = [];
        foreach ($this->getLanguages() as $languages__value) {
            $url = $this->getCurrentUrlTranslationsInLanguage($languages__value);
            $data[] = [
                'lng' => $languages__value,
                'url' => $url,
                'active' => rtrim($url, '/') === rtrim($this->getCurrentUrl(), '/')
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
        $this->group_cache = [];
        $this->original_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // store without get parameters
        $this->original_path_with_args = $_SERVER['REQUEST_URI'];
        $this->original_path_args = str_replace(
            $this->original_path,
            '',
            $this->original_path_with_args
        );
        $this->original_url =
            'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->original_host =
            'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'];
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
        if ($this->args->exclude_urls !== null && is_array($this->args->exclude_urls)) {
            foreach ($this->args->exclude_urls as $exclude__value) {
                if (strpos(trim($this->getCurrentPath(), '/'), trim($exclude__value, '/')) === 0) {
                    return true;
                }
            }
        }
        return false;
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
        $this->translations_cache_reverse = [];
        $poLoader = new PoLoader();
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $this->translations_cache[$languages__value] = [];
            $this->translations_cache_reverse[$languages__value] = [];
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
                $this->translations_cache_reverse[$languages__value][
                    $translations__value->getTranslation()
                ] = $translations__value->getOriginal();
            }
        }
    }

    private function id($node)
    {
        return $node->getNodePath();
    }

    private function preloadExcludedNodes()
    {
        if ($this->args->exclude_dom !== null) {
            foreach ($this->args->exclude_dom as $exclude__value) {
                $nodes = $this->DOMXpath->query(
                    '/html/body//*[contains(concat(" ", normalize-space(@class), " "), " ' .
                        str_replace('.', '', $exclude__value) .
                        ' ")]'
                );
                foreach ($nodes as $nodes__value) {
                    $this->excluded_nodes[$this->id($nodes__value)] = true;
                    foreach ($this->getChildrenOfNode($nodes__value) as $nodes__value__value) {
                        $this->excluded_nodes[$this->id($nodes__value__value)] = true;
                    }
                }
            }
        }
    }

    private function modifyHtml()
    {
        $this->setupDomDocument();
        $this->setLangTags();
        if (!$this->sourceLngIsCurrentLng() && !$this->currentUrlIsExcluded()) {
            $this->preloadExcludedNodes();
            $this->modifyTextNodes();
            $this->modifyLinks();
            $this->modifyGeneral();
        }
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

    private function setLangTags()
    {
        $html_node = $this->DOMXpath->query('/html')[0];
        if ($html_node !== null) {
            $html_node->setAttribute('lang', $this->getCurrentLng());
        }

        $head_node = $this->DOMXpath->query('/html/head')[0];
        if ($head_node !== null) {
            $data = $this->getLanguagePickerData();
            foreach ($data as $data__value) {
                $tag = $this->DOMDocument->createElement('link', '');
                $tag->setAttribute('rel', 'alternate');
                $tag->setAttribute('hreflang', $data__value['lng']);
                $tag->setAttribute('href', $data__value['url']);
                $head_node->appendChild($tag);
            }
        }
    }

    private function modifyTextNodes()
    {
        $groups = [];

        $to_delete = [];
        $textnodes = $this->DOMXpath->query('/html/body//text()');
        foreach ($textnodes as $textnodes__value) {
            if (array_key_exists($this->id($textnodes__value), $this->excluded_nodes)) {
                continue;
            }
            if ($this->stringShouldNotBeTranslated($textnodes__value->nodeValue)) {
                continue;
            }
            if (@$textnodes__value->parentNode->tagName === 'script') {
                continue;
            }
            if (array_key_exists($this->id($textnodes__value), $to_delete)) {
                continue;
            }
            $group = $this->getNearestLogicalGroup($textnodes__value);
            if (array_key_exists($this->id($group), $to_delete)) {
                continue;
            }
            $groups[] = $group;
            $children = $this->getChildrenOfNode($group);
            foreach ($children as $children__value) {
                $to_delete[$this->id($children__value)] = true;
            }
        }
        foreach ($groups as $groups__key => $groups__value) {
            if (array_key_exists($this->id($groups__value), $to_delete)) {
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
            $trans = $this->autoTranslateString($orig, $lng, $comment);
            $this->addTranslationToGettextAndToCache($orig, $trans, $lng, $comment);
        }
        return $trans;
    }

    private function addTranslationToGettextAndToCache($orig, $trans, $lng, $comment = null)
    {
        $this->createNewTranslation($orig, $trans, $lng, $comment);
        $this->translations_cache[$lng][$orig] = $trans;
        $this->translations_cache_reverse[$lng][$trans] = $orig;
    }

    private function autoTranslateString($orig, $to_lng, $comment = null, $from_lng = null)
    {
        if ($this->args->auto_translation === false) {
            $trans = $this->translateStringMock($orig, $to_lng, $comment, $from_lng);
        } else {
            $trans = $this->translateStringWithGoogle($orig, $to_lng, $comment, $from_lng);
        }
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
        return in_array($node->tagName, ['a', 'br', 'strong', 'b', 'small', 'i']);
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

    private function getChildrenCountRecursivelyOfNodeTagsOnly($node)
    {
        return $this->DOMXpath->evaluate('count(.//*)', $node);
    }

    private function getChildrenCountOfNode($node)
    {
        return $this->DOMXpath->evaluate('count(./node())', $node);
    }

    private function getChildrenOfNode($node)
    {
        return $this->DOMXpath->query('.//node()', $node);
    }

    private function getParentNodeWithMoreThanOneChildren($node)
    {
        $cur = $node;
        $level = 0;
        $max_level = 11;
        while ($this->getChildrenCountOfNode($cur) <= 1) {
            $cur = $cur->parentNode;
            if ($cur === null) {
                break;
            }
            $level++;
            if ($level >= $max_level) {
                break;
            }
        }
        return $cur;
    }

    private function getNearestLogicalGroup($node)
    {
        $parent = $this->getParentNodeWithMoreThanOneChildren($node);
        if (!array_key_exists($this->id($parent), $this->group_cache)) {
            $this->group_cache[$this->id($parent)] = false;
            foreach ($this->getChildrenOfNode($parent) as $nodes__value) {
                if (
                    !(
                        $this->isTextNode($nodes__value) ||
                        ($this->isInnerTagNode($nodes__value) &&
                            $this->getChildrenCountRecursivelyOfNodeTagsOnly($nodes__value) <= 2)
                    )
                ) {
                    $this->group_cache[$this->id($parent)] = true;
                    break;
                }
            }
        }
        if ($this->group_cache[$this->id($parent)] === true) {
            return $node;
        }
        return $parent;
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
        if (
            $str === '' ||
            $str === null ||
            $this->translations_cache[$lng] === null ||
            !array_key_exists($str, $this->translations_cache[$lng])
        ) {
            return false;
        }
        return $this->translations_cache[$lng][$str];
    }

    private function getExistingTranslationReverseFromCache($str, $lng)
    {
        if (
            $str === '' ||
            $str === null ||
            $this->translations_cache_reverse[$lng] === null ||
            !array_key_exists($str, $this->translations_cache_reverse[$lng])
        ) {
            return false;
        }
        return $this->translations_cache_reverse[$lng][$str];
    }

    private function translateStringMock($str, $to_lng, $comment = null, $from_lng = null)
    {
        if ($comment === 'slug') {
            return $str . '-' . $to_lng;
        }
        return '%|%' . $str . '%|%' . $to_lng . '%|%';
    }

    private function translateStringWithGoogle($str, $to_lng, $comment = null, $from_lng = null)
    {
        $apiKey = $this->args->google_api_key;
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
        if ($this->firstCharIsUppercase($str) && !$this->firstCharIsUppercase($trans)) {
            $trans = $this->setFirstCharUppercase($trans);
        }

        // slugify
        if ($comment === 'slug') {
            $trans = $this->slugify($trans, $str, $to_lng);
        }

        return $trans;
    }

    private function modifyGeneral()
    {
        $include = [
            'a' => 'href',
            'img' => 'alt',
            'input' => 'placeholder'
        ];
        $include = array_merge($include, $this->args->include);

        /* TODO */
    }

    private function modifyLinks()
    {
        $links = $this->DOMXpath->query('/html/body//a');
        foreach ($links as $links__value) {
            if (array_key_exists($this->id($links__value), $this->excluded_nodes)) {
                continue;
            }
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

    private function stringShouldNotBeTranslated($str)
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
        return false;
    }

    private function getCurrentHost()
    {
        return $this->original_host;
    }

    private function getCurrentUrl()
    {
        return $this->original_url;
    }

    private function getCurrentPath()
    {
        return $this->original_path;
    }

    private function getCurrentUrlTranslationsInLanguage($lng)
    {
        return trim(
            trim($this->getCurrentHost(), '/') .
                '/' .
                trim($this->getCurrentPathTranslationsInLanguage($lng), '/'),
            '/'
        ) . '/';
    }

    private function getTranslationInForeignLng($str, $to_lng, $from_lng = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->getCurrentLng();
        }
        if ($from_lng === $this->getSourceLng()) {
            $str_in_source_lng = $str;
        } else {
            $str_in_source_lng = $this->getExistingTranslationReverseFromCache($str, $from_lng); // str in source lng
        }
        if ($str_in_source_lng === false) {
            return false;
        }
        if ($to_lng === $this->getSourceLng()) {
            return $str_in_source_lng;
        }
        return $this->getExistingTranslationFromCache($str_in_source_lng, $to_lng);
    }

    public function getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng,
        $from_lng = null
    ) {
        $trans = $this->getTranslationInForeignLng($str, $to_lng, $from_lng);
        if ($trans === false) {
            $str_in_source = $this->autoTranslateString(
                $str,
                $this->getSourceLng(),
                null,
                $from_lng
            );
            $trans = $this->autoTranslateString($str, $to_lng, null);
            $this->addTranslationToGettextAndToCache($str_in_source, $str, $from_lng, null);
            $this->addTranslationToGettextAndToCache($str_in_source, $trans, $to_lng, null);
        }
        return $trans;
    }

    private function getCurrentPathTranslationsInLanguage($lng)
    {
        $url = $this->getCurrentPath();
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
        if ($this->getSourceLng() === $lng && $this->args->prefix_source_lng === false) {
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
            $trans = $this->getTranslationInForeignLng($url_parts__value, $lng);
            if ($trans !== false) {
                $url_parts[$url_parts__key] = $trans;
            }
        }
        $url = implode('/', $url_parts);
        return $url;
    }

    private function initMagicRouter()
    {
        $path = $this->getCurrentPathTranslationsInLanguage($this->getSourceLng());
        $path = trim($path, '/');
        $path = '/' . $path . ($path != '' ? '/' : '') . $this->original_path_args;
        $_SERVER['REQUEST_URI'] = $path;
    }

    private function addCurrentUrlToTranslations()
    {
        if ($this->getCurrentLng() !== $this->getSourceLng()) {
            return;
        }
        $url_parts = $this->getCurrentPath();
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

    private function slugify($trans, $orig, $lng)
    {
        $slugify = new Slugify();
        $suggestion = $slugify->slugify($trans, '-');
        if (mb_strlen($suggestion) < mb_strlen($trans) / 2) {
            return $orig . '-' . $lng;
        }
        return $suggestion;
    }

    private function firstCharIsUppercase($str)
    {
        return mb_substr($str, 0, 1) == mb_strtoupper(mb_substr($str, 0, 1));
    }

    private function setFirstCharUppercase($str)
    {
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc . mb_substr($str, 1);
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
