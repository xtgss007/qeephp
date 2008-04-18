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
 * 定义 QColl 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QColl 实现了一个类型安全的集合
 *
 * @package core
 */
class QColl implements Iterator, ArrayAccess, Countable
{
    /**
     * 集合元素的类型
     *
     * @var string
     */
    private $type;

    /**
     * 保存元素的数组
     *
     * @var array
     */
    private $coll = array();

    /**
     * 构造函数
     *
     * @param string $type
     */
    function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * 从数组创建一个集合
     *
     * @param array $arr
     * @param string $type
     *
     * @return QColl
     */
    static function createFromArray(array $arr, $type)
    {
        $coll = new QColl($type);
        foreach ($arr as $item) {
            $coll[] = $item;
        }
        return $coll;
    }

    /**
     * 遍历集合中的所有对象，返回包含特定属性值的数组
     *
     * @param string $field
     *
     * @return array
     */
    function values($field)
    {
        $return = array();
        foreach (array_keys($this->coll) as $offset) {
            if (isset($this->coll[$offset]->{$field})) {
                $return[] = $this->coll[$offset]->{$field};
            }
        }
        return $return;
    }

    /**
     * 检查指定索引的元素是否存在
     *
     * @param mixed $offset
     *
     * @return boolean
     */
    function offsetExists($offset)
    {
        return isset($this->coll[$offset]);
    }

    /**
     * 返回指定索引的元素
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    function offsetGet($offset)
    {
        if (isset($this->coll[$offset])) {
            return $this->coll[$offset];
        }
        // LC_MSG: Undefined offset: "%s".
        throw new QException(__('Undefined offset: "%s".', $offset));
    }

    /**
     * 设置指定索引的元素
     *
     * @param mixed $offset
     * @param mixed $value
     */
    function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $offset = count($this->coll);
        }
        if (is_array($value)) {
            foreach (array_keys($value) as $key) {
                $this->checkType($value[$key]);
            }
        } else {
            $this->checkType($value);
        }
        $this->coll[$offset] = $value;
    }

    /**
     * 注销指定索引的元素
     *
     * @param mixed $offset
     */
    function offsetUnset($offset)
    {
        unset($this->coll[$offset]);
    }

    /**
     * 返回当前位置的元素
     *
     * @return mixed
     */
    function current()
    {
        return current($this->coll);
    }

    /**
     * 返回遍历时的当前索引
     *
     * @return mixed
     */
    function key()
    {
        return key($this->coll);
    }

    /**
     * 遍历下一个元素
     *
     * @return mixed
     */
    function next()
    {
        return next($this->coll);
    }

    /**
     * 重置遍历索引，并返回第一个元素
     *
     * @return mixed
     */
    function rewind()
    {
        return reset($this->coll);

    }

    /**
     * 判断是否是调用了 rewind() 或 next() 之后获得的有效元素
     *
     * @return boolean
     */
    function valid()
    {
        return current($this->coll) !== false;
    }

    /**
     * 返回元素总数
     *
     * @return int
     */
    function count()
    {
        return count($this->coll);
    }

    /**
     * 将所有内部元素返回为对象
     *
     * @return array
     */
    function toArray()
    {
        $arr = array();
        foreach ($this->coll as $obj) {
            $arr[] = $obj->toArray();
        }
        return $arr;
    }

    /**
     * 检查值是否符合类型要求
     *
     * @param mixed $value
     */
    protected function checkType($value)
    {
        if (is_object($value)) {
            if ($value instanceof $this->type) {
                return;
            }
            $type = get_class($value);
        } elseif (gettype($value) == $this->type) {
            return;
        } else {
            $type = gettype($value);
        }
        // LC_MSG: Type mismatch. expected "%s", but actual is "%s".
        throw new QException(__('Type mismatch. expected "%s", but actual is "%s".', $this->type, $type));
    }
}
