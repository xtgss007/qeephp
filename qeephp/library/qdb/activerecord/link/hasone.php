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
 * 定义 QDB_ActiveRecord_Link_HasOne 类
 *
 * @package database
 * @version $Id$
 */

class QDB_ActiveRecord_Link_Abstract extends QDB_Link_Abstract
{

    /**
     * 读取目标数据时，要读取数据的哪些属性
     *
     * @var array|string
     */
    public $on_find_fields = '*';
}

/**
 * QDB_ActiveRecord_Link_HasOne 封装了 ActiveRecord 对象间的 has one 关联
 *
 * @package databsae
 */
class QDB_ActiveRecord_Link_HasOne extends QDB_ActiveRecord_Link_Abstract
{
    function __construct(array $params)
    {
        parent::__construct($params, self::has_one);
    }
}
