<?php

/**
 * 定义 QDebug 类
 */

/**
 * QDebug 类提供了帮助调试应用程序的一些辅助方法
 */
abstract class QDebug
{

    /**
     * 是否使用 FirePHP
     *
     * @var boolean
     */
    protected static $_firephp_enabled = false;

    /**
     * 是否允许使用 assert
     *
     * @var boolean
     */
    protected static $_assert_enabled = true;

    static function enableFirePHP()
    {
        self::$_firephp_enabled = true;
    }

    static function disableFirePHP()
    {
        self::$_firephp_enabled = false;
    }

    static function enableAssert()
    {
        self::$_assert_enabled = true;
    }

    static function disableAssert()
    {
        self::$_assert_enabled = false;
    }

    /**
     * 输出变量的内容，通常用于调试
     *
     * @param mixed $vars 要输出的变量
     * @param string $label
     * @param boolean $return
     */
    static function dump($vars, $label = null, $return = false)
    {
        if (! $return && self::$_firephp_enabled)
        {
            QDebug_FirePHP::dump($vars, $label);
            return null;
        }

        if (ini_get('html_errors'))
        {
            $content = "<pre>\n";
            if ($label !== null && $label !== '')
            {
                $content .= "<strong>{$label} :</strong>\n";
            }
            $content .= htmlspecialchars(print_r($vars, true));
            $content .= "\n</pre>\n";
        }
        else
        {
            $content = "\n";
            if ($label !== null && $label !== '')
            {
                $content .= $label . " :\n";
            }
            $content .= print_r($vars, true) . "\n";
        }
        if ($return)
        {
            return $content;
        }

        echo $content;
        return null;
    }

    /**
     * 显示应用程序执行路径，通常用于调试
     *
     * @return string
     */
    static function dumpTrace()
    {
        if (self::$_firephp_enabled)
        {
            QDebug_FirePHP::dumpTrace();
            return;
        }

        $debug = debug_backtrace();
        $lines = '';
        $index = 0;
        for ($i = 0; $i < count($debug); $i ++)
        {
            if ($i == 0)
            {
                continue;
            }
            $file = $debug[$i];
            if (! isset($file['file']))
            {
                $file['file'] = 'eval';
            }
            if (! isset($file['line']))
            {
                $file['line'] = null;
            }
            $line = "#{$index} {$file['file']}({$file['line']}): ";
            if (isset($file['class']))
            {
                $line .= "{$file['class']}{$file['type']}";
            }
            $line .= "{$file['function']}(";
            if (isset($file['args']) && count($file['args']))
            {
                foreach ($file['args'] as $arg)
                {
                    $line .= gettype($arg) . ', ';
                }
                $line = substr($line, 0, - 2);
            }
            $line .= ')';
            $lines .= $line . "\n";
            $index ++;
        } // for

        $lines .= "#{$index} {main}\n";

        if (ini_get('html_errors'))
        {
            echo nl2br(str_replace(' ', '&nbsp;', $lines));
        }
        else
        {
            echo $lines;
        }
    }

    /**
     * 断言
     *
     * 如果 $bool 为 false，则调用 assert() 方法。这会导致一个警告信息或中断执行。
     *
     * @param boolean $bool
     *   断言结果
     * @param string $message
     *   要显示的断言信息
     */
    static function assert($bool, $message = null)
    {
        if (!self::$_assert_enabled || $bool)
        {
            return;
        }

        if (self::$_firephp_enabled)
        {
            QDebug_FirePHP::assert($bool, $message);
            return;
        }

        if (Q::getIni('assert_warning'))
        {
            trigger_error($message . "\nAssertion failed", E_USER_WARNING);
            self::dumpTrace();
        }

        if (Q::getIni('assert_exception'))
        {
            throw new QDebug_Assert_Failed($message);
        }
    }
}

