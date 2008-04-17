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
 * 定义 QDB_Table_Select 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table_Select 类封装了表数据入口的查询操作
 *
 * @package database
 */
class QDB_Table_Select extends QDB_Select_Abstract
{
    /**
     * 查询使用的表数据入口对象
     *
     * @var QDB_Table
     */
    protected $table;

    /**
     * 要查询的关联
     *
     * @var array of QDB_Link_Abstract
     */
    protected $links;

    /**
     * 指定使用递归查询时，需要查询哪个关联的 target_key 字段
     *
     * @var QDB_Table_Link_Abstract
     */
    protected $link_of_recursion;

    /**
     * 构造函数
     *
     * @param QDB_Table $table
     * @param array $links
     * @param array $where
     */
    function __construct(QDB_Table $table, array $links, array $where = null)
    {
        parent::__construct();
        $this->links = $links;
        $this->table = $table;
        if (!is_null($where)) {
            call_user_func_array(array($this, 'where'), $where);
        }
    }

    /**
     * 添加查询条件，并且以数组附加查询参数
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Table_Select
     */
    function whereArgs($where, array $args)
    {
        list($sql, $used_links) = $this->table->parseSQLInternal($where, $args);
        if (!empty($sql)) {
            $this->where[] = array($sql, $used_links);
        }
        return $this;
    }

    /**
     * 添加 HAVING 查询条件，并且以数组附加查询参数
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Table_Select
     */
    function havingArgs($where, array $args)
    {
        list($sql, $used_links) = $this->table->parseSQLInternal($where, $args);
        if (!empty($sql)) {
            $this->having[] = array($sql, $used_links);
        }
        return $this;
    }

    /**
     * 设置关联查询时要使用的关联
     *
     * $links 可以是数组或字符串。如果 $links 为 null，则表示不查询关联。
     *
     * @param array|string $links
     *
     * @return QDB_Select_Abstract
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
     * 指定使用递归查询时，需要查询哪个关联的 target_key 字段
     *
     * @param QDB_Table_Link_Abstract $link
     *
     * @return QDB_Table_Select
     */
    function recursionForTarget(QDB_Table_Link_Abstract $link)
    {
        $this->link_of_recursion = $link;
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
        // 构造查询 SQL，并取得查询中用到的关联
        list($sql, $find_links) = $this->toStringInternal();

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

        if ($this->recursion > 0 && !empty($find_links)) {
            // 对关联表进行查询，并组装数据
            $refs_value = null;
            $refs = null;
            $used_alias = array_keys($find_links);
            $rowset = $handle->fetchAllRefby($used_alias, $refs_value, $refs, $clean_up);

            // 进行关联查询，并组装数据集
            foreach ($find_links as $link) {
                /* @var $link QDB_Table_Link_Abstract */
                $tka = $link->target_key_alias;
                $ska = $link->source_key_alias;
                if (empty($refs_value[$ska])) { continue; }

                $select = $link->target_table->find("[{$link->target_key}] IN (?)", $refs_value[$ska])
                                            ->recursion($this->recursion - 1)
                                            ->recursionForTarget($link)
                                            ->order($link->on_find_order)
                                            ->select($link->on_find_keys)
                                            ->where($link->on_find_where);
                if (is_int($link->on_find) || is_array($link->on_find)) {
                    $select->limit($link->on_find);
                } else {
                    $select->all();
                }

                $target_rowset = $select->query(false);
                if ($link->on_find === 1) {
                    $target_rowset = array($target_rowset);
                }

                // 组装数据集
                if ($link->one_to_one) {
                    foreach (array_keys($target_rowset) as $offset) {
                        $v = $target_rowset[$offset][$tka];
                        unset($target_rowset[$offset][$tka]);

                        $i = 0;
                        foreach ($refs[$ska][$v] as $row) {
                            $refs[$ska][$v][$i][$link->mapping_name] = $target_rowset[$offset];
                            unset($refs[$ska][$v][$i][$ska]);
                            $i++;
                        }
                    }
                } else {
                    foreach (array_keys($target_rowset) as $offset) {
                        $v = $target_rowset[$offset][$tka];
                        unset($target_rowset[$offset][$tka]);

                        $i = 0;
                        foreach ($refs[$ska][$v] as $row) {
                            $refs[$ska][$v][$i][$link->mapping_name][] = $target_rowset[$offset];
                            unset($refs[$ska][$v][$i][$ska]);
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
     * 返回查询语句以及相关关联的信息
     *
     * @return array
     */
    function toStringInternal()
    {
        $used_links = array();
        $find_links = array();
        $strings = array();

        /**
         * 1. 确定要查询的字段
         */
        list($string, $links) = $this->fetchFieldsAndLinks($this->select);
        $strings['select'][] = $string;
        $used_links[] = $links;

        /**
         * 1.1 确定要查询的关联，从而确保查询主表时能够得到相关的关联字段
         */
        if ($this->recursion > 0) {
            foreach ($this->links as $link) {
                /* @var $link QDB_Table_Link_Abstract */
                if (!$link->enabled || $link->on_find === false || $link->on_find == 'skip') { continue; }
                $link->init();
                $strings['select'][] = $link->source_table->qfields($link->source_key) .
                                       ' AS ' .
                                       $link->source_table->getConn()->qfield($link->source_key_alias);
                $find_links[$link->source_key_alias] = $link;
            }
        }

        /**
         * 1.2 如果指定了来源关联，则需要查询组装数据所需的关联字段
         */
        if ($this->link_of_recursion) {
            $link = $this->link_of_recursion;
            $strings['select'][] = $link->target_table->qfields($link->target_key) .
                                   ' AS ' .
                                   $link->target_table->getConn()->qfield($link->target_key_alias);
        }

        /**
         * 2. 构造 WHERE 和 HAVING 子句
         */
        $keys = array('where', 'having');
        foreach ($keys as $key) {
            $parts = $this->{$key};
            if (empty($parts)) {
                $strings[$key] = '';
            } else {
                $strings[$key] = array();
                foreach ($parts as $part) {
                    list($string, $links) = $part;
                    $strings[$key][] = $string;
                    $used_links[] = $links;
                }
                $strings[$key] = ' ' . strtoupper($key) . ' (' . implode(') AND (', $strings[$key]) . ')';
            }
            unset($parts);
        }

        /**
         * 3. 构造 count、avg、max、min、sum 子句
         */
        $keys = array('count', 'avg', 'max', 'min', 'sum');
        foreach ($keys as $key) {
            if (empty($this->{$key})) { continue; }
            list($expr, $alias) = $this->{$key};
            if ($expr != '*') {
                list($expr, $links) = $this->partToString($expr);
                $used_links[] = $links;
            }
            $strings['select'][] = strtoupper($key) . "({$expr}) AS {$alias}";
        }

        /**
         * 4. 处理 ORDER
         */
        if (!empty($this->order)) {
            list($string, $links) = $this->partToString($this->order);
            $strings['order'] = ' ORDER BY ' . $string;
            $used_links[] = $links;
        } else {
            $strings['order'] = '';
        }

        /**
         * 5. 处理 GROUP BY
         */
        if (!empty($this->group)) {
            list($string, $links) = $this->partToString($this->order);
            $strings['group'] = ' GROUP BY ' . $string;
            $used_links[] = $links;
        } else {
            $strings['group'] = '';
        }

        /**
         * 6. 开始构造 SQL
         */
        $sql = 'SELECT ';
        if ($this->distinct) { $sql .= 'DISTINCT '; }
        $sql .= implode(',' , $strings['select']);

        // FROM
        $sql .= " FROM {$this->table->qtable_name}";

        // JOIN
        $joined = array();
        foreach ($used_links as $links) {
            foreach ($links as $mapping_name) {
                if (isset($joined[$mapping_name])) { continue; }
                $joined[$mapping_name] = $this->table->getLink($mapping_name)->init()->getJoinSQL();
            }
        }
        if (!empty($joined)) {
            $sql .= ' ' . implode(' ', $joined);
        }

        // WHERE ...
        $sql .= $strings['where'];
        $sql .= $strings['group'];
        $sql .= $strings['having'];
        $sql .= $strings['order'];
        if ($this->for_update) { $sql .= ' FOR UPDATE'; }

        return array($sql, $find_links);
    }

    /**
     * 某个查询部件的字符串表达形式及其涉及到的关联
     *
     * @param mixed $fields
     *
     * @return array of list($string, $used_links)
     */
    protected function fetchFieldsAndLinks($fields)
    {
        if (is_object($fields)) {
            return array($fields->__toString(), array());
        }

        if (!is_array($fields)) {
            $fields = Q::normalize($fields);
        }

        $used_links = array();
        $parts = array();
        $conn = $this->table->getConn();
        foreach ($fields as $offset => $field) {
            if (!is_int($offset)) {
                $alias = $field;
                $field = $offset;
            } else {
                $alias = null;
            }

            $arr = explode('.', $field);
            if (isset($arr[1])) {
                $field = $arr[1];
                $table = $arr[0];
            } else {
                $field = $arr[0];
                $table = null;
            }

            if ($table && isset($this->links[$table])) {
                $used_links[] = $table;
                $table = $this->links[$table]->target_table->full_table_name;
            }

            if ($alias) {
                $parts[] = $conn->qfield($field, $table) . ' AS ' . $conn->qfield($alias);
            } else {
                $parts[] = $conn->qfield($field, $table);
            }
        }
        return array(implode(',', $parts), $used_links);
    }

    /**
     * 将一个 SQL 组件转换为字符串表现形式，及其涉及到的关联名称
     *
     * @param mixed $part
     *
     * @return array of list($string, $used_links)
     */
    protected function partToString($part)
    {
        if (is_object($part)) {
            return array($part->__toString(), array());
        } else {
            return $this->table->parseSQLInternal($part);
        }
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
