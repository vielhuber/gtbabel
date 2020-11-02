<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$test = 1;
if ($test === 1) {
    $settings = [
        'de' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => 'de'
        ],
        'en' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => 'en'
        ]
    ];
}
if ($test === 2) {
    $settings = [
        'de' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => ''
        ],
        'en' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => 'en'
        ]
    ];
}
if ($test === 3) {
    $settings = [
        'de' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => 'deutsch'
        ],
        'en' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => 'english'
        ]
    ];
}
if ($test === 4) {
    $settings = [
        'de' => [
            'url_base' => 'http://gtbabel-de.local.vielhuber.de',
            'url_prefix' => ''
        ],
        'en' => [
            'url_base' => 'http://gtbabel-en.local.vielhuber.de',
            'url_prefix' => ''
        ]
    ];
    if (
        'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'] ===
        'http://gtbabel.local.vielhuber.de/'
    ) {
        header('Location: http://gtbabel-de.local.vielhuber.de');
        die();
    }
}
if ($test === 5) {
    $settings = [
        'de' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de/some/sub/path',
            'url_prefix' => 'de'
        ],
        'en' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de/some/sub/path',
            'url_prefix' => 'en'
        ]
    ];
    if (
        'http' .
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '') .
            '://' .
            $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'] ===
        'http://gtbabel.local.vielhuber.de/'
    ) {
        header('Location: http://gtbabel.local.vielhuber.de/some/sub/path/');
        die();
    }
}
if ($test === 6) {
    $settings = [
        'de' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => ''
        ],
        'en' => [
            'url_base' => 'http://gtbabel.local.vielhuber.de',
            'url_prefix' => ''
        ]
    ];
}

use vielhuber\gtbabel\Gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();
$gtbabel = new Gtbabel();
$gtbabel->config([
    'languages' => [
        [
            'code' => 'de',
            'label' => 'Deutsch',
            'rtl' => false,
            'hreflang_code' => 'de',
            'google_translation_code' => 'de',
            'microsoft_translation_code' => 'de',
            'deepl_translation_code' => 'de',
            'url_base' => $settings['de']['url_base'],
            'url_prefix' => $settings['de']['url_prefix']
        ],
        [
            'code' => 'en',
            'label' => 'English',
            'rtl' => false,
            'hreflang_code' => 'en',
            'google_translation_code' => 'en',
            'microsoft_translation_code' => 'en',
            'deepl_translation_code' => 'en',
            'url_base' => $settings['en']['url_base'],
            'url_prefix' => $settings['en']['url_prefix']
        ]
    ],
    'lng_source' => 'de',
    'lng_target' => null,
    'database' => [
        'type' => 'sqlite',
        'filename' => './../data.db',
        'table' => 'translations'
    ],
    'log_folder' => './../logs',
    'auto_translation' => true,
    'auto_translation_service' => [
        [
            'provider' => 'google',
            'lng' => null
        ]
    ],
    'google_translation_api_key' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
    'microsoft_translation_api_key' => @$_SERVER['MICROSOFT_TRANSLATION_API_KEY'],
    'deepl_translation_api_key' => @$_SERVER['DEEPL_TRANSLATION_API_KEY'],
    'localize_js' => false,
    'detect_dom_changes' => true,
    'detect_dom_changes_include' => ['.js']
]);

$gtbabel->start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>.</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let i = 0;
            document.addEventListener('keypress', function(e) {
                if (e.which == 110) {
                    document.querySelector('.timer').innerHTML = 'Meine Nummer: '+i;
                    i++;
                }
            });
        });
    </script>
</head>
<body>
    <div class="notranslate" style="font-weight:bold;color:red;font-size:30px;">
        <?php echo $_SERVER['REQUEST_URI']; ?>
    </div>
    <p>Dies ist ein Test!</p>
    <?php if (function_exists('gtbabel_languagepicker')) {
        echo '<ul class="lngpicker">';
        foreach (gtbabel_languagepicker() as $languagepicker__value) {
            echo '<li>';
            echo '<a href="' .
                $languagepicker__value['url'] .
                '"' .
                ($languagepicker__value['active'] ? ' class="active"' : '') .
                '>';
            echo $languagepicker__value['label'];
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    } ?>
    <p><a href="<?php echo $settings['de']['url_base']; ?>/">Dies ist ein Link</a></p>
    <p><a href="<?php echo $settings['de']['url_base']; ?>/<?php echo $test !== 6
    ? 'datenschutz/'
    : '?page_id=1337'; ?>">Dies ist ein Link</a></p>


    <div class="js">
        <div class="text">Dies ist ein Test</div>
        <div class="timer"></div>
    </div>
</body>
</html>
<?php $gtbabel->stop();
//$gtbabel->reset();
