<?php
// $Id$

/**
 * @file
 * 定义 QDB_ActiveRecord_Association_BelongsTo 类
 *
 * @ingroup activerecord
 *
 * @{
 */

/**
 * QDB_ActiveRecord_Association_BelongsTo 类封装 ActiveRecord 对象之间的 belongs to 关联
 */
class QDB_ActiveRecord_Association_BelongsTo extends QDB_ActiveRecord_Association_Abstract
{
	public $one_to_one = true;
	public $on_delete = 'skip';
	public $on_save = 'skip';

    function init()
    {
        if ($this->_inited) { return $this; }
        parent::init();

        $p = $this->_init_config;
        $this->source_key = !empty($p['source_key']) ? $p['source_key'] : reset($this->target_meta->idname);
        $this->target_key = !empty($p['target_key']) ? $p['target_key'] : reset($this->target_meta->idname);

        unset($this->_init_config);
        return $this;
    }

    function onSourceSave(QDB_ActiveRecord_Abstract $source, $recursion)
    {
        return $this;
    }

    function onSourceDestroy(QDB_ActiveRecord_Abstract $source)
    {
        return $this;
    }
}

/**
 * @}
 */
