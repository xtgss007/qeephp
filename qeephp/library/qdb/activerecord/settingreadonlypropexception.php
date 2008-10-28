<?php

class QDB_ActiveRecord_SettingReadonlyPropException extends QDB_ActiveRecord_Exception
{
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->prop_name = $prop_name;
        // LC_MSG: Setting readonly property "%s" on object "%s" instance.
        parent::__construct($class_name, __('Setting readonly property "%s" on object "%s" instance.', $prop_name, $class_name));
    }
}

