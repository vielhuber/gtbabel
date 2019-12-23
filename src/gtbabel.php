<?php
namespace vielhuber\gtbabel;

use Gettext\Loader\MoLoader;
use Gettext\Loader\PoLoader;
use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Gettext\Translations;
use Cocur\Slugify\Slugify;

class gtbabel
{
    private $args = null;

    private $original_path = null;
    private $original_path_with_args = null;
    private $original_args = null;
    private $original_url = null;
    private $original_url_with_args = null;
    private $original_host = null;

    private $html = null;

    private $gettext = null;
    private $gettext_cache = null;
    private $gettext_cache_reverse = null;
    private $gettext_pot = null;
    private $gettext_pot_cache = null;
    private $gettext_save_counter = null;

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
            $this->deletePotPoMoFiles();
        }
        $this->preloadGettextInCache();
        $this->redirectPrefixedSourceLng();
        $this->addCurrentUrlToTranslations();
        if (!$this->currentUrlIsExcluded()) {
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

        $this->generateGettextFiles();
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

    public function getCurrentPrefix()
    {
        foreach ($this->getLanguages() as $languages__value) {
            if (strpos($this->getCurrentPath(), '/' . $languages__value . '/') === 0) {
                return $languages__value;
            }
        }
        return null;
    }

    public function getCurrentLng()
    {
        if ($this->args->lng_target !== null) {
            return $this->args->lng_target;
        }
        return $this->getCurrentPrefix() ?? $this->args->lng_source;
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
        $this->original_args = str_replace(
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
        $this->original_url_with_args =
            'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'];
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
        return $this->urlIsExcluded($this->getCurrentPath());
    }

    private function urlIsExcluded($url)
    {
        if ($this->args->exclude_urls !== null && is_array($this->args->exclude_urls)) {
            foreach ($this->args->exclude_urls as $exclude__value) {
                if (strpos(trim($url, '/'), trim($exclude__value, '/')) !== false) {
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

    private function deletePotPoMoFiles()
    {
        $files = glob($this->getLngFolder() . '/*'); // get all file names
        foreach ($files as $files__value) {
            if (is_file($files__value)) {
                if (
                    strpos($files__value, '.pot') !== false ||
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

    private function preloadGettextInCache()
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
                $this->gettext[$languages__value] = $moLoader->loadFile(
                    $this->getLngFilename('mo', $languages__value)
                );
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

    private function id($node)
    {
        return $node->getNodePath();
    }

    private function transformSelectorToXpath($selector)
    {
        $xpath = './/';

        $parts = explode(' ', $selector);
        foreach ($parts as $parts__key => $parts__value) {
            // input[placeholder] => input[@placeholder]
            if (strpos($parts__value, '[') !== false) {
                $parts__value = str_replace('[', '[@', $parts__value);
            }
            // .foo => *[contains(concat(" ", normalize-space(@class), " "), " foo ")]
            if (strpos($parts__value, '.') !== false) {
                $parts__value_parts = explode('.', $parts__value);
                foreach (
                    $parts__value_parts
                    as $parts__value_parts__key => $parts__value_parts__value
                ) {
                    if ($parts__value_parts__key === 0 && $parts__value_parts__value === '') {
                        $parts__value_parts[$parts__value_parts__key] = '*';
                    }
                    if ($parts__value_parts__key > 0) {
                        $parts__value_parts[$parts__value_parts__key] =
                            '[contains(concat(" ", normalize-space(@class), " "), " ' .
                            $parts__value_parts__value .
                            ' ")]';
                    }
                }
                $parts__value = implode('', $parts__value_parts);
            }
            $parts[$parts__key] = $parts__value;
        }
        $xpath .= implode('//', $parts);
        return $xpath;
    }

    private function preloadExcludedNodes()
    {
        $this->excluded_nodes = [];
        if ($this->args->exclude_dom !== null) {
            foreach ($this->args->exclude_dom as $exclude__value) {
                $nodes = $this->DOMXpath->query($this->transformSelectorToXpath($exclude__value));
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
        $this->preloadExcludedNodes();
        $this->modifyTextNodes();
        $this->modifyTagNodes();
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
        if ($this->args->translate_text_nodes === false) {
            return;
        }
        if ($this->sourceLngIsCurrentLng()) {
            return;
        }

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

            $translatedTextWithPlaceholders = $this->prepareTranslationAndAddDynamicallyIfNeeded(
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

    private function prepareTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        if ($context === 'slug') {
            $link = $orig;
            if ($link === null || trim($link) === '') {
                return $link;
            }
            if (strpos($link, '#') === 0) {
                return $link;
            }
            $is_absolute_link = strpos($link, $this->getCurrentHost()) === 0;
            if (strpos($link, 'http') !== false && $is_absolute_link === false) {
                return $link;
            }
            if (strpos($link, 'http') === false && strpos($link, ':') !== false) {
                return $link;
            }
            $link = str_replace($this->getCurrentHost(), '', $link);
            $url_parts = explode('/', $link);
            foreach ($url_parts as $url_parts__key => $url_parts__value) {
                if ($this->stringShouldNotBeTranslated($url_parts__value)) {
                    continue;
                }
                $url_parts[$url_parts__key] = $this->getTranslationAndAddDynamicallyIfNeeded(
                    $url_parts__value,
                    $lng,
                    'slug'
                );
            }
            $link = implode('/', $url_parts);
            $link = '/' . $lng . '' . $link;
            if ($is_absolute_link === true) {
                $link = $this->getCurrentHost() . $link;
            }
            return $link;
        }
        if ($context === 'title') {
            foreach (
                ['-', '–', '—', ':', '·', '•', '*', '⋆', '|', '~', '«', '»', '<', '>']
                as $delimiters__value
            ) {
                if (strpos($orig, ' ' . $delimiters__value . ' ') !== false) {
                    $orig_parts = explode(' ' . $delimiters__value . ' ', $orig);
                    foreach ($orig_parts as $orig_parts__key => $orig_parts__value) {
                        $trans = $this->getTranslationAndAddDynamicallyIfNeeded(
                            $orig_parts__value,
                            $lng,
                            $context
                        );
                        $orig_parts[$orig_parts__key] = $trans;
                    }
                    $trans = implode(' ' . $delimiters__value . ' ', $orig_parts);
                    return $trans;
                }
            }
        }
        return $this->getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context);
    }

    private function getTranslationAndAddDynamicallyIfNeeded($orig, $lng, $context = null)
    {
        $trans = $this->getExistingTranslationFromCache($orig, $lng, $context);
        if ($trans === false) {
            $this->addStringToPotFileAndToCache($orig, $context);
            $trans = $this->autoTranslateString($orig, $lng, $context);
            if ($this->args->google_translation === true) {
                $this->addTranslationToPoFileAndToCache($orig, $trans, $lng, $context);
            }
        }
        return $trans;
    }

    private function addStringToPotFileAndToCache($str, $context)
    {
        $translation = Translation::create($context, $str);
        $translation->translate('');
        $this->gettext_pot->add($translation);
        $this->gettext_pot_cache[$context][$str] = null;
        $this->gettext_save_counter['pot'] = true;
    }

    private function addTranslationToPoFileAndToCache($orig, $trans, $lng, $context = null)
    {
        $translation = Translation::create($context, $orig);
        $translation->translate($trans);
        $this->gettext[$lng]->add($translation);
        $this->gettext_cache[$lng][$context ?? ''][$orig] = $trans;
        $this->gettext_cache_reverse[$lng][$context ?? ''][$trans] = $orig;
        $this->gettext_save_counter['po'][$lng] = true;
    }

    private function autoTranslateString($orig, $to_lng, $context = null, $from_lng = null)
    {
        if ($this->args->google_translation === false) {
            $trans = $this->translateStringMock($orig, $to_lng, $context, $from_lng);
        } else {
            $trans = $this->translateStringWithGoogle($orig, $to_lng, $context, $from_lng);
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

    private function generateGettextFiles()
    {
        $poGenerator = new PoGenerator();
        $moGenerator = new MoGenerator();

        if ($this->gettext_save_counter['pot'] === true) {
            $poGenerator->generateFile(
                $this->gettext_pot,
                $this->getLngFilename('pot', '_template')
            );
        }

        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
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

    private function getExistingTranslationFromCache($str, $lng, $context = null)
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

    private function getExistingTranslationReverseFromCache($str, $lng, $context = null)
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

    private function translateStringMock($str, $to_lng, $context = null, $from_lng = null)
    {
        if ($context === 'slug') {
            return $str . '-' . $to_lng;
        }
        return '%|%' . $str . '%|%' . $to_lng . '%|%';
    }

    private function translateStringWithGoogle($str, $to_lng, $context = null, $from_lng = null)
    {
        $apiKey = $this->args->google_translation_api_key;
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
        if ($context === 'slug') {
            $trans = $this->slugify($trans, $str, $to_lng);
        }

        return $trans;
    }

    private function modifyTagNodes()
    {
        if ($this->sourceLngIsCurrentLng() && $this->args->prefix_source_lng === false) {
            return;
        }

        $include = [];

        if ($this->args->translate_default_tag_nodes === true) {
            $include = array_merge($include, [
                [
                    'selector' => 'a',
                    'attribute' => 'href',
                    'context' => 'slug'
                ],
                [
                    'selector' => 'form',
                    'attribute' => 'action',
                    'context' => 'slug'
                ],
                [
                    'selector' => 'img',
                    'attribute' => 'alt',
                    'context' => null
                ],
                [
                    'selector' => 'input',
                    'attribute' => 'placeholder',
                    'context' => null
                ],
                [
                    'selector' => 'head title',
                    'attribute' => null,
                    'context' => 'title'
                ],
                [
                    'selector' => 'head meta[name="description"]',
                    'attribute' => 'content',
                    'context' => null
                ]
            ]);
        }

        $include = array_merge($include, $this->args->include);

        foreach ($include as $include__value) {
            $nodes = $this->DOMXpath->query(
                $this->transformSelectorToXpath($include__value['selector'])
            );
            if (!empty($nodes)) {
                foreach ($nodes as $nodes__value) {
                    if (array_key_exists($this->id($nodes__value), $this->excluded_nodes)) {
                        continue;
                    }
                    if (@$include__value['attribute'] != '') {
                        $value = $nodes__value->getAttribute($include__value['attribute']);
                    } else {
                        $value = $nodes__value->nodeValue;
                    }
                    if ($value != '') {
                        $context = null;
                        if (@$include__value['context'] != '') {
                            $context = $include__value['context'];
                        }
                        if (
                            $include__value['selector'] === 'a' &&
                            $include__value['attribute'] === 'href'
                        ) {
                            $context = 'slug';
                        }
                        if (strpos($value, $this->getCurrentHost()) === 0) {
                            $context = 'slug';
                        }

                        if ($context === 'slug' && $this->urlIsExcluded($value)) {
                            continue;
                        }

                        if ($this->sourceLngIsCurrentLng()) {
                            if ($context === 'slug') {
                                if (
                                    $value === null ||
                                    trim($value) === '' ||
                                    strpos($value, '#') === 0
                                ) {
                                    continue;
                                }
                                $is_absolute_link = strpos($value, $this->getCurrentHost()) === 0;
                                if (
                                    strpos($value, 'http') !== false &&
                                    $is_absolute_link === false
                                ) {
                                    continue;
                                }
                                if (
                                    strpos($value, 'http') === false &&
                                    strpos($value, ':') !== false
                                ) {
                                    continue;
                                }
                                $value = str_replace($this->getCurrentHost(), '', $value);
                                $value = '/' . $this->getCurrentLng() . '' . $value;
                                if ($is_absolute_link === true) {
                                    $value = $this->getCurrentHost() . $value;
                                }
                                $trans = $value;
                            } else {
                                continue;
                            }
                        } else {
                            $trans = $this->prepareTranslationAndAddDynamicallyIfNeeded(
                                $value,
                                $this->getCurrentLng(),
                                $context
                            );
                        }

                        if (@$include__value['attribute'] != '') {
                            $nodes__value->setAttribute($include__value['attribute'], $trans);
                        } else {
                            $nodes__value->nodeValue = $trans;
                        }
                    }
                }
            }
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

    private function getCurrentUrlWithArgs()
    {
        return $this->original_url_with_args;
    }

    private function getCurrentPath()
    {
        return $this->original_path;
    }

    private function getCurrentPathWithArgs()
    {
        return $this->original_path_with_args;
    }

    private function getCurrentUrlTranslationsInLanguage($lng)
    {
        return trim(
            trim($this->getCurrentHost(), '/') .
                '/' .
                trim($this->getCurrentPathTranslationsInLanguage($lng, false), '/'),
            '/'
        ) . '/';
    }

    private function getTranslationInForeignLng($str, $to_lng, $from_lng = null, $context = null)
    {
        if ($from_lng === null) {
            $from_lng = $this->getCurrentLng();
        }
        if ($from_lng === $this->getSourceLng()) {
            $str_in_source_lng = $str;
        } else {
            $str_in_source_lng = $this->getExistingTranslationReverseFromCache(
                $str,
                $from_lng,
                $context
            ); // str in source lng
        }
        if ($str_in_source_lng === false) {
            return false;
        }
        if ($to_lng === $this->getSourceLng()) {
            return $str_in_source_lng;
        }
        return $this->getExistingTranslationFromCache($str_in_source_lng, $to_lng, $context);
    }

    public function getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng,
        $from_lng = null,
        $context = null
    ) {
        $trans = $this->getTranslationInForeignLng($str, $to_lng, $from_lng, $context);
        if ($trans === false) {
            $str_in_source = $this->autoTranslateString(
                $str,
                $this->getSourceLng(),
                $context,
                $from_lng
            );
            $this->addStringToPotFileAndToCache($str_in_source, $context);
            $trans = $this->autoTranslateString($str, $to_lng, $context);
            if ($this->args->google_translation === true) {
                $this->addTranslationToPoFileAndToCache($str_in_source, $str, $from_lng, $context);
                $this->addTranslationToPoFileAndToCache($str_in_source, $trans, $to_lng, $context);
            }
        }
        return $trans;
    }

    private function getCurrentPathTranslationsInLanguage($lng, $always_remove_prefix = false)
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
        if (
            $always_remove_prefix === true ||
            ($this->getSourceLng() === $lng && $this->args->prefix_source_lng === false)
        ) {
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
            $trans = $this->getTranslationInForeignLng($url_parts__value, $lng, null, 'slug');
            if ($trans !== false) {
                $url_parts[$url_parts__key] = $trans;
            }
        }
        $url = implode('/', $url_parts);
        return $url;
    }

    private function modifyPrefixedSourceLngLinks()
    {
        if ($this->args->prefix_source_lng === false) {
            return;
        }
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        $nodes = $this->DOMXpath->query($this->transformSelectorToXpath('a'));
        if (!empty($nodes)) {
            foreach ($nodes as $nodes__value) {
                if (array_key_exists($this->id($nodes__value), $this->excluded_nodes)) {
                    continue;
                }
                $value = $nodes__value->getAttribute('href');
                if ($value != '') {
                    $link = $value;
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
                    $link = '/' . $this->getCurrentLng() . '' . $link;
                    if ($is_absolute_link === true) {
                        $link = $this->getCurrentHost() . $link;
                    }
                    $nodes__value->setAttribute('href', $link);
                }
            }
        }
    }

    private function redirectPrefixedSourceLng()
    {
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        if (
            $this->args->prefix_source_lng === false &&
            $this->getCurrentPrefix() !== $this->getSourceLng()
        ) {
            return;
        }
        if ($this->args->prefix_source_lng === true && $this->getCurrentPrefix() !== null) {
            return;
        }
        if ($this->args->prefix_source_lng === false) {
            $url =
                trim($this->getCurrentHost(), '/') .
                '/' .
                str_replace($this->getSourceLng() . '/', '', $this->getCurrentPathWithArgs());
        } else {
            $url = '';
            $url .= trim($this->getCurrentHost(), '/');
            $url .= '/';
            $url .= $this->getCurrentLng();
            $url .= '/';
            if (trim($this->getCurrentPath(), '/') != '') {
                $url .= trim($this->getCurrentPath(), '/') . '/';
            }
        }

        header('Location: ' . $url, true, 301);
        die();
    }

    private function initMagicRouter()
    {
        if ($this->sourceLngIsCurrentLng()) {
            if ($this->args->prefix_source_lng === false) {
                return;
            }
            if (strpos($this->getCurrentPathWithArgs(), '/' . $this->getSourceLng()) === 0) {
                $path = substr(
                    $this->getCurrentPathWithArgs(),
                    mb_strlen('/' . $this->getSourceLng())
                );
            }
        } else {
            $path = $this->getCurrentPathTranslationsInLanguage($this->getSourceLng(), true);
            $path = trim($path, '/');
            $path = '/' . $path . ($path != '' ? '/' : '') . $this->original_args;
        }
        $_SERVER['REQUEST_URI'] = $path;
    }

    private function addCurrentUrlToTranslations()
    {
        if (!$this->sourceLngIsCurrentLng()) {
            return;
        }
        foreach ($this->getLanguagesWithoutSource() as $languages__value) {
            $this->prepareTranslationAndAddDynamicallyIfNeeded(
                $this->getCurrentUrl(),
                $languages__value,
                'slug'
            );
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
