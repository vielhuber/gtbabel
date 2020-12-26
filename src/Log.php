<?php
namespace vielhuber\gtbabel;

class Log
{
    function __construct(Utils $utils = null, Settings $settings = null)
    {
        $this->utils = $utils ?: new Utils();
        $this->settings = $settings ?: new Settings();
    }

    function setup()
    {
        $this->setupLogFolder();
    }

    function setupLogFolder()
    {
        if (!is_dir($this->getLogFolder())) {
            mkdir($this->getLogFolder(), 0777, true);
        }
        if (!file_exists($this->getLogFolder() . '/.htaccess')) {
            file_put_contents($this->getLogFolder() . '/.htaccess', 'Deny from all');
        }
    }

    function getLogFolder()
    {
        return $this->utils->getFileOrFolderWithAbsolutePath($this->settings->get('log_folder'));
    }

    function generalLogReset()
    {
        @unlink($this->generalLogFilename());
    }

    function generalLog(...$msg)
    {
        if (is_array($msg) && count($msg) === 1) {
            $msg = reset($msg);
        }
        $filename = $this->generalLogFilename();
        if (!file_exists($filename)) {
            file_put_contents($filename, '');
        }
        if (is_array($msg)) {
            $msg = print_r($msg, true);
        }
        if (is_object($msg)) {
            $msg = print_r((array) $msg, true);
        }
        $msg = date('Y-m-d H:i:s') . ': ' . $msg;
        file_put_contents($filename, $msg . PHP_EOL, FILE_APPEND);
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
