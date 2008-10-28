<?php
// $Id$

/**
 * 定义 QDB_ActiveRecord_ValidateFailedException 异常
 */

/**
 * QDB_ActiveRecord_ValidateFailedException 异常封装了 ActiveRecord 对象的验证失败事件
 */
class QDB_ActiveRecord_ValidateFailedException extends QValidator_ValidateFailedException
{
    /**
     * 被验证的对象
     *
     * @var QDB_ActiveRecord_Abstract
     */
    public $validate_obj;

    /**
     * 构造函数
     *
     * @param array $error
     * @param QDB_ActiveRecord_Abstract $obj
     */
    function __construct(array $error, QDB_ActiveRecord_Abstract $obj)
    {
        $this->validate_obj = $obj;
        parent::__construct($error, $obj->toArray(0));
    }
}

