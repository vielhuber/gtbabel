<?php
namespace vielhuber\gtbabel;

class Log
{
    public $api_stats_cache;
    public $discovery_log_cache;

    public $utils;
    public $settings;
    public $host;

    function __construct(Utils $utils = null, Settings $settings = null, Host $host = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->settings = $settings ?: new Settings();
        $this->host = $host ?: new Host();
    }

    function apiStatsFilename()
    {
        return rtrim($this->utils->getDocRoot(), '/') . '/' . ltrim($this->settings->get('api_stats_filename'), '/');
    }

    function apiStatsGet($service)
    {
        $filename = $this->apiStatsFilename();
        if (!file_exists($filename)) {
            return 0;
        }
        $data = file_get_contents($filename);
        $data = explode(PHP_EOL, $data);
        foreach ($data as $data__value) {
            $line_parts = explode('=', $data__value);
            if ($service !== $line_parts[0]) {
                continue;
            }
            return intval($line_parts[1]);
        }
        return 0;
    }

    function apiStatsIsDisabled()
    {
        return !($this->settings->get('api_stats') == '1');
    }

    function apiStatsReset()
    {
        @unlink($this->apiStatsFilename());
    }

    function apiStatsIncrease($service, $count)
    {
        if ($this->apiStatsIsDisabled()) {
            return;
        }
        $filename = $this->apiStatsFilename();
        // we use a cache on writing (not reading, because this has to be live)
        if ($this->api_stats_cache === null) {
            if (!file_exists($filename)) {
                file_put_contents($filename, '');
            }
            $this->api_stats_cache = file_get_contents($filename);
        }
        $data = $this->api_stats_cache;
        $data = explode(PHP_EOL, $data);
        foreach ($data as $data__key => $data__value) {
            if (trim($data__value) == '') {
                unset($data[$data__key]);
            }
        }
        $avail = false;
        foreach ($data as $data__key => $data__value) {
            $line_parts = explode('=', $data__value);
            if ($service !== $line_parts[0]) {
                continue;
            }
            $avail = true;
            $line_parts[1] = intval($line_parts[1]) + $count;
            $data[$data__key] = implode('=', $line_parts);
        }
        if ($avail === false) {
            $data[] = $service . '=' . $count;
        }
        $data = implode(PHP_EOL, $data);
        file_put_contents($filename, $data);
        $this->api_stats_cache = $data;
    }

    function discoveryLogFilename()
    {
        return rtrim($this->utils->getDocRoot(), '/') .
            '/' .
            ltrim($this->settings->get('discovery_log_filename'), '/');
    }

    function discoveryLogGet($url = null)
    {
        $filename = $this->discoveryLogFilename();
        if (!file_exists($filename)) {
            return [];
        }
        $data = file_get_contents($filename);
        $data = explode(PHP_EOL, $data);

        $strings = [];
        foreach ($data as $data__value) {
            $line_parts = explode("\t", $data__value);
            if ($url !== null) {
                if (is_array($url) && !in_array($line_parts[0], $url)) {
                    continue;
                }
                if (is_string($url) && $line_parts[0] != $url) {
                    continue;
                }
            }
            $strings[] = [
                'string' => $line_parts[1],
                'context' => $line_parts[2]
            ];
        }

        $strings = __array_unique($strings);
        usort($strings, function ($a, $b) {
            if ($a['context'] != $b['context']) {
                return strcmp($a['context'], $b['context']);
            }
            return strcmp($a['string'], $b['string']);
        });

        return $strings;
    }

    function discoveryLogIsDisabled()
    {
        return !($this->settings->get('discovery_log') == '1');
    }

    function discoveryLogReset()
    {
        @unlink($this->discoveryLogFilename());
    }

    function discoveryLogAdd($url, $str, $context)
    {
        if ($this->discoveryLogIsDisabled()) {
            return;
        }
        if ($this->host->currentUrlIsExcluded()) {
            return;
        }
        $filename = $this->discoveryLogFilename();
        // we use a cache on writing (not reading, because this has to be live)
        if ($this->discovery_log_cache === null) {
            if (!file_exists($filename)) {
                file_put_contents($filename, '');
            }
            $this->discovery_log_cache = file_get_contents($filename);
        }
        $data = $this->discovery_log_cache;
        $data = explode(PHP_EOL, $data);
        foreach ($data as $data__key => $data__value) {
            if (trim($data__value) == '') {
                unset($data[$data__key]);
            }
        }
        array_unshift($data, $url . "\t" . $str . "\t" . $context);
        $data = implode(PHP_EOL, $data);
        file_put_contents($filename, $data);
        $this->discovery_log_cache = $data;
    }
}
