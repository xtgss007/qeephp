<?php

/**
 * 默认控制器
 *
 * @package app
 */
class Controller_Default extends AppController_Abstract
{
    /**
     * 返回被管理应用程序
     *
     * @return QReflection_Application
     */
    protected function _managedApp()
    {
        static $app;
        if (is_null($app))
        {
            $app = new QReflection_Application($this->app()->managed_app_config);
        }
        return $app;
    }

    /**
     * 确认应用程序基本信息
     */
    function actionIndex()
    {
        $app = $this->_managedApp();
        $this->view['app_config'] = $app->config();
        $this->view['all_ini'] = $app->getIni('/');
        $this->view['ini_descriptions'] = $app->getIniDescriptions($this->app()->lang);

        $this->_help_text = '在开始使用 WebSetup 之前，请务必仔细核对本页列出的应用程序信息，' .
                            "例如应用程序所在路径等等信息。\n\n" .
                            '只有当这些信息正确无误时，WebSetup 才能够正常工作。';
    }

    /**
     * 修改数据库连接设置
     */
    function actionSetDSN()
    {
        $app = $this->_managedApp();
        $dsn = (array)$app->getIni('db_dsn_pool');
        $default_dsn = array
        (
            'driver' => 'mysql',
            'host' => 'localhost',
            'login' => 'username',
            'password' => 'password',
            'database' => strtolower($app->appid()) . '_db',
            'charset' => 'utf8',
            'prefix' => '',
        );

        unset($dsn['default']);
        $data = array();
        foreach (array('devel', 'test', 'deploy') as $section)
        {
            if (!isset($dsn[$section]) || !is_array($dsn[$section]))
            {
                $data[$section] = $default_dsn;
                if ($section != 'deploy')
                {
                    $dsn[$section]['database'] = strtolower($app->appid()) . '_' . $section . '_db';
                }
            }
            else
            {
                $data[$section] = $dsn[$section];
            }
        }

        $this->view['db_dsn_pool'] = $data;
        $this->_help_text = '在这里可以分别设置不同运行模式使用的数据库连接信息。';
    }

    function actionUpdateDSN()
    {
        $dsn = array();
        foreach ($_POST as $key => $value)
        {
            $arr = explode('_', $key);
            if (count($arr) == 3 && $arr[1] == 'dsn')
            {
                $dsn[$arr[0]][$arr[2]] = $value;
            }
        }

        $filename = $this->_managedApp()->configItem('ROOT_DIR') . '/config/database.yaml';
        file_put_contents($filename, Helper_YAML::dump($dsn), LOCK_EX);

        $this->app->setFlashMessage('成功更新数据库配置');
        return new QView_Redirect($this->context->url(null, 'setdsn') . "#tab_{$section}");
    }

    /**
     * 修改运行环境设置
     */
    function actionSetENV()
    {
        $this->_help_text = '运行环境设置可以控制应用程序的运行行为。在这里可以修改大部分的运行环境设置。';
    }

    /**
     *  感谢
     */
    function actionThanks()
    {
        $this->_help_text = '没有来自社区的支持，就不会有今天的 QeePHP。';
    }
}
