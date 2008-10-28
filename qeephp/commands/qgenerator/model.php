<?php

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

		// 确定模型的类名称
		list($model_name, , $module) = $this->_splitName($model_name);

        if ($module)
        {
            $dir = "{$this->root_dir}/modules/{$module}/model";
        }
        else
        {
            $dir = "{$this->root_dir}/app/model";
        }

        $class_name = $this->_formatClassName($model_name);
        $path = $this->_getClassFilePath($dir, $class_name);

		// 确定控制器文件是否存在
		if (is_file($path)) {
			echo "Class '{$class_name}' declare file '{$path}' exists.\n";
			return 0;
		}

		$content = $this->getCode($class_name, $table_name);
		return $this->_createFile($path, $content);
	}

	/**
	 * 生成代码
	 */
	function getCode($class_name, $table_name)
	{
        $arr = explode('.', $table_name);
        if (isset($arr[1]))
        {
            $table_name = $arr[1];
            $schema = $arr[0] . '.';
        }
        else
        {
            $table_name = $arr[0];
            $schema = '';
        }

        $dbo = QDB::getConn();
        $prefix = $dbo->getTablePrefix();
        if ($prefix && substr($table_name, 0, strlen($prefix)) == $prefix)
        {
            $table_name = substr($table_name, strlen($prefix));
        }

        $table_name = "{$schema}{$table_name}";
		$config = array('name' => $table_name);
		$table = new QDB_Table($config);

		$meta = $table->columns();
		$pk = array();
		foreach ($meta as $field) {
			if ($field['pk']) {
				$pk[] = $field['name'];
			}
		}

		$viewdata = array(
			'class_name'  => $class_name,
			'table_name'  => $table_name,
			'meta'        => $meta,
			'pk'          => $pk,
		);
		return $this->_parseTemplate('model', $viewdata);
	}
}
