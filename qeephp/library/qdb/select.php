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
 * 定义 QDB_Select 类
 *
 * @package database
 * @version $Id: select.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB_Select 利用方法链，实现灵活的查询构造
 *
 * @package database
 */
class QDB_Select
{
    /**
     * 查询使用的表数据入口对象
     *
     * @var QDB_Table
     */
    protected $table;

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
     * 查询结果
     *
     * @var QDB_Result_Abstract
     */
    protected $handle = null;

    /**
     * 要查询的关联
     *
     * @var array
     */
    protected $links;

    /**
     * 递归关联查询的层数
     *
     * @var int
     */
    protected $recursion = 1;

    /**
     * 发起递归查询的表数据入口关联
     *
     * @var QDB_Table_Link
     */
    protected $recursion_link = null;

    /**
     * 指示是否将查询到的数据封装为对象
     *
     * @var string
     */
    protected $as_object = null;

    /**
     * 指示按照什么字段对结果集分组
     *
     * @var string
     */
    protected $group_result = null;

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
     *
     * @param QDB_Table $table
     * @param array|string $where
     * @param array $args
     * @param array $links
     */
    protected function __construct(QDB_Table $table, $where = null, array $args = null, array $links = null)
    {
        $this->table = $table;
        if (!is_array($links)) { $links = array(); }
        $this->links = $links;
        if (!is_null($where)) {
            list($sql, ) = $this->table->parseSQLInternal($where, $args);
            $this->where[] = $sql;
        }
    }

    /**
     * 发起一个来自表数据入口的查询
     *
     * @param QDB_Table $table
     * @param array|string $where
     * @param array $args
     * @param array $links
     *
     * @return QDB_Select
     */
    static function beginSelectFromTable(QDB_Table $table, $where = null, array $args = null, array $links = null)
    {
        return new QDB_Select($table, $where, $args, $links);
    }

    /**
     * 发起一个来自 ActiveRecord 的查询
     *
     * @param unknown_type $class
     * @param QDB_Table $table
     *
     * @return QDB_Select
     */
    static function beginSelectFromActiveRecord($class, QDB_Table $table)
    {
        $select = new QDB_Select($table);
        $select->class = $class;
        $select->table->connect();
        $select->links = $select->table->getAllLinks();
        $select->asObject($class);

        return $select;
    }

    /**
     * 指定 SELECT 子句后要查询的内容
     *
     * @param array|string|QDB_Expr $expr
     *
     * @return QDB_Select
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
     * @return QDB_Select
     */
    function recursion($recursion)
    {
        $this->recursion = abs($recursion);
        return $this;
    }

    /**
     * 设置递归查询的来源
     *
     * @param QDB_Table_Link_Abstract $link
     *
     * @return QDB_Select
     */
    function setRecursionSource(QDB_Table_Link_Abstract $link)
    {
        $this->recursion_link = $link;
        return $this;
    }

    /**
     * 设置关联查询时要使用的关联
     *
     * $links 可以是数组或字符串。如果 $links 为 null，则表示不查询关联。
     *
     * @param array|string $links
     *
     * @return QDB_Select
     */
    function links($links)
    {
        if (empty($links)) {
            $this->links = array();
        } else {
            $links = Q::normalize($links);
            $enabled = array();
            foreach ($links as $link) {
                if (isset($this->links[$link])) {
                    $enabled[$link] = $this->links[$link];
                }
            }
            $this->links = $enabled;
        }
        return $this;
    }

    /**
     * 添加查询条件
     *
     * @param array|string $where
     *
     * @return QDB_Select
     */
    function where($where)
    {
        $args = func_get_args();
        array_shift($args);
        list($sql, ) = $this->table->parseSQLInternal($where, $args);
        if (!empty($sql)) {
            $this->where[] = $sql;
        }
        return $this;
    }

    /**
     * 添加查询条件，并且以数组附加查询参数
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Select
     */
    function whereArgs($where, array $args)
    {
        list($sql, ) = $this->table->parseSQLInternal($where, $args);
        if (!empty($sql)) {
            $this->where[] = $sql;
        }
        return $this;
    }

    /**
     * 指定查询的排序
     *
     * @param string $expr
     *
     * @return QDB_Select
     */
    function order($expr)
    {
        $this->order = $expr;
        return $this;
    }

    /**
     * 指示查询所有符合条件的记录
     *
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
     */
    function group($expr)
    {
        $this->group = $expr;
    }

    /**
     * 指定 HAVING 子句的条件
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Select
     */
    function having($where, array $args = null)
    {
        $this->having[] = $this->table->parseSQL($where, $args);
    }

    /**
     * 是否构造一个 FOR UPDATE 查询
     *
     * @param boolean $flag
     *
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
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
     * @return QDB_Select
     */
    function asObject($class_name)
    {
        $this->as_object = $class_name;
        return $this;
    }

    /**
     * 指示将查询结果返回为数组
     *
     * @return QDB_Select
     */
    function asArray()
    {
        $this->as_object = null;
        return $this;
    }

    /**
     * 让结果集按照特定字段分组
     *
     * @param string $group_result
     *
     * @return QDB_Select
     */
    function groupResult($group_result)
    {
        $this->group_result = $group_result;
        return $this;
    }

    /**
     * 执行查询
     *
     * @param boolean $clean_up 是否清理数据集中的临时字段
     *
     * @return mixed
     */
    function query($clean_up = true)
    {
        if ($this->page_query) {
            $this->preparePagedQuery();
        }

        list($sql, $used_links) = $this->toStringInternal();

        // 对主表进行查询
        if (!is_array($this->limit)) {
            if (is_null($this->limit)) {
                $handle = $this->table->getConn()->execute($sql);
            } else {
                $handle = $this->table->getConn()->selectLimit($sql, intval($this->limit));
            }
        } else {
            list($count, $offset) = $this->limit;
            $handle = $this->table->getConn()->selectLimit($sql, $count, $offset);
        }
        /* @var $handle QDB_Result_Abstract */

        if ($this->recursion > 0) {
            // 对关联表进行查询，并组装数据
            $refs_value = null;
            $refs = null;
            $used_alias = array_keys($used_links);
            $rowset = $handle->fetchAllRefby($used_alias, $refs_value, $refs, $clean_up);

            // 进行关联查询，并组装数据集
            foreach ($used_links as $link) {
                /* @var $link QDB_Table_Link */
                $aka = $link->target_key_alias;
                $mka = $link->source_key_alias;
                if (empty($refs_value[$mka])) {
                    continue;
                }

                $select = $link->target_table->find("[{$link->target_key}] IN (?)", $refs_value[$mka])
                                            ->recursion($this->recursion - 1)
                                            ->setRecursionSource($link)
                                            ->order($link->on_find_order)
                                            ->select($link->on_find_keys)
                                            ->where($link->on_find_where);
                if (is_int($link->on_find) || is_array($link->on_find)) {
                    $select->limit($link->on_find);
                } else {
                    $select->all();
                }

                $assoc_rowset = $select->query(false);
                if ($link->on_find === 1) {
                    $assoc_rowset = array($assoc_rowset);
                }

                // 组装数据集
                if ($link->one_to_one) {
                    foreach (array_keys($assoc_rowset) as $offset) {
                        $v = $assoc_rowset[$offset][$aka];
                        unset($assoc_rowset[$offset][$aka]);

                        $i = 0;
                        foreach ($refs[$mka][$v] as $row) {
                            $refs[$mka][$v][$i][$link->mapping_name] = $assoc_rowset[$offset];
                            unset($refs[$mka][$v][$i][$mka]);
                            $i++;
                        }
                    }
                } else {
                    foreach (array_keys($assoc_rowset) as $offset) {
                        $v = $assoc_rowset[$offset][$aka];
                        unset($assoc_rowset[$offset][$aka]);

                        $i = 0;
                        foreach ($refs[$mka][$v] as $row) {
                            $refs[$mka][$v][$i][$link->mapping_name][] = $assoc_rowset[$offset];
                            unset($refs[$mka][$v][$i][$mka]);
                        }
                    }
                }
            }

            unset($refs);
            unset($refs_value);
            unset($row);
            if ($this->limit == 1) {
                $row = reset($rowset);
            }
        } else {
            // 非关联查询
            unset($row);
            if ($this->limit == 1) {
                $row = $handle->fetchRow();
            } else {
                $rowset = $handle->fetchAll();
            }
        }

        if ($this->is_stat && isset($rowset)) {
            $row = reset($rowset);
        }

        if ($this->as_object) {
            Q::loadClass($this->as_object);
        }

        if (isset($row)) {
            if (is_array($row) && $this->as_object) {
                return new $this->as_object($row);
            } else {
                return $row;
            }
        } else {
            if (is_array($rowset) && $this->as_object) {
                $objects = array();
                if (!empty($this->group_result)) {
                    foreach (array_keys($rowset) as $offset) {
                        $v = $rowset[$offset][$this->group_result];
                        $objects[$v][] = new $this->as_object($rowset[$offset]);
                    }
                } else {
                    foreach (array_keys($rowset) as $offset) {
                        $objects[] = new $this->as_object($rowset[$offset]);
                    }
                }
                return $objects;
            } else {
                if (!empty($this->group_result)) {
                    Q::loadVendor('array');
                    return array_group_by($rowset, $this->group_result);
                } else {
                    return $rowset;
                }
            }
        }
    }

    /**
     * 返回完整的 SQL 语句
     *
     * @return string
     */
    function toString()
    {
        list($sql) = $this->toStringInternal(false);
        return $sql;
    }

    /**
     * 将一个字段列表或者一个表达式转换为字符串
     *
     * @param mixed $part
     *
     * @return string
     */
    protected function toStringPart($part)
    {
        if (is_object($part)) {
            return $part->toString();
        } else {
            return $this->table->getConn()->qfields($part, $this->table->full_table_name);
        }
    }

    /**
     * 返回查询语句以及相关关联的信息
     *
     * @param boolean $use_links
     *
     * @return array
     */
    protected function toStringInternal($use_links = true)
    {
        $sql = 'SELECT ';
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        $used_links = array();
        $join = array();
        $next_select = '';
        if (!$this->is_stat) {
            if ($this->recursion_link) {
                $conn = $this->recursion_link->target_table->getConn();
                $sql .= $conn->qfield($this->recursion_link->target_key) .
                        ' AS ' .
                        $conn->qfield($this->recursion_link->target_key_alias) . ', ';
            }

            $sql .= $this->toStringPart($this->select);
            $next_select = ', ';

            $conn = $this->table->getConn();
            if ($use_links && $this->recursion > 0) {
                foreach ((array)$this->links as $link) {
                    /* @var $link QDB_Table_Link */
                    if (!$link->enabled || $link->on_find == 'skip') { continue; }
                    $link->init();
                    // if ($link->target_table === $this->recursion_link) { continue; }

                    switch ($link->type) {
                    case QDB_Table::has_one:
                    case QDB_Table::has_many:
                    case QDB_Table::belongs_to:
                        $sql .= ', ' . $conn->qfield($link->source_key, $this->table->full_table_name) .
                                " AS {$link->source_key_alias}";
                        $used_links[$link->source_key_alias] = $link;
                        break;
                    case QDB_Table::many_to_many:
                        if (empty($link->mid_on_find_keys)) {
                            $sql .= ', ' . $conn->qfield($link->mid_target_key, $link->mid_table->full_table_name) .
                                    " AS {$link->source_key_alias}";
                            $join[] = "LEFT JOIN {$link->mid_table->qtable_name} ON " .
                                      $conn->qfield($link->mid_source_key, $link->mid_table->full_table_name) .
                                      ' = ' . $conn->qfield($link->source_key, $this->table->full_table_name);
                            $used_links[$link->source_key_alias] = $link;
                        } else {
                            // 结果中要包含中间表的数据
                        }
                    }
                }
            }
        }

        // 如果使用了统计函数，则不允许使用 asObject() 和关联查询操作
        if ($this->is_stat) {
            if ($this->as_object) {
                // LC_MSG: Mixing of GROUP columns with asObject() or linked tables.
                throw new QDB_Select_Exception(__('Mixing of GROUP columns with asObject() or linked tables.'));
            }
        }

        if ($this->count) {
            list($expr, $alias) = $this->count;
            if ($expr != '*') {
                $expr = $this->toStringPart($expr);
            }
            $sql .= "{$next_select}COUNT({$expr}) AS {$alias}";
            $next_select = ', ';
        }
        if ($this->avg) {
            list($expr, $alias) = $this->avg;
            $expr = $this->toStringPart($expr);
            $sql .= "{$next_select}AVG({$expr}) AS {$alias} ";
            $next_select = ', ';
        }
        if ($this->max) {
            list($expr, $alias) = $this->max;
            $expr = $this->toStringPart($expr);
            $sql .= "{$next_select}MAX({$expr}) AS {$alias} ";
            $next_select = ', ';
        }
        if ($this->min) {
            list($expr, $alias) = $this->min;
            $expr = $this->toStringPart($expr);
            $sql .= "{$next_select}MIN({$expr}) AS {$alias} ";
            $next_select = ', ';
        }
        if ($this->sum) {
            list($expr, $alias) = $this->sum;
            $expr = $this->toStringPart($expr);
            $sql .= "{$next_select}SUM({$expr}) AS {$alias} ";
            $next_select = ', ';
        }

        $sql .= " FROM {$this->table->qtable_name}";
        $sql .= implode(' ', $join);
        $sql .= $this->toStringWhere();
        $sql .= $this->toStringGroup();
        $sql .= $this->toStringHaving();

        if ($this->order) {
            $order = $this->table->parseSQL($this->order);
            $sql .= " ORDER BY {$order}";
        }

        if ($this->for_update) {
            $sql .= ' FOR UPDATE';
        }

        return array($sql, $used_links);
    }

    /**
     * 构造 SQL 的 WHERE 子句
     *
     * @return string
     */
    protected function toStringWhere()
    {
        $c = array();
        $sql = '';
        foreach ($this->where as $where) {
            if (empty($where)) { continue; }
            $c[] = "({$where})";
        }
        if (!empty($c)) {
            $sql .= ' WHERE ' . implode(' AND ', $c);
        }
        return $sql;
    }


    /**
     * 构造 SQL 的 GROUP 子句
     *
     * @return string
     */
    protected function toStringGroup()
    {
        if ($this->group) {
            $group = $this->table->parseSQL($this->group);
            return " GROUP BY {$group}";
        } else {
            return '';
        }
    }

    /**
     * 构造 SQL 的 HAVING 子句
     *
     * @return string
     */
    protected function toStringHaving()
    {
        $c = array();
        $sql = '';
        foreach ($this->having as $where) {
            $c[] = '(' . $this->table->parseSQL($where) . ')';
        }
        if (!empty($c)) {
            $sql .= ' HAVING ' . implode(' AND ', $c);
        }
        return $sql;
    }

    /**
     * 为准备分页查询获得分页数据
     */
    protected function preparePagedQuery()
    {
        $sql = 'SELECT ';
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        $sql .= " COUNT(*) FROM {$this->table->qtable_name}";
        $sql .= $this->toStringWhere();
        $sql .= $this->toStringGroup();
        $sql .= $this->toStringHaving();

        $count = (int)$this->table->getConn()->getOne($sql);

        $pager = array();
        $pager['page_count'] = ceil($count / $this->page_size);
        $pager['first'] = $this->page_base;
        $pager['last'] = $pager['page_count'] + $this->page_base - 1;
        if ($pager['last'] < $pager['first']) { $pager['last'] = $pager['first']; }

        if ($this->page >= $pager['page_count'] + $this->page_base) {
            $this->page = $pager['last'];
        }
        if ($this->page < $this->page_base) {
            $this->page = $pager['first'];
        }
        if ($this->page < $pager['last'] - 1) {
            $pager['next'] = $this->page + 1;
        } else {
            $pager['next'] = $pager['last'];
        }
        if ($this->page > $this->page_base) {
            $pager['prev'] = $this->page - 1;
        } else {
            $pager['prev'] = $pager['first'];
        }
        $pager['current'] = $this->page;
        $pager['page_size'] = $this->page_size;
        $pager['page_base'] = $this->page_base;

        $this->pager = $pager;
        $this->limit = array($this->page_size, ($this->page - 1) * $this->page_size);
    }

}
