<?php
// $Id$

class Control_NavMain extends QUI_Control_Abstract
{
	function render($return = false)
    {
        $context = $this->context->parent();

		$menu = Menu::instance();
		$this->view['all_menu'] = $menu->getAll();
		$this->view['current'] = $menu->getCurrentMainMenu($context);

		return $this->_renderBlock('navmain', $return);
	}

}

