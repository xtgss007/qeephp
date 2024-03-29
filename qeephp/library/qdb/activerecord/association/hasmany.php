<?php
// $Id$

/**
 * @file
 * 定义 QDB_ActiveRecord_Association_HasMany 类
 *
 * @ingroup activerecord
 *
 * @{
 */

/**
 * QDB_ActiveRecord_Association_HasMany 类封装数据表之间的 has many 关联
 */
class QDB_ActiveRecord_Association_HasMany extends QDB_ActiveRecord_Association_Abstract
{
	public $one_to_one = false;
	public $on_delete = 'cascade';
	public $on_save   = 'save';

    function init()
    {
        if ($this->_inited) { return $this; }
        parent::init();

        $p = $this->_init_config;
        $this->source_key = !empty($p['source_key']) ? $p['source_key'] : reset($this->source_meta->idname);
        $this->target_key = !empty($p['target_key']) ? $p['target_key'] : reset($this->source_meta->idname);

        unset($this->_init_config);
        return $this;
    }

    function onSourceSave(QDB_ActiveRecord_Abstract $source, $recursion)
    {
    	$this->init();
    	$mapping_name = $this->mapping_name;
    	if ($this->on_save === 'skip' || $this->on_save === false || !isset($source->{$mapping_name}))
    	{
    		return $this;
    	}

    	$source_key_value = $source->{$this->source_key};
    	foreach ($source->{$mapping_name} as $obj)
    	{
    		/* @var $obj QDB_ActiveRecord_Abstract */
    		$obj->changePropForce($this->target_key, $source_key_value);
    		$obj->save($recursion - 1, $this->on_save);
    	}

        return $this;
    }

    function onSourceDestroy(QDB_ActiveRecord_Abstract $source)
    {
        $this->init();
        if ($this->on_delete === false || $this->on_delete == 'skip') { return $this; }

        $source_key_value = $source->{$this->source_key};
        $cond = array($this->target_key => $source_key_value);
        if ($this->on_delete === true || $this->on_delete == 'cascade')
        {
        	$this->target_meta->destroyWhere($cond);
        }
        elseif ($this->on_delete == 'reject')
        {
            $row = $this->target_meta->find($cond)->count()->query();
            if (intval($row['row_count']) > 0)
            {
            	// LC_MSG: 对象 "%s" 的关联 "%s" 拒绝了对象的删除操作.
                throw new QDB_ActiveRecord_Association_Exception_Reject(__(
                        '对象 "%s" 的关联 "%s" 拒绝了对象的删除操作.',
                        $this->source_meta->class_name, $this->mapping_name));
            }
        }
        else
        {
            $fill = ($this->on_delete == 'set_null') ? null : $this->on_delete_set_value;
            $this->target_meta->updateWhere($cond, array($this->target_key => $fill));
        }

        return $this;
    }

    /**
     * 直接添加一个关联对象
     *
     * @param QDB_ActiveRecord_Abstract $source
     * @param QDB_ActiveRecord_Abstract $target
     *
     * @return QDB_ActiveRecord_Association_Abstract
     */
    function addRelatedObject(QDB_ActiveRecord_Abstract $source, QDB_ActiveRecord_Abstract $target)
    {
    	$this->init();
    	$target->changePropForce($this->target_key, $source->{$this->source_key});
        $target->save(0, $this->on_save);
        return $this;
    }
}

/**
 * @}
 */
