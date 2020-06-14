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
    public $data;

    public $started;

    function __construct(
        Settings $settings = null,
        Utils $utils = null,
        Tags $tags = null,
        Host $host = null,
        Publish $publish = null,
        Log $log = null,
        Data $data = null,
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
        $this->data =
            $data ?: new Data($this->utils, $this->host, $this->settings, $this->tags, $this->log, $this->publish);
        $this->dom = $dom ?: new Dom($this->utils, $this->data, $this->host, $this->settings, $this->log);
        $this->router = $router ?: new Router($this->data, $this->host, $this->settings, $this->publish);
        $this->gettext = $gettext ?: new Gettext($this->data, $this->settings);
    }

    function start($args = [])
    {
        $this->started = true;
        $this->settings->setup($args);
        $this->host->setup();
        $this->log->setup();
        $this->data->initDatabase();
        $this->data->preloadDataInCache();
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $this->router->redirectPrefixedSourceLng();
        $this->router->addTrailingSlash();
        $this->router->redirectUnpublished();
        $this->router->initMagicRouter();
        $this->data->addCurrentUrlToTranslations();
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
        if ($content == '') {
            ob_end_clean();
            return;
        }
        $content = $this->dom->modifyContent($content);
        ob_end_clean();
        echo $content;
        $this->data->generateGettextFiles();
        $this->log->statsLogSave();
    }

    function reset()
    {
        $this->data->resetTranslations();
        $this->log->statsLogReset();
        $this->log->generalLogReset();
    }

    function translate($content, $args = [])
    {
        $args['only_show_checked_strings'] = 'false';
        $this->settings->setup($args);
        $this->host->setup();
        $this->log->setup();
        $this->data->preloadDataInCache();
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
        $this->log->setup();
        $this->data->initDatabase();
        $this->data->preloadDataInCache();
        $time = $this->utils->getCurrentTime();
        $content = $this->dom->modifyContent($content);
        $this->data->generateGettextFiles();
        $data = $this->data->discoveryLogGetAfter($time, null, true);
        $this->reset();
        return $data;
    }
}
