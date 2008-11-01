<?php

/**
 * 应用程序的公共控制器基础类
 *
 * 可以在这个类中添加方法来完成应用程序控制器共享的功能。
 */
abstract class AppController_Abstract extends QController_Abstract
{
    protected $_help_text;

    /**
     * 被管理的应用
     *
     * @var QReflection_Application
     */
    protected $_managed_app;

    /**
     * 构造函数
     */
    function __construct(QApplication_Abstract $app, QContext $context)
    {
        $this->app = $app;
        $this->context = $context;
        $this->_managed_app = new QReflection_Application($this->app()->managed_app_config);
    }

    /**
     * 返回最后一次出错的错误信息
     *
     * @return string
     */
    protected function _getLastError()
    {
        if (function_exists('error_get_last'))
        {
            $error = error_get_last();
            if (!empty($error['message']))
            {
                $error = strip_tags($error['message']);
            }
        }
        else
        {
            $error = '';
        }
        return $error;
    }

    protected function _after_execute($ret)
    {
        if (is_array($this->view))
        {
            $g = array();
            $g['appini']             = $this->context->getIni('appini');
            $g['flash_message']      = $this->app->getFlashMessage();
            $g['flash_message_type'] = $this->app->getFlashMessageType();
            $g['help_text']          = $this->_help_text;
            $this->view['g'] = $g;
         }
         return $ret;
    }
}

