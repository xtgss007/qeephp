<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QGenerator_Controller 类
 *
 * @package generator
 * @version $Id: controller.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QGenerator_Controller 创建控制器代码
 *
 * @package generator
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
        if (empty($controller_name)) {
            return false;
        }

        $arr = explode('::', $controller_name);
        if (count($arr) == 2) {
            list($namespace, $controller_name) = $arr;
            $full_name = "{$namespace}::{$controller_name}";
        } else {
            $namespace = null;
            $full_name = $controller_name;
        }

        $class_name = 'Controller_' . ucfirst($controller_name);
        $filename = strtolower($controller_name) . '_controller.php';
        Q::loadVendor('filesys');

        if ($namespace) {
            $path = ROOT_DIR . '/app/controller/' . $namespace;
        } else {
            $path = ROOT_DIR . '/app/controller';
        }
        $this->createDir($path);
        $path .= '/' . $filename;

        if (Q::isReadable($path)) {
            echo "Class '{$full_name}' declare file '{$path}' exists.\n";
            return 0;
        }

        $viewdata = array('class_name' => $class_name, 'namespace' => $namespace);
        $content = $this->parseTemplate('controller', $viewdata);
        if ($content == -1 || empty($content) || !file_put_contents($path, $content)) {
            return false;
        }

        echo "Create file '{$path}' successed.\n";

        if ($namespace) {
            $path = ROOT_DIR . '/app/view/' . $namespace;
        } else {
            $path = ROOT_DIR . '/app/view';
        }
        $this->createDir($path . '/_layouts');
        $this->createDir($path . '/' . $controller_name);

        return true;
    }
}
