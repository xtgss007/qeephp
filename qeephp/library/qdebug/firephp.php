<?php

abstract class QDebug_FirePHP
{
    static protected $_firephp;
    static protected $_ver = '0.1';

    /**
     * 选择要使用的 FirePHP 扩展版本
     *
     * @param string $ver
     */
    static function ver($ver)
    {
        if ($ver != '0.1' && $ver != '0.2')
        {
            throw new QException(__('Invalid FirePHP version.'));
        }

        self::$_ver = $ver;
    }

    static function dump($vars, $label = null)
    {
        self::_firephp()->fb($vars, $label, FirePHP::LOG);
    }

    static function dumpTrace()
    {
    }

    static function assert($bool, $message = null)
    {
        if ($message)
        {
            $message = ' - ' . $message;
        }

        if ($bool)
        {
            self::_firephp()->fb('Assert TRUE' . $message, FirePHP::INFO);
        }
        else
        {
            self::_firephp()->fb('Assert FALSE' . $message, FirePHP::WARN);
        }
    }

    static function log($msg, $type = 'LOG')
    {
        self::_firephp()->fb($msg, $type);
    }

    /**
     * 返回  FirePHP 实例
     *
     * @return FirePHP
     */
    static protected function _firephp()
    {
        if (is_null(self::$_firephp))
        {
            $ver = self::$_ver;
            require_once Q_DIR . "/_vendor/firephp/FirePHP.{$ver}.class.php";
            self::$_firephp = FirePHP::getInstance(true);
        }

        return self::$_firephp;
    }
}

