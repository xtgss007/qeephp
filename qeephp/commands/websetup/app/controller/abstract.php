<?php

/**
 * 应用程序的公共控制器基础类
 *
 * 可以在这个类中添加方法来完成应用程序控制器共享的功能。
 */
abstract class AppController_Abstract extends QController_Abstract
{
    protected $_help_text;

    protected function _after_execute($ret)
    {
        if (is_array($this->view))
        {
            $g = array();
            $g['appini']            = $this->context->getIni('appini');
            $g['flash_message']     = $this->app->getFlashMessage();
            $g['help_text']         = $this->_help_text;
            $this->view['g'] = $g;
         }
         return $ret;
    }
}
