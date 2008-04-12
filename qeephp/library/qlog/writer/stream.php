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
 * 定义 QLog_Writer_Stream 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QLog_Writer_Stream 类实现了一个流写入器
 *
 * @package core
 */
class QLog_Writer_Stream
{
    /**
     * 日志文件名
     *
     * @var string
     */
    protected $filename;

    /**
     * 构造函数
     */
    function __construct()
    {
        $dir = realpath(Q::getIni('log_writer_stream_dir'));
        if (empty($dir)) {
            $dir = realpath(Q::getIni('runtime_cache_dir'));
            if (empty($dir)) {
                // LC_MSG: 指定的日志文件保存目录不存在 "%s".
                throw new QLog_Exception(__('指定的日志文件保存目录不存在 "%s".', Q::getIni('log_writer_stream_dir')));
            }
        }

        $this->filename = rtrim($dir, '/\\') . DS . Q::getIni('log_writer_stram_filename');
    }

    /**
     * 写入日志条目
     *
     * @param array $log
     */
    function write(array $log)
    {
        if (empty($log)) { return; }
        $fp = fopen($this->filename, 'a');
        if (!$fp) {
            // LC_MSG: 写入日志文件 "%s" 失败.
            throw new QLog_Exception(__('写入日志文件 "%s" 失败.', basename($this->filename)));
        }

        if (flock($fp, LOCK_EX)) {
            foreach ($log as $item) {
                list($time, $msg, $type, $type_name) = $item;

                $string = date('c', $time) . ' ' . $type_name . ' (' . $type . '): ' . $msg . PHP_EOL;
                fwrite($fp, $string);
            }
            flock($fp, LOCK_UN);
        } else {
            // LC_MSG: 尝试锁定日志文件 "%s" 失败.
            throw new QLog_Exception(__('尝试锁定日志文件 "%s" 失败.', basename($this->filename)));
        }
        fclose($fp);
    }

}
