<?php
// $Id$

/**
 * @file
 * Web 界面代码生成器的入口文件
 *
 * @ingroup script
 *
 * @{
 */

$app_config = require(dirname(dirname(__FILE__)) . '/config/boot.php');
require '%QEEPHP_INST_DIR%/commands/websetup/run.php';
_startup_websetup($app_config);

/**
 * @}
 */
