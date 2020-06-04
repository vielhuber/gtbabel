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

    function __construct(Settings $settings = null)
    {
        $this->settings = $settings ?: new Settings();
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
                if (mb_strpos(trim($url, '/'), trim($exclude__value, '/')) !== false) {
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

    function responseCodeIsSuccessful()
    {
        return in_array(http_response_code(), [200, 304]);
    }
}
