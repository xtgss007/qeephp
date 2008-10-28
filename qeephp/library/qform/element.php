<?php
// $Id$

/**
 * @file
 * 定义 QForm_Element 类
 *
 * @ingroup form
 *
 * @{
 */

/**
 * QForm_Element 封装了一个表单元件
 *
 * 一个表单由多个元件组成。一个元件可以是带有值的表单输入框，也可以是装饰性质的内容。
 * 表单元件是否具有值，是由表单元件的数据绑定属性决定的。
 * 如果一个表单元件允许数据绑定，那么这个表单元件就具有值。
 *
 * 带有值的表单元件可以通过 setValue() 方法来指定元件值。
 * 并且在设置元件值时，会运行为该指定元件的过滤器和验证器，对元件值进行过滤和验证。
 * 如果验证失败，表单元件的 valid() 方法将返回 false。
 *
 * 最终，可以通过 getValue() 方法获得元件的值。
 *
 * 通常，开发者首先构造一个 QForm 对象，然后通过 add() 方法来添加表单元件对象。例如：
 *
 * <code>
 * $form = new QForm();
 * $form->add('username', 'textbox');
 * $form->add('email, 'textbox');
 * $form->add('nickname', 'textbox');
 *
 * // 另一种使用 add() 方法的方式
 * $form->add(new QForm_Element('address', 'textbox'));
 * $form->add(new QForm_Element('description', 'memo'));
 * </code>
 */
class QForm_Element extends QForm_Item_Abstract
{
    /**
     * 指示是否允许数据绑定
     *
     * @var boolean
     */
    protected $_bind_enabled = true;

    /**
     * 过滤器链
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * 验证规则
     *
     * @var array
     */
    protected $_validations = array();

    /**
     * 元件值
     *
     * @var mixed
     */
    protected $_value;

    /**
     * 指示值是否已经改变
     *
     * @var boolean
     */
    protected $_value_changed = true;

    /**
     * 当前表单元件对应的 UI 控件
     *
     * @var QUI_Control_Abstract
     */
    protected $_ctl;

    /**
     * 设置值时的回调方法
     *
     * @var callback
     */
    protected $_on_set_value_callback;

    /**
     * 读取值时的回调方法
     *
     * @var callback
     */
    protected $_on_get_value_callback;

    /**
     * 构造函数
     *
     * @param string $id
     * @param string $type
     * @param array $props
     * @param boolean $bind_enabled
     */
    function __construct($id, $type, array $props = null, $bind_enabled = true)
    {
        parent::__construct($id, $type,$props);
        $this->_bind_enabled = $bind_enabled;
    }

    /**
     * 返回元件值
     *
     * @return mixed
     */
    function getValue()
    {
        if ($this->_value_changed)
        {
            $this->validate(false);
        }

        if (!is_null($this->_on_get_value_callback))
        {
            return call_user_func($this->_on_get_value_callback, $this->_value);
        }
        else
        {
            return $this->_value;
        }
    }

    /**
     * 指定读取值时，要调用的回调函数
     *
     * @param callback $callback
     *
     * @return QForm_Element
     */
    function onGetValue($callback)
    {
        $this->_on_get_value_callback = $callback;
        return $this;
    }

    /**
     * 设置元件值
     *
     * @param mixed $value
     *
     * @return QForm_Element
     */
    function setValue($value)
    {
        if ($this->_bind_enabled)
        {
            $this->_value_changed = true;
            if (!is_null($this->_on_set_value_callback))
            {
                $this->_value = call_user_func($this->_on_set_value_callback, $value);
            }
            else
            {
                $this->_value = $value;
            }
            $this->filter();
        }
        return $this;
    }

    /**
     * 指定设置值时，要调用的回调函数
     *
     * @param callback $callback
     *
     * @return QForm_Element
     */
    function onSetValue($callback)
    {
        $this->_on_set_value_callback = $callback;
        return $this;
    }

    /**
     * 对元件值进行过滤
     *
     * @return QForm_Element
     */
    function filter()
    {
        if (!empty($this->_filters))
        {
            $this->_value = QFilter::filters($this->_value, $this->_filters);
        }
    }

    /**
     * 添加过滤器
     *
     * @param mixed $filter
     *
     * @return QForm_Element
     */
    function addFilter($filter)
    {
        $this->_filters[] = $filter;
        return $this;
    }

    /**
     * 移除所有过滤器
     *
     * @return QForm_Element
     */
    function removeAllFilters()
    {
        $this->_filters = array();
        return $this;
    }

    /**
     * 验证元件值，如果验证失败，并且 $throw 参数为 true，则抛出异常
     *
     * @param boolean $throw
     *
     * @return QForm_Element
     */
    function validate($throw = true)
    {
        $this->_value_changed = false;
        $error = array();

        if (!empty($this->_validations) && is_array($this->_validations))
        {
            reset($this->_validations);
            $this->_value_is_valid = true;
            foreach ($this->_validations as $rule)
            {
                list($validation, $args, $msg) = $rule;
                array_unshift($args, $this->_value);
                if (!QValidator::validateByArgs($validation, $args))
                {
                    $this->_value_is_valid = false;
                    $error[] = $msg;
                }
            }
        }
        else
        {
            $this->_value_is_valid = true;
        }

        if ($this->_value_is_valid)
        {
            $this->_validate_messages = null;
        }
        else
        {
            $this->_validate_messages = $error;
            if ($throw)
            {
                throw new QValidator_ValidateFailedException(array($this->id() => $error), array($this->id() => $this->_value));
            }
        }
    }

    /**
     * 添加验证器
     *
     * @param mixed $check
     * @param string $msg
     *
     * @return QForm_Element
     */
    function addValidation($check, $msg = '')
    {
        $args = func_get_args();
        $validation = array_shift($args);
        $msg = array_pop($args);
        $this->_validations[] = array($validation, $args, $msg);
        return $this;
    }

    /**
     * 返回所有验证器的信息
     *
     * @return array
     */
    function getAllValidations()
    {
        return $this->_validations;
    }

    /**
     * 移除所有验证器
     *
     * @return QForm_Element
     */
    function removeAllValidations()
    {
        $this->_validations = array();
        return $this;
    }

    /**
     * 检查是否允许数据绑定
     *
     * @return boolean
     */
    function bindEnabled()
    {
        return $this->_bind_enabled;
    }

    /**
     * 设置是否允许数据绑定
     *
     * @param boolean $bind_enabled
     *
     * @return QForm_Element
     */
    function setBindEnabled($bind_enabled)
    {
        $this->_bind_enabled = $bind_enabled;
        return $this;
    }

    /**
     * 指示当前元素不是一个组
     *
     * @return boolean
     */
    function isGroup()
    {
        return false;
    }

    /**
     * 获得元件对应的 UI 控件对象实例
     *
     * @param QContext $context
     *
     * @return QUI_Control_Abstract
     */
    function ctl(QContext $context = null)
    {
        if (is_null($this->_ctl))
        {
            $this->_event(self::BEFORE_CREATE_CTL);
            $this->_ctl = QUI::control($context, $this->_type, $this->_id);
            $this->_event(self::AFTER_CREATE_CTL, $this->_ctl);
        }

        return $this->_ctl;
    }

    /**
     * 渲染元件
     *
     * @param QContext $context
     * @param boolean $return
     *
     * @return string
     */
    function render(QContext $context, $return = false)
    {
        $this->_event(self::BEFORE_RENDER_CTL);

        $props = $this->_props;
        $props['value'] = $this->getValue();
        $output = $this->ctl($context)->setAttribs($props)->render(true);

        $this->_event(self::AFTER_RENDER_CTL);
        if (!$return)
        {
            echo $output;
            $output = null;
        }
        return $output;
    }
}

/**
 * @}
 */

