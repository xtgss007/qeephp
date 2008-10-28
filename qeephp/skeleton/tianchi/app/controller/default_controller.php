<?php

/**
 * 默认控制器
 */
class Controller_Default extends AppController_Abstract
{
    /**
     * default action
     */
    function actionIndex()
    {
        /**
         * 要传递到视图的数据，可以直接赋值给 $this->view。
         *
         * 为了便于在视图中使用这些数据，$this->view 应该是一个数组，键名对应视图中的变量名。
         */
        $this->view = array('text' => '<Hello, PHPer!>');
    }
}

