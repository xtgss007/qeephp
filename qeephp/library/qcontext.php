<?php
// $Id: qcontext.php 1436 2008-10-27 16:32:45Z dualface $

/**
 * @file
 * 定义 QContext 类
 *
 * @ingroup core
 * @{
 */

/**
 * QContext 类封装了一个模块的运行时上下文环境
 *
 * 部分方法参考 Zend Framework 实现。
 *
 * @addtogroup core 运行时上下文
 * @{
 */
class QContext implements ArrayAccess
{
    /**
     * 指示 destinfo() 方法要提取的内容
     */
    const DESTINFO_ALL          = 'all';
    const DESTINFO_CONTROLLER   = 'controller';
    const DESTINFO_ACTION       = 'action';
    const DESTINFO_NAMESPACE    = 'namespace';
    const DESTINFO_MODULE       = 'module';

    /**
     * 指示 URL 模式
     */
    const URL_MODE_STANDARD     = 'standard';
    const URL_MODE_PATHINFO     = 'pathinfo';
    const URL_MODE_REWRITE      = 'rewrite';

    /**
     * 请求包含的模块名
     *
     * @var string
     */
    public $module_name;

    /**
     * 请求包含的命名空间
     *
     * @var string
     */
    public $namespace;

    /**
     * 请求包含的控制器名称
     *
     * @var string
     */
    public $controller_name;

    /**
     * 请求包含的动作名
     *
     * @var string
     */
    public $action_name;

    /**
     * 当前上下文对象的名字
     *
     * @var string
     */
    protected $_name;

    /**
     * 附加的参数
     *
     * @var array
     */
    protected $_params = array();

    /**
     * 父级上下文对象
     *
     * @var QContext
     */
    protected $_parent;

    /**
     * 该上下文对象所属的应用程序
     *
     * @var QApplication_Abstract
     */
    protected $_app;

    /**
     * 该上下文对象所属的模块
     *
     * @var QApplication_Module
     */
    protected $_module;

    /**
     * 解析请求使用的路由对象
     *
     * @var QRouter
     */
    protected $_router;

    /**
     * 根上下文对象
     *
     * @var QContext
     */
    protected static $_root;

    /**
     * URL 模式
     *
     * @var int
     */
    protected static $_url_mode;

    /**
     * 当前模块的应用程序设置
     *
     * @var array
     */
    private $_config = array();

    /**
     * REQUEST_URI
     *
     * @var string
     */
    static private $_request_uri;

    /**
     * 不包含任何查询参数的 URI（但包含脚本名称）
     *
     * @var string
     */
    static private $_base_uri;

    /**
     * 请求 URL 中的基础路径（不含脚本名称）
     *
     * @var string
     */
    static private $_base_dir;

    /**
     * 对象注册表
     *
     * @var array
     */
    static private $_objects = array();

    /**
     * 类搜索路径
     *
     * @var array
     */
    static private $_class_path = array();

    /**
     * 各个模块的 QContext 对象
     *
     * @var QContext
     */
    static private $_instances = array();

    /**
     * 构造函数
     *
     * @param QApplication_Module $module
     */
    protected function __construct(QApplication_Module $module)
    {
        $module_name = $module->module_name();
        $appid = $module->appid();
        self::$_instances[$appid][$module_name] = $this;
        $this->_app = QApplication_Abstract::app($appid);
        $this->_module = $module;

        if ($module_name == '#default#')
        {
            // 初始化应用程序默认的上下文对象
            $this->_config = require Q_DIR . '/_config/default_config.php';
            $this->setIni($module->config());
        }
        else
        {
            // 构造其他模块的上下文对象
            $root = self::$_instances[$appid]['#default#'];

            $this->module_name      = $module_name;
            $this->namespace        = $root->namespace;
            $this->controller_name  = $root->controller_name;
            $this->action_name      = $root->action_name;
            $this->_router          = $root->router();
            $this->_params          = $root->_params;
            $this->_parent          = $root;

            // 载入该模块的配置
            $this->_config = $module->config();
            $app_config = QApplication_Abstract::getAppConfig($appid);
            Q::import($app_config['ROOT_DIR'] . "/modules/{$module_name}/model");
            Q::import($app_config['ROOT_DIR'] . "/modules/{$module_name}");
        }

        $this->_name = $module_name;
        $this->_initOneTime();
        $this->_prepare();
    }

    /**
     * 返回指定模块的上下文对象
     *
     * @param string $module_name
     * @param string $appid
     *
     * @return QContext
     */
    static function instance($module_name = null, $appid = null)
    {
        if (empty($appid))
        {
            $appid = QApplication_Abstract::defaultAppID();
        }
        if (empty($module_name))
        {
            $module_name = '#default#';
        }

        if (!isset(self::$_instances[$appid][$module_name]))
        {
            new QContext(QApplication_Module::instance($module_name, $appid));
        }
        return self::$_instances[$appid][$module_name];
    }

    /**
     * 返回该上下文对象所属的应用程序对象
     *
     * @return QApplication_Abstract
     */
    function app()
    {
        return $this->_app;
    }

    /**
     * 返回应用程序根目录
     *
     * @return string
     */
    function ROOT_DIR()
    {
        return $this->_app->ROOT_DIR();
    }

    /**
     * 返回该上下文对象所属的模块
     *
     * @return QApplication_Module
     */
    function module()
    {
        return $this->_module;
    }

    /**
     * 返回该上下文对象使用的路由器对象
     *
     * @return QRouter
     */
    function router()
    {
        return $this->_router;
    }

    /**
     * 返回上下文对象的名字
     *
     * @return string
     */
    function name()
    {
        return $this->_name;
    }

    /**
     * 返回父级上下文对象
     *
     * @return QContext
     */
    function parent()
    {
        if (is_null($this->_parent))
        {
            return self::$_root;
        }
        return $this->_parent;
    }

    /**
     * 返回顶级上下文对象
     *
     * @return QContext
     */
    function root()
    {
        return self::$_root;
    }

    /**
     * @addtogroup config 配置管理
     * @ingroup core
     *
     * QeePHP 提供一组接口，让框架和应用程序可以处理不同来源的配置信息。
     *
     * 不管配置信息的来源是什么，所有的配置信息都会被保存在一个内部的配置存储容器中。
     * 然后框架和应用程序可以利用 $context->getIni() 、 $context->setIni() 等方法读取或修改容器中的配置信息。
     *
     * @{
     */

    /**
     * 获取当前模块的配置内容
     *
     * $option 参数指定要获取的设置名。如果当前模板的设置中没有该选项，则尝试从应用程序的全局设置中读取。
     * 如果全局设置中还是找不到指定的选项，则返回由 $default 参数指定的值。
     *
     * @code
     * $option_value = $context->getIni('my_option');
     * @endcode
     *
     * 对于层次化的配置信息（类似 PHP 的嵌套数组），可以通过在 $option 中使用“/”符号来指定。
     *
     * 例如有一个名为 option_group 的设置项，其中包含三个子项目。现在要查询其中的 my_option 设置项的内容。
     *
     * @code
     * // +--- option_group
     * //    +-- my_option  = this is my_option
     * //    +-- my_option2 = this is my_option2
     * //    \-- my_option3 = this is my_option3
     *
     * // 查询 option_group 设置组里面的 my_option 项
     * $option_value = $context->getIni('option_group/my_option');
     *
     * // 将会显示 this is my_option
     * echo $option_value;
     *
     * @endcode
     *
     * 要读取更深层次的设置项，可以使用更多的“/”符号。但太多层次会导致读取速度变慢。
     *
     * 如果要获得当前模块所有设置项的内容，将 $option 参数指定为 '/' 即可：
     *
     * @code
     * // 获取所有设置项的内容
     * $all = $context->getIni('/');
     * @endcode
     *
     * @param string $option
     *   要获取设置项的名称
     * @param mixed $default
     *   当设置不存在时要返回的设置默认值
     * @param boolean $backtracking
     *   在当前模块中找不到指定设置时是否查询父级 QContext 对象
     *
     * @return mixed
     *   返回设置项的值
     */
    function getIni($option, $default = null, $backtracking = true)
    {
        if ($option == '/')
        {
            return $this->_config;
        }
        if (strpos($option, '/') === false)
        {
            if (array_key_exists($option, $this->_config))
            {
                return $this->_config[$option];
            }

            if (!$backtracking || is_null($this->_parent))
            {
                return $default;
            }

            return $this->_parent->getIni($option, $default);
        }
        $parts = explode('/', $option);
        $pos = & $this->_config;
        foreach ($parts as $part)
        {
            if (!isset($pos[$part]))
            {
                return $default;
            }
            $pos = & $pos[$part];
        }
        return $pos;
    }

    /**
     * 修改当前模块指定配置的内容
     *
     * 当 $option 参数是字符串时，$option 指定了要修改的设置项，$data 则是要为该设置项指定的新数据。
     *
     * @code
     * // 修改一个设置项
     * $context->setIni('option_group/my_option2', 'new value');
     * @endcode
     *
     * 如果 $option 是一个数组，则假定要修改多个设置项。那么 $option 则是一个由设置项名称和设置值组成的名值对，
     * 或者是一个嵌套数组。
     *
     * @code
     * // 修改多个设置项
     * $arr = array(
     *      'option_1' => 'value 1',
     *      'option_2' => 'value 2',
     *      'option_group/my_option3' => 'new value',
     * );
     * $context->setIni($arr);
     *
     * // 修改多个设置项，以及 option_group 中的多个设置项
     * $arr = array(
     *      'option_group' => array(
     *          'my_option'  => '1',
     *          'my_option2' => '2',
     *      ),
     *      'option_1' => 'value 1',
     *      'option_2' => 'value 2',
     * );
     * $context->setIni($arr);
     * @endcode
     *
     * 在修改包含子项目的设置项时，不管使用哪种方式，都不会清空设置项的所有内容，而只是替换要修改的内容。
     * 因此如果要清空某个设置项，应该使用 QContext::unsetIni() 方法。
     *
     * 注意：setIni() 方法只会影响当前模块的设置。
     *
     * @param string $option
     *   要修改的设置项名称，或包含多个设置项目的数组
     * @param mixed $data
     *   指定设置项的新值
     */
    function setIni($option, $data = null)
    {
        if (is_array($option))
        {
            foreach ($option as $key => $value)
            {
                $this->setIni($key, $value);
            }
            return;
        }

        if (! is_array($data))
        {
            if (strpos($option, '/') === false)
            {
                $this->_config[$option] = $data;
                return;
            }

            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos = & $this->_config;
            for ($i = 0; $i <= $max; $i ++)
            {
                $part = $parts[$i];
                if ($i < $max)
                {
                    if (! isset($pos[$part]))
                    {
                        $pos[$part] = array();
                    }
                    $pos = & $pos[$part];
                }
                else
                {
                    $pos[$part] = $data;
                }
            }
        }
        else
        {
            foreach ($data as $key => $value)
            {
                $this->setIni($option . '/' . $key, $value);
            }
        }
    }

    /**
     * 删除指定的配置
     *
     * $option 参数的用法同 QContext::getIni() 和 QContext::setIni()。
     *
     * 注意：unsetIni() 只影响当前模块的设置。
     *
     * @param mixed $option
     *   要删除的设置项名称
     */
    function unsetIni($option)
    {
        if (strpos($option, '/') === false)
        {
            unset($this->_config[$option]);
        }
        else
        {
            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos = & $this->_config;
            for ($i = 0; $i <= $max; $i ++)
            {
                $part = $parts[$i];
                if ($i < $max)
                {
                    if (! isset($pos[$part]))
                    {
                        $pos[$part] = array();
                    }
                    $pos =& $pos[$part];
                }
                else
                {
                    unset($pos[$part]);
                }
            }
        }
    }

    /* @} */

    /**
     * 魔法方法，访问请求参数
     *
     * @param string $key
     *
     * @return mixed
     */
    function __get($key)
    {
        if (isset($_GET[$key]))
        {
            return $_GET[$key];
        }
        elseif (isset($_POST[$key]))
        {
            return $_POST[$key];
        }
        elseif (isset($this->_params[$key]))
        {
            return $this->_params[$key];
        }
        return null;
    }

    /**
     * 魔法方法
     *
     * @param string $key
     * @param mixed $value
     */
    function __set($key, $value)
    {
        if (isset($_GET[$key]))
        {
            $_GET[$key] = $value;
        }
        elseif (isset($_POST[$key]))
        {
            $_POST[$key] = $value;
        }
        else
        {
            $this->_params[$key] = $value;
        }
    }

    /**
     * 魔法方法，确定是否包含请求参数
     *
     * @param string $key
     *
     * @return boolean
     */
    function __isset($key)
    {
        if (isset($_GET[$key]))
        {
            return true;
        }
        elseif (isset($_POST[$key]))
        {
            return true;
        }
        else
        {
            return isset($this->_params[$key]);
        }
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     *
     * @return boolean
     */
    function offsetExists($key)
    {
        return isset($this->{$key});
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     * @param mixed $value
     */
    function offsetSet($key, $value)
    {
        $this->{$key} = $value;
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     *
     * @return boolean
     */
    function offsetGet($key)
    {
        return $this->{$key};
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     */
    function offsetUnset($key)
    {
        unset($this->_params[$key]);
    }

    /**
     * 从 GET、POST，以及附加参数中查询指定的值
     *
     * @param string $key
     * @param mixed $default
     */
    function get($key, $default = null)
    {
        if (isset($_GET[$key]))
        {
            return $_GET[$key];
        }
        elseif (isset($_POST[$key]))
        {
            return $_POST[$key];
        }
        elseif (isset($this->_params[$key]))
        {
            return $this->_params[$key];
        }
        else
        {
            return $default;
        }
    }

    /**
     * 获得 GET 数据
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function getQuery($key = null, $default = null)
    {
        if (is_null($key))
        {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * 获得 POST 数据
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function getPost($key = null, $default = null)
    {
        if (is_null($key))
        {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }

    /**
     * 获得 Cookie 数据
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function getCookie($key = null, $default = null)
    {
        if (is_null($key))
        {
            return $_COOKIE;
        }
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * 获得 $_SERVER 数据
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function getServer($key = null, $default = null)
    {
        if (is_null($key))
        {
            return $_SERVER;
        }
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    /**
     * 获得 $_ENV 数据
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function getEnv($key = null, $default = null)
    {
        if (is_null($key))
        {
            return $_ENV;
        }
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

    /**
     * 设置附加的参数
     *
     * @param string $key
     * @param mixed $value
     */
    function setParam($key, $value)
    {
        $this->_params[$key] = $value;
    }

    /**
     * 获得附加的参数
     *
     * @param string $key
     *
     * @return mixed
     */
    function getParam($key)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : null;
    }

    /**
     * 取得请求使用的协议
     *
     * @retrun string
     */
    function getProtocol()
    {
        static $protocol;
        if (is_null($protocol))
        {
            list ($protocol) = explode('/', $_SERVER['SERVER_PROTOCOL']);
            $protocol = strtolower($protocol);
        }
        return $protocol;
    }

    /**
     * 设置 REQUEST_URI
     *
     * @param string $request_uri
     *
     * @return QContext
     */
    function setRequestUri($request_uri)
    {
        self::$_request_uri = $request_uri;
        self::$_base_uri = self::$_base_dir = null;
        return $this;
    }

    /**
     * 确定 REQUEST_URI
     *
     * @return string
     */
    function getRequestUri()
    {
        if (self::$_request_uri)
        {
            return self::$_request_uri;
        }

        if (isset($_SERVER['HTTP_X_REWRITE_URL']))
        { // check this first so IIS will catch
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
        }
        elseif (isset($_SERVER['REQUEST_URI']))
        {
            $uri = $_SERVER['REQUEST_URI'];
        }
        elseif (isset($_SERVER['ORIG_PATH_INFO']))
        { // IIS 5.0, PHP as CGI
            $uri = $_SERVER['ORIG_PATH_INFO'];
            if (! empty($_SERVER['QUERY_STRING']))
            {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
        else
        {
            $uri = '';
        }

        self::$_request_uri = $uri;
        return $uri;
    }

    /**
     * 设置不包含任何查询参数的 URI（但包含脚本名称）
     *
     * @param string $base_uri
     *
     * @return QContext
     */
    function setBaseUri($base_uri)
    {
        self::$_base_uri = $base_uri;
        self::$_base_dir = null;
        return $this;
    }

    /**
     * 返回不包含任何查询参数的 URI（但包含脚本名称）
     *
     * @return string
     */
    function getBaseUri()
    {
        if (self::$_base_uri)
        {
            return self::$_base_uri;
        }
        $filename = basename($_SERVER['SCRIPT_FILENAME']);

        if (basename($_SERVER['SCRIPT_NAME']) === $filename)
        {
            $url = $_SERVER['SCRIPT_NAME'];
        }
        elseif (basename($_SERVER['PHP_SELF']) === $filename)
        {
            $url = $_SERVER['PHP_SELF'];
        }
        elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename)
        {
            $url = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
        }
        else
        {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $_SERVER['PHP_SELF'];
            $segs = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $url = '';
            do
            {
                $seg = $segs[$index];
                $url = '/' . $seg . $url;
                ++ $index;
            } while (($last > $index) && (false !== ($pos = strpos($path, $url))) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $request_uri = $this->getRequestUri();

        if (0 === strpos($request_uri, $url))
        {
            // full $url matches
            self::$_base_uri = $url;
            return self::$_base_uri;
        }

        if (0 === strpos($request_uri, dirname($url)))
        {
            // directory portion of $url matches
            self::$_base_uri = rtrim(dirname($url), '/') . '/';
            return self::$_base_uri;
        }

        if (! strpos($request_uri, basename($url)))
        {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if ((strlen($request_uri) >= strlen($url)) && ((false !== ($pos = strpos($request_uri, $url))) && ($pos !== 0)))
        {
            $url = substr($request_uri, 0, $pos + strlen($url));
        }

        self::$_base_uri = rtrim($url, '/') . '/';
        return self::$_base_uri;
    }

    /**
     * 设置请求 URL 中的基础路径（不含脚本名称）
     *
     * @param string $base_dir
     *
     * @return QContext
     */
    function setBaseDir($base_dir)
    {
        self::$_base_dir = $base_dir;
        return $this;
    }

    /**
     * 返回请求 URL 中的基础路径（不包含脚本名称）
     *
     * @return string
     */
    function getBaseDir()
    {
        if (self::$_base_dir)
        {
            return self::$_base_dir;
        }

        $base_uri = $this->getBaseUri();

        if (substr($base_uri, - 1, 1) == '/')
        {
            $base_dir = $base_uri;
        }
        else
        {
            $base_dir = rtrim(dirname($base_uri) . '/', '/\\');
        }

        self::$_base_dir = $base_dir;
        return $base_dir;
    }

    /**
     * 返回服务器响应请求使用的端口
     *
     * @return string
     */
    function getServerPort()
    {
        static $server_port = null;

        if ($server_port)
        {
            return $server_port;
        }

        if (isset($_SERVER['SERVER_PORT']))
        {
            $server_port = intval($_SERVER['SERVER_PORT']);
        }
        else
        {
            $server_port = 80;
        }

        if (isset($_SERVER['HTTP_HOST']))
        {
            $arr = explode(':', $_SERVER['HTTP_HOST']);
            $count = count($arr);
            if ($count > 1)
            {
                $port = intval($arr[$count - 1]);
                if ($port != $server_port)
                {
                    $server_port = $port;
                }
            }
        }

        return $server_port;
    }

    /**
     * 获得响应请求的脚本文件名
     *
     * @return string
     */
    function getScriptName()
    {
        return basename($_SERVER['SCRIPT_FILENAME']);
    }

    /**
     * 返回 PATHINFO 信息
     *
     * @return string
     */
    function getPathinfo()
    {
        if (!empty($_SERVER['PATH_INFO']))
        {
            return $_SERVER['PATH_INFO'];
        }

        $base_url = $this->getBaseUri();

        if (null === ($request_uri = $this->getRequestUri()))
        {
            return '';
        }

        // Remove the query string from REQUEST_URI
        if (($pos = strpos($request_uri, '?')))
        {
            $request_uri = substr($request_uri, 0, $pos);
        }

        if ((null !== $base_url) && (false === ($pathinfo = substr($request_uri, strlen($base_url)))))
        {
            // If substr() returns false then PATH_INFO is set to an empty string
            $pathinfo = '';
        }
        elseif (null === $base_url)
        {
            $pathinfo = $request_uri;
        }

        return $pathinfo;
    }

    /**
     * 返回请求方法
     *
     * @return string
     */
    function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 是否是 GET 请求
     *
     * @return boolean
     */
    function isGET()
    {
        return $this->getMethod() == 'GET';
    }

    /**
     * 是否是 POST 请求
     *
     * @return boolean
     */
    function isPOST()
    {
        return $this->getMethod() == 'POST';
    }

    /**
     * 是否是 PUT 请求
     *
     * @return boolean
     */
    function isPUT()
    {
        return $this->getMethod() == 'PUT';
    }

    /**
     * 是否是 DELETE 请求
     *
     * @return boolean
     */
    function isDELETE()
    {
        return $this->getMethod() == 'DELETE';
    }

    /**
     * 是否是 HEAD 请求
     *
     * @return boolean
     */
    function isHEAD()
    {
        return $this->getMethod() == 'HEAD';
    }

    /**
     * 是否是 OPTIONS 请求
     *
     * @return boolean
     */
    function isOPTIONS()
    {
        return $this->getMethod() == 'OPTIONS';
    }

    /**
     * 返回请求的原始内容
     *
     * @return string
     */
    function getRawBody()
    {
        $body = file_get_contents('php://input');
        return (strlen(trim($body)) > 0) ? $body : false;
    }

    /**
     * 返回 HTTP 请求头中的指定信息
     *
     * @param string $header
     *
     * @return string
     */
    function getHeader($header)
    {
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (! empty($_SERVER[$temp]))
        {
            return $_SERVER[$temp];
        }

        if (function_exists('apache_request_headers'))
        {
            $headers = apache_request_headers();
            if (! empty($headers[$header]))
            {
                return $headers[$header];
            }
        }

        return false;
    }

    /**
     * 返回连接到当前页面的前一页地址
     *
     * @return string
     */
    function getReferer()
    {
        return $this->getHeader('REFERER');
    }

    /**
     * 判断 HTTP 请求是否是通过 XMLHttp 发起的
     *
     * @return boolean
     */
    function isAJAX()
    {
        return strtolower($this->getHeader('X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    /**
     * 判断 HTTP 请求是否是通过 Flash 发起的
     *
     * @return boolean
     */
    function isFlash()
    {
        return strtolower($this->getHeader('USER_AGENT')) == 'shockwave flash';
    }

    /**
     * 构造 url
     *
     * url() 方法支持多种调用模式，分别是：
     *
     * <ul>
     *   <li>url([控制器名], [动作名], [附加参数数组], [名字空间], [模块名], [路由名])</li>
     *   <li>url(UDI, [附件参数数组], [路由名])</li>
     *   <li>url(参数数组, [路由名])</li>
     * </ul>
     *
     * UDI 是统一目的地标识符（Uniform Destination Identifier）的缩写。
     *
     * UDI 由控制器、动作、名字空间以及模块名组成，采用如下的格式：
     *
     * namespace::controller/action@module
     *
     * 如果没有提供 namespace 和 module，则 controller 是必须提供的。
     *
     * 可以有下列写法：
     *
     * 'controller'
     * 'controller/action'
     * '/action'
     * 'controller@module'
     * 'controller/action@module'
     * 'namespace::controller'
     * 'namespace::controller/action'
     * 'namespace::controller@module'
     * 'namespace::controller/action@module'
     * '@module'
     * 'namespace::@module'
     *
     * @param string $controller_name
     * @param string $action_name
     * @param array $params
     * @param string $namespace
     * @param string $module
     * @param string $route_name
     *
     * @return string
     */
    function url($controller_name = null, $action_name = null, $params = null,
                 $namespace = null, $module = null, $route_name = null)
    {
        static $base_uri;

        if (is_null($base_uri))
        {
            $base_uri = $this->getProtocol() . '://' . rtrim($_SERVER['SERVER_NAME'], '/');
            $server_port = $this->getServerPort();
            if ($server_port != 80)
            {
                $base_uri .= ":{$server_port}";
            }
            $base_uri .= '/' . ltrim($this->getBaseDir(), '/');
        }

        $controller_accessor = $this->getIni('dispatcher_controller_accessor');
        $action_accessor     = $this->getIni('dispatcher_action_accessor');
        $namespace_accessor  = $this->getIni('dispatcher_namespace_accessor');
        $module_accessor     = $this->getIni('dispatcher_module_accessor');

        if (is_array($controller_name))
        {
            // 模式3: url(参数数组, [路由名])
            $url_args = $controller_name;
            $route_name = $action_name;

            if (isset($url_args['namespace']) || isset($url_args[$namespace_accessor]))
            {
                $namespace = isset($url_args['namespace'])
                             ? $url_args['namespace']
                             : $url_args[$namespace_accessor];
                unset($url_args['namespace']);
                unset($url_args[$namespace_accessor]);
            }
            else
            {
                $namespace = $this->namespace;
            }

            if (isset($url_args['module']) || isset($url_args[$module_accessor]))
            {
                $module = isset($url_args['module'])
                          ? $url_args['module']
                          : $url_args[$module_accessor];
                unset($url_args['module']);
                unset($url_args[$module_accessor]);
            }
            else
            {
                $module = $this->module;
            }

            if (isset($url_args['controller']) || isset($url_args[$controller_accessor]))
            {
                $controller_name = isset($url_args['controller']) ? $url_args['controller'] : $url_args[$controller_accessor];
                unset($url_args['controller']);
                unset($url_args[$controller_accessor]);
            }
            else
            {
                $controller_name = $this->controller_name;
            }

            if (isset($url_args['action']) || isset($url_args[$action_accessor]))
            {
                $action_name = isset($url_args['action']) ? $url_args['action'] : $url_args[$action_accessor];
                unset($url_args['action']);
                unset($url_args[$action_accessor]);
            }
            else
            {
                $action_name = null;
            }

            $params = $url_args;
        }
        else
        {
            $destinfo = $this->destinfo($controller_name);
            if (is_array($action_name))
            {
                // 模式2: url(UDI, [附件参数数组], [路由名])
                $module          = $destinfo[self::DESTINFO_MODULE];
                $namespace       = $destinfo[self::DESTINFO_NAMESPACE];
                $controller_name = $destinfo[self::DESTINFO_CONTROLLER];
                $route_name      = $params;
                $params          = $action_name;
                $action_name     = $destinfo[self::DESTINFO_ACTION];
            }
            else
            {
                // 模式1: url([控制器名], [动作名], [附加参数数组], [名字空间], [模块名], [路由名])
                if (is_null($module))
                {
                    $module = $destinfo[self::DESTINFO_MODULE];
                }
                if (is_null($namespace))
                {
                    $namespace = $destinfo[self::DESTINFO_NAMESPACE];
                }
                $controller_name = $destinfo[self::DESTINFO_CONTROLLER];
                if (is_null($action_name))
                {
                    $action_name = $destinfo[self::DESTINFO_ACTION];
                }
            }
        }

        // 确定控制器和动作的名字
        $controller_name = empty($controller_name)
                           ? $this->getIni('dispatcher_default_controller')
                           : $controller_name;
        $action_name = empty($action_name)
                       ? $this->getIni('dispatcher_default_action')
                       : $action_name;
        $controller_name = strtolower($controller_name);
        $action_name = strtolower($action_name);

        $url_args = array();

        if (!is_null($this->_router))
        {
            $module_accessor     = 'module';
            $namespace_accessor  = 'namespace';
            $controller_accessor = 'controller';
            $action_accessor     = 'action';
        }

        if ($module)
        {
            $url_args[$module_accessor] = $module;
        }

        if ($namespace)
        {
            $url_args[$namespace_accessor] = $namespace;
        }

        $url_args[$controller_accessor] = $controller_name;
        $url_args[$action_accessor] = $action_name;

        if (is_array($params) && !empty($params))
        {
            $url_args = array_merge($url_args, $params);
        }

        if (!is_null($this->_router))
        {
            $url = rtrim($base_uri, '/');
            if (self::$_url_mode == self::URL_MODE_PATHINFO)
            {
                $url .= '/' . $this->getScriptName();
            }
            $url .= $this->_router->url($url_args, $route_name);
        }
        else
        {
            $url = rtrim($base_uri, '/') . '/' . $this->getScriptName() . '?' . http_build_query($url_args, '', '&');
        }

        return $url;
    }

    /**
     * 根据提供的参数构造一个 UDI 字符串
     *
     * @param array $destinfo
     *
     * @return string
     */
    function makeDestinfo(array $destinfo)
    {
        $udi = '';

        if (!empty($destinfo[self::DESTINFO_NAMESPACE]))
        {
            $udi = $destinfo[self::DESTINFO_NAMESPACE] . '::';
        }

        if (!empty($destinfo[self::DESTINFO_CONTROLLER]))
        {
            $udi .= $destinfo[self::DESTINFO_CONTROLLER];
        }

        if (!empty($destinfo[self::DESTINFO_ACTION]))
        {
            $udi .= '/' . $destinfo[self::DESTINFO_ACTION];
        }

        if (!empty($destinfo[self::DESTINFO_MODULE]))
        {
            $udi .= '@' . $destinfo[self::DESTINFO_MODULE];
        }

        return $udi;
    }

    /**
     * 分析目的地参数，提取控制器、动作、名字空间以及模块名称
     *
     * @param string $destination
     * @param int $part_name
     *
     * @return array|string
     */
    function destinfo($destination, $part_name = 0)
    {
        /**
         * $destination 由三部分组成：
         *
         * namespace::controller/action@module
         *
         * 如果没有提供 namespace 和 module，则 controller 是必须提供的。
         *
         * 可以有下列写法：
         *
         * '/action'
         * 'controller'
         * 'controller/action'
         * 'controller@module'
         * 'controller/action@module'
         * 'namespace::controller'
         * 'namespace::controller/action'
         * 'namespace::controller@module'
         * 'namespace::controller/action@module'
         * '@module'
         * 'namespace::@module'
         */

        if (strpos($destination, '::') !== false)
        {
            $arr = explode('::', $destination);
            $namespace = array_shift($arr);
            $destination = array_shift($arr);
        }

        if (strpos($destination, '@') !== false)
        {
            $arr = explode('@', $destination);
            $module = array_pop($arr);
            $destination = array_pop($arr);
            if (!isset($namespace))
            {
                $namespace = '';
            }
        }

        $arr = explode('/', $destination);
        $controller = array_shift($arr);
        if (empty($controller))
        {
            $controller = $this->controller_name;
        }
        $action = array_shift($arr);

        $return = array();
        if (isset($module))
        {
            $return[self::DESTINFO_MODULE] = $module;
        }
        else
        {
            $return[self::DESTINFO_MODULE] = $this->module_name;
        }

        if (isset($namespace))
        {
            $return[self::DESTINFO_NAMESPACE] = $namespace;
        }
        else
        {
            $return[self::DESTINFO_NAMESPACE] = $this->namespace;
        }

        $return[self::DESTINFO_CONTROLLER] = $controller;
        $return[self::DESTINFO_ACTION] = $action;

        $return = $this->setRequestUDI($return);
        if ($part_name != self::DESTINFO_ALL)
        {
            return $return[$part_name];
        }
        else
        {
            return $return;
        }
    }

    /**
     * 返回当前请求对应的 UDI
     *
     * @return string
     */
    function getRequestUDI()
    {
        $destinfo = array
        (
            self::DESTINFO_CONTROLLER => $this->controller_name,
            self::DESTINFO_ACTION     => $this->action_name,
            self::DESTINFO_MODULE     => $this->module_name,
            self::DESTINFO_NAMESPACE  => $this->namespace,
        );
        return $this->makeDestinfo($destinfo);
    }

    /**
     * 检查指定的控制器名、动作名、名字空间、模块名是否合法
     *
     * @param array $destinfo
     *
     * @return array
     */
    function setRequestUDI(array $destinfo = null)
    {
        if (is_null($destinfo))
        {
            $no_return = true;
            $destinfo = array
            (
                self::DESTINFO_CONTROLLER => $this->controller_name,
                self::DESTINFO_ACTION     => $this->action_name,
                self::DESTINFO_MODULE     => $this->module_name,
                self::DESTINFO_NAMESPACE  => $this->namespace,
            );
        }
        else
        {
            $no_return = false;
        }

        if (empty($destinfo[self::DESTINFO_CONTROLLER]))
        {
            $destinfo[self::DESTINFO_CONTROLLER] = $this->getIni('dispatcher_default_controller');
        }

        if (empty($destinfo[self::DESTINFO_ACTION]))
        {
            $destinfo[self::DESTINFO_ACTION] = $this->getIni('dispatcher_default_action');
        }

        foreach ($destinfo as $key => $value)
        {
            $destinfo[$key] = strtolower(preg_replace('/[^a-z0-9]+/i', '', $value));
        }

        if ($no_return)
        {
            $this->controller_name = $destinfo[self::DESTINFO_CONTROLLER];
            $this->action_name     = $destinfo[self::DESTINFO_ACTION];
            $this->module_name     = $destinfo[self::DESTINFO_MODULE];
            $this->namespace       = $destinfo[self::DESTINFO_NAMESPACE];
            return null;
        }
        else
        {
            return $destinfo;
        }
    }

    /**
     * 初始化运行环境
     */
    protected function _prepare()
    {
        $keys = array_keys($_REQUEST);
        if (! empty($keys))
        {
            $keys = array_combine($keys, $keys);
            $keys = array_change_key_case($keys);
        }

        $k = strtolower($this->getIni('dispatcher_controller_accessor'));
        if (isset($keys[$k]))
        {
            $this->controller_name = $_REQUEST[$keys[$k]];
        }

        $k = strtolower($this->getIni('dispatcher_action_accessor'));
        if (isset($keys[$k]))
        {
            $this->action_name = $_REQUEST[$keys[$k]];
        }

        $k = strtolower($this->getIni('dispatcher_module_accessor'));
        if (isset($keys[$k]))
        {
            $this->module_name = $_REQUEST[$keys[$k]];
        }

        $k = strtolower($this->getIni('dispatcher_namespace_accessor'));
        if (isset($keys[$k]))
        {
            $this->namespace = $_REQUEST[$keys[$k]];
        }

        $this->setRequestUDI();
    }

    /**
     * 根据 php.ini 中的 magic quotes gpc 设置去除超全局变量中自动添加的转义符
     */
    private function _initOneTime()
    {
        if (self::$_root) { return; }
        self::$_root = $this;

        // 禁止 magic quotes
        set_magic_quotes_runtime(0);

        // 处理被 magic quotes 自动转义过的数据
        if (get_magic_quotes_gpc())
        {
            $in = array(& $_GET, & $_POST, & $_COOKIE, & $_REQUEST);
            while (list ($k, $v) = each($in))
            {
                foreach ($v as $key => $val)
                {
                    if (! is_array($val))
                    {
                        $in[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $in[] = & $in[$k][$key];
                }
            }
            unset($in);
        }

        $url_mode = strtolower($this->getIni('dispatcher_url_mode'));
        if ($url_mode == self::URL_MODE_PATHINFO || $url_mode == self::URL_MODE_REWRITE)
        {
            $this->_router = new QRouter($this);
            $result = $this->_router->match($this->getPathinfo());
            if ($result)
            {
                foreach ($result as $var => $value)
                {
                    if (empty($_GET[$var]))
                    {
                        $_GET[$var] = $_REQUEST[$var] = $value;
                    }
                }
            }
            self::$_url_mode = $url_mode;
        }
        else
        {
            self::$_url_mode = self::URL_MODE_STANDARD;
            $this->_router = null;
        }

    }
}

/**
 * @}
 */
