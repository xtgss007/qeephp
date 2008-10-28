<?php
// $Id$

/**
 * @file
 * 定义 QView_Adapter_Abstract 抽象基础类
 *
 * @ingroup view
 *
 * @{
 */

/**
 * QView_Adapter_Abstract 是所有视图适配器的基础类
 */
abstract class QView_Adapter_Abstract
{
	/**
	 * 指示视图模板文件的扩展名
	 *
	 * @var string
	 */
	public $tpl_file_ext = '.php';

    /**
     * 运行时上下文
     *
     * @var QContext
     */
    public $context;

	/**
	 * 构造函数
	 *
	 * @param QContext $context
	 *   运行时上下文
	 */
	function __construct(QContext $context)
    {
        $this->context = $context;

		$view_config = (array)$context->getIni('view_config');
		foreach ($view_config as $key => $value)
		{
			if (isset($this->{$key}))
			{
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * 指定视图变量
	 *
	 * @param string|array $data
	 * @param mixed $value
	 */
	abstract function assign($data, $value = null);

	/**
	 * 清除所有已经指定的视图变量
	 */
	abstract function clear();

	/**
	 * 渲染并输出指定的视图模板文件
	 *
	 * @param string $filename
	 */
	abstract function display($filename);

	/**
	 * 渲染指定的视图模板文件，并返回渲染结果
	 *
	 * @param string $filename
	 */
	abstract function fetch($filename);

    /**
     * 输出一个 URL
     */
    function url($controller_name = null, $action_name = null, $params = null,
                 $namespace = null, $module = null, $route_name = null)
    {
        echo $this->context->url($controller_name, $action_name, $params, $namespace, $module, $route_name);
    }

    /**
     * 创建一个用户界面控件，并渲染该控件
     */
    function control()
    {
        $args = func_get_args();
        array_unshift($args, $this->context);
        $control = call_user_func_array(array('QUI', 'control'), $args);
        /* @var $control QUI_Control_Abstract */
        $control->render();
    }

    /**
     * 输出一个元素
     */
    function element($element)
    {
        $dir = QView::getExtraViewDir($this->context, '_elements', 'elements_view_dir');
        $filename = "{$element}_element{$this->tpl_file_ext}";
        if ($this->context->getIni('view_config/flat_dir') > 0)
        {
        	$filename = '_' . $filename;
        }
        $_ctx = $this->context;
        include "{$dir}/{$filename}";
    }

	/**
	 * 获得指定名字的助手对象
	 *
	 * @param string $helper_name
	 *
	 * @return object
	 */
	protected function _getHelper($helper_name)
    {
		$class_name = 'Helper_' . $helper_name;
		return new $class_name($this);
	}
}

