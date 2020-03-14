<?php

namespace ScopedGtbabel\vielhuber\gtbabel;

class Gtbabel
{
    public $settings;
    public $utils;
    public $tags;
    public $host;
    public $publish;
    public $log;
    public $dom;
    public $router;
    public $gettext;
    public $started;
    function __construct(\ScopedGtbabel\vielhuber\gtbabel\Settings $settings = null, \ScopedGtbabel\vielhuber\gtbabel\Utils $utils = null, \ScopedGtbabel\vielhuber\gtbabel\Tags $tags = null, \ScopedGtbabel\vielhuber\gtbabel\Host $host = null, \ScopedGtbabel\vielhuber\gtbabel\Publish $publish = null, \ScopedGtbabel\vielhuber\gtbabel\Log $log = null, \ScopedGtbabel\vielhuber\gtbabel\Dom $dom = null, \ScopedGtbabel\vielhuber\gtbabel\Router $router = null, \ScopedGtbabel\vielhuber\gtbabel\Gettext $gettext = null)
    {
        $this->settings = $settings ?: new \ScopedGtbabel\vielhuber\gtbabel\Settings();
        $this->utils = $utils ?: new \ScopedGtbabel\vielhuber\gtbabel\Utils();
        $this->tags = $tags ?: new \ScopedGtbabel\vielhuber\gtbabel\Tags($this->utils);
        $this->host = $host ?: new \ScopedGtbabel\vielhuber\gtbabel\Host($this->settings);
        $this->publish = $publish ?: new \ScopedGtbabel\vielhuber\gtbabel\Publish($this->settings, $this->host);
        $this->log = $log ?: new \ScopedGtbabel\vielhuber\gtbabel\Log($this->utils, $this->settings, $this->host);
        $this->gettext = $gettext ?: new \ScopedGtbabel\vielhuber\gtbabel\Gettext($this->utils, $this->host, $this->settings, $this->tags, $this->log, $this->publish);
        $this->dom = $dom ?: new \ScopedGtbabel\vielhuber\gtbabel\Dom($this->utils, $this->gettext, $this->host, $this->settings);
        $this->router = $router ?: new \ScopedGtbabel\vielhuber\gtbabel\Router($this->gettext, $this->host, $this->settings, $this->publish);
    }
    function start($args = [])
    {
        $this->started = \true;
        $this->settings->setup($args);
        $this->host->setup();
        $this->gettext->createLngFolderIfNotExists();
        $this->gettext->preloadGettextInCache();
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $this->router->redirectPrefixedSourceLng();
        $this->gettext->addCurrentUrlToTranslations();
        $this->router->addTrailingSlash();
        $this->router->redirectUnpublished();
        $this->router->initMagicRouter();
        \ob_start();
    }
    function stop()
    {
        if ($this->started !== \true) {
            return;
        }
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $content = \ob_get_contents();
        $content = $this->dom->modifyContent($content);
        \ob_end_clean();
        echo $content;
        $this->gettext->generateGettextFiles();
    }
    function reset()
    {
        $this->gettext->resetTranslations();
        $this->log->apiStatsReset();
        $this->log->discoveryLogReset();
    }
    function translate($content, $args = [])
    {
        $this->settings->setup($args);
        $this->host->setup();
        $this->gettext->preloadGettextInCache();
        $content = $this->dom->modifyContent($content);
        return $content;
    }
    function tokenize($content, $args = [])
    {
        // set fixed source and target (important: they just need to be different)
        $args['lng_source'] = 'de';
        $args['lng_target'] = 'en';
        $args['discovery_log'] = \true;
        $this->settings->setup($args);
        $this->host->setup();
        $this->gettext->preloadGettextInCache();
        $since_time = \microtime(\true);
        $content = $this->dom->modifyContent($content);
        $data = $this->log->discoveryLogGet($since_time);
        return $data;
    }
}
