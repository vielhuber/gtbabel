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

    function getFileOrFolderWithAbsolutePath($folder)
    {
        $root = $this->getDocRoot();
        if (strpos($folder, $root) === false) {
            $folder = rtrim($root, '/') . '/' . $folder;
        }
        return $folder;
    }

    function guessContentType($response)
    {
        if (mb_stripos($response, '<?xml') === 0) {
            return 'xml';
        }
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
        // detect dynamically generated css files (that don't have an appropriate content header)
        if (
            strpos($response, '{') !== false &&
            (strpos($response, ':') === false || strpos($response, ';') !== false)
        ) {
            return 'css';
        }
        return 'plain';
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

    function getWordPressPluginFileStorePathRelative()
    {
        return '/' . trim(str_replace($this->getDocRoot(), '', wp_upload_dir()['basedir']), '/') . '/gtbabel';
    }

    function rrmdir($folder)
    {
        __::rrmdir($folder);
    }
}
