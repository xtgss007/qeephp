<?php
// $Id$

/**
 * @file
 * WebSetup 应用程序对象
 *
 * @ingroup websetup
 *
 * @{
 */

/**
 * QeePHP Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://qeephp.org/license/new-bsd
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to supprt@qeeyuan.com so we can send you a copy immediately.
 *
 * Copyright (c) 2006-2008 QeeYuan Technologies Ltd. Inc. (http://www.qeeyuan.com)
 */

/**
 * 应用程序对象
 */
class WebSetupApp extends QApplication_Abstract
{
    public $managed_app_config;
    public $lang = 'zh_CN';

	/**
	 * 构造函数
     *
     * @param array $app_config
     * @param array $managed_app_config
	 */
	protected function __construct($app_config, $managed_app_config)
	{
	    parent::__construct($app_config);
	    $this->managed_app_config = $managed_app_config;

        Q::import($app_config['ROOT_DIR'] . '/app/model');
        Q::import($app_config['ROOT_DIR'] . '/app');
        Q::import(dirname(Q_DIR) . '/extended');
        require $app_config['ROOT_DIR'] . '/app/controller/abstract.php';
	}

	/**
	 * 获得应用程序对象的唯一实例
     *
	 * @return WebSetupApp
	 */
	static function instance($app_config = null, $managed_app_config = null)
	{
		static $app;

		if (is_null($app))
        {
			$app = new WebSetupApp($app_config, $managed_app_config);
		}
		return $app;
	}

    /**
     * 默认的 on_access_denied 事件处理函数
     */
    function onAccessDenied(QContext $context)
    {
        echo 'access denied';
        exit();
    }

    /**
     * 默认的 on_action_not_found 事件处理函数
     */
    function onActionNotFound(QContext $context)
    {
        echo 'action not found';
        exit();
    }

    /**
     * 默认的异常处理
     */
    function exceptionHandler(Exception $ex)
    {
        QException::dump($ex);
    }
}

/**
 * WebSetupAppException 封装应用程序运行过程中产生的异常
 *
 * @package app
 */
class WebSetupAppException extends QException
{

}


/**
 * @}
 */

