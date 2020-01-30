<?php
use vielhuber\gtbabel\Gtbabel;
use Dotenv\Dotenv;

class Test extends \PHPUnit\Framework\TestCase
{
    private $gtbabel;

    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();
        $this->gtbabel = new Gtbabel();
    }

    public function testMainFunctionality()
    {
        $this->runDiff('1.html', ['html_lang_attribute' => false, 'html_hreflang_tags' => false]);
        /*
        $this->runDiff('2.php');
        $this->runDiff('3.html', ['lng_target' => 'fr']);
        $this->runDiff('4.html', null, 3000);
        */
    }

    public function getDefaultSettings()
    {
        return [
            'lng_target' => 'en',
            'prefix_source_lng' => false,
            'debug_mode' => true,
            'auto_translation' => false,
            'auto_translation_service' => 'google',
            'google_translation_api_key' => getenv('GOOGLE_TRANSLATION_API_KEY'),
            'exclude_urls' => null
        ];
    }

    public function runDiff($filename, $overwrite_settings = [], $max_time = 0)
    {
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

        $html_target = file_get_contents(__DIR__ . '/files/out/' . $filename);
        $this->assertEquals($this->lineEndingWeakEquals($html_translated, $html_target), true);
    }

    public function lineEndingWeakEquals($str1, $str2)
    {
        if ((!is_string($str1) || !is_string($str2)) && $str1 !== $str2) {
            return false;
        }
        $str1 = str_replace("\r\n", "\n", $str1);
        $str1 = str_replace("\r", "\n", $str1);
        $str2 = str_replace("\r\n", "\n", $str2);
        $str2 = str_replace("\r", "\n", $str2);
        return $str1 === $str2;
    }
}
