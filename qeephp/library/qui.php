<?php
// $Id$

/**
 * @file
 * 定义 QUI 类
 *
 * @ingroup ui
 *
 * @{
 */

/**
 * QUI 类负责构造界面控件，以及处理控件的事件响应
 */
abstract class QUI
{

    /**
     * 实例化一个控件
     *
     * @param QContext $context
     * @param string $type
     * @param string $id
     * @param array $attribs
     *
     * @return QUI_Control_Abstract
     */
    static function control($context, $type, $id = null, array $attribs = null)
    {
        static $standard_loaded = false;

        if (empty($id))
        {
            $id = strtolower($type);
        }
        else
        {
            $id = strtolower($id);
        }

        $class_name = 'Control_' . ucfirst(strtolower($type));

        if (!$standard_loaded)
        {
            $standard_loaded = true;
            require_once Q_DIR . '/qui/control/_standard.php';
        }

        if (! is_array($attribs))
        {
            $attribs = array();
        }

        if (is_null($context))
        {
            $context = QContext::instance();
        }

        $control = new $class_name($context, $id, $attribs);
        /* @var $control QUI_Control_Abstract */
        $control->namespace = $context->namespace;
        $control->module = $context->module;
        return $control;
    }

	/**
	 * 根据 ID 和 NAME 属性返回字符串
	 *
	 * @param QUI_Control_Abstract $control
	 *
	 * @return string
	 */
	static function renderIdAndName(QUI_Control_Abstract $control)
	{
		$out = '';
		$out .= 'id="' . htmlspecialchars($control->id()) . '" ';
		$out .= 'name="' . htmlspecialchars($control->name()) . '" ';
		return $out;
	}

	/**
	 * 根据 DISABLED 属性返回字符串
	 *
	 * @param QUI_Control_Abstract $control
	 *
	 * @return string
	 */
	static function renderDisabled(QUI_Control_Abstract $control)
	{
		$disabled = $control->getAttrib('disabled');
		return ($disabled) ? 'disabled="disabled" ' : '';
	}

	/**
	 * 根据 CHECKED 属性返回字符串
	 *
	 * @param QUI_Control_Abstract $control
	 *
	 * @return string
	 */
	static function renderChecked(QUI_Control_Abstract $control)
	{
		$checked = $control->getAttrib('checked');
		if (empty($checked)) { return ''; }

		$value = $control->getAttrib('value');
		if (!empty($value)) {
			if ($checked == $value && strlen($checked) == strlen($value) && strlen($checked) > 0) {
				return 'checked="checked" ';
			} else {
				return '';
			}
		} else {
			return 'checked="checked" ';
		}
	}

	/**
	 * 构造控件的属性字符串
	 *
	 * @param QUI_Control_Abstract $control
	 * @param array|string $exclude
	 *
	 * @return string
	 */
	static function renderAttribs(QUI_Control_Abstract $control, $exclude = 'name, value')
	{
		$exclude = Q::normalize($exclude);
		$exclude = array_flip($exclude);
		$out = '';
		foreach ($control->getAttribs() as $attrib => $value)
		{
			if (isset($exclude[$attrib])) { continue; }
			$out .= $attrib .'="' . str_replace('"', '\'', $value) . '" ';
		}
		return $out;
	}

	/**
	 * 将多维数组转换为一维数组
	 *
	 * @param array $items
	 * @param string $key
	 * @param string $caption
	 * @param boolean $key2caption
	 *
	 * @return boolean
	 */
	static function splitMultiDimArray(& $items, $key, $caption, $key2caption = false)
	{
		if ($caption == '') {
			$first = reset($items);
			if (!is_array($first)) {
				// LC_MSG: 无效的 items 属性.
				throw new QUI_Exception(__('无效的 items 属性.'));
			}
			next($first);
			$caption = key($first);
		}

		// 传入的 items 是一个多维数组
		$new = array();
		if ($key2caption) {
			foreach ($items as $item) {
				$new[$item[$key]] = $item[$caption];
			}
		} else {
			foreach ($items as $item) {
				$new[$item[$caption]] = $item[$key];
			}
		}
		$items = $new;
		return true;
	}
}

/**
 * @}
 */
