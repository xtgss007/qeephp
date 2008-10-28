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
 * 代码生成器的命令行启动脚本
 *
 * @package commands
 * @version $Id: generate.php 955 2008-03-16 23:52:44Z dualface $
 */

Q::import(dirname(dirname(__FILE__)));

$runner = new QGenerator_Runner_Cli($argv);
$runner->run();
