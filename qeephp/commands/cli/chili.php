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
 * 用于创建一个应用程序骨架的脚本
 *
 * @package commands
 * @version $Id: chili.php 222 2008-03-06 15:03:16Z dualface $
 */

$dir = dirname(dirname(__FILE__));
require dirname($dir) . '/library/q.php';
Q::import($dir);

$runner = new Chili_Runner_Cli($argv);
$runner->run();
