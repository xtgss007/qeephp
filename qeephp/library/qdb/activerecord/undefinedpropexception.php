<?php

class QDB_ActiveRecord_UndefinedPropException extends QException
{
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->prop_name = $prop_name;
        // LC_MSG: Undefined property "%s" on object "%s" instance.
        parent::__construct($class_name, __('Undefined property "%s" on object "%s" instance.', $prop_name, $class_name));
    }
}

