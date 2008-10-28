<?php
// $Id$

/**
 * @file
 * 定义 QRouter 类
 *
 * @ingroup mvc
 *
 * @{
 */

/**
 * QRouter 类实现自定义路由的解析
 */
class QRouter
{
    /**
     * 匹配模式各部分的类型
     */
    const PARTTYPE_VAR      = 1;
    const PARTTYPE_STATIC   = 2;
    const PARTTYPE_WILDCARD = 3;

    /**
     * 运行时上下文对象
     *
     * @var QContext
     */
    public $context;

    /**
     * 当前匹配成功的路由名字
     *
     * @var string
     */
    public $matched_route_name;

    /**
     * 最后一次反向匹配成功的路由名称
     *
     * @var string
     */
    public $reserve_matched_route_name;

    /**
     * 所有解析过的路由
     *
     * @var array
     */
    protected $_routes = array();

    /**
     * 路由规则
     *
     * @var array
     */
    protected $_rules;

    /**
     * 用于识别变量的前缀符号
     *
     * @var string
     */
    protected $_var_prefix = ':';

    /**
     * 用于分割匹配模式多个部分的定界符
     *
     * @var string
     */
    protected $_part_delimiter = '/';

    /**
     * 匹配变量的正则表达式
     *
     * @var string
     */
    protected $_default_var_regex = '.+';

    /**
     * 保留变量的默认比对规则
     *
     * @var string
     */
    protected $_default_reserved_var_regex = '([a-z][a-z0-9_]*)*';

    /**
     * 用于调试的 QLog 对象
     *
     * @var QLog
     */
    //protected $_log;

    /**
     * 保留的变量
     *
     * @var array
     */
    protected $_reserved_vars = array
    (
        'module' => true,
        'namespace' => true,
        'controller' => true,
        'action' => true,
    );

    /**
     * 构造函数
     *
     * @param QContext $context
     */
    function __construct(QContext $context)
    {
        $this->context = $context;
        $this->_rules = (array)$context->getIni('routes');
        //$this->_log = new QLog(array('log_writer_filename' => 'router.log'));
    }

    /**
     * 添加一条路由规则
     *
     * @param string $route_name
     * @param array $rule
     *
     * @return QRouter
     */
    function add($route_name, array $rule)
    {
        $this->_rules[$route_name] = $rule;
        unset($this->_routes[$route_name]);
        return $this;
    }

    /**
     * 移除指定的路由规则
     *
     * @param string $route_name
     *
     * @return QRouter
     */
    function remove($route_name)
    {
        unset($this->_rules[$route_name]);
        unset($this->_routes[$route_name]);
        return $this;
    }

    /**
     * 取得指定名称的路由规则
     *
     * @param string $route_name
     *
     * @retrun array
     */
    function get($route_name)
    {
        $this->_prepareRoute($route_name);
        return $this->_routes[$route_name];
    }

    /**
     * 匹配路由规则，成功返回匹配结果，失败返回 false
     *
     * @param string $path
     *
     * @return array|boolean
     */
    function match($path)
    {
        // 分割 URL
        $path = explode($this->_part_delimiter, trim($path, $this->_part_delimiter));

        foreach (array_keys($this->_rules) as $route_name)
        {
            $this->_prepareRoute($route_name);
            $result = $this->_match($this->_routes[$route_name], $path);
            if ($result)
            {
                $this->matched_route_name = $route_name;
                return $result;
            }
        }

        return false;
    }

    /**
     * 反相匹配路由规则
     *
     * @param array $url_args
     * @param string $route_name
     *
     * @return string
     */
    function url(array $url_args, $route_name = null)
    {
        // 过滤掉所有具有空值的保留变量
        foreach ($this->_reserved_vars as $varname => $tmp)
        {
            if (empty($url_args[$varname]))
            {
                unset($url_args[$varname]);
            }
        }

        if ($route_name && isset($this->_rules[$route_name]))
        {
            $this->_prepareRoute($route_name);
            $url = $this->_url($this->_routes[$route_name], $url_args);
            if ($url !== false)
            {
                $this->reserve_matched_route_name = $route_name;
                return $url;
            }
        }
        else
        {
            //$this->_log->append('<!------ 开始反向匹配');
            //$this->_log->append(http_build_query($url_args));
            foreach (array_keys($this->_rules) as $route_name)
            {
                $this->_prepareRoute($route_name);
                $url = $this->_url($this->_routes[$route_name], $url_args);
                if ($url !== false)
                {
                    $this->reserve_matched_route_name = $route_name;
                    //$this->_log->append("匹配规则 [{$route_name}] 成功: {$url}");
                    //$this->_log->append('反向匹配结束 ------>');
                    return $url;
                }
            }
        }


        /**
         * 如果没有找到匹配的规则，则使用内置的规则
         */
        $this->reserve_matched_route_name = null;
        $path = array();
        if (empty($url_args))
        {
            return '';
        }

        $url = '?' . http_build_query($url_args, '', '&');
        //$this->_log->append('使用内置规则: ' . $url);
        //$this->_log->append('反向匹配结束（失败） ------->');

        return $url;
    }

    /**
     * 匹配路径，成功返回匹配结果，失败返回 false
     *
     * @param array $route
     * @param array $path
     *
     * @return array|boolean
     */
    protected function _match(array $route, array $path)
    {
        // 保存匹配成功后获得的变量名值对
        $values = array();

        // 遍历匹配模式的每一个部分，确认 URL 中包含需要的内容
        foreach ($route['pattern_parts'] as $pos => $part_type)
        {
            switch ($part_type)
            {
            case self::PARTTYPE_VAR:
                $varname = $route['vars'][$pos];
                if (isset($path[$pos]))
                {
                    // 用 URL 的相应部分和变量比对规则进行比对
                    $regex = "#^{$route['config'][$varname]}\$#iu";
                    if (!preg_match($regex, $path[$pos]))
                    {
                        return false;
                    }
                    $value = $path[$pos];
                }
                elseif (isset($route['defaults'][$varname]))
                {
                    // 如果该变量有默认值，则使用默认值
                    $value = $route['defaults'][$varname];
                }
                else
                {
                    // 如果 URL 没有对应部分，并且变量没有默认值，则视为比对失败
                    return false;
                }
                $values[$varname] = $value;
                break;

            case self::PARTTYPE_STATIC:
                if (empty($path[$pos]))
                {
                    if ($route['static_optional'][$pos])
                    {
                        // 对于可选的静态部分，允许不提供
                        continue;
                    }
                    return false;
                }

                $value = $path[$pos];
                if ($route['static_parts'][$pos] != $value
                    || strlen($route['static_parts'][$pos]) != strlen($value))
                {
                    // 对于静态部分，如果 URL 没有提供该部分，或者与预期的不符，则比对失败
                    return false;
                }
                break;

            case self::PARTTYPE_WILDCARD:
                /**
                 * 对于通配符，获得剩余的所有部分，并停止匹配
                 *
                 * 剩余的所有参数都按照 /name/value 的形式解析，并存入 $values。
                 */
                while (isset($path[$pos]))
                {
                    $varname = urldecode($path[$pos]);
                    // 如果路由中明确定义了一个变量，则不应该让通配符匹配的变量覆盖已定义变量的值
                    if (strlen($varname) && !isset($route['varnames'][$varname]))
                    {
                        if (isset($path[$pos + 1]))
                        {
                            $values[$varname] = urldecode($path[$pos + 1]);
                        }
                        else
                        {
                            $values[$varname] = '';
                        }
                    }

                    $pos += 2;
                }
                break;
            }
        }

        // 如果 URL 还包含更多的部分，则比对失败
        if (isset($path[$pos + 1]))
        {
            return false;
        }

        foreach ($route['defaults'] as $varname => $value)
        {
            if (!isset($values[$varname]))
            {
                $values[$varname] = $value;
            }
        }

        return $values;
    }

    /**
     * 根据参数创建匹配该路由的 URL，成功返回 URL 字符串，失败返回 FALSE
     *
     * @param array $route
     * @param array $url_args
     *
     * @return string|boolean
     */
    protected function _url(array $route, array $url_args)
    {
        //$this->_log->append(".. 开始比对路由 [{$route['name']}]");

        // 比对所有保留变量
        foreach ($this->_reserved_vars as $varname => $tmp)
        {
            if (isset($url_args[$varname])
                && !isset($route['config'][$varname])
                && !isset($route['defaults'][$varname]))
            {
                // $url_args 中包含该路由没有要求的保留变量
                return false;
            }
        }

        // 比对所有必须的变量
        foreach ($route['varnames'] as $varname => $tmp)
        {
            /**
             * 1. 对于指定了验证规则的变量，首先检查 $url_args 中是否包含该变量
             *   1.1 如果不包含，则检查路由中是否指定了默认值
             *     1.1.1 如果没有指定默认值，则比对失败
             *     1.1.1 如果提供了默认值，则将变量及默认值添加到 $url_args 中
             *   1.2 如果包含，则用变量的验证规则进行比对
             * 2. 对于没有指定验证规则的变量，首先检查 $url_args 中是否包含该变量
             *   2.1 如果不包含，则比对失败
             *   2.2 如果包含但不相等，则比对失败
             */

            if (isset($route['config'][$varname]))
            {
                if (!isset($url_args[$varname]))
                {
                    if (!isset($route['defaults'][$varname]))
                    {
                        //$this->_log->append(".... 缺少必须的变量 [{$varname}]");
                        return false;
                    }

                    $url_args[$varname] = $route['defaults'][$varname];
                    continue;
                }

                $regex = "#^{$route['config'][$varname]}\$#iu";
                if (!preg_match($regex, $url_args[$varname]))
                {
                    //$this->_log->append(".... 变量 [{$varname}] 的值 \"{$url_args[$varname]}\" 不符合比对规则 \"{$regex}\"");
                    return false;
                }
            }
            else
            {
                if (!isset($url_args[$varname]))
                {
                    //$this->_log->append(".... 具有默认值的变量 {$varname} 没有提供");
                    return false;
                }

                $value = $route['defaults'][$varname];
                if ($url_args[$varname] != $value
                    || strlen($url_args[$varname]) != strlen($value))
                {
                    //$this->_log->append(".... 具有默认值的变量 {$varname} 值不符");
                    return false;
                }
            }
        }

        // 构造 URL
        $path = array();
        $query_args = array();
        $use_wildcard = false;
        foreach ($route['pattern_parts'] as $pos => $part_type)
        {
            switch ($part_type)
            {
            case self::PARTTYPE_VAR:
                $varname = $route['vars'][$pos];
                $path[$pos] = $url_args[$varname];
                unset($url_args[$varname]);
                break;

            case self::PARTTYPE_STATIC:
                $path[$pos] = $route['static_parts'][$pos];
                break;

            case self::PARTTYPE_WILDCARD:
                // 处理通配符时，所有变量都国过滤掉
                foreach ($url_args as $varname => $value)
                {
                    if (isset($route['varnames'][$varname]))
                    {
                        unset($url_args[$varname]);
                        continue;
                    }

                    if ($route['static_parts'][$pos] == '*')
                    {
                        $use_wildcard = true;
                        $path[] = $varname;
                        $path[] = $value;
                        unset($url_args[$varname]);
                    }
                    else
                    {
                        $query_args[$varname] = $value;
                        unset($url_args[$varname]);
                    }
                }
                break;
            }
        }

        // 过滤掉所有的保留变量
        foreach ($this->_reserved_vars as $varname => $tmp)
        {
            unset($url_args[$varname]);
        }

        // 比对在匹配模式中没有使用到，但指定了默认值的变量
        foreach ($route['defaults'] as $varname => $value)
        {
            if (isset($url_args[$varname]) && $url_args[$varname] != $value)
            {
                return false;
            }
            unset($url_args[$varname]);
        }

        // 如果构造完 URL 后 $url_args 不为空，则说明 $url_args 存在该路由无法匹配的参数
        if (!empty($url_args))
        {
            //$this->_log->append(".... 多余的参数: " . http_build_query($url_args));
            return false;
        }

        // 在没有使用通配符的情况下尝试消除 URL 中不必要的部分
        if (!$use_wildcard)
        {
            $count = count($path);
            for ($pos = $count - 1; $pos >= 0; $pos--)
            {
                switch ($route['pattern_parts'][$pos])
                {
                case self::PARTTYPE_STATIC:
                    // 一旦该部分是不可选的静态内容，则停止消除
                    if (!$route['static_optional'][$pos])
                    {
                        $pos = -1;
                    }
                    else
                    {
                        unset($path[$pos]);
                    }
                    break;

                default:
                    // 如果该部分是变量，同时 $path 中的值又和路由中指定的默认值一样，则该部分可以消除
                    $varname = $route['vars'][$pos];
                    if (isset($route['defaults'][$varname])
                        && $path[$pos] == $route['defaults'][$varname]
                        && strlen($path[$pos]) == strlen($route['defaults'][$varname]))
                    {
                        unset($path[$pos]);
                    }
                    else
                    {
                        // 否则终止消除
                        $pos = -1;
                    }
                }
            }
        }

        // 构造 URL
        foreach ($path as $offset => $path_part)
        {
            if (strlen($path_part))
            {
                $path[$offset] = rawurlencode($path_part);
            }
            else
            {
                unset($path[$offset]);
            }
        }
        if (!empty($path))
        {
            $url = $this->_part_delimiter . implode($this->_part_delimiter, $path);
        }
        else
        {
            $url = '';
        }

        if (!empty($query_args))
        {
            $url .= '?' . http_build_query($query_args, '', '&');
        }
        return $url;
    }

    /**
     * 准备指定的路由
     *
     * @param string $route_name
     */
    protected function _prepareRoute($route_name)
    {
        if (isset($this->_routes[$route_name]))
        {
            return;
        }

        if (!isset($this->_rules[$route_name]))
        {
            throw new QRouter_RouteNotFoundException($route_name);
        }

        $rule = $this->_rules[$route_name];

        $route = array
        (
            // 路由的名字
            'name' => $route_name,
            // 匹配模式
            'pattern' => !empty($rule['pattern']) ? $rule['pattern'] : '',
            // 匹配模式各部分的类型
            'pattern_parts' => array(),
            // 变量的比对规则
            'config' => isset($rule['config']) ? (array)$rule['config'] : array(),
            // 变量的默认值
            'defaults' => isset($rule['defaults']) ? (array)$rule['defaults'] : array(),
            // 变量在匹配模式中的位置及其名称
            'vars' => array(),
            // 所有已定义的变量
            'varnames' => array(),
            // 静态文本部分
            'static_parts' => array(),
            // 静态部分的可选状态
            'static_optional' => array(),
        );

        $pattern = ltrim($route['pattern'], $this->_part_delimiter);
        foreach ($route['defaults'] as $varname => $value)
        {
            $route['varnames'][$varname] = true;
        }

        // 将匹配模式 pattern 按照“/”进行分割
        $parts = explode($this->_part_delimiter, $pattern);
        $use_static_optional = false;
        foreach ($parts as $pos => $part)
        {
            if (substr($part, 0, 1) == $this->_var_prefix)
            {
                // 从分割后的组成部分中提取出变量名
                $varname = substr($part, 1);

                if ($use_static_optional && !isset($route['defaults'][$varname]))
                {
                    // 如果前面部分已经出现了可选的静态内容，则后续的变量必须指定默认值
                    throw new QRouter_InvalidRouteException($route_name, $rule);
                }

                if (isset($this->_reserved_vars[$varname]))
                {
                    $default_regex = $this->_default_reserved_var_regex;
                }
                else
                {
                    $default_regex = $this->_default_var_regex;
                }

                if (!isset($route['config'][$varname]))
                {
                    $route['config'][$varname] = $default_regex;
                }

                // 记录下变量名及其位置
                $route['vars'][$pos] = $varname;
                $route['varnames'][$varname] = true;
                $route['pattern_parts'][$pos] = self::PARTTYPE_VAR;
            }
            else
            {
                // 如果静态部分是 [text] 这样的形式，则转换为变量
                if (substr($part, 0, 1) == '[' && substr($part, -1) == ']')
                {
                    $part = substr($part, 1, -1);
                    $use_static_optional = $optional = true;
                }
                else
                {
                    if ($use_static_optional)
                    {
                        // 如果前面部分已经出现了可选的静态内容，则后续的内容也必须是可选的
                        throw new QRouter_InvalidRouteException($route_name, $rule);
                    }

                    $part = str_replace(array('\\[', '\\]'), array('[', ']'), $part);
                    $optional = false;
                }
                
                $route['static_parts'][$pos] = $part;
                $route['static_optional'][$pos] = $optional;
                if ($part != '*' && $part != '?')
                {
                    $route['pattern_parts'][$pos] = self::PARTTYPE_STATIC;
                }
                else
                {
                    $route['pattern_parts'][$pos] = self::PARTTYPE_WILDCARD;
                    if (isset($parts[$pos + 1]))
                    {
                        // 通配符只能在最后
                        throw new QRouter_InvalidRouteException($route_name, $rule);
                    }
                }
            }
        }

        $this->_routes[$route_name] = $route;
    }

}

/**
 * @}
 */

