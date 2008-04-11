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
 * 测试 QDB 的 MySQL 驱动
 *
 * @package tests
 * @version $Id$
 */

require dirname(__FILE__) . '/_include.php';

/**
 * MySQL 驱动的单元测试
 *
 * @package tests
 */
class Test_QDB_Adapter_MySQL extends Test_QDB_Adapter_Abstract
{
    protected function setUp()
    {
        Q::setIni('default_dsn', Q::getIni('dsn_mysql'));
        parent::setUp();
    }

    function testQstr()
    {
        $checks = array(
            array('12345', "'12345'"),
            array(12345, 12345),
            array(true, 1),
            array(false, 0),
            array(null, 'NULL'),
            array('string', "'string'"),
            array("string'string", "'string\\'string'"),
        );
        $this->qstr($checks);
    }

    function testQtable()
    {
        $checks = array(
            array(array('posts', null), '`posts`'),
            array(array('`posts`', null), '`posts`'),
            array(array('posts', 'test'), '`test`.`posts`'),
            array(array('`posts`', 'test'), '`test`.`posts`'),
            array(array('`posts`', '`test`'), '`test`.`posts`'),
        );
        $this->qtable($checks);
    }

    function testQfield()
    {
        $checks = array(
            array(array('post_id', null), '`post_id`'),
            array(array('post_id', 'posts'), '`posts`.`post_id`'),
            array(array('post_id', 'posts', 'test'), '`test`.`posts`.`post_id`'),
            array(array('`post_id`', null), '`post_id`'),
            array(array('`post_id`','`posts`'), '`posts`.`post_id`'),
            array(array('post_id', null, 'test'), '`post_id`'),
        );
        $this->qfield($checks);
    }

    function testQfields()
    {
        $checks = array(
            array(array('post_id, title', null), '`post_id`, `title`'),
            array(array(array('post_id', 'title'), 'posts'), '`posts`.`post_id`, `posts`.`title`'),
            array(array('post_id', 'posts', 'test'), '`test`.`posts`.`post_id`'),
        );
        $this->qfields($checks);
    }

    function testExecute()
    {
        $sql = "INSERT INTO q_posts (title, body, created, updated) VALUES (?, ?, ?, ?)";
        $args = array('title', 'body', time(), time());
        $this->execute($sql, $args);
    }
}
