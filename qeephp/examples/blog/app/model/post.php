<?php
// $Id$

/**
 * 定义 Post 和 Post_Exception 类
 */

/**
 * Post 封装来自 blog_posts 数据表的记录及领域逻辑
 */
class Post extends QDB_ActiveRecord_Abstract
{

    /**
     * 返回格式化以后的 body 文本
     *
     * @return string
     */
    function getFormattedBody()
    {
        if (!function_exists('bbencode_all'))
        {
            require ExampleBlogApp::instance()->ROOT_DIR() . '/vendor/bbcode.php';
        }
        return bbencode_all($this->body);
    }

    /**
     * 设置文章使用的标签
     *
     * @param mixed $tags
     */
    function setTags($tags)
    {
        if (! ($tags instanceof QColl) && ! is_array($tags))
        {
            $names = Q::normalize(explode(' ', $tags));
            if (empty($names))
            {
            	return;
            }
            $tags = Tag::find('label in (?)', $names)->all()->query();
            $names = array_flip($names);

            foreach ($tags as $tag)
            {
                if (isset($names[$tag->label]))
                {
                    unset($names[$tag->label]);
                }
            }

            foreach (array_keys($names) as $label)
            {
                $tag = new Tag(array(
                    'label' => $label
                ));
                $tags[] = $tag;
            }
        }

        $this->_props['tags'] = $tags;
        $this->willChanged('tags');
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
        return array
        (
            // 指定该 ActiveRecord 要使用的行为插件

            /**
             * 使用了 relation 插件
             *
             * relation 插件为 ActiveRecord 添加一组直接操作关联对象的方法。
             *
             * 例如 Post 关联了 Comment，则可以使用 $post->addComment() 来直接添加关联对象。
             */
            'behaviors' => 'relation',

	        // 指定行为插件的配置
	        'behaviors_settings' => array
            (
                'relation' => array
                (
                    'assoc_props' => 'comments, tags',
                ),
            ),

	        // 用什么数据表保存对象
	        'table_name' => 'posts',

	        // 指定数据表记录字段与对象属性之间的映射关系
	        // 没有在此处指定的属性，QeePHP 会自动设置将属性映射为对象的可读写属性
	        'props' => array
	        (
	            // 主键应该是只读，确保领域对象的“不变量”
	            'post_id' => array( 'readonly' => true ),
	            // 对象创建时间应该是只读
	            'created' => array( 'readonly' => true ),
	            // 对象最后更新时间应该是只读
	            'updated' => array( 'readonly' => true ),

		        // post 与 tag 是多对多关联
		        'tags' => array
                (
		            'many_to_many'  => 'Tag',
		            'setter'        => 'setTags',
		            'on_find_order' => 'label ASC'
		        ),

		        // post 与 comment 是一对多关联
		        'comments' => array
		        (
		            'has_many'      => 'Comment',
		            'on_find_order' => 'created DESC'
		        ),

		        // formatted_body 是一个虚拟属性，由 getFormattedBody() 方法返回
		        'formatted_body' => array
		        (
		            'getter'        => 'getFormattedBody'
		        )
	        ),

	        /**
	         * 指定在数据库中创建对象时，哪些属性的值不允许由外部提供
	         */
	        'create_reject' => 'post_id',

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
	        'create_autofill' => array
	        (
	            # 属性名 => 填充值
                'created' => self::AUTOFILL_TIMESTAMP,
                'updated' => self::AUTOFILL_TIMESTAMP
	        ),

	        /**
	         * 指定更新数据库中的对象时，哪些属性的值由下面指定的内容进行覆盖
	         *
	         * 填充值的指定规则同 create_autofill
	         */
	        'update_autofill' => array
	        (
	            'updated' => self::AUTOFILL_TIMESTAMP
	        ),

	        /**
	         * 在保存对象时，会按照下面指定的验证规则进行验证。验证失败会抛出异常。
	         *
	         * 除了在保存时自动验证，还可以通过对象的 ::meta()->validate() 方法对数组数据进行验证。
	         *
	         * 如果需要添加一个自定义验证，应该写成
	         *
	         * 'title' => array
	         * (
	         *     array(array(__CLASS__, 'checkTitle'), '标题不能为空'),
	         * )
	         *
	         * 然后在该类中添加 checkTitle() 方法。函数原型如下：
	         *
	         * static function checkTitle($title)
	         *
	         * 该方法返回 true 表示通过验证。
	         */
	        'validations' => array
	        (
	            'title' => array
                (
		            array( 'not_empty', '标题不能为空' ),
		            array( 'max_length', 255, '标题不能超过 255 个字符或 85 个汉字' ),
		        ),

		        'body' => array
		        (
		            array( 'not_empty', '内容不能为空' ),
		        ),

		        'tags' => array
		        (
                    array( 'not_empty', '必须为文章指定标签'),
		        ),

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
 * Post_Exception 异常用于封装 Post 领域逻辑错误
 */
class Post_Exception extends QException
{
}
