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
        $prevent_publish_urls = array_filter($prevent_publish_urls, function ($a) use ($url) {
            return $a['url'] !== $url;
        });
        if ($lngs !== null && !empty($lngs)) {
            $prevent_publish_urls[] = ['url' => $url, 'lng' => $lngs];
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function unpublish($url, $lng)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        $found = false;
        foreach ($prevent_publish_urls as $prevent_publish_urls__key => $prevent_publish_urls__value) {
            if ($prevent_publish_urls__value['url'] !== $url) {
                continue;
            }
            $found = true;
            if (!in_array($lng, $prevent_publish_urls__value['lng'])) {
                $prevent_publish_urls[$prevent_publish_urls__key]['lng'][] = $lng;
            }
        }
        if ($found === false) {
            $prevent_publish_urls[] = ['url' => $url, 'lng' => [$lng]];
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function publish($url, $lng)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        foreach ($prevent_publish_urls as $prevent_publish_urls__key => $prevent_publish_urls__value) {
            if ($prevent_publish_urls__value['url'] !== $url) {
                continue;
            }
            if (in_array($lng, $prevent_publish_urls__value['lng'])) {
                $prevent_publish_urls[$prevent_publish_urls__key]['lng'] = array_diff(
                    $prevent_publish_urls__value['lng'],
                    [$lng]
                );
            }
            if (empty($prevent_publish_urls[$prevent_publish_urls__key]['lng'])) {
                unset($prevent_publish_urls[$prevent_publish_urls__key]);
            }
        }
        $this->settings->set('prevent_publish_urls', $prevent_publish_urls);
    }

    function change($old_url, $new_url)
    {
        $prevent_publish_urls = $this->settings->get('prevent_publish_urls');
        $old_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($old_url), '/');
        $new_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($new_url), '/');
        foreach ($prevent_publish_urls as $prevent_publish_urls__key => $prevent_publish_urls__value) {
            if ($prevent_publish_urls__value['url'] !== $old_url) {
                continue;
            }
            $prevent_publish_urls[$prevent_publish_urls__key]['url'] = $new_url;
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
        foreach ($prevent_publish_urls as $prevent_publish_urls__value) {
            if ($allow_regex === true) {
                $regex = str_replace('\*', '.*', preg_quote(trim($prevent_publish_urls__value['url'], '/'), '/'));
                if (preg_match('/' . $regex . '/', trim($url, '/')) == 0) {
                    continue;
                }
            } else {
                if (mb_strpos(trim($url, '/'), trim($prevent_publish_urls__value['url'], '/')) === false) {
                    continue;
                }
            }
            return in_array($lng, $prevent_publish_urls__value['lng']);
        }
        return false;
    }
}
