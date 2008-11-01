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
        $controllers = new QColl('QReflection_Controller');
        foreach ($this->_managed_app->reflectionModules() as $module)
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
                $error = $this->_getLastError();
                if ($error)
                {
                    $error = "\n\n{$error}";
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
        $models = new QColl('QReflection_Model');
        foreach ($this->_managed_app->reflectionModules() as $module)
        {
           $models->append($module->reflectionModels());
        }

        $this->view['models'] = $models;

        try
        {
            $tables = $this->_getDBO()->metaTables();
            if (!empty($tables))
            {
                $tables = array_combine($tables, $tables);
            }
            array_unshift($tables, 0);
            $tables[0] = '- 选择要使用的数据表 -';
        }
        catch (QException $ex)
        {
            $error = $this->_getLastError();
            if ($error)
            {
                $error = "\n\n{$error}";
            }
            $this->app()->setFlashMessage($ex->getMessage(). $error, self::FLASH_MSG_ERROR);
            $tables = array('- 无法读取数据库或没有数据表 -');
        }

        $this->view['tables'] = $tables;
        $this->_help_text = '查看已有的模型，并能够创建新模型。';
    }

    /**
     * 获得指定数据表的字段信息
     */
    function actionGetColumns()
    {
        $table_name = $this->context->table;
        $this->view['columns'] = $this->_getDBO()->metaColumns($table_name);
        $this->view['table_name'] = $table_name;
    }

    /**
     * 创建一个新模型
     */
    function actionNewModel()
    {
        $name = $this->context->new_model_name;
        $table_name = $this->context->table_name;
        if (!empty($name))
        {
            try
            {
                $log = $this->_managed_app->generateModel($name, $table_name)->log();
                $this->app()->setFlashMessage(implode("\n", $log));
            }
            catch (QException $ex)
            {
                $error = $this->_getLastError();
                if ($error)
                {
                    $error = "\n\n{$error}";
                }
                $this->app()->setFlashMessage($ex->getMessage() . $error, self::FLASH_MSG_ERROR);
            }
        }

        return new QView_Redirect($this->context->url(null, 'models'));
    }

    protected function _getDBO()
    {
        $dsn = $this->_managed_app->getIni('db_dsn_pool/default');
        $dbtype = $dsn['driver'];
        $objid = "dbo_{$dbtype}_" .  md5(serialize($dsn));
        $class_name = 'QDB_Adapter_' . ucfirst($dbtype);
        return new $class_name($dsn, $objid);
    }
}

