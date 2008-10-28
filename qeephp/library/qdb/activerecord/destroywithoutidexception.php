<?php

class QDB_ActiveRecord_DestroyWithoutIdException extends QDB_ActiveRecord_Exception
{
    public $ar_object;

    function __construct(QDB_ActiveRecord_Abstract $object)
    {
        $this->ar_object = $object;
        $class_name = $object->getMeta()->class_name;
        // LC_MSG: Destroy object "%s" instance without ID.
        parent::__construct($class_name, __('Destroy object "%s" instance without ID.', $class_name));
    }
}

