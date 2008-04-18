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
 * 定义 QLog 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QLog 类实现了日志服务系统
 *
 * 参考 Zend Framework 的日志服务实现，增加了事件回调机制。
 *
 * @package core
 */
class QLog
{
    /**
     * 优先级
     */
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages

    /**
     * 日期格式
     *
     * @var string
     */
    public $date_format = 'Y-m-d H:i:s';

    /**
     * 保存运行期间的日志
     *
     * @var array
     */
    protected $log = array();

    /**
     * 已缓存日志内容的大小
     *
     * @var int
     */
    protected $cached_size = 0;

    /**
     * 日志缓存块大小
     *
     * @var int
     */
    protected $cache_chunk_size;

    /**
     * 日志优先级
     *
     * @var array
     */
    protected $priorities = array();

    /**
     * 写入程序
     *
     * @var array
     */
    protected $writers = array();

    /**
     * 日志过滤器
     *
     * @var array
     */
    protected $filters = array();

    /**
     * 构造函数
     *
     * @return Log
     */
    function __construct()
    {
        $priorities = array(
            self::EMERG     => 'EMERG',
            self::ALERT     => 'ALERT',
            self::CRIT      => 'CRIT',
            self::ERR       => 'ERR',
            self::WARN      => 'WARN',
            self::NOTICE    => 'NOTICE',
            self::INFO      => 'INFO',
            self::DEBUG     => 'DEBUG',
        );

        $this->priorities = array(
            'indexs' => $priorities,
            'names' => array_flip($priorities),
        );

        $this->cache_chunk_size = intval(Q::getIni('log_cache_chunk_size')) * 1024;
        $this->writers = Q::normalize(Q::getIni('log_writers'));
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        $this->flush();
    }

    /**
     * 追加日志到日志缓存
     *
     * @param string $msg
     * @param int $type
     */
    static function log($msg, $type)
    {
        static $instance;

        if (is_null($instance)) {
            $instance = Q::getService('log');
        }
        /* @var $instance QLog */
        $instance->append($msg, $type);
    }

    /**
     * 追加日志到日志缓存
     *
     * @param string $msg
     * @param int $type
     */
    function append($msg, $type)
    {
        if (isset($this->priorities['indexs'][$type])) {
            $type_name = $this->priorities['indexs'][$type];
        } elseif (isset($this->priorities['names'][$type])) {
            $type_name = $type;
            $type = $this->priorities['names'][$type];
        } else {
            // LC_MSG: '无效的日志类型 "%s".
            throw new QLog_Exception(__('无效的日志类型 "%s".', $type));
        }

        $this->log[] = array(time(), $msg, $type, $type_name);
        $this->cached_size += strlen($msg);

        if ($this->cached_size >= $this->cache_chunk_size) {
            $this->flush();
        }
    }

    /**
     * 将缓存的日志信息写入实际存储，并清空缓存
     */
    function flush()
    {
        $this->write();
        $this->log = array();
        $this->cached_size = 0;
    }

    /**
     * 将日志信息写入缓存
     */
    protected function write()
    {
        foreach ($this->writers as $offset => $writer) {
            if (!is_object($writer)) {
                $writer = Q::getSingleton($writer);
                $this->writers[$offset] = $writer;
            }
            $writer->write($this->log);
        }
    }
}
