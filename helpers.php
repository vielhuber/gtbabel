<?php
if (!function_exists('gtbabel_current_lng')) {
    function gtbabel_current_lng()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->data->getCurrentLanguageCode();
    }
}

if (!function_exists('gtbabel_source_lng')) {
    function gtbabel_source_lng()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->settings->getSourceLanguageCode();
    }
}

if (!function_exists('gtbabel_referer_lng')) {
    function gtbabel_referer_lng()
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->host->getRefererLanguageCode();
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
        return $gtbabel->data->getLanguagePickerData();
    }
}

if (!function_exists('gtbabel__')) {
    function gtbabel__($str, $lng_target = null, $lng_source = null, $context = null)
    {
        global $gtbabel;
        if ($gtbabel === null) {
            $gtbabel = new \vielhuber\gtbabel\Gtbabel();
        }
        return $gtbabel->translate($str, $lng_target, $lng_source, $context);
    }
}
