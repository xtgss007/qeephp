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
 * 定义 Helper_Form 类
 *
 * @package helper
 * @version $Id: form_helper.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * Helper_Form 为控制器提供处理表单的辅助方法
 *
 * @package helper
 */
class Helper_Form
{
    /**
     * QController_Abstract 实例
     *
     * @var QController_Abstract
     */
    protected $controller;

    /**
     * 构造函数
     *
     * @param QController_Abstract $controller
     */
    function __construct(QController_Abstract $controller = null)
    {
        $this->controller = $controller;
    }

    /**
     * 根据特定 Model 类名，返回包含该 Model 所有属性名的数组
     *
     * @param string $model_class
     * @param QRequest $request
     *
     * @return array
     */
    function prepare($model_class, QRequest $request = null)
    {
        $meta = QDB_ActiveRecord_Meta::getInstance($model_class);
        $form = array();
        $pk = $meta->fields2prop[$meta->table->pk];
        if ($request) {
            foreach ($meta->fields2prop as $prop_name) {
                if ($prop_name == $pk) { continue; }
                $form[$prop_name] = $request->getPost($prop_name);
            }
        } else {
            foreach ($meta->fields2prop as $prop_name) {
                if ($prop_name == $pk) { continue; }
                $form[$prop_name] = null;
            }
        }
        $form['_pk'] = $pk;
        return $form;
    }
}
