<?php
function gtbabel_current_lng()
{
    global $gtbabel;
    return $gtbabel->getCurrentLng();
}

function gtbabel_languages()
{
    global $gtbabel;
    return $gtbabel->getLanguages();
}

function gtbabel_languagepicker()
{
    global $gtbabel;
    return $gtbabel->getLanguagePickerData();
}

function gtbabel_get_translation($str, $to_lng, $from_lng = null)
{
    global $gtbabel;
    return $gtbabel->getTranslationInForeignLngAndAddDynamicallyIfNeeded($str, $to_lng, $from_lng);
}
