<?php
// $Id$

/**
 * 单元测试公用初始化文件
 */

require_once 'PHPUnit/Framework.php';

if (defined('TEST_INIT')) { return; }
define('TEST_INIT', true);

date_default_timezone_set('Asia/ShangHai');

require dirname(__FILE__) . '/../../library/q.php';

spl_autoload_register(array('Q', 'loadClass'));

Q::setIni('runtime_cache_dir', dirname(__FILE__) . '/../../tmp');
Q::setIni('log_writer_dir', dirname(__FILE__) . '/../../tmp');
define('FIXTURE_DIR', dirname(dirname(__FILE__)) . DS . 'fixture');
Q::import(FIXTURE_DIR);


abstract class QTest_UnitTest_Abstract extends PHPUnit_Framework_TestCase
{

    protected function assertEmpty($var, $msg = '')
    {
        $this->assertTrue(empty($var), $msg);
    }
}

