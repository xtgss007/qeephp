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
 * 定义 QDB_Table 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Table 类（表数据入口）封装数据表的 CRUD 操作，并提供了扩展接口。
 *
 * @package database
 */
class QDB_Table implements QDB_Link_Consts
{
    /**
     * 数据表的 schema
     *
     * @var string
     */
    public $schema = '';

    /**
     * 不包含前缀的数据表名称
     *
     * @var string
     */
    public $table_name;

    /**
     * 包含前缀的数据表名称
     *
     * @var string
     */
    public $full_table_name;

    /**
     * 数据表的完全限定名（不包含 schema）
     *
     * @var string
     */
    public $qtable_name;

    /**
     * 主键字段名
     *
     * @var string|array
     */
    public $pk;

    /**
     * 转义后的主键字段名
     *
     * @var string|array
     */
    public $qpk;

    /**
     * 定义 HAS ONE 关联
     *
     * @var array
     */
    protected $has_one;

    /**
     * 定义 HAS MANY 关联
     *
     * @var array
     */
    protected $has_many;

    /**
     * 定义 BELONGS TO 关联
     *
     * @var array
     */
    protected $belongs_to;

    /**
     * 定义 MANY TO MANY 关联
     *
     * @var array
     */
    protected $many_to_many;

    /**
     * 创建记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    protected $created_time_fields = array('created', 'updated');

    /**
     * 创建和更新记录时，要自动填入当前时间的字段
     *
     * 只要数据表具有下列字段之一，则调用 create() 方法创建记录或 update() 更新记录时，
     * 将以服务器时间自动填充该字段。
     *
     * @var array
     */
    protected $updated_time_fields = array('updated');

    /**
     * 保存该表数据入口的所有关联
     *
     * @var array
     */
    protected $links = array();

    /**
     * 指示是否使用了复合主键
     *
     * @var boolean
     */
    protected $is_cpk;

    /**
     * 指示主键字段的总数
     *
     * @var int
     */
    protected $pk_count;

    /**
     * 数据访问对象
     *
     * @var QDB_Adapter_Abstract
     */
    protected $conn;

    /**
     * 当前表数据入口对象元信息的缓存id
     *
     * @var string
     */
    protected $meta_cache_id;

    /**
     * 当前数据表的元数据
     *
     * 元数据是一个二维数组，每个元素的键名就是全小写的字段名，而键值则是该字段的数据表定义。
     *
     * @var array
     */
    static private $tables_meta;

    /**
     * 构造 Table 实例
     *
     * $params 参数允许有下列选项：
     *   - schema:          指定数据表的 schema
     *   - table_name:      指定数据表的名称
     *   - full_table_name: 指定数据表的完整名称
     *   - pk:              指定主键字段名
     *   - conn:            指定数据库访问对象
     *
     * @param array $params
     *
     * @return Table
     */
    function __construct(array $params = null)
    {
        if (!empty($params['schema'])) {
            $this->schema = $params['schema'];
        }
        if (!empty($params['table_name'])) {
            $this->table_name = $params['table_name'];
        }
        if (!empty($params['full_table_name'])) {
            $this->full_table_name = $params['full_table_name'];
            if (empty($this->table_name)) {
                $this->table_name = $this->full_table_name;
            }
        }
        if (!empty($params['pk'])) {
            $this->pk = $params['pk'];
        }
        if (!empty($params['conn'])) {
            $this->setConn($params['conn']);
        } else {
            $this->setupConn();
        }
        $this->connect();
        $this->relinks();
    }

    /**
     * 根据类定义的 $has_one、$has_many、$belongs_to 和 $many_to_many 成员变量重建所有关联
     */
    function relinks()
    {
        $this->removeAllLinks();
        if (is_array($this->has_one)) {
            $this->createLinks($this->has_one, self::has_one);
        }
        if (is_array($this->belongs_to)) {
            $this->createLinks($this->belongs_to, self::belongs_to);
        }
        if (is_array($this->has_many)) {
            $this->createLinks($this->has_many, self::has_many);
        }
        if (is_array($this->many_to_many)) {
            $this->createLinks($this->many_to_many, self::many_to_many);
        }
    }

    /**
     * 建立关联
     *
     * @param array $links_params
     * @param const $type
     */
    function createLinks(array $links_params, $type)
    {
        if (!is_array(reset($links_params))) {
            $links_params = array($links_params);
        }
        foreach ($links_params as $params) {
            $link = QDB_Table_Link_Abstract::createLink($type, $params, $this);
            $this->links[$link->mapping_name] = $link;
        }
    }

    /**
     * 检查指定名称的单个关联是否存在
     *
     * @param string $mapping_name
     *
     * @return boolean
     */
    function existsLink($mapping_name)
    {
        return isset($this->links[$mapping_name]);
    }

    /**
     * 返回所有关联
     *
     * @return array
     */
    function getAllLinks()
    {
        return $this->links;
    }

    /**
     * 返回所有关联的名字
     *
     * @return array
     */
    function getAllLinksName()
    {
        return array_keys($this->links);
    }

    /**
     * 返回指定名称的关联，如果关联不存在则抛出异常
     *
     * @param string $mapping_name
     *
     * @return QDB_Table_Link_Abstract
     */
    function getLink($mapping_name)
    {
        if (isset($this->links[$mapping_name])) {
            return $this->links[$mapping_name];
        }
        // LC_MSG: 指定的关联 "%s" 不存在.
        throw new QDB_Table_Exception(__('指定的关联 "%s" 不存在.', $mapping_name));
    }

    /**
     * 允许指定名称的关联，如果关联不存在则抛出异常
     *
     * @param array|string $mapping_names
     */
    function enableLinks($mapping_names)
    {
        if (!is_array($mapping_names)) {
            $mapping_names = Q::normalize($mapping_names);
        }
        foreach ($mapping_names as $mapping_name) {
            if (isset($this->links[$mapping_name])) {
                $this->links[$mapping_name]->enabled = true;
            } else {
                // LC_MSG: 指定的关联 "%s" 不存在.
                throw new QDB_Table_Exception(__('指定的关联 "%s" 不存在.', $mapping_name));
            }
        }
    }

    /**
     * 启用所有关联
     */
    function enableAllLinks()
    {
        foreach ($this->links as $link) {
            /* @var $link QDB_Table_Link_Abstract */
            $link->enabled = true;
        }
    }

    /**
     * 禁用指定名称的关联，如果关联不存在则抛出异常
     *
     * @param array|string $mapping_names
     */
    function disableLinks($mapping_names)
    {
        if (!is_array($mapping_names)) {
            $mapping_names = Q::normalize($mapping_names);
        }
        foreach ($mapping_names as $mapping_name) {
            if (isset($this->links[$mapping_name])) {
                $this->links[$mapping_name]->enabled = false;
            } else {
                // LC_MSG: 指定的关联 "%s" 不存在.
                throw new QDB_Table_Exception(__('指定的关联 "%s" 不存在.', $mapping_name));
            }
        }
    }

    /**
     * 禁用所有关联
     */
    function disableAllLinks()
    {
        foreach ($this->links as $link) {
            /* @var $link QDB_Table_Link_Abstract */
            $link->enabled = false;
        }
    }

    /**
     * 清除指定名称的关联，如果关联不存在则抛出异常
     *
     * @param array|string $mapping_names
     */
    function removeLinks($mapping_names)
    {
        if (!is_array($mapping_names)) {
            $mapping_names = Q::normalize($mapping_names);
        }
        foreach ($mapping_names as $mapping_name) {
            if (isset($this->links[$mapping_name])) {
                unset($this->links[$mapping_name]);
            } else {
                // LC_MSG: 指定的关联 "%s" 不存在.
                throw new QDB_Table_Exception(__('指定的关联 "%s" 不存在.', $mapping_name));
            }
        }
    }

    /**
     * 清除表数据入口对象实例的所有关联
     */
    function removeAllLinks()
    {
        $this->links = array();
    }

    /**
     * 创建一个查询对象
     *
     * @param string|array $where
     *
     * @return QDB_Select
     */
    function find($where = null)
    {
        $args = func_get_args();
        array_shift($args);
        return QDB_Select::beginSelectFromTable($this, $where, $args, $this->links);
    }

    /**
     * 直接执行一个 SQL 语句，并返回结果集（数组）
     *
     * @param string $sql
     * @param array $args
     *
     * @return array
     */
    function findBySQL($sql)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->conn->getAll($sql, $args);
    }

    /**
     * 创建一条新记录，返回新记录的主键值
     *
     * @param array $row
     * @param int $recursion 保存记录时，递归多少层关联
     *
     * @return mixed
     */
    function create(array $row, $recursion = 99)
    {
        if ($this->is_cpk) {
            // 复合主键
            // 对于包含空值（null、0、空字符串）的主键字段，一律清除，以便让数据库自动填充
            $insert_id = array();
            foreach ($this->pk as $pk) {
                if (empty($row[$pk])) {
                    unset($row[$pk]);
                } else {
                    $insert_id[$pk] = $row[$pk];
                }
            }
        } else {
            // 如果只有一个主键字段，并且主键字段不是自增，则通过 nextID() 获得一个主键字段值
            if (empty($row[$this->pk])) {
                unset($row[$this->pk]);
                if (!self::$tables_meta[$this->meta_cache_id][$this->pk]['auto_incr']) {
                    $row[$this->pk] = $this->nextID($this->pk);
                    $insert_id = $row[$this->pk];
                }
            } else {
                $insert_id = $row[$this->pk];
            }
        }

        // 填充当前时间
        $this->fillFieldsWithCurrentTime($row, $this->created_time_fields);
        // 创建 INSERT 语句并执行
        list($sql, $values) = $this->conn->getInsertSQL($row,
                                                       $this->full_table_name,
                                                       $this->schema,
                                                       self::$tables_meta[$this->meta_cache_id]);
        $this->conn->execute($sql, $values);

        // 创建主表的记录成功后，尝试获取新记录的主键值
        if (!isset($insert_id)) {
            // 只有单一主键，且没有指定主键值时，!isset($insert_id) 的结果才是真
            $insert_id = $this->conn->insertID();
            $row[$this->pk] = $insert_id;
        }

        if ($recursion > 0) {
            foreach ($this->links as $link) {
                /* @var $link QDB_Table_Link_Abstract */
                if (!isset($row[$link->mapping_name])) { continue; }
                if (!is_array($row[$link->mapping_name])) {
                    // LC_MSG: 关联操作要求 $row 数组中的 "%s" 字段必须是一个数组.
                    throw new QDB_Table_Exception(__('关联操作要求 $row 数组中的 "%s" 字段必须是一个数组.',
                                                     $link->mapping_name));
                }

                $link->init();
                if (empty($row[$link->source_key])) {
                    // LC_MSG: 保存关联记录 "%s" 需要 $row 数组中有名为 "%s" 的字段值.
                    throw new QDB_Table_Link_Exception(__('保存关联记录 "%s" 需要 $row 数组中有名为 "%s" 的字段值.',
                                                          $link->mapping_name,
                                                          $link->source_key));
                }

                $link->saveTargetData($row[$link->mapping_name], $row[$link->source_key], $recursion - 1);
            }
        }

        return $insert_id;
    }

    /**
     * 批量创建新记录，并返回包含新记录主键值的数组
     *
     * @param array $rowset
     * @param int $recursion 保存记录时，递归多少层关联
     *
     * @return array
     */
    function createRowset(array $rowset, $recursion = 99)
    {
        $return = array();
        foreach (array_keys($rowset) as $offset) {
            $return[] = $this->create($rowset[$offset], $recursion);
        }
        return $return;
    }

    /**
     * 更新一条记录，返回被更新记录的总数
     *
     * @param array $row
     * @param int $recursion 保存记录时，递归多少层关联
     *
     * @return int
     */
    function update(array $row, $recursion = 99)
    {
        // TODO: update() 实现对复合主键的处理

        $this->fillFieldsWithCurrentTime($row, $this->updated_time_fields);
        list($sql, $values) = $this->conn->getUpdateSQL($row,
                                                       $this->pk,
                                                       $this->full_table_name,
                                                       $this->schema,
                                                       self::$tables_meta[$this->meta_cache_id]);
        $this->conn->execute($sql, $values);

        if ($recursion > 0) {
            foreach ($this->links as $link) {
                /* @var $link QDB_Table_Link_Abstract */
                if (!isset($row[$link->mapping_name])) { continue; }
                if (!is_array($row[$link->mapping_name])) {
                    // LC_MSG: 关联操作要求 $row 数组中的 "%s" 字段必须是一个数组.
                    throw new QDB_Table_Exception(__('关联操作要求 $row 数组中的 "%s" 字段必须是一个数组.',
                                                     $link->mapping_name));
                }

                $link->init();
                if (empty($row[$link->source_key])) {
                    // LC_MSG: 保存关联记录 "%s" 需要 $row 数组中有名为 "%s" 的字段值.
                    throw new QDB_Table_Link_Exception(__('保存关联记录 "%s" 需要 $row 数组中有名为 "%s" 的字段值.',
                                                          $link->mapping_name,
                                                          $link->source_key));
                }

                $link->saveTargetData($row[$link->mapping_name], $row[$link->source_key], $recursion - 1);
            }
        }

        return $this->conn->affectedRows();
    }

    /**
     * 批量更新记录，返回被更新记录的总数
     *
     * @param array $rowset
     * @param int $recursion 保存记录时，递归多少层关联
     *
     * @return int
     */
    function updateRowset(array $rowset, $recursion = 99)
    {
        $update_count = 0;
        foreach (array_keys($rowset) as $offset) {
            $update_count += (int)$this->update($rowset[$offset], $recursion);
        }
        return $update_count;
    }

    /**
     * 更新所有符合条件的记录，返回被更新记录的总数
     *
     * @param array $pairs 要更新字段的值
     * @param array|string $where
     *
     * @return int
     */
    function updateWhere(array $pairs, $where)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        list($where, ) = $this->parseSQLInternal($where, $args);
        list($holders, $values) = $this->conn->getPlaceholderPairs($pairs);
        $sql = "UPDATE {$this->qtable_name} SET " . implode(',', $holders);
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $this->conn->execute($sql, $values);
        return $this->conn->affectedRows();
    }

    /**
     * 执行一个 UPDATE 操作，并且确保同时更新记录的 updated 字段，返回被更新记录的总数
     *
     * @param string $sql
     *
     * @return int
     */
    function updateBySQL($sql)
    {
        $parts = preg_split('/[ \n]set[ \n]/i', $sql);
        $sql = $parts[0] . ' SET ' . $this->getUpdatedTimeSQL() . ', ';
        for ($i = 1, $max = count($parts); $i < $max; $i++) {
            $sql .= $parts[$i];
        }
        $this->conn->execute($sql);
        return $this->conn->affectedRows();
    }

    /**
     * 增加所有符合条件记录的指定字段值，返回被更新记录的总数
     *
     * @param string $field
     * @param int $step
     * @param array|string $where
     *
     * @return mixed
     */
    function incrWhere($field, $step = 1, $where)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        return $this->incrOrDecrWhere($field, $step, true, $where, $args);
    }

    /**
     * 减小所有符合条件记录的指定字段值，返回被更新记录的总数
     *
     * @param string $field
     * @param int $step
     * @param array|string $where
     *
     * @return mixed
     */
    function decrWhere($field, $step = 1, $where)
    {
        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_shift($args);
        return $this->incrOrDecrWhere($field, $step, false, $where, $args);
    }

    /**
     * 根据是否包含主键字段值，创建或更新一条记录，返回记录的主键值
     *
     * @param array $row
     * @param int $recursion 保存记录时，递归多少层关联
     * @param string $method
     *
     * @return mixed
     */
    function save(array $row, $recursion = 99, $method = 'save')
    {
        if ($this->is_cpk) {
            // 如果是复合主键，并且需要自动判断使用 create() 或 update()，则抛出异常
            if ($method == 'save' || $method == 'only_create' || $method == 'only_update') {
                // LC_MSG: QDB_Table::save() 对复合主键的支持尚未实现.
                throw new QDB_Table_Exception(__('QDB_Table::save() 对复合主键的支持尚未实现.'));
            }
        }

        if ($method == 'create') {
            return $this->create($row, $recursion);
        } elseif ($method == 'update') {
            return $this->update($row, $recursion);
        } elseif ($method == 'replace') {
            return $this->replace($row);
        }

        if (empty($row[$this->pk]) && ($method == 'save' || $method == 'only_create')) {
            return $this->create($row, $recursion);
        } else {
            return $this->update($row, $recursion);
        }
    }

    /**
     * 批量保存记录集，返回所有记录的主键值
     *
     * @param array $rowset
     * @param int $recursion 保存记录时，递归多少层关联
     * @param string $method
     *
     * @return array
     */
    function saveRowset(array $rowset, $recursion = 99, $method = 'save')
    {
        $return = array();
        foreach (array_keys($rowset) as $offset) {
            $return[] = $this->save($rowset[$offset], $recursion, $method);
        }
        return $return;
    }

    /**
     * 用 SQL 的 REPLACE 操作替换一条现有记录或插入新记录，返回被影响的记录数
     *
     * @param array $row
     *
     * @return mixed
     */
    function replace(array $row)
    {
        $this->fillFieldsWithCurrentTime($row, $this->created_time_fields);
        $sql = $this->conn->getReplaceSQL($row, $this->full_table_name, $this->schema);
        $this->conn->execute($sql, $row);
        return $this->conn->affectedRows();
    }

    /**
     * 替换一组现有记录或插入新记录，返回被影响的记录总数
     *
     * @param array $rowset
     *
     * @return array
     */
    function replaceRowset(array $rowset)
    {
        $update_count = 0;
        foreach (array_keys($rowset) as $offset) {
            $update_count += (int)$this->replace($rowset[$offset], true);
        }
        return $update_count;
    }

    /**
     * 删除指定主键值的记录，返回被删除的记录总数
     *
     * @param mixed $pkv
     * @param int $recursion
     *
     * @return int
     */
    function remove($pkv, $recursion = 99)
    {
        // TODO: remove() 实现对复合主键的处理
        return $this->removeByField($this->pk, $pkv, $recursion);
    }

    /**
     * 删除指定字段值的记录，返回被删除的记录总数
     *
     * @param string $field
     * @param mixed $field_value
     * @param int $recursion
     *
     * @return int
     */
    function removeByField($field, $field_value, $recursion = 99)
    {
        // TODO: removeByField() 实现对多个字段的处理

        $qfield = $this->conn->qfield($field);
        if (is_array($field_value)) {
            $fvs = ' IN (' . implode(', ', array_map(array($this->conn, 'qstr'), $field_value)) . ')';
        } else {
            $fvs = ' = ' . $this->conn->qstr($field_value);
        }

        if ($recursion > 0) {
            $used_fields = array();
            foreach ($this->links as $name => $link) {
                /* @var $link QDB_Table_Link_Abstract */
                $link->init();
                if ($link->on_delete == 'reject') {
                    // LC_MSG: 表数据入口 "%s" 的关联 "%s" 拒绝对数据表 "%s" 记录的删除操作.
                    throw new QDB_Table_Exception(__('表数据入口 "%s" 的关联 "%s" 拒绝对数据表 "%s" 记录的删除操作.',
                                                  $this->table_name, $link->mapping_name, $this->full_table_name));
                }
                if ($link->on_delete === false || $link->on_delete == 'skip') {
                    continue;
                }
                if ($link->source_key == $field) {
                    // 如果关联字段和指定字段相同，则无需查询关联字段值
                    $link->removeAssocData($field_value, $recursion - 1);
                } else {
                    $used_fields[$name] = $link->source_key;
                }
            }

            if (!empty($used_fields)) {
                $sql = 'SELECT ' . $this->conn->qfields(array_values($used_fields)) . " FROM {$this->qtable_name} WHERE {$qfield} {$fvs}";

                // 查询出删除关联表记录需要的关联键值
                $mkv = (array)$this->conn->getAll($sql);
                $akv = array();
                foreach ($mkv as $row) {
                    foreach ($used_fields as $name => $field) {
                        if (is_null($row[$field])) { continue; }
                        $akv[$name][] = $row[$field];
                    }
                }
                foreach ($used_fields as $name => $field) {
                    /* @var $link QDB_Table_Link_Abstract */
                    $link = $this->links[$name];
                    if (empty($akv[$field])) { continue; }
                    $link->removeAssocData($akv[$field], $recursion - 1);
                }
            }
        }

        // 删除主表记录
        $sql = "DELETE FROM {$this->qtable_name} WHERE {$qfield} {$fvs}";
        $this->conn->execute($sql);
        return $this->conn->affectedRows();
    }

    /**
     * 删除所有符合条件的记录，返回被删除的记录总数
     *
     * @param mixed $where
     *
     * @return ini
     */
    function removeWhere($where)
    {
        $args = func_get_args();
        array_shift($args);
        list($where, ) = $this->parseSQLInternal($where, $args);
        $sql = "DELETE FROM {$this->qtable_name}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $this->conn->execute($sql);
        return $this->conn->affectedRows();
    }

    /**
     * 为当前数据表产生一个新的主键值
     *
     * @param $field_name
     *
     * @return mixed
     */
    function nextID($field_name = '')
    {
        return $this->conn->nextID($this->full_table_name, $field_name, $this->schema);
    }

    /**
     * 返回所有字段的元数据
     *
     * @return array
     */
    function columns()
    {
        return self::$tables_meta[$this->meta_cache_id];
    }

    /**
     * 确认是否已经连接到数据库
     *
     * @return boolean
     */
    function isConnected()
    {
        return $this->conn->isConnected();
    }

    /**
     * 确认是否是复合主键
     *
     * @return boolean
     */
    function isCompositePK()
    {
        return $this->is_cpk;
    }

    /**
     * 返回该表数据入口对象使用的数据访问对象
     *
     * @return QDB_Adapter_Abstract
     */
    function getConn()
    {
        return $this->conn;
    }

    /**
     * 设置数据库访问对象
     *
     * @param QDB_Adapter_Abstract $conn
     */
    function setConn(QDB_Adapter_Abstract $conn)
    {
        $this->conn = $conn;
        if (empty($this->schema) && $conn->getSchema() != '') {
            $this->schema = $conn->getSchema();
        }
        if (empty($this->full_table_name) && $conn->getTablePrefix() != '') {
            $this->full_table_name = $conn->getTablePrefix() . $this->table_name;
        } elseif (empty($this->full_table_name)) {
            $this->full_table_name = $this->table_name;
        }
        $this->qtable_name = $this->conn->qtable($this->full_table_name);
    }

    /**
     * 连接到数据库
     */
    function connect()
    {
        if (!$this->conn->isConnected()) {
            $this->conn->connect();
        }
        $this->meta_cache_id = $this->conn->getID() . '/' . $this->qtable_name;
        $this->prepareMeta();

        // 尝试自动设置主键字段
        if (empty($this->pk)) {
            $this->pk = array();
            foreach (self::$tables_meta[$this->meta_cache_id] as $field) {
                if ($field['pk']) {
                    $this->pk[] = $field['name'];
                }
            }
        }

        // 处理主键字段
        $pk = Q::normalize($this->pk);

        if (empty($pk)) {
            // LC_MSG: 数据表 "%s" 没有指定主键.
            throw new QDB_Table_Exception(__('数据表 "%s" 没有指定主键.', $this->full_table_name));
        }

        $this->pk_count = count($pk);
        $this->is_cpk = $this->pk_count > 1;
        $this->pk = ($this->is_cpk) ? $pk : reset($pk);

        $this->qpk = ($this->is_cpk) ?
                     $this->conn->qfields($this->pk, $this->full_table_name, null, true) :
                     $this->conn->qfield($this->pk, $this->full_table_name);

        // 过滤 created_time_fields 和 updated_time_fields
        foreach ($this->created_time_fields as $offset => $field) {
            if (!isset(self::$tables_meta[$this->meta_cache_id][strtolower($field)])) {
                unset($this->created_time_fields[$offset]);
            }
        }
        foreach ($this->updated_time_fields as $offset => $field) {
            if (!isset(self::$tables_meta[$this->meta_cache_id][strtolower($field)])) {
                unset($this->updated_time_fields[$offset]);
            }
        }
    }

    /**
     * 转义字段
     *
     * @param string|array $fields
     * @param boolean $return_array
     *
     * @return string|array
     */
    function qfields($fields, $return_array = false)
    {
        if (!is_array($fields)) {
            $fields = Q::normalize($fields);
        }
        $return = array();
        foreach ($fields as $key => $value) {
            if (is_string($key)) {
                $field = $this->conn->qfield($key, $this->full_table_name, $this->schema);
                $return[] =  $field . ' AS ' . $this->conn->qfield($value);
            } else {
                $return[] = $this->conn->qfield($value, $this->full_table_name, $this->schema);
            }
        }

        return ($return_array) ? $return : implode(', ', $return);
    }

    /**
     * 分析查询条件和参数
     *
     * @param array|string $sql
     *
     * @return string
     */
    function parseSQL($sql)
    {
        $args = func_get_args();
        array_shift($args);
        $ret = $this->parseSQLInternal($sql, $args);
        return $ret[0];
    }

    /**
     * 分析查询条件和参数内部版本
     *
     * 与 parseSQL() 的区别在于 parseSQLInternal() 用第二参数来传递所有的占位符参数。
     * 并且 parseSQLInternal() 的返回结果是一个数组，
     * 分别由处理后的 SQL 语句、从 SQL 语句中分析出来的关联名称、分析用到的参数个数组成。
     *
     * list($sql, $used_links, $args_count) = parseSQLInternal(...)
     *
     * @param mixed $sql
     * @param array $args
     *
     * @return array
     */
    function parseSQLInternal($sql, array $args = null)
    {
        if (is_int($sql)) {
            return array("{$this->qpk} = {$sql}", array(), null);
        }
        if (empty($sql)) { return array(null, null, null); }
        if (is_null($args)) {
            $args = array();
        }

        if (is_array($sql)) {
            return $this->parseSQLArray($sql, $args);
        } else {
            return $this->parseSQLString($sql, $args);
        }
    }

    /**
     * 设置表数据入口要使用的数据库访问对象
     */
    protected function setupConn()
    {
        $conn = QDB::getConn();
        $this->setConn($conn);
    }

    /**
     * 按照模式2对查询条件进行分析
     *
     * @param array $sql
     * @param array $args
     *
     * @return array|string
     */
    protected function parseSQLArray(array $arr, array $args = null)
    {
        static $keywords;

        if (is_null($keywords)) {
            $keywords = explode(' ', '( AND OR NOT BETWEEN CASE && || = <=> >= > <= < <> != IS LIKE');
            $keywords = array_flip($keywords);
        }

        $parts = array();
        $callback = array($this->conn, 'qstr');
        $next_op = '';
        $args_count = 0;
        $used_links = array();

        foreach ($arr as $key => $value) {
            if (is_int($key)) {
                /**
                 * 如果键名是整数，则判断键值是否是 “)”。
                 * 对于其他值，则假定为需要再分析的 SQL。
                 * 因此再次调用 parseSQLInternal() 分析。
                 */
                if (isset($keywords[$value])) {
                    $next_op = '';
                    $sql = $value;
                } elseif ($value == ')') {
                    $next_op = 'AND';
                    $sql = $value;
                } else {
                    if ($next_op != '') {
                        $parts[] = $next_op;
                    }
                    list($sql, , $args_count) = $this->parseSQLInternal($value, $args);
                    if (empty($sql)) { continue; }
                    if ($args_count > 0) {
                        $args = array_slice($args, $args_count);
                    }
                    $next_op = 'AND';
                }
                $parts[] = $sql;
            } else {
                /**
                 * 如果键名是字符串，则假定为字段名
                 */
                if ($next_op != '') {
                    $parts[] = $next_op;
                }
                $field = $this->parseSQLQfield($key);
                if (is_array($value)) {
                    // 如果 $value 是数组，则假定为 IN (??, ??) 表达式
                    $value = array_unique($value);
                    $value = array_map($callback, $value);
                    $parts[] = $field . ' IN (' . implode(',', $value) . ')';
                } else {
                    $value = $this->conn->qstr($value);
                    $parts[] = $field . ' = ' . $value;
                }
                $next_op = 'AND';
            }
        }

        return array(implode(' ', $parts), $used_links, $args_count);
    }

    /**
     * 按照模式1对查询条件进行分析
     *
     * @param string $where
     * @param array $args
     *
     * @return array|string
     */
    protected function parseSQLString($where, array $args = null)
    {
        // 替换宏
        if (!$this->is_cpk) {
            $where = str_replace('%PK%', '[' . $this->pk . ']', $where);
        }
        $matches = array();
        preg_match_all('/\[[a-z][a-z0-9_\.]*\]/i', $where, $matches, PREG_OFFSET_CAPTURE);
        $matches = reset($matches);

        $out = '';
        $offset = 0;
        $used_links = array();
        foreach ($matches as $m) {
            $len = strlen($m[0]);
            $field = substr($m[0], 1, $len - 2);
            $arr = explode('.', $field);
            switch(count($arr)) {
            case 3:
                $schema = $arr[0];
                $table = $arr[1];
                $field = $arr[2];
                break;
            case 2:
                $schema = $this->schema;
                $table = $arr[0];
                $field = $arr[1];
                break;
            default:
                $schema = $this->schema;
                $table = $this->full_table_name;
                $field = $arr[0];
            }

            if (isset($this->links[$table])) {
                // 找到一个关联表字段
                $link = $this->links[$table];
                /* @var $link QDB_Table_Link_Abstract */
                $used_links[] = $link;
                // TODO: parseSQLString() 处理查询中的关联表
            } else {
                $field = $this->conn->qfield($field, $table, $schema);
            }

            $out .= substr($where, $offset, $m[1] - $offset) . $field;
            $offset = $m[1] + $len;
        }
        $out .= substr($where, $offset);
        $where = $out;

        // 分析查询条件中的参数占位符
        $args_count = null;

        if (strpos($where, '?') !== false) {
            // 使用 ? 作为占位符的情况
            $ret = $this->conn->qinto($where, $args, QDB::param_qm, true);
        } elseif (strpos($where, ':') !== false) {
            // 使用 : 开头的命名参数占位符
            if (!empty($args)) { $args = reset($args); }
            $ret = $this->conn->qinto($where, $args, QDB::param_cl_named, true);
        } else {
            $ret = $where;
        }
        if (is_array($ret)) {
            list($where, $args_count) = $ret;
        } else {
            $where = $ret;
        }
        return array($where, $used_links, $args_count);
    }

    /**
     * 将字段名替换为转义后的完全限定名
     *
     * @param array $field
     *
     * @return string
     */
    private function parseSQLQfield($field)
    {
        $p = explode('.', $field);
        switch (count($p)) {
        case 3:
            list($schema, $table, $field) = $p;
            if ($table == $this->table_name) {
                $table = $this->full_table_name;
            }
            return $this->conn->qfield($field, $table, $schema);
        case 2:
            list($table, $field) = $p;
            if ($table == $this->table_name) {
                $table = $this->full_table_name;
            }
            return $this->conn->qfield($field, $table);
        default:
            return $this->conn->qfield($p[0]);
        }
    }

    /**
     * 在指定字段填充当前时间
     *
     * @param array $row
     * @param array $fields
     */
    protected function fillFieldsWithCurrentTime(array & $row, array $fields)
    {
        $curr = time();
        $curr_db_time = $this->conn->dbTimestamp($curr);

        foreach ($fields as $field) {
            $mf = strtolower($field);
            if (!isset(self::$tables_meta[$this->meta_cache_id][$mf])) { continue; }
            switch (self::$tables_meta[$this->meta_cache_id][$mf]['ptype']) {
            case 'd':
            case 't':
                $row[$field] = $curr_db_time;
                break;
            case 'i':
                $row[$field] = $curr;
            }
        }
    }


    /**
     * 获得用于更新 updated 字段的 sql 语句
     *
     * @return string
     */
    protected function getUpdatedTimeSQL()
    {
        $sql = '';
        $time = time();
        $db_time = $this->conn->dbTimestamp($time);
        foreach ($this->updated_time_fields as $field) {
            $field = strtolower($field);
            $sql .= self::$tables_meta[$this->meta_cache_id][$field]['name'];
            $sql .= ' = ';
            switch (self::$tables_meta[$this->meta_cache_id][$field]['ptype']) {
            case 'd':
            case 't':
                $sql .= $db_time;
                break;
            default:
                $sql .= $time;
            }
            $sql .= ', ';
        }

        return substr($sql, 0, -2);
    }

    protected function incrOrDecrWhere($field, $step, $is_incr, $where, array $args = null)
    {
        $where = $this->parseSQLInternal($where, $args);
        if (is_array($where)) { $where = reset($where); }
        $field = $this->conn->qfield($field);
        $step = (int)$step;
        $op = ($is_incr) ? '+' : '-';
        $sql = "UPDATE {$this->qtable_name} SET {$field} = {$field} {$op} {$step}";
        if (!empty($where)) { $sql .= " WHERE {$where}"; }
        return $this->updateBySQL($sql);
    }

    /**
     * 准备当前数据表的元数据
     *
     * @return boolean
     */
    private function prepareMeta()
    {
        if (isset(self::$tables_meta[$this->meta_cache_id])) { return; }
        $cached = Q::getIni('db_meta_cached');

        if ($cached) {
            // 尝试从缓存读取
            $policy = array(
                'encoding_filename' => true,
                'serialize' => true,
                'lifetime' => Q::getIni('db_meta_lifetime')
            );
            $backend = Q::getIni('db_meta_cache_backend');
            $meta = Q::getCache($this->meta_cache_id, $policy, $backend);
            if (is_array($meta) && !empty($meta)) {
                self::$tables_meta[$this->meta_cache_id] = $meta;
                return;
            }
        }

        // 从数据库获得 meta
        $meta = $this->conn->metaColumns($this->full_table_name, $this->schema);
        self::$tables_meta[$this->meta_cache_id] = $meta;
        if ($cached) {
            // 缓存数据
            Q::setCache($this->meta_cache_id, $meta, $policy, $backend);
        }
    }

    /**
     * 准备主键
     */
    private function preparePK()
    {
        $this->pk = Q::normalize($this->pk);
        $this->is_cpk = count($this->pk) > 1;
        $this->qpk = $this->conn->qfields($this->pk, $this->full_table_name);
    }
}
