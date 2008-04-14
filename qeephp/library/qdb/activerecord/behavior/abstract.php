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
 * 定义 QDB_ActiveRecord_Behavior_Abstract 类
 *
 * @package database
 * @version $Id: interface.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB_ActiveRecord_Behavior_Abstract 抽象类是所有行为插件的基础类
 *
 * @package database
 */
abstract class QDB_ActiveRecord_Behavior_Abstract implements QDB_ActiveRecord_Callbacks
{
    /**
     * ActiveRecord 继承类的元信息对象
     *
     * @var QDB_ActiveRecord_Meta
     */
    protected $meta;

    /**
     * 插件的设置信息
     *
     * @var array
     */
    protected $settings = array();

    /**
     * 构造函数
     *
     * QDB_ActiveRecord_Meta $meta
     * @param array $settings
     */
    function __construct(QDB_ActiveRecord_Meta $meta, array $settings)
    {
        $this->meta = $meta;
        foreach ($this->settings as $key => $value) {
            if (!empty($settings[$key])) {
                $this->settings[$key] = $settings[$key];
            }
        }
        $this->bind();
    }

    /**
     * 绑定行为插件
     */
    abstract function bind();

    /**
     * 插件绑定完成后调用
     */
    function afterBind()
    {
    }
}
