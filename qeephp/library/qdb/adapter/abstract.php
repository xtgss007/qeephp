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
 * 定义 QDB_Adapter_Abstract 类
 *
 * @package database
 * @version $Id: abstract.php 962 2008-03-17 02:43:30Z dualface $
 */

/**
 * QDB_Adapter_Abstract 是所有数据库驱动的抽象基础类
 *
 * @package database
 */
abstract class QDB_Adapter_Abstract
{
    /**
     * 数据库连接信息
     *
     * @var mixed
     */
    protected $dsn;

    /**
     * 数据库访问对象 ID
     *
     * @var string
     */
    protected $id;

    /**
     * 默认的 schema
     *
     * @var string
     */
    protected $schema = '';

    /**
     * 指示返回结果集的形式
     *
     * @var const
     */
    protected $fetch_mode = QDB::FETCH_MODE_ASSOC;

    /**
     * 数据库连接句柄
     *
     * @var resource
     */
    protected $conn;

    /**
     * 最后一次数据库操作的错误信息
     *
     * @var mixed
     */
    protected $last_err;

    /**
     * 最后一次数据库操作的错误代码
     *
     * @var mixed
     */
    protected $last_err_code;

    /**
     * 最近一次插入操作或者 nextID() 操作返回的插入 ID
     *
     * @var mixed
     */
    protected $insert_id;

    /**
     * 指示事务启动次数
     *
     * @var int
     */
    protected $trans_count = 0;

    /**
     * 指示事务执行期间是否发生了错误
     *
     * @var boolean
     */
    protected $has_failed_query = false;

    /**
     * SAVEPOINT 堆栈
     *
     * @var array
     */
    protected $savepoints_stack = array();

    /**
     * 用于描绘 true、false 和 null 的数据库值
     */
    protected $true_value       = 1;
    protected $false_value      = 0;
    protected $null_value       = 'NULL';

    /**
     * 数据库接受的日期格式
     */
    protected $timestamp_format = 'Y-m-d H:i:s';

    /**
     * 指示驱动是否支持原生的参数绑定
     *
     * @var boolean
     */
    protected $bind_enabled = true;
    /**
     * 指示使用何种样式的参数占位符
     *
     * @var string
     */
    protected $param_style  = QDB::PARAM_QM;

    /**
     * 指示数据库是否有自增字段功能
     *
     * @var boolean
     */
    protected $has_insert_id = true;

    /**
     * 指示数据库是否能获得更新、删除操作影响的记录行数量
     *
     * @var boolean
     */
    protected $affected_rows_enabled = true;

    /**
     * 指示数据库是否支持事务
     *
     * @var boolean
     */
    protected $transaction_enabled = true;

    /**
     * 指示数据库是否支持事务中的 SAVEPOINT 功能
     *
     * @var boolean
     */
    protected $savepoint_enabled = false;

    #IFDEF DEBUG
    /**
     * 日志服务程序
     *
     * @var QLog
     */
    protected $logger;
    #ENDIFDEF

    /**
     * 指示是否将查询结果中的字段名转换为全小写
     *
     * @var boolean
     */
    protected $result_field_name_lower = false;

    /**
     * 构造函数
     *
     * @param mixed $dsn
     * @param string $id
     */
    protected function __construct($dsn, $id)
    {
        $this->dsn = $dsn;
        $this->id = $id;

        #IFDEF DEBUG
        $this->logger = Q::getService('log');
        #ENDIFDEF
    }

    /**
     * 返回数据库访问对象使用的 DSN
     *
     * @return mixed
     */
    function getDSN()
    {
        return $this->dsn;
    }

    /**
     * 返回数据库访问对象的 ID
     *
     * @return string
     */
    function getID()
    {
        return $this->id;
    }

    /**
     * 返回数据库对象对应的 schema
     *
     * @return string
     */
    function getSchema()
    {
        return $this->schema;
    }

    /**
     * 返回数据库对象对应的表前缀
     *
     * @return string
     */
    function getTablePrefix()
    {
        return isset($this->dsn['prefix']) ? $this->dsn['prefix'] : '';
    }

    /**
     * 连接数据库，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract function connect();

    /**
     * 创建一个持久连接，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract function pconnect();

    /**
     * 强制创建一个新连接，失败时抛出异常
     *
     * 如果已经连接到了数据库，再次连接不会造成任何影响。
     */
    abstract function nconnect();

    /**
     * 确认是否已经连接到数据库
     *
     * @return boolean
     */
    function isConnected()
    {
        return is_resource($this->conn);
    }

    /**
     * 关闭数据库连接
     */
    function close()
    {
        $this->conn = null;
        $this->last_err = null;
        $this->last_err_code = null;
        $this->insert_id = null;
        $this->trans_count = 0;
    }

    /**
     * 返回连接数据库的句柄
     *
     * @return resource
     */
    function handle()
    {
        return $this->conn;
    }

    /**
     * 选择要操作的 SCHEMA
     *
     * @param string $schema
     *
     * @return boolean
     */
    abstract function setSchema($schema);

    /**
     * 转义值
     *
     * 为了能够在 SQL 语句中安全的插入数据，应该用 qstr() 方法将数据中的特殊字符转义。
     *
     * example:
     * <code>
     * $param = "It's live";
     * $param = $dbo->qstr($param);
     * $sql = "INSERT INTO posts (title) VALUES ({$param})";
     * $dbo->execute($sql);
     * </code>
     *
     * 但更有效，而且更简单的方式是使用参数占位符：
     *
     * example:
     * <code>
     * $param = "It's live";
     * $sql = "INSERT INTO posts (title) VALUES (?)";
     * $dbo->execute($sql, array($param));
     * </code>
     *
     * 而且对于 Oracle 等数据库，由于限制每条 SQL 语句不能超过 4000 字节，
     * 因此在插入包含大量数据的记录时，必须使用参数占位符的形式。
     *
     * example:
     * <code>
     * $title = isset($POST['title']) ? $POST['title'] : null;
     * $body = isset($POST['body']) ? $POST['body'] : null;
     *
     * ... 检查 $title、$body 是否为空 ...
     *
     * $sql = "INSERT INTO posts (title, body) VALUES (:title, :body)";
     * $dbo->execute($sql, array('title' => $title, 'body' => $body));
     * </code>
     *
     * @param mixed $value
     *
     * @return string
     */
    abstract function qstr($value);

    /**
     * 将 SQL 中用“[]”指示的字段名进行转义
     *
     * @param string $sql
     * @param string $table_name
     * @param array $mapping
     *
     * @return string
     */
    function qfieldsInto($sql, $table_name, array $mapping = null)
    {
        $matches = null;
        preg_match_all('/\[[a-z][a-z0-9_\.]*\]|\[\*\]/i', $sql, $matches, PREG_OFFSET_CAPTURE);
        $matches = reset($matches);
        if (!is_array($mapping)) { $mapping = array(); }

        $out = '';
        $offset = 0;
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
                $schema = null;
                $table = $arr[0];
                $field = $arr[1];
                break;
            default:
                $schema = null;
                $table = $table_name;
                $field = $arr[0];
            }

            if (isset($mapping[$field])) {
                $field = $mapping[$field];
            }
            $field = $this->qfield($field, $table, $schema);
            $out .= substr($sql, $offset, $m[1] - $offset) . $field;
            $offset = $m[1] + $len;
        }
        $out .= substr($sql, $offset);

        return $out;
    }

    /**
     * 将 SQL 语句中的参数占位符替换为相应的参数值
     *
     * @param string $sql
     * @param array $params
     * @param enum $param_style
     * @param boolean $return_args_count
     *
     * @return string|array
     */
    function qinto($sql, array $params = null, $param_style = null, $return_args_count = false)
    {
        if (is_null($param_style)) {
            $param_style = $this->param_style;
        }

        $callback = array($this, 'qstr');
        switch ($param_style) {
        case QDB::PARAM_QM:
        case QDB::PARAM_DL_SEQUENCE:
            if ($param_style == QDB::PARAM_QM) {
                $parts = explode('?', $sql);
            } else {
                $parts = preg_split('/\$[0-9]+/', $sql);
            }
            $str = $parts[0];
            $offset = 1;
            foreach ($params as $arg_value) {
                if (!isset($parts[$offset])) { break; }
                if (is_array($arg_value)) {
                    $arg_value = array_unique($arg_value);
                    $arg_value = array_map($callback, $arg_value);
                    $str .= implode(',', $arg_value) . $parts[$offset];
                } else {
                    $str .= $this->qstr($arg_value) . $parts[$offset];
                }
                $offset++;
            }
            if ($return_args_count) {
                return array($str, count($parts) - 1);
            } else {
                return $str;
            }

        case QDB::PARAM_CL_NAMED:
        case QDB::PARAM_AT_NAMED:
            $params = reset($params);
            $split = ($param_style == QDB::PARAM_CL_NAMED) ? ':' : '@';
            $parts = preg_split('/(' . $split . '[a-z0-9_\-]+)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
            $max = count($parts);
            $str = $parts[0];

            for ($offset = 1; $offset < $max; $offset += 2) {
                $arg_name = substr($parts[$offset], 1);
                if (!isset($params[$arg_name])) {
                    throw new QDB_Exception($sql, __('Invalid parameter "%s" for "%s"', $arg_name, $sql), 0);
                }
                if (is_array($params[$arg_name])) {
                    $arg_value = array_map($callback, $params[$arg_name]);
                    $str .= implode(',', $arg_value) . $parts[$offset + 1];
                } else {
                    $str .= $this->qstr($params[$arg_name]) . $parts[$offset + 1];
                }
            }
            if ($return_args_count) {
                return array($str, intval($max / 2) - 1);
            } else {
                return $str;
            }

        default:
            return $sql;
        }
    }

    /**
     * 返回数据表名称的完全限定名
     *
     * 如果数据表的名字是 SQL 中的关键字，或者是要通过 schema 来确定数据表。
     * 那么就应该用 qtable() 方法将数据表名称转换为完全限定名。
     *
     * example:
     * <code>
     * $tablename = 'posts';
     * $schema = 'test_db';
     *
     * $identifier = $dbo->qtable($tablename, $schema);
     * // 如果是 MySQL 驱动，则 $identifier 的值为 `test_db`.`posts`
     * </code>
     *
     * 注意：不同数据库驱动，产生的完全限定名是不同的。
     *
     * @param string $table_name
     * @param string $schema
     * @param string $alias
     *
     * @return string
     */
    abstract function qtable($table_name, $schema = null, $alias = null);

    /**
     * 返回字段名称的完全限定名
     *
     * 与 qtable() 类似，qfield() 产生包括 schema、数据库名、字段名的完全限定名。
     *
     * example:
     * <code>
     * $field = 'post_id';
     * $tablename = 'posts';
     * $schema = 'test_db';
     * $identifier = $dbo->qfield($field, $table_name, $schema);
     * // 如果是 MySQL 驱动，则 $identifier 的值为 `test_db`.`posts`.`post_id`
     * </code>
     *
     * 注意：不同数据库驱动，产生的完全限定名是不同的。
     *
     * @param string $field_name
     * @param string $table_name
     * @param string $schema
     * @param string $alias
     *
     * @return string
     */
    abstract function qfield($field_name, $table_name = null, $schema = null, $alias = null);

    /**
     * 返回多个字段名称的完全限定名
     *
     * 转换的结果是以“,”连接的、包含所有字段完全限定名的字符串，
     * 或者一个包含所有字段的完全限定名的数组。
     * 默认返回字符串，如果要返回数组，将 $return_array 参数指定为 true。
     *
     * example:
     * <code>
     * $fields = 'post_id, title, body';
     * $tablename = 'posts';
     * $identifiers = $dbo->qfields($fields, $tablename);
     * // 如果是 MySQL 驱动，则 $identifiers 的值为 `posts`.`post_id`, `posts`.`title`, `posts`.`body`
     *
     * $fields = array('post_id', 'title', 'body');
     * $identifiers = $dbo->qfields($fields, null, null, true);
     * // 如果是 MySQL 驱动，则 $identifiers 的值为 array("`post_id`", "`title`", "`body`")
     * </code>
     *
     * @param string|array $fields
     * @param string $table_name
     * @param string $schema
     * @param boolean $return_array
     *
     * @return string
     */
    function qfields($fields, $table_name = null, $schema = null, $return_array = false)
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
            $fields = array_map('trim', $fields);
        }
        $return = array();
        foreach ($fields as $key => $value) {
            if (is_string($key)) {
                $return[] = $this->qfield($key, $table_name, $schema, $value);
            } else {
                $return[] = $this->qfield($value, $table_name, $schema);
            }
        }
        return $return_array ? $return : implode(', ', $return);
    }

    /**
     * 为数据表产生下一个序列值，失败时抛出异常
     *
     * 调用 nextID() 方法，将获得指定名称序列的下一个值。
     * 此处所指的序列，是指一个不断增大的数字。
     *
     * 假设本次调用 nextID() 返回 3，那么下一次调用 nextID() 就会返回一个比 3 更大的值。
     * nextID() 返回的序列值，可以作为记录的主键字段值，以便确保插入记录时总是使用不同的主键值。
     *
     * 可以使用多个序列，只需要指定不同的 $seq_name 参数即可。
     *
     * 在不同的数据库中，序列的产生方式各有不同。
     * PostgreSQL、Oracle 等数据库中，会使用数据库自带的序列功能来实现。
     * 其他部分数据库会创建一个后缀为 _seq 表来存放序列值。
     *
     * 例如 $seq_name 为 posts，则存放该序列的表名称为 posts_seq。
     *
     * @param string $tablename
     * @param string $fieldname
     * @param string $schema
     * @param string $start_value
     *
     * @return int
     */
    abstract function nextID($tablename, $fieldname, $schema = null, $start_value = 1);

    /**
     * 创建一个新的序列，失败时抛出异常
     *
     * 调用 nextID() 时，如果指定的序列不存在，则会自动调用 create_seq() 创建。
     * 开发者也可以自行调用 create_seq() 创建一个新序列。
     *
     * @param string $seqname
     * @param int $start_value
     */
    abstract function createSeq($seqname, $start_value = 1);

    /**
     * 删除一个序列，失败时抛出异常
     *
     * @param string $seqname
     */
    abstract function dropSeq($seqname);

    /**
     * 获取自增字段的最后一个值或者 nextID() 方法产生的最后一个值
     *
     * 某些数据库（例如 MySQL）可以将一个字段设置为自增。
     * 也就是每次往数据表插入一条记录，该字段的都会自动填充一个更大的新值。
     *
     * insertID() 方法可以获得最后一次插入记录时产生的自增字段值，或者最后一次调用 nextID() 返回的值。
     *
     * 如果在一次操作中，插入了多条记录，那么 insertID() 有可能返回的是第一条记录的自增值。
     * 这个问题是由数据库本身的实现决定的。
     *
     * @return int
     */
    abstract function insertID();

    /**
     * 返回最近一次数据库操作受到影响的记录数
     *
     * 这些操作通常是插入记录、更新记录以及删除记录。
     * 不同的数据库对于其他操作，也可能影响到 affectedRows() 返回的值。
     *
     * @return int
     */
    abstract function affectedRows();

    /**
     * 执行一个查询，返回一个查询对象或者 boolean 值，出错时抛出异常
     *
     * $sql 是要执行的 SQL 语句字符串，而 $inputarr 则是提供给 SQL 语句中参数占位符需要的值。
     *
     * 如果执行的查询是诸如 INSERT、DELETE、UPDATE 等不会返回结果集的操作，
     * 则 execute() 执行成功后会返回 true，失败时将抛出异常。
     *
     * 如果执行的查询是 SELECT 等会返回结果集的操作，
     * 则 execute() 执行成功后会返回一个 DBO_Result 对象，失败时将抛出异常。
     *
     * QDB_Result_Abstract 对象封装了查询结果句柄，而不是结果集。
     * 因此要获得查询的数据，需要调用 QDB_Result_Abstract 的 fetchAll() 等方法。
     *
     * 如果希望执行 SQL 后直接获得结果集，可以使用驱动的 getAll()、getRow() 等方法。
     *
     * example:
     * <code>
     * $sql = "INSERT INTO posts (title, body) VALUES (?, ?)";
     * $dbo->execute($sql, array($title, $body));
     * </code>
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts WHERE post_id < 12";
     * $handle = $dbo->execute($sql);
     * $rowset = $handle->fetchAll();
     * $handle->free();
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return QDB_Result_Abstract
     */
    abstract function execute($sql, $inputarr = null);

    /**
     * 发起一个查询，获得一个 QDB_Select 查询对象
     *
     * @return QDB_Select
     */
    function select()
    {
        $select = new QDB_Select($this);
        $args = func_get_args();
        if (!empty($args)) {
            call_user_func_array(array($select, 'where'), $args);
        }
        return $select;
    }

    /*
     * 进行限定范围的查询，并且返回 QDB_Result_Abstract 对象，出错时抛出异常
     *
     * 使用 selectLimit()，可以限定 SELECT 查询返回的结果集的大小。
     * $length 参数指定结果集最多包含多少条记录。而 $offset 参数则指定在查询结果中，从什么位置开始提取记录。
     *
     * 假设 SELECT * FROM posts ORDER BY post_id ASC 的查询结果一共有 500 条记录。
     * 现在通过指定 $length 为 20，则可以限定只提取其中的 20 条记录作为结果集。
     * 进一步指定 $offset 参数为 59，则可以从查询结果的第 60 条记录开始提取 20 条作为结果集。
     *
     * 注意：$offset 参数是从 0 开始计算的。因此 $offset 为 59 时，实际上是从第 60 条记录开始提取。
     *
     * selectLimit() 并不直接返回结果集，而是返回 QDB_Result_Abstract 对象。
     * 因此需要调用 QDB_Result_Abstract 对象的 fetchAll() 等方法来获得数据。
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts WHERE created > ? ORDER BY post_id DESC";
     * $length = 20;
     * $offset = 0;
     * $current = time() - 60 * 60 * 24 * 15; // 查询创建时间在 15 天内的记录
     * $handle = $dbo->selectLimit($sql, $length, $offset, array($current));
     * $rowset = $handle->fetchAll();
     * $handle->free();
     * </code>
     *
     * @param string $sql
     * @param int $length
     * @param int $offset
     * @param array $inputarr
     *
     * @return QDB_Result_Abstract
     */
    abstract function selectLimit($sql, $length = null, $offset = null, array $inputarr = null);

    /**
     * 执行一个查询并返回记录集，失败时抛出异常
     *
     * getAll() 等同于执行下面的代码：
     *
     * <code>
     * $rowset = $dbo->execute($sql, $inputarr)->fetchAll();
     * </code>
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return array
     */
    function getAll($sql, array $inputarr = null)
    {
        return $this->execute($sql, $inputarr)->fetchAll();
    }


    /**
     * 执行查询，返回第一条记录
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    function getRow($sql, array $inputarr = null)
    {
        return $this->selectLimit($sql, 1, null, $inputarr)->fetchRow();
    }

    /**
     * 执行查询，返回第一条记录的第一个字段
     *
     * @param string $sql
     * @param array $inputarr
     *
     * @return mixed
     */
    function getOne($sql, array $inputarr = null)
    {
        return $this->selectLimit($sql, 1, null, $inputarr)->fetchOne();
    }

    /**
     * 执行查询，返回结果集的指定列
     *
     * @param string|resource $sql
     * @param int $col 要返回的列，0 为第一列
     * @param array $inputarr
     *
     * @return mixed
     */
    function getCol($sql, $col = 0, array $inputarr = null)
    {
        return $this->execute($sql, $inputarr)->fetchCol($col);
    }

    /**
     * 将 unix timestamp 转换为数据库可以接受的日期格式
     *
     * example:
     * <code>
     * // 假设 created 是 DATETIME 类型的字段
     * $sql = "INSERT INTO posts (title, body, created) VALUES (?, ?, ?)";
     * $created = $dbo->dbTimestamp(time());
     * $dbo->execute($sql, array($title, $body, $created));
     * </code>
     *
     * @param int $timestamp
     *
     * @return string
     */
    function dbTimestamp($timestamp)
    {
        return date($this->timestamp_format, $timestamp);
    }

    /**
     * 开始一个事务，并且返回一个 QDB_Transaction 对象
     *
     * 使用 beginTrans() 的好处是在 QDB_Transaction 对象销毁时，会自动提交事务或回滚事务。
     *
     * 传统的事务处理代码：
     * <code>
     * function doSometing()
     * {
     *      ...
     *
     *      $dbo->startTrans();
     *      try {
     *          $dbo->execute($sql);
     *      } catch (Exception $ex) {
     *          // 回滚事务
     *          $dbo->completeTrans(false);
     *          // 重新抛出异常
     *          throw $ex;
     *      }
     *      // 提交事务
     *      $dbo->completeTrans();
     * }
     * </code>
     *
     * 如果使用 beginTrans()，代码可以简化为：
     * <code>
     * function doSometing()
     * {
     *      $tran = $dbo->beginTrans();
     *      ...
     *      $dbo->execute($sql);
     *      ...
     *      // 退出函数时会自动提交事务
     * }
     * </code>
     *
     * 如果在 $dbo->execute($sql) 抛出了异常，那么 $tran 对象在销毁前会自动提交或回滚事务。
     * 正常执行到 doSometing() 函数结束时，$tran 对象会在销毁前自动提交事务。
     *
     * <strong>
     * 使用 beginTrans() 最重要的一点就是必须用一个变量存储 beginTrans() 返回的 QDB_Transaction 对象。
     * </strong>
     *
     * QDB_Transaction 对象也提供了明确的事务提交和回滚操作。具体信息请参考 QDB_Transaction 类的描述。
     *
     * @return QDB_Transaction
     */
    function beginTrans()
    {
        return new QDB_Transaction($this);
    }

    /**
     * 开始一个事务
     *
     * 调用 startTrans() 开始一个事务后，应该在关闭数据库连接前调用 completeTrans() 提交或回滚事务。
     */
    abstract function startTrans();

    /**
     * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
     *
     * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
     * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
     *
     * @param $commit_on_no_errors
     */
    abstract function completeTrans($commit_on_no_errors = true);

    /**
     * 指示在调用 completeTrans() 时回滚事务
     */
    function setTransFailed()
    {
        $this->has_failed_query = true;
    }

    /**
     * 检查事务过程中是否出现失败的查询
     */
    function hasFailedQuery()
    {
        return $this->has_failed_query;
    }

    /**
     * 返回指定数据表（或者视图）的元数据
     *
     * 返回的结果是一个二维数组，每一项为一个字段的元数据。
     * 每个字段包含下列属性：
     *
     * - name:            字段名
     * - scale:           小数位数
     * - type:            字段类型
     * - ptype:           简单字段类型（与数据库无关）
     * - length:          最大长度
     * - not_null:        是否不允许保存 NULL 值
     * - pk:              是否是主键
     * - auto_incr:       是否是自动增量字段
     * - binary:          是否是二进制数据
     * - unsigned:        是否是无符号数值
     * - has_default:     是否有默认值
     * - default:         默认值
     * - desc:            字段描述
     *
     * ptype 是下列值之一：
     *
     * - c char/varchar 等类型
     * - x text 等类型
     * - b 二进制数据
     * - n 数值或者浮点数
     * - d 日期
     * - t TimeStamp
     * - l 逻辑布尔值
     * - i 整数
     * - r 自动增量
     * - p 非自增的主键字段
     *
     * @param string $table_name
     * @param string $schema
     *
     * @return array
     */
    abstract function metaColumns($table_name, $schema = null);

    /**
     * 获得所有数据表的名称
     *
     * @param string $pattern
     * @param string $schema
     *
     * @return array
     */
    abstract function metaTables($pattern = null, $schema = null);

    /**
     * 根据包含记录内容的数组返回一条有效的 SQL 插入记录语句
     *
     * @param array $row
     * @param string $table 要插入的数据表
     * @param string $schema
     * @param array $meta
     *
     * @return string
     */
    function getInsertSQL(array $row, $table, $schema = null, array $meta = null)
    {
        if (!is_null($meta)) {
            $fields = array_keys($meta);
        } else {
            $fields = null;
        }
        list($holders, $values) = $this->getPlaceholder($row, $fields);
        $holders = implode(',', $holders);
        $fields = $this->qfields(array_keys($values));
        $table = $this->qtable($table, $schema);
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$holders})";
        return array($sql, $values);
    }

    /**
     * 根据包含记录内容的数组返回一条有效的 SQL 更新记录语句
     *
     * @param array $row
     * @param string $table 要插入的数据表
     * @param string $schema
     * @param array $meta
     *
     * @return string
     */
    function getUpdateSQL(array $row, $pk, $table, $schema = null, array $meta = null)
    {
        $pkv = $row[$pk];
        unset($row[$pk]);
        if (!is_null($meta)) {
            $fields = array_keys($meta);
        } else {
            $fields = null;
        }
        list($pairs, $values) = $this->getPlaceholderPairs($row, $fields);
        $row[$pk] = $pkv;
        $pairs = implode(',', $pairs);
        $table = $this->qtable($table, $schema);
        $pk = $this->qfield($pk);
        $sql = "UPDATE {$table} SET {$pairs} WHERE {$pk} = " . $this->qstr($pkv);
        return array($sql, $values);
    }

    /**
     * 根据包含记录内容的数组返回一条有效的 SQL REPLACE 语句
     *
     * @param array $row
     * @param string $table
     * @param string $schema
     *
     * @return string
     */
    function getReplaceSQL(array $row, $table, $schema = null)
    {
        list($holders, $values) = $this->getPlaceholder($row);
        $holders = implode(',', $holders);
        $fields = $this->qfields(array_keys($values));
        $table = $this->qtable($table, $schema);
        $sql = "REPLACE INTO {$table} ({$fields}) VALUES ({$holders})";
        return array($sql, $values);
    }

    /**
     * 确定驱动是否支持参数绑定
     *
     * @return boolean
     */
    function bindEnabled()
    {
        return $this->BIND_ENABLED;
    }

    /**
     * 返回驱动使用的参数占位符样式
     *
     * @return string
     */
    function paramStyle()
    {
        return $this->param_style;
    }

    /**
     * 根据驱动的参数占位符样式，返回包含参数占位符及有效数据的数组
     *
     * @param array $inputarr
     * @param array $fields
     *
     * @return array
     */
    function getPlaceholder(array $inputarr, array $fields = null)
    {
        $holders = array();
        $values = array();
        if (!is_array($fields)) {
            $fields = array_keys($inputarr);
        }
        $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
        foreach (array_keys($inputarr) as $offset => $key) {
            if (!isset($fields[strtolower($key)])) { continue; }
            switch($this->param_style) {
            case QDB::PARAM_QM:
                $holders[] = '?';
                break;
            case QDB::PARAM_DL_SEQUENCE:
                $holders[] = '$' . ($offset + 1);
                break;
            default:
                $holders[] = $this->param_style . $key;
            }
            $values[$key] = $inputarr[$key];
        }
        return array($holders, $values);
    }

    /**
     * 根据驱动的参数占位符样式，返回包含参数及占位符字符串对、有效数据的数组
     *
     * @param array $inputarr
     * @param array $fields
     *
     * @return array
     */
    function getPlaceholderPairs(array $inputarr, array $fields = null)
    {
        $pairs = array();
        $values = array();
        if (!is_array($fields)) {
            $fields = array_keys($inputarr);
        }
        $fields = array_change_key_case(array_flip($fields), CASE_LOWER);
        foreach (array_keys($inputarr) as $offset => $key) {
            if (!isset($fields[strtolower($key)])) { continue; }
            $qkey = $this->qfield($key);
            switch($this->param_style) {
            case QDB::PARAM_QM:
                $pairs[] = "{$qkey}={$this->param_style}";
                break;
            case QDB::PARAM_DL_SEQUENCE:
                $pairs[] = "{$qkey}=\$" . ($offset + 1);
                break;
            default:
                $pairs[] = "{$qkey}={$this->param_style}{$key}";
            }
            $values[$key] = $inputarr[$key];
        }
        return array($pairs, $values);
    }
}
