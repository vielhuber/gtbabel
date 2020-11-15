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
        Grabber $grabber = null,
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
        $this->grabber = $grabber ?: new Grabber($this->settings, $this->utils, $this->log, $this->data);
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
        $this->detectDomChangesBackend();
        $this->frontendEditorBackend();
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

    function grab($main_url, $chunk = 0, $dry_run = false, $sitemap_cache = [])
    {
        if ($this->configured === false) {
            $this->config();
        }

        $languages = $this->settings->getSelectedLanguageCodesWithoutSource();

        $lng = $languages[$chunk % count($languages)];

        $chunk_page = floor($chunk / count($languages));

        $return = ['replacements' => [], 'sitemap' => [], 'count' => 0, 'url' => null, 'foreign_url' => null];

        [$return['url'], $return['sitemap']] = $this->grabber->parseSitemap($main_url, $chunk_page, $sitemap_cache);

        $return['count'] = count($return['sitemap']) * count($languages);

        if ($return['url'] === null) {
            return $return;
        }

        $html = [];
        $tokens = [];

        $html_source = __curl($return['url'])->result;
        $lng_source = $this->grabber->getLngFromHtml($html_source);
        $html[$lng_source] = $html_source;

        $return['foreign_url'] = $this->grabber->getForeignUrlFromHrefLang($html_source, $lng);

        if ($return['foreign_url'] === null) {
            return $this->grab($main_url, $chunk + 1, $dry_run, $sitemap_cache);
        }

        $html[$lng] = __curl($return['foreign_url'])->result;

        $existing = $this->data->getGroupedTranslationsFromDatabase()['data'];
        $existing_settings = $this->settings->getSettings();

        $tmp_folder = $this->utils->getDocRoot() . '/gtbabel-migrate';
        $settings = $existing_settings;
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
        $settings['auto_translation'] = false;

        $settings['lng_source'] = $lng_source;
        $settings['lng_target'] = $lng;
        $this->config($settings);
        $time = $this->utils->getCurrentTime();
        $this->domfactory->modifyContentFactory($html[$lng_source], 'tokenize');
        $this->data->saveCacheToDatabase();
        $tokens['trans'][$lng] = $this->data->discoveryLogGetAfter($time, null, false);

        $settings['lng_source'] = $lng;
        $settings['lng_target'] = $lng_source; // doesn't matter (must be different)
        $this->config($settings);
        $time = $this->utils->getCurrentTime();
        $this->domfactory->modifyContentFactory($html[$lng], 'tokenize');
        $this->data->saveCacheToDatabase();
        $tokens['live'][$lng] = $this->data->discoveryLogGetAfter($time, null, false);

        $compare = $this->grabber->buildCompareData($tokens, $existing, $lng_source);

        $this->reset();
        $this->utils->rrmdir($tmp_folder);

        // reset to original instance
        $this->config($existing_settings);

        $return['replacements'] = $this->grabber->modifyAppropriateTranslations(
            $compare,
            $languages,
            $lng_source,
            $dry_run
        );

        return $return;
    }

    function detectDomChangesBackend()
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

    function frontendEditorBackend()
    {
        if (
            $this->settings->get('frontend_editor') === true &&
            isset($_GET['gtbabel_frontend_editor_save']) &&
            $_GET['gtbabel_frontend_editor_save'] == '1'
        ) {
            $str = trim(strip_tags(stripslashes(@$_POST['str'])));
            $context = trim(strip_tags(stripslashes(@$_POST['context'])));
            $lng_source = trim(strip_tags(stripslashes(@$_POST['lng_source'])));
            $lng_target = trim(strip_tags(stripslashes(@$_POST['lng_target'])));
            $trans = trim(strip_tags(stripslashes(@$_POST['trans'])));
            $this->data->editTranslation($str, $context, $lng_source, $lng_target, $trans);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true
            ]);
            die();
        }
    }
}
