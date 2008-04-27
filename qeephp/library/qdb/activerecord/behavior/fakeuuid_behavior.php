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
 * 定义 Behavior_Fakuuid 类
 *
 * @package database
 * @version $Id$
 */

/**
 * Behavior_Fakeuuid 实现了“伪”UUID算法
 *
 * @package database
 */
class Behavior_Fakeuuid extends QDB_ActiveRecord_Behavior_Abstract
{
    /**
     * 种子
     *
     * @var array
     */
    protected $_settings = array(
        'seed' => 't1MlGzPOy2WpUjTEBwN4aFR3mKdLs6gVcux89qSkCXh7iY5QbAJoeHIrDnfv0Z',
    );

    /**
     * 种子长度
     *
     * @var int
     */
    protected $_base;

    /**
     * 编码后的种子
     *
     * @var array
     */
    protected $_code = array();

    /**
     * 该方法返回行为插件定义的回调事件以及扩展的方法
     *
     * @return array
     */
    function __callbacks()
    {
        return array(
            array(self::before_create, '_before_create'),
        );
    }

    /**
     * 构造函数
     *
     * @param string $class
     * @param array $settings
     */
    function __construct($class, array $settings)
    {
        parent::__construct($class, $settings);
        $this->_base = strlen($this->_settings['seed']);
        for ($i = 0; $i < $this->_base; $i++) {
            $this->_code[$i] = substr($this->_seed, $i, 1);
        }
    }

    /**
     * 在数据库中创建 ActiveRecord 对象前调用
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function _before_create(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        $idname = $obj->idname();
        $props[$idname] = $this->_encodeID($obj->getMeta()->table->nextID($idname));
    }

    /**
     * 将一个整数转换为对应的字符串表现形式
     *
     * @param int $number
     * @param int $len
     *
     * @return string
     */
    protected function _encodeID($number, $len = 6)
    {
        $number = intval($number);
        $offset = 0;
        $encode = '';
        $first = $number % $this->_base;
        while ($len) {
            $pos = $number % $this->_base;
            $pos = ($pos + $first + $offset) % $this->_base;
            $encode .= $this->_code[$pos];
            $number = intval($number / $this->_base);
            $offset++;
            $len--;
        }
        $encode .= $this->_code[$first];
        return $encode;
    }
}

