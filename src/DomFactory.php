<?php
namespace vielhuber\gtbabel;

class DomFactory
{
    function __construct(
        Utils $utils = null,
        Data $data = null,
        Host $host = null,
        Settings $settings = null,
        Tags $tags = null,
        Log $log = null,
        Altlng $altlng = null
    ) {
        $this->utils = $utils ?: new Utils();
        $this->data = $data ?: new Data();
        $this->host = $host ?: new Host();
        $this->settings = $settings ?: new Settings();
        $this->tags = $tags ?: new Tags();
        $this->log = $log ?: new Log();
        $this->altlng = $altlng ?: new Altlng();
    }

    function modifyContentFactory($content, $type)
    {
        $dom = new Dom(
            $this->utils,
            $this->data,
            $this->host,
            $this->settings,
            $this->tags,
            $this->log,
            $this->altlng,
            $this
        );
        $content = $dom->modifyContent($content, $type);
        return $content;
    }
}