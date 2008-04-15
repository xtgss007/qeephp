<?php
/////////////////////////////////////////////////////////////////////////////
// QeePHP Framework
//
// Copyright (c) 2005 - 2008 QeeYuan China Inc. (http://www.qeeyuan.com)
//
// 许可协议，请查看源代码中附带的 LICENSE.TXT 文件，
// 或者访问 http://www.qeephp.org/ 获得详细信息。
/////////////////////////////////////////////////////////////////////////////

/**
 * 定义 QDB_Link_Abstract 抽象类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Link_Abstract 封装了一个关联
 *
 * @package database
 */
abstract class QDB_Link_Abstract
{
    /**
     * 定义四种关联关系
     */
    const has_one       = 'has_one';
    const has_many      = 'has_many';
    const belongs_to    = 'belongs_to';
    const many_to_many  = 'many_to_many';

    /**
     * 该关联的名字
     *
     * @var string
     */
    public $name;

    /**
     * 确定关联关系时，来源方使用哪一个键
     *
     * @var string
     */
    public $source_key;

    /**
     * 确定关联关系时，目标方使用哪一个键
     *
     * @var string
     */
    public $target_key;

    /**
     * 指示是否读取目标数据
     *
     * skip|false   - 不读取
     * all|true     - 读取所有关联数据
     * 整数         - 仅读取指定个数的目标数据
     * 数组         - 由 offset 和 length 组成的数组，指定读取目标数据的起始位置和个数
     *
     * 对于所有类型的关联，on_find 的默认值都是 all
     *
     * @var string|int|array
     */
    public $on_find = 'all';

    /**
     * 查询关联表时使用的查询条件
     *
     * @var array|string
     */
    public $on_find_where = null;

    /**
     * 指示按照什么排序规则查询关联的记录
     */
    public $on_find_order = null;

    /**
     * 指示在读取关联记录时，只获取关联记录的哪些字段
     */
    public $on_find_fields = '*';

    /**
     * 指示在删除主表记录时，如何处理关联的记录
     *
     * cascade   - 删除所有的关联记录
     * set_null  - 将关联记录的外键字段设置为 NULL
     * set_value - 将关联记录的外键字段设置为指定的值
     * skip      - 不处理关联记录
     * reject    - 拒绝删除
     *
     * 对于 belongs to 和 many to many 关联，on_delete 的默认值是 skip
     * 对于 has many 和 has one 关联，默认值则是 cascade
     *
     * @var string|boolean
     */
    public $on_delete = 'skip';

    /**
     * 如果 on_delete 为 set_value，则通过 on_delete_set_value 指定要填充的值。on_delete_set_value 的默认值为 null
     *
     * @var mixed
     */
    public $on_delete_set_value = null;

    /**
     * 指示是否保存关联的记录
     *
     * save        - 根据关联记录是否具有主键值来决定是创建记录还是更新现有记录
     * create      - 强制创建新记录
     * update      - 强制更新记录
     * replace     - 使用数据库的 replace 操作来尝试替换记录
     * skip        - 不处理关联记录
     * only_create - 仅仅保存需要创建的记录（根据是否具备主键值判断）
     * only_update - 仅仅保存需要更新的记录（根据是否具备主键值判断）
     *
     * 对于 belongs to 和 many to many 关联，on_save 的默认值是 skip
     * 对于 has many 和 has one 关联，on_save 的默认值是 save
     *
     * @var string
     */
    public $on_save = 'skip';

    /**
     * 指示关联两个数据集的行时，是一对一关联还是一对多关联
     *
     * @var boolean
     */
    public $one_to_one = false;

    /**
     * 关联的类型
     *
     * @var const
     */
    public $type;

    /**
     * 当 enabled 为 false 时，表数据入口的任何操作都不会处理该关联
     *
     * enabled 的优先级高于 linkRead、linkCreate、linkUpdate 和 linkRemove。
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * 指示关联的表数据入口是否已经初始化
     *
     * @var boolean
     */
    private $is_init = false;

    /**
     * 用于初始化关联对象的参数
     *
     * @var array
     */
    static private $init_params = array(
        'source_key',
        'target_key',
        'on_find',
        'on_find_where',
        'on_find_order',
        'on_find_fields',
        'on_delete',
        'on_delete_set_value',
        'on_save',
    );


    /**
     * 构造函数
     *
     * @param array $params
     * @param int $type
     *
     * @return QDB_Link_Abstract
     */
    function __construct(array $params, $type)
    {
        $this->name = strtolower($params['name']);
        $this->type = $type;

        foreach (self::$init_params as $key) {
            if (!empty($params[$key])) {
                $this->{$key} = $params[$key];
            }
        }
    }

    /**
     * 允许使用该关联
     *
     * @return QDB_Link_Abstract
     */
    function enable()
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * 禁用该关联
     *
     * @return QDB_Link_Abstract
     */
    function disable()
    {
        $this->enabled = false;
        return $this;
    }
}
