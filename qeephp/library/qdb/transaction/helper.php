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
 * 定义 QDB_Transaction_Helper 类
 *
 * @package database
 * @version $Id: helper.php 296 2008-04-11 16:05:01Z dualface $
 */

/**
 * QDB_Transaction_Helper 类为实现异常安全的事务提供帮助
 *
 * @package database
 */
class QDB_Transaction_Helper
{
	/**
	 * 是否将事务标记为已经失败
	 *
	 * @var boolean
	 */
	protected $_trans_failed;

	/**
	 * 析构函数执行时要抛出的异常
	 *
	 * @var Exception
	 */
	protected $_exception;

	/**
	 * 上一个异常处理例程
	 *
	 * @var callback
	 */
	protected $_previous_handler;

	/**
	 * 构造函数
	 *
	 * @param bool $trans_failed
	 */
	function __construct(& $trans_failed)
	{
		$this->_trans_failed =& $trans_failed;
		$this->_previous_handler = set_exception_handler(array($this, 'handler'));
		QDebug::dump(__METHOD__);
	}

	/**
	 * 析构函数
	 */
	function __destruct()
	{
		QDebug::dump(__METHOD__);
		$this->release();
		if ($this->_exception) {
			if ($this->_previous_handler) {
				call_user_func($this->_previous_handler, $this->_exception);
			} else {
				QException::dump($this->_exception);
			}
		}
	}

	function release()
	{
		restore_exception_handler();
	}

	/**
	 * 异常处理方法
	 *
	 * @param Exception $ex
	 */
	function handler(Exception $ex)
	{
		QDebug::dump(__METHOD__);
		$this->_trans_failed = true;
		$this->_exception = $ex;
	}
}
