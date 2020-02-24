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

    public $utils;
    public $settings;

    function __construct(Utils $utils = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->settings = $settings ?: new Settings();
    }

    function setup()
    {
        $this->original_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // store without get parameters
        $this->original_path_with_args = $_SERVER['REQUEST_URI'];
        $this->original_args = str_replace($this->original_path, '', $this->original_path_with_args);
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

    function getCurrentArgs()
    {
        return $this->original_args;
    }

    function getCurrentHost()
    {
        return $this->original_host;
    }

    function getCurrentUrl()
    {
        return $this->original_url;
    }

    function getCurrentUrlWithArgs()
    {
        return $this->original_url_with_args;
    }

    function getCurrentPath()
    {
        return $this->original_path;
    }

    function getCurrentPathWithArgs()
    {
        return $this->original_path_with_args;
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
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            return true;
        }
        return false;
    }
}
