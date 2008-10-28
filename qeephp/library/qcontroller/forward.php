<?php
// $Id$

/**
 * @file
 * 定义 QController_Forward 类
 *
 * @ingroup mvc
 *
 * @{
 */

class QController_Forward
{
    /**
     * 运行时上下文
     *
     * @var QContext
     */
    public $context;

    /**
     * 要调用的控制器方法路径
     *
     * @var string
     */
    public $destination;

    /**
     * 附加参数
     *
     * @var array
     */
    public $args;

    /**
     * 构造函数
     *
     * @param QContext $context
     * @param array $args
     */
    function __construct($destination, QContext $context, array $args = array())
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
            if (! isset($namespace))
            {
                $namespace = '';
            }
        }

        $arr = explode('/', $destination);
        $controller = array_shift($arr);
        if (empty($controller))
        {
            $controller = $context->controller_name;
        }
        $action = array_shift($arr);

		$context = QContext::instance();
        if (isset($module))
        {
            $context->module_name = $module;
        }
        if (isset($namespace))
        {
            $context->namespace = $namespace;
        }
        $context->controller_name = $controller;
        $context->action_name = $action;
        $context->setRequestUDI();

        $this->context = $context;
        $this->args = $args;
    }
}

/**
 * @}
 */
