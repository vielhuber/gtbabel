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
    public $excel;

    public $configured = false;
    public $started = false;

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
        Gettext $gettext = null,
        Excel $excel = null
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
            new Dom(
                $this->utils,
                $this->data,
                $this->host,
                $this->settings,
                $this->tags,
                $this->log,
                $this->altlng,
                $this
            );
        $this->router = $router ?: new Router($this->data, $this->host, $this->settings, $this->publish, $this->log);
        $this->gettext = $gettext ?: new Gettext($this->data, $this->settings);
        $this->excel = $excel ?: new Excel($this->data, $this->settings);
    }

    function config($args = [])
    {
        $this->settings->setup($args);
        $this->host->setup();
        $this->log->setup();
        $this->data->initDatabase();
        $this->data->preloadDataInCache();
        $this->configured = true;
    }

    function start()
    {
        if ($this->configured === false) {
            $this->config();
        }
        if ($this->started === true) {
            return;
        }
        $this->detectDomChanges();
        if ($this->host->contentTranslationIsDisabledForCurrentUrl()) {
            return;
        }
        if ($this->host->contentTypeIsInappropriate()) {
            return;
        }
        $this->router->redirectPrefixedUrls();
        $this->router->addTrailingSlash();
        $this->router->redirectUnpublished();
        $this->router->initMagicRouter();
        $this->data->addCurrentUrlToTranslations();
        $this->started = true;
        ob_start();
    }

    function stop()
    {
        if ($this->started === false) {
            return;
        }
        if ($this->host->contentTranslationIsDisabledForCurrentUrl()) {
            return;
        }
        if ($this->host->contentTypeIsInappropriate()) {
            return;
        }
        $this->dom->localizeJsPrepare();
        $content = $this->dom->modifyContent(ob_get_contents(), 'buffer');
        ob_end_clean();
        echo $content;
        $this->data->saveCacheToDatabase();
        $this->router->resetMagicRouter();
        $this->started = false;
    }

    function reset()
    {
        if ($this->configured === false) {
            $this->config();
        }
        $this->data->resetTranslations();
        $this->data->statsReset();
        $this->log->generalLogReset();
    }

    function translate($html, $lng_target = null, $lng_source = null)
    {
        // this function has two "modes"
        // if you don't specify $lng_target and $lng_source, it uses the current $gtbabel instance
        // however, if you provide it, the current instance is untouched and a new one is temporarily created
        if ($lng_target === null && $lng_source === null) {
            if ($this->configured === false) {
                $this->config();
            }
            // what's important: this function is destroying the current domdocument
            // therefore it must be called never *inside* modifyContent
            $trans = $this->dom->modifyContent($html, 'translate');
            $this->data->saveCacheToDatabase();
        } else {
            // use settings of already started instance
            $settings = $this->settings->getSettings();
            if ($lng_target === null) {
                $lng_target = $this->data->getCurrentLanguageCode();
            }
            if ($lng_source === null) {
                $lng_source = $this->settings->getSourceLanguageCode();
            }
            $settings['lng_target'] = $lng_target;
            $settings['lng_source'] = $lng_source;
            // start a totally independent session
            $tmp = new \vielhuber\gtbabel\Gtbabel();
            $tmp->config($settings);
            $trans = $tmp->dom->modifyContent($html, 'translate');
            $tmp->data->saveCacheToDatabase();
        }
        return $trans;
    }

    function tokenize($content)
    {
        if ($this->configured === false) {
            $this->config();
        }
        // set fixed source and target (important: they just need to be different)
        $settings = $this->settings->getSettings();
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $settings['localize_js'] = false;
        $settings['discovery_log'] = true;
        $this->config($settings);

        $time = $this->utils->getCurrentTime();
        $content = $this->dom->modifyContent($content, 'tokenize');
        $this->data->saveCacheToDatabase();
        $data = $this->data->discoveryLogGetAfter($time, null, true);
        $this->reset();
        return $data;
    }

    function detectDomChanges()
    {
        if (isset($_GET['gtbabel_translate_part']) && $_GET['gtbabel_translate_part'] == '1' && isset($_POST['html'])) {
            $input = $_POST['html'];
            $input = stripslashes($input);
            $output = $this->translate($input);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => ['input' => $input, 'output' => $output]
            ]);
            die();
        }
    }
}
