<?php
// $Id$

class Control_Nav extends QUI_Control_Abstract
{

    function render($return = false)
    {
    	$this->view['current_location'] = $this->context->getParam('current_location');
    	$this->view['current_tag'] = $this->context->getParam('current_tag');
    	$this->view['current_post'] = $this->context->getParam('current_post');

        return $this->_renderBlock('nav', $return);
    }
}
