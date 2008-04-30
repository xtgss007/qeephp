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
    /**
     * @var QDB_Adapter_Abstract
     */
    protected $_conn;

    protected function setUp()
    {
        $dsn = Q::getIni('db_dsn_pool/default');
        if (empty($dsn)) {
            Q::setIni('db_dsn_pool/default', Q::getIni('db_dsn_mysql'));
        }
        $this->_conn = QDB::getConn();
        $this->_conn->startTrans();
    }

    protected function tearDown()
    {
        $this->_conn->completeTrans(false);
    }

    /**
     * 构造对象
     */
    function testNew()
    {
        $content = new Content();
        $this->assertType('Content', $content);
    }

    /**
     * 构造对象，并在数据库中创建新记录
     */
    function testCreate()
    {
        $count = $this->_queryAuthorsCount();

        $time = time();
        $author = new Author();
        $author->name = 'name - ' . mt_rand();
        $author->save();

        // 通过比较记录数，确定新的记录已经创建
        $new_count = $this->_queryAuthorsCount();
        $this->assertEquals($new_count, $count + 1);
        $this->assertNotNull($author->id());

        // 确定对象值已经正确保存到数据库中
        $row = $this->_queryAuthor($author->id());
        $this->assertTrue(!empty($row));
        $this->assertEquals($author->name, $row['name']);
        $this->assertGreaterThanOrEqual($time, $row['created']);
        $this->assertGreaterThanOrEqual($time, $row['updated']);
    }

    /**
     * 先新建记录，然后更新已有数据
     */
    function testUpdate()
    {
        $name = 'name - ' . mt_rand();
        $author = new Author(array('name' => $name));
        $author->save();
        $id = $author->id();
        $row = $this->_queryAuthor($id);
        $count = $this->_queryAuthorsCount();

        $new_name = 'name - new - ' . mt_rand();
        $author->name = $new_name;
        $author->save();
        $this->assertEquals($id, $author->id());
        $this->assertEquals($count, $this->_queryAuthorsCount());

        $new_row = $this->_queryAuthor($id);
        $this->assertNotEquals($row['name'], $new_row['name']);
        $this->assertEquals($new_name, $new_row['name']);
    }

    /**
     * 先创建，再销毁一个对象
     */
    function testDestroyOne()
    {
        $author = new Author(array(
            'name' => 'congcong - ' . mt_rand(),
        ));
        $author->save();
        $id = $author->id();

        $count = $this->_queryAuthorsCount();
        $author->destroy();
        $new_count = $this->_queryAuthorsCount();

        $row = $this->_queryAuthor($id);
        $this->assertTrue(empty($row));
        $this->assertEquals($new_count, $count - 1);
    }

    /**
     * 查找符合条件的对象
     */
    function testFindOne()
    {
        $id_list = $this->_createAuthors(5);

        // 以字符串形式查找（? 作为参数占位符）
        $id = $this->_getRandID($id_list);
        $author = Author::find('author_id = ?', $id)->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);

        // 以字符串形式查找（:id 作为参数占位符）
        $id = $this->_getRandID($id_list);
        $author = Author::find('author_id = :id', array('id' => $id))->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);

        // 以数组形式查找
        $id = $this->_getRandID($id_list);
        $author = Author::find(array('author_id' => $id))->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);

        // 以 QDB_Expr 作为查询条件
        $id = $this->_getRandID($id_list);
        $author = Author::find(new QDB_Expr('author_id = ' . $id))->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);

        // 以 qdb_cond 作为查询条件（? 作为参数占位符）
        $id = $this->_getRandID($id_list);
        $author = Author::find(new QDB_Cond('author_id = ?', $id))->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);

        // 以 qdb_cond 作为查询条件（:id 作为参数占位符）
        $id = $this->_getRandID($id_list);
        $author = Author::find(new QDB_Cond('author_id = :id', array('id' => $id)))->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);

        // 以 qdb_cond 作为查询条件（数组条件）
        $id = $this->_getRandID($id_list);
        $author = Author::find(new QDB_Cond(array('author_id' => $id)))->query();
        $this->_checkAuthor($this->_queryAuthor($id), $author);
    }

    /**
     * 查找多个对象
     */
    function testFindMore()
    {
        $id_list = $this->_createAuthors(15);

        // 查找全部
        $authors = Author::find()->all()->query();
        $this->assertGreaterThanOrEqual(15, count($authors));
        $this->_checkAuthors($authors);
        unset($authors);

        // 查找ID 大于等于特定值的
        $authors = Author::find('author_id >= ?', $id_list[5])->all()->query();
        $this->assertEquals(10, count($authors));
        $this->_checkAuthors($authors);
        unset($authors);
    }

    /**
     * 测试关联对象访问
     */
    function testAssociationAccessWithNewObject()
    {
        $author = new Author(); 
        $this->assertType('QColl', $author->contents);
        $this->assertEquals(0, count($author->contents));
        $this->assertType('QColl', $author->comments);
        $this->assertEquals(0, count($author->comments));
        
        $content = new Content();
        $this->assertType('Author_Null', $content->author);
        $this->assertType('QColl', $content->tags);
        $this->assertEquals(0, count($content->tags));
    }
    
    /**
     * 创建对象时，保存 has_many 关联的对象
     */
    function testCreateWithHasMany()
    {
        $author = new Author(array('name' => 'name - ' . mt_rand()));
        for ($i = 0; $i < 5; $i++) {
	        $author->contents[] = new Content(array(
	            'title' => 'title - ' . mt_rand(),
	        ));
        }
        $author->save();
        
        $this->assertNotNull($author->id());
        $this->_checkAuthor($this->_queryAuthor($author->id()), $author);
        $this->_checkContents($author->contents);
    }

    /**
     * 检查一组 Author 对象
     */
    protected function _checkAuthors($authors)
    {
        if (!is_array($authors) && !($authors instanceof Iterator)) {
            $this->fail('$authors must be Array or Iterator.');
        }

        foreach ($authors as $author) {
            $this->assertType('Author', $author);
            $this->assertNotNull($author->id());
            $this->_checkAuthor($this->_queryAuthor($author->id()), $author);
        }
    }

    /**
     * 检查一组 Content 对象
     */
    protected function _checkContents($contents)
    {
        if (!is_array($contents) && !($contents instanceof Iterator)) {
            $this->fail('$contents must be Array or Iterator.');
        }

        foreach ($contents as $content) {
            $this->assertType('Content', $content);
            $this->assertNotNull($content->id());
            $this->_checkContent($this->_queryContent($content->id()), $content);
        }
    }
    
    /**
     * 检查 Author 对象是否和数据库记录相等
     */
    protected function _checkAuthor(array $row, $author)
    {
        $this->assertType('Author', $author);
        $this->assertEquals($row['author_id'], $author->id());
        $this->assertEquals($row['name'], $author->name);
    }


    /**
     * 检查 Content 对象是否和数据库记录相等
     */
    protected function _checkContent(array $row, $content)
    {
        $this->assertType('Content', $content);
        $this->assertEquals($row['content_id'], $content->id());
        $this->assertEquals($row['title'], $content->title);
    }
    
    /**
     * 从包含值的数组中随机返回其中一个
     */
    protected function _getRandID(array $id_list)
    {
        $count = count($id_list);
        $pos = mt_rand(0, $count - 1);
        return $id_list[$pos];
    }

    /**
     * 创建指定数量的 Author 对象，并返回这些对象的 ID
     */
    protected function _createAuthors($count)
    {
        $return = array();
        for ($i = 0; $i < $count; $i++) {
            $author = new Author(array(
                'name' => 'new author - ' . mt_rand(),
            ));
            $author->save();
            $return[] = $author->id();
        }
        return $return;
    }

    /**
     * 查询 Author 对象记录总数
     */
    protected function _queryAuthorsCount()
    {
        return $this->_queryCount('authors');
    }
    
    /**
     * 查询 Content 对象记录总数
     */
    protected function _queryContentsCount()
    {
        return $this->_queryCount('contents');
    }

    /**
     * 查询指定数据表的记录总数
     */
    protected function _queryCount($table_name)
    {
        $prefix = $this->_conn->getTablePrefix();
        $sql = "SELECT COUNT(*) FROM {$prefix}{$table_name}";
        return intval($this->_conn->getOne($sql));
    }

    /**
     * 查询指定 id 的 Author 对象记录
     */
    protected function _queryAuthor($id)
    {
        return $this->_queryRow('authors', 'author_id', $id);
    }
    
    /**
     * 查询指定 id 的 Content 对象记录
     */
    protected function _queryContent($id)
    {
        return $this->_queryRow('contents', 'content_id', $id);
    }

    /**
     * 查询指定数据表，指定键值的记录
     */
    protected function _queryRow($table_name, $idname, $id)
    {
        $prefix = $this->_conn->getTablePrefix();
        $sql = "SELECT * FROM {$prefix}{$table_name} WHERE {$idname} = ?";
        return $this->_conn->getRow($sql, array($id));
    }

}

