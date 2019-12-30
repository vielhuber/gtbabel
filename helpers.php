<?php
use vielhuber\gtbabel\Gtbabel;

function gtbabel_current_lng()
{
    global $gtbabel;
    if ($gtbabel === null) {
        $gtbabel = new Gtbabel();
    }
    return $gtbabel->gettext->getCurrentLng();
}

function gtbabel_languages()
{
    global $gtbabel;
    if ($gtbabel === null) {
        $gtbabel = new Gtbabel();
    }
    return $gtbabel->gettext->getLanguages();
}

function gtbabel_default_languages()
{
    global $gtbabel;
    if ($gtbabel === null) {
        $gtbabel = new Gtbabel();
    }
    return $gtbabel->gettext->getDefaultLanguages();
}

function gtbabel_default_settings($args = [])
{
    global $gtbabel;
    if ($gtbabel === null) {
        $gtbabel = new Gtbabel();
    }
    return $gtbabel->settings->setupSettings($args);
}

function gtbabel_languagepicker()
{
    global $gtbabel;
    if ($gtbabel === null) {
        $gtbabel = new Gtbabel();
    }
    return $gtbabel->gettext->getLanguagePickerData();
}

function gtbabel__($str, $to_lng = null, $from_lng = null)
{
    global $gtbabel;
    if ($gtbabel === null) {
        $gtbabel = new Gtbabel();
    }
    return $gtbabel->gettext->getTranslationInForeignLngAndAddDynamicallyIfNeeded($str, $to_lng, $from_lng);
}
