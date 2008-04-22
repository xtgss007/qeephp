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
 * 定义 QDB_Cond 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Cond 类封装复杂的查询条件
 *
 * @package database
 */
class QDB_Cond
{
    /**
     * 构成查询条件的各个部分
     *
     * @var array
     */
    protected $_parts = array();

    /**
     * 构造函数
     */
    function __construct()
    {
        $args = func_get_args();
        if (!empty($args)) {
            $this->_parts[] = array($args, true);
        }
    }

    /**
     * 直接创建一个 QDB_Cond 对象
     *
     * @param string|array|QDB_Expr|QDB_Cond $cond
     * @param array $cond_args
     *
     * @return QDB_Cond
     */
    static function createCronDirect($cond, array $cond_args = null)
    {
        $c = new QDB_Cond();
        array_unshift($cond_args, $cond);
        return $c->appendDirect($cond_args);
    }

    /**
     * 直接添加一个查询条件
     *
     * @param array $args
     * @param bool $bool
     *
     * @return QDB_Cond
     */
    function appendDirect(array $args, $bool = true)
    {
        $this->_parts[] = array($args, $bool);
        return $this;
    }

    /**
     * 添加一个新条件，与其他条件之间使用 AND 布尔运算符连接
     *
     * @return QDB_Cond
     */
    function andCond()
    {
        $this->_parts[] = array(func_get_args(), true);
        return $this;
    }

    /**
     * 添加一个新条件，与其他条件之间使用 OR 布尔运算符连接
     *
     * @return QDB_Cond
     */
    function orCond()
    {
        $this->_parts[] = array(func_get_args(), false);
        return $this;
    }

    /**
     * 格式化为字符串
     *
     * @param QDB_Adapter_Abstract $conn
     * @param string $table_name
     */
    function formatToString($conn, $table_name = null)
    {
        $sql = '';

        $last = '';
        while (list($args, $bool) = each($this->_parts)) {
            $cond = reset($args);
            array_shift($args);
            if ($cond instanceof QDB_Cond || $cond instanceof QDB_Expr) {
                $part = $cond->formatToString($conn, $table_name);
            } elseif (is_array($cond)) {
                $part = array();
                foreach ($cond as $field => $value) {
                    $part[] = $conn->qfield($field, $table_name) . '=' . $conn->qstr($value);
                }
                $part = '(' . implode(' AND ', $part) . ')';
            } else {
                $style = (strpos($cond, '?') === false) ? QDB::PARAM_CL_NAMED : QDB::PARAM_QM;
                $part = '(' . $conn->qinto($conn->qfieldsInto($cond, $table_name), $args, $style) . ')';
            }

            if ($bool) {
                $last = ' AND ';
            } else {
                $last = ' OR ';
            }
            $sql .= $part . $last;
        }

        return '(' . substr($sql, 0, - strlen($last)) . ')';
    }
}
