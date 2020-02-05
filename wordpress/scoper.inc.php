<?php
declare(strict_types=1);
return [
     // don't prefix
    'whitelist' => [
        'GtbabelWordPress\*',
        'vielhuber\gtbabel\*',
        'vielhuber\stringhelper\*',
        'vielhuber/stringhelper',
        'vielhuber\stringhelper',
        '*' // this is too much(!)
    ],
    'files-whitelist' => [
        'helpers.php',
        'vendor\vielhuber\stringhelper\stringhelper.php',
        'vendor/vielhuber/stringhelper/stringhelper.php',
        'vielhuber\stringhelper\stringhelper.php',
        'stringhelper.php'
    ],
    'whitelist-global-constants' => true,
    'whitelist-global-classes' => true,
    'whitelist-global-functions' => true,
];