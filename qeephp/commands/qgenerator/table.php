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
 * 定义 QGenerator_Table 类
 *
 * @package generator
 * @version $Id: table.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QGenerator_Table 创建表数据入口代码
 *
 * @package generator
 */
class QGenerator_Table extends QGenerator_Abstract
{
    /**
     * 执行代码生成器
     *
     * @param array $opts
     *
     * @return mixed
     */
    function execute(array $opts)
    {
        $table_name = array_shift($opts);
        if (empty($table_name)) {
            return false;
        }

        $class_name = 'Table_' . ucfirst($this->camelName($table_name));
        if ($filename = $this->existsClassFile($class_name)) {
            echo "Class '{$class_name}' declare file '{$filename}' exists.\n";
            return 0;
        }

        $content = $this->getCode($table_name, $class_name);
        if ($content !== -1 && !empty($content)) {
            return $this->createClassFile($class_name, $content);
        } else {
            return false;
        }
    }

    /**
     * 生成代码
     *
     * @param string $table_name
     * @param string $class_name
     *
     * @return string
     */
    function getCode($table_name, $class_name)
    {
        /**
         * 首先判断指定的数据表是否存在
         */

        try {
            Q::loadClass('QDB_Table');
            $params = array('table_name' => $table_name);
            $table = new QDB_Table($params);
        } catch (Exception $ex) {
            echo "Database table '{$table_name}' not exists.\n";
            return -1;
        }

        $meta = $table->columns();
        $pk = array();
        foreach ($meta as $field) {
            if ($field['pk']) {
                $pk[] = $field['name'];
            }
        }

        $viewdata = array(
            'table_name' => $table->table_name,
            'class_name' => $class_name,
            'pk' => $pk,
        );
        return $this->parseTemplate('table', $viewdata);
    }
}
