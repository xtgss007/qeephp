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
 * QeePHP 自带的用户界面控件库
 *
 * @package mvc
 * @version $Id: _standard.php 958 2008-03-17 00:56:12Z dualface $
 */

/**
 * Control_Input_Abstract 实现了公共控件库
 *
 * @package mvc
 */
abstract class Control_Input_Abstract extends QUI_Control_Abstract
{
	protected function _make($type, $return = false)
	{
		$out = "<input type=\"{$type}\" ";
		$out .= QUI::renderIdAndName($this);
		$out .= 'value="'. htmlspecialchars($this->extractAttrib('value')) . '" ';
		$out .= QUI::renderAttribs($this);
		$out .= QUI::renderDisabled($this);
		$out .= '/>';
		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 群组选择框基础类
 *
 * @package mvc
 */
abstract class Control_CheckboxGroup_Abstract extends QUI_Control_Abstract
{
	protected function _make($type, $suffix, $return = false)
	{
		static $id_index = 1;

		$selected = $this->extractAttrib('selected');
		if (!is_array($selected) && substr($selected, 0, 1) == ':') {
			$selected = intval(substr($selected, 1));
			$selected_by_index = true;
		} else {
			$selected_by_index = false;
		}

		$out = '';
		$items = $this->extractAttrib('items');
		$max = count($items);
		if ($max == 0) { return ''; }

		$key         = $this->extractAttrib('key');
		$caption     = $this->extractAttrib('caption');
		$key2caption = $this->extractAttrib('key2caption');


		if ($key) {
			QUI::splitMultiDimArray($items, $key, $caption, $key2caption);
		} else if ($key2caption) {
			$tmp = array();
			foreach ($items as $caption => $key) {
				$tmp[$key] = $caption;
			}
			$items = $tmp;
		}

		$ix = 0;
		$col = 0;
		$table       = $this->extractAttrib('table');
		$border      = $this->extractAttrib('border');
		$cellspacing = $this->extractAttrib('cellspacing');
		$cellpadding = $this->extractAttrib('cellpadding');
		$multirow    = $this->extractAttrib('multirow');
		$cols        = $this->extractAttrib('cols');

		if ($table) {
			$border = is_null($border) ? 0 : $border;
			$cellspacing = is_null($cellspacing) ? 0 : $cellspacing;
			$cellpadding = is_null($cellpadding) ? 0 : $cellpadding;
			$out .= "<table border=\"{$border}\" cellspacing=\"{$cellspacing}\" cellpadding=\"{$cellpadding}\">\n";
			if ($multirow) { $out .= "<tr>\n"; }
		}
		foreach ($items as $caption => $value) {
			if ($table) { $out .= "<td>"; }
			$checked = false;
			if ($selected_by_index) {
				if (is_array($selected)) {
					if (in_array($ix, $selected)) { $checked = true; }
				} else if ($ix == $selected) {
					$checked = true;
				}
			} else {
				if (is_array($selected)) {
					if (in_array($value, $selected)) { $checked = true; }
				} else if ($value == $selected && strlen($value) == strlen($selected)) {
					$checked = true;
				}
			}

			$out .= "<input type=\"{$type}\" ";
			$name = $this->id() . $suffix;
			$id = $this->id() . "_{$id_index}";
			$out .= "name=\"{$name}\" ";
			$id_index++;
			$out .= "id=\"{$id}\" ";
			if (strlen($value) == 0) { $value = 1; }
			$out .= 'value="' . htmlspecialchars($value) . '" ';
			$out .= QUI::renderAttribs($this);
			$out .= QUI::renderChecked($this);
			$out .= QUI::renderDisabled($this);
			if ($checked) {
				$out .= 'checked="checked" ';
			}
			QUI::renderDisabled($this);
			$out .= '/>';
			if ($caption) {
				$ctl = QUI::control($this->context, 'label', "{$id}_label", array(
					'for' => $id, 'caption' => $caption
				));
				$out .= $ctl->render(true);
			}

			if ($ix < $max) {
				if ($multirow) {
					if ($cols) {
						$col++;
						if ($col >= $cols) {
							if ($table) { $out .= "</td>\n</tr>\n<tr>\n"; }
							else { $out .= "<br />\n"; }
							$col = 0;
						} else {
							if ($table) { $out .= "</td>\n"; }
							else { $out .= "&nbsp;&nbsp;\n"; }
						}
					} else {
						if ($table) { $out .= "</td>\n</tr>\n<tr>\n"; }
						else { $out .= "<br />\n"; }
					}
				} else {
					if ($table) { $out .= "</td>\n"; }
					else { $out .= "&nbsp;&nbsp;\n"; }
				}
			}

			$ix++;
		}

		if ($table) {
			if ($cols && $ix % $cols > 0) {
			   $out .= str_repeat("<td>&nbsp;</td>\n", $cols - $ix % $cols);
			}
			$out .= "</tr>\n</table>\n";
		}

		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 选择框的基础类
 *
 * @package mvc
 */
abstract class Control_Checkbox_Abstract extends QUI_Control_Abstract
{
	protected function _make($type, $return = false)
	{
	    $value_check = $this->extractAttrib('value_check');
		$value = $this->extractAttrib('value');
		if (empty($value))
		{
			$value = 1;
		}
		elseif ($value_check)
		{
		    $this->setAttrib('checked', true);
		}

		$out = "<input type=\"{$type}\" ";
		$out .= QUI::renderIdAndName($this);
		$out .= 'value="' . htmlspecialchars($value) . '" ';
		$out .= QUI::renderAttribs($this);
		$out .= QUI::renderDisabled($this);
		$out .= QUI::renderChecked($this);
		$out .= '/>';
		$caption = $this->extractAttrib('caption');
		if ($caption) {
			$attribs = array('for' => $this->id(), 'caption' => $caption);
			$label = QUI::control($this->context, 'label', $this->id() . '_label', $attribs);
			$out .= "\n" . $label->render(true);
		}
		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 单行文本框
 *
 * @package mvc
 */
class Control_Textbox extends Control_Input_Abstract
{
	function render($return = false)
	{
		return $this->_make('text', $return);
	}
}

/**
 * 密码输入框
 *
 * @package mvc
 */
class Control_Password extends Control_Input_Abstract
{
	function render($return = false)
	{
		return $this->_make('password', $return);
	}
}

/**
 * 构造一个隐藏表单控件
 *
 * @package mvc
 */
class Control_Hidden extends Control_Input_Abstract
{
	function render($return = false)
	{
		return $this->_make('hidden', $return);
	}
}


/**
 * 构造一个上传文件选择框
 *
 * @package mvc
 */
class Control_Upload extends Control_Input_Abstract
{
	function render($return = false)
	{
		return $this->_make('file', $return);
	}
}

/**
 * 构造一个多行文本框
 *
 * @package mvc
 */
class Control_Memo extends QUI_Control_Abstract
{
	function render($return = false)
	{
		$value = $this->extractAttrib('value');
		$out = '<textarea ';
		$out .= QUI::renderIdAndName($this);
		$out .= QUI::renderAttribs($this);
		$out .= QUI::renderDisabled($this);
		$out .= '>';
		$out .= htmlspecialchars($value);
		$out .= '</textarea>';
		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 构造一个单选框
 *
 * @package mvc
 */
class Control_Checkbox extends Control_Checkbox_Abstract
{
	function render($return = false)
	{
		return $this->_make('checkbox', $return);
	}
}

/**
 * 构造一个单选按钮
 *
 * @package mvc
 */
class Control_Radio extends Control_Checkbox_Abstract
{
	function render($return = false)
	{
		return $this->_make('radio', $return);
	}
}

/**
 * 构造一个多选框组
 *
 * @package mvc
 */
class Control_CheckboxGroup extends Control_CheckboxGroup_Abstract
{
	function render($return = false)
	{
		return $this->_make('checkbox', '[]', $return);
	}
}

/**
 * 构造一组单选按钮
 *
 * @package mvc
 */
class Control_RadioGroup extends Control_CheckboxGroup_Abstract
{
	function render($return = false)
	{
		return $this->_make('radio', '', $return);
	}
}

/**
 * 构造列表框
 *
 * @package mvc
 */
class Control_Listbox extends QUI_Control_Abstract
{
	function render($return = false)
	{
		$selected   = $this->extractAttrib('selected');
		$size       = $this->extractAttrib('size');
		$items      = $this->extractAttrib('items');
		$multiple   = $this->extractAttrib('multiple');
		$key        = $this->extractAttrib('key');
		$caption    = $this->extractAttrib('caption');

		if (!is_array($selected) && substr($selected, 0, 1) == ':') {
			$selected = intval(substr($selected, 1));
			$selected_by_index = true;
		} else {
			$selected_by_index = false;
		}
		$out = '<select ';
		$out .= QUI::renderIdAndName($this);
		if ($size <= 0) {
			$size = 4;
		}
		$out .= 'size="' . $size . '" ';
		if ($multiple) {
			$out .= 'multiple="multiple" ';
		}
		$out .= QUI::renderDisabled($this);
		$out .= QUI::renderAttribs($this);
		$out .= ">\n";

		$items = (array)$items;

		if ($key) {
			QUI::splitMultiDimArray($items, $key, $caption);
		}

		$ix = 0;
		foreach ($items as $caption => $value) {
			$out .= '<option value="' . htmlspecialchars($value) . '" ';
			$checked = false;
			if ($selected_by_index) {
				if (is_array($selected)) {
					if (in_array($ix, $selected)) {
						$checked = true;
					}
				} else if ($ix == $selected) {
					$checked = true;
				}
			} else {
				if (is_array($selected)) {
					if (in_array($value, $selected)) {
						$checked = true;
					}
				} else if ($value == $selected) {
					$checked = true;
				}
			}
			if ($checked) {
				$out .= 'selected="selected" ';
			}
			$out .= '>';
			$out .= htmlspecialchars($caption);
			$out .= "</option>\n";
			$ix++;
		}
		$out .= "</select>\n";

		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 构造一个下拉列表框
 *
 * @package mvc
 */
class Control_DropdownList extends QUI_Control_Abstract
{
	function render($return = false)
	{
        $selected = $this->extractAttrib('selected');
        $value    = $this->extractAttrib('value');
		$key      = $this->extractAttrib('key');
		$items    = $this->extractAttrib('items');
        $caption  = $this->extractAttrib('caption');
        $no_keys  = $this->extractAttrib('no_keys');

        if (strlen($value) && strlen($selected) == 0)
        {
            $selected = $value;
        }

        if (substr($selected, 0, 1) == ':')
        {
			$selected = intval(substr($selected, 1));
			$selected_by_index = true;
        }
        else
        {
			$selected_by_index = false;
		}

		$out = '<select ';
		$out .= QUI::renderIdAndName($this);
		$out .= QUI::renderDisabled($this);
		$out .= QUI::renderAttribs($this);
		$out .= ">\n";

		$items = (array)$items;

        if ($no_keys)
        {
            $items = array_combine($items, $items);
        }
        elseif ($key)
        {
			QUI::splitMultiDimArray($items, $key, $caption);
        }

		$ix = 0;
		foreach ($items as $caption => $value) {
			$out .= '<option value="' . htmlspecialchars($value) . '" ';
			if ($selected_by_index) {
				if ($ix == $selected) {
					$out .= 'selected="selected" ';
				}
			} else {
				if ($value == $selected) {
					$out .= 'selected="selected" ';
				}
			}
			$out .= '>';
			$out .= htmlspecialchars($caption);
			$out .= "</option>\n";
			$ix++;
		}
		$out .= "</select>\n";

		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 构造一个按钮
 *
 * @package mvc
 */
class Control_Button extends Control_Input_Abstract
{
	function render($return = false, $button_type = 'button')
	{
		$caption = $this->extractAttrib('caption');
		if (!empty($caption)) {
		    $this->setAttrib('value', $caption);
		}
		return $this->_make($button_type, $return);
	}
}

/**
 * 构造一个表单提交按钮
 *
 * @package mvc
 */
class Control_Submit extends Control_Button
{
	function render($return = false)
	{
		$caption = $this->extractAttrib('caption');
		if (!empty($caption)) {
		    $this->setAttrib('value', $caption);
		}
		return $this->_make('submit', $return);
	}
}

/**
 * 构造一个表单重置按钮
 *
 * @package mvc
 */
class Control_Reset extends Control_Button
{
	function render($return = false)
	{
		$caption = $this->extractAttrib('caption');
		if (!empty($caption)) {
		    $this->setAttrib('value', $caption);
		}
		return $this->_make('reset', $return);
	}
}

/**
 * 构造一个标签控件
 *
 * @package mvc
 */
class Control_Label extends QUI_Control_Abstract
{
	function render($return = false)
	{
		$caption = $this->extractAttrib('caption');

		$out = '<label ';
		$out .= QUI::renderIdAndName($this);
		$out .= QUI::renderAttribs($this);
		$out .= '>';
		$out .= htmlspecialchars($caption);
		$out .= '</label>';

		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}

/**
 * 构造一个静态控件
 *
 * @package mvc
 */
class Control_Static extends QUI_Control_Abstract
{
	function render($return = false)
	{
		$caption = $this->extractAttrib('caption');
		$out = '<div ';
		$out .= QUI::renderIdAndName($this);
		$out .= QUI::renderAttribs($this);
		$out .= '>';
		$out .= htmlspecialchars($caption);
		$out .= '</div>';

		if ($return) {
			return $out;
		} else {
			echo $out;
			return null;
		}
	}
}
