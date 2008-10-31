<?php

/**
 * 指定模块的反射
 */
class QReflection_Module
{
    /**
     * 应用程序存储模块文件使用的默认目录布局
     */
    const MODULE_DIR_LAYOUT = '%ROOT_DIR%/modules/%MODULE_NAME%';

    /**
     * 应用程序默认模块的目录布局
     */
    const DEFAULT_MODULE_DIR_LAYOUT = '%ROOT_DIR%/app';

    /**
     * 该模块对应的应用程序反射
     *
     * @var QReflection_Application
     */
    protected $_reflection_app;

    /**
     * 模块所在目录
     *
     * @var string
     */
    protected $_reflection_module_dir;

    /**
     * 该模块对应的模块对象
     *
     * @var QApplication_Module
     */
    protected $_module;

    /**
     * 模块的配置信息
     *
     * @var array of settings
     */
    protected $_config;

    /**
     * 模块的所有控制器
     *
     * @var QColl
     */
    protected $_reflection_controllers;

    /**
     * 模块所有控制器的名字
     *
     * @var array of controller name
     */
    protected $_reflection_controllers_name;

    /**
     * 该模块的控制器生成器
     *
     * @var QGenerator_Controller
     */
    protected $_controller_generator;

    /**
     * 构造函数
     *
     * @param QReflection_Application $app
     * @param string $module_name
     */
    function __construct(QReflection_Application $app, $module_name, 
                         $default_module_dir_layout = null, $module_dir_layout = null)
    {
        if (empty($default_module_dir_layout))
        {
            $default_module_dir_layout = self::DEFAULT_MODULE_DIR_LAYOUT;
        }
        if (empty($module_dir_layout))
        {
            $module_dir_layout = self::MODULE_DIR_LAYOUT;
        }

        /**
         * 检查指定的模块是否存在
         *
         * 判断模块存在的标准：
         *  - 在 modules 目录中是否存在以该模块名命名的子目录
         */
        if (empty($module_name) || $module_name == QApplication_Module::DEFAULT_MODULE_NAME)
        {
            $search = array('%ROOT_DIR%');
            $replace = array($app->ROOT_DIR());
            $dir = $default_module_dir_layout;
            $module_name = QApplication_Module::DEFAULT_MODULE_NAME;
        }
        else
        {
            if ($app->hasModule($module_name))
            {
                $search = array('%ROOT_DIR%', '%MODULE_NAME%');
                $replace = array($app->ROOT_DIR(), $module_name);
                $dir = $module_dir_layout;
            }
            else
            {
                throw new QReflection_UndefinedModule($app, $module_name);
            }
        }

        $this->_reflection_app = $app;
        $this->_reflection_module_dir = str_replace($search, $replace, $dir);
        $this->_module = QApplication_Module::instance($module_name, $app->APPID());
        $this->_config = QApplication_Module::loadModuleConfig($module_name, $app->config());
    }

    /**
     * 返回该模块反射所属的应用程序反射
     *
     * @return QReflection_Application
     */
    function reflectionApp()
    {
        return $this->_reflection_app;
    }

    /**
     * 返回该模块的模块名
     *
     * @return string
     */
    function moduleName()
    {
        return $this->_module->moduleName();
    }

    /**
     * 返回该模亏所在目录
     *
     * @return string
     */
    function moduleDir()
    {
        return $this->_reflection_module_dir;
    }

    /**
     * 返回该模块对应的模块对象
     *
     * @retrun QApplication_Module
     */
    function module()
    {
        return $this->_module;
    }

    /**
     * 指示该模块是否是默认模块
     *
     * @return boolean
     */
    function isDefault()
    {
        return $this->reflection_module_name == QReflection_Module::DEFAULT_MODULE_NAME;
    }

    /**
     * 获得该模块所有控制器的名字
     *
     * @return array of controller name
     */
    function reflectionControllersName()
    {
        if (is_null($this->_reflection_controllers_name))
        {
            $dir = rtrim($this->_reflection_module_dir, '/\\') . '/controller';
            $this->_reflection_controllers_name = array();

            foreach ((array)glob($dir . '/*') as $path)
            {
                if (!is_dir($path))
                {
                    continue;
                }

                $namespace = basename($path);
                if ($namespace == '..' || $namespace == '.')
                {
                    continue;
                }

                $names = $this->_reflectionControllersNameInDir("{$dir}/{$namespace}");
                $this->_reflection_controllers_name[$namespace] = $names;
            }

            $names = $this->_reflectionControllersNameInDir($dir);
            $this->_reflection_controllers_name[] = $names;
            ksort($this->_reflection_controllers_name, SORT_STRING);
        }

        return $this->_reflection_controllers_name;
    }

    /**
     * 获得该模块所有控制器的反射
     *
     * @return array of QReflection_Controller
     */
    function reflectionControllers()
    {
        if (is_null($this->_reflection_controllers))
        {
            $this->_reflection_controllers = new QColl('QReflection_Controller');
            foreach ($this->reflectionControllersName() as $namespace => $names)
            {
                if (empty($namespace) || is_numeric($namespace))
                {
                    $namespace = null;
                }
                foreach ($names as $name)
                {
                    $reflection_controller = new QReflection_Controller($this, $name, $namespace);
                    $this->_reflection_controllers[$reflection_controller->UDI()] = $reflection_controller;
                }
            }
        }

        return $this->_reflection_controllers;
    }

    /**
     * 获得指定名字的控制器反射
     *
     * @param string $controller_name
     *
     * @return QReflection_Controller
     */
    function reflectionController($controller_name)
    {
        return $this->reflectionControllers[$controller_name];
    }

    /**
     * 检查是否存在指定的控制器
     *
     * @param string $controller_name
     * @param string $namespace
     *
     * @return boolean
     */
    function hasController($controller_name, $namespace = null)
    {
        $names = $this->reflectionControllersName();
        return in_array($controller_name, $names);
    }

    /**
     * 返回模块的设置
     *
     * @param string $option
     * @param mixed $default
     *
     * @return mixed
     */
    function getIni($option, $default = null)
    {
        if ($option == '/')
        {
            return $this->_config;
        }
        if (strpos($option, '/') === false)
        {
            if (array_key_exists($option, $this->_config))
            {
                return $this->_config[$option];
            }
            return $default;
        }
        $parts = explode('/', $option);
        $pos =& $this->_config;
        foreach ($parts as $part)
        {
            if (!isset($pos[$part]))
            {
                return $default;
            }
            $pos =& $pos[$part];
        }

        return $pos;
    }

    /**
     * 修改当前模块指定配置的内容
     *
     * @param string $option
     * @param mixed $data
     */
    function setIni($option, $data = null)
    {
        if (is_array($option))
        {
            foreach ($option as $key => $value)
            {
                $this->setIni($key, $value);
            }
            return;
        }

        if (! is_array($data))
        {
            if (strpos($option, '/') === false)
            {
                $this->_config[$option] = $data;
                return;
            }

            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos = & $this->_config;
            for ($i = 0; $i <= $max; $i ++)
            {
                $part = $parts[$i];
                if ($i < $max)
                {
                    if (! isset($pos[$part]))
                    {
                        $pos[$part] = array();
                    }
                    $pos = & $pos[$part];
                }
                else
                {
                    $pos[$part] = $data;
                }
            }
        }
        else
        {
            foreach ($data as $key => $value)
            {
                $this->setIni($option . '/' . $key, $value);
            }
        }
    }

    /**
     * 删除指定的配置
     *
     * $option 参数的用法同 QContext::getIni() 和 QContext::setIni()。
     *
     * 注意：unsetIni() 只影响当前模块的设置。
     *
     * @param mixed $option
     *   要删除的设置项名称
     */
    function unsetIni($option)
    {
        if (strpos($option, '/') === false)
        {
            unset($this->_config[$option]);
        }
        else
        {
            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos = & $this->_config;
            for ($i = 0; $i <= $max; $i ++)
            {
                $part = $parts[$i];
                if ($i < $max)
                {
                    if (! isset($pos[$part]))
                    {
                        $pos[$part] = array();
                    }
                    $pos =& $pos[$part];
                }
                else
                {
                    unset($pos[$part]);
                }
            }
        }
    }

    /**
     * 生成属于该模块的控制器，返回包含创建信息的数组
     *
     * @param string $controller_name
     * @param string $namespace
     *
     * @return QGenerator_Controller
     */
    function generateController($controller_name, $namespace = null)
    {
        if (strpos($controller_name, '::') !== false)
        {
            list($namespace, $controller_name) = explode('::', $controller_name);
        }
        return $this->controllerGenerator()->generate($controller_name, $namespace);
    }

    /**
     * 返回对应该模块的控制器生成器
     *
     * @return QGenerator_Controller
     */
    function controllerGenerator()
    {
        if (is_null($this->_controller_generator))
        {
            $this->_controller_generator = new QGenerator_Controller($this);
        }
        return $this->_controller_generator;
    }

    /**
     * 遍历指定目录下的所有控制器文件
     *
     * @param string $dir
     * 
     * @return array
     */
    protected function _reflectionControllersNameInDir($dir)
    {
        $names = array();
        foreach ((array)glob($dir . '/*_controller.php') as $filename)
        {
            if (!is_file($filename))
            {
                continue;
            }
            $names[] = substr(basename($filename), 0, -15);
        }
        return $names;
    }

}


