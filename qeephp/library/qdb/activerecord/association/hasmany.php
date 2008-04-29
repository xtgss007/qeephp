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
 * 定义 QDB_ActiveRecord_Association_HasMany 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Association_HasMany 类封装数据表之间的 has many 关联
 *
 * @package database
 */
class QDB_ActiveRecord_Association_HasMany extends QDB_ActiveRecord_Association_Abstract
{
    /**
     * 构造函数
     *
     * @param array $params
     * @param QDB_ActiveRecord_Meta $source_meta
     *
     * @return QDB_ActiveRecord_Association
     */
    protected function __construct(array $params, QDB_ActiveRecord_Meta $source_meta)
    {
        parent::__construct(QDB::HAS_MANY, $params, $source_meta);
        $this->one_to_one = false;
    }

    /**
     * 初始化
     */
    function init()
    {
        if ($this->_is_init) { return $this; }
        parent::init();
        $params = $this->_init_params;
        $this->source_key   = !empty($params['source_key']) ? $params['source_key'] : $this->source_meta->pk;
        $this->target_key   = !empty($params['target_key']) ? $params['target_key'] : $this->source_meta->pk;
        $this->on_delete    = !empty($params['on_delete'])  ? $params['on_delete']  : 'cascade';
        $this->on_save      = !empty($params['on_save'])    ? $params['on_save']    : 'save';
        $this->source_key_alias = $this->mapping_name . '_source_key';
        $this->target_key_alias = $this->mapping_name . '_target_key';
        return $this;
    }

    /**
     * 保存一对多数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function saveTarget(array $target_data, $source_key_value, $recursion)
    {
        $this->init();
        if ($this->on_save === false || $this->on_save == 'skip') { return; }
        foreach (array_keys($target_data) as $offset) {
            $target_data[$offset][$this->target_key] = $source_key_value;
        }
        $this->target_meta->saveRowset($target_data, $recursion, $this->on_save);
    }

    /**
     * 删除目标数据
     *
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function removeTarget($source_key_value, $recursion)
    {
        $this->init();
        if ($this->on_delete === false || $this->on_delete == 'skip') { return; }
        if ($this->on_delete === true || $this->on_delete == 'cascade') {
            $this->target_meta->removeByField($this->target_key, $source_key_value, $recursion);
        } elseif ($this->on_delete == 'reject') {
            $row = $this->target_meta->find(array($this->target_key => $source_key_value))->count()->query();
            if (intval($row['row_count']) > 0) {
                // LC_MSG: 关联 "%s" 拒绝删除来源 "%s" 的数据.
                throw new QDB_ActiveRecord_Association_Remove_Exception(__('关联 "%s" 拒绝删除来源 "%s" 的数据.',
                                                             $this->name, $this->source_meta->table_name));
            }
        } else {
            $fill = ($this->on_delete == 'set_null') ? null : $this->on_delete_set_value;
            $this->target_meta->updateWhere(array($this->target_key => $fill), array($this->target_key => $source_key_value));
        }
    }

    /**
     * 返回用于 JOIN 操作的 SQL 字符串
     *
     * @return string
     */
    function getJoinSQL()
    {
        $this->init();
        $sk = $this->source_meta->qfields($this->source_key);
        $tk = $this->target_meta->qfields($this->target_key);

        return " LEFT JOIN {$this->target_meta->qtable_name} ON {$sk} = {$tk} ";
    }
}
