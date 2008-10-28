<?php
// $Id$

/**
 * @file
 * 定义 Behavior_Relation
 *
 * @ingroup behavior
 *
 * @{
 */

/**
 * Behavior_Relation 为 ActiveRecord 添加一组直接操作关联对象的方法
 *
 * 如果是与类 Comment 的 HAS_MANY 关联，则添加 addComment() 方法。
 * 如果是与类 Tag 的 MANY_TO_MANY 关联，则添加 bindTag()、unbindTag() 和 unbindAllTag() 方法。
 *
 * 其他类型的关联将不会添加方法。
 */
class Model_Behavior_Relation extends QDB_ActiveRecord_Behavior_Abstract
{

    protected $_settings = array
    (
        //! 要添加操作方法的关联属性名称，可以有多个
        'assoc_props' => ''
    );

    /**
     * 绑定行为插件
     */
    function bind()
    {
        $props = Q::normalize($this->_settings['assoc_props']);

        foreach ($props as $prop_name)
        {
        	if (empty($this->_meta->props[$prop_name]) || !$this->_meta->props[$prop_name]['assoc'])
        	{
        		// LC_MSG: 类 "%s" 的 "%s" 属性不是一个关联.
        		throw new QDB_ActiveRecord_Behavior_Exception(__('类 "%s" 的 "%s" 属性不是一个关联.',
        		                                                 $this->_meta->class_name, $prop_name));
        	}

        	$assoc_type = $this->_meta->props[$prop_name]['assoc'];
        	$suffix = $this->_meta->props[$prop_name]['assoc_class'];
        	$arg = array($prop_name);

        	if ($assoc_type == QDB::HAS_MANY)
        	{
        		$this->_addDynamicMethod("add{$suffix}", array($this, 'addRelated'), $arg);
        	}
        	elseif ($assoc_type == QDB::MANY_TO_MANY)
        	{
                $this->_addDynamicMethod("bind{$suffix}", array($this, 'bindRelated'), $arg);
        		$this->_addDynamicMethod("unbind{$suffix}", array($this, 'unbindRelated'), $arg);
                $this->_addDynamicMethod("unbindAll{$suffix}", array($this, 'unbindAllRelated'), $arg);
        	}
        }
    }

    /**
     * 添加一个关联对象
     *
     * @param QDB_ActiveRecord_Abstract $source
     * @param string $prop_name
     * @param QDB_ActiveRecord_Abstract $target
     */
    function addRelated(QDB_ActiveRecord_Abstract $source, $prop_name, QDB_ActiveRecord_Abstract $target)
    {
        $this->_meta->getAssoc($prop_name)->addRelatedObject($source, $target);
    }

    /**
     * 绑定一个关联对象
     *
     * @param QDB_ActiveRecord_Abstract $source
     * @param string $prop_name
     * @param QDB_ActiveRecord_Abstract $target
     */
    function bindRelated(QDB_ActiveRecord_Abstract $source, $prop_name, QDB_ActiveRecord_Abstract $target)
    {
        $this->_meta->getAssoc($prop_name)->bindRelatedObject($source, $target);
    }

    /**
     * 取消与一个对象的绑定
     *
     * @param QDB_ActiveRecord_Abstract $source
     * @param string $prop_name
     * @param QDB_ActiveRecord_Abstract $target
     */
    function unbindRelated(QDB_ActiveRecord_Abstract $source, $prop_name, QDB_ActiveRecord_Abstract $target)
    {
    	$this->_meta->getAssoc($prop_name)->unbindRelatedObject($source, $target);
    }

    /**
     * 取消与所有对象的绑定
     *
     * @param QDB_ActiveRecord_Abstract $source
     * @param string $prop_name
     */
    function unbindAllRelated(QDB_ActiveRecord_Abstract $source, $prop_name)
    {
        $this->removeRelated($source, $prop_name, null);
    }
}

/**
 * @}
 */
