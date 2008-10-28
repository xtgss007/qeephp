<?php

class QReflection_Application
{
    /**
     * 应用程序配置
     *
     * @var array
     */
    protected $_app_config;

    /**
     * 应用程序ID
     *
     * @var string
     */
    protected $_appid;

    /**
     * 应用程序的默认模块
     *
     * @var QApplication_Module
     */
    protected $_default_module;

    /**
     * 应用程序的根上下文对象
     *
     * @var QContext
     */
    protected $_root_context;

    /**
     * 已经载入的应用程序设置描述信息
     *
     * @var array
     */
    static protected $_ini_descriptions = array();

    /**
     * 构造函数
     *
     * @param array $app_config
     */
    function __construct($app_config)
    {
        $this->_app_config = $app_config;
        $this->_appid = $app_config['APPID'];
        QApplication_Abstract::setAppConfig($this->_appid, $app_config);
    }

    /**
     * 返回应用程序 ID
     *
     * @return string
     */
    function appid()
    {
        return $this->_appid;
    }

    /**
     * 返回应用程序配置
     *
     * @return array
     */
    function config()
    {
        return $this->_app_config;
    }

    /**
     * 返回应用程序配置中指定项目的值
     *
     * @param string $item
     *
     * @return mixed
     */
    function configItem($item)
    {
        return isset($this->_app_config[$item]) ? $this->_app_config[$item] : null;
    }

    /**
     * 返回应用程序的默认模块
     *
     * @return QApplication_Module
     */
    function defaultModule()
    {
        if (is_null($this->_default_module))
        {
            $this->_default_module = QApplication_Module::instance(null, $this->_appid);
        }
        return $this->_default_module;
    }

    /**
     * 返回应用程序的根上下文对象
     *
     * @return QContext
     */
    function rootContext()
    {
        if (is_null($this->_root_context))
        {
            $this->_root_context = QContext::instance(null, $this->_appid);
        }
        return $this->_root_context;
    }

    /**
     * 取得应用程序的设置信息
     *
     * @param string $path
     *
     * @return mixed
     */
    function getIni($path)
    {
        return $this->rootContext()->getIni($path);
    }

    /**
     * 获取指定语言的应用程序设置描述
     *
     * @param string $lang
     *
     * @return array
     */
    function getIniDescriptions($lang)
    {
        $lang = preg_replace('/[^a-z_]/i', '', $lang);
        if (!isset(self::$_ini_descriptions[$lang]))
        {
            $filename = dirname(__FILE__) . '/_descriptions/' . $lang . '.yaml';
            self::$_ini_descriptions[$lang] = Helper_Yaml::load($filename);
        }

        return self::$_ini_descriptions[$lang];
    }

}

