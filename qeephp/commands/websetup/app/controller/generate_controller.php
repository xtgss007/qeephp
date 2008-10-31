<?php

class Controller_Generate extends AppController_Abstract
{
    function actionIndex()
    {
        $this->_help_text = '代码生成器可以自动生成控制器、模型等代码，提高开发效率。';
    }

    /**
     * 列出所有控制器
     */
    function actionControllers()
    {
        $app = $this->_managed_app;
        $dir = rtrim($app->ROOT_DIR(), '/\\') . '/app/controller';
        $controllers = new QColl('QReflection_Controller');
        foreach ($app->reflectionModules() as $module)
        {
            $controllers->append($module->reflectionControllers());
        }

        $this->view['controllers'] = $controllers;
        $this->_help_text = '查看已有的控制器，并能够创建新控制器。';
    }

    /**
     * 创建一个新控制器
     */
    function actionNewController()
    {
        $name = $this->context->new_controller_name;
        if (!empty($name))
        {
            try
            {
                $log = $this->_managed_app->generateController($name)->log();
                $this->app()->setFlashMessage(implode("\n", $log));
            }
            catch (QException $ex)
            {
                if (function_exists('error_get_last'))
                {
                    $error = error_get_last();
                    if (!empty($error['message']))
                    {
                        $error = "\n\n" . strip_tags($error['message']);
                    }
                }
                else
                {
                    $error = '';
                }
                $this->app()->setFlashMessage($ex->getMessage() . $error, self::FLASH_MSG_ERROR);
            }
        }

        return new QView_Redirect($this->context->url(null, 'controllers'));
    }

    /**
     * 列出所有模型
     */
    function actionModels()
    {
        $dir = $this->_managed_app->configItem('ROOT_DIR') . '/app/model';
        $appinfo = $this->_managed_app;
		$dsn = (array)$appinfo->getIni('db_dsn_pool');
		
        $files = $this->_getModels($dir);
        ksort($files);
        $this->view['models'] = $files;
        $tables = $this->_getTables($dsn['default']);
        if (!empty($tables))
        {
            $tables = array_combine($tables, $tables);
        }
        array_unshift($tables, 0);
        $tables[0] = '- 选择要使用的数据表 -';
        $this->view['tables'] = $tables;
        $this->_help_text = '查看已有的模型，并能够创建新模型。';
    }

    /**
     * 获得指定数据表的字段信息
     */
    function actionGetColumns()
    {
    	$table_name = $this->context->table;
    	$appinfo = $this->_managed_app;
		$dsn = (array)$appinfo->getIni('db_dsn_pool');
		
    	$this->view['columns'] = $this->_getColumns($table_name, $dsn['default']);
    	$this->view['table_name'] = $table_name;
    	$this->view_layouts = '-none-';
	}

	/**
	 * 创建一个新模型
	 */
	function actionNewModel()
	{
        $name = $this->context->new_model_name;
        $table_name = $this->context->table_name;
        if (!empty($name) && !empty($table_name))
        {
        	$url = $this->context->url(null, null, array('_app_op' => 'newmodel', 'name' => $name, 'table_name' => $table_name), '', '');
            $this->app->setFlashMessage(file_get_contents($url));
        }

        return new QView_Redirect($this->context->url(null, 'models'));
	}

    protected function _getControllers($dir, $namespace = '')
    {
        $files = array();
        $dh = opendir($dir);
        while (($file = readdir($dh)))
        {
            $path = realpath(rtrim($dir, '/\\') . DS . $file);
            if (substr($file, 0, 1) == '.') { continue; }
            if (is_dir($path))
            {
                $other = $this->_getControllers($path, $file);
                $files = array_merge($other, $files);
            }
            elseif (!is_file($path) || strpos($file, '_') == false)
            {
                continue;
            }
            else
            {
                list($name) = explode('_', $file);
                if ($namespace)
                {
                    $name = "{$namespace}::{$name}";
                }
                $files[$name] = $path;
            }
        }
        closedir($dh);
        return $files;
    }

    protected function _getModels($dir)
    {
        $files = array();
        $dh = opendir($dir);
        while (($file = readdir($dh)))
        {
            $path = realpath(rtrim($dir, '/\\') . DS . $file);
            if (substr($file, 0, 1) == '.') { continue; }
            if (is_dir($path))
            {
            	$models = $this->_getModels($path);
            	$files = array_merge($files, $models);
			}
			else
			{
            	$content = file_get_contents($path);
            	$m = null;
            	preg_match("/^class ([a-z_0-9]+) extends /im", $content, $m);
            	if (empty($m[1])) { continue; }
            	$class_name = $m[1];
            	preg_match("/'table_name'[ ]*=>[ ]*'([a-z_0-9\\.]+)'/", $content, $m);
            	if (empty($m[1])) { continue; }
            	$table_name = $m[1];

            	$files[$class_name] = array($table_name, $path);
			}
        }
        closedir($dh);
        return $files;
    }

    protected function _getTables($dsn)
    {
        Q::setIni('db_dsn_pool/temp_dsn', $dsn);
        $dbo = QDB::getConn('temp_dsn');

        try
        {
            return $dbo->metaTables();
        }
        catch (Exception $ex)
        {
            $this->app->setFlashMessage($ex->getMessage());
        }
        return array();
    }

    protected function _getColumns($table_name, $dsn)
    {
        Q::setIni('db_dsn_pool/temp_dsn', $dsn);
        $dbo = QDB::getConn('temp_dsn');

        try
        {
        	return $dbo->metaColumns($table_name);
        }
        catch (Exception $ex)
        {
            $this->app->setFlashMessage($ex->getMessage());
        }
        return array();
	}
}
