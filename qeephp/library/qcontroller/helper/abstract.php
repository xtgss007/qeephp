<?php
// $Id$

/**
 * @file
 * 定义 QController_Helper_Abstract 类
 *
 * @ingroup helper
 *
 * @{
 */

/**
 * QController_Helper_Abstract 是所有控制器助手的基础类
 */
abstract class QController_Helper_Abstract
{
    /**
     * 上下文对象
     *
     * @var QContext
     */
    public $context;

    /**
     * 构造函数
     *
     * @param QContext $context
     */
    function __construct(QContext $context)
    {
        $this->context = $context;
    }
}

/**
 * @}
 */
