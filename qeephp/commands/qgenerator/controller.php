<?php
// $Id$

/**
 * @file
 * 定义 QGenerator_Controller 类
 *
 * @ingroup generator
 *
 * @{
 */

/**
 * QGenerator_Controller 创建控制器代码
 */
class QGenerator_Controller extends QGenerator_Abstract
{

    /**
     * 执行代码生成器
     *
     * @param array $opts
     *
     * @return mixed
     */
    function execute(array $opts)
    {
        $controller_name = reset($opts);
        if (empty($controller_name))
        {
            return false;
        }

        // 确定控制器的类名称
        list ($controller_name, $namespace, $module) = $this->_splitName($controller_name);

        if ($module)
        {
            $dir = "{$this->root_dir}/modules/{$module}";
        }
        else
        {
            $dir = "{$this->root_dir}/app";
        }

        if ($namespace)
        {
            $class_name = "Controller_{$namespace}_{$controller_name}";
        }
        else
        {
            $class_name = "Controller_{$controller_name}";
        }

        $class_name = $this->_formatClassName($class_name);
        $path = $this->_getClassFilePath($dir, $class_name, '_controller.php');

        if (file_exists($path))
        {
            echo "Class '{$class_name}' declare file '{$path}' exists.\n";
            return 0;
        }

        // 创建控制器文件
        $viewdata = array
        (
            'class_name' => $class_name,
            'namespace'  => $namespace,
            'module'     => $module
        );
        $content = $this->_parseTemplate('controller', $viewdata);
        $ret = $this->_createFile($path, $content);

        // 建立视图目录
        if ($module)
        {
            $dir = "{$this->root_dir}/modules/{$module}/view";
        }
        else
        {
            $dir = "{$this->root_dir}/app/view";
        }
        if ($namespace)
        {
            $dir .= "/{$namespace}";
        }

        $this->_createDirs($dir . '/_layouts');
        $this->_createDirs($dir . "/{$controller_name}");

        return $ret;
    }
}
