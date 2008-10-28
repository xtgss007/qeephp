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
 * 定义 QGenerator_Runner_Abstract 类
 */

/**
 * QGenerator_Runner_Abstract 是所有代码生成器运行入口的基础类
 *
 * @package generator
 */
abstract class QGenerator_Runner_Abstract
{
    /**
     * 可用的生成器列表
     *
     * @var array
     */
    protected $generators_list = array('controller', 'table', 'model');

    /**
     * 获取指定类型的生成器对象实例（每次调用都获得一个新的）
     *
     * @param string $type
     *
     * @return QGenerator_Abstract
     */
    protected function getGenerator($type)
    {
        $type = strtolower($type);
        if (!in_array($type, $this->generators_list)) {
            throw new QGenerator_Exception(sprintf('Invalid generator type "%s".', $type));
        }

        $class_name = 'QGenerator_' . ucfirst($type);
        if (!class_exists($class_name, false)) {
            require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $type . '.php';
        }

        return new $class_name();
    }

    /**
     * 执行代码生成器
     */
    abstract function run();
}
