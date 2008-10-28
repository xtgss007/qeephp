<?php
// $Id$

/**
 * @file
 * 定义 YAML 插件
 *
 * @ingroup helper
 *
 * @{
 */

// {{{ includes
require_once Q_DIR . '/helper/_spyc.php';
// }}}

/**
 * Helper_YAML 提供 yaml 文件的解析服务
 */
abstract class Helper_YAML
{
    /**
     * 载入缓存的 YAML 解析结果，如果缓存失效，则重新解析并生成缓存
     *
     * @param string $filename
     *   要解析的 yaml 文件名
     * @param array $replace
     *   对于 YAML 内容要进行自动替换的字符串对
     * @param string $cache_backend
     *   要使用的缓存后端
     *
     * @return array
     *   解析结果
     */
    static function loadCached($filename, array $replace = null, $cache_backend = null)
    {
        static $cache_obj = null;

        if (!is_file($filename))
        {
            throw new QException_FileNotFound($filename);
        }

        $policy = array('lifetime' => 86400, 'serialize' => true);
        $mtime = filemtime($filename);
        $id = 'yaml_cache_' . md5($filename);

        if (is_null($cache_backend))
        {
            if (is_null($cache_obj))
            {
                $cache_obj = Q::getSingleton(Q::getIni('runtime_cache_backend'));
            }
            $cache = $cache_obj;
        }
        else
        {
            $cache = self::getSingleton($cache_backend);
        }

        /* @var $cache QCache_File */
        $data = $cache->get($id, $policy);
        if (!isset($data['yaml']) || empty($data['mtime']) || $data['mtime'] < $mtime)
        {
            // 缓存失效
            $data = array(
                'mtime' => $mtime,
                'yaml' => self::load($filename, $replace),
            );
            $cache->set($id, $data, $policy);
        }

        return $data['yaml'];
    }

    /**
     * 载入 YAML 文件，返回分析结果
     *
     * 关于 YAML 的详细信息,请参考 http://www.yaml.org 。
     *
     * @code
     * $data = Helper_YAML::load('my_data.yaml.php');
     * @endcode
     *
     * 如果指定了 $replace 参数，解析过程中会使用 $replace 指定的内容去替换 YAML 文件的内容。
     *
     * @code
     * $replace = array
     * (
     *     '%TITLE%' => 'application title',
     *     '%ADMIN_USER%' => 'administrator',
     * );
     * $data = Helper_YAML::load('my_data.yaml.php');
     * // my_data.yaml.php 中包含的 %TITLE% 和 %ADMIN_USER% 字符串会被替换为指定的内容
     * @endcode
     *
     * <strong>注意：</strong>
     * 为了安全起见，不要将 yaml 文件置于浏览器能够访问的目录中。
     * 或者将 YAML 文件的扩展名设置为 .yaml.php，并且在每一个 YAML 文件开头添加“exit()”。
     *
     * 例如：
     * @code
     * # <?php exit(); ?>
     *
     * invoice: 34843
     * date   : 2001-01-23
     * bill-to: &id001
     * ......
     * @endcode
     *
     * 这样可以确保即便浏览器直接访问该 .yaml.php 文件，也无法看到内容。
     *
     * 书写 yaml 文件时，不要插入多余的空行。完整的书写规范请参考 http://www.yaml.org 。
     *
     * @param string $filename
     *   要解析的 yaml 文件名
     * @param array $replace
     *   对于 YAML 内容要进行自动替换的字符串对
     *
     * @return array
     *   解析结果
     */
    static function load($filename, array $replace = null)
    {
        return self::parse(file_get_contents($filename), $replace);
    }

    /**
     * 分析 YAML 字符串，返回分析结果
     *
     * @param string $filename
     *   要解析的 yaml 文件名
     * @param array $replace
     *   对于 YAML 内容要进行自动替换的字符串对
     *
     * @return array
     *   解析结果
     */
    static function parse($input, array $replace = null)
    {
        static $callback;

        $yaml = Spyc::YAMLLoad($input);

        if (!is_array($yaml))
        {
            $yaml = array();
        }

        if (!empty($replace))
        {
            if (is_null($callback))
            {
                $callback = create_function('& $v, $key, $replace', 'foreach ($replace as $search => $rep) { $v = str_replace($search, $rep, $v); }; return $v;');
            }

            array_walk_recursive($yaml, $callback, $replace);
        }

        return $yaml;
    }

    /**
     * 将 PHP 数组（或者实现了 ArrayAccess 接口的对象）输出为字符串
     *
     * @param array $data
     */
    static function dump($data, $indent = 2)
    {
        return Spyc::YAMLDump($data) . "\n";
    }

}

/**
 * @}
 */

