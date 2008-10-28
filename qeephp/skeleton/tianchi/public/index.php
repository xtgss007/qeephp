<?php

global $g_boot_time;
$g_boot_time = microtime(true);

$app_config = require dirname(__FILE__) . '/../config/boot.php';
require $app_config['ROOT_DIR'] . '/app/myapp.php';
MyApp::instance($app_config)->run();
