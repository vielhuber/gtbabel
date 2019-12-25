<?php
function gtbabel_current_lng()
{
    global $gtbabel;
    return $gtbabel->gettext->getCurrentLng();
}

function gtbabel_languages()
{
    global $gtbabel;
    return $gtbabel->gettext->getLanguages();
}

function gtbabel_languagepicker()
{
    global $gtbabel;
    return $gtbabel->gettext->getLanguagePickerData();
}

function gtbabel_get_translation($str, $to_lng, $from_lng = null)
{
    global $gtbabel;
    return $gtbabel->gettext->getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng,
        $from_lng
    );
}
