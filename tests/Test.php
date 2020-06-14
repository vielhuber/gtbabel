<?php
use vielhuber\gtbabel\Gtbabel;
use vielhuber\stringhelper\__;
use Dotenv\Dotenv;

class Test extends \PHPUnit\Framework\TestCase
{
    private $gtbabel;
    private $server_orig;

    protected function setUp(): void
    {
        // load env file
        if (file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }
        // mock response code
        http_response_code(200);
    }

    public function test1()
    {
        $this->runDiff('1.html', 200);
    }

    public function test2()
    {
        $this->runDiff('2.html', 200);
    }

    public function test3()
    {
        $this->runDiff('3.html', 200);
    }

    public function test4()
    {
        $this->runDiff('4.html', 200);
    }

    public function test5()
    {
        $this->runDiff('5.html', 200);
    }

    public function test6()
    {
        $this->runDiff('6.html', 200);
    }

    public function test7()
    {
        $this->runDiff('7.html', 200);
    }

    public function test8()
    {
        $this->runDiff('8.html', 200);
    }

    public function test9()
    {
        $this->runDiff('9.html', 200);
    }

    public function test10()
    {
        $this->runDiff('10.html', 200);
    }

    public function test11()
    {
        $this->runDiff('11.html', 200);
    }

    public function test12()
    {
        $this->runDiff('12.html', 200);
    }

    public function test13()
    {
        $this->runDiff('13.html', 200);
    }

    public function test14()
    {
        $this->runDiff('14.html', 200);
    }

    public function test15()
    {
        $this->runDiff('15.html', 200);
    }

    public function test16()
    {
        $this->runDiff('16.html', 200);
    }

    public function test17()
    {
        $this->runDiff('17.html', 200);
    }

    public function test18()
    {
        $this->runDiff('18.html', 3000);
    }

    public function test19()
    {
        $this->runDiff('19.html', 1500);
    }

    public function test20()
    {
        $this->runDiff('20.html', 200, [
            'languages' => ['de', 'en', 'fr'],
            'html_lang_attribute' => true,
            'html_hreflang_tags' => true
        ]);
    }

    public function test21()
    {
        $this->runDiff('21.php', 7500);
    }

    public function test22()
    {
        $this->runDiff('22.php', 3000);
    }

    public function test23()
    {
        $this->runDiff('23.php', 4500);
    }

    public function test24()
    {
        $this->runDiff('24.php', 3500);
    }

    public function test25()
    {
        $this->runDiff('25.html', 3500, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test26()
    {
        $this->runDiff('26.html', 3500, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test27()
    {
        $this->runDiff('27.html', 3500);
    }

    public function test28()
    {
        $this->runDiff('28.html', 200);
    }

    public function test29()
    {
        $this->runDiff('29.json', 200);
    }

    public function test30()
    {
        $this->runDiff('30.json', 200);
    }

    public function test31()
    {
        $this->runDiff('31.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test32()
    {
        $this->runDiff('32.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test33()
    {
        $this->runDiff('33.html', 200);
    }

    public function test34()
    {
        $this->runDiff('34.html', 200);
    }

    public function test35()
    {
        $this->runDiff('35.html', 200);
    }

    public function test36()
    {
        $this->runDiff('36.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test37()
    {
        $this->runDiff('37.html', 200, [
            'exclude_dom' => ['.foo', '#bar']
        ]);
    }

    public function test38()
    {
        $this->runDiff('38.html', 500, [
            'lng_target' => 'ar',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test39()
    {
        $this->runDiff('39.html', 500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'google_translation_api_key' => null
        ]);
    }

    public function test40()
    {
        $this->runDiff('40.html', 500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'google_translation_api_key' => 'free'
        ]);
    }

    public function test41()
    {
        $this->runDiff('41.html', 2000, [
            'lng_source' => 'en',
            'lng_target' => 'de',
            'debug_translations' => false,
            'auto_translation' => true,
            'prefix_source_lng' => true
        ]);
    }

    public function test42()
    {
        $this->runDiff('42.html', 1500, [
            'lng_source' => 'en',
            'lng_target' => 'de',
            'debug_translations' => false,
            'auto_translation' => true,
            'prefix_source_lng' => true
        ]);
    }

    public function test43()
    {
        $this->runDiff('43.html', 200);
    }

    public function test44()
    {
        $this->runDiff('44.html', 200);
    }

    public function test45()
    {
        $this->runDiff('45.html', 200);
    }

    public function test46()
    {
        $this->runDiff('46.html', 200);
    }

    public function test_translate()
    {
        $this->gtbabel = new Gtbabel();
        $output = $this->gtbabel->translate('<p>Dies ist ein Test!</p>', [
            'lng_source' => 'de',
            'lng_target' => 'en',
            'auto_translation' => true,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => @$_ENV['GOOGLE_TRANSLATION_API_KEY']
        ]);
        $this->assertEquals($output, '<p>This is a test!</p>');
    }

    public function test_tokenize()
    {
        $this->gtbabel = new Gtbabel();
        $this->assertEquals($this->gtbabel->tokenize('<p>Dies ist ein Test!</p>'), [
            ['str' => 'Dies ist ein Test!', 'context' => null]
        ]);
        $this->assertEquals($this->gtbabel->tokenize('<div><p>Dies ist ein Test!</p><p>1</p></div>'), [
            ['str' => 'Dies ist ein Test!', 'context' => null]
        ]);
        $this->assertEquals($this->gtbabel->tokenize('<div><p>Dies ist ein Test!</p><p>Wow!</p></div>'), [
            ['str' => 'Dies ist ein Test!', 'context' => null],
            ['str' => 'Wow!', 'context' => null]
        ]);
        $this->assertEquals(
            $this->gtbabel->tokenize(
                '<p class="footer-copyright">
            ©' .
                    "\t\t\t" .
                    'Vorname' .
                    "\t\t\t" .
                    'Nachname' .
                    '</p>'
            ),
            [
                [
                    'str' => '© Vorname Nachname',
                    'context' => null
                ]
            ]
        );
    }

    public function test_data()
    {
        $this->gtbabel = new Gtbabel();
        $settings = $this->getDefaultSettings();
        $settings['languages'] = ['de', 'en'];
        $settings['debug_translations'] = false;
        $this->gtbabel->start($settings);
        $this->gtbabel->stop();
        $this->gtbabel->reset();

        $settings['auto_translation'] = false;
        $settings['auto_add_translations_to_gettext'] = false;
        $settings['only_show_checked_strings'] = false;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>Haus-en</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationsFromDb(), []);
        $this->gtbabel->reset();

        $settings['auto_translation'] = true;
        $settings['auto_add_translations_to_gettext'] = false;
        $settings['only_show_checked_strings'] = false;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>House</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationsFromDb(), []);
        $this->gtbabel->reset();

        $settings['auto_translation'] = true;
        $settings['auto_add_translations_to_gettext'] = true;
        $settings['only_show_checked_strings'] = false;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>House</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationFromDb('Haus', null, 'en')['trans'] === 'House', true);
        $this->gtbabel->reset();

        $settings['auto_translation'] = true;
        $settings['auto_add_translations_to_gettext'] = true;
        $settings['only_show_checked_strings'] = true;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>Haus</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationFromDb('Haus', null, 'en')['trans'] === 'House', true);

        $this->gtbabel->data->editCheckedValue('Haus', null, 'en', true);

        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>House</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationFromDb('Haus', null, 'en')['checked'] == 1, true);
        $this->gtbabel->reset();
    }

    public function test_file()
    {
        $this->gtbabel = new Gtbabel();
        $settings = $this->getDefaultSettings();
        $settings['languages'] = ['de', 'en'];
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations_to_gettext'] = true;
        $settings['only_show_checked_strings'] = false;

        $input = <<<'EOD'
<div style="background-image: url(http://gtbabel.local.vielhuber.de/datenschutz/beispiel-bilddatei1.jpg);"></div>
<div style="background-image: url(/beispiel-bilddatei2.jpg);"></div>
<div style="background-image: url('beispiel-bilddatei3.jpg');"></div>
<div style="background-image: url('http://test.de/beispiel-bilddatei4.jpg');"></div>
<div style="background-image: url('beispiel-bilddatei1.jpg'), url('beispiel-bilddatei2.jpg');"></div>
<img src="http://test.de/beispiel-bilddatei5.jpg" alt="" />
<img src="http://gtbabel.local.vielhuber.de/datenschutz/beispiel-bilddatei6.jpg" alt="" />
<img src="/beispiel-bilddatei7.jpg" alt="" />
<img src="beispiel-bilddatei8.jpg" alt="" />
<a href="mailto:david@vielhuber.de"></a>
<a href="tel:+4989111312113"></a>
<a href="http://test.de/beispiel-bilddatei9.jpg"></a>
<a href="http://test.de/beispiel-pfad10"></a>
<a href="http://gtbabel.local.vielhuber.de/datenschutz/beispiel-pfad11"></a>
<a href="http://gtbabel.local.vielhuber.de/datenschutz/beispiel-bilddatei12.jpg"></a>
<a href="http://gtbabel.local.vielhuber.de"></a>
<a href="http://gtbabel.local.vielhuber.de/"></a>
<a href="/beispiel-bilddatei13.jpg"></a>
<a href="beispiel-bilddatei14.jpg"></a>
<a href="beispiel-script.php?foo=bar"></a>
<a href="beispiel.html"></a>
<a href="beispiel/pfad/1._Buch_Moses"></a>
EOD;

        $expected_html = <<<'EOD'
<div style="background-image: url(http://gtbabel.local.vielhuber.de/datenschutz/beispiel-bilddatei1_EN.jpg);"></div>
<div style="background-image: url(/beispiel-bilddatei2_EN.jpg);"></div>
<div style="background-image: url('beispiel-bilddatei3_EN.jpg');"></div>
<div style="background-image: url('http://test.de/beispiel-bilddatei4.jpg');"></div>
<div style="background-image: url('beispiel-bilddatei1_EN.jpg'), url('beispiel-bilddatei2_EN.jpg');"></div>
<img src="http://test.de/beispiel-bilddatei5.jpg" alt="">
<img src="http://gtbabel.local.vielhuber.de/datenschutz/beispiel-bilddatei6_EN.jpg" alt="">
<img src="/beispiel-bilddatei7_EN.jpg" alt="">
<img src="beispiel-bilddatei8_EN.jpg" alt="">
<a href="mailto:david@vielhuber.de"></a>
<a href="tel:+4989111312113"></a>
<a href="http://test.de/beispiel-bilddatei9.jpg"></a>
<a href="http://test.de/beispiel-pfad10"></a>
<a href="http://gtbabel.local.vielhuber.de/en/privacy/example-path11"></a>
<a href="http://gtbabel.local.vielhuber.de/datenschutz/beispiel-bilddatei12_EN.jpg"></a>
<a href="http://gtbabel.local.vielhuber.de/en/"></a>
<a href="http://gtbabel.local.vielhuber.de/en/"></a>
<a href="/beispiel-bilddatei13_EN.jpg"></a>
<a href="beispiel-bilddatei14_EN.jpg"></a>
<a href="en/beispiel-script.php?foo=bar"></a>
<a href="en/beispiel.html"></a>
<a href="en/example/path/1-buch-moses"></a>
EOD;

        $expected_data = [
            ['datenschutz', 'slug', 'en', 'privacy', 0],
            ['beispiel-pfad11', 'slug', 'en', 'example-path11', 0],
            ['beispiel', 'slug', 'en', 'example', 0],
            ['pfad', 'slug', 'en', 'path', 0],
            ['1._Buch_Moses', 'slug', 'en', '1-buch-moses', 0],
            ['datenschutz/beispiel-bilddatei1.jpg', 'file', 'en', 'datenschutz/beispiel-bilddatei1_EN.jpg', 1],
            ['beispiel-bilddatei2.jpg', 'file', 'en', 'beispiel-bilddatei2_EN.jpg', 1],
            ['beispiel-bilddatei3.jpg', 'file', 'en', 'beispiel-bilddatei3_EN.jpg', 1],
            ['beispiel-bilddatei1.jpg', 'file', 'en', 'beispiel-bilddatei1_EN.jpg', 1],
            ['datenschutz/beispiel-bilddatei6.jpg', 'file', 'en', 'datenschutz/beispiel-bilddatei6_EN.jpg', 1],
            ['beispiel-bilddatei7.jpg', 'file', 'en', 'beispiel-bilddatei7_EN.jpg', 1],
            ['beispiel-bilddatei8.jpg', 'file', 'en', 'beispiel-bilddatei8_EN.jpg', 1],
            ['datenschutz/beispiel-bilddatei12.jpg', 'file', 'en', 'datenschutz/beispiel-bilddatei12_EN.jpg', 1],
            ['beispiel-bilddatei13.jpg', 'file', 'en', 'beispiel-bilddatei13_EN.jpg', 1],
            ['beispiel-bilddatei14.jpg', 'file', 'en', 'beispiel-bilddatei14_EN.jpg', 1]
        ];

        ob_start();
        $this->gtbabel->start($settings);
        echo $input;
        $this->gtbabel->stop();
        ob_get_contents();
        ob_end_clean();

        $this->gtbabel->data->editTranslation(
            'datenschutz/beispiel-bilddatei1.jpg',
            'file',
            'en',
            'datenschutz/beispiel-bilddatei1_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei2.jpg',
            'file',
            'en',
            'beispiel-bilddatei2_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei3.jpg',
            'file',
            'en',
            'beispiel-bilddatei3_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei1.jpg',
            'file',
            'en',
            'beispiel-bilddatei1_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'datenschutz/beispiel-bilddatei6.jpg',
            'file',
            'en',
            'datenschutz/beispiel-bilddatei6_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei7.jpg',
            'file',
            'en',
            'beispiel-bilddatei7_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei8.jpg',
            'file',
            'en',
            'beispiel-bilddatei8_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'datenschutz/beispiel-bilddatei12.jpg',
            'file',
            'en',
            'datenschutz/beispiel-bilddatei12_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei13.jpg',
            'file',
            'en',
            'beispiel-bilddatei13_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei14.jpg',
            'file',
            'en',
            'beispiel-bilddatei14_EN.jpg',
            true
        );

        ob_start();
        $this->gtbabel->start($settings);
        echo $input;
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals(
            __::minify_html($this->normalize($output)),
            __::minify_html($this->normalize($expected_html))
        );
        $translations = $this->gtbabel->data->getTranslationsFromDb();
        $this->assertEquals(count($translations), count($expected_data));
        foreach ($translations as $translations__key => $translations__value) {
            $match = false;
            foreach ($expected_data as $expected_data__value) {
                if (
                    $translations__value['str'] == $expected_data__value[0] &&
                    $translations__value['context'] == $expected_data__value[1] &&
                    $translations__value['lng'] == $expected_data__value[2] &&
                    $translations__value['trans'] == $expected_data__value[3] &&
                    $translations__value['checked'] == $expected_data__value[4]
                ) {
                    $match = true;
                }
            }
            if ($match === true) {
                $this->assertEquals(true, true);
            } else {
                $this->assertEquals($translations__value, []);
            }
        }

        $this->gtbabel->reset();
    }

    public function test_router()
    {
        $this->gtbabel = new Gtbabel();
        $settings = $this->getDefaultSettings();
        $settings['languages'] = ['de', 'en'];
        $settings['exclude_dom'] = ['.notranslate', '.lngpicker'];
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = null;
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations_to_gettext'] = true;
        $settings['only_show_checked_strings'] = false;

        $settings['prefix_source_lng'] = false;
        $_SERVER['REQUEST_URI'] = '/impressum/';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html><body><div class="lngpicker">';
        foreach ($this->gtbabel->data->getLanguagePickerData() as $lngpicker__value) {
            echo '<a href="' . $lngpicker__value['url'] . '"></a>';
        }
        echo '</div></body></html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(
            strpos(
                $output,
                '<a href="http://gtbabel.local.vielhuber.de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/imprint/"></a>'
            ) !== false,
            true
        );
        $this->gtbabel->reset();

        $settings['prefix_source_lng'] = true;
        $_SERVER['REQUEST_URI'] = '/de/impressum/';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html><body><div class="lngpicker">';
        foreach ($this->gtbabel->data->getLanguagePickerData() as $lngpicker__value) {
            echo '<a href="' . $lngpicker__value['url'] . '"></a>';
        }
        echo '</div></body></html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(
            strpos(
                $output,
                '<a href="http://gtbabel.local.vielhuber.de/de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/imprint/"></a>'
            ) !== false,
            true
        );
        $this->gtbabel->reset();

        $settings['only_show_checked_strings'] = true;
        $_SERVER['REQUEST_URI'] = '/de/impressum/';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html><body><div class="lngpicker">';
        foreach ($this->gtbabel->data->getLanguagePickerData() as $lngpicker__value) {
            echo '<a href="' . $lngpicker__value['url'] . '"></a>';
        }
        echo '</div></body></html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(
            strpos(
                $output,
                '<a href="http://gtbabel.local.vielhuber.de/de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/impressum/"></a>'
            ) !== false,
            true
        );
        $this->gtbabel->reset();

        $settings['only_show_checked_strings'] = true;
        $_SERVER['REQUEST_URI'] = '/en/impressum/';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html><body><div class="lngpicker">';
        foreach ($this->gtbabel->data->getLanguagePickerData() as $lngpicker__value) {
            echo '<a href="' . $lngpicker__value['url'] . '"></a>';
        }
        echo '</div></body></html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(
            strpos(
                $output,
                '<a href="http://gtbabel.local.vielhuber.de/de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/impressum/"></a>'
            ) !== false,
            true
        );
        $this->gtbabel->reset();
    }

    public function getDefaultSettings()
    {
        return [
            'languages' => gtbabel_default_language_codes(),
            'lng_source' => 'de',
            'lng_target' => 'en',
            'database' => [
                'type' => 'sqlite',
                'filename' => './tests/data.db',
                'table' => 'translations'
            ],
            'log_folder' => './tests/logs',
            'prefix_source_lng' => false,
            'redirect_root_domain' => 'browser',
            'debug_translations' => true,
            'auto_add_translations_to_gettext' => false,
            'auto_set_new_strings_checked' => false,
            'only_show_checked_strings' => false,
            'exclude_urls' => null,
            'html_lang_attribute' => false,
            'html_hreflang_tags' => false,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => @$_ENV['GOOGLE_TRANSLATION_API_KEY'],
            'stats_log' => true,
            'discovery_log' => false
        ];
    }

    public function runDiff($filename, $time_max = 0, $overwrite_settings = [])
    {
        $time_begin = microtime(true);

        $this->gtbabel = new Gtbabel();

        // start another output buffer (that does not interfer with gtbabels output buffer)
        ob_start();

        $settings = $this->getDefaultSettings();
        if (!empty($overwrite_settings)) {
            foreach ($overwrite_settings as $overwrite_settings__key => $overwrite_settings__value) {
                $settings[$overwrite_settings__key] = $overwrite_settings__value;
            }
        }

        $this->gtbabel->start($settings);

        require_once __DIR__ . '/files/in/' . $filename;

        $this->gtbabel->stop();

        $html_translated = ob_get_contents();

        ob_end_clean();

        $this->gtbabel->reset();

        $time_end = microtime(true);
        if ($time_max > 0 && $time_end - $time_begin > $time_max / 1000) {
            $this->assertEquals($time_end - $time_begin, $time_max / 1000);
            return;
        }

        if (!file_exists(__DIR__ . '/files/out/' . $filename)) {
            file_put_contents(__DIR__ . '/files/out/' . $filename, $html_translated);
        }

        $html_target = file_get_contents(__DIR__ . '/files/out/' . $filename);

        $extension = mb_substr($filename, mb_strrpos($filename, '.') + 1);

        $debug_filename = __DIR__ . '/files/out/' . str_replace('.' . $extension, '_expected.' . $extension, $filename);

        $passed =
            __::minify_html($this->normalize($html_translated)) === __::minify_html($this->normalize($html_target));

        if ($passed === false) {
            file_put_contents(
                $debug_filename,
                $html_translated .
                    PHP_EOL .
                    PHP_EOL .
                    __::minify_html($this->normalize($html_translated)) .
                    PHP_EOL .
                    PHP_EOL .
                    __::minify_html($this->normalize($html_target))
            );
            // debug output to copy
            echo PHP_EOL . PHP_EOL . '##############################################' . PHP_EOL;
            echo json_encode([
                __::minify_html($this->normalize($html_translated)),
                __::minify_html($this->normalize($html_target))
            ]);
            echo PHP_EOL . '##############################################' . PHP_EOL . PHP_EOL;
            $this->assertTrue(false);
        } else {
            @unlink($debug_filename);
            $this->assertTrue(true);
        }
    }

    public function normalize($str)
    {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\r", "\n", $str);
        $str = trim($str);
        return $str;
    }
}
