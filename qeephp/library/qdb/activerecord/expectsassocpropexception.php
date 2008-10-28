<?php

class QDB_ActiveRecord_ExpectsAssocPropException extends QDB_ActiveRecord_Exception
{
    public $prop_name;

    function __construct($class_name, $prop_name)
    {
        $this->prop_name = $prop_name;
        // LC_MSG: Expects property "%s" on object "%s" instance for association operation.
        parent::__construct($class_name, __('Expects property "%s" on object "%s" instance for association operation.', $prop_name, $class_name));
    }
}

