<?php
namespace vielhuber\gtbabel;

class Router
{
    public $gettext;
    public $host;
    public $settings;
    public $publish;

    function __construct(Gettext $gettext = null, Host $host = null, Settings $settings = null, Publish $publish = null)
    {
        $this->gettext = $gettext ?: new Gettext();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->publish = $publish ?: new Publish();
    }

    function redirectPrefixedSourceLng()
    {
        if (!$this->gettext->sourceLngIsCurrentLng()) {
            return;
        }
        if (
            $this->settings->get('prefix_source_lng') === false &&
            $this->gettext->getCurrentPrefix() !== $this->settings->getSourceLanguageCode()
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
                    str_replace(
                        $this->settings->getSourceLanguageCode() . '/',
                        '',
                        $this->host->getCurrentPathWithArgs()
                    ),
                    '/'
                );
        } else {
            $url = '';
            $url .= trim($this->host->getCurrentHost(), '/');
            $url .= '/';
            if ($this->host->isAjaxRequest() && @$_SERVER['HTTP_REFERER'] != '') {
                $url .= $this->gettext->getLngFromUrl($_SERVER['HTTP_REFERER']);
            } else {
                if ($this->settings->get('redirect_root_domain') === 'browser') {
                    $url .= $this->gettext->getBrowserLng();
                } else {
                    $url .= $this->settings->getSourceLanguageCode();
                }
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
        header('Location: ' . $url, true, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 302); // 307 forces the browser to repost to the new url
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
            if (
                mb_strpos($this->host->getCurrentPathWithArgs(), '/' . $this->settings->getSourceLanguageCode()) === 0
            ) {
                $path = mb_substr(
                    $this->host->getCurrentPathWithArgs(),
                    mb_strlen('/' . $this->settings->getSourceLanguageCode())
                );
            }
        } else {
            $path = $this->gettext->getPathTranslationInLanguage($this->settings->getSourceLanguageCode(), true);
            $path = trim($path, '/');
            $path = '/' . $path;
        }
        $_SERVER['REQUEST_URI'] = $path;
    }

    function redirectUnpublished()
    {
        if ($this->gettext->sourceLngIsCurrentLng()) {
            return;
        }
        $url = $this->host->getCurrentUrl();
        $source_url = $this->gettext->getUrlTranslationInLanguage($this->settings->getSourceLanguageCode(), $url);
        if (
            !$this->publish->isActive() ||
            !$this->publish->isPrevented($source_url, $this->gettext->getCurrentLanguageCode())
        ) {
            return;
        }
        header('Location: ' . $source_url, true, 302);
        die();
    }
}
