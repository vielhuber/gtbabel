<?php
namespace vielhuber\gtbabel;

use vielhuber\stringhelper\__;

class Log
{
    public $stats_log_to_save;
    public $discovery_log_to_save;

    public $utils;
    public $settings;
    public $host;

    function __construct(Utils $utils = null, Settings $settings = null, Host $host = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->settings = $settings ?: new Settings();
        $this->host = $host ?: new Host();
    }

    function setup()
    {
        $this->setupLogFolder();
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

    function getLogFolder()
    {
        return rtrim($this->utils->getDocRoot(), '/') . '/' . trim($this->settings->get('log_folder'), '/');
    }

    function statsLogFilename()
    {
        return $this->getLogFolder() . '/stats-log.txt';
    }

    function statsLogGet($service)
    {
        $filename = $this->statsLogFilename();
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

    function statsLogIsDisabled()
    {
        return !($this->settings->get('stats_log') == '1');
    }

    function statsLogReset()
    {
        @unlink($this->statsLogFilename());
    }

    function statsLogIncrease($service, $count)
    {
        if ($this->statsLogIsDisabled()) {
            return;
        }

        if ($this->stats_log_to_save === null) {
            $filename = $this->statsLogFilename();
            if (!file_exists($filename)) {
                file_put_contents($filename, '');
            }
            $data = file_get_contents($filename);
            $data = explode(PHP_EOL, $data);
        } else {
            $data = $this->stats_log_to_save;
        }

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

        // we save this asynchroniously, because otherwise hdd throughput is the bottleneck
        $this->stats_log_to_save = $data;
    }

    function statsLogSave()
    {
        if ($this->stats_log_to_save === null) {
            return;
        }
        $filename = $this->statsLogFilename();
        if (!file_exists($filename)) {
            file_put_contents($filename, '');
        }
        file_put_contents($filename, implode(PHP_EOL, $this->stats_log_to_save) . PHP_EOL);
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

        $handle = fopen($filename, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line_parts = explode("\t", $line);
                if ($urls !== null && !in_array($line_parts[0], $urls)) {
                    continue;
                }
                if ($since_time !== null && floatval($since_time) > floatval($line_parts[4])) {
                    continue;
                }

                // needed for fast array unique below (for hosts that have a low memory limit)
                if ($slim_output === true) {
                    $key = $line_parts[1] . '#' . $line_parts[2];
                } else {
                    $key =
                        $line_parts[1] .
                        '#' .
                        $line_parts[2] .
                        '#' .
                        $line_parts[0] .
                        '#' .
                        $line_parts[3] .
                        '#' .
                        $line_parts[4] .
                        '#' .
                        (count($strings) + 1);
                }

                $strings[$key] = [
                    'string' => $line_parts[1],
                    'context' => $line_parts[2],
                    'url' => $line_parts[0],
                    'lng' => $line_parts[3],
                    'date' => $line_parts[4],
                    'order' => count($strings) + 1
                ];
            }
            fclose($handle);
        }

        usort($strings, function ($a, $b) {
            if ($a['context'] != $b['context']) {
                return strcmp($a['context'], $b['context']);
            }
            return $a['order'] - $b['order'];
        });

        if ($slim_output === true) {
            $strings = array_map(function ($a) {
                return [
                    'string' => $a['string'],
                    'context' => $a['context']
                ];
            }, $strings);
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
        // we save this asynchroniously, because otherwise hdd throughput is the bottleneck
        if ($this->discovery_log_to_save === null) {
            $this->discovery_log_to_save = [];
        }
        $this->discovery_log_to_save[] = $url . "\t" . $str . "\t" . $context . "\t" . $lng . "\t" . microtime(true);
    }

    function discoveryLogSave()
    {
        if ($this->discovery_log_to_save === null) {
            return;
        }
        $filename = $this->discoveryLogFilename();
        if (!file_exists($filename)) {
            file_put_contents($filename, '');
        }
        file_put_contents($filename, implode(PHP_EOL, $this->discovery_log_to_save) . PHP_EOL, FILE_APPEND);
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
