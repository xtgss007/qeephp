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
     * 对象间的关联
     *
     * @var array of QDB_Link_Abstract
     */
    public $links = array();

    /**
     * Meta 对应的 ActiveRecord 继承类
     *
     * @var string
     */
    protected $class_name;

    /**
     * 表数据入口
     *
     * @var QDB_Table
     */
    protected $table;

    /**
     * 行为插件对象
     *
     * @var array of QDB_ActiveRecord_Behavior_Abstract objects
     */
    private $behaviors = array();

    /**
     * 可用的对象聚合类型
     *
     * @var array
     */
    static private $assoc_types = array('has_one', 'has_many', 'belongs_to', 'many_to_many');

    /**
     * 所有 ActiveRecord 继承类的 Meta 对象
     *
     * @var array of QDB_ActiveRecord_Meta
     */
    static private $metas = array();

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
     * 获得指定 class 对应的唯一实例
     *
     * @param string $class
     *
     * @return QDB_ActiveRecord_Meta
     */
    static function getInstance($class)
    {
        if (!isset(self::$metas[$class])) {
            self::$metas[$class] = new QDB_ActiveRecord_Meta($class);
        }
        return self::$metas[$class];
    }

    /**
     * 返回 ActiveRecord 继承类的类名称
     *
     * @return string
     */
    function getClassName()
    {
        return $this->class_name;
    }

    /**
     * 返回当前使用的表数据入口
     *
     * @return QDB_Table
     */
    function getTable()
    {
        return $this->table;
    }

    /**
     * 设置要使用的表数据入口对象
     *
     * @param QDB_Table $table
     */
    function setTable(QDB_Table $table)
    {
        $this->table = $table;
    }

    /**
     * 开启一个查询
     *
     * @param arary $where
     *
     * @return QDB_ActiveRecord_Select
     */
    function find(array $where)
    {
        return new QDB_ActiveRecord_Select($this, $where);
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
            ROOT_DIR . '/app/model/behavior',
        );

        foreach ($behaviors as $name) {
            $name = strtolower($name);
            // 已经绑定过的插件不再绑定
            if (isset($this->behaviors[$name])) { continue; }

            // 载入插件
            $class = 'Behavior_' . ucfirst($name);
            if (!class_exists($class, false)) {
                $filename = $name . '_behavior.php';
                Q::loadClassFile($filename, $dirs, $class);
            }

            // 构造行为插件
            $settings = (!empty($config[$name])) ? $config[$name] : array();
            $this->behaviors[$name] = new $class($this, $settings);
        }
    }


    /**
     * 为一个 ActiveRecord 类定义一个关联
     *
     * @param string $class
     * @param string $mapping_name
     * @param array $options
     */
    static function bind($class, $mapping_name, array $options)
    {
        self::reflection($class);

        $define = array('public' => false);
        $define['readonly'] = isset($options['readonly']) ? $options['readonly'] : false;
        $define['alias']    = isset($options['alias'])    ? $options['alias']    : $mapping_name;
        $define['getter']   = isset($options['getter'])   ? $options['getter']   : null;
        $define['setter']   = isset($options['setter'])   ? $options['setter']   : null;
        $define['default']  = null;

        if (!empty($options['has_one'])) {
            $define['assoc'] = 'has_one';
            $define['class'] = $options['has_one'];
        }
        if (!empty($options['has_many'])) {
            $define['assoc'] = 'has_many';
            $define['class'] = $options['has_many'];
        }
        if (!empty($options['belongs_to'])) {
            $define['assoc'] = 'belongs_to';
            $define['class'] = $options['belongs_to'];
        }
        if (!empty($options['many_to_many'])) {
            $define['assoc'] = 'many_to_many';
            $define['class'] = $options['many_to_many'];
        }

        unset($options['readonly']);
        unset($options['alias']);
        unset($options['setter']);
        unset($options['getter']);
        $define['assoc_options'] = $options;
        $define['virtual'] = true;

        self::$__ref[$class]['attribs'][$mapping_name] = $define;
        self::$__ref[$class]['alias'][$mapping_name] = $define['alias'];
        self::$__ref[$class]['ralias'][$mapping_name['alias']] = $mapping_name;
        self::$__ref[$class]['links'][$mapping_name] = $define;
    }


    /**
     * 完成对象间的关联
     *
     * @param string $class
     */
    private static function __bindAll($class)
    {
        self::reflection($class);
        $table = self::$__ref[$class]['table'];
        /* @var $table QDB_Table */
        foreach (self::$__ref[$class]['links'] as $define) {
            $mapping_name = $define['alias'];
            if ($table->existsLink($mapping_name)) { continue; }
            $ref = QDB_ActiveRecord_Abstract::reflection($define['class']);
            $assoc_table = $ref['table'];

            $link = $define['assoc_options'];
            $link['table_obj'] = $assoc_table;
            $link['mapping_name'] = $define['alias'];

            switch ($define['assoc']) {
            case QDB_Table::has_one:
            case QDB_Table::has_many:
                if (empty($link['assoc_key'])) {
                    $link['assoc_key'] = strtolower($class) . '_id';
                }
                break;
            case QDB_Table::belongs_to:
                if (empty($link['main_key'])) {
                    $link['main_key'] = strtolower($define['class']) . '_id';
                }
                break;
            case QDB_Table::many_to_many:
                if (empty($link['mid_main_key'])) {
                    $link['mid_main_key'] = strtolower($class) . '_id';
                }
                if (empty($link['mid_assoc_key'])) {
                    $link['mid_assoc_key'] = strtolower($define['class']) . '_id';
                }
            }

            $table->createLinks($link, $define['assoc']);
            $table->getLink($define['alias'])->init();
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

        // 处理对象聚合
        foreach (self::$assoc_types as $type) {
            if (empty($config[$type])) { continue; }
            $params['assoc'] = $type;
            $params['assoc_class'] = $config[$type];
            $params['assoc_params'] = (!empty($config['assoc_params'])) ? (array)$config['assoc_params'] : null;
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
        if (isset($this->ref['behaviors'])) {
            $config = isset($this->ref['behaviors_settings']) ? $this->ref['behaviors_settings'] : array();
            $this->bindBehaviors($this->ref['behaviors'], $config);
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
                $this->setTable(Q::registry($obj_id));
            } else {
                Q::loadClass('QDB_Table');
                $table_params = isset($ref['table_params']) ? (array)$ref['table_params'] : array();
                $table_params['table_name'] = $ref['table_name'];
                $table = new QDB_Table($table_params);
                Q::register($table, $obj_id);
                $this->setTable($table);
            }
        } elseif (!empty($ref['table_class'])) {
            // 通过 table_class 指定表数据入口
            $this->setTable(Q::getSingleton($ref['table_class']));
        }
    }


    /**
     * 开启一个查询
     *
     * @param string $class
     * @param array $args
     *
     * @return QDB_Table_Select
     */
    protected static function __find($class, array $args)
    {
        self::__bindAll($class);
        $select = QDB_Table_Select::beginSelectFromActiveRecord($class, self::$__ref[$class]['table']);
        if (!empty(self::$__callbacks[$class][self::after_find])) {
            $select->bindCallbacks(self::$__callbacks[$class][self::after_find]);
        }
        if (!empty($args)) {
            call_user_func_array(array($select, 'where'), $args);
        }
        return $select;
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
    protected static function __validate($class, array $data, $props = null)
    {
        self::reflection($class);
        if (!is_null($props)) {
            $props = Q::normalize($props);
            $props = array_flip($props);
        } else {
            $props = self::$__ref[$class]['ralias'];
        }

        $error = array();
        $v = new QValidate_Validator(null);

        foreach (self::$__ref[$class]['validation'] as $prop => $rules) {
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


}
