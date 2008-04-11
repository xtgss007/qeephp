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
 * 定义 QRequest 类
 *
 * 部分方法修改自 Zend Framework.
 *
 * @package core
 * @version $Id$
 */

/**
 * QRequest 类封装了一个请求的数据及状态信息
 *
 * @package core
 */
class QRequest
{
    /**
     * 附加的请求参数
     *
     * @var array
     */
    public $params = array();

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
     * 构造函数
     */
    function __construct()
    {
        if (get_magic_quotes_gpc()) {
            $in = array(& $_GET, & $_POST, & $_COOKIE, & $_REQUEST);
            while (list($k,$v) = each($in)) {
                foreach ($v as $key => $val) {
                    if (!is_array($val)) {
                        $in[$k][$key] = stripslashes($val);
                        continue;
                    }
                    $in[] =& $in[$k][$key];
                }
            }
            unset($in);
        }

        $keys = array_keys($_REQUEST);
        if (!empty($keys)) {
            $keys = array_combine($keys, $keys);
            $keys = array_change_key_case($keys);
        }

        $k = strtolower(Q::getIni('dispatcher_controller_accessor'));
        if (isset($keys[$k])) {
            $this->controller_name = $_REQUEST[$keys[$k]];
        } else {
            $this->controller_name = Q::getIni('dispatcher_default_controller');
        }

        $k = strtolower(Q::getIni('dispatcher_action_accessor'));
        if (isset($keys[$k])) {
            $this->action_name = $_REQUEST[$keys[$k]];
        } else {
            $this->action_name = Q::getIni('dispatcher_default_action');
        }

        $k = strtolower(Q::getIni('dispatcher_module_accessor'));
        if (isset($keys[$k])) {
            $this->module_name = $_REQUEST[$keys[$k]];
        } else {
            $this->module_name = Q::getIni('dispatcher_default_module');
        }

        $k = strtolower(Q::getIni('dispatcher_namespace_accessor'));
        if (isset($keys[$k])) {
            $this->namespace = $_REQUEST[$keys[$k]];
        } else {
            $this->namespace = Q::getIni('dispatcher_default_namespace');
        }

        $this->controller_name = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $this->controller_name));
        $this->action_name = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $this->action_name));
        $this->module_name = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $this->module_name));
        $this->namespace = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $this->namespace));
    }

    /**
     * 魔法方法，访问请求参数
     *
     * @param string $key
     *
     * @return mixed
     */
    function __get($key)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } elseif (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * 魔法方法，禁止设置请求参数
     *
     * @param string $key
     * @param mixed $value
     */
    function __set($key, $value)
    {
        // LC_MSG: Setting values in superglobals not allowed.
        throw new QException(__('Setting values in superglobals not allowed.'));
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
        if (isset($_GET[$key])) {
            return true;
        } elseif (isset($_POST[$key])) {
            return true;
        } else {
            return isset($this->params[$key]);
        }
    }

    /**
     * 从 GET、POST，以及附加参数中查询指定的值
     *
     * @param string $key
     * @param mixed $default
     */
    function get($key, $default = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        } elseif (isset($_POST[$key])) {
            return $_POST[$key];
        } elseif (isset($this->params[$key])) {
            return $this->params[$key];
        } else {
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
        if (is_null($key)) { return $_GET; }
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
        if (is_null($key)) { return $_POST; }
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
        if (is_null($key)) { return $_COOKIE; }
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
        if (is_null($key)) { return $_SERVER; }
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
        if (is_null($key)) { return $_ENV; }
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
        $this->params[$key] = $value;
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
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    /**
     * 确定 REQUEST_URI
     *
     * @return string
     */
    function getRequestUri()
    {
        static $uri = null;

        if ($uri) { return $uri; }
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch
            $uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $uri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $uri = '';
        }

        return $uri;
    }

    /**
     * 返回不包含任何查询参数的 URI（但包含脚本名称）
     *
     * @return string
     */
    function getBaseUri()
    {
        static $baseuri = null;

        if ($baseuri) { return $baseuri; }
        $filename = basename($_SERVER['SCRIPT_FILENAME']);

        if (basename($_SERVER['SCRIPT_NAME']) === $filename) {
            $url = $_SERVER['SCRIPT_NAME'];
        } elseif (basename($_SERVER['PHP_SELF']) === $filename) {
            $url = $_SERVER['PHP_SELF'];
        } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
            $url = $_SERVER['ORIG_SCRIPT_NAME']; // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path    = $_SERVER['PHP_SELF'];
            $segs    = explode('/', trim($_SERVER['SCRIPT_FILENAME'], '/'));
            $segs    = array_reverse($segs);
            $index   = 0;
            $last    = count($segs);
            $url = '';
            do {
                $seg     = $segs[$index];
                $url = '/' . $seg . $url;
                ++$index;
            } while (($last > $index) && (false !== ($pos = strpos($path, $url))) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $request_uri = $this->getRequestUri();

        if (0 === strpos($request_uri, $url)) {
            // full $url matches
            $baseuri = $url;
            return $baseuri;
        }

        if (0 === strpos($request_uri, dirname($url))) {
            // directory portion of $url matches
            $baseuri = rtrim(dirname($url), '/') . '/';
            return $baseuri;
        }

        if (!strpos($request_uri, basename($url))) {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if ((strlen($request_uri) >= strlen($url))
            && ((false !== ($pos = strpos($request_uri, $url))) && ($pos !== 0)))
        {
            $url = substr($request_uri, 0, $pos + strlen($url));
        }

        $baseuri = rtrim($url, '/') . '/';
        return $baseuri;
    }

    /**
     * 返回请求 URL 中的基础路径（不包含脚本名称）
     *
     * @return string
     */
    function getBaseDir()
    {
        static $public_root = null;

        if ($public_root) { return $public_root; }
        $baseuri = $this->getBaseUri();
        if (substr($baseuri, -1, 1) == '/') {
            $public_root = $baseuri;
        } else {
            $public_root = dirname($baseuri) . '/';
        }
        return $public_root;
    }

    /**
     * 返回 PATHINFO 信息
     *
     * @return string
     */
    function getPathinfo()
    {
        $base_url = $this->getBaseUri();

        if (null === ($request_uri = $this->getRequestUri())) {
            return '';
        }

        // Remove the query string from REQUEST_URI
        if (($pos = strpos($request_uri, '?'))) {
            $request_uri = substr($request_uri, 0, $pos);
        }

        if ((null !== $base_url)
            && (false === ($pathinfo = substr($request_uri, strlen($base_url)))))
        {
            // If substr() returns false then PATH_INFO is set to an empty string
            $pathinfo = '';
        } elseif (null === $base_url) {
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
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers[$header])) {
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
    function isFlashRequest()
    {
        return strtolower($this->getHeader('USER_AGENT')) == 'shockwave flash';
    }

    /**
     * 获得当前请求包含的控制器名称
     *
     * @return string
     */
    function getControllerName()
    {
        return $this->controller_name;
    }

    /**
     * 获得当前请求包含的动作名
     *
     * @return string
     */
    function getActionName()
    {
        return $this->action_name;
    }

    /**
     * 获得当前请求包含的模块名
     *
     * @return string
     */
    function getModuleName()
    {
        return $this->module_name;
    }

    /**
     * 获得当前请求包含的命名空间
     *
     * @return string
     */
    function getNamespace()
    {
        return $this->namespace;
    }
}
