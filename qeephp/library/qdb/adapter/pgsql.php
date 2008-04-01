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
 * 定义 QDB_Adapter_Pgsql 类
 *	 这个是参考QDB_Adpter_Mysql 及 FLEA_Db_Driver_Pgsql 类修改而来，并根据具体情况进行了一些修改。
 * 
 * @package database
 * @author  Abin30@163.com
 * @version $Id: pgsql.php  
 */

/**
 * QDB_Adapter_Pgsql 提供了对 PostgreSQL 数据库的支持
 *
 * @package database
 */
class QDB_Adapter_Pgsql extends QDB_Adapter_Abstract
{
	protected $BIND_ENABLED = false;

	/**
     *  保存最后一次查询的资源ID
     *
     * @var  Resource 
     */
	protected  $_lastrs= null ;
	function __construct($dsn, $id)
	{
		if (!is_array($dsn)) {
			$dsn = QDB::parseDSN($dsn);
		}
		parent::__construct($dsn, $id);
		// PostgreSQL 不支持SavePoint
		$this->savepoint_enabled =false;
	}

	function connect($pconnect = false, $force_new = false)
	{
		if (is_resource($this->conn)) { return; }

		$this->last_err = null;
		$this->last_err_code = null;
		$dsnstring = '';
		if (isset($this->dsn['host'])) {
			$dsnstring = 'host=' . $this->_addslashes($this->dsn['host']);
		}
		if (isset($this->dsn['port'])) {
			$dsnstring .= ' port=' . $this->_addslashes($this->dsn['port']);
		}
		if (isset($this->dsn['login'])) {
			$dsnstring .= ' user=' . $this->_addslashes($this->dsn['login']);
		}
		if (isset($this->dsn['password'])) {
			$dsnstring .= ' password=' . $this->_addslashes($this->dsn['password']);
		}
		if (isset($this->dsn['database'])) {
			$dsnstring .= ' dbname=' . $this->_addslashes($this->dsn['database']);
		}
		$dsnstring .= ' ';
		if ($pconnect) {
			$this->conn =pg_pconnect($dsnstring );
		} else {
			$this->conn =pg_connect($dsnstring );
		}
		if (!is_resource($this->conn)) {
			throw new QDB_Exception('CONNECT DATABASE', pg_errormessage(), 0);
		}
		if (!$this->execute("set datestyle='ISO'")) { return false; }
		$charset=$this->dsn['charset'];
		if (strtoupper($charset) == 'GB2312') { $charset = 'GBK'; }
		if ($charset != '') {
			pg_set_client_encoding($this->conn, $charset);
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
		if (is_resource($this->conn)) { pg_close($this->conn); }
		parent::close();
	}

	function selectDB($database)
	{
		Throw New QDB_Exception('SelectDB','NotImplemented');
	}

	function qstr($value)
	{
		if (is_int($value) || is_float($value)) { return $value; }
		if (is_bool($value)) { return $value ? $this->true_value : $this->false_value; }
		if (is_null($value)) { return $this->null_value; }
		return "'" . pg_escape_string($value) . "'";
	}

	function qtable($table_name, $schema = null)
	{
		if (strpos($table_name, '.') !== false) {
			$parts = explode('.', $table_name);
			$table_name = $parts[1];
			$schema = $parts[0];
		}
		$table_name = trim($table_name, '"');
		$schema = trim($schema, '"');
		//public 是默认的schema
		if(strtoupper($schema)=='PUBLIC')$schema='';
		return $schema != '' ? "\"{$schema}\".\"{$table_name}\"" : "\"{$table_name}\"";
	}

	function qfield($field_name, $table_name = null, $schema = null, $alias = null)
	{
		$field_name=trim($field_name,'"');
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
		$field_name = ($field_name == '*') ? '*' : "\"{$field_name}\"";
		if (!empty($table_name)) {
			$field_name = $this->qtable($table_name, $schema) . '.' . $field_name;
		}
		if ($alias) {
			return "{$field_name} AS \"{$alias}\"";
		} else {
			return $field_name;
		}
	}

	function nextID($tablename, $fieldname = null, $schema = null, $start_value = 1)
	{
		$seqName = $tablename . '_' . $fieldname . '_seq';
		$next_sql = sprintf("SELECT NEXTVAL('%s')", $seqName);
		$this->insert_id = $this->execute($next_sql)->fetchOne();
		if(empty($this->insert_id)){
			if (!$this->createSeq($seqName, $start_value)) { return false; }
			$this->insert_id = $this->execute($next_sql)->fetchOne();
			if(empty($this->insert_id)) {return false; }
		}
		return $this->insert_id;
	}
	function createSeq($seqname, $start_value = 1)
	{
		return  $this->execute(sprintf('CREATE SEQUENCE %s START %s',$seqname,$start_value));
	}

	function dropSeq($seqname)
	{
		return $this->execute(sprintf('DROP SEQUENCE %s', $seqname));
	}

	function insertID()
	{
		throw new QDB_Exception("InsertID() ","'Not Implemented",0);
	}

	function affectedRows()
	{
		return pg_affected_rows($this->_lastrs);
	}
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
     * 
     * @param string $sql
     * @param array $inputarr
     *
     * @return QDB_Result_Abstract
     */
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
		$this->_lastrs = @pg_exec($this->conn, $sql);

		if (is_resource($this->_lastrs)) {
			Q::loadClass('Qdb_Result_Pgsql');
			return new QDB_Result_Pgsql($this->_lastrs, $this->fetch_mode);
		} elseif ($this->_lastrs) {
			$this->last_err = null;
			$this->last_err_code = null;
			return $this->_lastrs;
		} else {
			$this->last_err = pg_errormessage($this->conn);
			$this->last_err_code = null ;
			$this->has_failed_query = true;
			throw new QDB_Exception($sql, $this->last_err, $this->last_err_code);
		}
	}

	function selectLimit($sql, $length = null, $offset = null, array $inputarr = null)
	{
		if (strtoupper($length) != 'ALL') { $length = (int)$length; }
		$sql = sprintf('%s LIMIT %s OFFSET %s', $sql, $length, (int)$offset);
		return $this->execute($sql,$inputarr);
	}
	/**
     * 启动事务
     */
	function startTrans()
	{
		if (!$this->transaction_enabled) { return false; }
		$this->trans_count+=1;
		if ($this->trans_count == 1) {
			$this->execute('BEGIN;');
			$this->has_failed_query = false;
		}
	}
	/**
     * 完成事务，根据查询是否出错决定是提交事务还是回滚事务
     *
     * 如果 $commitOnNoErrors 参数为 true，当事务中所有查询都成功完成时，则提交事务，否则回滚事务
     * 如果 $commitOnNoErrors 参数为 false，则强制回滚事务
     *
     * @param $commitOnNoErrors 指示在没有错误时是否提交事务
     */

	function completeTrans($commit_on_no_errors = true)
	{
		if ($this->trans_count < 1) { return; }
		if ($this->trans_count > 1) {
			$this->trans_count -= 1;
			return;
		}
		$this->trans_count = 0;
		if ($this->has_failed_query == false && $commit_on_no_errors) {
			$this->execute('COMMIT');
		} else {
			$this->execute('ROLLBACK');
		}
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
	function metaColumns($table_name, $schema = null)
	{
		static $typeMap = array(
		'money' => 'c',
		'interval' => 'c',
		'char' => 'c',
		'character' => 'c',
		'varchar' => 'c',
		'name' => 'c',
		'bpchar' => 'c',
		'_varchar' => 'c',
		'inet' => 'c',
		'macaddr' => 'c',
		'text' => 'x',
		'image' => 'b',
		'blob' => 'b',
		'bit' => 'b',
		'varbit' => 'b',
		'bytea' => 'b',
		'bool' => 'l',
		'boolean' => 'l',
		'date' => 'd',
		'timestamp without time zone' => 't',
		'time' => 't',
		'datetime' => 't',
		'timestamp' => 't',
		'timestamptz' => 't',
		'smallint' => 'i',
		'begint' => 'i',
		'integer' => 'i',
		'int8' => 'i',
		'int4' => 'i',
		'int2' => 'i',
		'oid' => 'r',
		'serial' => 'r',
		'float'  => 'n',
		'float4' =>'n',
		'double' => 'n',
		'float8' =>'n',
		);

		$table_name = trim($table_name,'"');
		$keys=$this->getAll(sprintf("SELECT ic.relname AS index_name, a.attname AS column_name,i.indisunique AS unique_key, i.indisprimary AS primary_key FROM pg_class bc, pg_class ic, pg_index i, pg_attribute a WHERE bc.oid = i.indrelid AND ic.oid = i.indexrelid AND (i.indkey[0] = a.attnum OR i.indkey[1] = a.attnum OR i.indkey[2] = a.attnum OR i.indkey[3] = a.attnum OR i.indkey[4] = a.attnum OR i.indkey[5] = a.attnum OR i.indkey[6] = a.attnum OR i.indkey[7] = a.attnum) AND a.attrelid = bc.oid AND (bc.relname = '%s' or bc.relname=lower('%s'))",$table_name,$table_name));
		$rsdefa = array();
		$sql = sprintf("SELECT d.adnum as num, d.adsrc as def from pg_attrdef d, pg_class c where d.adrelid=c.oid and c.relname='%s' or c.relname=lower('%s') order by d.adnum", $table_name,$table_name);
		$rsdef=$this->getAll($sql);
		if(count($rsdef)>0){
			foreach($rsdef as $row )	{
				$num=$row['num'];
				$def=$row['def'];
				if(strpos($def,'::')===false && strpos($def,"'")===0){
					$def=substr($def,1,strlen($def)-2);
				}
				$rsdefa[$num]=$def;
			}
			unset($rsdef);
		}
		if (!empty($schema)) {
			$rs=$this->execute(sprintf("SELECT a.attname, t.typname, a.attlen, a.atttypmod, a.attnotnull, a.atthasdef, a.attnum FROM pg_class c, pg_attribute a, pg_type t, pg_namespace n WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) and c.relnamespace=n.oid and n.nspname='%s' and a.attname not like '....%%' AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum",$table_name,$table_name,$schema));
		}else{
			$rs=$this->execute(sprintf("SELECT a.attname,t.typname,a.attlen,a.atttypmod,a.attnotnull,a.atthasdef,a.attnum FROM pg_class c, pg_attribute a,pg_type t WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) and a.attname not like '....%%' AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum ",$table_name,$table_name));
		}
		/* @var $rs QDB_Result_Abstract */
		$retarr = array();
		$rs->fetchMode = QDB::fetch_mode_array;
		while (($row = $rs->fetchRow())) {
			$field = array();
			$field['name'] = $row['attname'];
			$field['type'] = strtolower($row['typname']);
			$field['length']=$row['attlen'];
			$field['attnum'] = $row['attnum'];
			if ($field['length'] <= 0) {
				$field['length'] = $row['atttypmod'] - 4;
			}
			if ($field['length'] <= 0) {
				$field['length'] = -1;
			}
			$field['scale'] = null;
			if ($field['type'] == 'numeric') {
				$field['scale'] = $field['length'] & 0xFFFF;
				$field['length'] >>= 16;
			}

			$field['has_default'] = ($row['atthasdef'] == 't');
			if ($field['has_default']) {
				$field['default'] = $rsdefa[$row['attnum']];
			}
			else
			$field['default']=null;
			$field['not_null'] = ($row['attnotnull'] == 't');
			if (is_array($keys)) {
				foreach($keys as $key) {
					if ($field['name'] == $key['column_name'] && $key['primary_key'] == 't') {
						$field['pk'] = true;
					} else {
						$field['pk'] = false;
					}
					if ($field['name'] == $key['column_name'] && $key['unique_key'] == 't') {
						$field['unique'] = true; // What name is more compatible?
					} else {
						$field['unique'] = false;
					}
				}
			}
			// 这里要对几种特殊的类型的默认值进行处理
			$field['ptype'] = $typeMap[strtolower($field['type'])];
			// 这里是为了配合解决无法取得InsertID的情况。
			if($field['ptype']=='r' || ($field['ptype']=='i' && strpos($field['default'],'nextval')!==false )){
				$field['has_default']=false;
				$field['default']=null;
			}

			$field['auto_incr'] = false;
			$field['binary'] = ($field['ptype']=='b');
			$field['unsigned'] = false ;
			if (!$field['binary'] ) {
				$d = $field['default'];
				if ($d != '' && $d != 'NULL') {
					$field['has_default'] = true;
					$field['default'] = $d;
				} else {
					$field['has_default'] = false;
				}
			}
			$field['desc'] = '';
			$retarr[strtolower($field['name'])] = $field;
		}
		return $retarr;
	}

	function metaTables($pattern = null, $schema = null)
	{
		if(!empty($schema)){
			$sql = sprintf("select relname from pg_class c, pg_namespace n WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) and c.relnamespace=n.oid and n.nspname='%s'",$table_name,$table_name,$schema);
		}else{
			$sql = sprintf("select relname from pg_class c, pg_namespace n WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) ",$table_name,$table_name);
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
	/**
     *  按照 PostgreSQL 的要求转义 DSN 字符串参数
     *
     * @param string $s
     *
     * @return string
     */
	function _addslashes($s)
	{
		$len = strlen($s);
		if ($len == 0) return "''";
		if (strncmp($s,"'",1) === 0 && substr($s,$len-1) == "'") return $s; // already quoted
		return "'".addslashes($s)."'";
	}

}
