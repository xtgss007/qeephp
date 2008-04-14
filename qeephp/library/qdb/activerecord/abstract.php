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
abstract class QDB_ActiveRecord_Abstract implements QDB_ActiveRecord_Callbacks, QDB_ActiveRecord_Interface, ArrayAccess
{
    /**
     * 对象所有属性的引用
     *
     * @var array
     */
    protected $_props;

    /**
     * 元信息对象
     *
     * @var QDB_ActiveRecord_Meta
     */
    protected $_meta;

    /**
     * 构造函数
     *
     * @param array $data
     */
    function __construct(array $data = null)
    {
        // 判断是否是 ActiveRecord_Null 类
        $class_name = get_class($this);
        if (strtolower(substr($class_name, -5)) == '_null') {
            $class_name = substr($class_name, 0, -5);
        }
        // 构造元对象
        $this->meta = QDB_ActiveRecord_Meta::getInstance($class_name);

        // 将数组赋值给对象属性
        if (is_array($data)) {
            $this->_attach($data);
        } else {
            $this->_attach(array());
        }

        // 触发 after_initialize 事件
        $this->_meta->event(self::after_initialize);
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
        $this->beforeSave($recursion);
        $this->_meta->event(self::before_save, $recursion);
        $id = $this->id();
        if (empty($id) || $force_create) {
            $this->create($recursion);
        } else {
            $this->update($recursion);
        }
        $this->_meta->event(self::after_save, $recursion);
        $this->afterSave($recursion);
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
        $this->_attach($arr);
    }

    /**
     * 验证对象属性
     *
     * @param string $mode
     */
    function validate($mode = 'general')
    {
        $this->beforeValidation();
        $this->_meta->event(self::before_validation);
        if ($mode == 'create') {
            $this->beforeValidationOnCreate();
            $this->_meta->event(self::before_validation_on_create);
        } elseif ($mode == 'update') {
            $this->beforeValidationOnUpdate();
            $this->_meta->event(self::before_validation_on_update);
        }

        // 进行验证
        $error = $this->_meta->validate($this->_props);
        if (!empty($error)) {
            throw new QDB_ActiveRecord_Validate_Exception($this, $error);
        }

        if ($mode == 'create') {
            $this->afterValidationOnCreate();
            $this->_meta->event(self::after_validation_on_create);
        } elseif ($mode == 'update') {
            $this->afterValidationOnUpdate();
            $this->_meta->event(self::after_validation_on_update);
        }
        $this->_meta->event(self::after_validation);
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
        $this->_meta->event(self::before_destroy);

        foreach ($this->_meta->props as $prop_name => $params) {
            if ($params['assoc']) {
                // TODO: ActiveRecord 的层叠删除
            }
        }

        $this->_meta->event(self::after_destroy);
        $this->afterDestroy();
    }

    /**
     * 获得对象ID（对象在数据库中的主键值）
     *
     * @return mixed
     */
    function id()
    {
        $pk = $this->_meta->idname;
        return $this->_props[$pk];
    }

    /**
     * 获得对象的ID属性名（对象在数据库中的主键字段名）
     *
     * @return string
     */
    function idname()
    {
        return $this->_meta->idname;
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
        foreach ($this->_meta->fields2prop as $prop_name) {
            if ($this->_meta->props[$prop_name]['assoc']) {
                if ($recursion <= 0) { continue; }
                if ($this->_meta->props[$prop_name]['assoc'] == 'has_one'
                    || $this->_meta->props[$prop_name]['assoc'] == 'belongs_to') {
                    $row[$prop_name] = $this->_props[$prop_name]->toArray($recursion - 1);
                } else {
                    $row[$prop_name] = array();
                    foreach ($this->_props[$prop_name] as $obj) {
                        $row[$prop_name][] = $obj->toArray($recursion - 1);
                    }
                }
            } else {
                $row[$prop_name] = $this->_props[$prop_name];
            }
        }
        return $row;
    }

    /**
     * 返回对象属性数组，键名是保存对象时用的数据表的字段名
     *
     * @param int $recursion
     *
     * @return array
     */
    function toDbArray($recursion = 99)
    {
        $row = array();
        foreach ($this->_meta->fields2prop as $field_name => $prop_name) {
            if ($this->_meta->props[$prop_name]['assoc']) {
                if ($recursion <= 0) { continue; }
                if ($this->_meta->props[$prop_name]['assoc'] == 'has_one'
                    || $this->_meta->props[$prop_name]['assoc'] == 'belongs_to') {
                    $row[$field_name] = $this->_props[$prop_name]->toArray($recursion - 1);
                } else {
                    $row[$field_name] = array();
                    foreach ($this->_props[$prop_name] as $obj) {
                        $row[$field_name][] = $obj->toArray($recursion - 1);
                    }
                }
            } else {
                $row[$field_name] = $this->_props[$prop_name];
            }
        }
        return $row;
    }

    /**
     * 返回对象所有属性的 JSON 字符串
     *
     * @param int $recursion
     *
     * @return string
     */
    function toJSON($recursion = 99)
    {
        return json_encode($this->toArray($recursion));
    }

    /**
     * 触发事件
     *
     * @param int $event
     * @param int $recursion
     */
    protected function _event($event, $recursion = 0)
    {
        if (empty($this->_meta->callbacks[$event])) { return; }
        foreach ($this->_meta->callbacks[$event] as $callback) {
            call_user_func($callback, $this, $recursion);
        }
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
        if (!isset($this->_meta->props[$varname])) {
            // LC_MSG: 对象 "%s" 的属性 "%s" 没有定义.
            throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 没有定义.',
                                                    $this->_meta->getClassName(), $varname));
        }
        $params = $this->_meta->props[$varname];
        if (!empty($params['getter'])) {
            if (!is_array($params['getter'])) {
                $params['getter'] = array($this, $params['getter']);
            }
            return call_user_func($params['getter']);
        }

        if (!isset($this->_props[$varname]) && $params['assoc']) {
            if ($params['assoc'] == 'has_one' || $params['assoc'] == 'belongs_to') {
                $this->_props[$varname] = new $params['assoc_class'] . '_Null';
            } else {
                $this->_props[$varname] = new QColl($params['assoc_class']);
            }
        }

        return $this->_props[$varname];
    }

    /**
     * 魔法方法，实现只读属性的支持
     *
     * @param string $varname
     * @param mixed $value
     */
    function __set($varname, $value)
    {
        if (!isset($this->_meta->props[$varname])) {
            // LC_MSG: 对象 "%s" 的属性 "%s" 没有定义.
            throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 没有定义.',
                                                    $this->_meta->getClassName(), $varname));
        }
        $params = $this->_meta->props[$varname];
        if ($params['readonly']) {
            // LC_MSG: 对象 "%s" 的属性 "%s" 是只读属性.
            throw new QException(__('对象 "%s" 的属性 "%s" 是只读属性.',
                                    $this->_meta->getClassName(), $varname));
        }

        if (!empty($params['setter'])) {
            if (!is_array($params['setter'])) {
                $params['setter'] = array($this, $params['setter']);
            }
            call_user_func($params['setter'], $value);
            return;
        }

        if ($params['assoc']) {
            if ($params['assoc'] == 'has_one' || $params['assoc'] == 'belongs_to') {
                if (!is_object($value) || !($value instanceof $params['assoc_class'])) {
                    // LC_MSG: 对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象.
                    throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象.',
                                                    $this->_meta->getClassName(), $varname, $params['assoc_class']));

                }

                $this->_props[$varname] = $value;
            } else {
                // 聚合的对象，要求 $value 必须是一个包含特定类型对象的数组
                if (!is_array($value) && !($value instanceof Iterator)) {
                    // LC_MSG: 对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象组成的集合.
                    throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象组成的集合.',
                                     $this->_meta->getClassName(), $varname, $params['assoc_class']));
                }

                if (!isset($this->_props[$varname])) {
                    $this->_props[$varname] = new QColl($params['assoc_class']);
                }
                foreach ($value as $obj) {
                    $this->_props[$varname][] = $obj;
                }
            }
        } else {
            $this->_props[$varname] = $value;
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

        if (isset($this->_meta->methods[$method])) {
            array_unshift($args, $this->_props);
            array_unshift($args, $this);
            return call_user_func_array($this->_meta->methods[$method], $args);
        } else {
            // LC_MSG: Call to undefined method "%s::%s()".
            throw new QDB_ActiveRecord_Exception(__('Call to undefined method "%s::%s()".', $this->__class, $method));
        }
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     *
     * @return boolean
     */
    function offsetExists($key)
    {
        return isset($this->_props[$key]);
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     * @param mixed $value
     */
    function offsetSet($key, $value)
    {
        $this->{$key} = $value;
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     *
     * @return boolean
     */
    function offsetGet($key)
    {
        return $this->{$key};
    }

    /**
     * ArrayAccess 接口方法
     *
     * @param string $key
     */
    function offsetUnset($key)
    {
        // LC_MSG: QDB_ActiveRecord_Abstract 没有实现 offsetUnset() 方法.
        throw new QDB_ActiveRecord_Exception(__('QDB_ActiveRecord_Abstract 没有实现 offsetUnset() 方法.'));
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
     * 将对象附着到一个包含对象属性的数组，等同于将数组的属性值复制到对象
     *
     * @param array $row
     */
    private function _attach(array $row)
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
                        $this->_props[$a] = new QColl($define['class']);
                    }
                    if (!isset($row[$field])) {
                        $this->_props[$a] = null;
                        continue;
                    } elseif (!is_array($row[$field])) {
                        // LC_MSG: Property "%s" type mismatch. expected is "%s", actual is "%s".
                        $msg = 'Property "%s" type mismatch. expected is "%s", actual is "%s".';
                        throw new QDB_ActiveRecord_Exception(__($msg, "\$row[{$field}]", 'array', gettype($row[$field])));
                    } else {
                        if ($define['assoc'] == 'has_one' ||  $define['assoc'] == 'belongs_to') {
                            $this->_props[$a] = new $define['class']($row[$field]);
                        } else {
                            foreach ($row[$field] as $assoc_row) {
                                $this->_props[$a][] = new $define['class']($assoc_row);
                            }
                        }
                    }
                    $this->_props[$a] =& $this->_props[$a];
                } else {
                    $this->_props[$a] = $row[$field];
                    $this->_props[$a] =& $this->_props[$a];
                }
            } else {
                $this->{$a} = $row[$field];
                $this->_props[$a] =& $this->{$a};
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
        $ref = self::$__ref[$this->__class];
        $table = $this->getTable();
        $null = QDB_ActiveRecord_Removed_Prop::instance();

        // 开启事务
        $tran = $table->getConn()->beginTrans();

        // 根据 create_reject 数组，将属性设置为 QDB_ActiveRecord_Removed_Prop 对象
        foreach ($ref['create_reject'] as $prop) {
            $this->_props[$prop] = $null;
        }

        // 根据 create_autofill 设置对属性进行填充
        foreach ($ref['create_autofill'] as $prop => $fill) {
            $this->_props[$prop] = $fill;
        }

        // 进行 create 验证
        $this->doValidate('create', $recursion);

        // 引发 before_create 事件
        $this->beforeCreate($recursion);
        $this->_meta->event(self::before_create, $recursion);

        // 将对象属性转换为名值对数组
        $row = $this->toDbArray(0);

        // 过滤掉值为 QDB_ActiveRecord_Removed_Prop 的键
        foreach ($row as $key => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_Removed_Prop)) {
                unset($row[$key]);
            }
        }

        // 将名值对保存到数据库
        $id = $table->create($row, 0);
        $this->_props[$this->idname()] = $id;

        // 遍历关联的对象，并调用对象的save()方法
        foreach ($ref['links'] as $prop => $null) {
            if (!isset($this->_props[$prop])) { continue; }

            $link = $table->getLink($prop);
            /* @var $link QDB_Table_Link */
            $mk = $this->alias_name($link->main_key);

            if ($link->type == QDB_Table::has_one || $link->type == QDB_Table::belongs_to) {
                if (!isset($this->_props[$prop]) || !is_object($this->_props[$prop])) {
                    continue;
                }
                // 务必为关联对象设置 assoc_key 字段值
                $obj = $this->_props[$prop];
                $ak = $obj->alias_name($link->assoc_key);
                $obj->{$ak} = $this->{$mk};
                $obj->save(false, $recursion - 1);
            } else {
                $ak = null;
                $mkv = $this->{$mk};
                foreach ($this->_props[$prop] as $obj) {
                    if (is_null($ak)) { $ak = $obj->alias_name($link->assoc_key); }
                    $obj->{$ak} = $mkv;
                    $obj->save(false, $recursion - 1);
                }
            }
        }

        // 引发after_create事件
        $this->_meta->event(self::after_create, $recursion);
        $this->afterCreate($recursion);

        // 将所有为QDB_ActiveRecord_Removed_Prop的属性设置为null
        foreach ($this->_props as $prop => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_Removed_Prop)) {
                $this->_props[$prop] = null;
            }
        }
    }

    /**
     * 更新对象到数据库
     *
     * @param int $recursion
     */
    protected function update($recursion = 99)
    {
        $ref = self::$__ref[$this->__class];
        $table = $this->getTable();
        $null = QDB_ActiveRecord_Removed_Prop::instance();

        // 开启事务
        $tran = $table->getConn()->beginTrans();

        // 根据 update_reject 设置，将属性设置为 QDB_ActiveRecord_Removed_Prop 对象
        foreach ($ref['update_reject'] as $prop) {
            $this->_props[$prop] = $null;
        }

        // 根据 update_autofill 设置对属性进行填充
        foreach ($ref['update_autofill'] as $prop => $fill) {
            $this->_props[$prop] = $fill;
        }

        // 进行 update 验证
        $this->doValidate('update');

        // 引发before_update事件
        $this->beforeUpdate($recursion);
        $this->_meta->event(self::before_update, $recursion);

        // 将对象属性转换为名值对数组
        $row = $this->toDbArray(0);

        // 过滤掉值为 QDB_ActiveRecord_Removed_Prop 的键
        foreach ($row as $key => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_Removed_Prop)) {
                unset($row[$key]);
            }
        }

        // 将名值对保存到数据库
        $table->update($row, 0);

        // 遍历关联的对象，并调用对象的save()方法
        foreach ($ref['links'] as $prop => $null) {
            if (!isset($this->_props[$prop])) { continue; }

            $link = $table->getLink($prop);
            /* @var $link QDB_Table_Link */
            $mk = $this->alias_name($link->main_key);

            if ($link->type == QDB_Table::has_one || $link->type == QDB_Table::belongs_to) {
                if (!isset($this->_props[$prop]) || !is_object($this->_props[$prop])) {
                    continue;
                }
                // 务必为关联对象设置 assoc_key 字段值
                $obj = $this->_props[$prop];
                $ak = $obj->alias_name($link->assoc_key);
                $obj->{$ak} = $this->{$mk};
                $obj->save(false, $recursion - 1);
            } else {
                $ak = null;
                $mkv = $this->{$mk};
                foreach ($this->_props[$prop] as $obj) {
                    if (is_null($ak)) { $ak = $obj->alias_name($link->assoc_key); }
                    $obj->{$ak} = $mkv;
                    $obj->save(false, $recursion - 1);
                }
            }
        }

        // 引发after_create事件
        $this->_meta->event(self::after_update, $recursion);
        $this->afterUpdate($recursion);

        // 将所有为QDB_ActiveRecord_Removed_Prop的属性设置为null
        foreach ($this->_props as $prop => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_Removed_Prop)) {
                $this->_props[$prop] = null;
            }
        }
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
            if (is_object($data[$prop]) && ($data[$prop] instanceof QDB_ActiveRecord_Removed_Prop)) { continue; }

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
     *
     * @param int $recursion
     */
    protected function beforeSave($recursion = 0)
    {
    }

    /**
     * 事件回调：保存记录之后
     *
     * @param int $recursion
     */
    protected function afterSave($recursion = 0)
    {
    }

    /**
     * 事件回调：创建记录之前
     *
     * @param int $recursion
     */
    protected function beforeCreate($recursion = 0)
    {
    }

    /**
     * 事件回调：创建记录之后
     *
     * @param int $recursion
     */
    protected function afterCreate($recursion = 0)
    {
    }

    /**
     * 事件回调：更新记录之前
     *
     * @param int $recursion
     */
    protected function beforeUpdate($recursion = 0)
    {
    }

    /**
     * 事件回调：更新记录之后
     *
     * @param int $recursion
     */
    protected function afterUpdate($recursion = 0)
    {
    }

    /**
     * 事件回调：删除记录之前
     *
     * @param int $recursion
     */
    protected function beforeDestroy($recursion = 0)
    {
    }

    /**
     * 事件回调：删除记录之后
     *
     * @param int $recursion
     */
    protected function afterDestroy($recursion = 0)
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

class QDB_ActiveRecord_Removed_Prop
{
    private function __construct()
    {
    }

    static function instance()
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new QDB_ActiveRecord_Removed_Prop();
        }
        return $instance;
    }

    function __toString()
    {
        return '';
    }
}
