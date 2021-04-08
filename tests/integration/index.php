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
            'hidden' => false,
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
            'hidden' => false,
            'url_base' => $settings['en']['url_base'],
            'url_prefix' => $settings['en']['url_prefix']
        ]
    ],
    'lng_source' => 'de',
    'lng_target' => null,
    'database' => [
        'type' => 'sqlite',
        'filename' => './data.db',
        'table' => 'translations'
    ],
    'log_folder' => './../logs',
    'redirect_root_domain' => 'source',
    'auto_translation' => true,
    'auto_translation_service' => [
        [
            'provider' => 'google',
            'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
            'throttle_chars_per_month' => 1000000,
            'lng' => null,
            'label' => null,
            'api_url' => null,
            'disabled' => false
        ],
        [
            'provider' => 'microsoft',
            'api_keys' => @$_SERVER['MICROSOFT_TRANSLATION_API_KEY'],
            'throttle_chars_per_month' => 1000000,
            'lng' => null,
            'label' => null,
            'api_url' => null,
            'disabled' => false
        ],
        [
            'provider' => 'deepl',
            'api_keys' => @$_SERVER['DEEPL_TRANSLATION_API_KEY'],
            'throttle_chars_per_month' => 1000000,
            'lng' => null,
            'label' => null,
            'api_url' => null,
            'disabled' => false
        ]
    ],
    'localize_js' => false,
    'detect_dom_changes' => true,
    'detect_dom_changes_include' => [['selector' => '.js']],
    'show_language_picker' => true,
    'frontend_editor' => @$_GET['gtbabel_frontend_editor'] == '1',
    'show_frontend_editor_links' => true
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
                // press "n"
                if (e.which == 110) {
                    document.querySelector('.timer').innerHTML = 'Meine Nummer: '+i;
                    i++;
                }
            });
        });
    </script>
    <style>
        ._4 {
            background-color:brown;
        }
        ._4:before {
            content:"";
            width:10px;
            height:10px;
            display:inline-block;
        }
        .strong { font-weight:bold; }
    </style>
</head>
<body>
    <div class="notranslate" style="font-weight:bold;color:red;font-size:30px;">
        <?php echo $_SERVER['REQUEST_URI']; ?>
    </div>
    <p>Dies ist ein Test!</p>
    <?php if (function_exists('gtbabel_languagepicker')) {
        echo '<ul class="lngpicker">';
        foreach (gtbabel_languagepicker() as $val) {
            echo '<li>';
            echo '<a href="' . $val['url'] . '"' . ($val['active'] ? ' class="active"' : '') . '>';
            echo $val['label'];
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
    } ?>
    <div class="_1" style="padding:40px;background-color:grey;">
        Dies
        <div class="_2" style="padding:40px;background-color:blue;">
            ist ein
            <p class="_3" style="background-color:yellow;display:inline">
                Es gibt im Moment in diese <strong>Mannschaft</strong>, oh, einige Spieler vergessen ihnen Profi was sie sind. Ich lese nicht sehr viele Zeitungen, aber ich habe gehört viele Situationen. Erstens: wir haben nicht offensiv gespielt. Es gibt keine deutsche Mannschaft spielt offensiv und die Name offensiv wie Bayern. Letzte Spiel hatten wir in Platz drei Spitzen: Elber, Jancka und dann Zickler. Wir müssen nicht vergessen Zickler. Zickler ist eine Spitzen mehr, Mehmet eh mehr Basler. Ist klar diese Wörter, ist möglich verstehen, was ich hab gesagt? Danke.
            </p>
        </div>
    </div>

    <div class="_4">
        Das ist ein Test
    </div>

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
