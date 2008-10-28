<?php
class QDB_Adapter_Pdo_Pgsql extends QDB_Adapter_Pdo_Abstract {
    protected $_pdo_type = 'pgsql';

    public function nextID($table_name, $field_name, $start_value = 1) {
        $schema = '';

        if (strpos('.', $table_name)) {
            $result = explode('.', $table_name);
            if (2 == count($result)) {
                $schema_name = $result[0];
                $table_name = $result[1];
            } else if (3 == count($result)) {
                $schema_name = $result[1];
                $table_name = $result[2];
            }
        }

        $seqName = sprintf('%s_%s_seq', trim($table_name, '"'), $field_name);
        if ($schema) { $seqName = sprintf('"%s"."%s"', $schema, $seqName); }

        $next_sql = sprintf("SELECT NEXTVAL('%s')", $seqName);

        try {
            $next_id = $this->execute($next_sql)->fetchOne();
        } catch (QDB_Exception $e) {
            if (!$this->createSeq($seqName, $start_value)) { return false; }

            $next_id = $this->execute($next_sql)->fetchOne();
        }

        $this->_insert_id = $next_id;
        return $this->_insert_id;
    }

    public function createSeq($seq_name, $start_value = 1) {
        return  $this->execute(sprintf('CREATE SEQUENCE %s START %s', $seqname, $start_value));
    }

    public function dropSeq($seq_name) {
        return $this->execute(sprintf('DROP SEQUENCE %s', $seqname));
    }

    public function insertID() {
        $this->_insert_id = $this->execute('SELECT LASTVAL();')->fetchOne();
        return $this->_insert_id;
    }

    public function identifier($name) {
        $name = trim($name, '"');
        return ($name != '*') ? "\"{$name}\"" : '*';
    }

    function selectLimit($sql, $offset = 0, $length = 30, array $inputarr = null)
    {
        if (strtoupper($length) != 'ALL') { $length = (int)$length; }
        $sql = sprintf('%s LIMIT %s OFFSET %d', $sql, $length, $offset);
        return $this->execute($sql, $inputarr);
    }

    public function qtable($table_name, $schema = null, $alias = null) {
        if (strpos($table_name, '.') !== false) {
            $parts = explode('.', $table_name);
            $table_name = $parts[1];
            $schema = $parts[0];
        }
        $table_name = trim($table_name, '"');
        $schema = trim($schema, '"');

        //public 是默认的schema
        if (strtoupper($schema) == 'PUBLIC') { $schema = ''; }
        $i = $schema != '' ? "\"{$schema}\".\"{$table_name}\"" : "\"{$table_name}\"";

        return empty($alias) ? $i : $i . " \"{$alias}\"";
    }

    /**
     * 启动事务
     */
    function startTrans()
    {
        if (!$this->_transaction_enabled) { return false; }

        if ($this->_trans_count == 0) {
            $this->execute('BEGIN;');
            $this->_has_failed_query = false;
        } elseif ($this->_trans_count && $this->_savepoint_enabled) {
            $savepoint = 'savepoint_'. $this->_trans_count;
            $this->execute("SAVEPOINT {$savepoint};");
            array_push($this->_savepoints_stack, $savepoint);
        }

        ++$this->_trans_count;
        return true;
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
        if ($this->_trans_count == 0)
        {
            return;
        }

        -- $this->_trans_count;
        if ($this->_trans_count == 0)
        {
            if ($this->_has_failed_query == false && $commit_on_no_errors)
            {
                $this->execute('COMMIT');
            }
            else
            {
                $this->execute('ROLLBACK');
            }
        }
        elseif ($this->_savepoint_enabled)
        {
            $savepoint = array_pop($this->_savepoints_stack);
            if ($this->_has_failed_query || $commit_on_no_errors == false)
            {
                $this->execute("ROLLBACK TO SAVEPOINT {$savepoint}");
            }
            else
            {
                $this->execute("RELEASE SAVEPOINT {$savepoint}");
            }
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
        if (strpos($table_name, '.') !== false) {
            $result = explode('.', $table_name);
            $schema = trim($result[0], '"');
            $table_name = trim($result[1], '"');
        }

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
            'uuid' => 'c',
            'xml' => 'x',
        );

        $table_name = trim($table_name, '"');
        $keys = $this->getAll(sprintf("SELECT ic.relname AS index_name, a.attname AS column_name,i.indisunique AS unique_key, i.indisprimary AS primary_key FROM pg_class bc, pg_class ic, pg_index i, pg_attribute a WHERE bc.oid = i.indrelid AND ic.oid = i.indexrelid AND (i.indkey[0] = a.attnum OR i.indkey[1] = a.attnum OR i.indkey[2] = a.attnum OR i.indkey[3] = a.attnum OR i.indkey[4] = a.attnum OR i.indkey[5] = a.attnum OR i.indkey[6] = a.attnum OR i.indkey[7] = a.attnum) AND a.attrelid = bc.oid AND (bc.relname = '%s' or bc.relname=lower('%s'))", $table_name, $table_name));

        $rsdefa = array();
        $sql = sprintf("SELECT d.adnum as num, d.adsrc as def from pg_attrdef d, pg_class c where d.adrelid=c.oid and (c.relname='%s' or c.relname=lower('%s')) order by d.adnum", $table_name, $table_name);
        $rsdef = $this->getAll($sql);

        if (count($rsdef)>0) {
            foreach ($rsdef as $row) {
                $num = $row['num'];
                $def = $row['def'];
                if (strpos($def, '::') === false && strpos($def, "'") === 0) {
                    $def = substr($def, 1, strlen($def) - 2);
                }
                $rsdefa[$num] = $def;
            }
            unset($rsdef);
        }
        if (!empty($schema)) {
            $rs = $this->execute(sprintf("SELECT a.attname, t.typname, a.attlen, a.atttypmod, a.attnotnull, a.atthasdef, a.attnum FROM pg_class c, pg_attribute a, pg_type t, pg_namespace n WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) and c.relnamespace=n.oid and n.nspname='%s' and a.attname not like '....%%' AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum", $table_name, $table_name, $schema));
        }else{
            $rs = $this->execute(sprintf("SELECT a.attname,t.typname,a.attlen,a.atttypmod,a.attnotnull,a.atthasdef,a.attnum FROM pg_class c, pg_attribute a,pg_type t WHERE relkind in ('r','v') AND (c.relname='%s' or c.relname = lower('%s')) and a.attname not like '....%%' AND a.attnum > 0 AND a.atttypid = t.oid AND a.attrelid = c.oid ORDER BY a.attnum ", $table_name, $table_name));
        }
        /* @var $rs QDB_Result_Abstract */
        $retarr = array();
        $cnt111 = 0;
        $rs->fetchMode = QDB::FETCH_MODE_ARRAY;
        while ($row = $rs->fetchRow()) {
            $field = array();
            $field['default'] = '';
            $field['name'] = $row['attname'];
            $field['type'] = strtolower($row['typname']);
            $field['length'] = $row['attlen'];
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
            $field['default'] = null;
            $field['not_null'] = ($row['attnotnull'] == 't');
            $field['pk'] = false;
            $field['unique'] = false;
            if (is_array($keys)) {
                foreach($keys as $key) {
                    if ($field['name'] == $key['column_name'])
                        $field['pk']=($key['primary_key'] == 't');
                    if ($field['name'] == $key['column_name'] )
                        $field['unique'] = ( $key['unique_key'] == 't');
                }
            }
            // 这里要对几种特殊的类型的默认值进行处理
            $field['ptype'] = $typeMap[strtolower($field['type'])];
            // 这里是为了配合解决无法取得InsertID的情况。
            if ($field['ptype'] == 'r' || ($field['ptype'] == 'i' && strpos($field['default'],'nextval') !== false)) {
                $field['has_default'] = false;
                $field['default'] = null;
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
        if (!empty($schema)) {
            $sql = sprintf("select relname from pg_class c, pg_namespace n WHERE c.relname !~ '^(pg_|sql_)' and c.relkind = 'r' and c.relnamespace = n.oid and n.nspname = %s", $this->qstr($schema));
        }else{
            $sql = "select relname from pg_class as c WHERE relkind = 'r' and relname !~ '^(pg_|sql_)'";
        }

        if (!empty($pattern)) {
            $sql .= sprintf(' AND (c.relname like %s or c.relname like %s)', $this->qstr($pattern), $this->qstr(strtolower($pattern)));
        }

        return $this->getCol($sql);
    }
}
