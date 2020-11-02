<?php
namespace vielhuber\gtbabel;

class Host
{
    public $original_path;
    public $original_path_with_args;
    public $original_args;
    public $original_url;
    public $original_url_with_args;
    public $original_host;

    function __construct(Settings $settings = null, Log $log = null)
    {
        $this->settings = $settings ?: new Settings();
        $this->log = $log ?: new Log();
    }

    function setup()
    {
        $this->original_path = $this->getCurrentPathConverted();
        $this->original_path_with_args = $this->getCurrentPathWithArgsConverted();
        $this->original_args = $this->getCurrentArgsConverted();
        $this->original_url = $this->getCurrentUrlConverted();
        $this->original_url_with_args = $this->getCurrentUrlWithArgsConverted();
        $this->original_host = $this->getCurrentHostConverted();
    }

    function getCurrentPathConverted()
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    function getCurrentPathWithArgsConverted()
    {
        return $_SERVER['REQUEST_URI'];
    }

    function getCurrentArgsConverted()
    {
        return str_replace($this->original_path, '', $this->original_path_with_args);
    }

    function getCurrentUrlConverted()
    {
        return 'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    function getCurrentUrlWithArgsConverted()
    {
        return 'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'];
    }

    function getCurrentHostConverted()
    {
        return 'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'];
    }

    function getCurrentPath()
    {
        return $this->original_path;
    }

    function getCurrentPathWithArgs()
    {
        return $this->original_path_with_args;
    }

    function getCurrentArgs()
    {
        return $this->original_args;
    }

    function getCurrentUrl()
    {
        return $this->original_url;
    }

    function getCurrentUrlWithArgs()
    {
        return $this->original_url_with_args;
    }

    function getCurrentHost()
    {
        return $this->original_host;
    }

    function getContentType()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            return $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            return $_SERVER['HTTP_ACCEPT'];
        }
        return null;
    }

    function contentTypeIsInappropriate()
    {
        $type = $this->getContentType();
        if ($type == '') {
            return false;
        }
        if (strpos($type, 'html') !== false) {
            return false;
        }
        if (strpos($type, 'php') !== false) {
            return false;
        }
        if (strpos($type, 'php') !== false) {
            return false;
        }
        if (strpos($type, 'plain') !== false) {
            return false;
        }
        if (strpos($type, 'xml') !== false) {
            return false;
        }
        if (strpos($type, 'json') !== false) {
            return false;
        }
        if (strpos($type, '*/*') !== false) {
            return false;
        }
        if (strpos($type, 'form') !== false) {
            return false;
        }
        return true;
    }

    function contentTranslationIsDisabledForCurrentUrl()
    {
        return $this->contentTranslationIsDisabledForUrl($this->getCurrentPath());
    }

    function contentTranslationIsDisabledForUrl($url)
    {
        if (
            $this->settings->get('exclude_urls_content') !== null &&
            is_array($this->settings->get('exclude_urls_content'))
        ) {
            foreach ($this->settings->get('exclude_urls_content') as $exclude__value) {
                $regex = '/^(.+\/)?' . preg_quote(trim($exclude__value, '/'), '/') . '((\/|\?).+)?$/';
                if (preg_match($regex, trim($url, '/'))) {
                    return true;
                }
            }
        }
        return false;
    }

    function slugTranslationIsDisabledForCurrentUrl()
    {
        return $this->slugTranslationIsDisabledForUrl($this->getCurrentPath());
    }

    function slugTranslationIsDisabledForUrl($url)
    {
        if (
            $this->settings->get('exclude_urls_slugs') !== null &&
            is_array($this->settings->get('exclude_urls_slugs'))
        ) {
            foreach ($this->settings->get('exclude_urls_slugs') as $exclude__value) {
                $regex = '/^(.+\/)?' . preg_quote(trim($exclude__value, '/'), '/') . '(\/.+)?$/';
                if (preg_match($regex, trim($url, '/'))) {
                    return true;
                }
            }
        }
        return false;
    }

    function isAjaxRequest()
    {
        if (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
            (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        ) {
            return true;
        }
        // we surely cannot detect ajax requests; so use common patterns
        if (strpos($this->getCurrentUrl(), 'wp-json/') !== false) {
            return true;
        }
        return false;
    }

    function currentUrlIsStaticFile()
    {
        return $this->urlIsStaticFile($this->getCurrentPath());
    }

    function urlIsStaticFile($url)
    {
        return preg_match('/\.(php|html)$/', rtrim($url, '/'));
    }

    function responseCodeIsSuccessful()
    {
        return in_array(http_response_code(), [200, 304]);
    }

    function getReferer()
    {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return null;
        }
        return $_SERVER['HTTP_REFERER'];
    }

    function getArgsFromUrl($url)
    {
        $url_parts = parse_url($url);
        if (!isset($url_parts['query']) || empty($url_parts['query'])) {
            return [];
        }
        parse_str(html_entity_decode($url_parts['query']), $url_args);
        if (empty($url_args)) {
            return [];
        }
        return $url_args;
    }

    function stripArgsFromUrl($url)
    {
        $pos = mb_strrpos('?', $url);
        if ($pos !== false) {
            return mb_substr($url, 0, $pos);
        }
        return $url;
    }

    function appendArgToUrl($url, $key, $value)
    {
        $append = $key . '=' . urlencode($value);
        if (mb_strpos($url, $append) !== false) {
            return $url;
        }
        $hash = '';
        $hash_pos = mb_strrpos($url, '#');
        if ($hash_pos !== false) {
            $hash = mb_substr($url, $hash_pos);
            $url = str_replace($hash, '', $url);
        }
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= $append . $hash;
        return $url;
    }

    function getLanguageCodeFromUrl($url)
    {
        // if query parameter is provided
        $url_args = $this->getArgsFromUrl($url);
        if (!empty($url_args)) {
            if (array_key_exists('lang', $url_args)) {
                $suggested_lng = $url_args['lang'];
                if (in_array($suggested_lng, $this->settings->getSelectedLanguageCodes())) {
                    return $suggested_lng;
                }
            }
        }
        // strip args
        $url = $this->stripArgsFromUrl($url);
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            $base_urls[$languages__value] = $this->getBaseUrlWithPrefixForLanguageCode($languages__value);
        }
        uasort($base_urls, function ($a, $b) {
            return strlen($b) - strlen($a) <=> 0;
        });
        foreach ($base_urls as $base_urls__key => $base_urls__value) {
            if (trim($url, '/') === trim($base_urls__value, '/')) {
                return $base_urls__key;
            }

            if (strpos(trim($url, '/') . '/', rtrim($base_urls__value, '/') . '/') === 0) {
                return $base_urls__key;
            }
        }
        return $this->settings->getSourceLanguageCode();
    }

    function getBaseUrlWithPrefixForLanguageCode($lng)
    {
        return rtrim(
            $this->getBaseUrlForLanguageCode($lng) . '/' . ($this->getPrefixForLanguageCode($lng) ?? '') . '/',
            '/'
        );
    }

    function getBaseUrlForSourceLanguage()
    {
        return $this->getBaseUrlForLanguageCode($this->settings->getSourceLanguageCode());
    }

    function getBaseUrlForLanguageCode($lng)
    {
        $url_base = '';
        $data = $this->settings->getLanguageDataForCode($lng);
        if ($data !== null && array_key_exists('url_base', $data)) {
            $url_base = $data['url_base'];
        }
        if ($url_base == '') {
            return $this->getCurrentHost();
        }
        return $url_base;
    }

    function getPrefixForLanguageCode($lng)
    {
        $data = $this->settings->getLanguageDataForCode($lng);
        if ($data !== null && !array_key_exists('url_prefix', $data)) {
            return $lng;
        }
        return $data['url_prefix'];
    }

    function shouldUseLangQueryArg()
    {
        $count = 0;
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if ($this->getPrefixForLanguageCode($languages__value) == '') {
                $count++;
            }
            if ($count >= 2) {
                return true;
            }
        }
        return false;
    }

    function getPathWithPrefixFromUrl($url)
    {
        $lng = $this->getLanguageCodeFromUrl($url);
        $base_url = $this->getBaseUrlForLanguageCode($lng);
        if (mb_strpos($url, $base_url) === 0) {
            $url = str_replace($base_url, '', $url);
        }
        $url = ltrim($url, '/');
        return $url;
    }

    function getPathWithoutPrefixFromUrl($url)
    {
        $lng = $this->getLanguageCodeFromUrl($url);
        $strip = [];
        $strip[] = $this->getBaseUrlWithPrefixForLanguageCode($lng);
        $strip[] = $this->getBaseUrlForLanguageCode($lng);
        foreach ($strip as $strip__value) {
            if (strpos($url, $strip__value) === 0) {
                $url = str_replace($strip__value, '', $url);
            }
        }
        if ($this->getPrefixForLanguageCode($lng) != '') {
            $strip = [];
            $strip[] = '/' . $this->getPrefixForLanguageCode($lng);
            $strip[] = $this->getPrefixForLanguageCode($lng);
            foreach ($strip as $strip__value) {
                if ($url === $strip__value || strpos($url, $strip__value . '/') === 0) {
                    $url = str_replace($strip__value, '', $url);
                }
            }
        }
        return $url;
    }

    function getRefererLanguageCode()
    {
        $referer = @$_SERVER['HTTP_REFERER'];
        if ($referer == '') {
            return $this->settings->getSourceLanguageCode();
        }
        return $this->getLanguageCodeFromUrl($referer);
    }

    function getBrowserLanguageCode()
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

    function getCurrentPrefix()
    {
        return $this->getPrefixFromUrl($this->getCurrentUrl());
    }

    function getPrefixFromUrl($url)
    {
        $path = $this->getPathWithPrefixFromUrl($url);
        foreach ($this->settings->getSelectedLanguageCodes() as $languages__value) {
            if ($path === $languages__value || mb_strpos($path, $languages__value . '/') === 0) {
                return $languages__value;
            }
        }
        return '';
    }
}
