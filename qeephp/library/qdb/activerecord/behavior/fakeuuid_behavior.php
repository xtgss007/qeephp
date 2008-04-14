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
    protected $seed = 't1MlGzPOy2WpUjTEBwN4aFR3mKdLs6gVcux89qSkCXh7iY5QbAJoeHIrDnfv0Z';

    /**
     * 种子长度
     *
     * @var int
     */
    protected $base;

    /**
     * 编码后的种子
     *
     * @var array
     */
    protected $code = array();

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
     * 构造函数
     *
     * @param string $class
     * @param array $settings
     */
    function __construct($class, array $settings)
    {
        parent::__construct($class, $settings);
        $this->base = strlen($this->seed);
        for ($i = 0; $i < $this->base; $i++) {
            $this->code[$i] = substr($this->seed, $i, 1);
        }
    }

    /**
     * 在数据库中创建 ActiveRecord 对象前调用
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function beforeCreate(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        $idname = $obj->idname();
        $props[$idname] = $this->encodeID($obj->getTable()->nextID($idname));
    }

    /**
     * 将一个整数转换为对应的字符串表现形式
     *
     * @param int $number
     * @param int $len
     *
     * @return string
     */
    protected function encodeID($number, $len = 6)
    {
        $number = intval($number);
        $offset = 0;
        $encode = '';
        $first = $number % $this->base;
        while ($len) {
            $pos = $number % $this->base;
            $pos = ($pos + $first + $offset) % $this->base;
            $encode .= $this->code[$pos];
            $number = intval($number / $this->base);
            $offset++;
            $len--;
        }
        $encode .= $this->code[$first];
        return $encode;
    }
}
