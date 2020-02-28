<?php
namespace vielhuber\gtbabel;

use Cocur\Slugify\Slugify;

class Utils
{
    function slugify($trans, $orig, $lng)
    {
        $slugify = new Slugify();
        $suggestion = $slugify->slugify($trans, '-');
        if (mb_strlen($suggestion) < mb_strlen($trans) / 2) {
            return $orig . '-' . $lng;
        }
        return $suggestion;
    }

    function getDocRoot()
    {
        return @$_SERVER['DOCUMENT_ROOT'] == '' ? './' : $_SERVER['DOCUMENT_ROOT'];
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
        $filename = $this->getDocRoot() . '/log.txt';
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }
        $msg = date('Y-m-d H:i:s') . ': ' . $msg;
        file_put_contents($filename, $msg . PHP_EOL . @file_get_contents($filename));
    }

    function getContentType($response)
    {
        if (mb_stripos($response, '<!DOCTYPE') === 0) {
            return 'html';
        }
        if (mb_stripos($response, '<html') === 0) {
            return 'html';
        }
        if (__string_is_json($response)) {
            return 'json';
        }
        if (strip_tags($response) !== $response) {
            return 'html';
        }
        return 'html';
    }
}
