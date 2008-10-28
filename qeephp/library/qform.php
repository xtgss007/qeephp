<?php
// $Id$

/**
 * @file
 * 定义 QForm 类
 *
 * @ingroup form
 *
 * @{
 */

/**
 * QForm 实现了一个灵活的表单模型
 */
class QForm extends QForm_Group
{
    const BEFORE_RENDER_FORM = 'before_render_form';
    const AFTER_RENDER_FORM  = 'after_render_form';

    /**
     * 视图名称
     *
     * @var string
     */
    protected $_viewname;

    /**
     * 视图适配器
     *
     * @var QView_Adapter_Abstract
     */
    protected $_view_adapter;

    /**
     * 渲染表单要使用的视图适配器类，不指定则以全局设置为准
     *
     * @var string
     */
    protected $_view_adapter_class;

    /**
     * 构造函数
     *
     * @param string $id
     * @param string $action
     * @param string $method
     * @param arary $props
     */
    function __construct($id = 'form1', $action = null, $method = 'post', array $props = null)
    {
        parent::__construct($id, 'form', $props);
        $this->action = $action;
        $this->method = $method;
        $this->_is_root_group = true;
    }

    /**
     * 从配置数组直接添加表单元素
     *
     * @param array $config
     * @param boolean $nested
     *
     * @return QForm
     */
    function loadFromConfig(array $config, $nested = false)
    {
        $validations = !empty($config['_validations']) ? (array)$config['_validations'] : array();
        unset($config['_validations']);
        $this->loadValidationsFromConfig($validations);

        if ($nested)
        {
            foreach ($config as $group_name => $elements)
            {
                $group = new QForm_Group($group_name);
                foreach ($elements as $id => $element_define)
                {
                    $element = new QForm_Element($id, $element_define['type'], $element_define['props']);
                    if (isset($element_define['bind_enabled']))
                    {
                        $element->setBindEnabled($element_define['bind_enabled']);
                    }
                    $group->add($element);
                }
                $this->add($group);
            }
        }
        else
        {
            foreach ($config as $id => $element_define)
            {
                $element = new QForm_Element($id, $element_define['type'], $element_define['props']);
                if (isset($element_define['bind_enabled']))
                {
                    $element->setBindEnabled($element_define['bind_enabled']);
                }
                $this->add($element);
            }
        }

        return $this;
    }

    /**
     * 设置表单 ID
     *
     * @param string $id
     *
     * @return QForm
     */
    function setID($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * 设置视图名称
     *
     * @param string $viewname
     *
     * @return QForm
     */
    function setViewName($viewname)
    {
        $this->_viewname = $viewname;
        return $this;
    }

    function getValue()
    {
        $this->_before_validate();
        $data = parent::getValue();
        if ($this->_value_is_valid)
        {
            $this->_validate_messages = array();
        }
        $this->_after_validate($data);

        if (!empty($this->_validate_messages))
        {
            $this->_value_is_valid = false;
            throw new QValidator_ValidateFailedException($this->_validate_messages, $data);
        }
        else
        {
            $this->_validate_messages = null;
        }

        return $data;
    }

    /**
     * 用指定的表单视图类渲染表单
     *
     * @param string $class_name
     * @param QContext $context
     * @param boolean $return
     */
    function renderWithLayoutClass($class_name, QContext $context, $return = false)
    {
        return call_user_func(array($class_name, 'render'), $context, $this, $return);
    }

    /**
     * 渲染表单
     *
     * @param QContext $context
     * @param boolean $return
     *
     * @return string
     */
    function render(QContext $context, $return = false)
    {
        $this->_event(self::BEFORE_RENDER_FORM, $this);

        if (!is_object($this->_view_adapter))
        {
            $adapter_class = is_null($this->_view_adapter_class)
                           ? $context->getIni('view_adapter')
                           : $this->_view_adapter_class;
            $adapter_obj_id = "form_{$adapter_class}";

            if (Q::isRegistered($adapter_obj_id))
            {
                $adapter = Q::registry($adapter_obj_id);
            }
            else
            {
                $adapter = new $adapter_class($context);
                Q::register($adapter, $adapter_obj_id);
            }
        }
        else
        {
            $adapter = $this->_view_adapter;
        }

        /* @var $adapter QView_Adapter_Abstract */
        $viewname = !empty($this->_viewname) ? $this->_viewname : 'form';
        $filename = QView::getViewLayoutsFilename($adapter->context, $adapter, $viewname);

        $adapter->clear();
        $adapter->assign('form', $this);
        $adapter->assign('_ctx', $context);
        $output = $adapter->fetch($filename);

        $this->_event(self::AFTER_RENDER_FORM, $this);
        if (!$return)
        {
            echo $output;
            $output = null;
        }
        return $output;
    }


    protected function _before_validate()
    {
    }

    protected function _after_validate(array $data)
    {
    }

    protected function _validateFailed($id, $msg)
    {
        if ($this->_value_is_valid)
        {
            $this->_value_is_valid = false;
            $this->_validate_messages = array();
        }

        if (!is_array($msg))
        {
            $msg = array($msg);
        }
        if (!isset($this->_validate_messages[$id]))
        {
            $this->_validate_messages[$id] = $msg;
        }
        else
        {
            $this->_validate_messages[$id] = array_merge($this->_validate_messages[$id], $msg);
        }
    }

}

