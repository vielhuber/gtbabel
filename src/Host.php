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

    public $settings;
    public $log;

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

    function currentUrlIsExcluded()
    {
        return $this->urlIsExcluded($this->getCurrentPath());
    }

    function urlIsExcluded($url)
    {
        if ($this->settings->get('exclude_urls') !== null && is_array($this->settings->get('exclude_urls'))) {
            foreach ($this->settings->get('exclude_urls') as $exclude__value) {
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

    function getLngFromUrl($url)
    {
        $base_urls = [];
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
        $url_base = @$this->settings->getLanguageDataForCode($lng)['url_base'];
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

    function getPathWithPrefixFromUrl($url)
    {
        $lng = $this->getLngFromUrl($url);
        $base_url = $this->getBaseUrlForLanguageCode($lng);
        if (mb_strpos($url, $base_url) === 0) {
            $url = str_replace($base_url, '', $url);
        }
        return $url;
    }

    function getPathWithoutPrefixFromUrl($url)
    {
        $lng = $this->getLngFromUrl($url);
        $strip = [];
        $strip[] = $this->getBaseUrlWithPrefixForLanguageCode($lng);
        $strip[] = $this->getBaseUrlForLanguageCode($lng);
        if ($this->getPrefixForLanguageCode($lng) != '') {
            $strip[] = '/' . $this->getPrefixForLanguageCode($lng);
            $strip[] = $this->getPrefixForLanguageCode($lng);
        }
        foreach ($strip as $strip__value) {
            if (strpos($url, $strip__value) === 0) {
                $url = str_replace($strip__value, '', $url);
            }
        }
        return $url;
    }

    function getRefererLng()
    {
        $referer = @$_SERVER['HTTP_REFERER'];
        if ($referer == '') {
            return $this->settings->getSourceLanguageCode();
        }
        return $this->getLngFromUrl($referer);
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
