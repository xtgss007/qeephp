<?php

class Content extends QDB_ActiveRecord_Abstract
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
            'table_name' => 'contents',

            // 指定数据表记录字段与对象属性之间的映射关系
            // 没有在此处指定的字段，QeePHP 会自动设置将字段映射为对象的可读写属性
            'props' => array(
                // 主键应该是只读，确保领域对象的“不变量”
                'content_id' => array('readonly' => true),
                'created' => array('readonly' => true),
                'updated' => array('readonly' => true),
                'comments_count' => array('readonly' => true),
                'marks_avg' => array('readonly' => true),

                // Content 属于一个作者
                'author' => array('belongs_to' => 'Author'),
                // Content 有多个 Comment
                'comments' => array('has_many' => 'Comment', 'count_cache' => 'comments_count'),
                // Content 关联到多个 Tag
                'tags' => array('many_to_many' => 'Tag', 'mid_table_name' => 'contents_has_tags'),
            ),

            // 在保存对象时，会按照下面指定的验证规则进行验证。验证失败会抛出异常。
            // 也可以调用对象的 isValidate() 方法确认对象数据是否通过了验证。
            // 还可以通过对象的 ::validate() 静态方法对数组数据进行验证。
            'validation' => array(
                'title' => array(
                    array('not_empty', 'title 不能为空'),
                    array('max_length', 255, 'title 不能超过 84 个汉字'),
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
     * @return QDB_ActiveRecord_Select
     */
    static function find()
    {
        $args = func_get_args();
        return QDB_ActiveRecord_Meta::getInstance(__CLASS__)->findArgs($args);
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
        return QDB_ActiveRecord_Meta::getInstance(__CLASS__);
    }

/* -------------------------------------------------------------------- */

}

class Post_Null extends Post
{
    function id() { return null; }
    function setProps(array $props) {}
    function save($force_create = false, $recursion = 99) {}
    function reload($recursion = 1) {}
    function validate($mode = 'general', $throw = false) {}
    function destroy($recursion = 99) {}
    protected function create($recursion = 99) {}
    protected function update($recursion = 99) {}
}

class Post_Exception extends QException
{
}

