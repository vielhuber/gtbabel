<?php
namespace vielhuber\gtbabel;

class Publish
{
    public $settings;
    public $host;

    function __construct(Settings $settings = null, Host $host = null)
    {
        $this->settings = $settings ?: new Settings();
        $this->host = $host ?: new Host();
    }

    function edit($url, $lngs = null)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $url = '/' . trim(str_replace($this->host->getCurrentHost(), '', $url), '/');
        if ($lngs === null || empty($lngs)) {
            if (array_key_exists($url, $prevent_publish_urls)) {
                unset($prevent_publish_urls[$url]);
            }
        } else {
            $prevent_publish_urls[$url] = $lngs;
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function change($old_url, $new_url)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $old_url = '/' . trim(str_replace($this->host->getCurrentHost(), '', $old_url), '/');
        $new_url = '/' . trim(str_replace($this->host->getCurrentHost(), '', $new_url), '/');
        if (!array_key_exists($old_url, $prevent_publish_urls)) {
            return;
        } else {
            $lngs = $prevent_publish_urls[$old_url];
            unset($prevent_publish_urls[$old_url]);
            $prevent_publish_urls[$new_url] = $lngs;
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function isActive()
    {
        return $this->settings->get('prevent_publish');
    }

    function isPrevented($url, $lng)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        foreach ($prevent_publish_urls as $prevent_publish_urls__key => $prevent_publish_urls__value) {
            $regex = str_replace('\*', '.*', preg_quote(trim($prevent_publish_urls__key, '/'), '/'));
            if (preg_match('/' . $regex . '/', trim($url, '/')) == 0) {
                continue;
            }
            return in_array($lng, $prevent_publish_urls__value);
        }
        return false;
    }
}
