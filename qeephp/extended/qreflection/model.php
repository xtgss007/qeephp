<?php

class QReflection_Model
{

    /**
     * 模型的名字
     *
     * @var string
     */
    protected $_model_name;

    /**
     * 模型的类名称
     *
     * @var string
     */
    protected $_model_class_name;

    /**
     * 模型所用数据表的名称
     *
     * @var string
     */
    protected $_model_table_name;

    /**
     * 模型文件完整路径
     *
     * @var string
     */
    protected $_model_file_path;

    /**
     * 该模型所属模块的反射
     *
     * @var QReflection_Module
     */
    protected $_reflection_module;

    function __construct(QReflection_Module $module, $model_name)
    {
        $this->_reflection_module = $module;
        $this->_model_name = $model_name;
        $this->_model_file_path = rtrim($module->moduleDir(), '/\\') . '/model/' . strtolower($model_name) . '.php';
    }

    /**
     * 返回该模型所属模块的反射
     *
     * @return QReflection_Module
     */
    function reflectionModule()
    {
        return $this->_reflection_module;
    }

    /**
     * 返回模型所述模块的名字
     *
     * @return string
     */
    function moduleName()
    {
        return $this->reflectionModule()->moduleName();
    }

    /**
     * 返回该模型所属应用的反射
     *
     * @return QReflection_Application
     */
    function reflectionApp()
    {
        return $this->_reflection_module->reflectionApp();
    }

    /**
     * 返回模型名称
     *
     * @return string
     */
    function modelName()
    {
        return $this->_model_name;
    }

    /**
     * 返回模型文件的完整路径
     *
     * @return string
     */
    function filePath()
    {
        return $this->_model_file_path;
    }

    /**
     * 返回模型所用数据表的名称（不含全局前缀）
     *
     * @return string
     */
    function tableName()
    {
        if (is_null($this->_model_table_name))
        {
            $this->_parseClassFile();
        }
        return $this->_model_table_name;
    }

    /**
     * 返回模型的类名称
     *
     * @return string
     */
    function className()
    {
        if (is_null($this->_model_class_name))
        {
            $this->_parseClassFile();
        }
        return $this->_model_class_name;
    }

    /**
     * 检查指定的文件是否是模型定义文件
     *
     * @return boolean
     */
    static function isModelFile($filename)
    {
        static $cached_check = array();

        $filename = realpath($filename);
        if (empty($filename))
        {
            return false;
        }

        if (isset($cached_check[$filename]))
        {
            return $cached_check[$filename];
        }

        $included = get_included_files();
        $included = array_combine($included, $included);

        if (isset($included[$filename]))
        {
            /**
             * 对于已经载入了的文件，则尝试猜测
             */
            $name = pathinfo($filename, PATHINFO_BASENAME);
            if (!class_exists($name))
            {
                $cached_check[$filename] = false;
                return false;
            }

            $new = array($name);
        }
        else
        {
            $classes = get_declared_classes();
            include($filename);
            $arr = get_declared_classes();
            $new = array_diff($arr, $classes);
            if (empty($new))
            {
                return false;
            }
        }

        foreach ($new as $name)
        {
            $r = new ReflectionClass($name);
            if ($r->getFileName() == $filename)
            {
                if (is_subclass_of($name, 'QDB_ActiveRecord_Abstract'))
                {
                    $cached_check[$filename] = true;
                    return true;
                }
            }
        }

        $cached_check[$filename] = false;
        return false;
    }

    /**
     * 分类类文件，确定模型类名称和所用数据表名称
     */
    protected function _parseClassFile()
    {
        if (!self::isModelFile($this->filePath()))
        {
            throw new QReflection_NotModelFileException($this->_filePath());
        }

        $arr = get_declared_classes();
        $classes = array_combine($arr, $arr);
        $classes = array_change_key_case($classes, CASE_LOWER);

        $define = call_user_func(array($this->_model_name, '__define'));
        $this->_model_table_name = $define['table_name'];
        $this->_model_class_name = $classes[strtolower($this->_model_name)];
    }

}

