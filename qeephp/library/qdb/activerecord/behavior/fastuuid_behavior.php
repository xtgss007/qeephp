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
 * 定义 Behavior_Fastuuid 类
 *
 * @package database
 * @version $Id$
 */

/**
 * Behavior_Fastuuid 为模型生成 64bit 的不重复 id，相比 fakeuuid 插件速度更快
 *
 * 感谢“Ivan Tan|谭俊青 DrinChing (at) Gmail.com”提供的算法。
 *
 * @package database
 */
class Behavior_Fastuuid extends QDB_ActiveRecord_Behavior_Abstract
{
    /**
     * 设置
     *
     * @var array
     */
    protected $settings = array(
        'being_timestamp' => 1206576000, // 2008-03-27
        'suffix_len' => 3,
    );

    /**
     * 该方法返回行为插件定义的回调事件以及扩展的方法
     *
     * @return array
     */
    function __callbacks()
    {
        return array(
            array(self::before_create, 'beforeCreate'),
        );
    }

    /**
     * 在数据库中创建 ActiveRecord 对象前调用
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function beforeCreate(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        $props[$obj->idname()] = self::newID($this->settings['being_timestamp'], $this->settings['suffix_len']);
    }

    /**
     * 生成不重复的 UUID
     *
     * @param int $being_timestamp
     * @param int $suffix_len
     *
     * @return string
     */
    static function newID($being_timestamp, $suffix_len)
    {
        $time = explode( ' ', microtime());
        $id = ($time[1] - $being_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
        if ($suffix_len > 0) {
            $id .= substr(sprintf('%010u', mt_rand()), 0, $suffix_len);
        }
        return $id;
    }
}
