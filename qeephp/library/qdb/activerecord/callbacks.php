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
 * 定义 QDB_ActiveRecord_Callbacks 接口
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Callbacks 定义了 ActiveRecord 对象及行为插件可用的回调类型
 *
 * @package database
 */
interface QDB_ActiveRecord_Callbacks
{
    /**
     * 对象生存期事件
     */
    const before_find                 = 'before_find';                  // 查询前
    const after_find                  = 'after_find';                   // 查询后
    const after_initialize            = 'after_initialize';             // 初始化后

    const before_save                 = 'before_save';                  // 保存之前
    const after_save                  = 'after_save';                   // 保存之后

    const before_create               = 'before_create';                // 创建之前
    const after_create                = 'after_create';                 // 创建之后

    const before_update               = 'before_update';                // 更新之前
    const after_update                = 'after_update';                 // 更新之后

    const before_validation           = 'before_validation';            // 验证之前
    const after_validation            = 'after_validation';             // 验证之后

    const before_validation_on_create = 'before_validation_on_create';  // 创建记录验证之前
    const after_validation_on_create  = 'after_validation_on_create';   // 创建记录验证之后

    const before_validation_on_update = 'before_validation_on_update';  // 更新记录验证之前
    const after_validation_on_update  = 'after_validation_on_update';   // 更新记录验证之后

    const before_destroy              = 'before_destroy';               // 销毁之前
    const after_destroy               = 'after_destroy';                // 销毁之后
}
