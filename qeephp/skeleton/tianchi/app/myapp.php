<?php

/**
 * 应用程序对象
 *
 * @package app
 */
class MyApp extends QApplication_Abstract
{
    /**
     * 构造函数
     *
     * @param array $app_config
     */
    protected function __construct(array $app_config)
	{
        parent::__construct($app_config);
        $root_dir = $app_config['ROOT_DIR'];
        Q::import($root_dir . '/app/model');
        Q::import($root_dir . '/app');
        require_once $root_dir . '/app/controller/abstract.php';
	}

	/**
	 * 获得应用程序对象的唯一实例
     *
	 * @return MyApp
	 */
    static function instance(array $app_config = null)
    {
        static $app;

        if (is_null($app))
        {
            $app = new MyApp($app_config);
        }
        return $app;
    }

	/**
	 * 默认的 on_access_denied 事件处理函数
	 */
	function onAccessDenied(QContext $context)
	{
		require $this->ROOT_DIR() . '/public/403.php';
	}

	/**
	 * 默认的 on_action_not_found 事件处理函数
	 */
	function onActionNotFound(QContext $context)
	{
		require $this->ROOT_DIR() . '/public/404.php';
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
 * MyAppException 封装应用程序运行过程中产生的异常
 *
 * @package app
 */
class MyAppException extends QException
{

}

