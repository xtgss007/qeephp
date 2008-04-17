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
 * 定义 QDB_Table_Link_BelongsTo 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Link_BelongsTo 类封装数据表之间的 belongs to 关联
 *
 * @package database
 */
class QDB_Table_Link_BelongsTo extends QDB_Table_Link_Abstract
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
        parent::__construct(self::belongs_to, $params, $source_table);
        $this->one_to_one = true;
    }

    /**
     * 初始化
     */
    function init()
    {
        if ($this->is_init) { return $this; }
        parent::init();
        $params = $this->init_params;
        $this->main_key   = !empty($params['main_key'])   ? $params['main_key']   : $this->target_table->pk;
        $this->target_key = !empty($params['target_key']) ? $params['target_key'] : $this->target_table->pk;
        $this->source_key_alias = $this->mapping_name . '_' . $this->source_key;
        $this->target_key_alias = $this->mapping_name . '_' . $this->target_key;
        $this->on_delete  = 'skip';
        $this->on_save    = 'skip';
        return $this;
    }

    /**
     * 保存 belongs to 目标数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function saveTargetData(array $target_data, $source_key_value, $recursion)
    {
        return;
    }

    /**
     * 删除目标数据
     *
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function removeTargetData($source_key_value, $recursion)
    {
        return;
    }

    /**
     * 返回用于 JOIN 操作的 SQL 字符串
     *
     * @return string
     */
    function getJoinSQL()
    {
        $this->init();
        $sk = $this->source_table->qfields($this->source_key);
        $tk = $this->target_table->qfields($this->target_key);

        return " LEFT JOIN {$this->target_table->qtable_name} ON {$sk} = {$tk} ";
    }
}
