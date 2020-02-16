<?php
namespace vielhuber\gtbabel;

use Cocur\Slugify\Slugify;

class Utils
{
    private $stats_cache = null;

    function slugify($trans, $orig, $lng)
    {
        $slugify = new Slugify();
        $suggestion = $slugify->slugify($trans, '-');
        if (mb_strlen($suggestion) < mb_strlen($trans) / 2) {
            return $orig . '-' . $lng;
        }
        return $suggestion;
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
        $this->log(
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

    function log($msg)
    {
        $filename = $_SERVER['DOCUMENT_ROOT'] . '/log.txt';
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }
        file_put_contents($filename, $msg . PHP_EOL . @file_get_contents($filename));
    }

    function statsFilename()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/stats-api.txt';
    }

    function statsAdd($service, $count)
    {
        $filename = $this->statsFilename();
        if ($this->stats_cache === null) {
            if (!file_exists($filename)) {
                file_put_contents($filename, '');
            }
            $this->stats_cache = file_get_contents($filename);
        }
        $data = $this->stats_cache;
        $data = explode(PHP_EOL, $data);
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
        $this->stats_cache = $data;
    }

    function statsGet($service)
    {
        $filename = $this->statsFilename();
        if ($this->stats_cache === null) {
            if (!file_exists($filename)) {
                return 0;
            }
            $this->stats_cache = file_get_contents($filename);
        }
        $data = $this->stats_cache;
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
}
