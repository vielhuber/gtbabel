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
        $prevent_publish = $this->settings->get('prevent_publish');
        $url = '/' . trim(str_replace($this->host->getCurrentHost(), '', $url), '/');
        if ($lngs === null || empty($lngs)) {
            if (array_key_exists($url, $prevent_publish)) {
                unset($prevent_publish[$url]);
            }
        } else {
            $prevent_publish[$url] = $lngs;
        }
        $this->settings->set('prevent_publish', $prevent_publish);
    }

    function change($old_url, $new_url)
    {
        $prevent_publish = $this->settings->get('prevent_publish');
        $old_url = '/' . trim(str_replace($this->host->getCurrentHost(), '', $old_url), '/');
        $new_url = '/' . trim(str_replace($this->host->getCurrentHost(), '', $new_url), '/');
        if (!array_key_exists($old_url, $prevent_publish)) {
            return;
        } else {
            $lngs = $prevent_publish[$old_url];
            unset($prevent_publish[$old_url]);
            $prevent_publish[$new_url] = $lngs;
        }
        $this->settings->set('prevent_publish', $prevent_publish);
    }

    function isActive($url, $lng)
    {
        $prevent_publish = $this->settings->get('prevent_publish');
        foreach ($prevent_publish as $prevent_publish__key => $prevent_publish__value) {
            $regex = str_replace('\*', '.*', preg_quote(trim($prevent_publish__key, '/'), '/'));
            if (preg_match('/' . $regex . '/', trim($url, '/')) == 0) {
                continue;
            }
            return in_array($lng, $prevent_publish__value);
        }
        return false;
    }
}
