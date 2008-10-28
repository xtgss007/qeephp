<?php
// $Id$

/**
 * @file
 * 定义 QDB_ActiveRecord_Association_Exception_NotDefined 异常
 *
 * @ingroup activerecord
 *
 * @{
 */

/**
 * QDB_ActiveRecord_Association_Exception_NotDefined 异常指示未定义的关联
 */
class QDB_ActiveRecord_Association_Exception_NotDefined extends QException
{
    /**
     * 相关的 ActiveRecord 类名称
     * 
     * @var string
     */
    public $class_name;
    
    
    /**
     * 关联属性名
     * 
     * @var string
     */
    public $prop_name;
    
    function __construct($class_name, $prop_name)
    {
        $this->class_name = $class_name;
        $this->prop_name = $prop_name;
        // LC_MSG: ActiveRecord 类 "%s" 没有定义属性 "%s"，或者该属性不是关联对象.
        parent::__construct(__('ActiveRecord 类 "%s" 没有定义属性 "%s"，或者该属性不是关联对象.', $class_name, $prop_name));
    }
}

/**
 * @}
 */
