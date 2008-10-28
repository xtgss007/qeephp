<?php
// $Id$

/**
 * @file
 * 定义 QException_ClassNotDefined 异常
 *
 * @ingroup core
 *
 * @{
 */

/**
 * QException_ClassNotDefined 异常指示指定的文件中没有定义需要的类
 *
 */
class QException_ClassNotDefined extends QException
{
    public $class_name;
    public $filename;

    function __construct($class_name, $filename)
    {
        $this->class_name = $class_name;
        $this->filename = $filename;
        parent::__construct(__('Class "%s" not defined in file "%s".', $class_name, $filename));
    }
}

/**
 * @}
 */

