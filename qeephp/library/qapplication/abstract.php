<?php
// $Id$

/**
 * @file
 * 定义 QApplication_Abstract 类
 *
 * @ingroup mvc
 * @{
 */

/**
 * QApplication_Abstract 提供应用程序级别的访问点
 *
 * @addtogroup application 应用程序实例对象
 * @ingroup mvc
 * @{
 *
 * 应用程序实例对象封装了整个应用程序共享的服务和数据，包括日志服务、运行时上下文、ACL 访问控制等。
 *
 * 每一个 QeePHP 应用都有一个对应的应用程序实例对象。
 * 该实例具有全局访问点，因此可以在应用程序的任何位置访问该应用程序实例对象。
 *
 * 默认的 QApplication_Abstract 继承类名为 MyApp。因此可以用下面的代码来访问应用程序实例对象：
 *
 * @code
 * $app = MyApp::instance();
 * @endcode
 *
 * 由于应用程序实例对象具有全局访问点，因此非常适合封装一些整个应用程序都需要的基础服务和共享功能。
 *
 * 此外，QApplication_Abstract 还承担了前端控制器的责任，负责解析请求，并调用相应的控制器动作。
 */
abstract class QApplication_Abstract
{
    /**
     * 用于提供验证服务的对象实例
     *
     * @var QACL
     */
    public $acl;

    /**
     * QContext 对象，封装当前的请求
     *
     * @var QContext
     */
    public $context;

    /**
     * 可以跨请求显示的提示信息
     *
     * @var string
     */
    protected $_flash_message;

    /**
     * 应用程序ID
     *
     * @var string
     */
    protected $_appid;

    /**
     * 应用程序的默认模块
     *
     * @var QApplication_Module
     */
    protected $_default_module;

    /**
     * 保存应用程序的设置信息
     *
     * @var array
     */
    static private $_app_configs = array();

    /**
     * 默认的应用程序ID
     *
     * @var string
     */
    static private $_default_appid;

    /**
     * 应用程序对象
     *
     * @var array
     */
    static private $_apps = array();

    /**
     * 构造函数
     *
     * 构造应用程序对象，并且初始化根上下文对象
     *
     * @param array $app_config
     */
    protected function __construct(array $app_config)
    {
        $this->_appid = $app_config['APPID'];
        self::$_apps[$this->_appid] = $this;
        self::setAppConfig($this->_appid, $app_config);
        $this->_default_module = QApplication_Module::instance(null, $this->_appid);
        $this->context = QContext::instance(null, $this->_appid);

        // #IFDEF DEBUG
        QLog::log(__METHOD__, QLog::DEBUG);
        // #ENDIF

        // 设置默认的异常处理例程
        // set_exception_handler(array($this, 'exceptionHandler'));
    }

    /**
     * 返回应用程序根目录
     *
     * @return string
     */
    function ROOT_DIR()
    {
        $app_config = self::getAppConfig($this->_appid);
        return $app_config['ROOT_DIR'];
    }

    /**
     * QeePHP 应用程序 MVC 模式入口
     *
     * @return mixed
     */
    function run()
    {
        // #IFDEF DEBUG
        QLog::log(__METHOD__, QLog::DEBUG);
        // #ENDIF

        // 设置默认的时区
        date_default_timezone_set($this->context->getIni('l10n_default_timezone'));

        // 设置 session 服务
        if ($this->context->getIni('runtime_session_provider'))
        {
            Q::loadClass($this->context->getIni('runtime_session_provider'));
        }

        // 打开 session
        if ($this->context->getIni('runtime_session_start'))
        {
            // #IFDEF DEBUG
            QLog::log('session_start()', QLog::DEBUG);
            // #ENDIF
            session_start();
        }

        // 设置验证失败错误处理的回调方法
        $this->context->setIni('dispatcher_on_access_denied', array($this, 'onAccessDenied'));
        // 设置处理动作方法未找到错误的回调方法
        $this->context->setIni('dispatcher_on_action_not_found', array($this, 'onActionNotFound'));

        // 从 session 中提取 flash message
        if (isset($_SESSION))
        {
            $key = $this->context->getIni('app_flash_message_key');
            $this->_flash_message = isset($_SESSION[$key]) ? $_SESSION[$key] : null;
            unset($_SESSION[$key]);
        }

        // 初始化访问控制对象
        $this->acl = Q::getSingleton('QACL');

        // 执行动作
        $context = QContext::instance($this->context->module_name, $this->_appid);
        return $this->_executeAction($context);
    }

    /**
     * 设置可以跨请求显示的提示信息
     */
    function setFlashMessage()
    {
        $args = func_get_args();
        $this->_flash_message = call_user_func_array('sprintf', $args);

        if (isset($_SESSION))
        {
            $_SESSION[$this->context->getIni('app_flash_message_key')] = $this->_flash_message;
        }
    }

    /**
     * 返回用 setFlashMessage() 设置的提示信息
     *
     * @return string
     */
    function getFlashMessage()
    {
        return $this->_flash_message;
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
        if (empty($raw_act))
        {
            return true;
        }

        $act = $this->acl->formatACT($raw_act);
        $act['actions'] = array();
        if (isset($raw_act['actions']) && is_array($raw_act['actions']))
        {
            foreach ($raw_act['actions'] as $key => $raw_actions_act)
            {
                $act['actions'][strtolower($key)] = $this->acl->formatACT($raw_actions_act);
            }
        }

        // 取出用户角色信息
        $roles = $this->getUserRoles();

        // 如果要访问的动作指定了 ACL，则以该动作的 ACL 来验证
        $action_name = strtolower($action_name);
        if (isset($act['actions'][$action_name]))
        {
            return $this->acl->check($roles, $act['actions'][$action_name]);
        }

        // 检查 act 中是否提供了 ACTION_ALL
        if (isset($act['actions'][QACL::ALL_ACTIONS]))
        {
            return $this->acl->check($roles, $act['actions'][QACL::ALL_ACTIONS]);
        }

        // 否则检查是否可以访问指定控制器
        return $this->acl->check($roles, $act);
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
        $path = 'acl_global_act';
        if ($module)
        {
            $path .= '/' . $module;
        }
        if ($namespace)
        {
            $path .= '/' . $namespace;
        }
        $act = $this->context->getIni("{$path}/{$controller_name}");

        if (!empty($act))
        {
            return $act;
        }
        else
        {
            // 如果指定控制器没有 ACL，则尝试从 ALL_CONTROLLERS 设置中读取
            $act = $this->context->getIni($path . '/' . QACL::ALL_CONTROLLERS);
            if (!empty($act))
            {
                return $act;
            }

            // 读取默认的 ACL
            return $this->context->getIni('acl_default_act');
        }
    }

    /**
     * 将用户数据保存到 session 中
     *
     * @param mixed $user
     * @param mixed $roles
     */
    function setUser($user, $roles)
    {
        $roles = Q::normalize($roles);
        $user[$this->context->getIni('acl_roles_key')] = implode(',', $roles);
        $key = $this->context->getIni('acl_session_key');
        $_SESSION[$key] = $user;
    }

    /**
     * 获取保存在 session 中的用户数据
     *
     * @return array
     */
    function getUser()
    {
        $key = $this->context->getIni('acl_session_key');
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    /**
     * 从 session 中清除用户数据
     */
    function cleanUser()
    {
        $key = $this->context->getIni('acl_session_key');
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
        $key = $this->context->getIni('acl_roles_key');
        $roles = isset($user[$key]) ? explode(',', $user[$key]) : '';
        return Q::normalize($roles);
    }

    /**
     * 默认的 on_access_denied 事件处理函数
     */
    abstract function onAccessDenied(QContext $context);

    /**
     * 默认的 on_action_not_found 事件处理函数
     *
     * @param QContext $context
     */
    abstract function onActionNotFound(QContext $context);

    /**
     * 默认的异常处理
     */
    abstract function exceptionHandler(Exception $ex);

    /**
     * 注册应用程序设置
     *
     * 注册时，如果还没有任何应用程序设置，则注册为默认应用。此时忽略 $default 参数的值。
     * 如果已经注册了应用程序，指定 $default 参数为 true 可以将新注册的应用程序设置为默认应用。
     *
     * @param string $appid
     * @param array $config
     * @param boolean $default
     */
    static function setAppConfig($appid, array $config, $default = false)
    {
        if (empty($appid))
        {
            // LC_MSG: Invalid APPID.
            throw new QApplication_Exception(__('Invalid APPID.'));
        }

        if (isset(self::$_app_configs[$appid]))
        {
            // LC_MSG: Can't register exists APPID "%s".
            throw new QApplication_Exception(__('Can\'t register exists APPID "%s".', $appid));
        }

        $config['APPID'] = $appid;
        self::$_app_configs[$appid] = $config;

        if (is_null(self::$_default_appid) || $default)
        {
            self::$_default_appid = $appid;
        }
    }

    /**
     * 读取指定应用的设置
     *
     * 如果不指定 $appid，则读取默认应用的设置。
     *
     * @param string $appid
     *
     * @return array
     */
    static function getAppConfig($appid = null)
    {
        if (is_null($appid))
        {
            $appid = self::$_default_appid;
        }

        if (!isset(self::$_app_configs[$appid]))
        {
            // LC_MSG: Unregistered APPID "%s".
            throw new QApplication_Exception(__('Unregistered APPID "%s".', $appid));
        }

        return self::$_app_configs[$appid];
    }

    /**
     * 取得默认应用程序 ID
     *
     * @return string
     */
    static function defaultAppID()
    {
        return self::$_default_appid;
    }

    /**
     * 缺的指定 ID 的应用程序对象
     *
     * @param string $appid
     */
    static function app($appid)
    {
        return isset(self::$_apps[$appid]) ? self::$_apps[$appid] : null;
    }

    /**
     * 执行指定的 Action 方法
     *
     * @param QContext $context
     * @param array $args
     *
     * @return mixed
     */
    protected function _executeAction(QContext $context, array $args = array())
    {
        // 检查是否有权限访问
        $controller_name = $context->controller_name;
        $action_name     = $context->action_name;
        $namespace       = $context->namespace;
        $module          = $context->module_name;

        QLog::log(sprintf('Execute controller action: "%s".', $context->getRequestUDI()));

        if (!$this->checkAuthorized($controller_name, $action_name, $namespace, $module))
        {
            $response = call_user_func($context->getIni('dispatcher_on_access_denied'), $context);
        }
        else
        {
            // 尝试载入控制器
            $class_name = $context->getIni('controller_class_prefix') . 'Controller_';
            if ($namespace)
            {
                $class_name .= ucfirst($namespace) . '_';
            }
            $class_name .= ucfirst(str_replace('_', '', $controller_name));

            $app_config = self::getAppConfig($this->_appid);
            if ($module)
            {
                $dir = $app_config['ROOT_DIR'] . "/modules/{$module}/controller";
            }
            else
            {
                $dir = $app_config['ROOT_DIR'] . "/app/controller";
            }

            if ($namespace)
            {
                $dir .= DS . $namespace;
            }

            // 构造控制器对象
            try
            {
                $filename = $controller_name . '_controller.php';
                Q::loadClassFile($filename, array($dir), $class_name);
            }
            catch (QException $ex)
            {
                $response = call_user_func($this->context->getIni('dispatcher_on_action_not_found'), $context);
                if (is_null($response))
                {
                    $response = '';
                }
            }

            if (!isset($response))
            {
                $controller = new $class_name($this, $context);

                /* @var $controller QController_Abstract */
                if ($context->isAJAX())
                {
                	$controller->view = null;
                }

                $response = $controller->_execute($args);
            }
        }

        if (is_object($response) && method_exists($response, 'execute'))
        {
            $response = $response->execute();
        }
        elseif ($response instanceof QController_Forward)
        {
            // 更新 flash message
            $key = $this->context->getIni('app_flash_message_key');
            unset($_SESSION[$key]);
            $response = $this->_executeAction($response->context, $response->args);
        }

        return $response;
    }
}

/**
 * @}
 */
