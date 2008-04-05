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
 * 定义 QGenerator_Model 类
 *
 * @package generator
 * @version $Id: model.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QGenerator_Model 创建 ActiveRecord 对象代码
 *
 * @package generator
 */
class QGenerator_Model extends QGenerator_Abstract
{
    /**
     * 执行代码生成器
     */
    function execute(array $opts)
    {
        $model_name = array_shift($opts);
        $table_name = array_shift($opts);
        if (empty($model_name) || empty($table_name)) { return false; }

        if (strtolower(substr($table_name, 0, 6)) == 'table_') {
            $table_class = $table_name;
            $table_name = null;
        } else {
            $table_class = null;
        }


        $dir = ROOT_DIR . '/app/model';
        $this->createDir($dir);
        $filename = $dir . '/' . strtolower($model_name) . '.php';
        $class_name = ucfirst($model_name);

        if (file_exists($filename)) {
            echo "Class '{$class_name}' declare file '{$filename}' exists.\n";
            return 0;
        }

        $content = $this->getCode($class_name, $table_class, $table_name);
        if ($content == -1 || empty($content) || !file_put_contents($filename, $content)) {
            return false;
        } else {
            $filename = str_replace('/', DS, $filename);
            echo "Create file '{$filename}' successed.\n";
            return true;
        }
    }

    /**
     * 生成代码
     */
    function getCode($class_name, $table_class, $table_name)
    {
        if ($table_class) {
            // 首先尝试创建表数据入口的文件，然后再创建 Model 文件
            require_once dirname(__FILE__) . DS . 'table.php';
            $generator = new QGenerator_Table();
            if ($generator->execute(array($table_name)) === false) {
                return false;
            }

            // 尝试读取数据表的信息
            Q::loadClass($table_class);
            $table = new $table_class();
            /* @var $table QDB_Table */
        } else {
            Q::loadClass('QDB_Table');
            $params = array('table_name' => $table_name);
            $table = new QDB_Table($params);
        }

        $table->connect();
        $meta = $table->columns();
        $pk = array();
        foreach ($meta as $field) {
            if ($field['pk']) {
                $pk[] = $field['name'];
            }
        }

        $viewdata = array(
            'class_name'  => $class_name,
            'table_class' => $table_class,
            'table_name'  => $table_name,
            'meta'        => $meta,
            'pk'          => $pk,
        );
        return $this->parseTemplate('model', $viewdata);
    }
}
