<?php
namespace vielhuber\gtbabel;

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

    function __construct(
        Settings $settings = null,
        Utils $utils = null,
        Tags $tags = null,
        Host $host = null,
        Publish $publish = null,
        Log $log = null,
        Dom $dom = null,
        Router $router = null,
        Gettext $gettext = null
    ) {
        $this->settings = $settings ?: new Settings();
        $this->utils = $utils ?: new Utils();
        $this->tags = $tags ?: new Tags($this->utils);
        $this->host = $host ?: new Host($this->settings);
        $this->publish = $publish ?: new Publish($this->settings, $this->host);
        $this->log = $log ?: new Log($this->utils, $this->settings, $this->host);
        $this->gettext =
            $gettext ?:
            new Gettext($this->utils, $this->host, $this->settings, $this->tags, $this->log, $this->publish);
        $this->dom = $dom ?: new Dom($this->utils, $this->gettext, $this->host, $this->settings);
        $this->router = $router ?: new Router($this->gettext, $this->host, $this->settings, $this->publish);
    }

    function start($args = [])
    {
        $this->started = true;
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
        $this->log->apiStatsReset();
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
        $args['discovery_log'] = true;
        $this->settings->setup($args);
        $this->host->setup();
        $this->gettext->preloadGettextInCache();
        $this->log->discoveryLogReset();
        $content = $this->dom->modifyContent($content);
        $data = $this->log->discoveryLogGet();
        $this->log->discoveryLogReset();
        return $data;
    }
}
