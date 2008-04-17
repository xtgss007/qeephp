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
 * 定义 QDB_ActiveRecord_Select 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Select 类封装了 ActiveRecord 的查询操作
 *
 * @package database
 */
class QDB_ActiveRecord_Select extends QDB_Select_Abstract
{
    /**
     * 发起查询的 ActiveRecord meta object
     *
     * @var QDB_ActiveRecord_Meta
     */
    protected $meta;

    /**
     * 构造函数
     *
     * @param QDB_ActiveRecord_Meta $meta
     * @param array $where
     */
    function __construct(QDB_ActiveRecord_Meta $meta, array $where)
    {
        parent::__construct();
        $this->meta = $meta;
        if (!empty($where)) {
            call_user_func_array(array($this, 'where'), $where);
        }
    }

    function whereArgs($where, array $args)
    {
        return $this;
    }

    function havingArgs($where, array $args)
    {
        return $this;
    }

    function query()
    {
        return $this;
    }

    /**
     * 设置关联查询时要使用的关联
     *
     * $links 可以是数组或字符串。如果 $links 为 null，则表示不查询关联。
     *
     * @param array|string $links
     *
     * @return QDB_Select_Abstract
     */
    function links($links)
    {
        if (empty($links)) {
            $this->links = array();
        } else {
            $links = Q::normalize($links);
            $enabled = array();
            foreach ($links as $link) {
                if (isset($this->links[$link])) {
                    $enabled[$link] = $this->links[$link];
                }
            }
            $this->links = $enabled;
        }
        return $this;
    }

}
