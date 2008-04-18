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
 * 定义 QDB_Result_Abstract 类
 *
 * @package database
 * @version $Id: abstract.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB_Result_Abstract 是封装查询结果对象的抽象基础类
 *
 * @package database
 */
abstract class QDB_Result_Abstract
{
    /**
     * 指示返回结果集的形式
     *
     * @var const
     */
    public $fetch_mode;

    /**
     * 查询句柄
     *
     * @var resource
     */
    protected $handle = null;

    /**
     * 构造函数
     *
     * @param resource $handle
     * @param const $fetch_mode
     */
    function __construct($handle, $fetch_mode)
    {
        if (is_resource($handle) || is_object($handle)) {
            $this->handle = $handle;
        }
        $this->fetch_mode = $fetch_mode;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        $this->free();
    }

    /**
     * 返回句柄
     *
     * @return resource
     */
    function handle()
    {
        return $this->handle;
    }

    /**
     * 指示句柄是否有效
     *
     * @return boolean
     */
    function valid()
    {
        return $this->handle != null;
    }

    /**
     * 释放句柄
     */
    abstract function free();

    /**
     * 从查询句柄提取一条记录
     *
     * @return array
     */
    abstract function fetchRow();

    /**
     * 从查询句柄中提取记录集
     *
     * @return array
     */
    function fetchAll()
    {
        $rowset = array();
        while (($row = $this->fetchRow())) {
            $rowset[] = $row;
        }
        return $rowset;
    }

    /**
     * 从查询句柄提取一条记录，并返回该记录的第一个字段
     *
     * @return mixed
     */
    function fetchOne()
    {
        $row = $this->fetchRow();
        return $row ? reset($row) : null;
    }

    /**
     * 从查询句柄提取记录集，并返回包含每行指定列数据的数组，如果 $col 为 0，则返回第一列
     *
     * @param int $col
     *
     * @return array
     */
    function fetchCol($col = 0)
    {
        $mode = $this->fetch_mode;
        $this->fetch_mode = QDB::fetch_mode_array;
        $cols = array();
        while (($row = $this->fetchRow())) {
            $cols[] = $row[$col];
        }
        $this->fetch_mode = $mode;
        return $cols;
    }

    /**
     * 返回记录集和指定字段的值集合，以及以该字段值作为索引的结果集
     *
     * 假设数据表 posts 有字段 post_id 和 title，并且包含下列数据：
     *
     * <code>
     * +---------+-----------------------+
     * | post_id | title                 |
     * +---------+-----------------------+
     * |       1 | It's live             |
     * +---------+-----------------------+
     * |       2 | QeePHP Recipes        |
     * +---------+-----------------------+
     * |       7 | QeePHP User manual    |
     * +---------+-----------------------+
     * |      15 | QeePHP Quickstart     |
     * +---------+-----------------------+
     * </code>
     *
     * 现在我们查询 posts 表的数据，并以 post_id 的值为结果集的索引值：
     *
     * example:
     * <code>
     * $sql = "SELECT * FROM posts";
     * $handle = $dbo->execute($sql);
     *
     * $fields_value = array();
     * $ref = array();
     * $rowset = $handle->fetchAllRefby(array('post_id'), $fields_value, $ref);
     * </code>
     *
     * 上述代码执行后，$rowset 包含 posts 表中的全部 4 条记录。
     * 最后，$fields_value 和 $ref 是如下形式的数组：
     *
     * <code>
     *
     * $fields_value = array(
     *     'post_id' => array(1, 2, 7, 15),
     * );
     *
     * $ref = array(
     *     'post_id' => array(
     *          1 => & array(array(...)),
     *          2 => & array(array(...), array(...)),
     *          7 => & array(array(...), array(...)),
     *         15 => & array(array(...), array(...), array(...))
     *     ),
     * );
     * </code>
     *
     * $ref 用 post_id 字段值作为索引值，并且指向 $rowset 中 post_id 值相同的记录。
     * 由于是以引用方式构造的 $ref 数组，因此并不会占用双倍内存。
     *
     * @param array $fields
     * @param array $fields_value
     * @param array $ref
     * @param boolean $clean_up
     *
     * @return array
     */
    function fetchAllRefby(array $fields, & $fields_value, & $ref, $clean_up)
    {
        $ref = $fields_value = $data = array();
        $offset = 0;

        if ($clean_up) {
            while (($row = $this->fetchRow())) {
                $data[$offset] = $row;
                foreach ($fields as $field) {
                    $field_value = $row[$field];
                    $fields_value[$field][$offset] = $field_value;
                    $ref[$field][$field_value][] =& $data[$offset];
                    unset($data[$offset][$field]);
                }
                $offset++;
            }
        } else {
            while (($row = $this->fetchRow())) {
                $data[$offset] = $row;
                foreach ($fields as $field) {
                    $field_value = $row[$field];
                    $fields_value[$field][$offset] = $field_value;
                    $ref[$field][$field_value][] =& $data[$offset];
                }
                $offset++;
            }
        }

        return $data;
    }
}
