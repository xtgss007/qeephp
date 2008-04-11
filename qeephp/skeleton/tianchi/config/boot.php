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
 * 应用程序的启动文件
 */

/**
 * 设置错误输出级别
 *
 * 如果要屏蔽错误输出信息，修改为 error_reporting(0)
 */
error_reporting(E_ALL | E_STRICT);

/**
 * 将应用程序发布到生产服务器时，请将 RUN_MODE 修改为“deploy”
 *
 * 可用的 RUN_MODE 值如下：
 * deploy   - 部署模式
 * test     - 测试模式
 * devel    - 开发模式
 */
define('RUN_MODE', 'devel');


/*******************************************************************************
 * 使用绝对路径载入 QeePHP 框架文件
 *******************************************************************************/

/**
 * 定义常量，指示 QeePHP 框架根目录
 *
 * 如果改动了 QeePHP 框架文件所在位置，需要修改下面的常量定义。
 */
define('QEEPHP_INST_DIR', '%QEEPHP_INST_DIR%');

/**
 * 载入 QeePHP 框架
 */
require QEEPHP_INST_DIR . '/library/q.php';


/*******************************************************************************
 * 如果已经将 QeePHP 框架文件所在位置加入了 include_path，那么使用下面的代码
 *******************************************************************************/

/**
 * 载入 QeePHP 框架
 */
# require 'library/q.php';

/**
 * 定义常量，指示 QeePHP 框架根目录
 */
# define('QEEPHP_INST_DIR', dirname(Q_DIR));


/**
 * 定义缓存配置文件要使用的缓存服务
 *
 * 默认使用 QCache_File 来缓存配置文件。
 */
define('CONFIG_CACHE_BACKEND', 'QCache_File');

// 定义应用程序根目录
define('ROOT_DIR', dirname(dirname(__FILE__)));

// 导入应用程序目录，以便 Q::loadClass() 能够加载类定义文件
Q::import(ROOT_DIR . DS . 'app' . DS . 'model');
Q::import(ROOT_DIR . DS . 'app');

/**
 * load_boot_config() 函数用于载入应用程序的配置文件
 *
 * @param boolean $reload
 *
 * @return array
 */
function load_boot_config($reload = false)
{
    Q::setIni('runtime_cache_dir', ROOT_DIR . DS . 'tmp' . DS . 'runtime_cache');

    switch (RUN_MODE) {
    case 'deploy':
        // 在部署模式下，配置文件的缓存每 24 小时更新一次
        $life_time = 86400;
        break;
    case 'test':
        // 测试模式下，配置文件每 5 分钟更新一次
        $life_time = 300;
        break;
    case 'devel':
        // 开发模式每次访问都更新缓存，确保修改配置文件后能立即生效
        $life_time = 0;
    }
    $cacheid = 'app.config.' . RUN_MODE;

    $policy = array('life_time' => $life_time, 'serialize' => true);
    if (!$reload) {
        // 尝试从缓存载入配置
        $config = Q::getCache($cacheid, $policy, CONFIG_CACHE_BACKEND);
        if (is_array($config)) {
            Q::setIni($config);
            return;
        }
    }

    $config = load_module_config(null);
    Q::setCache($cacheid, $config, $policy, CONFIG_CACHE_BACKEND);
    Q::setIni($config);
}

/**
 * 载入指定模块的配置
 *
 * @param string $module
 *
 * @return array
 */
function load_module_config($module)
{
    // 载入配置文件，并替换配置文件中的宏
    if ($module) {
        $module = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $module));
        $root = ROOT_DIR . '/module/' . $module;
    } else {
        $root = ROOT_DIR;
    }

    $files = array(
        $root . '/config/environment.yaml.php'                   => 'global',
        $root . '/config/database.yaml.php'                      => 'db_dsn_pool',
        $root . '/config/acl.yaml.php'                           => 'global_act',
        $root . '/config/environments/' . RUN_MODE . '.yaml.php' => 'global',
    );
    $replace = array('%ROOT_DIR%' => ROOT_DIR);

    $config = array();
    foreach ($files as $filename => $namespace) {
        if (!file_exists($filename)) { continue; }
        $contents = Q::loadYAML($filename, $replace);
        if ($namespace == 'global') {
            $config = array_merge_recursive($config, $contents);
        } else {
            if (!isset($config[$namespace])) {
                $config[$namespace] = array();
            }
            $config[$namespace] = array_merge_recursive($config[$namespace], $contents);
        }
    }
    $config['db_dsn_pool']['default'] = $config['db_dsn_pool'][RUN_MODE];

    return $config;
}
