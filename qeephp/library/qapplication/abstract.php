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
 * 定义 QApplication_Abstract 类
 *
 * @package mvc
 * @version $Id$
 */

/**
 * QApplication_Abstract 提供应用程序级别的访问点
 *
 * QApplication_Abstract 实际上是一个前端控制器，负责解析请求，并调用相应的控制器动作。
 *
 * @package mvc
 */
abstract class QApplication_Abstract
{
    /**
     * QRequest 对象，封装当前的请求
     *
     * @var QRequest
     */
    public $request;

    /**
     * 日志对象
     *
     * @var QLog
     */
    public $log;

    /**
     * 用于提供验证服务的对象实例
     *
     * @var QACL
     */
    public $acl;

    /**
     * 当前访问的控制器
     *
     * @var QController_Abstract
     */
    public $current_controller;

    /**
     * 构造函数
     */
    protected function __construct()
    {
        require_once Q_DIR . '/qdebug.php';
        Q::register($this, 'app');
        load_boot_config();

        set_exception_handler(array($this, 'exceptionHandler'));
        set_magic_quotes_runtime(0);
        date_default_timezone_set(Q::getIni('l10n_default_timezone'));

        $this->log = Q::getService('log');
        $this->request = Q::getSingleton(Q::getIni('dispatcher_request_class'));
        $this->acl = Q::getService('acl');

        if (Q::getIni('runtime_session_provider')) {
            Q::loadClass(Q::getIni('runtime_session_provider'));
        }
        if (Q::getIni('runtime_session_start')) {
            session_start();
        }

        Q::setIni('dispatcher_on_access_denied',    array($this, 'onAccessDenied'));
        Q::setIni('dispatcher_on_action_not_found', array($this, 'onActionNotFound'));
    }

    /**
     * QeePHP 应用程序 MVC 模式入口
     */
    function run()
    {
        $this->executeAction(
            $this->request->controller_name,
            $this->request->action_name,
            $this->request->namespace,
            $this->request->module_name
        );
    }

    /**
     * 执行指定的 Action 方法
     *
     * @param string $controller_name
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     *
     * @return mixed
     */
    function executeAction($controller_name, $action_name, $namespace = null, $module = null)
    {
        // 检查是否有权限访问
        $arr = array($controller_name, $action_name, $namespace, $module);
        if (!$this->checkAuthorized($controller_name, $action_name, $namespace, $module)) {
            return call_user_func_array(Q::getIni('dispatcher_on_access_denied'), $arr);
        }

        // 尝试载入控制器
        $class_name = 'Controller_' . ucfirst(str_replace('_', '', $controller_name));
        if ($module) {
            $dir = ROOT_DIR . DS . 'module' . DS . $module . DS . 'controller';
        } else {
            $dir = ROOT_DIR . DS . 'app' . DS . 'controller';
        }
        if ($namespace) {
            $dir .= DS . $namespace;
        }

        // 构造控制器对象
        $filename = $controller_name . '_controller.php';
        try {
            Q::loadClassFile($filename, array($dir), $class_name);
        } catch (Exception $ex) {
            return call_user_func_array(Q::getIni('dispatcher_on_action_not_found'), $arr);
        }

        $controller = new $class_name($this);
        /* @var $controller QController_Abstract */

        $ret = $controller->execute($action_name, $namespace, $module);

        if (!headers_sent() && Q::getIni('runtime_response_header')) {
            header('Content-Type: text/html; charset=' . Q::getIni('i18n_response_charset'));
        }

        if (is_object($ret) && ($ret instanceof QResponse_Interface)) {
            $ret->controller = $controller;
            return $ret->run();
        }

        if (is_array($controller->view)) {
            if (!empty($controller->viewname)) {
                $viewname = $controller->viewname;
                if (strpos($viewname, '/') === false) {
                    $viewname = $controller_name . '/' . $viewname;
                }
            } else {
                $viewname = $controller_name . '/' . $action_name;
            }
            $response = new QResponse_Render($controller, $viewname, $namespace, $module);
            $response->data = $controller->view;
            $response->layouts = $controller->view_layouts;
            return $response->run();
        } elseif (!is_null($controller->view)) {
            echo $controller->view;
        }
        return null;
    }

    /**
     * 检查当前用户是否有权限访问指定的控制器和动作
     *
     * @param string $controller_name
     * @param string $action_name
     * @param string $namespace
     * @param string $module
     *
     * @return boolean
     */
    function checkAuthorized($controller_name, $action_name, $namespace = null, $module = null)
    {
        // 如果控制器没有提供 ACT，或者提供了一个空的 ACT，则假定允许用户访问
        $raw_act = $this->getControllerACT($controller_name, $namespace, $module);
        if (empty($raw_act)) { return true; }

        $act = $this->acl->formatACT($raw_act);
        $act['actions'] = array();
        if (isset($raw_act['actions']) && is_array($raw_act['actions'])) {
            foreach ($raw_act['actions'] as $rawaction_name => $raw_actions_act) {
                if ($rawaction_name !== 'action_all') {
                    $rawaction_name = strtolower($rawaction_name);
                }
                $act['actions'][$rawaction_name] = $this->acl->formatACT($raw_actions_act);
            }
        }

        // 取出用户角色信息
        $roles = $this->getUserRoles();
        // 首先检查用户是否可以访问该控制器
        if (!$this->acl->check($roles, $act)) { return false; }

        // 接下来验证用户是否可以访问指定的控制器方法
        $action_name = strtolower($action_name);
        if (isset($act['actions'][$action_name])) {
            return $this->acl->check($roles, $act['actions'][$action_name]);
        }

        // 如果当前要访问的控制器方法没有在 act 中指定，则检查 act 中是否提供了 ACTION_ALL
        if (!isset($act['actions']['ACTION_ALL'])) { return true; }
        return $this->acl->check($roles, $act['actions']['ACTION_ALL']);
    }

    /**
     * 获取指定控制器的访问控制表（ACT）
     *
     * @param string $controller_name
     * @param string $namespace
     * @param string $module
     *
     * @return array
     */
    function getControllerACT($controller_name, $namespace = null, $module = null)
    {
        if (empty($namespace)) {
            $namespace = 'default';
        }
        if (empty($module)) {
            $module = 'default';
        }

        $act = Q::getIni("acl_global_act/{$module}/{$namespace}/{$controller_name}");
        if ($act) {
            return $act;
        } else {
            return Q::getIni('acl_default_act');
        }
    }

    /**
     * 将用户数据保存到 session 中
     *
     * @param array $user
     * @param mixed $roles
     */
    function setUser(array $user, $roles)
    {
        $roles = Q::normalize($roles);
        $user[Q::getIni('acl_roles_key')] = implode(',', $roles);
        $key = Q::getIni('acl_session_key');
        $_SESSION[$key] = $user;
    }

    /**
     * 获取保存在 session 中的用户数据
     *
     * @return array
     */
    function getUser()
    {
        $key = Q::getIni('acl_session_key');
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    /**
     * 从 session 中清除用户数据
     */
    function cleanUser()
    {
        $key = Q::getIni('acl_session_key');
        unset($_SESSION[$key]);
    }

    /**
     * 获取 session 中用户信息包含的角色
     *
     * @return array
     */
    function getUserRoles()
    {
        $user = $this->getUser();
        $key = Q::getIni('acl_roles_key');
        $roles = isset($user[$key]) ? explode(',', $user[$key]) : '';
        return Q::normalize($roles);
    }

    /**
     * 默认的 on_access_denied 事件处理函数
     */
    abstract function onAccessDenied($controller_name, $action_name, $namespace = null, $module = null);

    /**
     * 默认的 on_action_not_found 事件处理函数
     */
    abstract function onActionNotFound($controller_name, $action_name, $namespace = null, $module = null);

    /**
     * 默认的异常处理
     */
    abstract function exceptionHandler(Exception $ex);
}
