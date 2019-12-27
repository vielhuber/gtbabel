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

function gtbabel__($str, $to_lng = null, $from_lng = null)
{
    global $gtbabel;
    return $gtbabel->gettext->getTranslationInForeignLngAndAddDynamicallyIfNeeded(
        $str,
        $to_lng,
        $from_lng
    );
}
