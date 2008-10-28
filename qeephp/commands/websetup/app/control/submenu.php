<?php
// $Id$

class Control_SubMenu extends QUI_Control_Abstract
{
    function render($return = false)
    {
        $context = $this->context->parent();

        $menu = Menu::instance();
        $this->view['menu'] = $menu;
        $this->view['main'] = $menu->getCurrentMainMenu($context);
        $this->view['current'] = $menu->getCurrentSubMenu($context, $this->view['main']['items']);

        return $this->_renderBlock('submenu', $return);
    }

}
