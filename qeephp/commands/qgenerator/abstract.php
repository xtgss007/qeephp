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

}
