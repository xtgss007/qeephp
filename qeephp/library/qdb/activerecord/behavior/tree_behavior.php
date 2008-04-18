<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 Behavior_Tree 类
 *
 * @package database
 * @version $Id$
 */

/**
 * Behavior_Tree 使用改进型先根遍历算法存储树状结构
 *
 * @package database
 */
class Behavior_Tree extends QDB_ActiveRecord_Behavior_Abstract
{
    /**
     * 设置
     *
     * @var array
     */
    protected $settings = array(
        'parent_id' => 'parent_id',
        'left'      => 'lft',
        'right'     => 'rgt',
    );

    /**
     * 该方法返回行为插件定义的回调事件以及扩展的方法
     *
     * @return array
     */
    function __callbacks()
    {
        return array(
            array(self::before_create,    'beforeCreate'),
            array(self::after_destroy,    'afterDestroy'),
            array(self::dynamic_callback, 'createChild'),
            array(self::dynamic_callback, 'getSubNodes'),
            array(self::dynamic_callback, 'getPath'),
            array(self::dynamic_callback, 'getSubTree'),
            array(self::dynamic_callback, 'getCurrentLevelNodes'),
            array(self::dynamic_callback, 'allChildCount'),
        );
    }

    /**
     * 在数据库中创建 ActiveRecord 对象前调用
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function beforeCreate(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        $table = $obj->getMeta()->table;

        $rgt = $this->settings['right'];
        $lft = $this->settings['left'];
        $pid = $this->settings['parent_id'];

        $parent_id = !empty($props[$pid]) ? (int)$props[$pid] : null;
        if ($parent_id) {
            $idname = $obj->idname();
            $sql = "SELECT * FROM {$table->qtable_name} WHERE {$idname} = {$parent_id}";
            $parent = $table->conn->getRow($sql);
            if (empty($parent)) {
                throw new QException("指定的 {$pid} 值 {$parent_id} 无效。");
            }

            // 根据父节点的左值和右值更新数据
            $sql = "UPDATE {$table->qtable_name} SET {$lft} = {$lft} + 2 WHERE {$lft} >= {$parent[$rgt]}";
            $table->conn->execute($sql);
            $sql = "UPDATE {$table->qtable_name} SET {$rgt} = {$rgt} + 2 WHERE {$rgt} >= {$parent[$rgt]}";
            $table->conn->execute($sql);

            $props[$lft] = $parent[$rgt];
            $props[$rgt] = $parent[$rgt] + 1;
        } else {
            $props[$pid] = 0;
            $props[$lft] = 1;
            $props[$rgt] = 2;
        }
    }

    /**
     * 在数据库中删除记录后调用
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function afterDestroy(QDB_ActiveRecord_Abstract $obj, array $props)
    {
        $table = $obj->getMeta()->table;
        $rgt = $this->settings['right'];
        $lft = $this->settings['left'];

        $span = $props[$rgt] - $props[$lft] + 1;
        $sql = "DELETE FROM {$table->qtable_name} WHERE {$lft} >= {$props[$lft]} AND {$rgt} <= {$props[$rgt]}";
        $table->conn->execute($sql);
        $sql = "UPDATE {$table->qtable_name} SET {$lft} = {$lft} - {$span} WHERE {$lft} > {$props[$lft]}";
        $table->conn->execute($sql);
        $sql = "UPDATE {$table->qtable_name} SET {$rgt} = {$rgt} - {$span} WHERE {$rgt} > {$props[$rgt]}";
        $table->conn->execute($sql);
    }

    /**
     * 创建一个子节点
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     *
     * @return QDB_ActiveRecord_Abstract
     */
    function createChild(QDB_ActiveRecord_Abstract $obj, array $props)
    {
        $this->testNode($obj);
        $class = get_class($obj);
        return new $class(array($this->settings['parent_id'] => $obj->id()));
    }

    /**
     * 返回根节点到指定节点路径上的所有节点
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param boolean $asArray
     *
     * @return QColl|array
     */
    function getPath(QDB_ActiveRecord_Abstract $obj, array $props, $asArray = false)
    {
        $rgt = $this->settings['right'];
        $lft = $this->settings['left'];

        $select = $obj->getMeta()->table
                      ->find("[{$lft}] < ? AND [{$rgt}] > ?", $props[$lft], $props[$rgt])
                      ->all()
                      ->order($this->settings['left'] . ' ASC');
        if ($asArray) {
            $select->asArray();
        } else {
            $select->asObject(get_class($obj));
        }
        return $select->query();
    }

    /**
     * 返回指定节点的直接子节点
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param boolean $asArray
     *
     * @return QColl|array
     */
    function getSubNodes(QDB_ActiveRecord_Abstract $obj, array $props, $asArray = false)
    {
        $select = $obj->getMeta()->table
                      ->find("[{$this->settings['parent_id']}] = ?", $obj->id())
                      ->all()
                      ->order($this->settings['left'] . ' ASC');
        if ($asArray) {
            $select->asArray();
        } else {
            $select->asObject(get_class($obj));
        }
        return $select->query();
    }

    /**
     * 返回指定节点为根的整个子节点树
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param boolean $asArray
     *
     * @return QColl|array
     */
    function getSubTree(QDB_ActiveRecord_Abstract $obj, array $props, $asArray = false)
    {
        $rgt = $this->settings['right'];
        $lft = $this->settings['left'];

        $select = $obj->getMeta()->table
                      ->find("[{$lft}] BETWEEN ? AND ?", $props[$lft], $props[$rgt])
                      ->all()
                      ->order($this->settings['left'] . ' ASC');
        if ($asArray) {
            $select->asArray();
        } else {
            $select->asObject(get_class($obj));
        }
        return $select->query();
    }

    /**
     * 获取指定节点同级别的所有节点
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param boolean $asArray
     *
     * @return QColl|array
     */
    function getCurrentLevelNodes(QDB_ActiveRecord_Abstract $obj, array $props, $asArray = false)
    {
        $pid = $this->settings['parent_id'];

        $select = $obj->getMeta()->table
                      ->find("[{$pid}] = ?", $props[$pid])
                      ->all()
                      ->order($this->settings['left'] . ' ASC');
        if ($asArray) {
            $select->asArray();
        } else {
            $select->asObject(get_class($obj));
        }
        return $select->query();
    }

    /**
     * 计算所有子节点的总数
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     *
     * @return int
     */
    function allChildCount(QDB_ActiveRecord_Abstract $obj, array $props)
    {
        return intval(($props[$this->settings['right']] - $props[$this->settings['left']] - 1) / 2);
    }

    /**
     * 测试指定的对象是否还存在于数据库中
     *
     * @param QDB_ActiveRecord_Abstract $obj
     */
    protected function testNode(QDB_ActiveRecord_Abstract $obj)
    {
        $table = $obj->getMeta()->table;
        $idname = $obj->idname();
        $id = $obj->id();
        if (!empty($id)) {
            $sql = "SELECT COUNT(*) FROM {$table->qtable_name} WHERE {$idname} = {$id}";
            $count = (int)$table->conn->getOne($sql);
        } else {
            $count = 0;
        }
        if ($count == 0) {
            // LC_MSG: 节点对象已经从数据库中删除.
            throw new QException(__('节点对象已经从数据库中删除.'));
        }
    }

}
