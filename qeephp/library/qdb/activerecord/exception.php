<?php

/**
 * 定义 QDB_ActiveRecord_Exception 异常
 */

/**
 * QDB_ActiveRecord_Exception 封装所有与 ActiveRecord 有关的错误
 */
class QDB_ActiveRecord_Exception extends QException
{
    /**
     * 相关的 ActiveRecord 类
     *
     * @var string
     */
    public $ar_class_name;

    function __construct($class_name, $msg)
    {
        $this->ar_class_name = $class_name;
        parent::__construct($msg);
    }
}

