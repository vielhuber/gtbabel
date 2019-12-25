<?php
namespace vielhuber\gtbabel;

class Settings
{
    public $args;

    function set($args)
    {
        $this->args = (object) $args;
    }

    function get($prop)
    {
        return $this->args->{$prop};
    }

    function shouldBeResetted()
    {
        if (@$_GET['gtbabel_reset'] == 1) {
            return true;
        }
        return false;
    }
}
