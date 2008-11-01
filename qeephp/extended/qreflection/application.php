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
     * @var QReflection_Module
     */
    protected $_reflection_default_module;

    /**
     * 应用所有模块的反射
     *
     * @var QColl
     */
    protected $_reflection_modules;

    /**
     * 应用所有模块的名字
     *
     * @var array of module name
     */
    protected $_reflection_modules_name;

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
        QApplication_Abstract::setAppConfig($this->_appid, $this->_app_config);
    }

    /**
     * 返回应用程序 ID
     *
     * @return string
     */
    function APPID()
    {
        return $this->_appid;
    }

    /**
     * 返回应用的 ROOT_DIR
     *
     * @return string
     */
    function ROOT_DIR()
    {
        return $this->configItem('ROOT_DIR');
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
     * 返回该应用所有模块的名字
     *
     * @return array of module name
     */
    function reflectionModulesName()
    {
        if (is_null($this->_reflection_modules_name))
        {
            $this->_reflection_modules_name = array();
            $module_dir_layout = $this->configItem('MODULE_DIR_LAYOUT');
            if (empty($module_dir_layout))
            {
                $module_dir_layout = QReflection_Module::MODULE_DIR_LAYOUT;
            }

            $search = array('%ROOT_DIR%', '%MODULE_NAME%');
            $replace = array($this->ROOT_DIR(), '');
            $dir = rtrim(str_replace($search, $replace, $module_dir_layout), '/\\');
            $this->_modules_name = array();
            foreach (glob($dir . '/*') as $file)
            {
                if (!is_dir($file))
                {
                    continue;
                }

                $basename = basename($file);
                if ($basename == '.' || $basename == '..')
                {
                    continue;
                }

                $this->_reflection_modules_name[] = $basename;
            }
            sort($this->_reflection_modules_name, SORT_STRING);
        }

        return $this->_reflection_modules_name;
    }

    /**
     * 获得应用所有模块的反射
     *
     * @return array of QReflection_Module
     */
    function reflectionModules()
    {
        if (is_null($this->_reflection_modules))
        {
            $this->_reflection_modules = new QColl('QReflection_Module');
            $this->_reflection_modules[QApplication_Module::DEFAULT_MODULE_NAME] = new QReflection_Module($this, null);
            foreach ($this->reflectionModulesName() as $module_name)
            {
                $this->_reflection_modules[$module_name] = new QReflection_Module($this, $module_name);
            }
        }

        return $this->_reflection_modules;
    }

    /**
     * 获得指定名称模块的反射
     *
     * @param string $module_name
     *
     * @return QReflection_Module
     */
    function reflectionModule($module_name)
    {
        if (is_null($this->_reflection_modules))
        {
            $this->reflectionModules();
        }
        if (empty($module_name))
        {
            $module_name = QApplication_Module::DEFAULT_MODULE_NAME;
        }
        return $this->_reflection_modules[$module_name];
    }

    /**
     * 返回应用程序的默认模块的反射
     *
     * @return QReflection_Module
     */
    function reflectionDefaultModule()
    {
        $all_reflection_modules = $this->reflectionModules();
        return $all_reflection_modules[QApplication_Module::DEFAULT_MODULE_NAME];
    }

    /**
     * 检查是否存在指定的模块
     *
     * @param string $module_name
     *
     * @return boolean
     */
    function hasModule($module_name)
    {
        $modules_name = $this->reflectionModulesName();
        return in_array($module_name, $modules_name);
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
        return $this->reflectionDefaultModule()->getIni($path);
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

    /**
     * 为应用程序生成一个控制器的代码
     *
     * @param string $controller_name
     *
     * @return QGenerator_Controller
     */
    function generateController($controller_name)
    {
        $module = null;
        if (strpos($controller_name, '@') !== false)
        {
            list($controller_name, $module) = explode('@', $controller_name);
        }
        return $this->reflectionModule($module)->generateController($controller_name);
    }

    /**
     * 为应用程序生成一个模型的代码
     *
     * @param string $model_name
     * @param string $table_name
     *
     * @return QGenerator_Model
     */
    function generateModel($model_name, $table_name)
    {
        $module = null;
        if (strpos($model_name, '@') !== false)
        {
            list($model_name, $module) = explode('@', $model_name);
        }
        return $this->reflectionModule($module)->generateModel($model_name, $table_name);
    }
}

