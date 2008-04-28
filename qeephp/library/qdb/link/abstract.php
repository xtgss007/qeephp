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
 * 定义 QDB_Link_Abstract 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_Link_Abstract 是表数据入口关联和 ActiveRecord 关联的基础类，封装了关联的共有属性和方法
 *
 * @package database
 */
abstract class QDB_Link_Abstract
{
    /**
     * 目标数据映射到来源数据的哪一个键，同时 mapping_name 也是关联的名字
     *
     * @var string
     */
    public $mapping_name;

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
     * 查询目标数据时要使用的查询条件
     *
     * @var array|string
     */
    public $on_find_where = null;

    /**
     * 查询目标数据时的排序
     *
     * @var string
     */
    public $on_find_order = null;

    /**
     * 查询目标数据时要查询哪些属性
     *
     * @var array|string
     */
    public $on_find_keys = '*';

    /**
     * 指示在来源数据时，如何处理相关的目标数据
     *
     * cascade|true - 删除关联的目标数据
     * set_null     - 将目标数据的 target_key 键设置为 NULL
     * set_value    - 将目标数据的 target_key 键设置为指定的值
     * skip|false   - 不处理关联记录
     * reject       - 拒绝对来源数据的删除
     *
     * 对于 has many 和 has one 关联，默认值则是 cascade
     * 对于 belongs to 和 many to many 关联，on_delete 设置固定为 skip
     *
     * @var string|boolean
     */
    public $on_delete = 'skip';

    /**
     * 如果 on_delete 为 set_value，则通过 on_delete_set_value 指定要填充的值
     *
     * @var mixed
     */
    public $on_delete_set_value = null;

    /**
     * 指示保存来源数据时，似乎否保存关联的目标数据
     *
     * save|true    - 根据目标数据是否有 ID 或主键值来决定是创建新的目标数据还是更新已有的目标数据
     * create       - 强制创建新的目标数据
     * update       - 强制更新已有的目标数据
     * replace      - 尝试替换已有的目标数据
     * skip|false   - 保存来源数据时，不保存目标数据
     * only_create  - 仅仅保存需要新建的目标数据
     * only_update  - 仅仅保存需要更新的目标数据
     *
     * 对于 many to many 关联，on_save 的默认值是 skip
     * 对于 has many 和 has one 关联，on_save 的默认值是 save
     * 对于 belongs to 关联，on_save 设置固定为 skip
     *
     * @var string
     */
    public $on_save = 'skip';

    /**
     * 查询多对多关联时，中间数据使用哪一个键关联到来源方
     *
     * @var string
     */
    public $mid_source_key;

    /**
     * 查询多对多关联时，中间数据使用哪一个键关联到目标方
     *
     * @var string
     */
    public $mid_target_key;

    /**
     * 查询多对多关联时，是否也要把中间数据放到结果中
     *
     * 如果 mid_on_find_keys 为 null，则不查询。如果为特定属性名，
     * 则会根据 mid_mapping_to 将中间数据指定为目标数据的一个键。
     *
     * @var array|string
     */
    public $mid_on_find_keys = null;

    /**
     * 查询多对多关联时，中间数据要指定到目标数据的哪一个键
     *
     * @var string
     */
    public $mid_mapping_to;

    /**
     * 指示关联两个数据时，是一对一关联还是一对多关联
     *
     * @var boolean
     */
    public $one_to_one = false;

    /**
     * 关联的类型
     *
     * @var int
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
     * 关联中的主表
     *
     * @var QDB_Table
     */
    public $source_table;

    /**
     * 关联到哪一个表数据入口对象
     *
     * @var QDB_Table
     */
    public $target_table;

    /**
     * many to many 关联中处理中间表的表数据入口对象
     *
     * @var QDB_Table
     */
    public $mid_table;

    /**
     * 查询时，主表的关联字段使用什么别名
     *
     * @var string
     */
    public $source_key_alias;

    /**
     * 查询时，关联表的关联字段使用什么别名
     *
     * @var string
     */
    public $target_key_alias;

    /**
     * 指示关联是否已经初始化
     *
     * @var boolean
     */
    protected $_is_init = false;

    /**
     * 初始化参数
     *
     * @var array
     */
    protected $_init_params;

    /**
     * 用于初始化关联对象的参数
     *
     * @var array
     */
    static protected $_init_params_keys = array(
        'source_key',
        'target_key',
        'on_find',
        'on_find_where',
        'on_find_order',
        'on_find_keys',
        'on_delete',
        'on_delete_set_value',
        'on_save',
        'mid_source_key',
        'mid_target_key',
        'mid_on_find_keys',
        'mid_mapping_to',
        'enabled',
    );

    /**
     * 构造函数
     *
     * @param int $type
     * @param array $params
     *
     * @return QDB_Link_Abstract
     */
    protected function __construct($type, array $params)
    {
        $this->type = $type;
        if (empty($params['mapping_name'])) {
            // LC_MSG: 创建关联必须指定关联的 mapping_name 属性.
            throw new QDB_Link_Exception(__('创建关联必须指定关联的 mapping_name 属性.'));
        } else {
            $this->mapping_name = strtolower($params['mapping_name']);
        }

        foreach (self::$_init_params_keys as $key) {
            if (!empty($params[$key])) {
                $this->{$key} = $params[$key];
            }
        }
        $this->_init_params = $params;
    }

    /**
     * 初始化关联
     *
     * @return QDB_Link_Abstract
     */
    abstract function init();

    /**
     * 获得一个关联对象
     *
     * @param int $type
     * @param array $params
     * @param QDB_Table $source_table
     *
     * @return QDB_Link_Abstract
     */
    static function createLink($type, array $params, QDB_Table $source_table)
    {
        switch ($type) {
        case QDB::HAS_ONE:
            return new QDB_Link_HasOne($params, $source_table);
        case QDB::HAS_MANY:
            return new QDB_Link_HasMany($params, $source_table);
        case QDB::BELONGS_TO:
            return new QDB_Link_BelongsTo($params, $source_table);
        case QDB::MANY_TO_MANY:
            return new QDB_Link_ManyToMany($params, $source_table);
        default:
            // LC_MSG: 无效的关联类型 "%s".
            throw new QDB_Link_Exception(__('无效的关联类型 "%s".', $type));
        }
    }

    /**
     * 保存目标数据
     *
     * @param array $target_data
     * @param mixed $source_key_value
     * @param int $recursion
     */
    abstract function saveTarget(array $target_data, $source_key_value, $recursion);

    /**
     * 删除目标数据
     *
     * @param mixed $target_key_value
     * @param int $recursion
     */
    abstract function removeTarget($source_key_value, $recursion);
}
