<?php

class QGenerator_Controller extends QGenerator_Abstract
{
    /**
     * 该生成器所述的应用程序模块反射
     *
     * @var QReflection_Module
     */
    protected $_reflection_module;

    /**
     * 构造函数
     *
     * @param QReflection_Module $module
     */
    function __construct(QReflection_Module $module)
    {
        $this->_reflection_module = $module;
    }

    /**
     * 生成指定名称的控制器
     *
     * @param string $controller_name
     * @param string $namespace
     *
     * @return QGenerator_Controller
     */
    function generate($controller_name, $namespace)
    {
        if ($namespace)
        {
            $class_name = "Controller_{$namespace}_{$controller_name}";
        }
        else
        {
            $class_name = "Controller_{$controller_name}";
        }
        $class_name = $this->_normalizeClassName($class_name);

        $path = $this->_classFilePath($this->_reflection_module->moduleDir(), $class_name, '_controller.php');

        $this->_logClean();
        if (file_exists($path))
        {
            throw new Q_ClassFileExistsException($class_name, $path);
        }

        // 创建控制器文件
        $data = array(
            'class_name' => $class_name,
            'namespace'  => $namespace,
        );

        $content = $this->_parseTemplate('controller', $data);
        $this->_createFile($path, $content);

        // 建立视图目录
        $dir = rtrim($this->_reflection_module->moduleDir(), '/\\') . '/view';
        if ($namespace)
        {
            $dir .= "/{$namespace}";
        }

        $this->_createDirs($dir . '/_layouts');
        $this->_createDirs($dir . "/{$controller_name}");
        return $this;
    }

}


