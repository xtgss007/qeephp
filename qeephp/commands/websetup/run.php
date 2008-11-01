<?php
// $Id$

global $g_boot_time;
$g_boot_time = microtime(true);

function _startup_websetup($managed_app_config)
{
    $app_config = require(dirname(__FILE__) . '/config/boot.php');
    $app_config['MANAGED_APP_ROOT_DIR'] = $managed_app_config['ROOT_DIR'];
    require $app_config['ROOT_DIR'] . '/app/websetup_app.php';
    WebSetupApp::instance($app_config, $managed_app_config)->run();
}

$runtime_info_elapsed_time = sprintf('%0.3f', microtime(true) - $g_boot_time);

