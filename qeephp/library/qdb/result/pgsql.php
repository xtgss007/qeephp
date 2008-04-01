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
 * 定义 QDB_Result_Pgsql 类
 *
 * @package database
 * @version $Id: pgsql.php
 */

/**
 * QDB_Result_Pgsql 封装了一个 PostgreSQL 查询句柄，便于释放资源
 *
 * @package database
 */
class QDB_Result_Pgsql extends QDB_Result_Abstract
{
    function free()
    {
        if ($this->handle) { pg_free_result($this->handle); }
        $this->handle = null;
    }

    function fetchRow()
    {
        if ($this->fetch_mode == QDB::fetch_mode_assoc) {
            return pg_fetch_assoc($this->handle);
        } else {
            return pg_fetch_array($this->handle);
        }
    }
}
