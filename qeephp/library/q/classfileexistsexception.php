<?php

class Q_ClassFileExistsException extends QException
{
    public $class_name;
    public $filename;

    function __construct($class_name, $filename)
    {
        $this->class_name = $class_name;
        $this->filename = $filename;
        parent::__construct(__('Class "%s" declare file "%s" exists.', $class_name, $filename));
    }

}

