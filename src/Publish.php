<?php
namespace vielhuber\gtbabel;

class Publish
{
    function __construct(Settings $settings = null, Host $host = null, Log $log = null)
    {
        $this->settings = $settings ?: new Settings();
        $this->host = $host ?: new Host();
        $this->log = $log ?: new Log();
    }

    function edit($url, $lngs = null)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        if ($lngs === null || empty($lngs)) {
            if (array_key_exists($url, $prevent_publish_urls)) {
                unset($prevent_publish_urls[$url]);
            }
        } else {
            $prevent_publish_urls[$url] = $lngs;
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function unpublish($url, $lng)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        if (!array_key_exists($url, $prevent_publish_urls)) {
            $prevent_publish_urls[$url] = [];
        }
        if (!in_array($lng, $prevent_publish_urls[$url])) {
            $prevent_publish_urls[$url][] = $lng;
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function publish($url, $lng)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        if (!array_key_exists($url, $prevent_publish_urls)) {
            return;
        }
        if (in_array($lng, $prevent_publish_urls[$url])) {
            $prevent_publish_urls[$url] = array_diff($prevent_publish_urls[$url], [$lng]);
        }
        if (empty($prevent_publish_urls[$url])) {
            unset($prevent_publish_urls[$url]);
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function change($old_url, $new_url)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $old_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($old_url), '/');
        $new_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($new_url), '/');
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

    function isPrevented($url, $lng, $allow_regex = true)
    {
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        foreach ($prevent_publish_urls as $prevent_publish_urls__key => $prevent_publish_urls__value) {
            if ($allow_regex === true) {
                $regex = str_replace('\*', '.*', preg_quote(trim($prevent_publish_urls__key, '/'), '/'));
                if (preg_match('/' . $regex . '/', trim($url, '/')) == 0) {
                    continue;
                }
            } else {
                if (mb_strpos(trim($url, '/'), trim($prevent_publish_urls__key, '/')) === false) {
                    continue;
                }
            }
            return in_array($lng, $prevent_publish_urls__value);
        }
        return false;
    }
}
