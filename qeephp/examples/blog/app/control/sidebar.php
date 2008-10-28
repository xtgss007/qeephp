<?php
// $Id$

class Control_Sidebar extends QUI_Control_Abstract
{

    function render($return = false)
    {
        $this->view['tags'] = Tag::find()->all()->order('label ASC')->query();
        $this->view['comments'] = Comment::find()->limit(0, 10)->order('created DESC')->query();
        return $this->_renderBlock('sidebar', $return);
    }
}
