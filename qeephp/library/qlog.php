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
 */

/**
 * QLog 类实现了日志服务系统
 *
 * 参考 Zend Framework 的日志服务实现，增加了事件回调机制。
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
	protected $_log = array();

	/**
	 * 已缓存日志内容的大小
	 *
	 * @var int
	 */
	protected $_cached_size = 0;

	/**
	 * 日志缓存块大小
	 *
	 * @var int
	 */
	protected $_cache_chunk_size;

	/**
	 * 日志优先级
	 *
	 * @var array
	 */
	protected $_priorities = array();

    /**
     * 日志文件名
     *
     * @var string
     */
    protected $_filename;

	/**
     * 构造函数
     *
     * @param array $config
	 *
	 * @return Log
	 */
	function __construct(array $config = null)
	{
		$names = array
        (
			'EMERG'		=> self::EMERG,
			'ALERT'		=> self::ALERT,
			'CRIT'		=> self::CRIT,
			'ERR'		=> self::ERR,
			'WARN'		=> self::WARN,
			'NOTICE'	=> self::NOTICE,
			'INFO'		=> self::INFO,
			'DEBUG'		=> self::DEBUG,
		);

        $arr = isset($config['log_priorities']) 
                ? Q::normalize($config['log_priorities']) 
                : Q::normalize(Q::getIni('log_priorities'));
		$this->_priorities = array
        (
			'index' => array(),
			'names' => array(),
		);

		foreach ($arr as $item)
		{
			if (isset($names[$item]))
            {
				$this->_priorities['index'][$names[$item]] = $item;
				$this->_priorities['names'][$item] = $names[$item];
			}
        }

        $dir_tmp = isset($config['log_writer_dir'])
                ? $config['log_writer_dir']
                : Q::getIni('log_writer_dir');
        $dir = realpath($dir_tmp);
        if (empty($dir))
        {
            $dir = realpath(Q::getIni('runtime_cache_dir'));
            if (empty($dir))
            {
                // LC_MSG: 指定的日志文件保存目录不存在 "%s".
                throw new QLog_Exception(__('指定的日志文件保存目录不存在 "%s".', $dir_tmp));
            }
        }

        $filename = isset($config['log_writer_filename'])
                ? $config['log_writer_filename']
                : Q::getIni('log_writer_filename');
        $this->_filename = rtrim($dir, '/\\') . DS . $filename;
        $chunk_size = isset($config['log_cache_chunk_size'])
                ? intval($config['log_cache_chunk_size'])
                : intval(Q::getIni('log_cache_chunk_size'));
		$this->_cache_chunk_size = $chunk_size * 1024;
        $this->append(__METHOD__, self::DEBUG);
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
	static function log($msg, $type = self::DEBUG)
	{
		static $instance;

        if (is_null($instance))
        {
			$instance = Q::getSingleton('QLog');
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
	function append($msg, $type = self::DEBUG)
	{
        if (!Q::getIni('log_enabled'))
        {
            return;
        }

        if (isset($this->_priorities['index'][$type]))
        {
			$type_name = $this->_priorities['index'][$type];
        }
        elseif (isset($this->_priorities['names'][$type]))
        {
			$type_name = $type;
			$type = $this->_priorities['names'][$type];
        }
        else
        {
			return;
		}

		$msg = str_replace(array("\n", "\r"), '', $msg);
		$this->_log[] = array(time(), $msg, $type, $type_name);
		$this->_cached_size += strlen($msg);
		unset($msg);

        if ($this->_cached_size >= $this->_cache_chunk_size)
        {
			$this->flush();
		}
	}

	/**
	 * 将缓存的日志信息写入实际存储，并清空缓存
	 */
	function flush()
	{
        if (empty($this->_log))
        {
            return;
        }

        $string = '';
        foreach ($this->_log as $offset => $item)
        {
            list($time, $msg, $type, $type_name) = $item;
            unset($this->_log[$offset]);
            $string .= date('c', $time) . " {$type_name} ({$type}): {$msg}\n";
        }

        $fp = fopen($this->_filename, 'a');
        if ($fp && flock($fp, LOCK_EX))
        {
            fwrite($fp, $string);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

		$this->_log = array();
		$this->_cached_size = 0;
	}
}

