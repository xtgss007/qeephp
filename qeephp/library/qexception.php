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
 * 定义 QException 类
 */

/**
 * QException 是 QeePHP 的异常基础类
 */
class QException extends Exception
{
    /**
     * 构造函数
     *
     * @param string $message
     * @param int $code
     */
    function __construct($message, $code = 0)
    {
        parent::__construct($message, intval($code));
    }

    /**
     * 输出异常
     *
     * @param Exception $ex
     */
    static function dump(Exception $ex)
    {
        $out = "exception '" . get_class($ex) . "'";
        if ($ex->getMessage() != '')
        {
            $out .= " with message '" . $ex->getMessage() . "'";
        }

        $out .= ' in ' . $ex->getFile() . ':' . $ex->getLine() . "\n\n";
        $out .= $ex->getTraceAsString();

        if (ini_get('html_errors'))
        {
            echo nl2br(htmlspecialchars($out));
        }
        else
        {
            echo $out;
        }
    }
}
