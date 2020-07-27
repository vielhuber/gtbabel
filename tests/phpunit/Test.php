<?php
use vielhuber\gtbabel\Gtbabel;
use vielhuber\stringhelper\__;
use Dotenv\Dotenv;

class Test extends \PHPUnit\Framework\TestCase
{
    private $gtbabel;

    protected function setUp(): void
    {
        // load env file
        if (file_exists(dirname(__DIR__, 2) . '/.env')) {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
            $dotenv->load();
        }
        // mock response code
        http_response_code(200);

        // start
        $this->gtbabel = new Gtbabel();
    }

    public function test001()
    {
        $this->runDiff('1.html', 200);
    }

    public function test002()
    {
        $this->runDiff('2.html', 200);
    }

    public function test003()
    {
        $this->runDiff('3.html', 200);
    }

    public function test004()
    {
        $this->runDiff('4.html', 200);
    }

    public function test005()
    {
        $this->runDiff('5.html', 200);
    }

    public function test006()
    {
        $this->runDiff('6.html', 200);
    }

    public function test007()
    {
        $this->runDiff('7.html', 300);
    }

    public function test008()
    {
        $this->runDiff('8.html', 200);
    }

    public function test009()
    {
        $this->runDiff('9.html', 200);
    }

    public function test010()
    {
        $this->runDiff('10.html', 200);
    }

    public function test011()
    {
        $this->runDiff('11.html', 200);
    }

    public function test012()
    {
        $this->runDiff('12.html', 200);
    }

    public function test013()
    {
        $this->runDiff('13.html', 200);
    }

    public function test014()
    {
        $this->runDiff('14.html', 200);
    }

    public function test015()
    {
        $this->runDiff('15.html', 200);
    }

    public function test016()
    {
        $this->runDiff('16.html', 200);
    }

    public function test017()
    {
        $this->runDiff('17.html', 200);
    }

    public function test018()
    {
        $this->runDiff('18.html', 3000);
    }

    public function test019()
    {
        $this->runDiff('19.html', 1500);
    }

    public function test020()
    {
        $this->runDiff('20.html', 200, [
            'languages' => $this->getLanguageSettings([
                ['code' => 'de', 'url_prefix' => ''],
                ['code' => 'en'],
                ['code' => 'fr']
            ]),
            'html_lang_attribute' => true,
            'html_hreflang_tags' => true
        ]);
    }

    public function test021()
    {
        $this->runDiff('21.php', 7500);
    }

    public function test022()
    {
        $this->runDiff('22.php', 3000);
    }

    public function test023()
    {
        $this->runDiff('23.php', 4500);
    }

    public function test024()
    {
        $this->runDiff('24.php', 3500);
    }

    public function test025()
    {
        $this->runDiff('25.html', 3500, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test026()
    {
        $this->runDiff('26.html', 3500, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test027()
    {
        $this->runDiff('27.html', 3500);
    }

    public function test028()
    {
        $this->runDiff('28.html', 200);
    }

    public function test029()
    {
        $this->runDiff('29.json', 200);
    }

    public function test030()
    {
        $this->runDiff('30.json', 200);
    }

    public function test031()
    {
        $this->runDiff('31.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test032()
    {
        $this->runDiff('32.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test033()
    {
        $this->runDiff('33.html', 200);
    }

    public function test034()
    {
        $this->runDiff('34.html', 200);
    }

    public function test035()
    {
        $this->runDiff('35.html', 200);
    }

    public function test036()
    {
        $this->runDiff('36.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test037()
    {
        $this->runDiff('37.html', 200, [
            'exclude_dom' => ['.foo', '#bar']
        ]);
    }

    public function test038()
    {
        $this->runDiff('38.html', 500, [
            'lng_target' => 'ar',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test039()
    {
        $this->runDiff('39.html', 500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'google_translation_api_key' => null
        ]);
    }

    public function test040()
    {
        $this->runDiff('40.html', 500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'google_translation_api_key' => 'free'
        ]);
    }

    public function test041()
    {
        $this->runDiff('41.html', 2000, [
            'languages' => $this->getLanguageSettings([['code' => 'de', 'url_prefix' => 'de']], false),
            'lng_source' => 'en',
            'lng_target' => 'de',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test042()
    {
        $this->runDiff('42.html', 1500, [
            'languages' => $this->getLanguageSettings([['code' => 'de', 'url_prefix' => 'de']], false),
            'lng_source' => 'en',
            'lng_target' => 'de',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test043()
    {
        $this->runDiff('43.html', 200);
    }

    public function test044()
    {
        $this->runDiff('44.html', 200);
    }

    public function test045()
    {
        $this->runDiff('45.html', 200);
    }

    public function test046()
    {
        $this->runDiff('46.html', 200);
    }

    public function test047()
    {
        $this->runDiff('47.html', 200);
    }

    public function test048()
    {
        $this->runDiff('48.html', 1500, [
            'lng_source' => 'en',
            'lng_target' => 'de',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test049()
    {
        $this->runDiff('49.html', 1500, [
            'lng_source' => 'de',
            'lng_target' => 'en',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test050()
    {
        $this->runDiff('50.html', 200);
    }

    public function test051()
    {
        $this->runDiff('51.html', 200);
    }

    public function test052()
    {
        $this->runDiff('52.html', 200);
    }

    public function test053()
    {
        $this->runDiff('53.html', 1500, [
            'languages' => $this->getLanguageSettings([['code' => 'de', 'url_prefix' => 'de']], false),
            'lng_source' => 'de',
            'lng_target' => 'en',
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test054()
    {
        $this->runDiff('54.html', 1500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'google_translation_api_key' => 'free'
        ]);
    }

    public function test_translate()
    {
        $output = $this->gtbabel->translate('<p>Dies ist ein Test!</p>', [
            'lng_source' => 'de',
            'lng_target' => 'en',
            'auto_translation' => true,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
            'log_folder' => './tests/logs'
        ]);
        $this->assertEquals($output, '<p>This is a test!</p>');
        $this->gtbabel->reset();
    }

    public function test_tokenize()
    {
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
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $this->gtbabel->start($settings);
        $this->gtbabel->stop();
        $this->gtbabel->reset();

        $settings['auto_translation'] = false;
        $settings['auto_add_translations'] = false;
        $settings['only_show_checked_strings'] = false;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>Haus-en</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationsFromDatabase(), []);
        $this->assertEquals($this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'], []);
        $this->gtbabel->reset();

        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = false;
        $settings['only_show_checked_strings'] = false;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>House</p>');
        $this->assertEquals($this->gtbabel->data->getTranslationsFromDatabase(), []);
        $this->assertEquals($this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'], []);
        $this->gtbabel->reset();

        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = false;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>House</p>');
        $this->assertEquals(
            $this->gtbabel->data->getTranslationFromDatabase('Haus', null, 'de', 'en')['trans'] === 'House',
            true
        );
        $this->assertEquals($this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'][0]['en'], 'House');
        $this->gtbabel->reset();

        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = true;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>Haus</p>');
        $this->assertEquals(
            $this->gtbabel->data->getTranslationFromDatabase('Haus', null, 'de', 'en')['trans'] === 'House',
            true
        );
        $this->assertEquals($this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'][0]['en'], 'House');

        $this->gtbabel->data->editCheckedValue('Haus', null, 'de', 'en', true);

        ob_start();
        $this->gtbabel->start($settings);
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p>House</p>');
        $this->assertEquals(
            $this->gtbabel->data->getTranslationFromDatabase('Haus', null, 'de', 'en')['checked'] == 1,
            true
        );
        $this->assertEquals($this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'][0]['en_checked'], 1);
        $this->gtbabel->reset();
    }

    public function test_referer_lng()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;

        $_SERVER['HTTP_REFERER'] = 'http://gtbabel.local.vielhuber.de/de/';
        $this->gtbabel->start($settings);
        $this->gtbabel->stop();
        $this->assertEquals($this->gtbabel->host->getRefererLng(), 'de');
        $this->gtbabel->reset();

        $_SERVER['HTTP_REFERER'] = 'http://gtbabel.local.vielhuber.de/en/';
        $this->gtbabel->start($settings);
        $this->gtbabel->stop();
        $this->assertEquals($this->gtbabel->host->getRefererLng(), 'en');
        $this->gtbabel->reset();
    }

    public function test_multiple_sources()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en'], ['code' => 'fr']]);
        $settings['lng_source'] = 'en';
        $settings['lng_target'] = 'de';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = false;

        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="en"><body>
            <p>
                Some content in english.
            </p>
            <div lang="fr">
                Contenu en français.
            </div>
            <p>
                Some other content in english.
            </p>
            <p lang="en">
                Some other content in english.
            </p>
        </body></html>';
        $this->gtbabel->stop();
        ob_end_clean();

        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals($translations[0]['str'], 'Some content in english.');
        $this->assertEquals($translations[0]['lng_source'], 'en');
        $this->assertEquals($translations[0]['lng_target'], 'de');
        $this->assertEquals($translations[0]['trans'], 'Einige Inhalte in Englisch.');
        $this->assertEquals($translations[1]['str'], 'Contenu en français.');
        $this->assertEquals($translations[1]['lng_source'], 'fr');
        $this->assertEquals($translations[1]['lng_target'], 'de');
        $this->assertEquals($translations[1]['trans'], 'Inhalt in Französisch.');
        $this->assertEquals($translations[2]['str'], 'Some other content in english.');
        $this->assertEquals($translations[2]['lng_source'], 'en');
        $this->assertEquals($translations[2]['lng_target'], 'de');
        $this->assertEquals($translations[2]['trans'], 'Einige andere Inhalte in Englisch.');

        $this->gtbabel->reset();
    }

    public function test_gettext()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en'], ['code' => 'fr']]);
        $settings['lng_source'] = 'en';
        $settings['lng_target'] = 'de';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = false;

        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="en"><body>
            <p>
                Some content in english.
            </p>
            <div lang="fr">
                Contenu en français.
            </div>
            <p>
                Some other content in english.
            </p>
            <p lang="en">
                Some other content in english.
            </p>
        </body></html>';
        $this->gtbabel->stop();
        ob_end_clean();

        $data1 = $this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'];
        $files = $this->gtbabel->gettext->export(false);
        $this->gtbabel->gettext->import($files[2], 'en', 'de');
        $this->gtbabel->gettext->import($files[6], 'fr', 'de');
        $data2 = $this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'];
        $this->assertEquals($data1, $data2);
        $this->assertEquals(strpos(file_get_contents($files[2]), 'msgid "Some content in english."') !== false, true);
        $this->assertEquals(
            strpos(file_get_contents($files[2]), 'msgstr "Einige Inhalte in Englisch."') !== false,
            true
        );

        $this->gtbabel->reset();
    }

    public function test_get_translated_chars_by_service()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en'], ['code' => 'fr']]);
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = false;

        $settings['auto_translation_service'] = 'google';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'google');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);

        $this->gtbabel->reset();

        $settings['auto_translation_service'] = 'google';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'google');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = 'microsoft';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'microsoft');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = 'microsoft';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p><p>Other content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'microsoft');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 49);
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = 'deepl';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'deepl');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $settings['auto_translation_service'] = 'google';
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html lang="de"><body><p>Other content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'deepl');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[1]['service'], 'google');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[1]['length'], 25);
        $this->gtbabel->reset();
    }

    public function test_throttling()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = false;

        $settings['auto_translation_service'] = 'google';
        $settings['google_throttle_chars_per_month'] = 30;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html><body><p>Einige Inhalte in Englisch.</p><p>Einige andere Inhalte in Englisch.</p></body></html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame(
            __::minify_html($this->normalize($output)),
            __::minify_html(
                $this->normalize(
                    '<!DOCTYPE html><html><body><p>Some content in English.</p><p>Some other content in English.</p></body></html>'
                )
            )
        );
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = 'google';
        $settings['google_throttle_chars_per_month'] = 20;
        ob_start();
        $this->gtbabel->start($settings);
        echo '<!DOCTYPE html><html><body><p>Einige Inhalte in Englisch.</p><p>Einige andere Inhalte in Englisch.</p></body></html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertSame(
            __::minify_html($this->normalize($output)),
            __::minify_html(
                $this->normalize(
                    '<!DOCTYPE html><html><body><p>Some content in English.</p><p>Einige andere Inhalte in Englisch.</p></body></html>'
                )
            )
        );
        $this->gtbabel->reset();
    }

    public function test_file()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
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
<a href="mailto:david@vielhuber.de_EN"></a>
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
<a href="beispiel.html"></a>
<a href="en/example/path/1-buch-moses"></a>
EOD;

        $expected_data = [
            ['datenschutz', 'slug', 'de', 'en', 'privacy', 0],
            ['beispiel-pfad11', 'slug', 'de', 'en', 'example-path11', 0],
            ['beispiel', 'slug', 'de', 'en', 'example', 0],
            ['pfad', 'slug', 'de', 'en', 'path', 0],
            ['1._Buch_Moses', 'slug', 'de', 'en', '1-buch-moses', 0],
            ['datenschutz/beispiel-bilddatei1.jpg', 'file', 'de', 'en', 'datenschutz/beispiel-bilddatei1_EN.jpg', 1],
            ['beispiel-bilddatei2.jpg', 'file', 'de', 'en', 'beispiel-bilddatei2_EN.jpg', 1],
            ['beispiel-bilddatei3.jpg', 'file', 'de', 'en', 'beispiel-bilddatei3_EN.jpg', 1],
            ['beispiel-bilddatei1.jpg', 'file', 'de', 'en', 'beispiel-bilddatei1_EN.jpg', 1],
            ['datenschutz/beispiel-bilddatei6.jpg', 'file', 'de', 'en', 'datenschutz/beispiel-bilddatei6_EN.jpg', 1],
            ['beispiel-bilddatei7.jpg', 'file', 'de', 'en', 'beispiel-bilddatei7_EN.jpg', 1],
            ['beispiel-bilddatei8.jpg', 'file', 'de', 'en', 'beispiel-bilddatei8_EN.jpg', 1],
            ['david@vielhuber.de', 'email', 'de', 'en', 'david@vielhuber.de_EN', 1],
            ['datenschutz/beispiel-bilddatei12.jpg', 'file', 'de', 'en', 'datenschutz/beispiel-bilddatei12_EN.jpg', 1],
            ['beispiel-bilddatei13.jpg', 'file', 'de', 'en', 'beispiel-bilddatei13_EN.jpg', 1],
            ['beispiel-bilddatei14.jpg', 'file', 'de', 'en', 'beispiel-bilddatei14_EN.jpg', 1]
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
            'de',
            'en',
            'datenschutz/beispiel-bilddatei1_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei2.jpg',
            'file',
            'de',
            'en',
            'beispiel-bilddatei2_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei3.jpg',
            'file',
            'de',
            'en',
            'beispiel-bilddatei3_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei1.jpg',
            'file',
            'de',
            'en',
            'beispiel-bilddatei1_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'datenschutz/beispiel-bilddatei6.jpg',
            'file',
            'de',
            'en',
            'datenschutz/beispiel-bilddatei6_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei7.jpg',
            'file',
            'de',
            'en',
            'beispiel-bilddatei7_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei8.jpg',
            'file',
            'de',
            'en',
            'beispiel-bilddatei8_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation('david@vielhuber.de', 'email', 'de', 'en', 'david@vielhuber.de_EN', true);
        $this->gtbabel->data->editTranslation(
            'datenschutz/beispiel-bilddatei12.jpg',
            'file',
            'de',
            'en',
            'datenschutz/beispiel-bilddatei12_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei13.jpg',
            'file',
            'de',
            'en',
            'beispiel-bilddatei13_EN.jpg',
            true
        );
        $this->gtbabel->data->editTranslation(
            'beispiel-bilddatei14.jpg',
            'file',
            'de',
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
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), count($expected_data));
        foreach ($translations as $translations__value) {
            $match = false;
            foreach ($expected_data as $expected_data__value) {
                if (
                    $translations__value['str'] == $expected_data__value[0] &&
                    $translations__value['context'] == $expected_data__value[1] &&
                    $translations__value['lng_source'] == $expected_data__value[2] &&
                    $translations__value['lng_target'] == $expected_data__value[3] &&
                    $translations__value['trans'] == $expected_data__value[4] &&
                    $translations__value['checked'] == $expected_data__value[5]
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
        $settings = $this->getDefaultSettings();
        $settings['exclude_dom'] = ['.notranslate', '.lngpicker'];
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = null;
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['only_show_checked_strings'] = false;

        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => ''], ['code' => 'en']],
            true
        );
        $this->setHostTo('/impressum/');
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

        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => 'de'], ['code' => 'en']],
            true
        );
        $this->setHostTo('/de/impressum/');
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
        $this->setHostTo('/de/impressum/');
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
        $this->setHostTo('/en/impressum/');
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

        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => 'de'], ['code' => 'en'], ['code' => 'fr']],
            true
        );
        $settings['only_show_checked_strings'] = false;
        $this->setHostTo('/de/impressum/');
        $this->gtbabel->start($settings);
        $this->gtbabel->stop();

        $this->setHostTo('/en/imprint/');
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
                '<a href="http://gtbabel.local.vielhuber.de/de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/imprint/"></a><a href="http://gtbabel.local.vielhuber.de/fr/imprimer/"></a>'
            ) !== false,
            true
        );

        $this->setHostTo('/fr/imprimer/');
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
                '<a href="http://gtbabel.local.vielhuber.de/de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/imprint/"></a><a href="http://gtbabel.local.vielhuber.de/fr/imprimer/"></a>'
            ) !== false,
            true
        );
        $this->gtbabel->reset();

        $settings['only_show_checked_strings'] = true;
        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => ''], ['code' => 'en'], ['code' => 'fr']],
            true
        );
        $this->setHostTo('/impressum/');
        $this->gtbabel->start($settings);
        $this->gtbabel->stop();

        $this->setHostTo('/en/impressum/');
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
                '<a href="http://gtbabel.local.vielhuber.de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/fr/impressum/"></a>'
            ) !== false,
            true
        );

        $this->setHostTo('/fr/impressum/');
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
                '<a href="http://gtbabel.local.vielhuber.de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/fr/impressum/"></a>'
            ) !== false,
            true
        );

        $this->gtbabel->data->editCheckedValue('impressum', 'slug', 'de', 'en', true);

        $this->setHostTo('/en/imprint/');
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
                '<a href="http://gtbabel.local.vielhuber.de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/imprint/"></a><a href="http://gtbabel.local.vielhuber.de/fr/impressum/"></a>'
            ) !== false,
            true
        );

        $this->gtbabel->data->editCheckedValue('impressum', 'slug', 'de', 'fr', true);

        $this->setHostTo('/fr/imprimer/');
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
                '<a href="http://gtbabel.local.vielhuber.de/impressum/"></a><a href="http://gtbabel.local.vielhuber.de/en/imprint/"></a><a href="http://gtbabel.local.vielhuber.de/fr/imprimer/"></a>'
            ) !== false,
            true
        );

        $this->gtbabel->reset();
    }

    public function getLanguageSettings($overwrite = [], $unset_others = true)
    {
        $languages = $this->gtbabel->settings->getDefaultLanguages();
        foreach ($languages as $languages__key => $languages__value) {
            $found = false;
            foreach ($overwrite as $overwrite__value) {
                if ($languages__value['code'] === $overwrite__value['code']) {
                    foreach ($overwrite__value as $overwrite__value__key => $overwrite__value__value) {
                        $languages[$languages__key][$overwrite__value__key] = $overwrite__value__value;
                    }
                    $found = true;
                    break;
                }
            }
            if ($found === false && $unset_others === true) {
                unset($languages[$languages__key]);
            }
        }
        $languages = array_values($languages);
        return $languages;
    }

    public function getDefaultSettings()
    {
        return [
            'languages' => $this->getLanguageSettings([['code' => 'de', 'url_prefix' => '']], false),
            'lng_source' => 'de',
            'lng_target' => 'en',
            'database' => [
                'type' => 'sqlite',
                'filename' => './tests/data.db',
                'table' => 'translations'
            ],
            'log_folder' => './tests/logs',
            'redirect_root_domain' => 'browser',
            'translate_html' => true,
            'translate_json' => true,
            'debug_translations' => true,
            'auto_add_translations' => false,
            'auto_set_new_strings_checked' => false,
            'auto_set_discovered_strings_checked' => false,
            'only_show_checked_strings' => false,
            'exclude_urls' => null,
            'html_lang_attribute' => false,
            'html_hreflang_tags' => false,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
            'microsoft_translation_api_key' => @$_SERVER['MICROSOFT_TRANSLATION_API_KEY'],
            'deepl_translation_api_key' => @$_SERVER['DEEPL_TRANSLATION_API_KEY'],
            'google_throttle_chars_per_month' => 1000000,
            'microsoft_throttle_chars_per_month' => 1000000,
            'deepl_throttle_chars_per_month' => 1000000,
            'discovery_log' => false,
            'localize_js' => false,
            'detect_dom_changes' => false
        ];
    }

    public function runDiff($filename, $time_max = 0, $overwrite_settings = [])
    {
        $time_begin = microtime(true);

        // start another output buffer (that does not interfer with gtbabels output buffer)
        ob_start();

        $settings = $this->getDefaultSettings();
        if (!empty($overwrite_settings)) {
            foreach ($overwrite_settings as $overwrite_settings__key => $overwrite_settings__value) {
                $settings[$overwrite_settings__key] = $overwrite_settings__value;
            }
        }

        $this->setHostTo($settings['lng_target']);

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

    public function setHostTo($lng_target)
    {
        if ($lng_target !== null) {
            $_SERVER['REQUEST_URI'] = '/' . trim($lng_target, '/') . '/';
        }
    }
}
