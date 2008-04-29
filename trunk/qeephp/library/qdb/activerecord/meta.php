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
 * 定义 QDB_ActiveRecord_Meta 类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Meta 类封装了 ActiveRecord 类的元数据
 *
 * @package database
 */
class QDB_ActiveRecord_Meta implements QDB_ActiveRecord_Callbacks
{
    /**
     * ID 属性名
     *
     * @var string
     */
    public $idname;

    /**
     * 数据表的元信息
     *
     * @var array
     */
    public $table_meta;

    /**
     * 验证规则
     *
     * @var array
     */
    public $validation;

    /**
     * 创建时要过滤的属性
     *
     * @var array
     */
    public $create_reject = array();

    /**
     * 更新时要过滤的属性
     *
     * @var array
     */
    public $update_reject = array();

    /**
     * 创建时要自动填充的属性
     *
     * @var array
     */
    public $create_autofill = array();

    /**
     * 更新时要自动填充的属性
     *
     * @var array
     */
    public $update_autofill = array();

    /**
     * 属性到字段名的映射
     *
     * @var array
     */
    public $prop2fields = array();

    /**
     * 字段名到属性的映射
     *
     * @var array
     */
    public $fields2prop = array();

    /**
     * 所有属性的元信息
     *
     * @var array of properties meta
     */
    public $props = array();

    /**
     * ActiveRecord 继承类之间的关联
     *
     * @var array of QDB_ActiveRecord_Association_Abstract
     */
    public $associations = array();

    /**
     * 事件钩子
     *
     * @var array of callbacks
     */
    public $callbacks = array();

    /**
     * 扩展的方法
     *
     * @var array of callbacks
     */
    public $methods = array();

    /**
     * 扩展的静态方法
     *
     * @var array of callbacks
     */
    public $static_methods = array();

    /**
     * 表数据入口
     *
     * @var QDB_Table
     */
    public $table;

    /**
     * Meta 对应的 ActiveRecord 继承类
     *
     * @var string
     */
    public $class_name;

    /**
     * 所有托管的对象
     *
     * @var array of QDB_ActiveRecord_Abstract objects
     */
    public $objects = array();

    /**
     * 行为插件对象
     *
     * @var array of QDB_ActiveRecord_Behavior_Abstract objects
     */
    private $_behaviors = array();

    /**
     * 托管对象的关联参考
     *
     * @var array
     */
    private $_objects_refs = array();

    /**
     * 关联参考到对象
     *
     * @var array
     */
    private $_refs_to_objects = array();

    /**
     * 指示是否已经初始化了对象的关联
     *
     * @var boolean
     */
    private $_links_inited = false;

    /**
     * 可用的对象聚合类型
     *
     * @var array
     */
    static private $_assoc_types = array(QDB::HAS_ONE, QDB::HAS_MANY, QDB::BELONGS_TO, QDB::MANY_TO_MANY);

    /**
     * 所有 ActiveRecord 继承类的 Meta 对象
     *
     * @var array of QDB_ActiveRecord_Meta
     */
    static private $_metas = array();

    /**
     * 构造函数
     *
     * @param string $class
     */
    protected function __construct($class)
    {
        $this->init($class);
    }

    /**
     * 获得指定指定 ActiveRecord 继承类的元对象唯一实例
     *
     * @param string $class
     *
     * @return QDB_ActiveRecord_Meta
     */
    static function getInstance($class)
    {
        if (!isset(self::$_metas[$class])) {
            self::$_metas[$class] = new QDB_ActiveRecord_Meta($class);
        }
        return self::$_metas[$class];
    }

    /**
     * 开启一个查询
     *
     * @return QDB_Select
     */
    function find()
    {
        return $this->findArgs(func_get_args());
    }

    /**
     * 开启一个查询
     *
     * @param array $args
     *
     * @return QDB_Select
     */
    function findArgs(array $args)
    {
        $this->initLinks();
        $select = new QDB_Select($this->table->conn);
        $select->asObject($this->class_name);
        $select->link($this->table->links);
        $select->from($this->table)->link($this->table->links);

        if (!empty($args)) {

            call_user_func_array(array($select, 'where'), $args);
        }

        return $select;
    }

    /**
     * 获得一个新的 Null 对象
     *
     * @return QDB_ActiveRecord_Abstract
     */
    function newNullObject()
    {
        $class_name = $this->class_name . '_Null';
        return new $class_name();
    }

    /**
     * 注册一个对象
     *
     * @param QDB_ActiveRecord_Abstract $object
     * @param array $batch_refs
     * @param mixed $query_id
     */
    function register(QDB_ActiveRecord_Abstract $object, array $batch_refs = null, $query_id = null)
    {
        $id = $object->id();
        $this->objects[$id] = $object;
        if (!is_null($batch_refs)) {
            $this->_refs_to_objects[$query_id] = $batch_refs;
            $this->_objects_refs[$id] = $query_id;
        }
    }

    /**
     * 检查指定 ID 的对象是否已经被注册
     *
     * @param mixed $id
     *
     * @return boolean
     */
    function isRegistered($id)
    {
        return isset($this->objects[$id]);
    }

    /**
     * 删除对一个对象的注册
     *
     * @param mixed $id
     */
    function unregister($id)
    {
        unset($this->objects[$id]);
        unset($this->_objects_refs[$id]);
    }

    /**
     * 组装指定 ID 对象的指定关联对象
     *
     * @param mixed $id
     * @param string $prop_name
     */
    function assembleAssocObjects($id, $prop_name)
    {
        $query_id = $this->_objects_refs[$id];
        if (isset($this->_refs_to_objects[$query_id][$prop_name])) {
            /**
             * refs_to_objects 是一个二维数组
             *
             * 格式是：
             *
             *    mapping_name => array(
             *      source_key_value => 对象ID
             *      ....
             *    ),
             */
            $refs = $this->_refs_to_objects[$query_id][$prop_name];
            $link = $this->table->links[$prop_name];
            /* @var $link QDB_Table_Link_Abstract */
            $target_meta = self::getInstance($this->props[$prop_name]['assoc_class']);
            /* @var $target_meta QDB_ActiveRecord_Meta */

            $target_values = array_keys($refs);
            switch ($link->type) {
            case QDB::HAS_ONE:
            case QDB::HAS_MANY:
            case QDB::BELONGS_TO:
                $where = array(array($link->target_key => $target_values));
                $objects = $target_meta->findArgs($where)
                                       ->all()
                                       ->queryObjectsForAssemble($this->table, $link, $target_meta);
                break;
            case QDB::MANY_TO_MANY:
//                $where = array(array($link->mid_table->qfields($link->mid_source_key) => $target_values));
//                $objects = QDB_Select::beginQueryForActiveRecord($target_meta, $where)
//                                           ->where(array($link->mid_target_key => $link->target_key))
//                                           ->all()
//                                           ->queryObjectsForAssemble($link->target_key, $link->target_key_alias, $target_meta);
                break;
            }

            // 将这些查询出来的对象指定给已有的各个对象
            if ($link->one_to_one) {
                foreach ($refs as $target_value => $source_obj_id) {
                    if (isset($objects[$target_value])) {
                        $this->objects[$source_obj_id]->{$prop_name} = $objects[$target_value][0];
                    } else {
                        $this->objects[$source_obj_id]->{$prop_name} = $target_meta->newNullObject();
                    }
                }
            } else {
                foreach ($refs as $target_value => $source_obj_id) {
                    $coll = QColl::createFromArray($objects[$target_value], $target_meta->class_name);
                    $this->objects[$source_obj_id]->{$prop_name} = $coll;
                }
            }
        } else {
            // LC_MSG: 查找 %s 类 "%s" 属性关联的对象时，使用了未登记的对象 ID: "%s".
            throw new QDB_ActiveRecord_Exception(__('查找 %s 类 "%s" 属性关联的对象时，使用了未登记的对象 ID: "%s".',
                                                    $this->class_name, $prop_name, $id));
        }
    }

    /**
     * 绑定行为插件
     *
     * @param string|array $behaviors
     * @param array $config
     */
    function bindBehaviors($behaviors, array $config = null)
    {
        $behaviors = Q::normalize($behaviors);
        if (!is_array($config)) { $config = array(); }

        // TODO: 载入行为插件时应该考虑到当前访问的 module
        $dirs = array(
            Q_DIR . '/qdb/activerecord/behavior',
            // ROOT_DIR . '/app/model/behavior',
        );

        foreach ($behaviors as $name) {
            $name = strtolower($name);
            // 已经绑定过的插件不再绑定
            if (isset($this->_behaviors[$name])) { continue; }

            // 载入插件
            $class = 'Behavior_' . ucfirst($name);
            if (!class_exists($class, false)) {
                $filename = $name . '_behavior.php';
                Q::loadClassFile($filename, $dirs, $class);
            }

            // 构造行为插件
            $settings = (!empty($config[$name])) ? $config[$name] : array();
            $this->_behaviors[$name] = new $class($this, $settings);
        }
    }

    /**
     * 添加一个动态方法
     *
     * @param string $method_name
     * @param callback $callback
     */
    function addDynamicMethod($method_name, $callback)
    {
        if (!empty($this->methods[$method_name])) {
            // LC_MSG: 指定的动态方法名 "%s" 已经存在于 "%s" 对象中.
            throw new QDB_ActiveRecord_Meta_Exception(__('指定的动态方法名 "%s" 已经存在于 "%s" 对象中.',
                                                         $method_name, $this->class_name));
        }
        $this->methods[$method_name] = $callback;
    }

    /**
     * 添加一个静态方法
     *
     * @param string $method_name
     * @param callback $callback
     */
    function addStaticMethod($method_name, $callback)
    {
        if (!empty($this->static_methods[$method_name])) {
            // LC_MSG: 指定的静态方法名 "%s" 已经存在于 "%s" 对象中.
            throw new QDB_ActiveRecord_Meta_Exception(__('指定的静态方法名 "%s" 已经存在于 "%s" 对象中.',
                                                         $method_name, $this->class_name));
        }
        $this->static_methods[$method_name] = $callback;
    }

    /**
     * 设置属性的 setter 方法
     *
     * @param string $name
     * @param callback $callback
     */
    function setPropSetter($name, $callback)
    {
        if (isset($this->props[$name])) {
            $this->props[$name]['setter'] = $callback;
        } else {
            $this->addProp($name, array('setter' => $callback));
        }
    }

    /**
     * 设置属性的 getter 方法
     *
     * @param string $name
     * @param callback $callback
     */
    function setPropGetter($name, $callback)
    {
        if (isset($this->props[$name])) {
            $this->props[$name]['getter'] = $callback;
        } else {
            $this->addProp($name, array('getter' => $callback));
        }
    }

    /**
     * 为指定事件添加处理方法
     *
     * @param int $event_type
     * @param callback $callback
     */
    function addEventHandler($event_type, $callback)
    {
        $this->callbacks[$event_type][] = $callback;
    }

    /**
     * 添加一个属性
     *
     * @param string $prop_name
     * @param array $params
     */
    function addProp($prop_name, array $config)
    {
        if (isset($this->prop2fields[$prop_name])) {
            // LC_MSG: 尝试添加的属性 "%s" 已经存在.
            throw new QDB_ActiveRecord_Meta_Exception(__('尝试添加的属性 "%s" 已经存在.', $prop_name));
        }
        $params = array('assoc' => false);
        $params['readonly'] = isset($config['readonly']) ? (bool)$config['readonly'] : false;

        // 确定属性和字段名之间的映射关系
        if (!empty($config['field_name'])) {
            // 指定属性是哪个字段的别名
            $this->prop2fields[$prop_name] = $config['field_name'];
            $this->fields2prop[$config['field_name']] = $prop_name;
            $field_name = $config['field_name'];
        } else {
            $this->prop2fields[$prop_name] = $prop_name;
            $this->fields2prop[$prop_name] = $prop_name;
            $field_name = $prop_name;
        }

        // 根据数据表的元信息确定属性是否是虚拟属性
        if (!empty($this->table_meta[$field_name])) {
            $params['virtual'] = false;
            if ($this->table_meta[$field_name]['has_default']) {
                $params['default_value'] = $this->table_meta[$field_name]['default'];
            } else {
                $params['default_value'] = null;
            }
        } else {
            $params['virtual'] = true;
            $params['default'] = null;
        }

        // 处理对象聚合
        foreach (self::$_assoc_types as $type) {
            if (empty($config[$type])) { continue; }
            $params['assoc'] = $type;
            $params['assoc_class'] = $config[$type];
            $params['assoc_params'] = $config;
        }

        // 设置属性信息
        $this->props[$prop_name] = $params;

        // 设置 getter 和 setter
        if (!empty($config['setter'])) {
            $this->setPropSetter($prop_name, $config['setter']);
        }
        if (!empty($config['getter'])) {
            $this->setPropGetter($prop_name, $config['getter']);
        }
    }

    /**
     * 添加一个对象聚合关联
     *
     * @param string $prop_name
     * @param int $assoc_type
     * @param array $params
     */
    function addAssoc($prop_name, $assoc_type, array $params)
    {
        $target_meta = QDB_ActiveRecord_Meta::getInstance($params['assoc_class']);

        switch ($assoc_type) {
        case QDB::HAS_ONE:
        case QDB::HAS_MANY:
            if (empty($params['target_key'])) {
                $params['target_key'] = strtolower($this->class_name) . '_id';
            }
            break;
        case QDB::BELONGS_TO:
            if (empty($params['source_key'])) {
                $params['source_key'] = strtolower($target_meta->class_name) . '_id';
            }
            break;
        case QDB::MANY_TO_MANY:
            if (empty($params['mid_source_key'])) {
                $params['mid_source_key'] = strtolower($this->class_name) . '_id';
            }
            if (empty($params['mid_target_key'])) {
                $params['mid_target_key'] = strtolower($target_meta->class_name) . '_id';
            }
        }

        $assoc = $params['assoc_params'];
        $assoc['mapping_name'] = $prop_name;

        $this->associations[$prop_name] = QDB_ActiveRecord_Association_Abstract::createLink($assoc_type, $assoc, $this);
    }

    /**
     * 初始化指定类的反射信息
     *
     * @param string $class
     */
    private function init($class)
    {
        // 从指定类获得初步的定义信息
        Q::loadClass($class);
        $this->class_name = $class;
        $ref = (array)call_user_func(array($class, '__define'));

        // 设置表数据入口对象
        $this->setTableFromRef($ref);
        $this->table_meta = $this->table->columns();

        // 根据字段定义确定字段属性
        if (empty($ref['props']) || !is_array($ref['props'])) {
            $ref['props'] = array();
        }
        foreach ($ref['props'] as $prop_name => $params) {
            $this->addProp($prop_name, $params);
        }

        // 将没有指定的字段也设置为对象属性
        foreach ($this->table_meta as $field_name => $field) {
            if (isset($this->fields2prop[$field_name])) { continue; }
            $this->addProp($field_name, $field);
        }

        // 绑定行为插件
        if (isset($ref['behaviors'])) {
            $config = isset($ref['behaviors_settings']) ? $ref['behaviors_settings'] : array();
            $this->bindBehaviors($ref['behaviors'], $config);
        }

        // 设置其他选项
        if (!empty($ref['validation']) && is_array($ref['validation'])) {
            $this->validation = $ref['validation'];
        }
        if (!empty($ref['create_reject'])) {
            $this->create_reject = array_flip(Q::normalize($ref['create_reject']));
        }
        if (!empty($ref['update_reject'])) {
            $this->update_reject = array_flip(Q::normalize($ref['update_reject']));
        }
        if (!empty($ref['create_autofill']) && is_array($ref['create_autofill'])) {
            $this->create_autofill = $ref['create_autofill'];
        }
        if (!empty($ref['update_autofill']) && is_array($ref['update_autofill'])) {
            $this->update_autofill = $ref['update_autofill'];
        }

        // 设置对象ID属性名
        $this->idname = $this->fields2prop[$this->table->pk];
    }

    /**
     * 根据反射信息设置表数据入口
     *
     * @param array $ref
     */
    private function setTableFromRef(array $ref)
    {
        // 获得提供持久化服务的表数据入口对象
        if (!empty($ref['table_name'])) {
            // 通过 table_name 指定数据表
            $obj_id = 'activerecord_table_' . strtolower($this->class_name);
            if (Q::isRegistered($obj_id)) {
                $this->table = Q::registry($obj_id);
            } else {
                Q::loadClass('QDB_Table');
                $table_params = isset($ref['table_params']) ? (array)$ref['table_params'] : array();
                $table_params['table_name'] = $ref['table_name'];
                $table = new QDB_Table($table_params);
                Q::register($table, $obj_id);
                $this->table = $table;
            }
        } elseif (!empty($ref['table_class'])) {
            // 通过 table_class 指定表数据入口
            $this->table = Q::getSingleton($ref['table_class']);
        }
    }

    /**
     * 实例化符合条件的对象，并调用对象的 destroy() 方法
     *
     * @param string $class
     * @param array $args
     */
    protected static function __destroyWhere($class, array $args)
    {
        $objs = self::__find($class, $args)->all()->query();
        foreach ($objs as $obj) {
            $obj->destroy();
        }
    }

    /**
     * 对数据进行验证，返回所有未通过验证数据的名称错误信息
     *
     * @param string $class
     * @param array $data
     * @param array|string $props
     *
     * @return array
     */
    function validate($class, array $data, $props = null)
    {
        $meta = self::getInstance($class);
        if (!is_null($props)) {
            $props = Q::normalize($props);
            $props = array_flip($props);
        } else {
            $props = $meta->prop2fields;
        }

        $error = array();
        $v = new QValidate_Validator(null);

        foreach ($meta->validation as $prop => $rules) {
            if (!isset($props[$prop])) { continue; }
            if (is_object($data[$prop]) && ($data[$prop] instanceof QDB_ActiveRecord_RemovedProp)) { continue; }

            $v->setData($data[$prop]);
            $v->id = $prop;
            foreach ($rules as $rule) {
                $check = $rule[0];
                if (is_array($check)) {
                    $rule[0] = $data[$prop];
                    $check = reset($check);
                    if (!call_user_func_array(array($class, $check), $rule)) {
                        $error[$prop][$check] = $rule[count($rule) - 1];
                    }
                } else {
                    $v->runRule($rule);
                }
            }

            if (!$v->isPassed()) {
                $error[$prop] = $v->getFailed();
            }
        }
        return $error;
    }

    /**
     * 初始化对象间的关联
     */
    function initLinks()
    {
        if ($this->_links_inited) { return; }
        $this->_links_inited = true;
        foreach ($this->props as $prop_name => $params) {
            if (!$params['assoc']) { continue; }

            $this->addAssoc($prop_name, $params['assoc'], $params);
        }
    }
}
