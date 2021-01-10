<?php
namespace vielhuber\gtbabel;

class Altlng
{
    function __construct(Settings $settings = null, Host $host = null)
    {
        $this->settings = $settings ?: new Settings();
        $this->host = $host ?: new Host();
    }

    function edit($url, $lng = null)
    {
        $alt_lng_urls = $this->settings->get('alt_lng_urls');
        $url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($url), '/');
        $alt_lng_urls = array_filter($alt_lng_urls, function ($a) use ($url) {
            return $a['url'] !== $url;
        });
        if ($lng !== null && $lng !== $this->settings->getSourceLanguageCode()) {
            $alt_lng_urls[] = ['url' => $url, 'lng' => $lng];
        }
        $this->settings->set('alt_lng_urls', $alt_lng_urls);
    }

    function change($old_url, $new_url)
    {
        $alt_lng_urls = $this->settings->get('alt_lng_urls');
        $old_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($old_url), '/');
        $new_url = '/' . trim($this->host->getPathWithoutPrefixFromUrl($new_url), '/');
        foreach ($alt_lng_urls as $alt_lng_urls__key => $alt_lng_urls__value) {
            if ($alt_lng_urls__value['url'] !== $old_url) {
                continue;
            }
            $alt_lng_urls[$alt_lng_urls__key]['url'] = $new_url;
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
        foreach ($alt_lng_urls as $alt_lng_urls__value) {
            if ($alt_lng_urls__value['url'] !== $url) {
                continue;
            }
            return $alt_lng_urls__value['lng'];
        }
        return $this->settings->getSourceLanguageCode();
    }
}
