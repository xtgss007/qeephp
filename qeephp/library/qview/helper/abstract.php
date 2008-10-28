<?php
// $Id$

/**
 * @file
 * 定义 QView_Helper_Abstract 基础类
 *
 * @ingroup view
 *
 * @{
 */

/**
 * QView_Helper_Abstract 是所有视图助手的基础类
 */
abstract class QView_Helper_Abstract
{
    /**
     * 调用该助手的视图适配器
     *
     * @var QView_Adapter_Abstract
     */
    public $adapter;

    /**
     * 构造函数
     *
     * @param QView_Adapter_Abstract $adapter
     */
    function __construct(QView_Adapter_Abstract $adapter)
    {
        $this->adapter = $adapter;
    }
}

/**
 * @}
 */
