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
     * 查询时，主表的关联字段使用什么别名
     *
     * @var string
     */
    public $main_key_alias;

    /**
     * 查询时，关联表的关联字段使用什么别名
     *
     * @var string
     */
    public $target_key_alias;

    /**
     * 构造函数
     *
     * @param string $name
     * @param int $type
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_Table_Link
     */
    function __construct($name, $type, array $params, QDB_Table $source_table)
    {
        parent::__construct($name, $type, $params);

        $this->source_table = $source_table;
        $this->source_table->connect();

        /**
         * target_table_obj    目标表数据入口对象
         *
         * target_table_class  目标表数据入口类
         *
         * target_table_name   目标数据表
         * target_table_params 构造目标表数据入口对象时的参数
         *
         * target_table_obj、target_table_class、target_table_name 三者只需要指定一个，三者的优先级从上到下。
         * 如果 target_table_name 有效，则可以通过 target_table_params 指示构造关联表数据入口时的选项。
         */
        if (!empty($params['target_table_obj'])) {
            $this->target_table = $params['target_table_obj'];
        } elseif (!empty($params['target_table_class'])) {
            $this->target_table = Q::getSingleton($params['target_table_class']);
        } elseif (!empty($params['target_table_name'])) {
            $target_table_params = !empty($params['target_table_params']) ? (array)$params['target_table_params'] : array();
            $target_table_params['table_name'] = $params['target_table_name'];
            $this->target_table = new QDB_Table($target_table_params);
        } else {
            // LC_MSG: Expected parameter "%s".
            $err = 'target_table_obj or target_table_class or target_table_name';
            throw new QDB_Table_Link_Exception(__('Expected parameter "%s" for link "%s".', $err, $this->name));
        }
        $this->target_table->connect();

        $this->on_find             = !empty($params['on_find'])             ? $params['on_find']             : 'all';
        $this->on_find_where       = !empty($params['on_find_where'])       ? $params['on_find_where']       : null;
        $this->on_find_keys        = !empty($params['on_find_keys'])        ? $params['on_find_keys']        : '*';
        $this->on_find_order       = !empty($params['on_find_order'])       ? $params['on_find_order']       : null;
        $this->on_delete_set_value = !empty($params['on_delete_set_value']) ? $params['on_delete_set_value'] : null;
    }

    /**
     * 保存目标数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    abstract function saveTargetData(array $target_data, $source_key_value, $recursion);

    /**
     * 删除目标数据
     *
     * @param mixed $target_key_value
     * @param int $recursion
     */
    abstract function removeTargetData($source_key_value, $recursion);
}
