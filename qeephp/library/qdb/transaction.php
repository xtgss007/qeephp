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
 * 定义 QDB_Transaction 类
 *
 * @package database
 * @version $Id: transaction.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB_Transaction 类实现了一个异常安全的数据库事务机制
 *
 * @package database
 */
class QDB_Transaction
{
	/**
	 * 数据库访问对象
	 *
	 * @var QDB_Adapter_Abstract
	 */
	protected $_dbo;

	/**
	 * 指示当前是否在事务中
	 *
	 * @var boolean
	 */
	protected $_in_transaction = true;

	/**
	 * 是否将事务标记为已经失败
	 *
	 * @var boolean
	 */
	protected $_trans_failed = false;

	/**
	 * 该事务对象使用的助手
	 *
	 * @var QDB_Transaction_Helper
	 */
	protected $_helper;

	/**
	 * 事务的ID
	 *
	 * @var string
	 */
	protected $_id;

	/**
	 * 构造函数
	 *
	 * @param QDB_Adapter_Abstract $dbo
	 */
	function __construct(QDB_Adapter_Abstract $dbo)
	{
		QDebug::dump(__METHOD__);
		$this->_dbo = $dbo;
		$this->_dbo->startTrans();
		$this->_id = $dbo->getID();
		$this->_helper = new QDB_Transaction_Helper($this->_trans_failed);
	}

	/**
	 * 析构函数
	 */
	function __destruct()
	{
		// $this->_helper->release();
		unset($this->_helper);
		$this->_helper = null;
		QDebug::dump(__METHOD__);
		if ($this->_trans_failed) {
			$this->rollback();
		} else {
			$this->commit();
		}
	}

	/**
	 * 完成事务，根据事务期间的查询是否出错决定是提交还是回滚事务
	 *
	 * 如果 $commit_on_no_errors 参数为 true，当事务期间所有查询都成功完成时，则提交事务，否则回滚事务；
	 * 如果 $commit_on_no_errors 参数为 false，则强制回滚事务。
	 *
	 * @param $commit_on_no_errors
	 */
	function commit($commit_on_no_errors = true)
	{
		if (!$this->_in_transaction) { return; }
		$this->_dbo->completeTrans($commit_on_no_errors);
		$this->_in_transaction = false;
	}

	/**
	 * 回滚事务
	 */
	function rollback()
	{
		if (!$this->_in_transaction) { return; }
		$this->_dbo->completeTrans(false);
		$this->_in_transaction = false;
	}

	/**
	 * 指示在调用 complete_trans() 时回滚事务
	 */
	function setTransFailed()
	{
		$this->_trans_failed = true;
		$this->_dbo->setTransFailed();
	}

	/**
	 * 确定事务过程中是否出现失败的查询
	 */
	function hasFailedQuery()
	{
		return $this->_dbo->hasFailedQuery();
	}
}
