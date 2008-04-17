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
 * 定义 QDB_ActiveRecord_Select 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Select 类封装了 ActiveRecord 的查询操作
 *
 * @package database
 */
class QDB_ActiveRecord_Select extends QDB_Select_Abstract
{
    /**
     * 发起查询的 ActiveRecord meta object
     *
     * @var QDB_ActiveRecord_Meta
     */
    protected $meta;

    /**
     * 构造函数
     *
     * @param QDB_ActiveRecord_Meta $meta
     * @param array $where
     */
    function __construct(QDB_ActiveRecord_Meta $meta, array $where = null)
    {
        parent::__construct();
        $this->meta = $meta;
        if (!is_null($where)) {
            call_user_func_array(array($this, 'where'), $where);
        }
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
            // 返回单行记录或单个对象
            if (is_array($row) && $this->as_object) {
                return new $this->as_object($row);
            } else {
                return $row;
            }
        }

        // 返回多行记录或多个对象
        if (is_array($rowset) && $this->as_object) {
            $objects = array();
            foreach (array_keys($rowset) as $offset) {
                $objects[] = new $this->as_object($rowset[$offset]);
            }
            return $objects;
        } else {
            return $rowset;
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
        /**
         * 1、构造 WHERE 子句，从而决定需要添加哪些 JOIN 操作，以便允许使用关联表字段作为查询条件
         */



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
