<?php

/**
 * 应用程序的公共控制器基础类
 *
 * 可以在这个类中添加方法来完成应用程序控制器共享的功能。
 */
abstract class AppController_Abstract extends QController_Abstract
{

	/**
	 * 在动作方法执行完毕后调用
	 *
	 * @param mixed $response
	 *
	 * @return mixed
	 */
	protected function _after_execute($response)
	{
		if (is_array($this->view))
		{
			$this->view['flash_message'] = $this->app->getFlashMessage();
		}
		return $response;
	}
}
