<?php

class Profile extends QDB_ActiveRecord_Abstract
{
    /**
     * 返回对象的定义
     *
     * @static
     *
     * @return array
     */
    static function __define()
    {
        return array(
            'behaviors' => '',
            'table_name' => 'profiles',
            'props' => array(
                'author_id' => array('readonly' => true),
                'author' => array('belongs_to' => 'Author'),
            ),

            'validation' => array(
                'address' => array(
                    array('not_empty', 'address 不能为空'),
                    array('max_length', 200, 'address 不能超过 60 个汉字'),
                ),
                'postcode' => array(
                    array('not_empty', 'postcode 不能为空'),
                    array('max_length', 10, 'postcode 不能超过 10 个字符'),
                ),
            ),
        );
    }


/* ------------------ 以下是自动生成的代码，不能修改 ------------------ */

    /**
     * 开启一个查询，查找符合条件的对象或对象集合
     *
     * @static
     *
     * @return QDB_Select
     */
    static function find()
    {
        $args = func_get_args();
        return QDB_ActiveRecord_Meta::instance(__CLASS__)->findByArgs($args);
    }

    /**
     * 返回当前 ActiveRecord 类的元数据对象
     *
     * @static
     *
     * @return QDB_ActiveRecord_Meta
     */
    static function meta()
    {
        return QDB_ActiveRecord_Meta::instance(__CLASS__);
    }

/* -------------------------------------------------------------------- */

}

class Profile_Exception extends QException
{
}

