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
 * 定义 Helper_Url 类
 *
 * @package helper
 * @version $Id$
 */

/**
 * Helper_Url 类提供生成 URL 的功能
 *
 * @package helper
 */
class Helper_Url
{
    /**
     * QController_Abstract 实例
     *
     * @var QController_Abstract
     */
    protected $controller;

    /**
     * 构造函数
     *
     * @param QController_Abstract $controller
     */
    function __construct(QController_Abstract $controller = null)
    {
        $this->controller = $controller;
    }

    /**
     * 构造 url
     *
     * @param string $controller_name
     * @param string $action_name
     * @param array $params
     * @param string $namespace
     * @param string $module
     *
     * @return string
     */
    function make($controller_name = null, $action_name = null, $params = null, $namespace = null, $module = null)
    {
        $baseuri = $this->controller->request->getBaseUri();

        if (is_null($namespace)) {
            $namespace = $this->controller->request->namespace;
        }
        if (is_null($module)) {
            $module = $this->controller->request->module_name;
        }
        if (is_null($controller_name) && !is_null($this->controller)) {
            $controller_name = $this->controller->request->getControllerName();
        }

        // 确定控制器和动作的名字
        $controller_name = empty($controller_name) ? Q::getIni('dispatcher_default_controller') : $controller_name;
        $action_name = empty($action_name) ? Q::getIni('dispatcher_default_action') : $action_name;
        $controller_name = strtolower($controller_name);
        $action_name = strtolower($action_name);

        $url = $baseuri . '?' . Q::getIni('dispatcher_controller_accessor'). '=' . $controller_name;
        $url .= '&' . Q::getIni('dispatcher_action_accessor') . '=' . $action_name;
        if ($namespace) {
            $url .= '&' . Q::getIni('dispatcher_namespace_accessor') . '=' . $namespace;
        }
        if ($module) {
            $url .= '&' . Q::getIni('dispatcher_module_accessor') . '=' . $module;
        }

        if (is_array($params) && !empty($params)) {
            $url .= '&' . $this->encode_args($params);
        }

        return $url;
    }

    /**
     * 将数组转换为可通过 url 传递的字符串连接
     *
     * @param array $args
     *
     * @return string
     */
    function encode_args($args)
    {
        $str = '';
        $pair = '=';
        $sc = '&';

        foreach ($args as $key => $value) {
            if (is_null($value) || $value === '') { continue; }
            if (is_array($value)) {
                $append = $this->encode_args($value);
            } else {
                $append = rawurlencode($key) . $pair . rawurlencode($value);
            }
            if (substr($str, -1) != $sc) {
                $str .= $sc;
            }
            $str .= $append;
        }
        return substr($str, 1);
    }
}
