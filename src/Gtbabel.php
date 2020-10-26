<?php
namespace vielhuber\gtbabel;

class Gtbabel
{
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
        DomFactory $domfactory = null,
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
        $this->domfactory =
            $domfactory ?:
            new DomFactory(
                $this->utils,
                $this->data,
                $this->host,
                $this->settings,
                $this->tags,
                $this->log,
                $this->altlng
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
        $content = $this->domfactory->modifyContentFactory(ob_get_contents(), 'buffer');
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
        if ($this->configured === false) {
            $this->config();
        }

        $lng_source_prev = false;
        if ($lng_source !== null) {
            $lng_source_prev = $this->settings->get('lng_source');
            if ($lng_source != $lng_source_prev) {
                $this->settings->set('lng_source', $lng_source);
            }
        }
        $lng_target_prev = false;
        if ($lng_target !== null) {
            $lng_target_prev = $this->settings->get('lng_target');
            if ($lng_target != $lng_target_prev) {
                $this->settings->set('lng_target', $lng_target);
            }
        }

        $trans = $this->domfactory->modifyContentFactory($html, 'translate');

        if ($lng_source_prev !== false) {
            $this->settings->set('lng_source', $lng_source_prev);
        }
        if ($lng_target_prev !== false) {
            $this->settings->set('lng_target', $lng_target_prev);
        }

        $this->data->saveCacheToDatabase();

        return $trans;
    }

    function tokenize($content)
    {
        if ($this->configured === false) {
            $this->config();
        }
        $tmp_folder = $this->utils->getDocRoot() . '/gtbabel-tokenize';
        $settings = $this->settings->getSettings();
        // set fixed source and target (important: they just need to be different)
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $settings['exclude_urls_content'] = null;
        $settings['localize_js'] = false;
        $settings['discovery_log'] = true;
        $settings['auto_add_translations'] = true;
        $settings['log_folder'] = $tmp_folder;
        $settings['database'] = [
            'type' => 'sqlite',
            'filename' => $tmp_folder . '/tmp.db',
            'table' => 'translations'
        ];
        $this->config($settings);
        $time = $this->utils->getCurrentTime();
        $content = $this->domfactory->modifyContentFactory($content, 'tokenize');
        $this->data->saveCacheToDatabase();
        $data = $this->data->discoveryLogGetAfter($time, null, true);
        $this->reset();
        $this->utils->rrmdir($tmp_folder);
        return $data;
    }

    function migrate($url)
    {
        $tmp_folder = $this->utils->getDocRoot() . '/gtbabel-migrate';
        if (1 == 0) {
            $de = __curl('https://www.tld.com/de/')->result;
            $en = __curl('https://www.tld.com/en/')->result;
        } else {
            $de = '<p>Dies ist ein Test</p>';
            $en = '<p>This is a test!</p>';
        }

        if ($this->configured === false) {
            $this->config();
        }
        $settings = $this->settings->getSettings();
        $settings['exclude_urls_content'] = null;
        $settings['localize_js'] = false;
        $settings['discovery_log'] = true;
        $settings['auto_add_translations'] = true;
        $settings['log_folder'] = $tmp_folder;
        $settings['database'] = [
            'type' => 'sqlite',
            'filename' => $tmp_folder . '/tmp.db',
            'table' => 'translations'
        ];

        $settings['auto_translation'] = true;
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $this->config($settings);
        $time = $this->utils->getCurrentTime();
        $this->domfactory->modifyContentFactory($de, 'tokenize');
        $this->data->saveCacheToDatabase();
        $de_tokens = $this->data->discoveryLogGetAfter($time, null, false);

        $settings['auto_translation'] = false;
        $settings['lng_source'] = 'en';
        $settings['lng_target'] = 'de'; // doesn't matter
        $this->config($settings);
        $time = $this->utils->getCurrentTime();
        $this->domfactory->modifyContentFactory($en, 'tokenize');
        $this->data->saveCacheToDatabase();
        $en_tokens = $this->data->discoveryLogGetAfter($time, null, false);

        $data = [];

        foreach ($de_tokens as $de_tokens__value) {
            if ($de_tokens__value['lng_source'] !== 'de') {
                continue;
            }
            if ($de_tokens__value['lng_target'] !== 'en') {
                continue;
            }
            $data[] = [
                'de' => $de_tokens__value['str'],
                'en_translated' => $de_tokens__value['trans'],
                'en_real' => null,
                'similarity' => 0
            ];
        }
        foreach ($en_tokens as $en_tokens__value) {
            if ($en_tokens__value['lng_source'] !== 'en') {
                continue;
            }
            if ($en_tokens__value['lng_target'] !== 'de') {
                continue;
            }
            foreach ($data as $data__key => $data__value) {
                similar_text($en_tokens__value['str'], $data__value['en_translated'], $similarity);
                $similarity = round($similarity);
                if ($similarity > $data__value['similarity']) {
                    $data[$data__key]['similarity'] = $similarity;
                    $data[$data__key]['en_real'] = $en_tokens__value['str'];
                }
            }
        }
        $this->log->generalLog($data);
        $this->reset();
        $this->utils->rrmdir($tmp_folder);
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
