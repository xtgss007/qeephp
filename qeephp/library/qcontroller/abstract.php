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
 * 定义 QController_Abstract 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QController_Abstract 实现了一个其它控制器的超类，
 * 为开发者自己的控制器提供了一些方便的成员变量和方法
 *
 * @package mvc
 */
abstract class QController_Abstract
{
    /**
     * 应用程序对象
     *
     * @var MyApp
     */
    public $app;

    /**
     * 封装请求的对象
     *
     * @var QRequest
     */
    public $request;

    /**
     * 封装 SESSION 对象
     *
     * @var QSession
     */
    public $session;

    /**
     * 控制器要使用的布局视图
     *
     * @var string
     */
    public $view_layouts = 'default';

    /**
     * 控制器要使用的视图
     *
     * @var string
     */
    public $viewname = null;

    /**
     * 控制器动作要渲染的数据
     *
     * @var array
     */
    public $view = array();

    /**
     * 控制器动作执行完毕后的响应对象
     *
     * @var QResponse_Interface
     */
    public $response;

    /**
     * 构造函数
     */
    function __construct(QApplication_Abstract $app)
    {
        $this->app = $app;
        $this->app->current_controller = $this;
        $this->request = $app->request;
    }

    /**
     * 执行指定的动作
     *
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     *
     * @return mixed
     */
    function execute($action_name, $namespace = null, $module = null)
    {
        $action_method = 'action' . ucfirst(strtolower($action_name));
        $action_method = str_replace('_', '', $action_method);
        if (method_exists($this, $action_method)) {
            $this->beforeExecute($action_name, $namespace, $module);
            $ret = $this->{$action_method}();
            return $this->afterExecute($action_name, $ret, $namespace, $module);
        } else {
            $arr = array($this->request->controller_name, $action_name, $namespace, $module);
            $this->view = null;
            return call_user_func_array(Q::getIni('dispatcher_on_action_not_found'), $arr);
        }
    }

    /**
     * 魔法方法，用于自动加载 helper
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        static $dirs;

        if (is_null($dirs)) {
            $dirs = array(Q_DIR . '/qcontroller/helper');
            if ($this->request->module_name) {
                $dir = ROOT_DIR . '/module/' . $this->request->module_name . '/helper';
            } else {
                $dir = ROOT_DIR . '/app/helper';
            }
            if ($this->request->namespace) {
                $dir .= '/' . $this->request->namespace;
            }
            $dirs[] = $dir;
        }

        $class_name = 'Helper_' . ucfirst($varname);
        $filename = $varname . '_helper.php';
        try {
            Q::loadClassFile($filename, $dirs, $class_name);
            $this->{$varname} = new $class_name($this);
            return $this->{$varname};
        } catch (Exception $ex) {
            if (!class_exists($class_name, false)) {
                // LC_MSG: Property "%s" not defined.
                throw new QException(__('Property "%s" not defined.', $varname));
            } else {
                throw $ex;
            }
        }
    }

    /**
     * 执行控制器动作之前调用
     *
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     */
    function beforeExecute($action_name, $namespace, $module)
    {
        return true;
    }

    /**
     * 执行控制器动作之后调用
     *
     * @param string $action_name
     * @param mixed $ret
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     *
     * @return mixed
     */
    function afterExecute($action_name, $ret, $namespace = null, $module = null)
    {
        return $ret;
    }

    /**
     * 重定向浏览器
     *
     * @param string $url
     * @param int $delay
     */
    protected function redirect($url, $delay = 0)
    {
        return new QResponse_Redirect($url, $delay);
    }

    /**
     * 构造限於当前控制器的 URL
     *
     * @param string $action
     * @param array $params
     *
     * @return string
     */
    protected function url($action, array $params = null)
    {
        return $this->url->make($this->request->getControllerName(), $action, $params);
    }
}
