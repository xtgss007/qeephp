<?php

// $Id$


/**
 * @file
 * 定义 Behavior_Fastuuid 类
 *
 * @ingroup behavior
 *
 * @{
 */

/**
 * Behavior_Fastuuid 为模型生成 64bit 的不重复整数 ID
 *
 * 感谢“Ivan Tan|谭俊青 DrinChing (at) Gmail.com”提供的算法。
 */
class Model_Behavior_Fastuuid extends QDB_ActiveRecord_Behavior_Abstract
{

    /**
     * 设置
     *
     * @var array
     */
    protected $_settings = array
    (
        //! 计算种子数的开始时间
        'being_timestamp' => 1206576000, // 2008-03-27
        //! 计算 ID 时要添加多少位随机数
        'suffix_len' => 3
    );

    /**
     * 构造函数
     *
     * @param QDB_ActiveRecord_Meta $meta
     * @param array $settings
     */
    function __construct(QDB_ActiveRecord_Meta $meta, array $settings)
    {
        parent::__construct($meta, $settings);

        if ($meta->idname_count > 1)
        {
            throw new QDB_ActiveRecord_CompositePKIncompatibleException($this->_meta->class_name, __CLASS__);
        }
    }

    /**
     * 绑定行为插件
     */
    function bind()
    {
        $this->_addStaticMethod('genUUID', array(__CLASS__, 'genUUID'));
        $this->_addEventHandler(self::BEFORE_CREATE, array($this, '_before_create'));
    }

    /**
     * 在数据库中创建 ActiveRecord 对象前调用
     *
     * @param QDB_ActiveRecord_Abstract $obj
     */
    function _before_create(QDB_ActiveRecord_Abstract $obj)
    {
        $new_id = self::genUUID($this->_settings['being_timestamp'], $this->_settings['suffix_len']);
        $idname = reset($this->_meta->idname);
        $obj->changePropForce($idname, $new_id);
    }

    /**
     * 生成不重复的 UUID
     *
     * @param int $being_timestamp
     * @param int $suffix_len
     *
     * @return string
     */
    static function genUUID($being_timestamp, $suffix_len)
    {
        $time = explode(' ', microtime());
        $id = ($time[1] - $being_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
        if ($suffix_len > 0)
        {
            $id .= substr(sprintf('%010u', mt_rand()), 0, $suffix_len);
        }
        return $id;
    }

}
