<?php
namespace vielhuber\gtbabel;

use Cocur\Slugify\Slugify;

use vielhuber\stringhelper\__;

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

    function getContentType($response)
    {
        if (mb_stripos($response, '<!DOCTYPE') === 0) {
            return 'html';
        }
        if (mb_stripos($response, '<html') === 0) {
            return 'html';
        }
        if (__::string_is_json($response)) {
            return 'json';
        }
        if (strip_tags($response) !== $response) {
            return 'html';
        }
        return 'html';
    }

    function getCurrentTime()
    {
        $date = new \DateTime('now');
        return $date->format('Y-m-d H:i:s.u');
    }

    function isWordPress()
    {
        return function_exists('get_bloginfo');
    }
}
