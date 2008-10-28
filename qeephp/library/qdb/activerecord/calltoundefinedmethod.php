<?php

class QDB_ActiveRecord_CallToUndefinedMethod extends QDB_ActiveRecord_Exception
{
    public $method_name;

    function __construct($class_name, $method_name)
    {
        $this->method_name = $method_name;
        // LC_MSG: Call to undefined method "%s" on object "%s" instance.
        parent::__construct($class_name, __('Call to undefined method "%s" on object "%s" instance.', $method_name, $class_name));
    }
}

