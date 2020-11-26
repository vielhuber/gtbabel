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

    function handleRedirects()
    {
        if ($this->host->currentUrlIsStaticFile()) {
            return;
        }

        $url = $this->host->getCurrentUrlWithArgs();
        $lng = $this->data->getCurrentLanguageCode();

        // this only works if path prefixes are provided in the domain settings
        // and a root domain is opened without a path
        // we don't want to use a cookie based approach to redirect domains
        // (on setups without prefixes) without prefixes to other domains for the first time
        if (
            ($this->host->shouldUseLangQueryArg() === true &&
                $this->host->getArgFromUrl($this->host->getCurrentUrlWithArgs(), 'lang') === null) ||
            ($this->host->shouldUseLangQueryArg() === false &&
                $this->host->getPrefixForLanguageCode($this->data->getCurrentLanguageCode()) != '' &&
                $this->host->getCurrentPrefix() == '')
        ) {
            if ($this->settings->get('redirect_root_domain') === 'browser') {
                $lng = $this->host->getBrowserLanguageCode();
            }
            if ($this->settings->get('redirect_root_domain') === 'source') {
                $lng = $this->settings->getSourceLanguageCode();
            }
            if ($this->settings->get('redirect_root_domain') === 'ip') {
                $lng = $this->host->getIpLanguageCode();
            }
            if (
                $this->data->sourceLngIsCurrentLng() &&
                $this->host->isAjaxRequest() &&
                $this->host->getReferer() !== null
            ) {
                $lng = $this->host->getLanguageCodeFromUrl($this->host->getReferer());
            }

            $url =
                rtrim($this->host->getBaseUrlWithPrefixForLanguageCode($lng), '/') .
                '/' .
                ltrim($this->host->getPathWithoutPrefixFromUrl($this->host->getCurrentUrlWithArgs()), '/');

            if ($this->host->shouldUseLangQueryArg()) {
                $url = $this->host->appendArgToUrl($url, 'lang', $lng);
            }
        }

        // redirect unpublished
        if ($this->publish->isActive()) {
            if ($this->settings->getSourceLanguageCode() !== $lng) {
                $source_url = $this->data->getUrlTranslationInLanguage(
                    $lng,
                    $this->settings->getSourceLanguageCode(),
                    $url
                );
            } else {
                $source_url = $url;
            }
            if ($this->publish->isPrevented($source_url, $lng)) {
                $target_url = $this->host->getBaseUrlWithPrefixForLanguageCode($lng);
                // if whole languages are disabled
                if ($this->publish->isPrevented($target_url, $lng)) {
                    $url = $this->host->getBaseUrlWithPrefixForLanguageCode($this->settings->getSourceLanguageCode());
                } else {
                    $url = $target_url;
                }
            }
        }

        // add trailing slash
        if (
            mb_strrpos($this->host->stripArgsFromUrl($url), '/') !==
            mb_strlen($this->host->stripArgsFromUrl($url)) - 1
        ) {
            // exclude pseudo filenames like automatically generated urls like /sitemap.xml
            $path_last_part = $this->host->stripArgsFromUrl($url);
            $path_last_part = explode('/', $path_last_part);
            $path_last_part = $path_last_part[count($path_last_part) - 1];
            if (mb_strpos($path_last_part, '.') === false) {
                $url = $this->host->stripArgsFromUrl($url) . '/' . $this->host->stripNonArgsFromUrl($url);
            }
        }

        if ($this->host->getCurrentUrlWithArgs() === $url) {
            return;
        }

        $this->redirect($url, @$_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 302); // 307 forces the browser to repost to the new url
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

    function redirect($url, $status_code)
    {
        header('Location: ' . $url, true, $status_code);
        die();
    }
}
