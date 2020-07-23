<?php
namespace vielhuber\gtbabel;

class Altlng
{
    public $settings;
    public $host;

    function __construct(Settings $settings = null, Host $host = null)
    {
        $this->settings = $settings ?: new Settings();
        $this->host = $host ?: new Host();
    }

    function edit($url, $lng = null)
    {
        $alt_lng_urls = $this->settings->get('alt_lng_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        $alt_lng_urls[$url] = $lng;
        if ($lng === null || $lng === $this->settings->getSourceLanguageCode()) {
            unset($alt_lng_urls[$url]);
        }
        $this->settings->set('alt_lng_urls', $alt_lng_urls);
    }

    function change($old_url, $new_url)
    {
        $alt_lng_urls = $this->settings->get('alt_lng_urls');
        $old_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($old_url), '/');
        $new_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($new_url), '/');
        if (!array_key_exists($old_url, $alt_lng_urls)) {
            return;
        } else {
            $lng = $alt_lng_urls[$old_url];
            unset($alt_lng_urls[$old_url]);
            $alt_lng_urls[$new_url] = $lng;
        }
        $this->settings->set('alt_lng_urls', $alt_lng_urls);
    }

    function get($url = null)
    {
        if ($url === null) {
            $url = $this->host->getCurrentUrl();
        }
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        $alt_lng_urls = $this->settings->get('alt_lng_urls');
        if ($alt_lng_urls === null) {
            return null;
        }
        return @$alt_lng_urls[$url] ?? $this->settings->getSourceLanguageCode();
    }
}
