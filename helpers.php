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
