<?php
// $Id$

/**
 * @file
 * 定义 QView_Adapter_Smarty 类
 *
 * @ingroup view
 *
 * @{
 */

// {{{ includes

do
{
	if (class_exists('Smarty', false)) { break; }

	$view_config = (array)Q::getIni('view_config');
	if (empty($view_config['smarty_dir']) && !defined('SMARTY_DIR'))
	{
		throw new QView_Exception(__('Application settings "view_config[\'smarty_dir\']" ' .
									 'and constant SMARTY_DIR must be defined for QView_Adapter_Smarty.'));
	}

	if (empty($view_config['smarty_dir']))
	{
		$view_config['smarty_dir'] = SMARTY_DIR;
	}

	Q::loadClassFile('Smarty.class.php', $view_config['smarty_dir'], 'Smarty');
} while (false);

// }}}

/**
 * QView_Adapter_Smarty 提供了对 Smarty 模板引擎的支持
 */
class QView_Adapter_Smarty extends QView_Adapter_Abstract
{
	public $tpl_file_ext = '.html';

	/**
	 * Smarty 对象
	 *
	 * @var Smarty
	 */
	public $smarty;

	function __construct(QContext $context)
	{
		parent::__construct($context);
		$this->smarty = new Smarty();

		$view_config = (array)$context->getIni('view_config');
		foreach ($view_config as $key => $value)
		{
			$this->smarty->{$key} = $value;
		}

		$this->smarty->assign('context', $this->context);
		$this->smarty->assign('view_adapter', $this);

		$this->smarty->register_function('url',		array($this, 'func_url'));
		$this->smarty->register_function('control',	array($this, 'func_control'));
		$this->smarty->register_function('ini',		array($this, 'func_ini'));
        $this->smarty->register_modifier('mb_truncate', array($this, 'mod_mb_truncate'));
	}

	function assign($data, $value = null)
	{
		$this->smarty->assign($data, $value);
	}

	function clear()
	{
		$this->smarty->clear_all_assign();
		$this->smarty->assign('context', $this->context);
	}

	function display($filename, $cache_id = null, $compile_id = null)
	{
		$this->smarty->display($filename, $cache_id, $compile_id);
	}

	function fetch($filename, $cache_id = null, $compile_id = null)
	{
		return $this->smarty->fetch($filename, $cache_id, $compile_id);
	}

	/**
	 * 提供对 QeePHP url() 函数的支持
	 */
	function func_url(array $params)
	{
		$controller_name = isset($params['controller']) ? $params['controller'] : null;
		unset($params['controller']);
		$action_name = isset($params['action']) ? $params['action'] : null;
		unset($params['action']);
		$namespace = isset($params['namespace']) ? $params['namespace'] : null;
		unset($params['namespace']);
		$module_name = isset($params['module']) ? $params['module'] : null;
		unset($params['module']);

		$args = array();
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$args = array_merge($args, $value);
				unset($params[$key]);
			}
		}
		$args = array_merge($args, $params);

		return $this->context->url($controller_name, $action_name, $args, $namespace, $module_name);
	}

	/**
	 * 提供对 QeePHP QWebControls 的支持
	 */
	function func_control(array $params)
	{
		$type = isset($params['type']) ? $params['type'] : 'textbox';
		unset($params['type']);
		$id = isset($params['id']) ? $params['id'] : null;
		unset($params['id']);
		$control = QUI::control($this->context, $type, $id, $params);
		$control->render();
	}

	/**
	 * 提供对 Q::getIni() 方法的支持
	 */
	function func_ini(array $params)
	{
		return $this->context->getIni($params['key']);
	}

    /**
     * 支持多语言的字符串截断修饰符
     */
    function mod_mb_truncate($string, $width = 90, $im = '', $charset = null)
    {
        if (empty($charset))
        {
            $charset = $this->context->getIni('i18n_response_charset');
            $charset = 'UTF-8';
        }

        return mb_strimwidth($string, 0, $width, $im, $charset);
    }
}
