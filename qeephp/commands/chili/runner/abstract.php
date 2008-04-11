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
 * 定义 Chili_Runner_Abstract 类
 *
 * @package chili
 * @version $Id$
 */

// {{ include
Q::loadClass('Chili_Exception');
// }}

/**
 * Chili_Runner_Abstract 是应用程序构造器的抽象类
 *
 * @package chili
 */
abstract class Chili_Runner_Abstract
{
    /**
     * 复制文件时要动态替换的内容
     *
     * @var array
     */
    private $content_search = array('%QEEPHP_INST_DIR%', '%APP_NAME%');
    private $content_replace = array();

    /**
     * 运行构造器
     */
    abstract function run();

    /**
     * 生成应用程序骨架
     *
     * @param string $appname
     * @param string $dir
     * @param string $theme
     */
    protected function buildApp($appname, $dir, $theme)
    {
        $skeleton_dir = dirname(dirname(dirname(dirname(__FILE__)))) . DS . 'skeleton';
        $theme_dir = $skeleton_dir . DS . $theme;
        if (!file_exists($theme_dir . DS . 'readme.txt')) {
            throw new Chili_Exception(__('Specified theme "%s" notexistent.', $theme));
        }

        $appname = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $appname));
        if (empty($appname)) {
            throw new Chili_Exception(__('Invalid appname "%s".', $appname));
        }

        $test_dir = realpath($dir);
        if (empty($test_dir)) {
            throw new Chili_Exception(__('Invalid dir "%s".', $dir));
        }

        clearstatcache();
        $dir = rtrim(realpath($dir), '/\\');
        $appdir = $dir . DS . $appname;
        if (file_exists($appdir)) {
            throw new Chili_Exception(__('Application dir "%s" already exists.', $appdir));
        }
        if (!mkdir($appdir)) {
            throw new Chili_Exception(__('Creation application dir "%s" failed.', $appdir));
        }

        $this->content_replace = array(dirname(Q_DIR), $appname);

        echo "Building application {$appname}......\n\n";
        $this->copydir($theme_dir, $appdir);
        echo "\n";
    }

    /**
     * 拷贝目录
     *
     * @param string $source
     * @param string $target
     */
    protected function copydir($source, $target)
    {
        $source .= DIRECTORY_SEPARATOR;
        $target .= DIRECTORY_SEPARATOR;
        $h = opendir($source);
        while (($file = readdir($h)) !== false) {
            if (substr($file, 0, 1) == '.') { continue; }
            $path = $source . $file;
            echo '  create ', $target . $file;
            echo "\n";
            if (is_dir($path)) {
                mkdir($target . $file);
                $this->copydir($path, $target . $file);
            } else {
                $content = file_get_contents($path);
                $content = str_replace($this->content_search, $this->content_replace, $content);
                file_put_contents($target . $file, $content);
                unset($content);
            }
        }
        closedir($h);
    }
}
