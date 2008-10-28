<?php
// $Id$

/**
 * 应用程序的启动文件
 */

/*******************************************************************************
 * 使用绝对路径载入 QeePHP 框架文件
 *******************************************************************************/

/**
 * 如果改动了 QeePHP 框架文件所在位置，需要修改下面的代码
 */
require '%QEEPHP_INST_DIR%/library/q.php';

/*******************************************************************************
 * 如果已经将 QeePHP 框架文件所在位置加入了 include_path，那么使用下面的代码
 *******************************************************************************/

/**
 * 载入 QeePHP 框架
 */
# require 'qeephp/library/q.php';

// -----------------------------------------------------------------------------

/**
 * 定义应用程序根目录
 */
$root_dir = dirname(dirname(__FILE__));

/**
 * 设置错误输出级别
 *
 * 如果要屏蔽错误输出信息，修改为 error_reporting(0)
 */
error_reporting(E_ALL | E_STRICT);

/**
 * 应用程序配置信息
 */
$app_config = array
(
    /**
     * 应用程序的 ID，用于唯一标识一个应用程序
     */
    'APPID'                 => '%APP_ID%',

    /**
     * 指示应用程序的运行模式
     */
    'RUN_MODE'              => Q::RUN_MODE_DEVEL,

    /**
     * 应用程序根目录
     */
    'ROOT_DIR'              => $root_dir,

    /**
     * 定义缓存配置文件要使用的缓存服务
     *
     * 默认使用 QCache_File 来缓存配置文件。
     */
    'CONFIG_CACHE_BACKEND'  => 'QCache_File',

    /**
     * 指示是否缓存配置文件的内容
     */
    'CONFIG_CACHED'         => true,

    /**
     * 指示配置文件缓存的有效期
     */
    'CONFIG_CACHE_LIFETIME' => 0,

    /**
     * 配置文件的缓存目录
     */
    'CONFIG_CACHE_DIR'      => $root_dir . '/tmp/runtime_cache',

    /**
     * 配置文件的扩展名
     */
    'CONFIG_FILE_EXTNAME'   => 'yaml',
);

return $app_config;

