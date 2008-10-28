<?php
// $Id$


/**
 * @file
 * 定义 QView 类
 *
 * @ingroup mvc
 *
 * @{
 */

class QView
{
    /**
     * 运行时上下文
     *
     * @var QContext
     */
    public $context;

    /**
     * 要渲染的视图的名称
     *
     * @var string
     */
    public $viewname;

    /**
     * 视图文件所在目录，如果不指定则由框架自行决定
     *
     * @var string
     */
    public $view_dir;

    /**
     * 视图布局
     *
     * @var string
     */
    public $view_layouts;

    /**
     * 视图布局所在目录，如果不指定则由框架自行决定
     *
     * @var string
     */
    public $view_layouts_dir;

    /**
     * element 视图文件所在目录
     *
     * @var string
     */
    public $elements_view_dir;

    /**
     * 要传递到视图的数据
     *
     * @var array
     */
    public $viewdata;

    /**
     * 要使用的视图适配器类名称
     *
     * @var string
     */
    public $view_adapter_class;

    /**
     * 当前使用的适配器
     *
     * @var QView_Adapter_Abstract
     */
    protected $_adapter;

    /**
     * 构造函数
     *
     * @param QContext $context
     * @param string $view_adapter_class
     */
    function __construct(QContext $context, $view_adapter_class = null)
    {
        $this->context = $context;
        $this->view_adapter_class = empty($view_adapter_class) ? $context->getIni('view_adapter') : $view_adapter_class;
    }

    /**
     * 执行
     */
    function execute()
    {
        if (! headers_sent() && $this->context->getIni('runtime_response_header'))
        {
            header('Content-Type: text/html; charset=' . $this->context->getIni('i18n_response_charset'));
        }
        $this->_before_render();
        $this->render();
        $this->_after_render();
    }

    /**
     * 用视图适配器来渲染视图
     */
    function render($return = false)
    {
        // 构造视图适配器
        $context = $this->context;
        $adapter_class = $this->view_adapter_class;
        $this->_adapter = $adapter = new $adapter_class($this->context);
        /* @var $adapter QView_Adapter_Abstract */

        // 将视图变量传递到适配器
        $adapter->assign($this->viewdata);
        $adapter->assign('_ctx', $context);
        $filename = self::getViewFilename($context, $adapter, $this->viewname, $this->view_dir);

        // 渲染内容视图
        if (file_exists($filename))
        {
            $contents_for_layouts = $adapter->fetch($filename);
        }
        else
        {
            $contents_for_layouts = '';
        }

        // 确定要使用的布局视图
        $view_layouts = self::getViewLayouts($context, $this->view_layouts);

        // 如果使用父布局视图，则要切换 context
        if (($pos = strpos($view_layouts, '@')) !== false)
        {
            $module = substr($view_layouts, $pos + 1);
            $view_layouts = substr($view_layouts, 0, $pos);
            $arr = explode('::', $view_layouts);
            if (isset($arr[1]))
            {
                $context->namespace = $arr[0];
                $view_layouts = $arr[1];
            }
            else
            {
                $context->namespace = null;
            }
            if (empty($view_layouts))
            {
                $view_layouts = 'default';
            }
            if ($module == 'app' || empty($module))
            {
                $module = null;
            }
            $context->module_name = $module;
        }

        // 渲染布局视图
        $filename = self::getViewLayoutsFilename($context, $adapter, $view_layouts, $this->view_layouts_dir);

        if (file_exists($filename))
        {
            $adapter->assign('contents_for_layouts', $contents_for_layouts);
            $contents = $adapter->fetch($filename);
        }
        else
        {
            $contents = $contents_for_layouts;
        }

        // 输出或返回渲染内容
        if ($return)
        {
            return $contents;
        }
        else
        {
            echo $contents;
            return '';
        }
    }

    /**
     * 确定视图模板文件所在目录
     *
     * @param QContext $context
     * @param string $view_dir
     *
     * @return string
     */
    static function getViewDir(QContext $context, $view_dir = null)
    {
        if ($view_dir)
        {
            return $view_dir;
        }

        $dir = $context->getIni('view_config/view_dir');
        if ($dir)
        {
            return $dir;
        }

        $flat_dir = intval($context->getIni('view_config/flat_dir'));
        $root_dir = $context->app()->ROOT_DIR();
        switch ($flat_dir)
        {
        case 0:
        case 1:
        case 2:
            if ($context->module_name)
            {
                $root = $root_dir . "/modules/{$context->module_name}/view";
            }
            else
            {
                $root = $root_dir . "/app/view";
            }

            if ($flat_dir < 2 && $context->namespace)
            {
                $root .= "/{$context->namespace}";
            }
            break;

        case 3:
        default:
            $root = $root_dir . "/app/view";
        }

        return $root;
    }

    /**
     * 确定视图模板文件的完整路径
     *
     * @param QContext $context
     * @param QView_Adapter_Abstract $adapter
     * @param string $viewname
     * @param string $view_dir
     *
     * @return string
     */
    static function getViewFilename(QContext $context, QView_Adapter_Abstract $adapter,
                                    $viewname, $view_dir = null)
    {
        $dir = self::getViewDir($context, $view_dir);
        $dir = rtrim($dir, '/\\') . '/';

        $extname = strpos($viewname, '.') === false ? $adapter->tpl_file_ext : '';
        if ($viewname[0] == '/')
        {
            return $dir . substr($viewname, 1) . $extname;
        }

        $flat_dir = intval($context->getIni('view_config/flat_dir'));
        switch ($flat_dir)
        {
        case 0:
        case 1:
            return "{$dir}{$viewname}{$extname}";

        case 2:
            if ($context->namespace)
            {
                return "{$dir}{$context->namespace}_{$viewname}{$extname}";
            }
            return "{$dir}{$viewname}{$extname}";

        case 3:
        default:
            if ($context->module_name)
            {
                $prefix = $context->module_name . '_';
            }
            else
            {
                $prefix = 'app_';
            }

            if ($context->namespace)
            {
                $prefix .= "{$context->namespace}_";
            }

            return "{$dir}{$prefix}{$viewname}{$extname}";
        }
    }

    /**
     * 确定要使用的布局视图名字
     *
     * @param QContext $context
     * @param string $view_layouts
     *
     * @return string
     */
    static function getViewLayouts(QContext $context, $view_layouts = null)
    {
        if (!empty($view_layouts))
        {
            return $view_layouts;
        }

        /**
         * 可以通过 view_config/layouts/控制器名 来指定控制器使用的布局视图
         */
        $view_layouts = $context->getIni("view_config/layouts/{$context->controller_name}");
        if (empty($view_layouts))
        {
            // 如果没有指定，则尝试取得默认布局视图名
            $view_layouts = $context->getIni("view_config/default_view_layouts");
            if (empty($view_layouts))
            {
                // 使用默认布局视图名
                $view_layouts = 'default';
            }
        }

        return $view_layouts;
    }

    /**
     * 确定布局视图文件的完整路径
     *
     * @return string
     */
    static function getViewLayoutsFilename(QContext $context, QView_Adapter_Abstract $adapter,
                                           $view_layouts, $view_layouts_dir = null)
    {
        return self::getExtraViewFilename($context, $adapter, $view_layouts, '_layouts', '_layout',
                                          'view_layouts_dir', $view_layouts_dir);
    }

    /**
     * 确定 element 视图文件的完整路径
     *
     * @return string
     */
    static function getElementViewFilename(QContext $context, QView_Adapter_Abstract $adapter,
                                           $element, $elements_view_dir = null)
    {
        return self::getExtraViewFilename($context, $adapter, $element, '_elements', '_element',
                                          'elements_view_dir', $elements_view_dir);
    }

    /**
     * 确定用户界面控件视图文件的完整路径
     *
     * @return string
     */
    static function getControlViewFilename(QContext $context, QView_Adapter_Abstract $adapter,
                                           $control, $controls_view_dir = null)
    {
        return self::getExtraViewFilename($context, $adapter, $control, '_controls', '_control',
                                          'controls_view_dir', $controls_view_dir);
    }

    /**
     * 辅助方法，用于获得一些特定目录的路径
     *
     * @return string
     */
    static function getExtraViewDir(QContext $context, $suffix, $ini_name, $ini_default = null)
    {
        if ($ini_default)
        {
            return $ini_default;
        }

        $dir = $context->getIni('view_config/' . $ini_name);
        if ($dir)
        {
            return $dir;
        }

        $dir = self::getViewDir($context, $ini_default);
        if (intval($context->getIni('view_config/flat_dir')) < 1)
        {
            $dir = $dir . '/' . $suffix;
        }

        return $dir;
    }

    static function getExtraViewFilename(QContext $context, QView_Adapter_Abstract $adapter,
                                         $viewname, $dir_suffix, $file_suffix,
                                         $ini_name, $ini_default = null)
    {
        $dir = self::getExtraViewDir($context, $dir_suffix, $ini_name, $ini_default);
        $dir = rtrim($dir, '/\\') . '/';

        $extname = strpos($viewname, '.') === false ? $adapter->tpl_file_ext : '';
        if ($viewname[0] == '/')
        {
            return $dir . substr($viewname, 1) . $extname;
        }

        $flat_dir = intval($context->getIni('view_config/flat_dir'));
        switch ($flat_dir)
        {
        case 0:
            $prefix = '';
            break;

        case 1:
            $prefix = '_';
            break;

        case 2:
        case 3:
        default:
            if ($context->namespace)
            {
                $prefix = $context->namespace . '_';
            }
            else
            {
                $prefix = '';
            }

            if ($flat_dir > 2)
            {
                if ($context->module_name)
                {
                    $prefix = $context->module_name . '_' . $prefix;
                }
                else
                {
                    $prefix = 'app_' . $prefix;
                }
            }

            $prefix = "_{$prefix}";
        }

        return "{$dir}{$prefix}{$viewname}{$file_suffix}{$extname}";
    }

    /**
     * 渲染之前调用
     *
     * 继承类可以覆盖此方法。
     */
    protected function _before_render()
    {
    }

    /**
     * 渲染之后调用
     *
     * 继承类可以覆盖此方法。
     */
    protected function _after_render()
    {
    }

}

