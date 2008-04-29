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
 * 定义 QDB_Table_Link_Abstract 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Link_Abstract 封装数据表之间的关联关系
 *
 * @package database
 */
/**
 * QDB_Table_Link_Abstract 封装了一个关联
 *
 * @package database
 */
abstract class QDB_Table_Link_Abstract extends QDB_Link_Abstract
{
    /**
     * 关联中的主表
     *
     * @var QDB_Table
     */
    public $source_table;

    /**
     * 关联到哪一个表数据入口对象
     *
     * @var QDB_Table
     */
    public $target_table;

    /**
     * many to many 关联中处理中间表的表数据入口对象
     *
     * @var QDB_Table
     */
    public $mid_table;

    /**
     * 构造函数
     *
     * @param int $type
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_Table_Link_Abstract
     */
    protected function __construct($type, array $params, QDB_Table $source_table)
    {
        parent::__construct($type, $params);
        $this->source_table = $source_table;
    }

    /**
     * 获得一个关联对象
     *
     * @param int $type
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_Table_Link_Abstract
     */
    static function createLink($type, array $params, QDB_Table $source_table)
    {
        switch ($type) {
        case QDB::HAS_ONE:
            return new QDB_Table_Link_HasOne($params, $source_table);
        case QDB::HAS_MANY:
            return new QDB_Table_Link_HasMany($params, $source_table);
        case QDB::BELONGS_TO:
            return new QDB_Table_Link_BelongsTo($params, $source_table);
        case QDB::MANY_TO_MANY:
            return new QDB_Table_Link_ManyToMany($params, $source_table);
        default:
            // LC_MSG: 无效的关联类型 "%s".
            throw new QDB_Table_Link_Exception(__('无效的关联类型 "%s".', $type));
        }
    }

    /**
     * 初始化关联
     *
     * @return QDB_Table_Link_Abstract
     */
    function init()
    {
        if ($this->_is_init) { return $this; }

        $this->source_table->connect();
        $params = $this->_init_params;

        /**
         * table_obj    目标表数据入口对象
         *
         * table_class  目标表数据入口类
         *
         * table_name   目标数据表
         * table_params 构造目标表数据入口对象时的参数
         *
         * table_obj、table_class、table_name 三者只需要指定一个，三者的优先级从上到下。
         * 如果 table_name 有效，则可以通过 table_params 指示构造关联表数据入口时的选项。
         */
        if (!empty($params['table_obj'])) {
            $this->target_table = $params['table_obj'];
        } elseif (!empty($params['table_class'])) {
            $this->target_table = Q::getSingleton($params['table_class']);
        } elseif (!empty($params['table_name'])) {
            $target_table_params = !empty($params['table_params']) ? (array)$params['table_params'] : array();
            $target_table_params['table_name'] = $params['table_name'];
            $this->target_table = new QDB_Table($target_table_params);
        } else {
            // LC_MSG: Expected parameter "%s".
            $err = 'target_table_obj or target_table_class or target_table_name';
            throw new QDB_Table_Link_Exception(__('Expected parameter "%s" for link "%s".', $err, $this->mapping_name));
        }
        $this->target_table->connect();

        $this->on_find             = !empty($params['on_find'])             ? $params['on_find']             : 'all';
        $this->on_find_where       = !empty($params['on_find_where'])       ? $params['on_find_where']       : null;
        $this->on_find_keys        = !empty($params['on_find_keys'])        ? $params['on_find_keys']        : '*';
        $this->on_find_order       = !empty($params['on_find_order'])       ? $params['on_find_order']       : null;
        $this->on_delete_set_value = !empty($params['on_delete_set_value']) ? $params['on_delete_set_value'] : null;

        $this->_is_init = true;
        return $this;
    }

    /**
     * 返回用于 JOIN 操作的 SQL 字符串
     *
     * @return string
     */
    abstract function getJoinSQL();
}
