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
 * 定义 QDB_Table_Link_HasOne 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Link_HasOne 类封装数据表之间的 has one 关联
 *
 * @package database
 */
class QDB_Table_Link_HasOne extends QDB_Table_Link_HasMany
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
        parent::__construct($params, $source_table);
        $this->type = self::has_one;
        $this->one_to_one = true;
    }

    /**
     * 保存 has one 目标数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    function saveTargetData(array $target_data, $source_key_value, $recursion)
    {
        parent::saveTargetData(array($target_data), $source_key_value, $recursion);
    }
}
