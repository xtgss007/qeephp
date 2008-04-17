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
 * 定义 QDB_Select_Abstract 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Select_Abstract 类是数据库查询接口的基础类
 *
 * @package database
 */
abstract class QDB_Select_Abstract
{
    /**
     * SELECT 子句后要查询的内容
     *
     * @var string
     */
    protected $select = '*';

    /**
     * 查询条件
     *
     * @var array
     */
    protected $where = array();

    /**
     * 查询的排序
     *
     * @var string
     */
    protected $order = null;

    /**
     * 限定结果集大小
     *
     * @var mixed
     */
    protected $limit = 1;

    /**
     * 分页查询时，页码的基数
     *
     * @var int
     */
    protected $page_base = 1;

    /**
     * 分页查询时，每页包含的记录数
     *
     * @var int
     */
    protected $page_size = 20;

    /**
     * 当前查询的页
     *
     * @var int
     */
    protected $page = 1;

    /**
     * 分页信息
     *
     * @var array
     */
    protected $pager = null;

    /**
     * 指示是否是分页查询
     *
     * @var boolean
     */
    protected $page_query = false;

    /**
     * 添加 GROUP BY 子句
     *
     * @var string
     */
    protected $group = null;

    /**
     * 添加 HAVING 子句
     *
     * @var string
     */
    protected $having = array();

    /**
     * 使用 DISTINCT 模式
     *
     * @var boolean
     */
    protected $distinct = false;

    /**
     * 是否是 FOR UPDATE
     *
     * @var boolean
     */
    protected $for_update = false;

    /**
     * 递归关联查询的层数
     *
     * @var int
     */
    protected $recursion = 1;

    /**
     * 指示是否将查询到的数据封装为对象
     *
     * @var string
     */
    protected $as_object = null;

    /**
     * 查询中是否使用了统计函数
     *
     * @var boolean
     */
    protected $is_stat = false;

    /**
     * 统计记录数
     *
     * @var string
     */
    protected $count = null;

    /**
     * 统计平均值
     *
     * @var string
     */
    protected $avg = null;

    /**
     * 统计最大值
     *
     * @var string
     */
    protected $max = null;

    /**
     * 统计最小值
     *
     * @var string
     */
    protected $min = null;

    /**
     * 统计合计
     *
     * @var string
     */
    protected $sum = null;

    /**
     * 构造函数
     */
    function __construct()
    {
    }

    /**
     * 指定 SELECT 子句后要查询的内容
     *
     * @param array|string|QDB_Expr $expr
     *
     * @return QDB_Select_Abstract
     */
    function select($expr = '*')
    {
        $this->select = $expr;
        return $this;
    }

    /**
     * 设置递归关联查询的层数（默认为1层）
     *
     * @param int $recursion
     *
     * @return QDB_Select_Abstract
     */
    function recursion($recursion)
    {
        $this->recursion = abs(intval($recursion));
        return $this;
    }

    /**
     * 添加查询条件
     *
     * @param array|string $where
     *
     * @return QDB_Select_Abstract
     */
    function where($where)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->whereArgs($where, $args);
    }

    /**
     * 添加查询条件，并且以数组附加查询参数
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Select_Abstract
     */
    abstract function whereArgs($where, array $args);

    /**
     * 指定 HAVING 子句的条件
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Select_Abstract
     */
    function having($where)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->havingArgs($where, $args);
    }

    /**
     * 添加 HAVING 查询条件，并且以数组附加查询参数
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Select_Abstract
     */
    abstract function havingArgs($where, array $args);

    /**
     * 指定查询的排序
     *
     * @param string $expr
     *
     * @return QDB_Select_Abstract
     */
    function order($expr)
    {
        $this->order = $expr;
        return $this;
    }

    /**
     * 指示查询所有符合条件的记录
     *
     * @return QDB_Select_Abstract
     */
    function all()
    {
        $this->limit = null;
        return $this;
    }

    /**
     * 限制查询结果总数
     *
     * @param int $count
     * @param int $offset
     *
     * @return QDB_Select_Abstract
     */
    function limit($count, $offset = 0)
    {
        $this->limit = array($count, $offset);
        return $this;
    }

    /**
     * 设置分页查询
     *
     * @param int $page
     * @param int $page_size
     * @param int $base
     *
     * @return QDB_Select_Abstract
     */
    function limitPage($page, $page_size = 20, $base = 1)
    {
        if ($base < 0) { $base = 0; }
        if ($page < $base) { $page = $base; }
        $this->page_base = $base;
        $this->page_size = $page_size;
        $this->page = $page;
        $this->pager = null;
        $this->page_query = true;
        return $this;
    }

    /**
     * 获得分页信息
     *
     * 必须先使用 limitPage() 指定有效分页参数。
     *
     * @return array
     */
    function getPager()
    {
        return $this->pager;
    }

    /**
     * 指定 GROUP BY 子句
     *
     * @param string $expr
     *
     * @return QDB_Select_Abstract
     */
    function group($expr)
    {
        $this->group = $expr;
    }

    /**
     * 是否构造一个 FOR UPDATE 查询
     *
     * @param boolean $flag
     *
     * @return QDB_Select_Abstract
     */
    function forUpdate($flag = true)
    {
        $this->for_update = (bool)$flag;
        return $this;
    }

    /**
     * 是否构造一个 DISTINCT 查询
     *
     * @param boolean $flag
     *
     * @return QDB_Select_Abstract
     */
    function distinct($flag = true)
    {
        $this->distinct = (bool)$flag;
        return $this;
    }


    /**
     * 统计符合条件的记录数
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Select_Abstract
     */
    function count($expr = '*', $alias = 'row_count')
    {
        $this->count = array($expr, $alias);
        $this->is_stat = true;
        return $this;
    }

    /**
     * 统计平均值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Select_Abstract
     */
    function avg($expr, $alias = 'avg_value')
    {
        $this->avg = array($expr, $alias);
        $this->is_stat = true;
        return $this;
    }

    /**
     * 统计最大值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Select_Abstract
     */
    function max($expr, $alias = 'max_value')
    {
        $this->max = array($expr, $alias);
        $this->is_stat = true;
        return $this;
    }

    /**
     * 统计最小值
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Select_Abstract
     */
    function min($expr, $alias = 'min_value')
    {
        $this->min = array($expr, $alias);
        $this->is_stat = true;
        return $this;
    }

    /**
     * 统计合计
     *
     * @param string $expr
     * @param string $alias
     *
     * @return QDB_Select_Abstract
     */
    function sum($expr, $alias = 'sum_value')
    {
        $this->sum = array($expr, $alias);
        $this->is_stat = true;
        return $this;
    }

    /**
     * 指示将查询结果封装为特定的 ActiveRecord 对象
     *
     * @param string $class_name
     *
     * @return QDB_Select_Abstract
     */
    function asObject($class_name)
    {
        $this->as_object = $class_name;
        return $this;
    }

    /**
     * 指示将查询结果返回为数组
     *
     * @return QDB_Select_Abstract
     */
    function asArray()
    {
        $this->as_object = null;
        return $this;
    }
}
