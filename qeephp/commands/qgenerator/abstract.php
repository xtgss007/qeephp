<?php
// $Id$

/**
 * @file
 * 定义 QGenerator_Abstract 类
 *
 * @ingroup generator
 *
 * @{
 */

/**
 * QGenerator_Abstract 是所有生成器的基础类
 */
abstract class QGenerator_Abstract
{
    /**
     * 应用程序根目录
     *
     * @var string
     */
    public $root_dir;

    /**
     * 应用程序的配置
     *
     * @var array
     */
    protected $_config;

    /**
     * 构造函数
     */
    function __construct($root_dir = ROOT_DIR)
    {
        $this->root_dir = $root_dir;
        $this->_config = load_module_config_without_cache(null);
        $this->_config['db_meta_cached'] = false;
        $this->_config['log_enabled'] = false;
        Q::setIni($this->_config);
    }

    /**
     * 继承类必须覆盖此方法
     *
     * @param array $opts
     */
    abstract function execute(array $opts);

    /**
     * 拆分一个名称，分解为 $name, $namespace 和 $module
     *
     * @return array
     */
    protected function _splitName($name)
    {
        $name = trim($name);
        if (strpos($name, '::') !== false)
        {
            list ($namespace, $name) = explode('::', $name);
        }
        else
        {
            $namespace = null;
        }

        if (strpos($name, '@') !== false)
        {
            list ($name, $module) = explode('@', $name);
        }
        else
        {
            $module = null;
        }

        return array( $name, strtolower($namespace), strtolower($module) );
    }

    /**
     * 格式化类名称，确保每个词首字母都是大写
     *
     * @param string $class_name
     *
     * @return string
     */
    protected function _formatClassName($class_name)
    {
        $arr = explode('_', $class_name);
        foreach ($arr as $offset => $name)
        {
            $arr[$offset] = ucfirst($name);
        }
        return implode('_', $arr);
    }

    /**
     * 获得类定义文件的完整路径
     *
     * @param string $dir
     * @param string $class_name
     * @param string $suffix
     * @param string $prefix
     *
     * @return string
     */
    protected function _getClassFilePath($dir, $class_name, $suffix = '.php', $prefix = '')
    {
        $arr = explode('_', strtolower($class_name));
        $c = count($arr);
        for ($i = 1; $i < $c; $i++)
        {
            $j = $i - 1;
            $dir .= "/{$arr[$j]}";
        }
        $c--;
        return "{$dir}/{$prefix}{$arr[$c]}{$suffix}";
    }

    /**
     * 创建指定文件
     *
     * @param string $path
     * @param string $content
     */
    protected function _createFile($path, $content)
    {
        $this->_createDirs(dirname($path));
        if (file_put_contents($path, $content))
        {
            $path = realpath($path);
            echo "Create file '{$path}' successed.\n";
            return true;
        }
        return false;
    }

    /**
     * 建立需要的目录路径
     *
     * @param string $dir
     */
    protected function _createDirs($dir)
    {
        $dir = str_replace('/\\', DS, $dir);
        if (! file_exists($dir))
        {
            Helper_FileSys::mkdirs($dir);
            $dir = realpath($dir);
            echo "Create directory '{$dir}' successed.\n";
        }
    }

    /**
     * 获得符合规范的类名称
     *
     * @param string $name
     * @param string $namespace
     * @param string $module
     *
     * @return string
     */
    protected function _stdClassName($name, $namespace = null, $module = null)
    {
        $name = explode('_', $name);
        $arr = array();
        foreach ($name as $n)
        {
            $arr[] = ucfirst($n);
        }
        $name = implode('_', $arr);
        $basename = $name;

        if ($namespace)
        {
            $name = "{$namespace}::{$name}";
        }
        if ($module)
        {
            $name .= "@{$module}";
        }
        return array( $name, $basename );
    }

    /**
     * 载入模板，返回解析结果
     *
     * @param string $tpl
     * @param array $viewdata
     *
     * @return string
     */
    protected function _parseTemplate($__template, $viewdata)
    {
        ob_start();
        self::_parseTemplateStatic($__template, $viewdata);
        return ob_get_clean();
    }

    /**
     * 载入模板，返回解析结果（静态方法）
     *
     * @param string $tpl
     * @param array $viewdata
     *
     * @static
     */
    protected static function _parseTemplateStatic($__template, $viewdata)
    {
        $__template = dirname(__FILE__) . '/templates/template_' . $__template . '.php';
        extract($viewdata);
        return include $__template;
    }
}
