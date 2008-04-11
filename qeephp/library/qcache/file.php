<?php
/////////////////////////////////////////////////////////////////////////////
// 这个文件是 QeePHP 项目的一部分
//
// Copyright (c) 2007 - 2008 QeePHP.org (www.qeephp.org)
//
// 要查看完整的版权信息和许可信息，请查看源代码中附带的 LICENSE 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QCache_File 类
 *
 * @copyright Copyright (c) 2007 - 2008 QeePHP.org (http://www.qeephp.org/)
 * @author 起源科技 (http://www.qeeyuan.com/)
 * @package core
 * @version $Id$
 */

/**
 * Cache_File 提供以文件系统来缓存数据的服务
 *
 * @package core
 */
class QCache_File
{
    /**
     * 默认的缓存策略
     *
     * @var array
     */
    protected $default_policy = array(
        /**
         * 缓存有效时间
         *
         * 如果设置为 0 表示缓存总是失效，设置为 null 则表示不检查缓存有效期。
         */
        'life_time'         => 900,
        /**
         * 自动序列化数据后再写入缓存
         *
         * 可以很方便的缓存 PHP 变量值（例如数组），但要慢一点。
         */
        'serialize'         => false,
        /**
         * 编码缓存文件名
         *
         * 如果缓存ID存在非文件名字符，那么必须对缓存文件名编码。
         */
        'encoding_filename' => true,
        /**
         * 缓存目录深度
         *
         * 如果大于 1，则会在缓存目录下创建子目录保存缓存文件。
         * 如果要写入的缓存文件超过 500 个，目录深度设置为 1 或者 2 较为合适。
         * 如果有更多文件，可以采用更大的缓存目录深度。
         */
        'cache_dir_depth'   => 0,
        /**
         * 创建缓存目录时的标志
         */
        'cache_dir_umask'   => 0700,
        /**
         * 缓存目录（必须指定）
         */
        'cache_dir'         => null,
        /**
         * 读取和写入文件时锁定文件（更安全），避免读取或写入破损的缓存文件
         */
        'file_locking'      => true,
        /**
         * 是否在读取缓存内容时检验缓存内容完整性
         */
        'test_validity'     => true,
        /**
         * 检验缓存内容完整性的方式
         *
         * crc32 速度较快，而且安全。md5 速度最慢，但最可靠。strlen 速度最快，可靠性略差。
         */
        'test_method'       => 'crc32',
    );

    /**
     * 构造函数
     *
     * @param 默认的缓存策略 $default_policy
     */
    function __construct(array $default_policy = null)
    {
        if (!is_null($default_policy)) {
            $this->default_policy = array_merge($this->default_policy, $default_policy);
        }
        if (empty($this->default_policy['cache_dir'])) {
            $this->default_policy['cache_dir'] = Q::getIni('runtime_cache_dir');
        }
    }

    /**
     * 写入缓存
     *
     * @param string $id
     * @param mixed $data
     * @param array $policy
     */
    function set($id, $data, array $policy = null)
    {
        $policy = $this->policy($policy);
        if ($policy['serialize']) {
            $data = serialize($data);
        }
        // 取得文件名
        $filename = $this->filename($id, $policy['encoding_filename']);

        // 确定缓存目录
        if ($policy['cache_dir_depth'] > 0) {
            $root = $this->dirname($policy['cache_dir'], $policy['cache_dir_depth'], $filename, $policy['cache_dir_umask']);
        } else {
            $root = rtrim($policy['cache_dir'], '\\/') . DIRECTORY_SEPARATOR;
        }
        $fp = fopen($root . $filename, 'wb');
        if ($fp) {
            if ($policy['file_locking']) { flock($fp, LOCK_EX); }
            // 缓存文件头部的 32 个字节写入该缓存的策略信息
            $head = pack('ISS', $policy['life_time'], $policy['serialize'], $policy['test_validity']);
            $head .= sprintf('% 8s', $policy['test_method']);
            $head .= '                ';
            fwrite($fp, $head, 32);
            if ($policy['test_validity']) {
                // 接下来的 32 个字节写入用于验证数据完整性的验证码
                fwrite($fp, $this->hash($data, $policy['test_method']), 32);
            }
            fwrite($fp, $data, strlen($data));
            if ($policy['file_locking']) { flock($fp, LOCK_UN); }
            fclose($fp);
        } else {
            // LC_MSG: Unable to write cache file "%s".
            throw new QCache_Exception(__('Unable to write cache file "%s".',  $root . $filename));
        }
    }

    /**
     * 读取缓存，缓存失效时返回 false
     *
     * @param string $id
     * @param array $policy
     * @return mixed
     */
    function get($id, array $policy = null)
    {
        $policy = $this->policy($policy);
        // 如果缓存策略 life_time 为 null，表示缓存数据永不过期
        if (is_null($policy['life_time'])) {
            $refresh_time = null;
        } else {
            $refresh_time = time();
        }

        // 确定缓存文件名和目录名
        $filename = $this->filename($id, $policy['encoding_filename']);
        if ($policy['cache_dir_depth'] > 0) {
            $root = $this->dirname($policy['cache_dir'], $policy['cache_dir_depth'], $filename, $policy['cache_dir_umask']);
        } else {
            $root = rtrim($policy['cache_dir'], '\\/') . DIRECTORY_SEPARATOR;
        }
        $path = $root . $filename;

        // 清除PHP缓存的相关状态信息
        clearstatcache();

        // 如果文件不存在，返回 false
        if (!file_exists($path)) { return false; }


        $fp = fopen($path, 'rb');
        if (!$fp) { return false; }

        if ($policy['file_locking']) { flock($fp, LOCK_SH); }
        clearstatcache();
        $len = filesize($path);
        $mqr = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);

        // 头部的 32 个字节存储了该缓存的策略
        $head = fread($fp, 32);
        $len -= 32;
        $tmp = unpack('Il/Ss/St', substr($head, 0, 8));
        $policy['life_time'] = $tmp['l'];
        $policy['serialize'] = $tmp['s'];
        $policy['test_validity'] = $tmp['t'];
        $policy['test_method'] = trim(substr($head, 8, 8));

        // 检查缓存是否已经过期
        do {
            if (!is_null($refresh_time)) {
                if (filemtime($path) <= $refresh_time - $policy['life_time']) {
                    $hashtest = null;
                    $data = false;
                    break;
                }
            }

            // 检查缓存数据的完整性
            if ($policy['test_validity']) {
                $hashtest = fread($fp, 32);
                $len -= 32;
            }

            if ($len > 0) {
                $data = fread($fp, $len);
            } else {
                $data = false;
            }
            set_magic_quotes_runtime($mqr);
        } while (false);

        if ($policy['file_locking']) { flock($fp, LOCK_UN); }
        fclose($fp);

        if ($policy['test_validity']) {
            $hash = $this->hash($data, $policy['test_method']);
            if ($hash != $hashtest) {
                if (is_null($refresh_time)) {
                    // 如果是永不过期的缓存文件没通过验证，则直接删除
                    unlink($path);
                } else {
                    // 否则设置文件时间为已经过期
                    touch($path, time() - 2 * abs($policy['life_time']));
                }
                return false;
            }
        }

        if ($policy['serialize'] && is_string($data)) {
            $data = unserialize($data);
        }
        return $data;
    }

    /**
     * 删除指定的缓存
     *
     * @param string $id
     * @param array $policy
     */
    function remove($id, array $policy = null)
    {
        $policy = $this->policy($policy);
        $filename = $this->filename($id, $policy['encoding_filename']);
        if ($policy['cache_dir_depth'] > 0) {
            $root = $this->dirname($policy['cache_dir'], $policy['cache_dir_depth'], $filename, $policy['cache_dir_umask'], false);
        } else {
            $root = rtrim($policy['cache_dir'], '\\/') . DIRECTORY_SEPARATOR;
        }
        if (unlink($root . $filename)) {
            // LC_MSG: Unable to remove cache file "%s".
            throw new Cache_Exception('Unable to remove cache file "%s".', $root . $filename);
        }
        return $this->_unlink($this->_file);
    }

    /**
     * 返回有效的策略选项
     *
     * @param array $policy
     * @return array
     */
    protected function policy(array $policy = null)
    {
        return !is_null($policy) ? array_merge($this->default_policy, $policy) : $this->default_policy;
    }

    /**
     * 获得缓存目录名
     *
     * @param string $root
     * @param int $depth
     * @param string $filename
     * @param int $umask
     * @param boolean $create
     */
    protected function dirname($root, $depth, $filename, $umask, $create = true)
    {
        $root = rtrim($root, '\\/') . DIRECTORY_SEPARATOR;
        $hash = md5($filename);
        $root .= 'cache_';
        for ($i = 1; $i <= $depth; $i++) {
            $root .= substr($hash, 0, $i) . DIRECTORY_SEPARATOR;
            if (!$create) { continue; }
            if (is_dir($root)) { continue; }
            mkdir($root, $umask);
        }
        return $root;
    }

    /**
     * 获得缓存文件名
     *
     * @param string $id
     * @param boolean $encoding_filename
     * @return string
     */
    protected function filename($id, $encoding_filename)
    {
        return $encoding_filename ? 'cache_' . md5($id) : 'cache_' . $id;
    }

    /**
     * 获得数据的校验码
     *
     * @param string $data
     * @param string $type
     * @return string
     */
    protected function hash($data, $type)
    {
        switch ($type) {
        case 'md5':
            return md5($data);
        case 'crc32':
            return sprintf('% 32d', crc32($data));
        case 'strlen':
            return sprintf('% 32d', strlen($data));
        default:
            // LC_MSG: Unknown test_method ! (available values are only 'md5', 'crc32', 'strlen').
            throw new Cache_Exception("Unknown test_method ! (available values are only 'md5', 'crc32', 'strlen').");
        }
    }
}
