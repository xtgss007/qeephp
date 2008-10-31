<?php

class QReflection_Controller
{
    /**
     * 控制器名称
     *
     * @var string
     */
    protected $_controller_name;

    /**
     * 控制器名字空间
     *
     * @var string
     */
    protected $_namespace;

    /**
     * 控制器的类名称
     *
     * @var string
     */
    protected $_controller_class_name;

    /**
     * 控制器文件完整路径
     *
     * @var string
     */
    protected $_controller_file_path;

    /**
     * 控制器所属模块的反射
     *
     * @var QReflection_Module
     */
    protected $_reflection_module;
    /**
     * 构造函数
     *
     * @param QReflection_Module $module
     * @param string $controller_name
     * @param string $namespace
     */
    function __construct(QReflection_Module $module, $controller_name, $namespace = null)
    {
        $this->_reflection_module = $module;
        $this->_controller_name = $controller_name;
        $this->_namespace = $namespace;
        if ($namespace)
        {
            $this->_controller_file_path = rtrim($module->moduleDir(), '/\\') . "/controller/{$namespace}/" . $controller_name . '_controller.php';
        }
        else
        {
            $this->_controller_file_path = rtrim($module->moduleDir(), '/\\') . '/controller/' . $controller_name . '_controller.php';
        }
    }

    /**
     * 返回该控制器所属模块的反射
     *
     * @return QReflection_Module
     */
    function reflectionModule()
    {
        return $this->_reflection_module;
    }

    /**
     * 返回该控制器所属应用的反射
     *
     * @return QReflection_Application
     */
    function reflectionApp()
    {
        return $this->_reflection_module->reflectionApp();
    }

    /**
     * 返回控制器名称
     *
     * @return string
     */
    function controllerName()
    {
        return $this->_controller_name;
    }

    /**
     * 返回控制器文件的完整路径
     *
     * @return string
     */
    function filePath()
    {
        return $this->_controller_file_path;
    }

    /**
     * 返回控制器所属的名字空间
     *
     * @return string
     */
    function namespace()
    {
        return $this->_namespace;
    }

    /**
     * 返回控制器所述模块的名字
     *
     * @return string
     */
    function moduleName()
    {
        return $this->reflectionModule()->moduleName();
    }

    /**
     * 返回控制器的类名称
     *
     * @return string
     */
    function className()
    {
        if (is_null($this->_controller_class_name))
        {
            $path = $this->filePath();
            if (!file_exists($path))
            {
                throw new Q_FileNotFoundException($path);
            }
            if (!is_readable($path))
            {
                throw new Q_FileNotReadableException($path);
            }
            $content = @file_get_contents($this->filePath());
            if ($content === false)
            {
                throw new Q_FileNotReadableException($path);
            }

            $regx = '/\n[\r]?[ \t]*class[ \t]+([a-z][a-z0-9_]+)[ \t]+extends.+/i';
            $m = array();
            preg_match($regx, $content, $m);

            if (empty($m[1]))
            {
                $expected_class = 'Controller_' . ucfirst($this->_controller_name);
                throw new Q_ClassNotDefinedException($expected_class, $path);
            }

            $this->_controller_class_name = $m[1];
        }

        return $this->_controller_class_name;
    }

    /**
     * 返回控制器的 UDI 名称
     *
     * @return string
     */
    function UDI()
    {
        if ($this->_namespace)
        {
            $name = $this->_namespace . '::' . $this->_controller_name;
        }
        else
        {
            $name = $this->_controller_name;
        }

        if ($this->_reflection_module->moduleName() != QApplication_Module::DEFAULT_MODULE_NAME)
        {
            $name .= '@' . $this->_reflection_module->moduleName();
        }
        return $name;
    }
}


