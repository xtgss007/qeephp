<?php

/**
 * QForm_Layout_Nested 是 qeephp 预定义的表单布局视图
 *
 * 渲染表单时，将遍历表单包含的每一个组和元件，然后渲染该组的所有元件。
 *
 * 要指定错误信息，需要设置表单的 error_messages 属性。
 * 该属性是一个二维数组，格式如下：
 *
 * $error_messages = array
 * (
 *   '组名_元件名' => array(错误消息1, 错误消息2, ...),
 *   '组名_元件名' => array(错误消息1, 错误消息2, ...),
 *   ....
 * )
 *
 * 然后 nested 视图会自动根据错误消息中指定的组名和元件名在合适的位置显示错误消息。
 *
 * nested 表单布局视图使用了下列 css 样式：
 *
 * notes: 表单元件的说明文字
 * error: 表单元件错误信息
 * hide: 隐藏元素
 *
 * .hide 的定义应该是：
 *
 * .hide {
 *   display: none;
 * }
 *
 *
 * 在表单布局视图中，只需要一行代码：
 *
 * $form->renderWithLayoutClass('QForm_Layout_Nested', $_ctx);
 *
 */

abstract class QForm_Layout_Nested
{
    /**
     * 渲染一个表单
     *
     * @param QContext $context
     * @param QForm $form
     * @param boolean $return
     */
    static function render(QContext $context, QForm $form, $return = false)
    {
        $error_messages = $form->error_messages;
        if (!is_array($error_messages))
        {
            $error_messages = array();
        }

        $id = $form->id();
        $action = htmlspecialchars($form->action);

        $out = <<<EOT

<form name="{$id}" id="{$id}" action="{$action}" method="post">

EOT;

        foreach ($form as $item)
        {
            $out .= self::_renderItem($context, $item, $error_messages);
        }

        $out .= <<<EOT

</form>

EOT;

        if (!$return)
        {
            echo $out;
            $out = null;
        }

        return $out;
    }

    /**
     * 渲染一个表单项目
     *
     * @param QContext $context
     * @param QForm_Item_Abstract $item
     * @param array $error_messages
     *
     * @return string
     */
    protected static function _renderItem(QContext $context, QForm_Item_Abstract $item, array $error_messages)
    {
        $out = '';

        if (!$item->isGroup())
        {
            $item = array($item);
            $prefix = '';
        }
        else
        {
            $prefix = $item->id() . '_';
        }

        foreach ($item as $element)
        {

            if ($element->type() != 'hidden')
            {
                $out .= "\n";

                $id = $element->id();
                $label = h($element->label);

                if ($label)
                {
                    $out .= "<p>\n";
                    $out .= "<label for=\"{$id}\">{$label}：";

                    if ($element->description)
                    {
                        $descript = h($element->description);
                        $out .= "&nbsp;<span class=\"notes\">({$descript})</span>";
                    }

                    $out .= "</label>\n";

                }

                $error_key = "{$prefix}{$id}";
                if (isset($error_messages[$error_key]))
                {
                    $out .= "<span class=\"error\">";
                    $msg = $error_messages[$error_key];
                    if (is_array($msg))
                    {
                        $msg = implode(', ', $msg);
                    }
                    $out .= nl2br(htmlspecialchars($msg));
                }
                else
                {
                    $out .= "<span class=\"error hide\">";
                }
                $out .= "<br /></span>\n";

                $element->unsetProps('label, description');
                $out .= $element->render($context, true);

                $out .= "\n";

                if ($label)
                {
                    $out .= "</p>\n";
                }
            }
            else
            {
                $out .= "\n";
                $out .= $element->render($context, true);
                $out .= "\n";
            }
        }

        return $out;
    }
}

