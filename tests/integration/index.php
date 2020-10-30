<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
use vielhuber\gtbabel\Gtbabel;
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();
$gtbabel = new Gtbabel();

$gtbabel->start([
    'languages' => [
        [
            'code' => 'de',
            'label' => 'Deutsch',
            'rtl' => false,
            'hreflang_code' => 'de',
            'google_translation_code' => 'de',
            'microsoft_translation_code' => 'de',
            'deepl_translation_code' => 'de',
            'url_base' => 'http://gtbabel.local.vielhuber.de/some/sub/path',
            'url_prefix' => 'de'
        ],
        [
            'code' => 'en',
            'label' => 'English',
            'rtl' => false,
            'hreflang_code' => 'en',
            'google_translation_code' => 'en',
            'microsoft_translation_code' => 'en',
            'deepl_translation_code' => 'en',
            'url_base' => 'http://gtbabel.local.vielhuber.de/some/sub/path',
            'url_prefix' => 'en'
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
    <p><a href="http://gtbabel.local.vielhuber.de/some/sub/path/">Dies ist ein Link</a></p>
    <p><a href="http://gtbabel.local.vielhuber.de/some/sub/path/datenschutz/">Dies ist ein Link</a></p>


    <div class="js">
        <div class="text">Dies ist ein Test</div>
        <div class="timer"></div>
    </div>
</body>
</html>
<?php $gtbabel->stop();
//$gtbabel->reset();
