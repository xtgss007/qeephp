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
 * 定义 QDB_Mysql 类
 *
 * @package database
 * @version $Id: mysql.php 976 2008-03-20 00:28:23Z dualface $
 */

/**
 * QDB_Mysql 提供了对 mysql 数据库的支持
 *
 * @package database
 */
class QDB_Adapter_Mysql extends QDB_Adapter_Abstract
{
    protected $BIND_ENABLED = false;

    function __construct($dsn, $id)
    {
        if (!is_array($dsn)) {
            $dsn = QDB::parseDSN($dsn);
        }
        parent::__construct($dsn, $id);
        $this->schema = $dsn['database'];
    }

    function connect($pconnect = false, $force_new = false)
    {
        if (is_resource($this->conn)) { return; }

        $this->last_err = null;
        $this->last_err_code = null;

        if (isset($this->dsn['port']) && $this->dsn['port'] != '') {
            $host = $this->dsn['host'] . ':' . $this->dsn['port'];
        } else {
            $host = $this->dsn['host'];
        }
        if (!isset($this->dsn['login'])) { $this->dsn['login'] = ''; }
        if (!isset($this->dsn['password'])) { $this->dsn['password'] = ''; }

        if ($pconnect) {
            $this->conn = mysql_pconnect($host, $this->dsn['login'], $this->dsn['password'], $force_new);
        } else {
            $this->conn = mysql_connect($host, $this->dsn['login'], $this->dsn['password'], $force_new);
        }

        if (!is_resource($this->conn)) {
            throw new QDB_Exception('CONNECT DATABASE', mysql_error(), mysql_errno());
        }

        if (!empty($this->dsn['database'])) {
            $this->selectDB($this->dsn['database']);
        }

        if (isset($this->dsn['charset']) && $this->dsn['charset'] != '') {
            $charset = $this->dsn['charset'];
            $this->execute("SET NAMES '" . $charset . "'");
        }
    }

    function pconnect()
    {
        $this->connect(true);
    }

    function nconnect()
    {
        $this->connect(false, true);
    }

    function close()
    {
        if (is_resource($this->conn)) { mysql_close($this->conn); }
        parent::close();
    }

    function setSchema($schema)
    {
        if (!$this->conn) { $this->connect(); }
        if (!mysql_select_db($schema, $this->conn)) {
            throw new QDB_Exception("USE {$schema}", mysql_error($this->conn), mysql_errno($this->conn));
        }
        $this->schema = $schema;
    }

    function qstr($value)
    {
        if (is_int($value)) { return $value; }
        if (is_bool($value)) { return $value ? $this->true_value : $this->false_value; }
        if (is_null($value)) { return $this->null_value; }
        return "'" . mysql_real_escape_string($value, $this->conn) . "'";
    }

    function qtable($table_name, $schema = null)
    {
        if (strpos($table_name, '.') !== false) {
            $parts = explode('.', $table_name);
            $table_name = $parts[1];
            $schema = $parts[0];
        }
        $table_name = trim($table_name, '`');
        $schema = trim($schema, '`');
        return $schema != '' ? "`{$schema}`.`{$table_name}`" : "`{$table_name}`";
    }

    function qfield($field_name, $table_name = null, $schema = null, $alias = null)
    {
        if (strpos($field_name, '.') !== false) {
            $parts = explode('.', $field_name);
            if (isset($parts[2])) {
                $schema = $parts[0];
                $table_name = $parts[1];
                $field_name = $parts[2];
            } elseif (isset($parts[1])) {
                $table_name = $parts[0];
                $field_name = $parts[1];
            }
        }
        $field_name = trim($field_name, '`');
        $field_name = ($field_name == '*') ? '*' : "`{$field_name}`";
        if ($table_name) {
            $field_name = $this->qtable($table_name, $schema) . '.' . $field_name;
        }
        if ($alias) {
            return "{$field_name} AS `{$alias}`";
        } else {
            return $field_name;
        }
    }

    function nextID($tablename, $fieldname = null, $schema = null, $start_value = 1)
    {
        $full_tablename = $tablename . '_' . $fieldname . '_seq';
        $qtable = $this->qtable($full_tablename, $schema);
        $next_sql = sprintf('UPDATE %s SET id = LAST_INSERT_ID(id + 1)', $qtable);

        $successed = false;
        try {
            // 首先产生下一个序列值
            $this->execute($next_sql);
            if ($this->affectedRows() > 0) {
                $successed = true;
            }
        } catch (QDB_Exception $ex) {
            // 产生序列值失败，创建序列表
            unset($ex);
            $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $qtable));
        }

        if (!$successed) {
            // 没有更新任何记录或者新创建序列表，都需要插入初始的记录
            if ($this->getOne(sprintf('SELECT COUNT(*) FROM %s', $qtable)) == 0) {
                $sql = sprintf('INSERT INTO %s VALUES (%s)', $qtable, $start_value);
                $this->execute($sql);
            }
            $this->execute($next_sql);
        }
        // 获得新的序列值
        $this->insert_id = $this->insertID();
        return $this->insert_id;
    }

    function createSeq($seqname, $start_value = 1)
    {
        $qtable = $this->qtable($seqname);
        $this->execute(sprintf('CREATE TABLE %s (id INT NOT NULL)', $qtable));
        $sql = sprintf('INSERT INTO %s VALUES (%s)', $qtable, $start_value);
        $this->execute($sql);
    }

    function dropSeq($seqname)
    {
        $this->execute(sprintf('DROP TABLE %s', $this->qtable($seqname)));
    }

    function insertID()
    {
        return mysql_insert_id($this->conn);
    }

    function affectedRows()
    {
        return mysql_affected_rows($this->conn);
    }

    function execute($sql, $inputarr = null)
    {
        if (is_array($inputarr)) {
            $sql = $this->fakebind($sql, $inputarr);
        }
        if (!$this->conn) { $this->connect(); }
        if ($this->log_query) {
            $this->log[] = $sql;
        }
        if ($this->log_message) {
            log_message($sql, 'debug');
        }
        $this->query_count++;
        $result = mysql_query($sql, $this->conn);

        if (is_resource($result)) {
            Q::loadClass('QDB_Result_Mysql');
            return new QDB_Result_Mysql($result, $this->fetch_mode);
        } elseif ($result) {
            $this->last_err = null;
            $this->last_err_code = null;
            return $result;
        } else {
            $this->last_err = mysql_error($this->conn);
            $this->last_err_code = mysql_errno($this->conn);
            $this->has_failed_query = true;
            throw new QDB_Exception($sql, $this->last_err, $this->last_err_code);
        }
    }

    function selectLimit($sql, $length = null, $offset = null, array $inputarr = null)
    {
        if (!is_null($offset)) {
            $sql .= ' LIMIT ' . (int)$offset;
            if (!is_null($length)) {
                $sql .= ', ' . (int)$length;
            } else {
                $sql .= ', 18446744073709551615';
            }
        } elseif (!is_null($length)) {
            $sql .= ' LIMIT ' . (int)$length;
        }
        return $this->execute($sql, $inputarr);
    }

    function startTrans()
    {
        if (!$this->transaction_enabled) { return false; }
        if ($this->trans_count == 0) {
            $this->execute('START TRANSACTION');
            $this->has_failed_query = false;
        } elseif ($this->trans_count && $this->savepoint_enabled) {
            $savepoint = 'savepoint_' . $this->trans_count;
            $this->execute("SAVEPOINT `{$savepoint}`");
            array_push($this->savepoints_stack, $savepoint);
        }
        $this->trans_count++;
    }

    function completeTrans($commit_on_no_errors = true)
    {
        if ($this->trans_count == 0) { return; }
        $this->trans_count--;
        if ($this->trans_count == 0) {
            if ($this->has_failed_query == false && $commit_on_no_errors) {
                $this->execute('COMMIT');
            } else {
                $this->execute('ROLLBACK');
            }
        } elseif ($this->savepoint_enabled) {
            $savepoint = array_pop($this->savepoints_stack);
            if ($this->has_failed_query || $commit_on_no_errors == false) {
                $this->execute("ROLLBACK TO SAVEPOINT `{$savepoint}`");
            }
        }
    }

    function metaColumns($table_name, $schema = null)
    {
        static $typeMap = array(
            'bit'           => 'i',
            'tinyint'       => 'i',
            'bool'          => 'l',
            'boolean'       => 'l',
            'smallint'      => 'i',
            'mediumint'     => 'i',
            'int'           => 'i',
            'integer'       => 'i',
            'bigint'        => 'i',
            'float'         => 'n',
            'double'        => 'n',
            'doubleprecision' => 'n',
            'float'         => 'n',
            'decimal'       => 'n',
            'dec'           => 'n',

            'date'          => 'd',
            'datetime'      => 't',
            'timestamp'     => 't',
            'time'          => 't',
            'year'          => 'i',

            'char'          => 'c',
            'nchar'         => 'c',
            'varchar'       => 'c',
            'nvarchar'      => 'c',
            'binary'        => 'b',
            'varbinary'     => 'b',
            'tinyblob'      => 'x',
            'tinytext'      => 'x',
            'blob'          => 'x',
            'text'          => 'x',
            'mediumblob'    => 'x',
            'mediumtext'    => 'x',
            'longblob'      => 'x',
            'longtext'      => 'x',
            'enum'          => 'e',
            'set'           => 'e',
        );

        $table_name = $this->qtable($table_name, $schema);
        $rs = $this->execute(sprintf('SHOW FULL COLUMNS FROM %s', $table_name));
        if (!$rs) { return false; }
        /* @var $rs QDB_Result_Abstract */
        $retarr = array();
        $rs->fetchMode = QDB::fetch_mode_array;
        while (($row = $rs->fetchRow())) {
            $field = array();
            $field['name'] = $row['Field'];
            $type = strtolower($row['Type']);

            $field['scale'] = null;
            $query_arr = false;
            if (preg_match('/^(.+)\((\d+),(\d+)/', $type, $query_arr)) {
                $field['type'] = $query_arr[1];
                $field['length'] = is_numeric($query_arr[2]) ? $query_arr[2] : -1;
                $field['scale'] = is_numeric($query_arr[3]) ? $query_arr[3] : -1;
            } elseif (preg_match('/^(.+)\((\d+)/', $type, $query_arr)) {
                $field['type'] = $query_arr[1];
                $field['length'] = is_numeric($query_arr[2]) ? $query_arr[2] : -1;
            } elseif (preg_match('/^(enum)\((.*)\)$/i', $type, $query_arr)) {
                $field['type'] = $query_arr[1];
                $arr = explode(",",$query_arr[2]);
                $field['enums'] = $arr;
                $zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
                $field['length'] = ($zlen > 0) ? $zlen : 1;
            } else {
                $field['type'] = $type;
                $field['length'] = -1;
            }
            $field['ptype'] = $typeMap[strtolower($field['type'])];
            $field['not_null'] = ($row['Null'] != 'YES');
            $field['pk'] = ($row['Key'] == 'PRI');
            $field['auto_incr'] = (strpos($row['Extra'], 'auto_incr') !== false);
            if ($field['auto_incr']) { $field['ptype'] = 'r'; }
            $field['binary'] = (strpos($type,'blob') !== false);
            $field['unsigned'] = (strpos($type,'unsigned') !== false);

            if (!$field['binary']) {
                $d = $row['Default'];
                if ($d != '' && $d != 'NULL') {
                    $field['has_default'] = true;
                    $field['default'] = $d;
                } else {
                    $field['has_default'] = false;
                }
            }

            if ($field['type'] == 'tinyint' && $field['length'] == 1) {
                $field['ptype'] = 'l';
            }

            $field['desc'] = isset($row['Comment']) ? $row['Comment'] : '';

            $retarr[strtolower($field['name'])] = $field;
        }
        return $retarr;
    }

    function metaTables($pattern = null, $schema = null)
    {
        $sql = 'SHOW TABLES';
        if ($schema != '') {
            $sql .= " FROM `{$schema}`";
        }
        if ($pattern != '') {
            $sql .= ' LIKE ' . $this->qstr($pattern);
        }
        $rs = $this->execute($sql);
        /* @var $rs QDB_Result_Abstract */
        $tables = array();
        while (($table_name = $rs->fetchOne())) {
           $tables[] = $table_name;
        }
        return $tables;
    }

    protected function fakebind($sql, $inputarr)
    {
        $arr = explode('?', $sql);
        $sql = array_shift($arr);
        foreach ($inputarr as $value) {
            if (isset($arr[0])) {
                $sql .= $this->qstr($value) . array_shift($arr);
            }
        }
        return $sql;
    }
}
