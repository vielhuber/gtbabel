<?php
if (!function_exists('gtbabel_current_lng')) {
    function gtbabel_current_lng()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->gettext->getCurrentLanguageCode();
    }
}

if (!function_exists('gtbabel_language_label')) {
    function gtbabel_language_label($lng)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->settings->getLabelForLanguageCode($lng);
    }
}

if (!function_exists('gtbabel_string_check')) {
    function gtbabel_string_check($checked, $str, $lng, $context = null)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->gettext->editCheckedValueFromFiles($checked, $str, $lng, $context);
    }
}

if (!function_exists('gtbabel_languages')) {
    function gtbabel_languages()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->settings->getSelectedLanguageCodes();
    }
}

if (!function_exists('gtbabel_default_language_codes')) {
    function gtbabel_default_language_codes()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->settings->getDefaultLanguageCodes();
    }
}

if (!function_exists('gtbabel_default_languages')) {
    function gtbabel_default_languages()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->settings->getDefaultLanguages();
    }
}

if (!function_exists('gtbabel_default_settings')) {
    function gtbabel_default_settings($args = [])
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->settings->setupSettings($args);
    }
}

if (!function_exists('gtbabel_languagepicker')) {
    function gtbabel_languagepicker()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->gettext->getLanguagePickerData();
    }
}

if (!function_exists('gtbabel__')) {
    function gtbabel__($str, $context = null, $to_lng = null, $from_lng = null)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
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
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->dom->outputJsLocalizationHelper($data);
    }
}
