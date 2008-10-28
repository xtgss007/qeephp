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
 * 定义 Helper_Control 类
 *
 * @package helper
 * @version $Id: control_helper.php 252 2008-03-15 23:50:52Z dualface $
 */

/**
 * Helper_Control 类实现了 WebControls
 *
 * @package helper
 */
class Helper_Control extends QView_Helper_Abstract
{
    /**
     * 构造一个控件
     *
     * @param string $type
     * @param string $id
     * @param array $attribs
     * @param boolean $return
     *
     * @return string
     */
    function make($type, $id = null, array $attribs = null, $return = false)
    {
        $control = QUI::control($this->adapter->context, $type, $id, $attribs);
        $control->viewdata = array();
        return $control->render($return);
    }
}
