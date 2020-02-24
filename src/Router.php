<?php
namespace vielhuber\gtbabel;

class Router
{
    public $utils;
    public $gettext;
    public $host;
    public $settings;

    function __construct(Utils $utils = null, Gettext $gettext = null, Host $host = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->gettext = $gettext ?: new Gettext();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
    }

    function redirectPrefixedSourceLng()
    {
        if (!$this->gettext->sourceLngIsCurrentLng()) {
            return;
        }
        if (
            $this->settings->get('prefix_source_lng') === false &&
            $this->gettext->getCurrentPrefix() !== $this->settings->getSourceLng()
        ) {
            return;
        }
        if ($this->settings->get('prefix_source_lng') === true && $this->gettext->getCurrentPrefix() !== null) {
            return;
        }
        if ($this->settings->get('prefix_source_lng') === false) {
            $url =
                trim($this->host->getCurrentHost(), '/') .
                '/' .
                trim(
                    str_replace($this->settings->getSourceLng() . '/', '', $this->host->getCurrentPathWithArgs()),
                    '/'
                );
        } else {
            $url = '';
            $url .= trim($this->host->getCurrentHost(), '/');
            $url .= '/';
            if ($this->host->isAjaxRequest() && @$_SERVER['HTTP_REFERER'] != '') {
                $url .= $this->gettext->getLngFromUrl($_SERVER['HTTP_REFERER']);
            } else {
                $url .= $this->gettext->getCurrentLng();
            }
            $url .= '/';
            if (trim($this->host->getCurrentPath(), '/') != '') {
                $url .= trim($this->host->getCurrentPath(), '/');
                $url .= '/';
            }
            if ($this->host->getCurrentArgs() != '') {
                $url .= $this->host->getCurrentArgs();
            }
        }
        header('Location: ' . $url, true, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 301); // 307 forces the browser to repost to the new url
        die();
    }

    function addTrailingSlash()
    {
        $args = $this->host->getCurrentArgs();
        if ($args != '') {
            return;
        }
        $url = $this->host->getCurrentUrl();
        if (mb_strrpos($url, '/') === mb_strlen($url) - 1) {
            return;
        }
        $url = $url . '/';
        header('Location: ' . $url, true, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 301);
        die();
    }

    function initMagicRouter()
    {
        if ($this->gettext->sourceLngIsCurrentLng()) {
            if ($this->settings->get('prefix_source_lng') === false) {
                return;
            }
            if (mb_strpos($this->host->getCurrentPathWithArgs(), '/' . $this->settings->getSourceLng()) === 0) {
                $path = mb_substr(
                    $this->host->getCurrentPathWithArgs(),
                    mb_strlen('/' . $this->settings->getSourceLng())
                );
            }
        } else {
            $path = $this->gettext->getPathTranslationInLanguage($this->settings->getSourceLng(), true);
            $path = trim($path, '/');
            $path = '/' . $path . ($path != '' ? '/' : '') . $this->host->getCurrentArgs();
        }
        $_SERVER['REQUEST_URI'] = $path;
    }
}
