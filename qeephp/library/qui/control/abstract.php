<?php
// $Id$

/**
 * @file
 * 定义 QUI_Control_Abstract 类
 *
 * @ingroup ui
 *
 * @{
 */

/**
 * QUI_Control_Abstract 是用户界面控件的基础类
 */
abstract class QUI_Control_Abstract
{
	/**
	 * 控件可以响应的事件类型
	 */
	const ON_CLICK      = 'onclick';
	const ON_DBLCLICK   = 'ondblclick';
	const ON_CHANGE     = 'onchange';
	const ON_FOCUS      = 'onfocus';
	const ON_BLUR		= 'onblur';
	const ON_KEYPRESS   = 'onkeypress';
	const ON_RESIZE     = 'onresize';
	const ON_LOAD       = 'onload';
	const ON_UNLOAD     = 'onunload';

	/**
	 * 运行时上下文
	 *
	 * @var QContext
	 */
	public $context;

	/**
	 * 渲染控件视图时要使用的视图变量
	 *
	 * @var array
	 */
	public $view = array();

	/**
	 * 用于处理事件的 ID
	 */
	protected $_id_for_event;

	/**
	 * 控件的 ID
	 *
	 * @var string
	 */
	protected $_id;

	/**
	 * 渲染控件时，控件 id 和 name 属性要添加的前缀
	 *
	 * @var string
	 */
	protected $_id_prefix = '';

	/**
	 * 控件的属性
	 *
	 * @var array
	 */
	protected $_attribs;

	/**
	 * 指示要使用的视图适配器，如果未指定则使用 view_adapter 设置
	 *
	 * @var string
	 */
	protected $_view_adapter_class;

    /**
     * 当前使用的视图适配器
     *
     * @var QView_Adapter_Abstract
     */
    protected $_view_adapter;

    /**
     * 控件视图文件所在目录
     *
     * @var string
     */
    protected $_controls_view_dir;

	/**
	 * 构造函数
	 *
	 * @param string $id
	 * @param array $attribs
	 */
	function __construct(QContext $context, $id, array $attribs = array())
	{
		$this->context = $context;
		$this->_id = $id;
        if (!isset($attribs['name']))
        {
			$attribs['name'] = $id;
		}
		$this->_attribs = (array)$attribs;
		$this->_id_for_event = self::_uuid();
	}

	/**
	 * 返回控件的 ID
	 *
	 * @return string
	 */
	function id()
	{
		return $this->_id;
	}

	/**
	 * 返回控件的 name
	 *
	 * @return string
	 */
	function name()
	{
		return isset($this->_attribs['name']) ? $this->_attribs['name'] : $this->_id;
	}

	/**
	 * 返回控件指定属性的值
	 *
	 * @param string $attr
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	function getAttrib($attr, $default = null)
	{
		return isset($this->_attribs[$attr]) ? $this->_attribs[$attr] : $default;
	}

	/**
	 * 返回控件所有属性的值
	 *
	 * @return array
	 */
	function getAttribs()
	{
		return $this->_attribs;
	}

	/**
	 * 提取控件指定属性的值
	 *
	 * @param string $attr
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	function extractAttrib($attr, $default = null)
	{
		$ret = isset($this->_attribs[$attr]) ? $this->_attribs[$attr] : $default;
		unset($this->_attribs[$attr]);
		return $ret;
	}

	/**
	 * 返回控件所有属性的值
	 *
	 * @return array
	 */
	function extractAttribs()
	{
		$ret = $this->_attribs;
		$this->_attribs = array();
		return $ret;
	}

	/**
	 * 设置控件指定属性的值
	 *
	 * @param string $attr
	 * @param mixed $value
	 *
	 * @return QUI_Control_Abstract
	 */
	function setAttrib($attr, $value)
	{
		$this->_attribs[$attr] = $value;
		return $this;
	}

	/**
	 * 设置控件多个属性的值
	 *
	 * @param array $attribs
	 *
	 * @return QUI_Control_Abstract
	 */
	function setAttribs(array $attribs)
	{
		$this->_attribs = array_merge($this->_attribs, $attribs);
		return $this;
	}

	/**
	 * 引发指定的事件
	 *
	 * @param enum $event
	 * @param array $params
	 *
	 * @return mixed
	 */
	function handlingEvent($event, array $params = null)
	{
		return null;
	}

	/**
	 * 渲染一个控件
	 *
	 * @param boolean $return
	 *
	 * @return mixed
	 */
    abstract function render($return = false);

    /**
     * 渲染一个控件
     *
     * @param string $type
     * @param string $id
     * @param array $attribs
     *
     * @return string
     */
    protected function _renderControl($type, $id = null, array $attribs = null)
    {
        return QUI::control($this->context, $type, $id, $attribs)->render(true);
    }

	/**
	 * 渲染指定的视图文件
	 *
	 * 渲染时，视图要使用的数据保存在控件的 $view 属性中。
	 *
	 * @param string $viewname
	 * @param boolean $return
	 *
	 * @return string
	 */
	protected function _renderBlock($viewname, $return = false)
	{
	    if (!is_object($this->_view_adapter))
	    {
    		$adapter_class = is_null($this->_view_adapter_class)
    		               ? $this->context->getIni('view_adapter')
    		               : $this->_view_adapter_class;
    		$adapter_obj_id = "webcontrols_{$adapter_class}";

    		if (Q::isRegistered($adapter_obj_id))
    		{
    			/**
    			 * @var QView_Adapter_Abstract
    			 */
    			$adapter = Q::registry($adapter_obj_id);
    		}
    		else
    		{
    			/**
    			 * @var QView_Adapter_Abstract
    			 */
    			$adapter = new $adapter_class($this->context);
    			Q::register($adapter, $adapter_obj_id);
    		}
	    }
	    else
	    {
	        $adapter = $this->_view_adapter;
	    }

		$adapter->clear();
		$adapter->assign($this->view);
		$adapter->assign('_ctx', $this->context);

		$filename = QView::getControlViewFilename($this->context, $adapter, $viewname, $this->_controls_view_dir);

		if ($return)
		{
			return $adapter->fetch($filename);
		}
		else
		{
			return $adapter->display($filename);
		}
	}

	/**
	 * 为控件生成一个不重复的 ID，用于事件系统
	 *
	 * @return string
	 */
	static private function _uuid()
	{
		static $being_timestamp = 1206576000; // 2008-03-27
		static $suffix_len = 3;

		$time = explode( ' ', microtime());
		$id = ($time[1] - $being_timestamp) . sprintf('%06u', substr($time[0], 2, 6));
		if ($suffix_len > 0) {
			$id .= substr(sprintf('%010u', mt_rand()), 0, $suffix_len);
		}
		return $id;
	}
}
