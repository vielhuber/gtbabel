<?php
declare(strict_types=1);
return [
    // don't prefix the following namespaces
    'whitelist' => [
        'GtbabelWordPress\*', // all global/native/class based functions in the wordpress plugin class (you must add a namespace "namespace GtbabelWordPress;" inside the file before!)
        'vielhuber\gtbabel\*' // all global/native/class based functions in the main wordpress class
    ],
    'files-whitelist' => [
        'uninstall.php', // the uninstall file
        'helpers.php', // all hotloaded global functions by the composer package itself
        'vendor/vielhuber/stringhelper/stringhelper.php' // all libraries with global functions that are hotloaded
    ],
    // all global functions/classes should NOT be prefixed
    'whitelist-global-constants' => true,
    'whitelist-global-classes' => true,
    'whitelist-global-functions' => true,
    // correct custom code
    'patchers' => [
        function (string $filePath, string $prefix, string $content): string {
            // DOMXPath is wrongly prefixed
            if (mb_strpos($filePath, 'src/Dom.php') !== false) {
                $content = str_replace('\\' . $prefix . '\\DOMXpath', '\\DOMXpath', $content);
            }
            return $content;
        }
    ]
];
