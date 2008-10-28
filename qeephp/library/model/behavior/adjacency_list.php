<?php

// $Id$


/**
 * @file
 * 定义 Behavior_Adjacency_List
 *
 * @ingroup behavior
 *
 * @{
 */

/**
 * Behavior_Adjacency_List 以邻接表存储树形结构数据
 */
class Model_Behavior_Adjacency_List extends QDB_ActiveRecord_Behavior_Abstract
{

    /**
     * 设置
     *
     * @var array
     */
    protected $_settings = array
    (
        //! 父对象ID属性名
        'parent_id_prop' => 'parent_id',
        //! 父对象映射为对象的什么属性
        'parent_mapping' => 'parent_node',
        //! 子对象映射为对象的什么属性
        'childs_mapping' => 'child_nodes',
    );

    /**
     * 绑定行为插件
     */
    function bind()
    {
        $config = array('target_key' => $this->_settings['parent_id_prop']);
        $this->_meta->addAssoc($this->_settings['childs_mapping'], QDB::HAS_MANY, $config);
        $config = array('source_key' => $this->_settings['parent_id_prop']);
        $this->_meta->addAssoc($this->_settings['parent_mapping'], QDB::BELONGS_TO, $config);
    }

    /**
     * 撤销绑定
     */
    function unbind()
    {
        $this->_meta->removeAssoc($this->_settings['childs_mapping']);
        $this->_meta->removeAssoc($this->_settings['parent_mapping']);
    }
}

/**
 * @}
 */
