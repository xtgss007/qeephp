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
 * 定义 QDB_ActiveRecord_Interface 接口
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Interface 接口确定了所有 QDB_ActiveRecord_Abstract 继承类必须实现的静态方法
 *
 * @package database
 */
interface QDB_ActiveRecord_Interface
{
    /**
     * 返回对象的定义
     *
     * @static
     *
     * @return array
     */
    static function __define();

    /**
     * 开启一个查询，查找符合条件的对象或对象集合
     *
     * @static
     *
     * @return QDB_Select
     */
    static function find();

    /**
     * 返回当前 ActiveRecord 类的元数据对象
     *
     * @static
     *
     * @return QDB_ActiveRecord_Meta
     */
    static function meta();
}
