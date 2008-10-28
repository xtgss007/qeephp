<?php

// $Id$


/**
 * 定义 Tag 和 Tag_Exception 类
 */

/**
 * Tag 封装来自 tags 数据表的记录及领域逻辑
 */
class Tag extends QDB_ActiveRecord_Abstract
{

    /**
     * 编码后的 label
     *
     * @return string
     */
    function getSlug()
    {
        return rawurlencode($this->label);
    }

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

            // 指定行为插件的配置
            'behaviors_settings' => array()# '插件名' => array('选项' => 设置),
            ,

            // 用什么数据表保存对象
            'table_name' => 'tags',

            // 指定数据表记录字段与对象属性之间的映射关系
            // 没有在此处指定的属性，QeePHP 会自动设置将属性映射为对象的可读写属性
            'props' => array(
                // 主键应该是只读，确保领域对象的“不变量”
                'tag_id' => array('readonly' => true),

                // post 与 tag 是多对多关联
                'posts' => array('many_to_many' => 'Post', 'on_find_order' => 'created DESC'),

                // slug 是一个虚拟属性，由 getSlug() 方法返回
                'slug' => array('getter' => 'getSlug'),
            ),

            /**
             * 指定在数据库中创建对象时，哪些属性的值不允许由外部提供
             */
            'create_reject' => 'tag_id',

            /**
             * 指定更新数据库中的对象时，哪些属性的值不允许由外部提供
             */
            'update_reject' => '',

            /**
             * 指定在数据库中创建对象时，哪些属性的值由下面指定的内容进行覆盖
             *
             * 如果填充值为 self::AUTOFILL_TIMESTAMP 或 self::AUTOFILL_DATETIME，
             * 则会根据属性的类型来自动填充当前时间（整数或字符串）。
             *
             * 如果填充值为一个数组，则假定为 callback 方法。
             */
            'create_autofill' => array()# 属性名 => 填充值
            # 'is_locked' => 0,
            ,

            /**
             * 指定更新数据库中的对象时，哪些属性的值由下面指定的内容进行覆盖
             *
             * 填充值的指定规则同 create_autofill
             */
            'update_autofill' => array(),

            /**
             * 在保存对象时，会按照下面指定的验证规则进行验证。验证失败会抛出异常。
             *
             * 除了在保存时自动验证，还可以通过对象的 ::meta()->validate() 方法对数组数据进行验证。
             *
             * 如果需要添加一个自定义验证，应该写成
             *
             * 'title' => array(
             *        array(array(__CLASS__, 'checkTitle'), '标题不能为空'),
             * )
             *
             * 然后在该类中添加 checkTitle() 方法。函数原型如下：
             *
             * static function checkTitle($title)
             *
             * 该方法返回 true 表示通过验证。
             */
            'validation' => array
            (
                'label' => array
                (
                    array('not_empty', 'label不能为空'),
                    array('max_length', 64, 'label不能超过 64 个字符'),
                )
            )
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

/* ------------------ 以上是自动生成的代码，不能修改 ------------------ */

}

/**
 * Tag_Exception 异常用于封装 Tag 领域逻辑错误
 */
class Tag_Exception extends QException
{
}
