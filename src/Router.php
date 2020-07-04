<?php
namespace vielhuber\gtbabel;

class Router
{
    public $data;
    public $host;
    public $settings;
    public $publish;

    function __construct(Data $data = null, Host $host = null, Settings $settings = null, Publish $publish = null)
    {
        $this->data = $data ?: new Data();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->publish = $publish ?: new Publish();
    }

    function redirectPrefixedSourceLng()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        if (!$this->data->sourceLngIsCurrentLng()) {
            return;
        }
        if (
            $this->settings->get('prefix_lng_source') === false &&
            $this->data->getCurrentPrefix() !== $this->settings->getSourceLanguageCode()
        ) {
            return;
        }
        if ($this->settings->get('prefix_lng_source') === true && $this->data->getCurrentPrefix() !== null) {
            return;
        }
        if ($this->settings->get('prefix_lng_source') === false) {
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
                $url .= $this->data->getLngFromUrl($_SERVER['HTTP_REFERER']);
            } else {
                if ($this->settings->get('redirect_root_domain') === 'browser') {
                    $url .= $this->data->getBrowserLng();
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
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        $args = $this->host->getCurrentArgs();
        if ($args != '') {
            return;
        }
        $url = $this->host->getCurrentUrl();
        if (mb_strrpos($url, '/') === mb_strlen($url) - 1) {
            return;
        }
        // also exclude pseudo filenames like automatically generated urls like /sitemap.xml
        $path_last_part = $this->host->getCurrentPath();
        $path_last_part = explode('/', $path_last_part);
        $path_last_part = $path_last_part[count($path_last_part) - 1];
        if (mb_strpos($path_last_part, '.') !== false) {
            return;
        }
        $url = $url . '/';
        header('Location: ' . $url, true, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 301); // 307 forces the browser to repost to the new url
        die();
    }

    function initMagicRouter()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        if ($this->data->sourceLngIsCurrentLng()) {
            if ($this->settings->get('prefix_lng_source') === false) {
                return;
            }
            if (
                $this->host->getCurrentPathWithArgs() === $this->settings->getSourceLanguageCode() ||
                mb_strpos(
                    $this->host->getCurrentPathWithArgs(),
                    '/' . $this->settings->getSourceLanguageCode() . '/'
                ) === 0
            ) {
                $path = mb_substr(
                    $this->host->getCurrentPathWithArgs(),
                    mb_strlen('/' . $this->settings->getSourceLanguageCode())
                );
            }
        } else {
            $path = $this->data->getPathTranslationInLanguage(
                $this->data->getCurrentLanguageCode(),
                $this->settings->getSourceLanguageCode(),
                true
            );
            $path = trim($path, '/');
            $path = '/' . $path;
        }
        $_SERVER['REQUEST_URI'] = $path;
    }

    function redirectUnpublished()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        if ($this->data->sourceLngIsCurrentLng()) {
            return;
        }
        $url = $this->host->getCurrentUrl();
        $source_url = $this->data->getUrlTranslationInLanguage(
            $this->data->getCurrentLanguageCode(),
            $this->settings->getSourceLanguageCode(),
            $url
        );
        if (
            !$this->publish->isActive() ||
            !$this->publish->isPrevented($source_url, $this->data->getCurrentLanguageCode())
        ) {
            return;
        }
        header('Location: ' . $source_url, true, 302);
        die();
    }
}
