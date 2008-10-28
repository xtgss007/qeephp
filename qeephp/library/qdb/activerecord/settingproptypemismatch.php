<?php

class QDB_ActiveRecord_SettingPropTypeMismatch extends QDB_ActiveRecord_Exception
{
    public $prop_name;
    public $expected_type;
    public $actual_type;

    function __construct($class_name, $prop_name, $expected_type, $actual_type)
    {
        $this->prop_name = $prop_name;
        $this->expected_type = $expected_type;
        $this->actual_type = $actual_type;
        // LC_MSG: Setting property "%s" type mismatch on object "%s" instance. Expected type is "%s", actual is "%s".
        parent::__construct($class_name, __('Setting property "%s" type mismatch on object "%s" instance. Expected type is "%s", actual is "%s".'), $prop_name, $class_name, $expected_type, $actual_type);
    }
}

