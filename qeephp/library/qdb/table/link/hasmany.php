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
 * 定义 QDB_Table_Link_HasMany 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Link_HasMany 类封装数据表之间的 has many 关联
 *
 * @package database
 */
class QDB_Table_Link_HasMany extends QDB_Table_Link_Abstract
{
    /**
     * 构造函数
     *
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_Table_Link
     */
    protected function __construct(array $params, QDB_Table $source_table)
    {
        parent::__construct(self::has_many, $params, $source_table);
        $this->one_to_one = false;
    }

    /**
     * 初始化
     */
    function init()
    {
        if ($this->is_init) { return; }
        parent::init();
        $params = $this->init_params;
        $this->main_key   = !empty($params['main_key'])    ? $params['main_key']   : $this->source_table->pk;
        $this->target_key = !empty($params['target_key'])  ? $params['target_key'] : $this->source_table->pk;
        $this->on_delete  = !empty($params['on_delete'])   ? $params['on_delete']  : 'cascade';
        $this->on_save    = !empty($params['on_save'])     ? $params['on_save']    : 'save';
    }

    /**
     * 保存一对多数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function saveTargetData(array $target_data, $source_key_value, $recursion)
    {
        $this->init();
        if ($this->on_save === false || $this->on_save == 'skip') { return; }
        foreach (array_keys($target_data) as $offset) {
            $target_data[$offset][$this->target_key] = $source_key_value;
        }
        $this->target_table->saveRowset($target_data, $recursion, $this->on_save);
    }

    /**
     * 删除目标数据
     *
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function removeTargetData($source_key_value, $recursion)
    {
        $this->init();
        if ($this->on_delete === false || $this->on_delete == 'skip') { return; }
        if ($this->on_delete === true || $this->on_delete == 'cascade') {
            $this->target_table->removeByField($this->target_key, $source_key_value, $recursion);
        } elseif ($this->on_delete == 'reject') {
            $row = $this->target_table->find(array($this->target_key => $source_key_value))->count()->query();
            if (intval($row['row_count']) > 0) {
                // LC_MSG: 关联 "%s" 拒绝删除来源 "%s" 的数据.
                throw new QDB_Link_Remove_Reject_Exception(__('关联 "%s" 拒绝删除来源 "%s" 的数据.',
                                                              $this->name, $this->source_table->table_name));
            }
        } else {
            $fill = ($this->on_delete == 'set_null') ? null : $this->on_delete_set_value;
            $this->target_table->updateWhere(array($this->target_key => $fill), array($this->target_key => $source_key_value));
        }
    }
}
