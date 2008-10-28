<?php
// $Id$

/**
 * @file
 * 定义 QDB_ActiveRecord_CompositePKIncompatibleException 异常
 *
 * @ingroup activerecord
 *
 * @{
 */

class QDB_ActiveRecord_CompositePKIncompatibleException extends QDB_ActiveRecord_Exception
{
    /**
     * 与复合主键不兼容的功能名称
     *
     * @var string
     */
    public $feature_name;

    function __construct($class_name, $feature_name)
    {
        $this->feature_name = $feature_name;
        // LC_MSG: Feature "%s" incompatible with composite primary keys.
        parent::__construct($class_name, __('Feature "%s" incompatible with composite primary keys.'));
    }
}

/**
 * @}
 */
