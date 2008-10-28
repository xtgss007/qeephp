<?php

abstract class QForm_Item_Abstract
{
    /**
     * 事件类型
     */
    // 创建控件之间
    const BEFORE_CREATE_CTL     = 'before_create_ctl';
    // 创建控件之后
    const AFTER_CREATE_CTL      = 'after_create_ctl';
    // 渲染控件之前
    const BEFORE_RENDER_CTL     = 'before_render_ctl';
    // 渲染控件之后
    const AFTER_RENDER_CTL      = 'after_render_ctl';

    /**
     * 事件回调函数
     *
     * @var array
     */
    protected $_callbacks = array();

    /**
     * 元件的类型
     *
     * @var mixed
     */
    protected $_type;

    /**
     * 元件的 ID
     *
     * @var string
     */
    protected $_id;

    /**
     * 元件的属性
     *
     * @var array
     */
    protected $_props = array();

    /**
     * 元件值是否有效
     *
     * @var boolean
     */
    protected $_value_is_valid = false;

    /**
     * 验证失败时保存的错误信息
     *
     * @var array
     */
    protected $_validate_messages;

    /**
     * 构造函数
     *
     * @param string $id
     * @param string $type
     * @param array $props
     */
    function __construct($id, $type, array $props = null)
    {
        $this->_id = $id;
        $this->_type = $type;
        if (is_array($props))
        {
            $this->setProps($props);
        }
    }

    /**
     * 返回元件的 ID
     *
     * @return string
     */
    function id()
    {
        return $this->_id;
    }

    /**
     * 返回元件的类型
     *
     * @return string
     */
    function type()
    {
        return $this->_type;
    }

    /**
     * 确认元件值是否有效
     *
     * @return boolean
     */
    function isValid()
    {
        return $this->_value_is_valid;
    }

    /**
     * 返回元件值
     *
     * @return mixed
     */
    abstract function getValue();

    /**
     * 设置元件值
     *
     * @param mixed $value
     *
     * @return QForm_Element
     */
    abstract function setValue($value);

    /**
     * 获得验证失败后的错误信息，如果尚未验证或验证通过，则返回 null
     *
     * @return array
     */
    function getValidateMessages()
    {
        return $this->_validate_messages;
    }

    /**
     * 添加事件处理函数
     *
     * @param int $event
     * @param callback $callback
     *
     * @return QForm_Element
     */
    function addEventHandler($event, $callback)
    {
        $this->_callbacks[$event][] = $callback;
        return $this;
    }

    /**
     * 指示当前元素不是一个组
     *
     * @return boolean
     */
    abstract function isGroup();

    /**
     * 设置元件的多个属性
     *
     * @param array $props
     *
     * @return QForm_Element
     */
    function setProps(array $props)
    {
        if (array_key_exists('value', $props))
        {
            $this->setValue($props['value']);
            unset($props['value']);
        }
        $this->_props = array_merge($this->_props, $props);
        return $this;
    }

    /**
     * 删除多个属性
     *
     * @param string|array $props
     *
     * @return QForm_Element
     */
    function unsetProps($props)
    {
        $props = Q::normalize($props);
        foreach ($props as $prop)
        {
            unset($this->_props[$prop]);
        }
        if (in_array('value', $props))
        {
            $this->setValue(null);
        }

        return $this;
    }

    /**
     * 设置元件的指定属性
     *
     * @param string $prop
     * @param mixed $value
     *
     * @return QForm_Element
     */
    function set($prop, $value)
    {
        if ($prop == 'value')
        {
            $this->setValue($value);
        }
        else
        {
            $this->_props[$prop] = $value;
        }
        return $this;
    }

    /**
     * 获得指定属性值
     *
     * @param string $prop
     * @param mixed $default
     *
     * @return mixed
     */
    function get($prop, $default = null)
    {
        if ($prop == 'value')
        {
            return $this->getValue();
        }
        else
        {
            return array_key_exists($prop, $this->_props) ? $this->_props[$prop] : $default;
        }
    }

    /**
     * 动态的 getter 和 setter
     *
     * @param string $method_name
     * @param array $args
     *
     * @return mixed
     */
    function __call($method_name, array $args)
    {
        $prefix = substr($method_name, 0, 3);
        if ($prefix == 'set')
        {
            return $this->set(strtolower(substr($method_name, 3)), reset($args));
        }

        if ($prefix == 'get')
        {
            return $this->get(strtolower(substr($method_name, 3)), reset($args));
        }

        // LC_MSG: 没有定义的方法 "%s".
        throw new QForm_Exception(__('没有定义的方法 "%s".', $method_name));
    }

    /**
     * 魔法方法，设置元件的指定属性
     *
     * @param string $prop
     * @param mixed $value
     */
    function __set($prop, $value)
    {
        return $this->set($prop, $value);
    }

    /**
     * 魔法方法，获得指定属性值
     *
     * @param string $prop
     *
     * @return mixed
     */
    function __get($prop)
    {
        return $this->get($prop);
    }

    /**
     * 指定指定事件的处理函数
     *
     * @param int $event
     *
     * @return mixed
     */
    protected function _event($event)
    {
        if (isset($this->_callbacks[$event]))
        {
            $args = func_get_args();
            array_shift($args);
            array_unshift($args, $this);

            foreach ($this->_callbacks[$event] as $callback)
            {
                call_user_func_array($callback, $args);
            }
        }
    }
}
