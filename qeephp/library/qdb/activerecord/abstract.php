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
     * 当前对象的类名称
     *
     * @var string
     */
    protected $_class;

    /**
     * ID 属性名
     *
     * @var string
     */
    protected $_idname;

    /**
     * 不同类的元信息对象
     *
     * @var array of QDB_ActiveRecord_Meta
     */
    static protected $_metas = array();

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
        $this->_class = $class_name;
        self::$_metas[$this->_class] = QDB_ActiveRecord_Meta::getInstance($class_name);
        $this->_idname = self::$_metas[$this->_class]->idname;


        // 将数组赋值给对象属性
        if (is_array($data)) {
            $this->_attach($data);
        } else {
            $this->_attach(array());
        }

        // 触发 after_initialize 事件
        $this->_event(self::after_initialize);
        $this->_after_initialize();
    }

    /**
     * 获得对象ID（对象在数据库中的主键值）
     *
     * @return mixed
     */
    function id()
    {
        return $this->_props[$this->_idname];
    }

    /**
     * 获得对象的ID属性名（对象在数据库中的主键字段名）
     *
     * @return string
     */
    function idname()
    {
        return $this->_idname;
    }

    /**
     * 返回当前对象的元信息对象
     *
     * @return QDB_ActiveRecord_Meta
     */
    function getMeta()
    {
        return self::$_metas[$this->_class];
    }

    /**
     * 保存对象到数据库
     *
     * @param boolean $force_create 是否强制创建新记录
     * @param int $recursion
     */
    function save($force_create = false, $recursion = 99)
    {
        $this->_before_save();
        $this->_event(self::before_save);
        $id = $this->id();
        if (empty($id) || $force_create) {
            $this->create($recursion);
        } else {
            $this->update($recursion);
        }
        $this->_event(self::after_save);
        $this->_after_save();
    }

    /**
     * 从数据库重新读取对象
     *
     * @param int $recursion
     */
    function reload($recursion = 1)
    {
        $row = self::$_metas[$this->_class]->table->find(array($this->idname() => $this->id()))
                                                  ->recursion($recursion)
                                                  ->asArray()
                                                  ->query();
        $this->_attach($row);
    }

    /**
     * 验证对象属性，成功返回 null，失败抛出异常或返回未通过验证的项目
     *
     * @param string $mode
     * @param boolean $throw 验证失败时是否抛出异常
     *
     * @return array|null
     */
    function validate($mode = 'general', $throw = false)
    {
        try {
            $this->_before_validation();
            $this->_event(self::before_validation);
            if ($mode == 'create') {
                $this->_before_validation_on_create();
                $this->_event(self::before_validation_on_create);
            } elseif ($mode == 'update') {
                $this->_before_validation_on_update();
                $this->_event(self::before_validation_on_update);
            }

            // 进行验证
            $error = self::$_metas[$this->_class]->validate($this->_props);
            if (!empty($error)) {
                throw new QDB_ActiveRecord_Validate_Exception($this, $error);
            }

            if ($mode == 'create') {
                $this->_after_validation_on_create();
                $this->_event(self::after_validation_on_create);
            } elseif ($mode == 'update') {
                $this->_after_validation_on_update();
                $this->_event(self::after_validation_on_update);
            }
            $this->_event(self::after_validation);
            $this->_after_validation();
        } catch (QDB_ActiveRecord_Validate_Exception $ex) {
            if ($throw) {
                throw $ex;
            } else {
                return $ex->validate_error;
            }
        }
        return null;
    }

    /**
     * 销毁对象对应的数据库记录
     *
     * @param int $recursion
     */
    function destroy($recursion = 99)
    {
        $this->_before_destroy();
        $this->_event(self::before_destroy);

        foreach (self::$_metas[$this->_class]->props as $prop_name => $params) {
            if ($params['assoc']) {
                $assoc_params = $params['assoc_params'];
                if ($params['assoc'] == 'has_one' || $params['assoc'] == 'belongs_to') {

                } else {

                }
            }
        }

        $this->_event(self::after_destroy);
        $this->_after_destroy();
    }


    /**
     * 批量设置对象的属性值（忽略只读的属性和关联对象）
     *
     * @param array $props
     */
    function setProps(array $props)
    {
        foreach ($props as $prop_name => $value) {
            $this->{$prop_name} = $value;
        }
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
        $meta = self::$_metas[$this->_class];
        foreach ($meta->fields2prop as $prop_name) {
            if ($meta->props[$prop_name]['assoc']) {
                if ($recursion <= 0) { continue; }
                if ($meta->props[$prop_name]['assoc'] == QDB::has_one
                    || $meta->props[$prop_name]['assoc'] == QDB::belongs_to) {
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
        $meta = self::$_metas[$this->_class];
        foreach ($meta->fields2prop as $field_name => $prop_name) {
            if ($meta->props[$prop_name]['assoc']) {
                if ($recursion <= 0) { continue; }
                if ($meta->props[$prop_name]['assoc'] == QDB::has_one
                    || $meta->props[$prop_name]['assoc'] == QDB::belongs_to) {
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
     * 魔法方法，实现对只读属性和属性方法的支持
     *
     * @param string $varname
     *
     * @return mixed
     */
    function __get($varname)
    {
        $meta = self::$_metas[$this->_class];
        if (!isset($meta->props[$varname])) {
            // LC_MSG: 对象 "%s" 的属性 "%s" 没有定义.
            throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 没有定义.', $this->_class, $varname));
        }
        $params = $meta->props[$varname];
        if (!empty($params['getter'])) {
            if (!is_array($params['getter'])) {
                return $this->{$params['getter']}($varname);
            } else {
                return call_user_func($params['getter']);
            }
        }

        if (!isset($this->_props[$varname]) && $params['assoc']) {
            // assembleAssocObjects() 会完成对象聚合的组装，因此下一步可以直接返回属性
            $meta->assembleAssocObjects($this->_props[$this->_idname], $varname);

            // 没有查询到对象
            if (!isset($this->_props[$varname])) {
                if ($params['assoc'] == QDB::has_one || $params['assoc'] == QDB::belongs_to) {
                    $this->_props[$varname] = QDB_ActiveRecord_Meta::getInstance($params['assoc_class'])->newNullObject();
                } else {
                    $this->_props[$varname] = new QColl($params['assoc_class']);
                }
            }
        }

        return $this->_props[$varname];
    }

    /**
     * 魔法方法，实现对属性的访问
     *
     * @param string $varname
     * @param mixed $value
     */
    function __set($varname, $value)
    {
        if (!isset(self::$_metas[$this->_class]->props[$varname])) {
            // LC_MSG: 对象 "%s" 的属性 "%s" 没有定义.
            throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 没有定义.', $this->_class, $varname));
        }
        $params = self::$_metas[$this->_class]->props[$varname];
        if ($params['readonly']) {
            // LC_MSG: 对象 "%s" 的属性 "%s" 是只读属性.
            throw new QException(__('对象 "%s" 的属性 "%s" 是只读属性.', $this->_class, $varname));
        }

        if (!empty($params['setter'])) {
            if (!is_array($params['setter'])) {
                $this->{$params['setter']}($varname, $value);
            } else {
                call_user_func($params['setter'], $value);
            }
            return;
        }

        if ($params['assoc']) {
            if ($params['assoc'] == QDB::has_one || $params['assoc'] == QDB::belongs_to) {
                if (!is_object($value) || !($value instanceof $params['assoc_class'])) {
                    // LC_MSG: 对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象.
                    throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象.',
                                                            $this->_class, $varname, $params['assoc_class']));
                }
                $this->_props[$varname] = $value;
            } else {
                if (!is_array($value) && !($value instanceof Iterator)) {
                    // LC_MSG: 对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型的对象.
                    throw new QDB_ActiveRecord_Exception(__('对象 "%s" 的属性 "%s" 只能设置为 "%s" 类型对象的集合.',
                                                            $this->_class, $varname, $params['assoc_class']));
                }
                if (is_object($value)) {
                    $this->_props[$varname] = $value;
                } else {
                    $this->_props[$varname] = QColl::createFromArray($value, $params['assoc_class']);
                }
            }
        } else {
            $this->_props[$varname] = $value;
        }
    }

    /**
     * 魔法方法，实现对 isset() 的支持
     *
     * @param string $varname
     *
     * @return boolean
     */
    function __isset($varname)
    {
        return isset($this->_props[$varname]);
    }

    /**
     * 魔法方法，用于调用行为插件为对象添加的方法
     *
     * @param string $method
     * @param array $args
     *
     * @return mixed
     */
    function __call($method, array $args)
    {
        if (isset(self::$_metas[$this->_class]->methods[$method])) {
            array_unshift($args, $this->_props);
            array_unshift($args, $this);
            return call_user_func_array(self::$_metas[$this->_class]->methods[$method], $args);
        }

        // getXX() 和 setXX() 方法
        $prefix = substr($method, 0, 3);
        if ($prefix == 'get') {
            $varname = substr($method, 3);
            return $this->{$varname};
        } elseif ($prefix == 'set') {
            $varname = substr($method, 3);
            $this->{$varname} = reset($args);
            return null;
        }

        // LC_MSG: Call to undefined method "%s::%s()".
        throw new QDB_ActiveRecord_Exception(__('Call to undefined method "%s::%s()".', $this->_class, $method));
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
     * 将对象附着到一个包含对象属性的数组，等同于将数组的属性值复制到对象
     *
     * @param array $row
     */
    private function _attach(array $row)
    {
        foreach (self::$_metas[$this->_class]->fields2prop as $field_name => $prop_name) {
            $params = self::$_metas[$this->_class]->props[$prop_name];

            if ($params['assoc'])
            {
                // 如果没有提供数据，聚合的对象将在第一次访问时设置
                if (empty($row[$field_name])) { continue; }
                if ($params['assoc'] == QDB::has_one || $params['assoc'] == QDB::belongs_to) {
                    $this->{$prop_name} = new $params['assoc_class']($row[$field_name]);
                } else {
                    $this->{$prop_name} = new QColl($params['assoc_class']);
                    foreach ($row[$field_name] as $srow) {
                        $this->{$prop_name}[] = new $params['assoc_class']($srow);
                    }
                }

                continue;
            }

            if ($params['virtual']) {
                if (empty($params['setter'])) { continue; }
                if (!is_array($params['setter'])) {
                    $this->{$params['setter']}($row[$field_name]);
                } else {
                    call_user_func($params['setter'], $row[$field_name]);
                }
                continue;
            }

            if (array_key_exists($field_name, $row)) {
                $this->_props[$prop_name] = $row[$field_name];
            } else {
                $this->_props[$prop_name] = self::$_metas[$this->_class]->props[$prop_name]['default_value'];
            }
        }
    }


    /**
     * 触发事件
     *
     * @param int $event
     */
    protected function _event($event)
    {
        if (empty(self::$_metas[$this->_class]->callbacks[$event])) { return; }
        foreach (self::$_metas[$this->_class]->callbacks[$event] as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * 在数据库中创建对象
     *
     * @param int $recursion
     */
    protected function create($recursion = 99)
    {
        $table = self::$_metas[$this->_class]->table;
        $null = QDB_ActiveRecord_RemovedProp::instance();

        // 根据 create_reject 数组，将属性设置为 QDB_ActiveRecord_RemovedProp 对象
//        foreach ($ref['create_reject'] as $prop) {
//            $this->_props[$prop] = $null;
//        }
//
//        // 根据 create_autofill 设置对属性进行填充
//        foreach ($ref['create_autofill'] as $prop => $fill) {
//            $this->_props[$prop] = $fill;
//        }

        // 进行 create 验证
        $this->validate('create', true);

        // 引发 before_create 事件
        $this->_before_create();
        $this->_event(self::before_create);

        // 将对象属性转换为名值对数组
        $row = $this->toDbArray(0);

        // 过滤掉值为 QDB_ActiveRecord_RemovedProp 的键
//        foreach ($row as $key => $value) {
//            if (is_object($value) && ($value instanceof QDB_ActiveRecord_RemovedProp)) {
//                unset($row[$key]);
//            }
//        }

        // 将名值对保存到数据库
        $id = $table->create($row, 0);
        $this->_props[$this->idname()] = $id;

        // 遍历关联的对象，并调用对象的save()方法
        foreach ($ref['links'] as $prop => $null) {
            if (!isset($this->_props[$prop])) { continue; }

            $link = $table->getLink($prop);
            /* @var $link QDB_Table_Link */
            $mk = $this->alias_name($link->source_key);

            if ($link->type == QDB_Table::has_one || $link->type == QDB_Table::belongs_to) {
                if (!isset($this->_props[$prop]) || !is_object($this->_props[$prop])) {
                    continue;
                }
                // 务必为关联对象设置 target_key 字段值
                $obj = $this->_props[$prop];
                $ak = $obj->alias_name($link->target_key);
                $obj->{$ak} = $this->{$mk};
                $obj->save(false, $recursion - 1);
            } else {
                $ak = null;
                $mkv = $this->{$mk};
                foreach ($this->_props[$prop] as $obj) {
                    if (is_null($ak)) { $ak = $obj->alias_name($link->target_key); }
                    $obj->{$ak} = $mkv;
                    $obj->save(false, $recursion - 1);
                }
            }
        }

        // 引发after_create事件
        $this->_event(self::after_create, $recursion);
        $this->_afterCreate($recursion);

        // 将所有为QDB_ActiveRecord_RemovedProp的属性设置为null
        foreach ($this->_props as $prop => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_RemovedProp)) {
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
        $ref = self::$__ref[$this->_class];
        $table = $this->table;
        $null = QDB_ActiveRecord_RemovedProp::instance();

        // 根据 update_reject 设置，将属性设置为 QDB_ActiveRecord_RemovedProp 对象
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
        $this->_beforeUpdate($recursion);
        $this->_event(self::before_update, $recursion);

        // 将对象属性转换为名值对数组
        $row = $this->toDbArray(0);

        // 过滤掉值为 QDB_ActiveRecord_RemovedProp 的键
        foreach ($row as $key => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_RemovedProp)) {
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
            $mk = $this->alias_name($link->source_key);

            if ($link->type == QDB_Table::has_one || $link->type == QDB_Table::belongs_to) {
                if (!isset($this->_props[$prop]) || !is_object($this->_props[$prop])) {
                    continue;
                }
                // 务必为关联对象设置 target_key 字段值
                $obj = $this->_props[$prop];
                $ak = $obj->alias_name($link->target_key);
                $obj->{$ak} = $this->{$mk};
                $obj->save(false, $recursion - 1);
            } else {
                $ak = null;
                $mkv = $this->{$mk};
                foreach ($this->_props[$prop] as $obj) {
                    if (is_null($ak)) { $ak = $obj->alias_name($link->target_key); }
                    $obj->{$ak} = $mkv;
                    $obj->save(false, $recursion - 1);
                }
            }
        }

        // 引发after_create事件
        $this->_event(self::after_update, $recursion);
        $this->_afterUpdate($recursion);

        // 将所有为QDB_ActiveRecord_RemovedProp的属性设置为null
        foreach ($this->_props as $prop => $value) {
            if (is_object($value) && ($value instanceof QDB_ActiveRecord_RemovedProp)) {
                $this->_props[$prop] = null;
            }
        }
    }

    /**
     * 事件回调：开始验证之前
     */
    protected function _before_validation() {}

    /**
     * 事件回调：为创建记录进行的验证开始之前
     */
    protected function _before_validation_on_create() {}

    /**
     * 事件回调：为创建记录进行的验证完成之后
     */
    protected function _after_validation_on_create() {}

    /**
     * 事件回调：为更新记录进行的验证开始之前
     */
    protected function _before_validation_on_update() {}

    /**
     * 事件回调：为更新记录进行的验证完成之后
     */
    protected function _after_validation_on_update() {}

    /**
     * 事件回调：验证完成之后
     */
    protected function _after_validation() {}

    /**
     * 事件回调：保存记录之前
     */
    protected function _before_save() {}

    /**
     * 事件回调：保存记录之后
     */
    protected function _after_save() {}

    /**
     * 事件回调：创建记录之前
     */
    protected function _before_create() {}

    /**
     * 事件回调：创建记录之后
     */
    protected function _after_create() {}

    /**
     * 事件回调：更新记录之前
     */
    protected function _before_update() {}

    /**
     * 事件回调：更新记录之后
     */
    protected function _after_update() {}

    /**
     * 事件回调：删除记录之前
     */
    protected function _before_destroy() {}

    /**
     * 事件回调：删除记录之后
     */
    protected function _after_destroy() {}

    /**
     * 事件回调：对象构造之后
     */
    protected function _after_initialize() {}
}


