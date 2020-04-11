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
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
        // reset server environment (this is changed in magic router)
        $this->server_orig = $_SERVER;
        // mock response code
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server_orig;
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
        $this->runDiff('19.html', 900);
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
        $this->runDiff('21.php', 3500);
    }

    public function test22()
    {
        $this->runDiff('22.php', 2000);
    }

    public function test23()
    {
        $this->runDiff('23.php', 4000);
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
        $this->runDiff('31.html', 500, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test32()
    {
        $this->runDiff('32.html', 500, [
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
        $this->runDiff('36.html', 500, [
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

    public function test_translate()
    {
        $this->gtbabel = new Gtbabel();
        $output = $this->gtbabel->translate('<p>Dies ist ein Test!</p>', [
            'lng_source' => 'de',
            'lng_target' => 'en',
            'auto_translation' => true,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => getenv('GOOGLE_TRANSLATION_API_KEY')
        ]);
        $this->assertEquals($output, '<p>This is a test!</p>');
    }

    public function test_tokenize()
    {
        $this->gtbabel = new Gtbabel();
        $this->assertEquals($this->gtbabel->tokenize('<p>Dies ist ein Test!</p>'), [
            ['string' => 'Dies ist ein Test!', 'context' => null]
        ]);
        $this->assertEquals($this->gtbabel->tokenize('<div><p>Dies ist ein Test!</p><p>1</p></div>'), [
            ['string' => 'Dies ist ein Test!', 'context' => null]
        ]);
        $this->assertEquals($this->gtbabel->tokenize('<div><p>Dies ist ein Test!</p><p>Wow!</p></div>'), [
            ['string' => 'Dies ist ein Test!', 'context' => null],
            ['string' => 'Wow!', 'context' => null]
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
                    'string' => '© Vorname Nachname',
                    'context' => null
                ]
            ]
        );
    }

    public function getDefaultSettings()
    {
        return [
            'lng_source' => 'de',
            'lng_target' => 'en',
            'prefix_source_lng' => false,
            'redirect_root_domain' => 'browser',
            'debug_translations' => true,
            'auto_add_translations_to_gettext' => false,
            'auto_add_added_date_to_gettext' => true,
            'exclude_urls' => null,
            'html_lang_attribute' => false,
            'html_hreflang_tags' => false,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => getenv('GOOGLE_TRANSLATION_API_KEY'),
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
        } else {
            @unlink($debug_filename);
        }

        $this->assertEquals($passed, true);
    }

    public function normalize($str)
    {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\r", "\n", $str);
        return $str;
    }
}
