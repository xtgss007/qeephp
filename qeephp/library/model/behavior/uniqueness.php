<?php

// $Id$


/**
 * @file
 * 定义 Behavior_Uniqueness 类
 *
 * @ingroup behavior
 *
 * @{
 */

/**
 * Behavior_Uniqueness 用于检查指定的属性是否存在重复值
 */
class Model_Behavior_Uniqueness extends QDB_ActiveRecord_Behavior_Abstract
{

    protected $_settings = array
    (
        //! 要检查的属性
        'check_props' => '',
        //! 检查未通过时的消息
        'error_messages' => array(),
    );

    function bind()
    {
        $this->_addEventHandler(self::BEFORE_UPDATE, array($this, '_before_update'));
        $this->_addEventHandler(self::BEFORE_CREATE, array($this, '_before_create'));
    }

    function _before_create(QDB_ActiveRecord_Abstract $obj)
    {
        $this->_checkUniqueness($obj, null);
    }

    function _before_update(QDB_ActiveRecord_Abstract $obj)
    {
        $cond = new QDB_Cond();
        foreach ($this->_meta->idname as $idname)
        {
            $cond->orCond("[{$idname}] <> ?", $obj->{$idname});
        }
        $this->_checkUniqueness($obj, $cond, true);
    }

    protected function _checkUniqueness(QDB_ActiveRecord_Abstract $obj, QDB_Cond $more_cond = null, $ignore_id = false)
    {
        $check_props = Q::normalize($this->_settings['check_props']);
        if (empty($check_props))
        {
            return;
        }

        $checks = array();
        $error = array();
        foreach ($check_props as $check)
        {
            if ($ignore_id && $check == $obj->idname())
            {
                continue;
            }

            if (strpos($check, '+') !== false)
            {
            	$props = Q::normalize($check, '+');
            	$cond = array();
            	foreach ($props as $prop_name)
            	{
            		$cond[$prop_name] = $obj->{$prop_name};
            	}
            }
            else
            {
                $cond = array($check => $obj->{$check});
                $props = $check;
            }

	        if (!is_null($more_cond))
	        {
	            $cond[] = $more_cond;
	        }

	        $test = $this->_meta->find($cond)->count()->query();
	        if ($test['row_count'] <1)
	        {
	        	continue;
	        }

	        if (isset($this->_settings['error_messages'][$check]))
	        {
	        	$error[$check] = array($check => $this->_settings['error_messages'][$check]);
	        }
	        else
	        {
	        	$error[$check] = array($check => "{$check} duplicated");
	        }
        }

        if (!empty($error))
        {
            throw new QDB_ActiveRecord_ValidateFailedException($error, $obj);
        }
    }

}

