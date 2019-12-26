<?php
namespace vielhuber\gtbabel;

class Gtbabel
{
    public $html;

    public $dom;
    public $utils;
    public $host;
    public $gettext;
    public $router;
    public $settings;
    public $translation;

    function __construct(
        Dom $dom = null,
        Utils $utils = null,
        Host $host = null,
        Gettext $gettext = null,
        Router $router = null,
        Settings $settings = null
    ) {
        $this->settings = $settings ?: new Settings();
        $this->utils = $utils ?: new Utils();
        $this->host = $host ?: new Host($this->utils, $this->settings);
        $this->gettext = $gettext ?: new Gettext($this->utils, $this->host, $this->settings);
        $this->dom = $dom ?: new Dom($this->utils, $this->gettext, $this->host, $this->settings);
        $this->router =
            $router ?: new Router($this->utils, $this->gettext, $this->host, $this->settings);
    }

    function start($args = [])
    {
        $this->utils->lb();
        $this->settings->set($args);
        $this->host->setup();
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $this->gettext->createLngFolderIfNotExists();
        if ($this->settings->shouldBeResetted() === true) {
            $this->gettext->deletePotPoMoFiles();
        }
        $this->gettext->preloadGettextInCache();
        $this->router->redirectPrefixedSourceLng();
        $this->gettext->addCurrentUrlToTranslations();
        if (!$this->host->currentUrlIsExcluded()) {
            $this->router->initMagicRouter();
        }
        ob_start();
    }

    function stop()
    {
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $html = ob_get_contents();
        $html = $this->dom->modifyHtml($html);
        ob_end_clean();
        echo $html;
        $this->gettext->generateGettextFiles();
        $this->utils->le();
    }
}
