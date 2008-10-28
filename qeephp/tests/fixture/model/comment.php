<?php

class Comment extends QDB_ActiveRecord_Abstract
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
            // 指定该 ActiveRecord 要使用的行为插件
            'behaviors' => '',

            // 用什么数据表保存对象
            'table_name' => 'comments',

            // 指定数据表记录字段与对象属性之间的映射关系
            // 没有在此处指定的字段，QeePHP 会自动设置将字段映射为对象的可读写属性
            'props' => array(
                'comment_id' => array('readonly' => true),
                'created' => array('readonly' => true),

                'author' => array('belongs_to' => 'Author', 'count_set_to' => 'comments_count'),
                'content' => array('belongs_to' => 'Content', 'count_set_to' => 'comments_count'),
            ),

            // 在保存对象时，会按照下面指定的验证规则进行验证。验证失败会抛出异常。
            // 也可以调用对象的 isValidate() 方法确认对象数据是否通过了验证。
            // 还可以通过对象的 ::validate() 静态方法对数组数据进行验证。
            'validation' => array(
                'body'  => array(
                    array('not_empty', 'body 不能为空'),
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

class Comment_Exception extends QException
{
}

