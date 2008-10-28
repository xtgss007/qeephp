<?php
// $Id$

/**
 * @file
 * 定义 QView_Adapter_Gingko 类以及一些辅助方法
 *
 * @ingroup view
 *
 * @{
 */

/**
 * QView_Adapter_Gingko 是 QeePHP 内置的一个模板引擎
 */
class QView_Adapter_Gingko extends QView_Adapter_Abstract
{
	/**
	 * 模板变量
	 *
	 * @var array
	 */
	protected $_vars = array();

	/**
	 * 指定模板变量
	 *
	 * @param string|array $data
	 * @param mixed $value
	 */
	function assign($data, $value = null)
	{
		if (is_array($data))
		{
			$this->_vars = array_merge($this->_vars, $data);
		}
		else
		{
			$this->_vars[$data] = $value;
		}
	}

	/**
	 * 显示指定文件
	 *
	 * @param string $filename
	 */
	function display($filename)
	{
		echo $this->fetch($filename);
	}

	/**
	 * 载入指定文件并返回解析结果
	 *
	 * @param string $___filename
	 *
	 * @return string
	 */
	function fetch($___filename)
	{
		extract($this->_vars);
		ob_start();
		include $___filename;
		return ob_get_clean();
	}

	/**
	 * 清除所有模板变量
	 */
	function clear()
	{
		$this->_vars = array();
    }

    /**
     * 动态载入视图插件
     *
     * @param string $helper_name
     *
     * @return Helper_Abstract
     */
	function __get($helper_name)
    {
        return $this->{$helper_name} = $this->_getHelper($helper_name);
	}
}

/**
 * 转换 HTML 特殊字符，等同于 htmlspecialchars()
 *
 * @param string $text
 *
 * @return string
 */
function h($text)
{
    return htmlspecialchars($text);
}

/**
 * 转换 HTML 特殊字符以及空格和换行符
 *
 * 空格替换为 &nbsp; ，换行符替换为 <br />。
 *
 * @param string $text
 *
 * @return string
 */
function t($text)
{
    return nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($text)));
}

/**
 * 将任意字符串转换为 JavaScript 字符串（不包括首尾的"）
 *
 * @param string $content
 *
 * @return string
 */
function t2js($content)
{
    return str_replace(array("\r", "\n"), array('', '\n'), addslashes($content));
}


/**
 * @}
 */
