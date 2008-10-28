<?php
// $Id$

/**
 * @file
 * 定义 QForm_Group 类
 *
 * @ingroup form
 *
 * @{
 */

class QForm_Group extends QForm_Item_Abstract implements Iterator, ArrayAccess
{
    /**
     * 嵌入的元件
     *
     * @var QColl
     */
    protected $_elements;

    /**
     * 是否是根
     *
     * @var boolean
     */
    protected $_is_root_group = false;

    /**
     * 构造函数
     *
     * @param string $id
     * @param string $label
     * @param array $props
     */
    function __construct($id, $label = '', array $props = null)
    {
        parent::__construct($id, 'group', $props);
        $this->_elements = new QColl('QForm_Item_Abstract');
        $this->set('label', !empty($label) ? $label : $id);
    }

    /**
     * 添加一个元件
     *
     * @param QForm_Item_Abstract $element
     *
     * @return QForm_Item_Abstract
     */
    function add(QForm_Item_Abstract $element)
    {
        $this->_elements[$element->id()] = $element;
        return $element;
    }

    /**
     * 返回指定 ID 的子元件
     *
     * @param string $id
     *
     * @return QForm_Item_Abstract
     */
    function element($id)
    {
        return $this->_elements[$id];
    }

    /**
     * 设置数据源
     *
     * @param array $value
     *
     * @return QForm_Group
     */
    function setValue($value)
    {
        foreach ($this->_elements as $id => $element)
        {
            /* @var $element QForm_Item_Abstract */
            if (!$element->isGroup())
            {
                $element->setValue(isset($value[$id]) ? $value[$id] : null);
            }
            else
            {
                $element->setValue($value);
            }
        }
        return $this;
    }

    /**
     * 从数据源中提取表单数据
     *
     * @return array
     */
    function getValue()
    {
        $data = array();
        $this->_validate_messages = array();
        $prefix = ($this->_is_root_group) ? '' : $this->_id . '_';

        foreach ($this->_elements as $id => $element)
        {
            /* @var $element QForm_Item_Abstract */
            $value = $element->getValue();
            if (is_array($value))
            {
                $data = array_merge($data, $value);
            }
            else
            {
                $data[$id] = $value;
            }

            $this->_valid_value = $this->_valid_value && $element->isValid();
            if (!$element->isValid())
            {
                if ($element->isGroup())
                {
                    $this->_validate_messages = array_merge($this->_validate_messages, $element->getValidateMessages());
                }
                else
                {
                    $this->_validate_messages["{$prefix}{$id}"] = $element->getValidateMessages();
                }
            }
        }

        if ($this->_valid_value)
        {
            $this->_validate_messages = null;
        }

        return $data;
    }


    /**
     * 从配置载入验证规则
     *
     * @param array $config
     *
     * @return QForm
     */
    function loadValidationsFromConfig(array $config)
    {
        if (isset($config['_load_from_model']))
        {
            $this->loadValidationsFromModel($config['_load_from_model']);
        }
        unset($config['_load_from_model']);

        return $this->appendValidations($config);
    }

    /**
     * 为指定的元件指定验证规则
     *
     * @param string $id
     * @param array $rules
     *
     * @return QForm
     */
    function appendValidation($id, array $rules)
    {
        if (!isset($this->_elements[$id]))
        {
            return $this;
        }

        $element = $this->element($id);
        /* @var $element QForm_Item_Abstract */
        foreach ($rules as $rule)
        {
            call_user_func_array(array($element, 'addValidation'), $rule);
        }
        return $this;
    }

    /**
     * 添加多个验证规则
     *
     * @param array $validations
     *
     * @return QForm
     */
    function appendValidations(array $validations)
    {
        foreach ($validations as $id => $rules)
        {
            $this->appendValidation($id, $rules);
        }
        return $this;
    }

    /**
     * 从模型载入验证规则
     *
     * @param array|string $models
     *
     * @return QForm
     */
    function loadValidationsFromModel($models)
    {
        $models = Q::normalize($models);

        foreach ($models as $model_class)
        {
            $meta = QDB_ActiveRecord_Meta::instance($model_class);
            $validations = $meta->getAllValidation();
            foreach ($validations as $id => $policy)
            {
                $this->appendValidation($id, $policy['rules']);
            }
        }

        return $this;
    }

    /**
     * 指示当前元素是一个组
     *
     * @return boolean
     */
    function isGroup()
    {
        return true;
    }

    /**
     * 迭代方法：返回当前元件
     *
     * @return QForm_Item_Abstract
     */
    function current()
    {
        return $this->_elements->current();
    }

    /**
     * 迭代方法：返回当前元件的 ID
     *
     * @return string
     */
    function key()
    {
        return $this->_elements->key();
    }

    /**
     * 迭代方法：返回下一个元件
     *
     * @return QForm_Item_Abstract
     */
    function next()
    {
        return $this->_elements->next();
    }

    /**
     * 迭代方法：重置迭代状态，并返回第一个元件
     *
     * @return QForm_Item_Abstract
     */
    function rewind()
    {
        return $this->_elements->rewind();
    }

    /**
     * 迭代方法：迭代状态是否有效
     *
     * @return boolean
     */
    function valid()
    {
        return $this->_elements->valid();
    }

    /**
     * ArrayAccess 接口实现：检查指定键名是否存在
     *
     * @param string $id
     *
     * @return boolean
     */
    function offsetExists($id)
    {
        return isset($this->_elements[$id]);
    }

    /**
     * ArrayAccess 接口实现：取得指定键名的元件
     *
     * @param string $id
     *
     * @return QForm_Item_Abstract
     */
    function offsetGet($id)
    {
        return $this->_elements[$id];
    }

    /**
     * ArrayAccess 接口实现：设置指定键名的元件
     *
     * @param string $id
     * @param QForm_Item_Abstract $value
     */
    function offsetSet($id, $element)
    {
        $this->_elements[$id] = $element;
    }

    /**
     * ArrayAccess 接口实现：删除指定键名的元件
     *
     * @param string $id
     */
    function offsetUnset($id)
    {
        unset($this->_elements[$id]);
    }
}

/**
 * @}
 */
