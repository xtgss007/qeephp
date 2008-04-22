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
 * 开发者无需直接构造 QDB_Table_Select 查询对象，而是通过某个表数据入口对象的 find() 方法或
 * ActiveRecord 继承类的 find() 方法发起查询来获得一个查询对象。
 *
 * 可以通过连贯方法改变查询对象的行为， 但要特别注意的是一旦使用了任何统计方法，
 * 包括 count、avg、min、max 和 sum。则返回的查询结果都只是一个一维数组，其中包含了统计结果。
 *
 * 因此如果要使用统计方法，就不要和其他数据一起查询。这可以通过分别构造两个不同的查询对象来解决。
 *
 * @package database
 */
class QDB_Table_Select
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
     * @var array of QDB_Table_Link_Abstract
     */
    protected $links;

    /**
     * 指定使用递归查询时，需要查询哪个关联的 target_key 字段
     *
     * @var QDB_Table_Link_Abstract
     */
    protected $link_of_recursion;

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
    protected $order;

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
    protected $pager;

    /**
     * 指示是否是分页查询
     *
     * @var boolean
     */
    protected $page_query = false;

    /**
     * 用于分页查询的 SQL
     *
     * @var string
     */
    protected $page_query_sql = '';

    /**
     * 添加 GROUP BY 子句
     *
     * @var string
     */
    protected $group;

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
    protected $count;

    /**
     * 统计平均值
     *
     * @var string
     */
    protected $avg;

    /**
     * 统计最大值
     *
     * @var string
     */
    protected $max;

    /**
     * 统计最小值
     *
     * @var string
     */
    protected $min;

    /**
     * 统计合计
     *
     * @var string
     */
    protected $sum;

    /**
     * 当前查询所服务的 ActiveRecord 继承类的元信息对象
     *
     * @var QDB_ActiveRecord_Meta
     */
    protected $meta;

    /**
     * 查询结果是否返回为数组
     *
     * @var boolean
     */
    protected $return_as_array = true;

    /**
     * 查询ID
     *
     * @var int
     */
    static private $query_id = 0;

    /**
     * 构造函数
     *
     * @param QDB_Table $table
     * @param array $links
     * @param array $where
     */
    protected function __construct(QDB_Table $table, array $links, array $where = null)
    {
        self::$query_id++;
        $this->links = $links;
        $this->table = $table;
        if (!empty($where)) {
            call_user_func_array(array($this, 'where'), $where);
        }
    }

    /**
     * 开始一个针对表数据入口的查询
     *
     * @param QDB_Table $table 查询哪一个表数据入口
     * @param array $links 查询中要使用的关联
     * @param array $where 指定查询条件
     *
     * @return QDB_Table_Select
     */
    static function beginQueryForTable(QDB_Table $table, array $links, array $where)
    {
        return new QDB_Table_Select($table, $links, $where);
    }

    /**
     * 开始一个针对 ActiveRecord 的查询
     *
     * @param QDB_ActiveRecord_Meta $meta 查询哪一个 ActiveRecord 继承类
     * @param array $where 指定查询条件
     *
     * @return QDB_Table_Select
     */
    static function beginQueryForActiveRecord(QDB_ActiveRecord_Meta $meta, array $where)
    {
        $select = new QDB_Table_Select($meta->table, $meta->table->links);
        $select->meta = $meta;
        $select->return_as_array = false;
        if (!empty($where)) {
            call_user_func_array(array($select, 'where'), $where);
        }
        return $select;
    }

    /**
     * 指定 SELECT 子句后要查询的内容
     *
     * @param array|string|QDB_Expr $expr 要查询的字段或表达式
     *
     * @return QDB_Table_Select
     */
    function select($expr = '*')
    {
        $this->select = $expr;
        return $this;
    }

    /**
     * 指定查询的排序
     *
     * @param string $expr 排序字段或表达式
     *
     * @return QDB_Table_Select
     */
    function order($expr)
    {
        $this->order = $expr;
        return $this;
    }

    /**
     * 指示查询所有符合条件的记录
     *
     * @return QDB_Table_Select
     */
    function all()
    {
        $this->limit = null;
        return $this;
    }

    /**
     * 限制查询结果总数
     *
     * @param int $count 只查询多少条数据
     * @param int $offset 从结果集的哪个位置开始查询（0 为第一条）
     *
     * @return QDB_Table_Select
     */
    function limit($count, $offset = 0)
    {
        $this->limit = array($count, $offset);
        return $this;
    }

    /**
     * 设置分页查询
     *
     * limitPage() 是用于分页查询的主要方法。
     * 使用时通常只需要指定 $page 和 $page_size 参数。
     *
     * $page 参数指定要查询哪一页的数据，$page_size 指定了页大小。
     * 默认情况下，$page 为 1 时表示要查询第 1 页。
     *
     * 如果希望用 $page = 0 来表示查询第一页，应该指定 $base 参数为 0。
     *
     * @param int $page 要查询的页码
     * @param int $page_size 页的大小
     * @param int $base 页码基数
     *
     * @return QDB_Table_Select
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
        $this->limit = array($page_size, ($page - $base) * $page_size);

        return $this;
    }

    /**
     * 获得分页信息
     *
     * 要使用该方法，必须先用 limitPage() 指定有效的分页参数。
     *
     * 该方法返回一个数组，包含下列信息：
     *
     * record_count: 符合查询条件的记录数
     * page_count: 按照页大小计算出来的总页数
     * first: 第一页的索引，等同于 limitPage() 的 $base 参数，默认为 1
     * last: 最后一页的索引
     * current: 当前页的索引
     * next: 下一页的索引
     * prev: 上一页的索引
     * page_size: 页大小
     * page_base: 页码基数（也就是第一页的索引值，默认为 1）
     *
     * 获得这个数组后，就可以通过 WebControls 或者其他途径构造分页导航条等用户界面内容。
     *
     * @return array
     */
    function getPager()
    {
        if (is_null($this->pager)) {
            $this->preparePagedQuery();
        }
        return $this->pager;
    }

    /**
     * 指定 GROUP BY 子句
     *
     * @param string $expr
     *
     * @return QDB_Table_Select
     */
    function group($expr)
    {
        $this->group = $expr;
        return $this;
    }

    /**
     * 是否构造一个 FOR UPDATE 查询
     *
     * 如果查询出记录后马上就要更新并写回数据库，则可以调用 forUpdate() 方法来指示这种情况。
     * 此时数据库会尝试对查询出来的记录加锁，避免在数据更新回数据库之前被其他查询改变。
     *
     * @param boolean $flag
     *
     * @return QDB_Table_Select
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
     * @return QDB_Table_Select
     */
    function distinct($flag = true)
    {
        $this->distinct = (bool)$flag;
        return $this;
    }


    /**
     * 统计符合条件的记录数
     *
     * @param string $expr 用于统计的字段名或表达式
     * @param string $alias 用什么别名在返回记录中保存统计结果
     *
     * @return QDB_Table_Select
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
     * @param string $expr 用于统计的字段名或表达式
     * @param string $alias 用什么别名在返回记录中保存统计结果
     *
     * @return QDB_Table_Select
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
     * @param string $expr 用于统计的字段名或表达式
     * @param string $alias 用什么别名在返回记录中保存统计结果
     *
     * @return QDB_Table_Select
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
     * @param string $expr 用于统计的字段名或表达式
     * @param string $alias 用什么别名在返回记录中保存统计结果
     *
     * @return QDB_Table_Select
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
     * @param string $expr 用于统计的字段名或表达式
     * @param string $alias 用什么别名在返回记录中保存统计结果
     *
     * @return QDB_Table_Select
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
     * 通常对于从 ActiveRecord 发起的查询不需要再调用该方法，QeePHP 会确保此类查询都返回对象。
     * 但如果是从表数据入口发起的查询，并且希望返回对象，就应该调用这个方法指定一个类名称。
     *
     * 类名称所指定的 ActiveRecord 继承类应该是一个适合返回结果数据结构的对象，否则会导致构造对象失败。
     *
     * @param string $class_name
     *
     * @return QDB_Table_Select
     */
    function asObject($class_name)
    {
        $this->meta = QDB_ActiveRecord_Meta::getInstance($class_name);
        $this->return_as_array = false;
        return $this;
    }

    /**
     * 指示将查询结果返回为数组
     *
     * 指示不管查询是由什么来源发起的，都将查询结果以数组方式返回。
     *
     * @return QDB_Table_Select
     */
    function asArray()
    {
        $this->meta = null;
        $this->return_as_array = true;
        return $this;
    }

    /**
     * 设置递归关联查询的层数（默认为1层）
     *
     * 假设 A 关联到 B，B 关联到 C，而 C 关联到 D。则通过 recursion 参数，
     * 我们可以指定从 A 出发的查询要到达哪一个关联层次才停止。
     *
     * 默认的 $recursion = 1，表示从 A 出发的查询只查询到 B 的数据就停止。
     *
     * 注意：对于来自 ActiveRecord 的查询，无需指定该参数。
     * 因为可以利用 ActiveRecord 的延迟加载能力自动查询更深层次的数据。
     *
     * @param int $recursion
     *
     * @return QDB_Table_Select
     */
    function recursion($recursion)
    {
        $this->recursion = abs(intval($recursion));
        return $this;
    }

    /**
     * 添加查询条件
     *
     * 为查询对象增加一个查询条件。多个查询条件之间以 AND 布尔运算符连接。
     *
     * 因此如果要添加一个
     *
     * @param array|string $where
     *
     * @return QDB_Table_Select
     */
    function where($where)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->whereArgs($where, $args);
    }

    /**
     * 指定 HAVING 子句的条件
     *
     * @param array|string $where
     * @param array $args
     *
     * @return QDB_Table_Select
     */
    function having($where)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->havingArgs($where, $args);
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
     * @return QDB_Table_Select
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
     * @param array|string $included_links 指定查询时要包含的关联
     *
     * @return mixed
     */
    function query($included_links = null)
    {
        if ($this->return_as_array) {
            return $this->queryArray();
        } else {
            return $this->queryObjects();
        }
    }

    /**
     * 查询，并返回数组结果
     *
     * @param boolean $clean_up 是否清理数据集中的临时字段
     *
     * @return array
     */
    function queryArray($clean_up = true)
    {
        list($handle, $find_links) = $this->getQueryHandle();
        /* @var $handle QDB_Result_Abstract */

        if ($this->recursion > 0 && !empty($find_links)) {
            // 对关联表进行查询，并组装数据
            $refs_value = null;
            $refs = null;
            $used_alias = array_keys($find_links);
            $rowset = $handle->fetchAllRefby($used_alias, $refs_value, $refs, $clean_up);
            $keys = array_keys($rowset);

            // 进行关联查询，并组装数据集
            foreach ($find_links as $link) {
                /* @var $link QDB_Table_Link_Abstract */
                foreach ($keys as $key) {
                    $rowset[$key][$link->mapping_name] = $link->one_to_one ? null : array();
                }

                $tka = $link->target_key_alias;
                $ska = $link->source_key_alias;
                if (empty($refs_value[$ska])) { continue; }

                $select = $link->target_table->find("[{$link->target_key}] IN (?)", $refs_value[$ska])
                                             ->recursion($this->recursion - 1)
                                             ->recursionForTarget($link)
                                             ->order($link->on_find_order)
                                             ->select($link->on_find_keys)
                                             ->where($link->on_find_where);
                if ($link->type == QDB::MANY_TO_MANY) {
                    // SELECT {$fields} FROM {$this->joinTDG->qtableName} INNER JOIN {$this->assocTDG->qtableName} ON {$this->assocTDG->qpk} = {$this->qassocForeignKey}
                    $select->join($link->mid_table->table_name, "[{$link->mid_target_key}] = [{$link->target_key}]");
                }
                if (is_int($link->on_find) || is_array($link->on_find)) {
                    $select->limit($link->on_find);
                } else {
                    $select->all();
                }

                $target_rowset = $select->queryArray(false);
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

        if (isset($row)) {
            return $row;
        } else {
            return $rowset;
        }
    }

    /**
     * 查询，并返回对象或对象集合
     *
     * @param boolean $coll_as_array
     *
     * @return QColl|QDB_ActiveRecord_Abstract
     */
    function queryObjects($coll_as_array = false)
    {
        /**
         * 执行查询，获得一个查询句柄
         *
         * $find_links 是查询涉及到的关联（关联别名 => 关联对象）
         */

        list($handle, $find_links) = $this->getQueryHandle(Q::normalize($this->table->pk));
        /* @var $handle QDB_Result_Abstract */

        /**
         * $batch_refs_value 是一个二维数组
         *
         * 格式是：
         *
         *    link1 mapping_name => array(
         *      source_key_value => 对象ID
         *      ....
         *    ),
         *    link2 mapping_name => array(
         *    ),
         */
        $batch_refs_value = null;
        $class_name = $this->meta->class_name;
        $rowset = array();
        while (($row = $handle->fetchRow())) {
            $obj = new $class_name($row);
            $id = $obj->id();
            foreach ($find_links as $alias_name => $link) {
                $batch_refs_value[$link->mapping_name][$row[$alias_name]] = $id;
            }
            $rowset[] = $obj;
        }

        if (empty($rowset)) {
            // 没有查询到数据时，返回 Null 对象或空集合
            if ($this->limit == 1) {
                return $this->meta->newNullObject();
            } else {
                return new QColl($this->meta->class_name);
            }
        }

        if ($this->limit == 1) {
            // 创建一个单独的对象
            $this->meta->register($rowset[0], $batch_refs_value, self::$query_id);
            return $rowset[0];
        } else{
            foreach ($rowset as $obj) {
                $this->meta->register($obj, $batch_refs_value, self::$query_id);
            }

            if ($coll_as_array) {
                return $rowset;
            } else {
                return QColl::createFromArray($rowset, $class_name);
            }
        }
    }

    /**
     * 查询，并返回对象或对象集合
     *
     * @param string $target_key
     * @param string $target_key_alias
     * @param QDB_ActiveRecord_Meta $target_meta
     *
     * @return array
     */
    function queryObjectsForAssemble($target_key, $target_key_alias, $target_meta)
    {
        /**
         * 与 queryObjects() 的区别在于，queryObjectsForAssemble() 会查询出关联的 target_key_value
         * 并且返回结果按照 taget_key_value 进行了分组
         *
         *   target_key_value => array(
         *     array(
         *         object,
         *         ....
         *     )
         *   ),
         */
        list($handle, $find_links) = $this->getQueryHandle(array($target_key => $target_key_alias, $this->table->pk));
        /* @var $handle QDB_Result_Abstract */

        $batch_refs_value = null;
        $rowset = array();
        $class_name = $target_meta->class_name;

        while (($row = $handle->fetchRow())) {
            $obj = new $class_name($row);
            $id = $obj->id();
            foreach ($find_links as $alias_name => $link) {
                $batch_refs_value[$link->mapping_name][$row[$alias_name]] = $id;
            }
            $rowset[$row[$target_key_alias]][] = $obj;
        }

        return $rowset;
    }

    /**
     * 返回完整的 SQL 语句
     *
     * @return string
     */
    function toString()
    {
        list($sql) = $this->toStringInternal();
        return $sql;
    }

    /**
     * 返回查询语句以及相关关联的信息
     *
     * @param array $more_keys
     *
     * @return array
     */
    function toStringInternal(array $more_keys = array())
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

        foreach ($more_keys as $key => $alias) {
            if (!is_int($key) && $key != $alias) {
                $strings['select'][] = $this->table->qfields($key) . ' AS ' . $this->table->conn->qfield($alias);
            } else {
                $strings['select'][] = $this->table->qfields($alias);
            }
        }

        /**
         * 1.1 确定要查询的关联，从而确保查询主表时能够得到相关的关联字段
         */
        if ($this->recursion > 0) {
            foreach ($this->links as $link) {
                /* @var $link QDB_Table_Link_Abstract */
                $link->init();
                if (!$link->enabled || $link->on_find === false || $link->on_find == 'skip') { continue; }
                $link->init();
                $strings['select'][] = $link->source_table->qfields($link->source_key) .
                                       ' AS ' .
                                       $link->source_table->conn->qfield($link->source_key_alias);
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
                                   $link->target_table->conn->qfield($link->target_key_alias);
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
         * 6. 开始构造 SQL（查询和用于分页统计的两种）
         */
        $arr = array();
        $arr[] = 'SELECT ';
        if ($this->distinct) { $arr[] = 'DISTINCT '; }
        $arr[] = implode(',' , $strings['select']);

        // FROM
        $arr[] = " FROM {$this->table->qtable_name}";

        // JOIN
        $joined = array();
        foreach ($used_links as $links) {
            foreach ($links as $mapping_name) {
                if (isset($joined[$mapping_name])) { continue; }
                $joined[$mapping_name] = $this->table->getLink($mapping_name)->init()->getJoinSQL();
            }
        }
        if (!empty($joined)) {
            $arr[] = ' ' . implode(' ', $joined);
        }

        // WHERE ...
        $arr[] = $strings['where'];
        $arr[] = $strings['group'];
        $arr[] = $strings['having'];
        $arr[] = $strings['order'];

        $sql = implode('', $arr);
        if ($this->for_update) { $sql .= ' FOR UPDATE'; }
        $arr[1] = 'COUNT(*) as total_count';
        $this->page_query_sql = implode('', $arr);

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
                $table = $this->table->full_table_name;
            }

            if ($table && isset($this->links[$table])) {
                $used_links[] = $table;
                $table = $this->links[$table]->target_table->full_table_name;
            }

            if ($alias) {
                $parts[] = $this->table->conn->qfield($field, $table) .
                           ' AS ' .
                           $this->table->conn->qfield($alias);
            } else {
                $parts[] = $this->table->conn->qfield($field, $table);
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
        if (empty($this->page_query_sql)) {
            $this->toStringInternal();
        }
        $count = (int)$this->table->conn->getOne($this->page_query_sql);

        $pager = array();
        $pager['record_count'] = $count;
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

    /**
     * 执行查询，返回结果句柄
     *
     * @param array $more_keys
     *
     * @return array of list($handle, $find_links)
     */
    protected function getQueryHandle(array $more_keys = array())
    {
        // 构造查询 SQL，并取得查询中用到的关联
        list($sql, $find_links) = $this->toStringInternal($more_keys);

        // 对主表进行查询
        if (!is_array($this->limit)) {
            if (is_null($this->limit)) {
                $handle = $this->table->conn->execute($sql);
            } else {
                $handle = $this->table->conn->selectLimit($sql, intval($this->limit));
            }
        } else {
            list($count, $offset) = $this->limit;
            $handle = $this->table->conn->selectLimit($sql, $count, $offset);
        }

        return array($handle, $find_links);
    }
}
