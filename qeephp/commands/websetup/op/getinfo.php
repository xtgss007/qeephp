<?php

$appinfo = array();

// 取得应用程序定义的常量
$current = get_defined_constants();
require MANAGED_APP_ROOT_DIR . '/config/boot.php';
$all = get_defined_constants();

$appinfo['const'] = array_diff_assoc($all, $current);

// 取得默认模块的设置
$appinfo['config'] = load_module_config_without_cache(null);

echo serialize($appinfo);
