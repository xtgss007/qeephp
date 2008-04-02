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
 * 定义 QDB_ActiveRecord_Abstract 抽象类
 *
 * @package database
 * @version $Id$
 */

/**
 * QDB_ActiveRecord_Abstract 类实现了 Active Record 模式
 *
 * @package database
 */
abstract class QDB_ActiveRecord_Abstract implements QDB_ActiveRecord_Interface
{
    /**
     * 预定义的事件
     */
    const after_find                  = 'after_find';                   // 查询后
    const after_initialize            = 'after_initialize';             // 初始化后

    const before_save                 = 'before_save';                  // 保存之前
    const after_save                  = 'after_save';                   // 保存之后

    const before_create               = 'before_create';                // 创建之前
    const after_create                = 'after_create';                 // 创建之后

    const before_update               = 'before_update';                // 更新之前
    const after_update                = 'after_update';                 // 更新之后

    const before_validation           = 'before_validation';            // 验证之前
    const after_validation            = 'after_validation';             // 验证之后

    const before_validation_on_create = 'before_validation_on_create';  // 创建记录验证之前
    const after_validation_on_create  = 'after_validation_on_create';   // 创建记录验证之后

    const before_validation_on_update = 'before_validation_on_update';  // 更新记录验证之前
    const after_validation_on_update  = 'after_validation_on_update';   // 更新记录验证之后

    const before_destroy              = 'before_destroy';               // 销毁之前
    const after_destroy               = 'after_destroy';                // 销毁之后

    /**
     * 其他类型 callback
     */
    const custom_callback             = 'custom_callback';              // 行为插件自定义方法

    /**
     * 属性方法
     */
    const getter                      = 'getter';                       // 读属性
    const setter                      = 'setter';                       // 写属性

    /**
     * 对象所有属性的引用
     *
     * @var array
     */
    protected $__all_props;

    /**
     * 当前对象的类名
     *
     * @var string
     */
    protected $__class;

    /**
     * 对象不允许直接访问的属性
     *
     * @var array
     */
    private $__props;

    /**
     * 对象属性的读写方法
     *
     * @var array
     */
    private $__props_events;

    /**
     * 事件钩子
     *
     * @var array
     */
    private static $__callbacks = array();

    /**
     * 扩展的方法
     *
     * @var array
     */
    private static $__methods = array();

    /**
     * 所有用到的 ActiveRecord 对象的定义
     *
     * @var array
     */
    private static $__ref = array();

    /**
     * 构造函数
     *
     * @param array $data
     */
    function __construct(array $data = null)
    {
        $class_name = get_class($this);
        if (substr($class_name, -4) == 'null') {
            $class_name = substr($class_name, 0, -4);
        }
        $this->__class = $class_name;
        self::reflection($this->__class);

        if (is_array($data)) {
            $this->__attach($data);
        } else {
            $this->__attach(array());
        }
        $this->__doCallbacks(self::after_initialize);
        $this->afterInitialize();
    }

    /**
     * 批量设置对象的属性值（忽略只读的属性和关联对象）
     *
     * @param array $props
     */
    function setProps(array $props)
    {
        foreach (self::$__ref[$this->__class]['attribs'] as $field => $attr) {
            if ($attr['readonly'] || $attr['assoc'] || !array_key_exists($attr['alias'], $props)) { continue; }
            $this->{$field} = $props[$attr['alias']];
        }
    }

    /**
     * 保存对象到数据库
     *
     * @param boolean $force_create 是否强制创建新记录
     * @param int $recursion
     */
    function save($force_create = false, $recursion = 99)
    {
        self::__bindAll($this->__class);
        $this->beforeSave();
        $this->__doCallbacks(self::before_save);
        $id = $this->id();
        if (empty($id) || $force_create) {
            $this->create($recursion);
        } else {
            $this->update($recursion);
        }
        $this->__doCallbacks(self::after_save);
        $this->afterSave();
    }

    /**
     * 从数据库重新读取对象
     *
     * @param int $recursion
     */
    function reload($recursion = 1)
    {
        $arr = $this->getTable()
                    ->find(array($this->idname() => $this->id()))
                    ->recursion($recursion)
                    ->asArray()
                    ->query();
        $this->__attach($arr);
    }

    /**
     * 验证对象属性
     *
     * @param string $mode
     */
    function doValidate($mode = 'general')
    {
        $this->beforeValidation();
        $this->__doCallbacks(self::before_validation);
        if ($mode == 'create') {
            $this->beforeValidationOnCreate();
            $this->__doCallbacks(self::before_validation_on_create);
        } elseif ($mode == 'update') {
            $this->beforeValidationOnUpdate();
            $this->__doCallbacks(self::before_validation_on_update);
        }

        // 进行验证
        $error = self::__validate($this->__class, $this->__all_props);
        if (!empty($error)) {
            throw new QDB_ActiveRecord_Validate_Exception($this, $error);
        }

        if ($mode == 'create') {
            $this->afterValidationOnCreate();
            $this->__doCallbacks(self::after_validation_on_create);
        } elseif ($mode == 'update') {
            $this->afterValidationOnUpdate();
            $this->__doCallbacks(self::after_validation_on_update);
        }
        $this->__doCallbacks(self::after_validation);
        $this->afterValidation();
    }

    /**
     * 销毁对象对应的数据库记录
     *
     * @param int $recursion
     */
    function destroy($recursion = 99)
    {
        $this->beforeDestroy();
        $this->__doCallbacks(self::before_destroy);
        $table = self::$__ref[$this->__class]['table'];
        /* @var $table QDB_Table */
        $table->remove($this->id(), $recursion);
        $this->__doCallbacks(self::after_destroy);
        $this->afterDestroy();
    }

    /**
     * 获得对象ID（对象在数据库中的主键值）
     *
     * @return mixed
     */
    function id()
    {
        $pk = self::$__ref[$this->__class]['pk'];
        return $this->{$pk};
    }

    /**
     * 获得对象的ID属性名（对象在数据库中的主键字段名）
     *
     * @return string
     */
    function idname()
    {
        return self::$__ref[$this->__class]['pk'];
    }

    /**
     * 获得包含对象所有属性的数组
     *
     * @param int $recursion
     *
     * @return array
     */
    function toArray($recursion = 99)
    {
        $row = array();
        $attribs = self::$__ref[$this->__class]['attribs'];
        // ralias 是 别名 => 实际字段名
        $ralias = self::$__ref[$this->__class]['ralias'];

        foreach (array_keys($this->__all_props) as $a) {
            $f = $ralias[$a];
            if ($attribs[$f]['assoc']) {
                if ($recursion <= 0) { continue; }
                if (is_array($this->__all_props[$a]) || $this->__all_props[$a] instanceof Iterator) {
                    $row[$f] = array();
                    foreach ($this->__all_props[$a] as $obj) {
                        $row[$f][] = $obj->toArray($recursion - 1);
                    }
                } else {
                    $row[$f] = $this->__all_props[$a]->toArray($recursion - 1);
                }
            } else {
                $row[$f] = isset($this->__all_props[$a]) ? $this->__all_props[$a] : $this->{$a};
            }
        }
        return $row;
    }

    /**
     * 返回对象所有属性的 JSON 字符串
     *
     * @return string
     */
    function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * 返回该对象使用的表数据入口对象
     *
     * @return QDB_Table
     */
    function getTable()
    {
        return self::$__ref[$this->__class]['table'];
    }

    /**
     * 魔法方法，实现对只读属性和属性方法的支持
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        if (!isset(self::$__ref[$this->__class]['attribs'][$varname])) {
            // LC_MSG: Property "%s" not defined.
            throw new QDB_ActiveRecord_Exception(__('Property "%s" not defined.', $varname));
        }
        $attr = self::$__ref[$this->__class]['attribs'][$varname];
        if (isset($attr['getter'])) {
            if (!is_array($attr['getter'])) {
                $attr['getter'] = array($this, $attr['getter']);
            }
            return call_user_func($attr['getter'], $varname);
        }
        if (!empty($attr['class']) && !is_object($this->__props[$varname])) {
            if ($attr['assoc'] == 'has_many' || $attr['assoc'] == 'many_to_many') {
                $this->{$varname} = array();
                return array();
            } else {
                // 如果请求的关联对象尚不存在，则尝试从数据库读取该关联对象

                // 如果读取不到，则尝试构造一个 Null 对象
                $class_name = $attr['class'] . 'Null';
                if (!class_exists($class_name, false)) {
                    $text = <<<EOT
class {$class_name} extends {$attr['class']}
{
    function __construct(array \$data = null)
    {
        parent::__construct();
    }

    function setProps(array \$props)
    {
    }

    function save(\$force_create = false, \$recursion = 99)
    {
    }

    function reload(\$recursion = 1)
    {
    }

    function doValidate(\$mode = 'general')
    {
    }

    function destroy(\$recursion = 99)
    {
    }

    function id()
    {
        return null;
    }

    protected function create(\$recursion = 99)
    {
    }

    protected function update(\$recursion = 99)
    {
    }
}

EOT;
                    eval($text);
                }
                $obj = new $class_name();
                $this->{$varname} = $obj;
                return $obj;
            }
        } else {
            return $this->__props[$varname];
        }
    }

    /**
     * 魔法方法，实现只读属性的支持
     *
     * @param string $varname
     * @param mixed $value
     */
    function __set($varname, $value)
    {
        if (!isset(self::$__ref[$this->__class]['attribs'][$varname])) {
            $this->{$varname} = $value;
            return;
        }
        $attr = self::$__ref[$this->__class]['attribs'][$varname];
//        if ($attr['readonly']) {
//            // LC_MSG: Property "%s" is readonly.
//            throw new QException(__('Property "%s" is readonly.', $varname));
//        }
        if (isset($attr['setter'])) {
            call_user_func($attr['setter'], $value);
            return;
        }

        if (!$attr['assoc']) {
            if (isset($this->__props[$varname])) {
                $this->__props[$varname] = $value;
                $this->__all_props[$varname] =& $this->__props[$varname];
            } else {
                $this->{$varname} = $value;
                $this->__all_props[$varname] =& $this->{$varname};
            }
            return;
        }

        if ($attr['assoc'] == 'has_many' || $attr['assoc'] == 'many_to_many') {
            // 聚合的对象，要求 $value 必须是一个包含特定类型对象的数组
            if (!is_array($value) && !($value instanceof Iterator)) {
                // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                throw new QDB_ActiveRecord_Exception(__($msg, $varname, 'array', gettype($value)));
            }
            foreach ($value as $obj) {
                if (!is_object($obj) || !($obj instanceof $attr['class'])) {
                    // LC_MSG: Property "%s[]" type mismatch. expected is "%s", actual is "%s".
                    $msg = 'Property "%s[]" type mismatch. expected is "%s", actual is "%s".';
                    throw new QDB_ActiveRecord_Exception(__($msg, $varname, $attr['class'], gettype($obj)));
                }
            }
            $this->{$varname} = $value;
            $this->__all_props[$varname] =& $this->{$varname};
        } else {
            if (!is_object($value) || !($value instanceof $attr['class'])) {
                // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                throw new QDB_ActiveRecord_Exception(__($msg, $varname, $attr['class'], get_class($value)));
            }
            $this->__props[$varname] = $value;
            $this->__all_props[$varname] =& $this->__props[$varname];
        }
    }

    /**
     * 魔法方法，用于调用行为插件为对象添加的方法
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    function __call($method, $args = array())
    {
        if (!is_array($args)) {
            $args = array();
        }
        if (isset(self::$__methods[$this->__class][$method])) {
            array_unshift($args, $this->__all_props);
            array_unshift($args, $this);
            return call_user_func_array(self::$__methods[$this->__class][$method], $args);
        } else {
            // LC_MSG: Call to undefined method "%s::%s()".
            throw new QDB_ActiveRecord_Exception(__('Call to undefined method "%s::%s()".', $this->__class, $method));
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
     * 为一个 ActiveRecord 类定义一个关联
     *
     * @param string $class
     * @param string $field
     * @param array $options
     */
    static function unbind($class, $mapping_name)
    {
        unset(self::$__ref[$class]['attribs'][$mapping_name]);
        unset(self::$__ref[$class]['alias'][$mapping_name]);
        unset(self::$__ref[$class]['ralias'][$mapping_name]);
        unset(self::$__ref[$class]['links'][$mapping_name]);
    }

    /**
     * 获得一个模型类的反射信息
     *
     * @param string $class
     *
     * @return array
     */
    static function reflection($class)
    {
        if (isset(self::$__ref[$class])) {
            return self::$__ref[$class];
        }

        Q::loadClass($class);
        $ref = call_user_func(array($class, '__define'));
        if (empty($ref['fields']) || !is_array($ref['fields'])) {
            $ref['fields'] = array();
        }
        if (empty($ref['validation']) || !is_array($ref['validation'])) {
            $ref['validation'] = array();
        }
        if (!empty($ref['create_reject'])) {
            $ref['create_reject'] = Q::normalize($ref['create_reject']);
        } else {
            $ref['create_reject'] = array();
        }
        if (!empty($ref['update_reject'])) {
            $ref['update_reject'] = Q::normalize($ref['update_reject']);
        } else {
            $ref['update_reject'] = array();
        }
        if (empty($ref['create_autofill']) || is_array($ref['create_autofill'])) {
            $ref['create_autofill'] = array();
        }
        if (empty($ref['update_autofill']) || is_array($ref['update_autofill'])) {
            $ref['update_autofill'] = array();
        }

        // 构造表数据入口
        if (!empty($ref['table_name'])) {
            // 通过 table_name 指定数据表
            $obj_id = 'activerecord_table_' . strtolower($class);
            if (Q::isRegistered($obj_id)) {
                $ref['table'] = Q::registry($obj_id);
            } else {
                Q::loadClass('QDB_Table');
                $params = array('table_name' => $ref['table_name']);
                foreach ($ref as $key => $value) {
                    if (substr($key, 0, 6) == 'table_' && $key != 'table_name') {
                        $params[substr($key, 6)] = $value;
                    }
                }
                $ref['table'] = new QDB_Table($params, true);
                Q::register($ref['table'], $obj_id);
            }
        } elseif (!empty($ref['table_class'])) {
            $ref['table'] = Q::getSingleton($ref['table_class']);
            $ref['table']->connect();
        }

        if (!isset($ref['table'])) {
            QDebug::dump($ref, $class);
            QDebug::dumpTrace();
            exit;
        }
        $ref['pk'] = $ref['table']->pk;

        // 绑定行为插件
        self::$__callbacks[$class] = array();
        self::$__methods[$class] = array();

        $behaviors = isset($ref['behaviors']) ? Q::normalize($ref['behaviors']) : array();
        $dirs = array(
            Q_DIR . '/qdb/activerecord/behavior',
            ROOT_DIR . '/app/model/behavior',
        );
        $behaviors_objs = array();
        foreach ($behaviors as $behavior) {
            $behavior = strtolower($behavior);
            $behavior_class = 'Behavior_' . ucfirst($behavior);

            if (!class_exists($behavior_class, false)) {
                $filename = $behavior . '_behavior.php';
                Q::loadClassFile($filename, $dirs, $behavior_class);
            }
            if (!empty($ref['behaviors_settings'][$behavior])) {
                $settings = $ref['behaviors_settings'][$behavior];
            } else {
                $settings = array();
            }
            $behavior_obj = new $behavior_class($class, $settings);
            /* @var $behavior_obj QDB_ActiveRecord_Behavior_Abstract */
            $callbacks = $behavior_obj->__callbacks($class);
            $behaviors_objs[] = $behavior_obj;

            foreach ($callbacks as $call) {
                list($type, $method) = $call;
                switch ($type) {
                case self::custom_callback:
                    if (is_array($method)) {
                        // array($obj/$class, $method) 形式的 callback
                        self::$__methods[$class][$method[1]] = $method;
                    } else {
                        self::$__methods[$class][$method] = $method;
                    }
                    break;
                case self::setter:
                    $ref['fields'][$method] = array('setter' => array($behavior_obj, 'set' . ucfirst($method)));
                    break;
                case self::getter:
                    $ref['fields'][$method] = array('getter' => array($behavior_obj, 'get' . ucfirst($method)));
                    break;
                default:
                    self::$__callbacks[$class][$type][] = $method;
                }
            }
        }

        // 根据字段定义确定字段属性
        $meta = $ref['table']->columns();
        $ref['links'] = array();
        $ref['alias'] = array();
        if (isset($ref['fields']) && is_array($ref['fields'])) {
            foreach ($ref['fields'] as $field => $options) {
                $define = array('public' => true, 'readonly' => false, 'assoc' => false);

                if (!is_array($options)) {
                    // 假定为别名
                    $define['alias'] = $options;
                } else {
                    $define['readonly'] = isset($options['readonly']) ? (bool)$options['readonly'] : false;
                    if (!empty($options['setter'])) {
                        $define['public'] = false;
                        $define['setter'] = $options['setter'];
                    }
                    if (!empty($options['getter'])) {
                        $define['public'] = false;
                        $define['getter'] = $options['getter'];
                    }
                    if (!empty($options['alias'])) {
                        $ref['alias'][$field] = $options['alias'];
                    } else {
                        $ref['alias'][$field] = $field;
                    }
                    $define['alias'] = $ref['alias'][$field];

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
                    if ($define['assoc']) {
                        unset($options['readonly']);
                        unset($options['alias']);
                        unset($options['setter']);
                        unset($options['getter']);
                        $define['assoc_options'] = $options;
                        $ref['links'][$field] = $define;
                    }
                }

                // 根据 META 确定属性是否是虚拟属性
                $define['virtual'] = empty($meta[$field]);

                // 根据 META 确定属性的默认值
                if (!empty($meta[$field]) && $meta[$field]['has_default']) {
                    $define['default'] = $meta[$field]['default'];
                } else {
                    $define['default'] = null;
                }
                $attribs[$field] = $define;
            }
        }

        // 将没有指定的字段也设置为对象属性
        foreach ($meta as $key => $field) {
            if (!isset($attribs[$key])) {
                $ref['alias'][$key] = $key;
                $attribs[$key] = array(
                    'public'    => true,
                    'readonly'  => false,
                    'assoc'     => false,
                    'alias'     => $key,
                    'virtual'   => false,
                    'default'   => ($field['has_default']) ? $field['default'] : null,
                );
            }
        }
        $ref['meta'] = $meta;
        $ref['attribs'] = $attribs;
        // ralias 是别名 => 字段名的映射
        $ref['ralias'] = array_flip($ref['alias']);
        unset($ref['fields']);

        self::$__ref[$class] = $ref;

        foreach ($behaviors_objs as $behavior_obj) {
            /* @var $behavior_obj QDB_ActiveRecord_Behavior_Abstract */
            $behavior_obj->__bindFinished($ref);
        }
        return $ref;
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
     * 将对象附着到一个包含对象属性的数组，等同于将数组的属性值复制到对象
     *
     * @param array $row
     */
    private function __attach(array $row)
    {
        $alias = self::$__ref[$this->__class]['alias'];
        foreach (self::$__ref[$this->__class]['attribs'] as $field => $define) {
            if ($define['virtual'] && !$define['assoc']) { continue; }
            if (!isset($row[$field]) && $define['assoc'] == false) {
                $row[$field] = self::$__ref[$this->__class]['attribs'][$field]['default'];
            }

            $a = isset($alias[$field]) ? $alias[$field] : $field;

            if ($define['readonly'] || !$define['public'] || $define['assoc']) {
                if ($define['assoc']) {
                    if ($define['assoc'] == 'has_many' ||  $define['assoc'] == 'many_to_many') {
                        $this->__props[$a] = new QColl($define['class']);
                    }
                    if (!isset($row[$field])) {
                        $this->__props[$a] = null;
                        continue;
                    } elseif (!is_array($row[$field])) {
                        // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                        $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                        throw new QDB_ActiveRecord_Exception(__($msg, "\$row[{$field}]", 'array', gettype($row[$field])));
                    } else {
                        if ($define['assoc'] == 'has_one' ||  $define['assoc'] == 'belongs_to') {
                            $this->__props[$a] = new $define['class']($row[$field]);
                        } else {
                            foreach ($row[$field] as $assoc_row) {
                                $this->__props[$a][] = new $define['class']($assoc_row);
                            }
                        }
                    }
                    $this->__all_props[$a] =& $this->__props[$a];
                } else {
                    $this->__props[$a] = $row[$field];
                    $this->__all_props[$a] =& $this->__props[$a];
                }
            } else {
                $this->{$a} = $row[$field];
                $this->__all_props[$a] =& $this->{$a};
            }
        }
    }

    /**
     * 在数据库中创建对象
     *
     * @param int $recursion
     */
    protected function create($recursion = 99)
    {
        $keys = array();
        foreach (self::$__ref[$this->__class]['create_reject'] as $f) {
            $keys[$f] = self::$__ref[$this->__class]['ralias'][$f];
            $this->__all_props[$f] = null;
        }
        foreach (self::$__ref[$this->__class]['create_autofill'] as $f => $fill) {
            $this->__all_props[$f] = $fill;
        }

        $this->doValidate('create');
        $this->beforeCreate();
        $this->__doCallbacks(self::before_create);

        $row = $this->toArray(0);

        foreach (self::$__ref[$this->__class]['create_reject'] as $f) {
            if (is_null($this->__all_props[$f])) {
                unset($row[$keys[$f]]);
            }
        }
        $table = self::$__ref[$this->__class]['table'];
        /* @var $table QDB_Table */
        $id = $table->create($row, 0);
        $this->__all_props[$this->idname()] = $id;

        foreach (self::$__ref[$this->__class]['links'] as $prop_name => $null) {
            $link = self::$__ref[$this->__class]['table']->getLink($prop_name);
            /* @var $link QDB_Table_Link */
            $mk = $this->alias_name($link->main_key);

            if ($link->type == QDB_Table::has_one || $link->type == QDB_Table::belongs_to) {
                if (!isset($this->__all_props[$prop_name]) || !is_object($this->__all_props[$prop_name])) {
                    continue;
                }
                $obj = $this->__all_props[$prop_name];
                $ak = $obj->alias_name($link->assoc_key);
                $obj->{$ak} = $this->{$mk};
                $obj->save();
//                $row = $this->__all_props[$prop_name]->toArray(0);
//                $link->saveAssocData($row, $this->__all_props[$link->main_key], 0);
            } else {
//                $rowset = array();
                $ak = null;
                $mkv = $this->{$mk};
                foreach ($this->__all_props[$prop_name] as $obj) {
                    if (is_null($ak)) { $ak = $obj->alias_name($link->assoc_key); }
                    $obj->{$ak} = $mkv;
                    $obj->save();
//                    $rowset[] = $obj->toArray(0);
                }
//                $link->saveAssocData($rowset, $this->__all_props[$link->main_key], 0);
            }
        }

        $this->__all_props[$this->idname()] = $id;
        $this->__doCallbacks(self::after_create);
        $this->afterCreate();
    }

    /**
     * 更新对象到数据库
     *
     * @param int $recursion
     */
    protected function update($recursion = 99)
    {
        $keys = array();
        foreach (self::$__ref[$this->__class]['update_reject'] as $f) {
            $keys[$f] = self::$__ref[$this->__class]['ralias'][$f];
            $this->__all_props[$f] = null;
        }
        foreach (self::$__ref[$this->__class]['update_autofill'] as $f => $fill) {
            $this->__all_props[$f] = $fill;
        }

        /* @var $table QDB_Table */
        $this->doValidate('update');
        $this->beforeUpdate();
        $this->__doCallbacks(self::before_update);

        $row = $this->toArray(0);

        foreach (self::$__ref[$this->__class]['update_reject'] as $f) {
            if ($this->__all_props[$f] === null) {
                unset($row[$keys[$f]]);
            }
        }
        $table = self::$__ref[$this->__class]['table'];
        $table->update($row, 0);

        foreach (self::$__ref[$this->__class]['links'] as $prop_name => $null) {
            if (!isset($this->__all_props[$prop_name])) { continue; }

            $link = self::$__ref[$this->__class]['table']->getLink($prop_name);
            /* @var $link QDB_Table_Link */
            $mk = $this->alias_name($link->main_key);
            if ($link->type == QDB_Table::has_one || $link->type == QDB_Table::belongs_to) {
                if (!isset($this->__all_props[$prop_name]) || !is_object($this->__all_props[$prop_name])) {
                    continue;
                }
                $obj = $this->__all_props[$prop_name];
                $ak = $obj->alias_name($link->assoc_key);
                $obj->{$ak} = $this->{$mk};
                $obj->save();
//                $row = $this->__all_props[$prop_name]->toArray(0);
//                $link->saveAssocData($row, $this->__all_props[$link->main_key], 0);
            } else {
//                $rowset = array();
                $ak = null;
                $mkv = $this->{$mk};
                foreach ($this->__all_props[$prop_name] as $obj) {
                    if (is_null($ak)) { $ak = $obj->alias_name($link->assoc_key); }
                    $obj->{$ak} = $mkv;
                    $obj->save();
//                    $rowset[] = $obj->toArray(0);
                }
//                $link->saveAssocData($rowset, $this->__all_props[$link->main_key], 0);
            }
        }

        $this->__doCallbacks(self::after_update);
        $this->afterUpdate();
    }

    /**
     * 取得字段名的别名
     *
     * @param string $field
     *
     * @return string
     */
    protected function alias_name($field)
    {
        return self::$__ref[$this->__class]['alias'][$field];
    }

    /**
     * 取得别名对应的字段名
     *
     * @param string $alias
     *
     * @return string
     */
    protected function field_name($alias)
    {
        return self::$__ref[$this->__class]['ralias'][$alias];
    }

    /**
     * 开启一个查询
     *
     * @param string $class
     * @param array $args
     *
     * @return QDB_Select
     */
    protected static function __find($class, array $args)
    {
        self::__bindAll($class);
        $select = QDB_Select::beginSelectFromActiveRecord($class, self::$__ref[$class]['table']);
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
     * 调用指定类型的 callback 方法
     */
    protected function __doCallbacks($type)
    {
        if (!isset(self::$__callbacks[$this->__class][$type])) { return; }
        foreach (self::$__callbacks[$this->__class][$type] as $callback) {
            call_user_func_array($callback, array($this, $this->__all_props));
        }
    }

    /**
     * 事件回调：开始验证之前
     */
    protected function beforeValidation()
    {
    }

    /**
     * 事件回调：为创建记录进行的验证开始之前
     */
    protected function beforeValidationOnCreate()
    {
    }

    /**
     * 事件回调：为创建记录进行的验证完成之后
     */
    protected function afterValidationOnCreate()
    {
    }

    /**
     * 事件回调：为更新记录进行的验证开始之前
     */
    protected function beforeValidationOnUpdate()
    {
    }

    /**
     * 事件回调：为更新记录进行的验证完成之后
     */
    protected function afterValidationOnUpdate()
    {
    }

    /**
     * 事件回调：验证完成之后
     */
    protected function afterValidation()
    {
    }

    /**
     * 事件回调：保存记录之前
     */
    protected function beforeSave()
    {
    }

    /**
     * 事件回调：保存记录之后
     */
    protected function afterSave()
    {
    }

    /**
     * 事件回调：创建记录之前
     */
    protected function beforeCreate()
    {
    }

    /**
     * 事件回调：创建记录之后
     */
    protected function afterCreate()
    {
    }

    /**
     * 事件回调：更新记录之前
     */
    protected function beforeUpdate()
    {
    }

    /**
     * 事件回调：更新记录之后
     */
    protected function afterUpdate()
    {
    }

    /**
     * 事件回调：删除记录之前
     */
    protected function beforeDestroy()
    {
    }

    /**
     * 事件回调：删除记录之后
     */
    protected function afterDestroy()
    {
    }

    /**
     * 事件回调：查询出对象数据之后，构造对象之前
     */
    protected function afterFind()
    {
    }

    /**
     * 事件回调：对象构造之后
     */
    protected function afterInitialize()
    {
    }
}
