<?php
namespace vielhuber\gtbabel;

class Router
{
    function __construct(
        Data $data = null,
        Host $host = null,
        Settings $settings = null,
        Publish $publish = null,
        Log $log = null
    ) {
        $this->data = $data ?: new Data();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->publish = $publish ?: new Publish();
        $this->log = $log ?: new Log();
    }

    function redirectPrefixedUrls()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }

        if (
            $this->host->getCurrentPrefix() ==
            $this->host->getPrefixForLanguageCode($this->data->getCurrentLanguageCode())
        ) {
            return;
        }

        $lng = $this->data->getCurrentLanguageCode();
        if ($this->data->sourceLngIsCurrentLng()) {
            if ($this->host->isAjaxRequest() && $this->host->getReferer() !== null) {
                $lng = $this->host->getLanguageCodeFromUrl($this->host->getReferer());
            } elseif ($this->settings->get('redirect_root_domain') === 'browser') {
                $lng = $this->host->getBrowserLanguageCode();
            }
        }

        $url =
            rtrim($this->host->getBaseUrlWithPrefixForLanguageCode($lng), '/') .
            '/' .
            ltrim($this->host->getPathWithoutPrefixFromUrl($this->host->getCurrentUrlWithArgs()), '/');

        if ($this->host->getCurrentUrlWithArgs() === $url) {
            return;
        }

        header('Location: ' . $url, true, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 302); // 307 forces the browser to repost to the new url
        die();
    }

    function addTrailingSlash()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        $url = $this->host->getCurrentUrl();
        $args = $this->host->getCurrentArgs();
        if ($args != '') {
            $url = str_replace($args, '', $url);
        }
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
        if ($args != '') {
            $url .= $args;
        }
        header('Location: ' . $url, true, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 301); // 307 forces the browser to repost to the new url
        die();
    }

    function initMagicRouter()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }
        $path = $this->host->getPathWithoutPrefixFromUrl($this->host->getCurrentUrlWithArgs());
        if (!$this->data->sourceLngIsCurrentLng()) {
            $path = $this->data->getPathTranslationInLanguage(
                $this->data->getCurrentLanguageCode(),
                $this->settings->getSourceLanguageCode(),
                $path
            );
        }
        $path = trim($path, '/');
        $path = '/' . $path;
        $_SERVER['REQUEST_URI'] = $path;
    }

    function resetMagicRouter()
    {
        $_SERVER['REQUEST_URI'] = $this->host->getCurrentPathWithArgs();
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
