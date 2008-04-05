<?php echo '<?php'; ?>

/**
 * <?php echo $class_name; ?> 封装来自 <?php echo $table_name; ?> 数据表的记录及领域逻辑
 */
class <?php echo $class_name; ?> extends QDB_ActiveRecord_Abstract
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

            // 指定行为插件的配置
            'behaviors_settings' => array(
                # '插件名' => array('选项' => 设置),
            ),

<?php if ($table_class): ?>
            // 用什么表数据入口处理对象的持久化
            'table_class' => '<?php echo $table_class; ?>',
<?php else: ?>
            // 用什么数据表保存对象
            'table_name' => '<?php echo $table_name; ?>',
<?php endif; ?>

            // 指定数据表记录字段与对象属性之间的映射关系
            // 没有在此处指定的属性，QeePHP 会自动设置将属性映射为对象的可读写属性
            'fields' => array(
                // 主键应该是只读，确保领域对象的“不变量”
<?php foreach ($pk as $p): ?>
                '<?php echo $p; ?>' => array('readonly' => true),
<?php endforeach; ?>
<?php if (isset($meta['created'])): ?>
                // 对象创建时间应该是只读
                'created' => array('readonly' => true),
<?php endif; ?>
<?php if (isset($meta['updated'])): ?>
                // 对象最后更新时间应该是只读
                'updated' => array('readonly' => true),
<?php endif; ?>

                /**
                 *  可以在此添加其他属性的设置
                 */
                # 'other_field' => array('readonly' => true),

                /**
                 * 添加对象间的关联
                 */
                # 'other' => array('has_one' => 'Class'),

            ),

            /**
             * 指定在数据库中创建对象时，哪些属性的值不允许由外部提供
             */
            'create_reject' => '<?php echo implode(', ', $pk); ?>',

            /**
             * 指定更新数据库中的对象时，哪些属性的值不允许由外部提供
             */
            'update_reject' => '',

            /**
             * 指定在数据库中创建对象时，哪些属性的值由下面指定的内容进行覆盖
             */
            'create_autofill' => array(
                # 属性名 => 填充值
                # 'is_locked' => 0,
            ),

            /**
             * 指定更新数据库中的对象时，哪些属性的值由下面指定的内容进行覆盖
             */
            'update_autofill' => array(
            ),

            // 在保存对象时，会按照下面指定的验证规则进行验证。验证失败会抛出异常。
            // 还可以通过对象的 ::validate() 静态方法对数组数据进行验证。
            // 注意：验证是按照属性名进行的，因此如果为字段指定了别名，则应该使用别名进行验证
            'validation' => array(
<?php
foreach ($meta as $name => $f):
if (in_array($name, $pk) || $name == 'created' || $name == 'updated' || $f['ptype'] == 'r') {
    continue;
}

$desc = !empty($f['desc']) ? $f['desc'] : $name;
$rules = array();
switch ($f['ptype']) {
case 'i': // 整数
    $rules[] = "array('is_int', '{$desc}必须是一个整数'),";
    break;
case 'n': // 浮点数
    $rules[] = "array('is_float', '{$desc}必须是一个浮点数'),";
    break;
case 'd': // 日期
case 't': // 时间
    if (strtolower($f['type']) == 'datetime') {
        $rules[] = "array('is_datetime', '{$desc}必须是一个有效的日期时间字符串'),";
    } elseif ($f['ptype'] == 'd') {
        $rules[] = "array('is_date', '{$desc}必须是一个有效的日期'),";
    } else {
        $rules[] = "array('is_time', '{$desc}必须是一个有效的时间'),";
    }
    break;
case 'c'; // 字符串
case 'x'; // 大字符串
    if (!empty($f['length'])) {
        if ($f['has_default'] == false && $f['not_null'] == true) {
            $rules[] = "array('not_empty', '{$desc}不能为空'),";
        }
        if ($f['length'] > 0) {
            $rules[] = "array('max_length', {$f['length']}, '{$desc}不能超过 {$f['length']} 个字符'),";
        }
    }
    break;

}

if (empty($rules)) { continue; }

?>
                '<?php echo $name; ?>' => array(
<?php foreach ($rules as $rule): ?>
                    <?php echo $rule; ?>

<?php endforeach; ?>                ),
<?php endforeach; ?>            ),
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
        return parent::__find(__CLASS__, $args);
    }

    /**
     * 实例化所有符合条件的对象，并调用这些对象的 destroy() 方法，返回成功删除的对象的数量
     *
     * @static
     *
     * @return int
     */
    static function destroyWhere()
    {
        $args = func_get_args();
        return parent::__destroyWhere(__CLASS__, $args);
    }

    /**
     * 对数据进行验证，返回所有未通过验证数据的名称错误信息
     *
     * @param array $data
     * @param array|string $props
     *
     * @return array
     */
    static function validate(array $data, $props = null)
    {
        return parent::__validate(__CLASS__, $data, $props);
    }

/* -------------------------------------------------------------------- */

}

/**
 * <?php echo $class_name; ?>_Null 用于封装不存在的 <?php echo $class_name; ?> 对象
 */
class <?php echo $class_name; ?>_Null extends <?php echo $class_name; ?>

{
    function __construct(array $data = null)
    {
        parent::__construct();
    }

    function setProps(array $props)
    {
    }

    function save($force_create = false, $recursion = 99)
    {
    }

    function reload($recursion = 1)
    {
    }

    function doValidate($mode = 'general')
    {
    }

    function destroy($recursion = 99)
    {
    }

    function id()
    {
        return null;
    }

    protected function create($recursion = 99)
    {
    }

    protected function update($recursion = 99)
    {
    }
}


/**
 * <?php echo $class_name; ?>_Exception 用于封装 <?php echo $class_name; ?> 领域逻辑异常
 */
class <?php echo $class_name; ?>_Exception extends QException
{
}
