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
 * @version $Id$
 */

/**
 * QDB_Transaction_Helper 类为实现异常安全的事务提供帮助
 *
 * @package database
 */
class QDB_Transaction_Helper
{
    /**
     * 事务对象
     *
     * @var QDB_Transaction
     */
    protected $transaction;

    /**
     * 析构函数执行时要抛出的异常
     *
     * @var Exception
     */
    protected $exception;

    /**
     * 上一个异常处理例程
     *
     * @var callback
     */
    protected $previous_handler;

    /**
     * 构造函数
     *
     * @param QDB_Transaction $transaction
     */
    function __construct(QDB_Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->previous_handler = set_exception_handler(array($this, 'handler'));
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        restore_exception_handler();
        if ($this->exception) {
            if ($this->previous_handler) {
                call_user_func($this->previous_handler, $this->exception);
            } else {
                QException::dump($this->exception);
            }
        }
    }

    /**
     * 异常处理方法
     *
     * @param Exception $ex
     */
    function handler(Exception $ex)
    {
        $this->transaction->setTransFailed();
        $this->transaction->unbindHelper();
        unset($this->transaction);
        $this->transaction = null;
        $this->exception = $ex;
    }
}
