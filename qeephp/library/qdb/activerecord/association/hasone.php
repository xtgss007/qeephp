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
 * 定义 QDB_ActiveRecord_Association_HasOne 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Association_HasOne 类封装数据表之间的 has one 关联
 *
 * @package database
 */
class QDB_ActiveRecord_Association_HasOne extends QDB_ActiveRecord_Association_HasMany
{
    /**
     * 构造函数
     *
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_ActiveRecord_Association
     */
    protected function __construct(array $params, QDB_Table $source_table)
    {
        parent::__construct($params, $source_table);
        $this->type = QDB::HAS_ONE;
        $this->one_to_one = true;
    }

    /**
     * 保存 has one 目标数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function saveTarget(array $target_data, $source_key_value, $recursion)
    {
        parent::saveTarget(array($target_data), $source_key_value, $recursion);
    }
}
