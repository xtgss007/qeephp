<?php
// $Id$

/**
 * @file
 * 定义 QColl 类
 *
 * @ingroup core
 *
 * @{
 */

/**
 * QColl 实现了一个类型安全的集合
 */
class QColl implements Iterator, ArrayAccess, Countable
{
    /**
     * 可用的事件
     */
    const ON_SET    = 'on_set';
    const ON_UNSET  = 'on_unset';

    /**
     * 集合元素的类型
     *
     * @var string
     */
    protected $_type;

    /**
     * 保存元素的数组
     *
     * @var array
     */
    protected $_coll = array();

    /**
     * 事件回调方法
     *
     * @var array
     */
    protected $_event_handlers = array
    (
        self::ON_SET => array(),
        self::ON_UNSET => array(),
    );

    /**
     * 构造函数
     *
     * @param string $type
     */
    function __construct($type)
    {
        $this->_type = $type;
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
        foreach ($arr as $item)
        {
            $coll[] = $item;
        }
        return $coll;
    }

    /**
     * 遍历集合中的所有对象，返回包含特定属性值的数组
     *
     * @param string $prop_name
     *
     * @return array
     */
    function values($prop_name)
    {
        $return = array();
        foreach (array_keys($this->_coll) as $offset)
        {
            if (isset($this->_coll[$offset]->{$prop_name}))
            {
                $return[] = $this->_coll[$offset]->{$prop_name};
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
        return isset($this->_coll[$offset]);
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
        if (isset($this->_coll[$offset]))
        {
            return $this->_coll[$offset];
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
        if (is_null($offset))
        {
            $offset = count($this->_coll);
        }
        if (is_array($value))
        {
            foreach (array_keys($value) as $key)
            {
                $this->_checkType($value[$key]);
            }
        }
        else
        {
            $this->_checkType($value);
        }
        $this->_coll[$offset] = $value;
        $this->_event(self::ON_SET, array($value));
    }

    /**
     * 注销指定索引的元素
     *
     * @param mixed $offset
     */
    function offsetUnset($offset)
    {
        if (isset($this->_coll[$offset]))
        {
            $this->_event(self::ON_UNSET, array($this->_coll[$offset]));
        }
        unset($this->_coll[$offset]);
    }

    /**
     * 返回当前位置的元素
     *
     * @return mixed
     */
    function current()
    {
        return current($this->_coll);
    }

    /**
     * 返回遍历时的当前索引
     *
     * @return mixed
     */
    function key()
    {
        return key($this->_coll);
    }

    /**
     * 遍历下一个元素
     *
     * @return mixed
     */
    function next()
    {
        return next($this->_coll);
    }

    /**
     * 重置遍历索引，返回第一个元素
     *
     * @return mixed
     */
    function rewind()
    {
        return reset($this->_coll);
    }

    /**
     * 判断是否是调用了 rewind() 或 next() 之后获得的有效元素
     *
     * @return boolean
     */
    function valid()
    {
        return current($this->_coll) !== false;
    }

    /**
     * 返回元素总数
     *
     * @return int
     */
    function count()
    {
        return count($this->_coll);
    }

    /**
     * 返回包含所有元素内容的数组
     *
     * @param int $recursion
     *
     * @return array
     */
    function toArray($recursion = 99)
    {
        $arr = array();
        foreach ($this->_coll as $obj)
        {
            $arr[] = $obj->toArray($recursion);
        }
        return $arr;
    }

    /**
     * 返回包含所有元素内容的 JSON 字符串
     *
     * @param int $recursion
     *
     * @return string
     */
    function toJSON($recursion = 99)
    {
        return json_encode($this->toArray($recursion));
    }

    /**
     * 查找符合指定键值的元素，没找到返回 NULL
     *
     * @param string $key
     * @param mixed $needle
     *
     * @return mixed
     */
    function search($key, $needle)
    {
        foreach ($this->_coll as $item)
        {
            if ($item->{$key} == $needle) { return $item; }
        }
        return null;
    }

    /**
     * 添加一个事件回调方法
     *
     * @param int $event
     * @param callback $callback
     * @param array $custom_parameters
     *
     * @return QColl
     */
    function addEventHandler($event, $callback, array $custom_parameters = array())
    {
        $this->_event_handlers[$event][] = array($callback, $custom_parameters);
        return $this;
    }

    /**
     * 删除一个事件回调方法
     *
     * @param int $event
     * @param callback $callback
     *
     * @return QColl
     */
    function removeEventHandler($event, $callback)
    {
        if (empty($this->_event_handlers[$event]))
        {
            return $this;
        }

        foreach ($this->_event_handlers[$event] as $offset => $arr)
        {
            if ($arr[0] == $callback)
            {
                unset($this->_event_handlers[$event][$offset]);
                return $this;
            }
        }
        return $this;
    }

    /**
     * 引发特定的事件
     *
     * @param int $event
     * @param array $args
     */
    protected function _event($event, array $args)
    {
        foreach ($this->_event_handlers[$event] as $callback)
        {
            foreach ($args as $arg)
            {
                array_push($callback[1], $arg);
            }
            call_user_func_array($callback[0], $callback[1]);
        }
    }

    /**
     * 检查值是否符合类型要求
     *
     * @param mixed $value
     */
    protected function _checkType($value)
    {
        if (is_object($value))
        {
            if ($value instanceof $this->_type)
            {
                return;
            }
            $type = get_class($value);
        }
        elseif (gettype($value) == $this->_type)
        {
            return;
        }
        else
        {
            $type = gettype($value);
        }
        // LC_MSG: Type mismatch. expected "%s", but actual is "%s".
        throw new QException(__('Type mismatch. expected "%s", but actual is "%s".', $this->_type, $type));
    }
}

/**
 * @}
 */
