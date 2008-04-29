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
 * 针对 ActiveRecord 的单元测试
 *
 * @package tests
 * @version $Id$
 */


require_once dirname(__FILE__) . '/../../_include.php';
Q::import(FIXTURE_DIR . DS . 'model');

class QDB_ActiveRecord_Test extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $dsn = Q::getIni('db_dsn_pool/default');
        if (empty($dsn)) {
            Q::setIni('db_dsn_pool/default', Q::getIni('db_dsn_mysql'));
        }
    }

    function testNew()
    {
        $content = new Content();
        $this->assertType('Content', $content);
    }

    function testCreate()
    {
        $author = new Author();
        $author->name = 'liaoyulei - ' . mt_rand();
        $author->save();
        $this->assertNotNull($author->id());
    }

}
