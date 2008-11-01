<?php
// $Id$

/**
 * @file
 * 命令行代码生成器的入口文件
 *
 * @ingroup script
 *
 * @{
 */

if (!isset($argv))
{
    echo <<<EOT
ERR: PHP running command line without \$argv.

EOT;

    exit;
}

$app_config = require(dirname(dirname(__FILE__)) . '/config/boot.php');
require '%QEEPHP_INST_DIR%/commands/cli/generator.php';

array_shift($argv);
$app = new CliGenerator($app_config, $argv);
$app->run();

/**
 * @}
 */

