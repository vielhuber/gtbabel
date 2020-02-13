<?php
use vielhuber\gtbabel\Gtbabel;

if (!function_exists('gtbabel_current_lng')) {
    function gtbabel_current_lng()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getCurrentLng();
    }
}

if (!function_exists('gtbabel_language_label')) {
    function gtbabel_language_label($lng)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getLabelForLanguageCode($lng);
    }
}

if (!function_exists('gtbabel_languages')) {
    function gtbabel_languages()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getSelectedLanguageCodes();
    }
}

if (!function_exists('gtbabel_default_language_codes')) {
    function gtbabel_default_language_codes()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getDefaultLanguageCodes();
    }
}

if (!function_exists('gtbabel_default_languages')) {
    function gtbabel_default_languages()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getDefaultLanguages();
    }
}

if (!function_exists('gtbabel_default_settings')) {
    function gtbabel_default_settings($args = [])
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->settings->setupSettings($args);
    }
}

if (!function_exists('gtbabel_languagepicker')) {
    function gtbabel_languagepicker()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getLanguagePickerData();
    }
}

if (!function_exists('gtbabel__')) {
    function gtbabel__($str, $context = null, $to_lng = null, $from_lng = null)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->gettext->getTranslationInForeignLngAndAddDynamicallyIfNeeded(
            $str,
            $to_lng,
            $from_lng,
            $context
        );
    }
}

if (!function_exists('gtbabel_localize_js')) {
    function gtbabel_localize_js($data)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new Gtbabel();
        }
        return $gtbabel->dom->outputJsLocalizationHelper($data);
    }
}
