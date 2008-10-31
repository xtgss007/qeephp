<?php
// $Id$

class Q_IllegalClassNameException extends QException
{
    public $class_name;

    function __construct($class_name)
    {
        $this->class_name = $class_name;
        parent::__construct(__('Security check: Illegal character in class name "%s".', $class_name));
    }
}


