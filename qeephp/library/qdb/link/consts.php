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
 * 定义 QDB_Link_Consts 接口
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Link_Consts 接口定义了可用的关联类型
 *
 * @package database
 */
interface QDB_Link_Consts
{
    /**
     * 定义四种关联关系
     */
    const has_one       = 'has_one';
    const has_many      = 'has_many';
    const belongs_to    = 'belongs_to';
    const many_to_many  = 'many_to_many';
}
