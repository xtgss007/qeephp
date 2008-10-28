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
 * 定义 QDB_Exception 类
 *
 * @package database
 * @version $Id: exception.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB_Exception 用于封装数据库操作相关的异常
 *
 * @package database
 */
class QDB_Exception extends QException
{
	/**
	 * 引发异常的 SQL 语句
	 *
	 * @var string
	 */
	public $sql;

	function __construct($sql, $error, $errcode = 0)
	{
		$this->sql = $sql;
		parent::__construct($error, $errcode);
	}
}

