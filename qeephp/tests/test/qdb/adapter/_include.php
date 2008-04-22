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
 * 数据库驱动公用方法的单元测试
 *
 * @package tests
 * @version $Id$
 */

require_once dirname(__FILE__) . '/../../_include.php';

/**
 * MySQL 驱动的单元测试
 *
 * @package tests
 */
abstract class Test_QDB_Adapter_Abstract extends PHPUnit_Framework_TestCase
{
    /**
     * @var QDB_Adapter_Abstract
     */
    protected $dbo;

    protected function setUp()
    {
        $this->dbo = QDB::getConn();
        $this->dbo->connect();
    }

    function testGetDSN()
    {
        $dsn = $this->dbo->getDSN();
        $this->assertTrue(!empty($dsn));
    }

    function testGetID()
    {
        $id = $this->dbo->getID();
        $this->assertTrue(!empty($id));
    }

    function testGetSchema()
    {
        $schema = $this->dbo->getSchema();
        $this->assertEquals(Q::getIni('default_dsn/database'), $schema);
    }

    function testGetTablePrefix()
    {
        $prefix = $this->dbo->getTablePrefix();
        $this->assertEquals(Q::getIni('default_dsn/prefix'), $prefix);
    }

    abstract function testQstr();

    protected function qstr($checks)
    {
        foreach ($checks as $check) {
            $this->assertSame($check[1], $this->dbo->qstr($check[0]), "check qstr({$check[0]})");
        }
    }

    function testQinto()
    {
        switch($this->dbo->paramStyle()) {
        case QDB::PARAM_QM:
            $sql = "SELECT * FROM testtable WHERE level_ix > ? AND int_x = ?";
            $args = array(1, 2);
            break;
        case QDB::PARAM_CL_NAMED:
            $sql = "SELECT * FROM testtable WHERE level_ix > :level_ix AND int_x = :int_x";
            $args = array('level_ix' => 1, 'int_x' => 2);
            break;
        case QDB::PARAM_DL_NAMED:
            $sql = "SELECT * FROM testtable WHERE level_ix > $1 AND int_x = $2";
            $args = array(1, 2);
            break;
        case QDB::PARAM_AT_NAMED:
            $sql = "SELECT * FROM testtable WHERE level_ix > @level_ix AND int_x = @int_x";
            $args = array('level_ix' => 1, 'int_x' => 2);
            break;
        }

        $expected = 'SELECT * FROM testtable WHERE level_ix > 1 AND int_x = 2';
        $this->assertEquals($expected, $this->dbo->qinto($sql, $args));
    }

    function testQinto2()
    {
        $checks = array(
            array(
                "SELECT * FROM testtable WHERE level_ix > ? AND int_x = ?",
                array(1, 2),
                QDB::PARAM_QM
            ),
            array(
                "SELECT * FROM testtable WHERE level_ix > :level_ix AND int_x = :int_x",
                array('level_ix' => 1, 'int_x' => 2),
                QDB::PARAM_CL_NAMED
            ),
            array(
                "SELECT * FROM testtable WHERE level_ix > $1 AND int_x = $2",
                array(1, 2),
                QDB::PARAM_DL_NAMED
            ),
            array(
                "SELECT * FROM testtable WHERE level_ix > @level_ix AND int_x = @int_x",
                array('level_ix' => 1, 'int_x' => 2),
                QDB::PARAM_AT_NAMED
            ),
        );

        $expected = 'SELECT * FROM testtable WHERE level_ix > 1 AND int_x = 2';
        foreach ($checks as $check) {
            list($sql, $args, $param_style) = $check;
            $this->assertEquals($expected, $this->dbo->qinto($sql, $args, $param_style), $sql);
        }
    }

    abstract function testQtable();

    protected function qtable($checks)
    {
        foreach ($checks as $check) {
            list($args, $expected) = $check;
            $actual = call_user_func_array(array($this->dbo, 'qtable'), $args);
            $this->assertEquals($expected, $actual, implode(', ', $args));
        }
    }

    abstract function testQfield();

    protected function qfield($checks)
    {
        foreach ($checks as $check) {
            list($args, $expected) = $check;
            $actual = call_user_func_array(array($this->dbo, 'qfield'), $args);
            $this->assertEquals($expected, $actual, implode(', ', $args));
        }
    }

    abstract function testQfields();

    protected function qfields($checks)
    {
        foreach ($checks as $check) {
            list($args, $expected) = $check;
            $actual = call_user_func_array(array($this->dbo, 'qfields'), $args);
            $this->assertEquals($expected, $actual);
        }
    }

    function testNextID()
    {
        $id = $this->dbo->nextID('testseq');
        $next_id = $this->dbo->nextID('testseq');
        $this->assertTrue($next_id > $id, "\$next_id({$next_id}) > \$id({$id})");
    }

    function testInsertID()
    {
        $idList = $this->insertIntoPosts(1);
        $id = reset($idList);
        $this->assertEquals($id, $this->dbo->insertID());
    }

    function testInsertID2()
    {
        $id = $this->dbo->nextID('testseq');
        $insertID = $this->dbo->insertID();
        $this->assertEquals($id, $insertID, '$id == $insertID');
    }

    function testAffectedRows()
    {
        $idList = implode(',', $this->insertIntoPosts(10));
        $sql = "DELETE FROM q_posts WHERE post_id IN ({$idList})";
        $this->dbo->execute($sql);
        $affectedRows = $this->dbo->affectedRows();
        $this->assertEquals(10, $affectedRows, '10 == $affectedRows');
    }

    abstract function testExecute();

    protected function execute($sql, $args)
    {
        $result = $this->dbo->execute($sql, $args);
        $this->assertTrue($result, 'execute sql: ' . $sql);
    }

    function testSelectLimit()
    {
        $this->dbo->execute('DELETE FROM q_posts');
        $idList = $this->insertIntoPosts(10);
        $sql = "SELECT post_id FROM q_posts ORDER BY post_id ASC";

        $length = 10;
        $offset = 0;
        $rowset = $this->dbo->selectLimit($sql, $length, $offset)->fetchAll();
        $msg = "\$rowset = \$this->dbo->select_limit('{$sql}', {$length}, {$offset});";
        $this->assertEquals($length, count($rowset), $msg);
        for ($i = $offset; $i < $offset + $length; $i++) {
            $msg = "\$length = {$length}, \$offset = {$offset}";
            $this->assertEquals($idList[$i], $rowset[$i - $offset]['post_id'], $msg);
        }

        $length = 3;
        $offset = 5;
        $rowset = $this->dbo->selectLimit($sql, $length, $offset)->fetchAll();
        $msg = "\$rowset = \$this->dbo->select_limit('{$sql}', {$length}, {$offset});";
        $this->assertEquals($length, count($rowset), $msg);
        for ($i = $offset; $i < $offset + $length; $i++) {
            $msg = "\$length = {$length}, \$offset = {$offset}";
            $this->assertEquals($idList[$i], $rowset[$i - $offset]['post_id'], $msg);
        }
    }

    function testGetAll()
    {
        $this->dbo->execute('DELETE FROM q_posts');
        $idList = $this->insertIntoPosts(10);

        $sql = "SELECT post_id FROM q_posts ORDER BY post_id ASC";
        $rowset = $this->dbo->getAll($sql);
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($idList[$i], $rowset[$i]['post_id']);
        }
    }

    function testGetCol()
    {
        $this->dbo->execute('DELETE FROM q_posts');
        $idList = $this->insertIntoPosts(10);

        $sql = "SELECT post_id FROM q_posts ORDER BY post_id ASC";
        $rowset = $this->dbo->getCol($sql);
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals($idList[$i], $rowset[$i]);
        }
    }

    function testBeginTrans()
    {
        $sql = 'SELECT COUNT(*) FROM q_posts';
        $count = $this->dbo->getOne($sql);
        $tran = $this->dbo->beginTrans();
        $this->assertType('QDB_Transaction', $tran);
        $this->insertIntoPosts(10);
        unset($tran);
        $newCount = $this->dbo->getOne($sql);
        $this->assertEquals($count + 10, $newCount);
    }

    function testBeginTrans2()
    {
        $sql = 'SELECT COUNT(*) FROM q_posts';
        $count = $this->dbo->getOne($sql);
        $tran = $this->dbo->beginTrans();
        $this->assertType('QDB_Transaction', $tran);
        $this->insertIntoPosts(10);

        // 明确的回滚事务
        $tran->rollback();
        unset($tran);
        $newCount = $this->dbo->getOne($sql);
        $this->assertEquals($count, $newCount);
    }

    function testBeginTrans3()
    {
        $sql = 'SELECT COUNT(*) FROM q_posts';
        $count = $this->dbo->getOne($sql);
        $tran = $this->dbo->beginTrans();
        $this->assertType('QDB_Transaction', $tran);
        $this->insertIntoPosts(10);

        // 明确的回滚事务
        $tran->setTransFailed();
        unset($tran);
        $newCount = $this->dbo->getOne($sql);
        $this->assertEquals($count, $newCount);
    }

    function testBeginTrans4()
    {
        $sql = 'SELECT COUNT(*) FROM q_posts';
        $count = $this->dbo->getOne($sql);
        $tran = $this->dbo->beginTrans();
        $this->assertType('QDB_Transaction', $tran);
        $this->insertIntoPosts(10);
        try {
            // 当事务中出现数据库操作错误时回滚
            $this->dbo->execute('INSERT XXXX'); // must failed query
        } catch (Exception $ex) { }
        $this->assertTrue($this->dbo->hasFailedQuery(), '$this->dbo->hasFailedQuery() == true');
        unset($tran);
        $newCount = $this->dbo->getOne($sql);
        $this->assertEquals($count, $newCount);
    }

    function testGetInsertSQL()
    {
        $row = array(
            'title' => 'Title :' . mt_rand(),
            'body' => 'Body :' . mt_rand(),
            'created' => time(),
            'updated' => time(),
        );
        $sql = $this->dbo->getInsertSQL($row, 'q_posts');
        $this->dbo->execute($sql, $row);
        $sql = 'SELECT * FROM q_posts WHERE post_id = ' . $this->dbo->insertID();
        $exists = $this->dbo->getRow($sql);
        $this->assertEquals($row['title'], $exists['title']);
    }

    function testGetUpdateSQL()
    {
        $idList = $this->insertIntoPosts(1);
        $id = reset($idList);
        $sql = "SELECT * FROM q_posts WHERE post_id = {$id}";
        $row = $this->dbo->getRow($sql);
        $row['title'] = 'Title +' . mt_rand();
        $updateSQL = $this->dbo->getUpdateSQL($row, 'post_id', 'q_posts');
        unset($row['post_id']);
        $this->dbo->execute($updateSQL, $row);

        $exists = $this->dbo->getRow($sql);
        $this->assertEquals($row['title'], $exists['title']);
    }


    private function insertIntoPosts($nums)
    {
        $time = time();
        $sql = "INSERT INTO q_posts (title, body, created, updated) VALUES ('title', 'body', {$time}, {$time})";
        $idList = array();
        for ($i = 0; $i < $nums; $i++) {
            $this->dbo->execute($sql);
            $idList[] = $this->dbo->insertID();
        }
        return $idList;
    }
}
