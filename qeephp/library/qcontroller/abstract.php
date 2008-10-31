<?php
// $Id$

/**
 * @file
 * 定义 QController_Abstract 类
 *
 * @ingroup mvc
 *
 * @{
 */

// require Q_DIR . '/qcontroller/helper/general.php';

/**
 * QController_Abstract 实现了一个其它控制器的基础类
 */
abstract class QController_Abstract
{
    const FLASH_MSG_ERROR   = '#flash_msg_error#';
    const FLASH_MSG_INFO    = '#flash_msg_info#';
    const FLASH_MSG_WARNING = '#flash_msg_warning#';

    /**
     * 应用程序对象
     *
     * @var QApplication_Abstract
     */
    public $app;

    /**
     * 封装请求的对象
     *
     * @var QContext
     */
    public $context;

    /**
     * 控制器要使用的布局视图
     *
     * @var string
     */
    public $view_layouts = null;

    /**
     * 控制器要使用的视图类
     *
     * @var string
     */
    public $view_class = null;

    /**
     * 控制器要使用的视图
     *
     * @var string
     */
    public $viewname = null;

    /**
     * 视图设置
     *
     * @var array
     */
    public $view_config = array
    (
        /**
         * 视图文件所在目录，如果不指定则由框架自行决定
         *
         * @var string
         */
        'view_dir' => null,

        /**
         * 布局视图文件所在目录，如果不指定则由框架自行决定
         *
         * @var string
         */
        'view_layouts_dir' => null,
    );

    /**
     * 控制器动作要渲染的数据
     *
     * @var array
     */
    public $view = array();

    /**
     * 构造函数
     */
    function __construct(QApplication_Abstract $app, QContext $context)
    {
        $this->app = $app;
        $this->context = $context;
    }

    /**
     * 返回该控制器所属的应用程序对象
     *
     * @return QApplication_Abstract
     */
    function app()
    {
        return $this->app;
    }

    /**
     * 执行指定的动作
     *
     * @return mixed
     */
    function _execute(array $args = array())
    {
        $action_method = 'action' . ucfirst(strtolower($this->context->action_name));
        if (! method_exists($this, $action_method))
        {
            return call_user_func($this->context->getIni('dispatcher_on_action_not_found'), $this->context);
        }

        $this->_before_execute();

        $response = call_user_func_array(array($this, $action_method), $args);
        $response = $this->_after_execute($response);

        if (is_null($response) && ! is_null($this->view))
        {
        	$viewname = $this->_getViewname();

            /**
             * 尝试载入控制器对应的视图类，如果没有找到则使用默认的 QView
             */
            if ($this->view_class)
            {
            	$view_class_name = $this->view_class;
            }
            else
            {
                $view_class_name = 'QView';
            }

            $response = new $view_class_name($this->context);
            $response->viewname = $viewname;
            $response->viewdata = (array)$this->view;
            if (!empty($this->view_config['view_dir']))
            {
                $response->view_dir = $this->view_config['view_dir'];
            }
            $response->view_layouts = $this->view_layouts;
            if (!empty($this->view_config['view_layouts_dir']))
            {
                $response->view_layouts_dir = $this->view_config['view_layouts_dir'];
            }

            if (method_exists($response, $action_method))
            {
            	$response->{$action_method}();
            }
        }

        return $response;
    }

    /**
     * 魔法方法，用于自动加载 helper
     *
     * @param string $helper_name
     *
     * @return mixed
     */
    function __get($helper_name)
    {
        return $this->{$helper_name} = $this->_loadHelper($helper_name);
    }

    /**
     * 确定视图名字
     *
     * 继承类可以覆盖此方法来改变视图名称确认规则。
     *
     * @return string
     */
    protected function _getViewname()
    {
        $viewname = !empty($this->viewname) ? $this->viewname : $this->context->action_name;
        if ($viewname[0] == '/')
        {
            return $viewname;
        }

        $arr = $this->context->destinfo($viewname);

        if ($this->context->getIni('view_config/flat_dir'))
        {
            return "{$this->context->controller_name}_{$viewname}";
        }
        else
        {
            return "{$this->context->controller_name}/{$viewname}";
        }
    }

    /**
     * 转发请求到控制器的指定动作
     *
     * @param string $destination
     *
     * @return mixed
     */
    protected function _forward($destination)
    {
        $args = func_get_args();
        array_shift($args);
        return new QController_Forward($destination, $this->context, $args);
    }

    /**
     * 载入指定名字的助手对象
     *
     * @param string $helper_name
     *
     * @return object
     */
    protected function _loadHelper($helper_name)
    {
        $class_name = 'Helper_' . ucfirst($helper_name);
        return new $class_name($this->context);
    }

    /**
     * 执行控制器动作之前调用
     */
    protected function _before_execute()
    {
        return true;
    }

    /**
     * 执行控制器动作之后调用
     *
     * @param mixed $response
     *
     * @return mixed
     */
    protected function _after_execute($response)
    {
        return $response;
    }

    /**
     * 构造限於当前控制器的 URL
     *
     * @param string $action
     * @param array $params
     *
     * @return string
     */
    protected function _url($action = null, array $params = null)
    {
        return $this->context->url($this->context->controller_name, $action, $params);
    }

    /**
     * 返回一个 QView_Redirect 对象
     *
     * @param string $url
     * @param int $delay
     *
     * @return QView_Redirect
     */
    protected function _redirect($url, $delay = 0)
    {
        return new QView_Redirect($url, $delay);
    }
}

/**
 * @}
 */
