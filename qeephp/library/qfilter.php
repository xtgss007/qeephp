<?php
// $Id$

/**
 * @file
 * 定义 QFilter 类
 *
 * @ingroup core
 *
 * @{
 */

/**
 * QFilter 类提供了一组常用的过滤方法，以及可扩展的结构
 */
abstract class QFilter
{
    /**
     * 用单个过滤器过滤值，并返回过滤结果
     *
     * @param mixed $value
     * @param mixed $filter
     *
     * @retrun mixed
     */
    static function filter($value, $filter)
    {
        // 自定义过滤方法
        if (is_array($filter))
        {
            return call_user_func($filter, $value);
        }

        // 内置的 PHP 函数
        if (function_exists($filter))
        {
            return $filter($value);
        }

        // 自定义的静态过滤方法
        if (strpos($filter, '::'))
        {
            return call_user_func(explode('::', $filter), $value);
        }

        // QFilter 类的过滤方法
        return call_user_func(array(__CLASS__, 'filter_' . $filter), $value);
    }

    /**
     * 用一组过滤器过滤值，返回过滤结果
     *
     * @param mixed $value
     * @param array $filters
     *
     * @return mixed
     */
    static function filters($value, array $filters)
    {
        foreach ($filters as $filter)
        {
            $value = self::filter($value, $filter);
        }
        return $value;
    }


    /**
     * 过滤掉非字母和数字
     *
     * @return mixed
     */
    static function filter_alnum($value)
    {
        if (self::_unicodeEnabled())
        {
            $pattern = '/[^a-zA-Z0-9]/';
        }
        else
        {
            $pattern = '/[^a-zA-Z0-9]/u';
        }

        return preg_replace($pattern, '', (string)$value);
    }

    /**
     * 过滤掉非字母
     *
     * @return mixed
     */
    static function filter_alpha($value)
    {
        if (self::_unicodeEnabled())
        {
            $pattern = '/[^a-zA-Z]/';
        }
        else
        {
            $pattern = '/[^a-zA-Z]/u';
        }

        return preg_replace($pattern, '', (string)$value);
    }

    /**
     * 过滤掉非数字
     *
     * @return mixed
     */
    static function filter_digits($value)
    {
        if (self::_unicodeEnabled())
        {
            $pattern = '/[^0-9]/';
        }
        else if (extension_loaded('mbstring'))
        {
            $pattern = '/[^[:digit:]]/';
        }
        else
        {
            $pattern = '/[\p{^N}]/';
        }

        return preg_replace($pattern, '', (string)$value);
    }

    /**
     * 确认 PCRE 是否支持 utf8 和 unicode
     *
     * @return boolean
     */
    static protected function _unicodeEnabled()
    {
        static $enabled = null;
        if (is_null($enabled))
        {
            $enabled = (@preg_match('/\pL/u', 'a')) ? true : false;
        }
        return $enabled;
    }
}

/**
 * @}
 */

