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
 * 定义 QDebug 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QDebug 类提供了帮助调试应用程序的一些辅助方法
 *
 * @package core
 */
class QDebug
{
    /**
     * 输出变量的内容，通常用于调试
     *
     * @package core
     *
     * @param mixed $vars 要输出的变量
     * @param string $label
     * @param boolean $return
     */
    static function dump($vars, $label = '', $return = false)
    {
        if (ini_get('html_errors')) {
            $content = "<pre>\n";
            if ($label != '') {
                $content .= "<strong>{$label} :</strong>\n";
            }
            $content .= htmlspecialchars(print_r($vars, true));
            $content .= "\n</pre>\n";
        } else {
            $content = '';
            if ($label) { $content .= $label . " :\n"; }
            $content .= print_r($vars, true) . "\n\n";
        }
        if ($return) { return $content; }
        echo $content;
        return null;
    }

    /**
     * 显示应用程序执行路径，通常用于调试
     *
     * @package core
     *
     * @return string
     */
    static function dumpTrace()
    {
        $debug = debug_backtrace();
        $lines = '';
        $index = 0;
        for ($i = 0; $i < count($debug); $i++) {
            if ($i == 0) { continue; }
            $file = $debug[$i];
            if (!isset($file['file'])) {
                $file['file'] = 'eval';
            }
            if (!isset($file['line'])) {
                $file['line'] = null;
            }
            $line = "#{$index} {$file['file']}({$file['line']}): ";
            if (isset($file['class'])) {
                $line .= "{$file['class']}{$file['type']}";
            }
            $line .= "{$file['function']}(";
            if (isset($file['args']) && count($file['args'])) {
                foreach ($file['args'] as $arg) {
                    $line .= gettype($arg) . ', ';
                }
                $line = substr($line, 0, -2);
            }
            $line .= ')';
            $lines .= $line . "\n";
            $index++;
        } // for
        $lines .= "#{$index} {main}\n";

        if (ini_get('html_errors')) {
            echo nl2br(str_replace(' ', '&nbsp;', $lines));
        } else {
            echo $lines;
        }
    }

}
