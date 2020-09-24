<?php
namespace vielhuber\gtbabel;

class Gtbabel
{
    public $settings;
    public $utils;
    public $tags;
    public $host;
    public $publish;
    public $altlng;
    public $log;
    public $data;
    public $dom;
    public $router;
    public $gettext;

    public $started;

    function __construct(
        Settings $settings = null,
        Utils $utils = null,
        Log $log = null,
        Tags $tags = null,
        Host $host = null,
        Publish $publish = null,
        Data $data = null,
        Altlng $altlng = null,
        Dom $dom = null,
        Router $router = null,
        Gettext $gettext = null
    ) {
        $this->settings = $settings ?: new Settings();
        $this->utils = $utils ?: new Utils();
        $this->log = $log ?: new Log($this->utils, $this->settings);
        $this->tags = $tags ?: new Tags($this->utils);
        $this->host = $host ?: new Host($this->settings, $this->log);
        $this->publish = $publish ?: new Publish($this->settings, $this->host, $this->log);
        $this->data =
            $data ?: new Data($this->utils, $this->host, $this->settings, $this->tags, $this->log, $this->publish);
        $this->altlng = $altlng ?: new Altlng($this->settings, $this->host);
        $this->dom =
            $dom ?:
            new Dom($this->utils, $this->data, $this->host, $this->settings, $this->tags, $this->log, $this->altlng);
        $this->router = $router ?: new Router($this->data, $this->host, $this->settings, $this->publish);
        $this->gettext = $gettext ?: new Gettext($this->data, $this->settings);
    }

    function start($args = [], $detectDomChanges = true)
    {
        if ($detectDomChanges === true) {
            $this->detectDomChanges($args);
        }
        $this->started = true;
        $this->settings->setup($args);
        $this->host->setup();
        $this->log->setup();
        $this->data->initDatabase();
        $this->data->preloadDataInCache();
        if ($this->host->contentTranslationIsDisabledForCurrentUrl()) {
            return;
        }
        $this->router->redirectPrefixedUrls();
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
        $this->started = false;
        if ($this->host->contentTranslationIsDisabledForCurrentUrl()) {
            return;
        }
        $content = ob_get_contents();
        $content = $this->dom->modifyContent($content);
        ob_end_clean();
        echo $content;
        $this->data->saveCacheToDatabase();
        $this->router->resetMagicRouter();
    }

    function reset()
    {
        $this->data->resetTranslations();
        $this->data->statsReset();
        $this->log->generalLogReset();
    }

    function translate($html, $args = [])
    {
        ob_start();
        $this->start($args, false);
        echo $html;
        $this->stop();
        $trans = ob_get_contents();
        ob_end_clean();
        return $trans;
    }

    function detectDomChanges($args)
    {
        if (isset($_GET['gtbabel_translate_part']) && $_GET['gtbabel_translate_part'] == '1' && isset($_POST['html'])) {
            $input = $_POST['html'];
            $input = stripslashes($input);
            $output = $this->translate($input, $args);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => ['input' => $input, 'output' => $output]
            ]);
            die();
        }
    }

    function tokenize($content, $args = [])
    {
        // set fixed source and target (important: they just need to be different)
        $args['lng_source'] = 'de';
        $args['lng_target'] = 'en';
        $args['localize_js'] = false;
        $args['discovery_log'] = true;
        $this->settings->setup($args);
        $this->host->setup();
        $this->log->setup();
        $this->data->initDatabase();
        $this->data->preloadDataInCache();
        $time = $this->utils->getCurrentTime();
        $content = $this->dom->modifyContent($content);
        $this->data->saveCacheToDatabase();
        $data = $this->data->discoveryLogGetAfter($time, null, true);
        $this->reset();
        return $data;
    }
}
