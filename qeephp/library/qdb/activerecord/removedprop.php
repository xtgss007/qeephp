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
 * 定义 QDB_ActiveRecord_RemovedProp 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_RemovedProp 类封装一个被移除的属性，用于 ActiveRecord 的 create() 和 update() 操作
 *
 * @package database
 */
class QDB_ActiveRecord_RemovedProp
{
    /**
     * 构造函数
     *
     */
    private function __construct()
    {
    }

    /**
     * 获得 QDB_ActiveRecord_RemovedProp 的唯一实例
     *
     * @return QDB_ActiveRecord_RemovedProp
     */
    static function instance()
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new QDB_ActiveRecord_RemovedProp();
        }
        return $instance;
    }

    /**
     * 返回对象的字符串呈现
     *
     * @return string
     */
    function __toString()
    {
        return '';
    }
}
