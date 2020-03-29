<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;

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

    function getLogFolder()
    {
        return rtrim($this->utils->getDocRoot(), '/') . '/' . trim($this->settings->get('log_folder'), '/');
    }

    function setupLogFolder()
    {
        if (!is_dir($this->getLogFolder())) {
            mkdir($this->getLogFolder());
        }
        if (!file_exists($this->getLogFolder() . '/.gitignore')) {
            file_put_contents($this->getLogFolder() . '/.gitignore', '*');
        }
    }

    function apiStatsFilename()
    {
        return $this->getLogFolder() . '/api-stats.txt';
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
            $this->setupLogFolder();
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
        return $this->getLogFolder() . '/discovery-log.txt';
    }

    function discoveryLogGet($since_time = null, $url = null, $slim_output = true)
    {
        $strings = [];

        $filename = $this->discoveryLogFilename();
        if (!file_exists($filename)) {
            return $strings;
        }

        $urls = null;
        if ($url !== null) {
            if (is_array($url)) {
                $urls = $url;
            }
            if (is_string($url)) {
                $urls = [$url];
            }
        }

        $data = file_get_contents($filename);
        $data = explode(PHP_EOL, $data);

        foreach ($data as $data__value) {
            $line_parts = explode("\t", $data__value);
            if ($urls !== null && !in_array($line_parts[0], $urls)) {
                continue;
            }
            if ($since_time !== null && floatval($since_time) > floatval($line_parts[4])) {
                continue;
            }
            $strings[] = [
                'string' => $line_parts[1],
                'context' => $line_parts[2],
                'url' => $line_parts[0],
                'lng' => $line_parts[3],
                'date' => $line_parts[4],
                'order' => count($strings) + 1
            ];
        }

        usort($strings, function ($a, $b) {
            if ($a['context'] != $b['context']) {
                return strcmp($a['context'], $b['context']);
            }
            return $a['order'] - $b['order'];
        });

        if ($slim_output === true) {
            foreach ($strings as $strings__key => $strings__value) {
                unset($strings[$strings__key]['url']);
                unset($strings[$strings__key]['lng']);
                unset($strings[$strings__key]['date']);
                unset($strings[$strings__key]['order']);
            }
            $strings = __::array_unique($strings);
            $strings = array_values($strings);
        }

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

    function discoveryLogAdd($url, $str, $context, $lng)
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
            $this->setupLogFolder();
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
        $data[] = $url . "\t" . $str . "\t" . $context . "\t" . $lng . "\t" . microtime(true);
        $data = implode(PHP_EOL, $data);
        file_put_contents($filename, $data);
        $this->discovery_log_cache = $data;
    }

    function generalLog($msg)
    {
        $filename = $this->generalLogFilename();
        if (!file_exists($filename)) {
            file_put_contents($filename, '');
        }
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }
        $msg = date('Y-m-d H:i:s') . ': ' . $msg;
        file_put_contents($filename, $msg . PHP_EOL . file_get_contents($filename));
    }

    function generalLogFilename()
    {
        return $this->getLogFolder() . '/general-log.txt';
    }

    function lb($message = '')
    {
        if (!isset($GLOBALS['performance'])) {
            $GLOBALS['performance'] = [];
        }
        $GLOBALS['performance'][] = ['time' => microtime(true), 'message' => $message];
    }

    function le()
    {
        $this->generalLog(
            'script ' .
                $GLOBALS['performance'][count($GLOBALS['performance']) - 1]['message'] .
                ' execution time: ' .
                number_format(
                    microtime(true) - $GLOBALS['performance'][count($GLOBALS['performance']) - 1]['time'],
                    5
                ) .
                ' seconds'
        );
        unset($GLOBALS['performance'][count($GLOBALS['performance']) - 1]);
        $GLOBALS['performance'] = array_values($GLOBALS['performance']);
    }
}
