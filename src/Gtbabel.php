<?php
namespace vielhuber\gtbabel;

class Gtbabel
{
    public $dom;
    public $utils;
    public $host;
    public $gettext;
    public $router;
    public $settings;
    public $tags;
    public $translation;

    public $started;

    function __construct(
        Dom $dom = null,
        Utils $utils = null,
        Host $host = null,
        Gettext $gettext = null,
        Router $router = null,
        Settings $settings = null,
        Tags $tags = null
    ) {
        $this->settings = $settings ?: new Settings();
        $this->utils = $utils ?: new Utils($this->settings);
        $this->tags = $tags ?: new Tags($this->utils, $this->settings);
        $this->host = $host ?: new Host($this->utils, $this->settings, $this->tags);
        $this->gettext = $gettext ?: new Gettext($this->utils, $this->host, $this->settings, $this->tags);
        $this->dom = $dom ?: new Dom($this->utils, $this->gettext, $this->host, $this->settings, $this->tags);
        $this->router = $router ?: new Router($this->utils, $this->gettext, $this->host, $this->settings, $this->tags);
    }

    function start($args = [])
    {
        $this->started = true;
        $this->settings->set($args);
        $this->host->setup();
        $this->gettext->createLngFolderIfNotExists();
        $this->gettext->preloadGettextInCache();
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $this->router->redirectPrefixedSourceLng();
        $this->gettext->addCurrentUrlToTranslations();
        if (!$this->host->currentUrlIsExcluded()) {
            $this->router->addTrailingSlash();
            $this->router->initMagicRouter();
        }
        ob_start();
    }

    function stop()
    {
        if ($this->started !== true) {
            return;
        }
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $content = ob_get_contents();
        $content = $this->dom->modifyContent($content);
        ob_end_clean();
        echo $content;
        $this->gettext->generateGettextFiles();
    }

    function reset()
    {
        $this->gettext->resetTranslations();
        $this->utils->apiStatsReset();
    }

    function translate($content, $args = [])
    {
        $this->settings->set($args);
        $this->host->setup();
        $this->gettext->preloadGettextInCache();
        $content = $this->dom->modifyContent($content);
        return $content;
    }

    function tokenize($content, $args = [])
    {
        // set fixed source and target (important: they just need to be different
        $args['lng_source'] = 'de';
        $args['lng_target'] = 'en';
        $this->settings->set($args);
        $this->host->setup();
        $this->gettext->preloadGettextInCache();
        $content = $this->dom->modifyContent($content);
        return $this->gettext->getDiscoveredStrings();
    }
}
