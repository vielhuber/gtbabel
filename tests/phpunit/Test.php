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

        // start (without mock)
        if (1 == 0) {
            $this->gtbabel = new Gtbabel();
        } else {
            $this->mock();
        }
    }

    public function mock()
    {
        $utils = $this->getMockBuilder(vielhuber\gtbabel\Utils::class)
            ->setConstructorArgs([])
            ->onlyMethods([])
            ->getMock();
        $settings = $this->getMockBuilder(vielhuber\gtbabel\Settings::class)
            ->setConstructorArgs([$utils])
            ->onlyMethods([])
            ->getMock();
        $log = $this->getMockBuilder(vielhuber\gtbabel\Log::class)
            ->setConstructorArgs([$utils, $settings])
            ->onlyMethods([])
            ->getMock();
        $tags = $this->getMockBuilder(vielhuber\gtbabel\Tags::class)
            ->setConstructorArgs([$utils, $settings, $log])
            ->onlyMethods([])
            ->getMock();
        $host = $this->getMockBuilder(vielhuber\gtbabel\Host::class)
            ->setConstructorArgs([$settings, $log])
            ->onlyMethods([])
            ->getMock();
        $data = $this->getMockBuilder(vielhuber\gtbabel\Data::class)
            ->setConstructorArgs([$utils, $host, $settings, $tags, $log])
            ->onlyMethods([])
            ->getMock();
        $grabber = $this->getMockBuilder(vielhuber\gtbabel\Grabber::class)
            ->setConstructorArgs([$settings, $utils, $log, $data])
            ->onlyMethods([])
            ->getMock();
        $domfactory = $this->getMockBuilder(vielhuber\gtbabel\DomFactory::class)
            ->setConstructorArgs([$utils, $data, $host, $settings, $tags, $log])
            ->onlyMethods([])
            ->getMock();
        $router = $this->getMockBuilder(vielhuber\gtbabel\Router::class)
            ->setConstructorArgs([$data, $host, $settings, $log, $utils])
            ->onlyMethods(['redirect'])
            ->getMock();
        $router
            ->expects($this->any())
            ->method('redirect')
            ->will(
                $this->returnCallback(function ($url, $status_code) {
                    throw new \Exception($url);
                })
            );
        $gettext = $this->getMockBuilder(vielhuber\gtbabel\Gettext::class)
            ->setConstructorArgs([$data, $settings])
            ->onlyMethods([])
            ->getMock();
        $excel = $this->getMockBuilder(vielhuber\gtbabel\Excel::class)
            ->setConstructorArgs([$data, $settings])
            ->onlyMethods([])
            ->getMock();

        $this->gtbabel = $this->getMockBuilder(vielhuber\gtbabel\Gtbabel::class)
            ->setConstructorArgs([
                $utils,
                $settings,
                $log,
                $tags,
                $host,
                $data,
                $grabber,
                $domfactory,
                $router,
                $gettext,
                $excel
            ])
            ->onlyMethods([])
            ->getMock();
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
        $this->runDiff('11.html', 200, [
            'translate_html_include' => array_merge($this->gtbabel->settings->getDefaultTranslateHtmlInclude(), [
                [
                    'selector' => '.search-submit',
                    'attribute' => 'value'
                ],
                [
                    'selector' => '.js-link',
                    'attribute' => 'alt-href',
                    'context' => 'slug'
                ]
            ])
        ]);
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
        $this->runDiff('19.html', 2500);
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
        $this->runDiff('22.php', 8500);
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
        $this->runDiff(
            '29.json',
            200,
            [
                'translate_json' => false
            ],
            '/en/blog'
        );
    }

    public function test030()
    {
        $this->runDiff(
            '30.json',
            200,
            [
                'translate_json' => true,
                'translate_json_include' => [['url' => '/blog', 'selector' => ['das.ist.*.ein']]]
            ],
            '/en/blog'
        );
    }

    public function test031()
    {
        $this->runDiff('31.html', 1250, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test032()
    {
        $this->runDiff('32.html', 1250, [
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
        $this->runDiff('36.html', 1250, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test037()
    {
        $this->runDiff('37.html', 200, [
            'translate_html_exclude' => [
                ['selector' => '.foo'],
                ['selector' => '#bar'],
                ['selector' => '.gnarr', 'attribute' => 'data-text'],
                ['selector' => '[data-text-foo]', 'attribute' => 'data-text-f*'],
                ['selector' => '[class="gnaf"]', 'attribute' => '*']
            ]
        ]);
    }

    public function test038()
    {
        $this->runDiff('38.html', 750, [
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
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => null,
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ]
        ]);
    }

    public function test040()
    {
        $this->runDiff('40.html', 750, [
            'debug_translations' => false,
            'auto_translation' => true,
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => 'free',
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ]
        ]);
    }

    public function test041()
    {
        $this->runDiff('41.html', 4500, [
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
        $this->runDiff('48.html', 1750, [
            'lng_source' => 'en',
            'lng_target' => 'de',
            'debug_translations' => false,
            'auto_translation' => true,
            'html_lang_attribute' => true
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
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => 'free',
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ]
        ]);
    }

    public function test055()
    {
        $this->runDiff('55.html', 200, [
            'translate_html_force_tokenize' => [
                ['selector' => '.postponded__date'],
                ['selector' => '.canceled__note > *']
            ]
        ]);
    }

    public function test056()
    {
        $this->runDiff('56.html', 1500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => 'free',
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ]
        ]);
    }

    public function test057()
    {
        $this->runDiff(
            '57.json',
            200,
            [
                'translate_json' => true,
                'translate_json_include' => [['url' => '/blog', 'selector' => ['data.html']]]
            ],
            '/en/blog'
        );
    }

    public function test058()
    {
        $this->runDiff('58.html');
    }

    public function test059()
    {
        $this->runDiff('59.html', 2250, [
            'debug_translations' => false,
            'auto_translation' => true
        ]);
    }

    public function test060()
    {
        $this->runDiff('60.html');
    }

    public function test061()
    {
        $this->runDiff('61.html');
    }

    public function test062()
    {
        $this->runDiff('62.html');
    }

    public function test063()
    {
        $this->runDiff('63.html');
    }

    public function test064()
    {
        $this->runDiff('64.html', 1500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => 'free',
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ]
        ]);
    }

    public function test065()
    {
        $this->runDiff('65.html');
    }

    public function test066()
    {
        $this->runDiff('66.html');
    }

    public function test067()
    {
        $this->runDiff('67.html', 200, [
            'translate_html_include' => array_merge($this->gtbabel->settings->getDefaultTranslateHtmlInclude(), [
                [
                    'selector' => 'custom-component',
                    'attribute' => ':product-*|:url'
                ]
            ])
        ]);
    }

    public function test068()
    {
        $this->runDiff('68.html', 1500, [
            'debug_translations' => false,
            'auto_translation' => true,
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => 'free',
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ],
            'translate_html_exclude' => [['selector' => '.test']]
        ]);
    }

    public function test069()
    {
        $this->runDiff('69.xml');
    }

    public function test070()
    {
        $this->runDiff('70.xml');
    }

    public function test071()
    {
        $this->runDiff('71.xml');
    }

    public function test072()
    {
        $this->runDiff('72.xml');
    }

    public function test073()
    {
        $this->runDiff('73.xml', 1500, [
            'xml_hreflang_tags' => true
        ]);
    }

    public function test074()
    {
        $this->runDiff('74.xml', 200, [
            'languages' => $this->getLanguageSettings([['code' => 'de', 'url_prefix' => ''], ['code' => 'en']]),
            'xml_hreflang_tags' => true
        ]);
    }

    public function test075()
    {
        $_SERVER['CONTENT_TYPE'] = 'text/css';
        $this->runDiff('75.css');
        $_SERVER['CONTENT_TYPE'] = null;
    }

    public function test076()
    {
        $this->runDiff('76.css');
    }

    public function test077()
    {
        $this->runDiff(
            '77.json',
            200,
            [
                'translate_json' => true,
                'translate_json_include' => [['url' => '/', 'selector' => ['link']]]
            ],
            '/en/'
        );
    }

    public function test078()
    {
        $this->runDiff('78.html', 1500, [
            'lng_source' => 'de',
            'lng_target' => 'en'
        ]);
    }

    public function test079()
    {
        $this->runDiff('79.html', 1500, [
            'lng_source' => 'de',
            'lng_target' => 'de'
        ]);
    }

    public function test080()
    {
        $this->runDiff('80.html', 1500, [
            'lng_source' => 'de',
            'lng_target' => 'es'
        ]);
    }

    public function test081()
    {
        $this->runDiff('81.html', 200, [
            'translate_html_include' => array_merge($this->gtbabel->settings->getDefaultTranslateHtmlInclude(), [
                [
                    'selector' => '.elementor-widget-video',
                    'attribute' => 'data-settings'
                ]
            ]),
            'translate_json' => true,
            'translate_json_include' => [['url' => '*', 'selector' => ['youtube_url']]]
        ]);
    }

    public function test_string_detection()
    {
        $should_translate = ['Haus', 'Sending...', 'ब्लॉग'];
        $should_not_translate = [
            '351',
            '351ADBU...',
            '350EPU-xxx.002',
            '351ADBU_xxx-key',
            '_TZM2042',
            '951PTO',
            '951PTO_xxx_xxx',
            '951PTO16',
            'PTO191',
            '0,083333333',
            '209KS19D',
            'B06_xxx_xxx_6498_2048',
            'btn--scheme-w',
            '|',
            'a)',
            '7)',
            '*',
            '•',
            '●',
            '(',
            ']',
            '.foo',
            '.foo .bar',
            '.foo-bar .bar-baz',
            'Array ([0] => Array ([0] => '
        ];
        foreach ($should_translate as $should_translate__value) {
            $this->assertEquals(
                $should_translate__value,
                $should_translate__value .
                    ($this->gtbabel->data->stringShouldNotBeTranslated($should_translate__value) === true
                        ? '_FAIL'
                        : '')
            );
        }
        foreach ($should_not_translate as $should_not_translate__value) {
            $this->assertEquals(
                $should_not_translate__value,
                $should_not_translate__value .
                    ($this->gtbabel->data->stringShouldNotBeTranslated($should_not_translate__value) === false
                        ? '_FAIL'
                        : '')
            );
        }
    }

    public function test_translate()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en'], ['code' => 'fr']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $this->gtbabel->config($settings);

        // basic
        $output = $this->gtbabel->translate('<p>Dies ist ein Test!</p>');
        $this->assertEquals($output, '<p>This is a test!</p>');
        $this->gtbabel->reset();

        // inline
        $this->gtbabel->config($settings);
        ob_start();
        $this->gtbabel->start();
        echo '<div class="translate">';
        echo 'Hund';
        echo '</div>';
        echo '<div class="notranslate">';
        echo $this->gtbabel->translate('Maison', 'en', 'fr');
        echo $this->gtbabel->translate('Haus', 'en', 'de');
        echo $this->gtbabel->translate('House', 'de', 'en');
        echo '</div>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<div class="translate">Dog</div><div class="notranslate">HouseHouseHaus</div>');
        $this->gtbabel->reset();

        // specific
        $this->gtbabel->config($settings);
        $this->assertEquals($this->gtbabel->translate('Hund'), 'Dog');
        $this->assertEquals($this->gtbabel->translate('Hund', 'en', 'de'), 'Dog');
        $this->assertEquals(
            $this->gtbabel->translate('<p>Hallo <a href="/fisch">Fisch</a>!</p>'),
            '<p>Hello <a href="/en/fish">fish</a> !</p>'
        );
        $this->assertEquals($this->gtbabel->translate('Fisch'), 'Fish');
        $this->assertEquals($this->gtbabel->translate('/fisch'), '/en/fish');
        $this->assertEquals($this->gtbabel->translate('/hund/haus/eimer'), '/en/dog/house/bucket');
        $this->assertEquals(
            $this->gtbabel->translate('http://gtbabel.local.vielhuber.de/katze/mund'),
            'http://gtbabel.local.vielhuber.de/en/cat/mouth'
        );

        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 9);
        $this->assertEquals($translations[0]['str'], 'Hund');
        $this->assertEquals($translations[0]['context'], '');
        $this->assertEquals($translations[1]['str'], 'Hallo <a>Fisch</a>!');
        $this->assertEquals($translations[1]['context'], '');
        $this->assertEquals($translations[2]['str'], 'fisch');
        $this->assertEquals($translations[2]['context'], 'slug');
        $this->assertEquals($translations[3]['str'], 'Fisch');
        $this->assertEquals($translations[3]['context'], '');
        $this->assertEquals($translations[4]['str'], 'hund');
        $this->assertEquals($translations[4]['context'], 'slug');
        $this->assertEquals($translations[5]['str'], 'haus');
        $this->assertEquals($translations[5]['context'], 'slug');
        $this->assertEquals($translations[6]['str'], 'eimer');
        $this->assertEquals($translations[6]['context'], 'slug');
        $this->assertEquals($translations[7]['str'], 'katze');
        $this->assertEquals($translations[7]['context'], 'slug');
        $this->assertEquals($translations[8]['str'], 'mund');
        $this->assertEquals($translations[8]['context'], 'slug');
        $this->gtbabel->reset();

        $this->gtbabel->config($settings);
        $this->assertEquals($this->gtbabel->translate('fisch'), 'fish');
        $this->assertEquals($this->gtbabel->translate('fisch', null, null, 'slug'), 'en/fish');
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 2);
        $this->assertEquals($translations[0]['str'], 'fisch');
        $this->assertEquals($translations[0]['context'], '');
        $this->assertEquals($translations[1]['str'], 'fisch');
        $this->assertEquals($translations[1]['context'], 'slug');
        $this->gtbabel->reset();

        $this->gtbabel->config($settings);
        $this->assertEquals($this->gtbabel->translate('Hund', null, null, null), 'Dog');
        $this->assertEquals($this->gtbabel->translate('Haus', null, null, null, false), 'House');
        $this->assertEquals($this->gtbabel->translate('Wasser', null, null, null, true), 'Water');
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 2);
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        $this->gtbabel->stop();
        $this->gtbabel->reset();

        $settings['auto_translation'] = false;
        $settings['auto_add_translations'] = false;
        $settings['unchecked_strings'] = 'trans';
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $settings['unchecked_strings'] = 'trans';
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $settings['unchecked_strings'] = 'trans';
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $settings['unchecked_strings'] = 'source';
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'hide';
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($output, '<p></p>');
        $this->assertEquals(
            $this->gtbabel->data->getTranslationFromDatabase('Haus', null, 'de', 'en')['trans'] === 'House',
            true
        );
        $this->assertEquals($this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'][0]['en'], 'House');

        $this->gtbabel->data->editCheckedValue('Haus', null, 'de', 'en', true);

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

    public function test_empty_dom_els()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<h2 class="section__hl h3">Test <span class="icon--bf icon--chevron-down"></span> Test</h2>';
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->gtbabel->reset();
        $this->assertEquals(count($translations), 1);
        $this->assertEquals($translations[0]['str'], 'Test <span></span> Test');
    }

    public function test_duplicates()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = false;
        $settings['auto_add_translations'] = true;

        // two identical strings are added in subsequent sessions (at the second call nothing is translated)
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 1);
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>Haus</p>';
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 1);
        $this->gtbabel->reset();

        // now we force concurrency and test, if duplicates are correctly prevented
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>Haus</p>';
        $this->gtbabel->data->db->insert($this->gtbabel->data->table, [
            'str' => 'Haus',
            'context' => '',
            'lng_source' => 'de',
            'lng_target' => 'en',
            'trans' => 'Haus-en',
            'added' => $this->gtbabel->utils->getCurrentTime(),
            'checked' => 1,
            'shared' => 1
        ]);
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 1);
        $this->gtbabel->reset();

        // lowercase/uppercase
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>Xing</p>';
        echo '<p>XING</p>';
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 2);
        $this->gtbabel->reset();
    }

    public function test_inline_links()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = false;
        $settings['auto_add_translations'] = true;

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>Dies ist ein Link: https://test.de</p>';
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 2);
        $this->assertEquals($translations[0]['str'], 'https://test.de');
        $this->assertEquals($translations[1]['str'], 'Dies ist ein Link: {1}');
        $this->gtbabel->reset();

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<p>https://test.de</p>';
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 1);
        $this->assertEquals($translations[0]['str'], 'https://test.de');
        $this->gtbabel->reset();
    }

    public function test_stopwords()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['exclude_stopwords'] = ['Haus'];

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<!DOCTYPE html>
<html>
    <head>
        <title>Ein Review von Haus</title>
        <meta name="description" content="Ich lese gerade Haus.">
    </head>
    <body>
        <a href="#" title="Haus">Der Link https://test.de handelt vom dem Stoppword Haus und mehr findet man auf https://test2.de.</a>
    </body>
</html>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->gtbabel->reset();

        $this->assertEquals(
            __::minify_html($this->normalize($output)),
            __::minify_html(
                $this->normalize('<!DOCTYPE html>
<html>
    <head>
        <title>A review by Haus</title>
        <meta name="description" content="I\'m reading Haus right now.">
    </head>
    <body>
        <a href="#" title="Haus">The link https://test.de is about the stop word Haus and more can be found on https://test2.de.</a>
    </body>
</html>')
            )
        );

        $this->assertEquals(count($translations), 5);
        $this->assertEquals($translations[0]['str'], 'https://test.de');
        $this->assertEquals($translations[1]['str'], 'https://test2.de');
        $this->assertEquals(
            $translations[2]['str'],
            'Der Link {1} handelt vom dem Stoppword {3} und mehr findet man auf {2}.'
        );
        $this->assertEquals($translations[3]['str'], 'Ein Review von {1}');
        $this->assertEquals($translations[4]['str'], 'Ich lese gerade {1}.');
    }

    public function test_inline_html_tags()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;

        $data = [
            ['<p>Dies ist ein Test</p>', '<p>This is a test</p>', 'Dies ist ein Test', 'This is a test'],
            [
                '<p>Dies ist ein <strong>Test</strong></p>',
                '<p>This is a <strong>test</strong></p>',
                'Dies ist ein <strong>Test</strong>',
                'This is a <strong>test</strong>'
            ],
            [
                '<p>Dies ist ein <strong data-foo="bar" data-bar="baz">Test</strong></p>',
                '<p>This is a <strong data-foo="bar" data-bar="baz">test</strong></p>',
                'Dies ist ein <strong>Test</strong>',
                'This is a <strong>test</strong>'
            ],
            [
                '<p>Das deutsche <a href="#test1" target="_self">Brot</a> <a href="#test2" target="_blank">vermisse</a> ich am meisten.</p>',
                '<p><a href="#test2" target="_blank">I miss</a> German <a href="#test1" target="_self">bread</a> the most.</p>',
                'Das deutsche <a>Brot</a> <a>vermisse</a> ich am meisten.',
                '<a p="2">I miss</a> German <a p="1">bread</a> the most.'
            ],
            [
                '<p>Das deutsche <strong data-foo="bar">Brot</strong> <a href="#test2" target="_blank">vermisse</a> ich am meisten.</p>',
                '<p><a href="#test2" target="_blank">I miss</a> German <strong data-foo="bar">bread</strong> the most.</p>',
                'Das deutsche <strong>Brot</strong> <a>vermisse</a> ich am meisten.',
                '<a>I miss</a> German <strong>bread</strong> the most.'
            ],
            [
                '<p>Das deutsche <strong data-foo="bar">Brot</strong> <a href="#test1" target="_blank">vermisse</a> <a href="#test2" target="_self">ich</a> am <small style="font-size:bold;">meisten</small></p>',
                '<p><a href="#test2" target="_self">I</a> <a href="#test1" target="_blank">miss</a> German <strong data-foo="bar">bread</strong> the <small style="font-size:bold;">most</small></p>',
                'Das deutsche <strong>Brot</strong> <a>vermisse</a> <a>ich</a> am <small>meisten</small>',
                '<a p="2">I</a> <a p="1">miss</a> German <strong>bread</strong> the <small>most</small>'
            ],
            [
                '<p><small class="_1">Haus</small> <span class="_2">Maus</span> Haus <u class="_4">Maus</u> <em class="_5">Haus</em></p>',
                '<p><small class="_1">House</small> <span class="_2">mouse</span> house <u class="_4">mouse</u> <em class="_5">house</em></p>',
                '<small>Haus</small> <span>Maus</span> Haus <u>Maus</u> <em>Haus</em>',
                '<small>House</small> <span>mouse</span> house <u>mouse</u> <em>house</em>'
            ],
            [
                '<p><small class="_1">Haus</small> <span class="_2">Maus</span> Haus <small class="_4">Maus</small> <span class="_5">Haus</span></p>',
                '<p><small class="_1">House</small> <span class="_2">mouse</span> house <small class="_4">mouse</small> <span class="_5">house</span></p>',
                '<small>Haus</small> <span>Maus</span> Haus <small>Maus</small> <span>Haus</span>',
                '<small>House</small> <span>mouse</span> house <small>mouse</small> <span>house</span>'
            ],
            [
                '<p>Das deutsche <strong data-foo="bar" class="notranslate">Brot</strong> <a href="#test1" target="_blank">vermisse</a> <a href="#test2" target="_self">ich</a> am <small style="font-size:bold;">meisten</small></p>',
                '<p><a href="#test2" target="_self">I</a> <a href="#test1" target="_blank">miss</a> German <strong data-foo="bar" class="notranslate">Brot</strong> the <small style="font-size:bold;">most</small></p>',
                'Das deutsche <strong class="notranslate">Brot</strong> <a>vermisse</a> <a>ich</a> am <small>meisten</small>',
                '<a p="2">I</a> <a p="1">miss</a> German <strong class="notranslate">Brot</strong> the <small>most</small>'
            ]
        ];

        foreach ($data as $data__value) {
            ob_start();
            $this->gtbabel->config($settings);
            $this->gtbabel->start();
            echo $data__value[0];
            $this->gtbabel->stop();
            $output = ob_get_contents();
            ob_end_clean();
            $translations = $this->gtbabel->data->getTranslationsFromDatabase();
            $this->gtbabel->reset();
            $this->assertEquals($output, $data__value[1]);
            if (count($translations) > 1) {
                __o($translations);
            }
            $this->assertEquals(count($translations), 1);
            $this->assertEquals($translations[0]['str'], $data__value[2]);
            $this->assertEquals($translations[0]['trans'], $data__value[3]);
        }
    }

    public function test_encoding()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        // #allesfürdich is encoded, #allesfürdich is not encoded
        // however, gtbabel does not add two entries to avoid confusion
        echo '<div data-text="#allesfürdich">#allesfürdich</div>';
        echo '<p>foo &amp; bar<br/>baz</p>';
        // this is also tricky: domdocument converts the double quotes around the attribute to single quotes!
        echo '<div data-text="' . htmlentities('"gnarr" & gnazz') . '"></div>';
        // this should be untouched
        echo '<a href="https://www.url.com/foo.php?lang=de&amp;foo=bar"></a>';
        // this should all be encoded
        echo '<img src="" alt="Erster &amp; Test" data-text="Zweiter &amp; Test"></div>';
        echo '<img src="" alt="Erster & Test" data-text="Zweiter & Test"></div>';
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->gtbabel->reset();
        $this->assertEquals(
            $output,
            '<div data-text="#anything for you">#anything for you</div>' .
                '<p>foo &amp; bar<br> baz</p>' .
                '<div data-text=\'"gnarr" &amp; gnazz\'></div>' .
                '<a href="https://www.url.com/foo.php?lang=de&amp;foo=bar"></a>' .
                '<img src="" alt="First &amp; test" data-text="Second &amp; test">' .
                '<img src="" alt="First &amp; test" data-text="Second &amp; test">'
        );
        $this->assertEquals(count($translations), 6);
        $this->assertEquals($translations[0]['str'], '#allesfürdich');
        $this->assertEquals($translations[1]['str'], 'foo &amp; bar<br>baz');
        $this->assertEquals($translations[2]['str'], 'https://www.url.com/foo.php?lang=de&amp;foo=bar');
        $this->assertEquals($translations[3]['str'], 'Erster &amp; Test');
        $this->assertEquals($translations[4]['str'], '"gnarr" &amp; gnazz');
        $this->assertEquals($translations[5]['str'], 'Zweiter &amp; Test');
    }

    public function test_referer_lng()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['debug_translations'] = false;

        $_SERVER['HTTP_REFERER'] = 'http://gtbabel.local.vielhuber.de/de/';
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        $this->gtbabel->stop();
        $this->assertEquals($this->gtbabel->host->getRefererLanguageCode(), 'de');
        $this->gtbabel->reset();

        $_SERVER['HTTP_REFERER'] = 'http://gtbabel.local.vielhuber.de/en/';
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        $this->gtbabel->stop();
        $this->assertEquals($this->gtbabel->host->getRefererLanguageCode(), 'en');
        $this->gtbabel->reset();
    }

    public function test_url_query_args()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'trans';
        $settings['url_query_args'] = [
            [
                'type' => 'keep',
                'selector' => '*'
            ],
            [
                'type' => 'translate',
                'selector' => 'foo'
            ],
            [
                'type' => 'discard',
                'selector' => 'bar'
            ]
        ];

        $html_in = '';
        $html_in .=
            '<a href="/suche?foo=' .
            urlencode('Coole Sache') .
            '&amp;bar=' .
            urlencode('Das funktioniert') .
            '&amp;baz=' .
            urlencode('Richtig gut') .
            '">hier</a>';
        $html_in .=
            '<p>http://gtbabel.local.vielhuber.de/suche?foo=' .
            urlencode('Coole Sache') .
            '&bar=' .
            urlencode('Das funktioniert') .
            '&baz=' .
            urlencode('Richtig gut') .
            '</p>';
        $html_in .=
            '<a href="/suche?foo=' .
            urlencode('Coole Sache') .
            '&amp;bar=' .
            urlencode('Das funktioniert') .
            '&amp;baz=' .
            urlencode('Richtig gut') .
            '#some-hash-after">hier</a>';

        $html_out = '';
        $html_out .=
            '<a href="/en/search?foo=' .
            urlencode('Cool thing') .
            '&amp;baz=' .
            urlencode('Richtig gut') .
            '">here</a>';
        $html_out .=
            '<p>http://gtbabel.local.vielhuber.de/en/search?foo=' .
            urlencode('Cool thing') .
            '&amp;baz=' .
            urlencode('Richtig gut') .
            '</p>';
        $html_out .=
            '<a href="/en/search?foo=' .
            urlencode('Cool thing') .
            '&amp;baz=' .
            urlencode('Richtig gut') .
            '#some-hash-after">here</a>';

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html_in;
        $this->gtbabel->stop();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($output, $html_out);

        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->assertEquals(count($translations), 3);
        $this->assertEquals($translations[0]['str'], 'hier');
        $this->assertEquals($translations[0]['trans'], 'here');
        $this->assertEquals($translations[1]['str'], 'Coole Sache');
        $this->assertEquals($translations[1]['trans'], 'Cool thing');
        $this->assertEquals($translations[2]['str'], 'suche');
        $this->assertEquals($translations[2]['trans'], 'search');

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
        $settings['unchecked_strings'] = 'trans';

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

    public function test_translate_missing()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en'], ['code' => 'fr']]);
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'trans';

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo 'Dies ist ein Test';
        $this->gtbabel->stop();
        ob_end_clean();

        $data1 = $this->gtbabel->data->getGroupedTranslationsFromDatabase();
        $this->gtbabel->data->translateMissing();
        $data2 = $this->gtbabel->data->getGroupedTranslationsFromDatabase();

        $this->assertEquals(isset($data1['data'][0]['fr']), false);
        $this->assertEquals(isset($data2['data'][0]['fr']), true);

        $this->gtbabel->reset();
    }

    public function test_exportimport()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en'], ['code' => 'fr']]);
        $settings['lng_source'] = 'en';
        $settings['lng_target'] = 'de';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'trans';

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

        $data1 = $this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'];
        $files = $this->gtbabel->excel->export(false);
        $this->gtbabel->excel->import($files[0], 'en', 'de');
        $this->gtbabel->excel->import($files[2], 'fr', 'de');
        $data2 = $this->gtbabel->data->getGroupedTranslationsFromDatabase()['data'];
        $this->assertEquals($data1, $data2);
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
        $settings['unchecked_strings'] = 'trans';

        $settings['auto_translation_service'] = [
            [
                'provider' => 'google',
                'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 1000000,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'google');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);

        $this->gtbabel->reset();

        $settings['auto_translation_service'] = [
            [
                'provider' => 'google',
                'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 1000000,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'google');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = [
            [
                'provider' => 'microsoft',
                'api_keys' => @$_SERVER['MICROSOFT_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 1000000,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'microsoft');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = [
            [
                'provider' => 'microsoft',
                'api_keys' => @$_SERVER['MICROSOFT_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 1000000,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p><p>Other content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'microsoft');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 49);
        $this->gtbabel->reset();

        $settings['auto_translation_service'] = [
            [
                'provider' => 'deepl',
                'api_keys' => @$_SERVER['DEEPL_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 1000000,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo '<!DOCTYPE html><html lang="de"><body><p>Some content in english.</p></body></html>';
        $this->gtbabel->stop();
        ob_end_clean();
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['service'], 'deepl');
        $this->assertSame($this->gtbabel->data->statsGetTranslatedCharsByService()[0]['length'], 24);
        $settings['auto_translation_service'] = [
            [
                'provider' => 'google',
                'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 1000000,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $settings['unchecked_strings'] = 'trans';

        $settings['auto_translation_service'] = [
            [
                'provider' => 'google',
                'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 30,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

        $settings['auto_translation_service'] = [
            [
                'provider' => 'google',
                'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
                'throttle_chars_per_month' => 20,
                'lng' => null,
                'label' => null,
                'api_url' => null,
                'disabled' => false
            ]
        ];
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

    public function test_file_url()
    {
        $settings = $this->getDefaultSettings();
        $settings['languages'] = $this->getLanguageSettings([['code' => 'de'], ['code' => 'en']]);
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = 'en';
        $this->setHostTo($settings['lng_target']);
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'trans';

        $input = <<<'EOD'
<div style="background-image: url(http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei1.jpg);"></div>
<div style="background-image: url('http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei1.jpg');"></div>
<div style="background-image:    url(    'http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei1.jpg' )"></div>
<div style="background-image: url(/beispiel-bilddatei2.jpg);"></div>
<div style="background-image: url('beispiel-bilddatei3.jpg');"></div>
<div style="background-image: url('http://test.de/beispiel-bilddatei4.jpg');"></div>
<div style="background-image: url('beispiel-bilddatei1.jpg'), url('beispiel-bilddatei2.jpg');"></div>
<div style="width: 20%;"></div>
<img src="http://test.de/beispiel-bilddatei5.jpg" alt="" />
<img src="http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei6.jpg" alt="" />
<img src="/beispiel-bilddatei7.jpg" alt="" />
<img src="beispiel-bilddatei8.jpg" alt="" />
<img srcset="http://gtbabel.local.vielhuber.de/320x100.png?text=small,
             http://gtbabel.local.vielhuber.de/600x100.png?text=medium 600w,
             http://gtbabel.local.vielhuber.de/900x100.png?text=large 2x"
    src="http://gtbabel.local.vielhuber.de/900x200.png?text=fallback"
    alt=""
/>
<img srcset="http://gtbabel.local.vielhuber.de/320x100.png?text=small,
             http://test.de/600x100.png?text=medium 600w,
             http://gtbabel.local.vielhuber.de/900x100.png?text=large 2x"
    src="http://test.de/900x200.png?text=fallback"
    alt=""
/>
<a href="mailto:"></a>
<a href="mailto:david@vielhuber.de"></a>
<a href="mailto:david@vielhuber.de?subject=Haus&amp;body=Dies%20ist%20ein%20Test"></a>
<a href="mailto:david@vielhuber.de?subject=Haus&amp;body=Dies%20ist%20ein%20Link%20http%3A%2F%2Fgtbabel.local.vielhuber.de%2Ffisch"></a>
<a href="mailto:?subject=Haus&amp;body=http%3A%2F%2Fgtbabel.local.vielhuber.de%2Ffisch%2F"></a>
<a href="tel:+4989111312113"></a>
<a href="http://test.de/beispiel-bilddatei9.jpg"></a>
<a href="http://test.de/beispiel-pfad10"></a>
<a href="http://gtbabel.local.vielhuber.de/fisch/beispiel-pfad11"></a>
<a href="http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei12.jpg"></a>
<a href="http://gtbabel.local.vielhuber.de"></a>
<a href="http://gtbabel.local.vielhuber.de/"></a>
<a href="/beispiel-bilddatei13.jpg"></a>
<a href="beispiel-bilddatei14.jpg"></a>
<a href="beispiel-script.php?foo=bar"></a>
<a href="beispiel.html"></a>
<a href="beispiel/pfad/1._Buch_Moses"></a>
<a href="beispiel/pfad/1._Buch_Moses?Hund=Haus"></a>
<a href="beispiel/pfad/1._Buch_Moses/?Hund=Haus"></a>
<a href="https://lighthouse-dot-webdotdevsite.appspot.com/lh/html?url=http://gtbabel.local.vielhuber.de"></a>
<iframe src="https://www.youtube.com/watch?v=4t1mgEBx1nQ"></iframe>
<a href="https://www.google.de/maps/dir//foo,bar,baz/gnarr,gnaz,gnab/data=!3m1!4b1!4m9!4m8!1m0!1m5!1m1!1s0x479de2be93111c6d:0x2bac2afe506c9fa9!2m2!1d11.8208515!2d48.0923082!3e0">#</a>
EOD;

        $expected_html = <<<'EOD'
<div style="background-image: url(http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei1_EN.jpg);"></div>
<div style="background-image: url('http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei1_EN.jpg');"></div>
<div style="background-image: url( 'http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei1_EN.jpg' )"></div>
<div style="background-image: url(/beispiel-bilddatei2_EN.jpg);"></div>
<div style="background-image: url('beispiel-bilddatei3_EN.jpg');"></div>
<div style="background-image: url('http://test.de/beispiel-bilddatei4.jpg');"></div>
<div style="background-image: url('beispiel-bilddatei1_EN.jpg'), url('beispiel-bilddatei2_EN.jpg');"></div>
<div style="width: 20%;"></div>
<img src="http://test.de/beispiel-bilddatei5.jpg" alt="">
<img src="http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei6_EN.jpg" alt="">
<img src="/beispiel-bilddatei7_EN.jpg" alt="">
<img src="beispiel-bilddatei8_EN.jpg" alt="">
<img srcset="http://gtbabel.local.vielhuber.de/320x100_EN.png?text=small, http://gtbabel.local.vielhuber.de/600x100_EN.png?text=medium 600w, http://gtbabel.local.vielhuber.de/900x100_EN.png?text=large 2x" src="http://gtbabel.local.vielhuber.de/900x200_EN.png?text=fallback" alt="" />
<img srcset="http://gtbabel.local.vielhuber.de/320x100_EN.png?text=small, http://test.de/600x100.png?text=medium 600w, http://gtbabel.local.vielhuber.de/900x100_EN.png?text=large 2x" src="http://test.de/900x200.png?text=fallback" alt="" />
<a href="mailto:"></a>
<a href="mailto:david@vielhuber.de_EN"></a>
<a href="mailto:david@vielhuber.de_EN?subject=House&amp;body=This%20is%20a%20test"></a>
<a href="mailto:david@vielhuber.de_EN?subject=House&amp;body=This%20is%20a%20link%20http://gtbabel.local.vielhuber.de/en/fish"></a>
<a href="mailto:?subject=House&amp;body=http://gtbabel.local.vielhuber.de/en/fish/"></a>
<a href="tel:+4989111312113"></a>
<a href="http://test.de/beispiel-bilddatei9.jpg"></a>
<a href="http://test.de/beispiel-pfad10"></a>
<a href="http://gtbabel.local.vielhuber.de/en/fish/example-path11"></a>
<a href="http://gtbabel.local.vielhuber.de/fisch/beispiel-bilddatei12_EN.jpg"></a>
<a href="http://gtbabel.local.vielhuber.de/en/"></a>
<a href="http://gtbabel.local.vielhuber.de/en/"></a>
<a href="/beispiel-bilddatei13_EN.jpg"></a>
<a href="beispiel-bilddatei14_EN.jpg"></a>
<a href="beispiel-script.php?foo=bar"></a>
<a href="beispiel.html"></a>
<a href="en/example/path/1-book-moses"></a>
<a href="en/example/path/1-book-moses?Hund=Haus"></a>
<a href="en/example/path/1-book-moses/?Hund=Haus"></a>
<a href="https://lighthouse-dot-webdotdevsite.appspot.com/lh/html?url=http://gtbabel.local.vielhuber.de"></a>
<iframe src="https://www.youtube.com/watch?v=sZhl6PyTflw"></iframe>
<a href="https://www.google.de/maps/dir//foo,bar,baz/gnarr,gnaz,gnab/data=!3m1!4b1!4m9!4m8!1m0!1m5!1m1!1s0x479de2be93111c6d:0x2bac2afe506c9fa9!2m2!1d11.8208515!2d48.0923082!3e0">#</a>
EOD;

        $expected_data = [
            ['fisch', 'slug', 'de', 'en', 'fish', 0],
            ['beispiel-pfad11', 'slug', 'de', 'en', 'example-path11', 0],
            ['beispiel', 'slug', 'de', 'en', 'example', 0],
            ['pfad', 'slug', 'de', 'en', 'path', 0],
            ['1._Buch_Moses', 'slug', 'de', 'en', '1-book-moses', 0],
            ['fisch/beispiel-bilddatei1.jpg', 'file', 'de', 'en', 'fisch/beispiel-bilddatei1_EN.jpg', 1],
            ['beispiel-bilddatei2.jpg', 'file', 'de', 'en', 'beispiel-bilddatei2_EN.jpg', 1],
            ['beispiel-bilddatei3.jpg', 'file', 'de', 'en', 'beispiel-bilddatei3_EN.jpg', 1],
            ['beispiel-bilddatei1.jpg', 'file', 'de', 'en', 'beispiel-bilddatei1_EN.jpg', 1],
            ['fisch/beispiel-bilddatei6.jpg', 'file', 'de', 'en', 'fisch/beispiel-bilddatei6_EN.jpg', 1],
            ['beispiel-bilddatei7.jpg', 'file', 'de', 'en', 'beispiel-bilddatei7_EN.jpg', 1],
            ['beispiel-bilddatei8.jpg', 'file', 'de', 'en', 'beispiel-bilddatei8_EN.jpg', 1],
            ['320x100.png?text=small', 'file', 'de', 'en', '320x100_EN.png?text=small', 1],
            ['600x100.png?text=medium', 'file', 'de', 'en', '600x100_EN.png?text=medium', 1],
            ['900x100.png?text=large', 'file', 'de', 'en', '900x100_EN.png?text=large', 1],
            ['900x200.png?text=fallback', 'file', 'de', 'en', '900x200_EN.png?text=fallback', 1],
            ['david@vielhuber.de', 'email', 'de', 'en', 'david@vielhuber.de_EN', 1],
            ['Haus', null, 'de', 'en', 'House', 0],
            ['Dies ist ein Test', null, 'de', 'en', 'This is a test', 0],
            ['Dies ist ein Link {1}', null, 'de', 'en', 'This is a link {1}', 0],
            ['fisch/beispiel-bilddatei12.jpg', 'file', 'de', 'en', 'fisch/beispiel-bilddatei12_EN.jpg', 1],
            ['beispiel-bilddatei13.jpg', 'file', 'de', 'en', 'beispiel-bilddatei13_EN.jpg', 1],
            ['beispiel-bilddatei14.jpg', 'file', 'de', 'en', 'beispiel-bilddatei14_EN.jpg', 1],
            ['http://test.de/beispiel-pfad10', 'url', 'de', 'en', 'http://test.de/beispiel-pfad10', 0],
            ['http://test.de/beispiel-bilddatei4.jpg', 'file', 'de', 'en', 'http://test.de/beispiel-bilddatei4.jpg', 0],
            ['http://test.de/beispiel-bilddatei5.jpg', 'file', 'de', 'en', 'http://test.de/beispiel-bilddatei5.jpg', 0],
            ['http://test.de/beispiel-bilddatei9.jpg', 'file', 'de', 'en', 'http://test.de/beispiel-bilddatei9.jpg', 0],
            ['tel:+4989111312113', 'url', 'de', 'en', 'tel:+4989111312113', 0],
            [
                'https://lighthouse-dot-webdotdevsite.appspot.com/lh/html?url=http://gtbabel.local.vielhuber.de',
                'url',
                'de',
                'en',
                'https://lighthouse-dot-webdotdevsite.appspot.com/lh/html?url=http://gtbabel.local.vielhuber.de',
                0
            ],
            ['http://test.de/600x100.png?text=medium', 'file', 'de', 'en', 'http://test.de/600x100.png?text=medium', 0],
            [
                'http://test.de/900x200.png?text=fallback',
                'file',
                'de',
                'en',
                'http://test.de/900x200.png?text=fallback',
                0
            ],
            [
                'https://www.youtube.com/watch?v=4t1mgEBx1nQ',
                'url',
                'de',
                'en',
                'https://www.youtube.com/watch?v=sZhl6PyTflw',
                1
            ],
            [
                'https://www.google.de/maps/dir//foo,bar,baz/gnarr,gnaz,gnab/data=!3m1!4b1!4m9!4m8!1m0!1m5!1m1!1s0x479de2be93111c6d:0x2bac2afe506c9fa9!2m2!1d11.8208515!2d48.0923082!3e0',
                'url',
                'de',
                'en',
                'https://www.google.de/maps/dir//foo,bar,baz/gnarr,gnaz,gnab/data=!3m1!4b1!4m9!4m8!1m0!1m5!1m1!1s0x479de2be93111c6d:0x2bac2afe506c9fa9!2m2!1d11.8208515!2d48.0923082!3e0',
                0
            ]
        ];

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $input;
        $this->gtbabel->stop();
        ob_get_contents();
        ob_end_clean();

        $this->gtbabel->data->editTranslation(
            'fisch/beispiel-bilddatei1.jpg',
            'file',
            'de',
            'en',
            'fisch/beispiel-bilddatei1_EN.jpg',
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
            'fisch/beispiel-bilddatei6.jpg',
            'file',
            'de',
            'en',
            'fisch/beispiel-bilddatei6_EN.jpg',
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
        $this->gtbabel->data->editTranslation(
            '320x100.png?text=small',
            'file',
            'de',
            'en',
            '320x100_EN.png?text=small',
            true
        );
        $this->gtbabel->data->editTranslation(
            '600x100.png?text=medium',
            'file',
            'de',
            'en',
            '600x100_EN.png?text=medium',
            true
        );
        $this->gtbabel->data->editTranslation(
            '900x100.png?text=large',
            'file',
            'de',
            'en',
            '900x100_EN.png?text=large',
            true
        );
        $this->gtbabel->data->editTranslation(
            '900x200.png?text=fallback',
            'file',
            'de',
            'en',
            '900x200_EN.png?text=fallback',
            true
        );
        $this->gtbabel->data->editTranslation('david@vielhuber.de', 'email', 'de', 'en', 'david@vielhuber.de_EN', true);
        $this->gtbabel->data->editTranslation(
            'fisch/beispiel-bilddatei12.jpg',
            'file',
            'de',
            'en',
            'fisch/beispiel-bilddatei12_EN.jpg',
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
        $this->gtbabel->data->editTranslation(
            'https://www.youtube.com/watch?v=4t1mgEBx1nQ',
            'url',
            'de',
            'en',
            'https://www.youtube.com/watch?v=sZhl6PyTflw',
            true
        );

        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

    public function test_exclude_urls()
    {
        $settings = $this->getDefaultSettings();
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = null;
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'trans';
        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => ''], ['code' => 'en']],
            true
        );

        $html = '<!DOCTYPE html><html><body>Der Inhalt</body></html>';

        $settings['exclude_urls_content'] = [];
        $settings['exclude_urls_slugs'] = [];
        $this->setHostTo('/haus/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html;
        $this->gtbabel->stop();
        ob_end_clean();
        $this->setHostTo('/en/house/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html;
        $path = $_SERVER['REQUEST_URI'];
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->gtbabel->reset();
        $this->assertEquals(count($translations), 2);
        $this->assertEquals($translations[0]['str'], 'haus');
        $this->assertEquals($translations[1]['str'], 'Der Inhalt');
        $this->assertEquals($path, '/haus');

        $settings['exclude_urls_content'] = [['url' => 'house']];
        $settings['exclude_urls_slugs'] = [];
        $this->setHostTo('/haus/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html;
        $this->gtbabel->stop();
        ob_end_clean();
        $this->setHostTo('/en/house/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html;
        $path = $_SERVER['REQUEST_URI'];
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->gtbabel->reset();
        $this->assertEquals(count($translations), 1);
        $this->assertEquals($translations[0]['str'], 'haus');
        $this->assertEquals($path, '/en/house/');

        $settings['exclude_urls_content'] = [];
        $settings['exclude_urls_slugs'] = [['url' => 'house']];
        $this->setHostTo('/haus/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html;
        $this->gtbabel->stop();
        ob_end_clean();
        $this->setHostTo('/en/house/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        echo $html;
        $path = $_SERVER['REQUEST_URI'];
        $this->gtbabel->stop();
        ob_end_clean();
        $translations = $this->gtbabel->data->getTranslationsFromDatabase();
        $this->gtbabel->reset();
        $this->assertEquals(count($translations), 2);
        $this->assertEquals($translations[0]['str'], 'haus');
        $this->assertEquals($translations[1]['str'], 'Der Inhalt');
        $this->assertEquals($path, '/house');
    }

    public function test_redirects()
    {
        $data = [
            [
                'http://gtbabel.local.vielhuber.de/',
                'http://gtbabel.local.vielhuber.de/de/',
                ['de', ['de', null, 'en', null], 'source', false, null, null]
            ],
            [
                'http://gtbabel.local.vielhuber.de/',
                'http://gtbabel.local.vielhuber.de/german/',
                ['de', ['german', null, 'english', null], 'source', false, null, null]
            ],
            ['http://gtbabel.local.vielhuber.de/', null, ['de', ['', null, 'en', null], 'source', false, null, null]],
            [
                'http://gtbabel.local.vielhuber.de/',
                'http://gtbabel.local.vielhuber.de/en/',
                ['en', ['de', null, 'en', null], 'source', false, null, null]
            ],
            [
                'http://gtbabel.local.vielhuber.de/dies/ist/ein/test/',
                'http://gtbabel.local.vielhuber.de/de/dies/ist/ein/test/',
                ['de', ['de', null, 'en', null], 'source', false, null, null]
            ],
            [
                'http://gtbabel-de.local.vielhuber.de/',
                'http://gtbabel-en.local.vielhuber.de/en/',
                [
                    'en',
                    ['de', 'http://gtbabel-de.local.vielhuber.de', 'en', 'http://gtbabel-en.local.vielhuber.de'],
                    'source',
                    false,
                    null,
                    null
                ]
            ],
            [
                'http://gtbabel.local.vielhuber.de/',
                'http://gtbabel.local.vielhuber.de/?lang=en',
                [
                    'en',
                    ['', 'http://gtbabel.local.vielhuber.de', '', 'http://gtbabel.local.vielhuber.de'],
                    'source',
                    false,
                    null,
                    null
                ]
            ],
            [
                'http://gtbabel.local.vielhuber.de/test/',
                'http://gtbabel.local.vielhuber.de/test/?lang=en',
                [
                    'en',
                    ['', 'http://gtbabel.local.vielhuber.de', '', 'http://gtbabel.local.vielhuber.de'],
                    'source',
                    false,
                    null,
                    null
                ]
            ],
            [
                'http://gtbabel.local.vielhuber.de/?ajax=foo',
                'http://gtbabel.local.vielhuber.de/en/?ajax=foo',
                ['en', ['de', null, 'en', null], 'source', true, 'http://gtbabel.local.vielhuber.de/en/', null]
            ],
            [
                'http://gtbabel.local.vielhuber.de/test',
                'http://gtbabel.local.vielhuber.de/test/',
                ['de', ['', null, 'en', null], 'source', false, null, null]
            ],
            [
                'http://gtbabel.local.vielhuber.de/test',
                'http://gtbabel.local.vielhuber.de/test/',
                ['de', ['', null, 'en', null], 'source', false, null, null]
            ],
            [
                'http://gtbabel.local.vielhuber.de/test',
                'http://gtbabel.local.vielhuber.de/de/test/',
                ['de', ['de', null, 'en', null], 'source', false, null, null]
            ],
            [
                'http://gtbabel.local.vielhuber.de/en/',
                'http://gtbabel.local.vielhuber.de/de/',
                ['de', ['de', null, 'en', null], 'source', false, null, ['en']]
            ],
            [
                'http://gtbabel.local.vielhuber.de/en/test/',
                'http://gtbabel.local.vielhuber.de/de/',
                ['de', ['de', null, 'en', null], 'source', false, null, ['en']]
            ]
        ];

        foreach ($data as $data__value) {
            try {
                $this->setUrlTo($data__value[0]);
                $settings = $this->getDefaultSettings();
                $settings['lng_target'] = null;
                $settings['debug_translations'] = true;
                $settings['auto_translation'] = false;
                $settings['auto_add_translations'] = true;
                $settings['lng_source'] = $data__value[2][0];
                $settings['languages'] = $this->getLanguageSettings(
                    [
                        [
                            'code' => 'de',
                            'url_prefix' => $data__value[2][1][0],
                            'url_base' => $data__value[2][1][1],
                            'hidden' => $data__value[2][5] !== null && in_array('de', $data__value[2][5])
                        ],
                        [
                            'code' => 'en',
                            'url_prefix' => $data__value[2][1][2],
                            'url_base' => $data__value[2][1][3],
                            'hidden' => $data__value[2][5] !== null && in_array('en', $data__value[2][5])
                        ]
                    ],
                    true
                );
                $settings['redirect_root_domain'] = $data__value[2][2];
                $this->setAjaxRequest($data__value[2][3]);
                if ($data__value[2][4] !== null) {
                    $this->setRefererTo($data__value[2][4]);
                }
                $this->gtbabel->config($settings);
                $this->gtbabel->start();
                $this->gtbabel->stop();
                $this->gtbabel->reset();
                if ($data__value[1] === null) {
                    $this->assertEquals(true, true);
                } else {
                    $this->assertEquals(true, false);
                }
            } catch (\Exception $e) {
                $this->assertEquals($e->getMessage(), $data__value[1]);
            }
        }
    }

    public function test_router()
    {
        $settings = $this->getDefaultSettings();
        $settings['translate_html_exclude'] = [
            ['selector' => '.notranslate'],
            ['selector' => '[data-context]', 'attribute' => 'data-context'],
            ['selector' => '.lngpicker'],
            ['selector' => '.xdebug-error'],
            ['selector' => '.example1', 'attribute' => 'data-text'],
            ['selector' => '.example2', 'attribute' => 'data-*']
        ];
        $settings['lng_source'] = 'de';
        $settings['lng_target'] = null;
        $settings['debug_translations'] = false;
        $settings['auto_translation'] = true;
        $settings['auto_add_translations'] = true;
        $settings['unchecked_strings'] = 'trans';

        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => ''], ['code' => 'en']],
            true
        );
        $this->setHostTo('/impressum/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

        $settings['unchecked_strings'] = 'source';
        $this->setHostTo('/de/impressum/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

        $settings['unchecked_strings'] = 'source';
        $this->setHostTo('/en/impressum/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $settings['unchecked_strings'] = 'trans';
        $this->setHostTo('/de/impressum/');
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        $this->gtbabel->stop();

        $this->setHostTo('/en/imprint/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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

        $settings['unchecked_strings'] = 'source';
        $settings['languages'] = $this->getLanguageSettings(
            [['code' => 'de', 'url_prefix' => ''], ['code' => 'en'], ['code' => 'fr']],
            true
        );
        $this->setHostTo('/impressum/');
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
        $this->gtbabel->stop();

        $this->setHostTo('/en/impressum/');
        ob_start();
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
        $this->gtbabel->config($settings);
        $this->gtbabel->start();
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
            'basic_auth' => null,
            'translate_html' => true,
            'translate_xml' => true,
            'translate_xml_include' => [
                [
                    'selector' => '//*[name()=\'loc\']',
                    'attribute' => null,
                    'context' => 'slug',
                    'comment' => 'Sitemap links'
                ]
            ],
            'translate_json' => true,
            'translate_json_include' => [
                ['url' => '/path/in/source/lng/to/specific/page', 'selector' => ['key']],
                ['url' => 'wp-json/v1/*/endpoint', 'selector' => ['key', 'nested.key', 'key.with.*.wildcard']]
            ],
            'translate_wp_localize_script' => true,
            'translate_wp_localize_script_include' => [
                ['selector' => 'key1_*.key2.*', 'comment' => 'Example'],
                ['selector' => 'key3_*.key4', 'comment' => 'Example']
            ],
            'debug_translations' => true,
            'auto_add_translations' => true,
            'auto_set_new_strings_checked' => false,
            'auto_set_discovered_strings_checked' => false,
            'unchecked_strings' => 'trans',
            'url_query_args' => [
                [
                    'type' => 'keep',
                    'selector' => '*'
                ],
                [
                    'type' => 'discard',
                    'selector' => 'nonce'
                ]
            ],
            'exclude_urls_content' => null,
            'exclude_urls_slugs' => null,
            'exclude_stopwords' => null,
            'html_lang_attribute' => false,
            'html_hreflang_tags' => false,
            'xml_hreflang_tags' => false,
            'show_language_picker' => false,
            'show_frontend_editor_links' => false,
            'auto_translation' => false,
            'auto_translation_service' => [
                [
                    'provider' => 'google',
                    'api_keys' => @$_SERVER['GOOGLE_TRANSLATION_API_KEY'],
                    'throttle_chars_per_month' => 1000000,
                    'lng' => null,
                    'label' => null,
                    'api_url' => null,
                    'disabled' => false
                ]
            ],
            'discovery_log' => false,
            'localize_js' => false,
            'detect_dom_changes' => false,
            'frontend_editor' => false,
            'wp_mail_notifications' => false
        ];
    }

    public function runDiff($filename, $time_max = 0, $overwrite_settings = [], $specific_host = null)
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

        if ($specific_host === null) {
            $specific_host = $settings['lng_target'];
        }
        $this->setHostTo($specific_host);

        $this->gtbabel->config($settings);
        $this->gtbabel->start();

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
            file_put_contents($debug_filename, $html_translated);
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
        if ($lng_target === '' || $lng_target === '/') {
            $_SERVER['REQUEST_URI'] = '/';
        } elseif ($lng_target !== null) {
            $_SERVER['REQUEST_URI'] = '/' . trim($lng_target, '/') . '/';
        }
    }

    public function setUrlTo($url)
    {
        $url = str_replace(['http://', 'https://'], '', $url);
        if (strpos($url, '/') !== false) {
            $path = substr($url, strpos($url, '/'));
            $domain = substr($url, 0, strpos($url, '/'));
        } else {
            $domain = $url;
            $path = '';
        }
        $_SERVER['HTTP_HOST'] = $domain;
        $_SERVER['REQUEST_URI'] = $path;
    }

    public function setRefererTo($url)
    {
        $_SERVER['HTTP_REFERER'] = $url;
    }

    public function setAjaxRequest($bool)
    {
        if ($bool === true) {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        } else {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            }
        }
    }
}
