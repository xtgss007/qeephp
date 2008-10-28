<?php

class QApplication_Module
{
    /**
     * 该模块所属的应用程序ID
     *
     * @var string
     */
    protected $_appid;

    /**
     * 该模块的配置
     *
     * @var array
     */
    protected $_config;

    /**
     * 模块名称
     *
     * @var string
     */
    private $_module_name;

    /**
     * 使用的缓存对象
     *
     * @var QCache_File
     */
    private $_cache_backend;

    /**
     * 所有模块的注册索引
     *
     * @var array
     */
    static private $_instances = array();

    /**
     * 构造函数
     *
     * @param string $module_name
     * @param string $appid
     */
    protected function __construct($module_name, $appid)
    {
        $this->_appid = $appid;
        if (empty($module_name))
        {
            $module_name = '#default#';
        }
        $this->_module_name = $module_name;
        self::$_instances[$appid][$module_name] = $this;
    }

    /**
     * 取得指定名字的模块实例
     *
     * @param string $module_name
     * @param string $appid
     *
     * @return QApplication_Module
     */
    static function instance($module_name, $appid)
    {
        $module_name = strtolower($module_name);
        if (empty($module_name))
        {
            $module_name = '#default#';
        }
        if (empty($appid))
        {
            $appid = QApplication_Abstract::defaultAppID();
        }

        if (!isset(self::$_instances[$appid][$module_name]))
        {
            new QApplication_Module($module_name, $appid);
        }
        return self::$_instances[$appid][$module_name];
    }

    /**
     * 返回该模块的配置信息
     *
     * @param boolean $reload
     *
     * @return array
     */
    function config($reload = false)
    {
        if (is_null($this->_config) || $reload)
        {
            $this->_config = $this->loadCachedModuleConfig();
        }
        return $this->_config;
    }

    /**
     * 返回该模块所属应用程序的ID
     *
     * @return string
     */
    function appid()
    {
        return $this->_appid;
    }

    /**
     * 返回该模块的名字
     *
     * @return string
     */
    function module_name()
    {
        return $this->_module_name;
    }

    /**
     * 载入模块的配置
     *
     * @return array
     */
    function loadCachedModuleConfig()
    {
        $app_config = QApplication_Abstract::getAppConfig($this->_appid);

        if (empty($app_config['CONFIG_CACHED']))
        {
            // 不使用缓存，直接读取配置文件
            return self::loadModuleConfig($this->_module_name, $app_config);
        }

        // 确定缓存 ID
        $run_mode = !empty($app_config['RUN_MODE']) ? $app_config['RUN_MODE'] : Q::RUN_MODE_DEPLOY;
        if ($this->_module_name)
        {
            $cache_id = "{$app_config['APPID']}.module.{$this->_module_name}.config.{$run_mode}";
        }
        else
        {
            $cache_id = "{$app_config['APPID']}.app.config.{$run_mode}";
        }

        // 如果有必要，构造缓存对象
        if (is_null($this->_cache_backend))
        {
            $class = $app_config['CONFIG_CACHE_BACKEND'];
            $default_policy = array
            (
                'life_time' => $app_config['CONFIG_CACHE_LIFETIME'],
                'serialize' => true,
                'cache_dir' => $app_config['CONFIG_CACHE_DIR'],
            );
            $this->_cache_backend = new $class($default_policy);
        }

        // 尝试载入缓存的配置信息
        $config = $this->_cache_backend->get($cache_id);
        if (is_array($config))
        {
            return $config;
        }

        // 读取并解析配置，然后写入缓存
        $config = self::loadModuleConfig($this->_module_name, $app_config);
        $this->_cache_backend->set($cache_id, $config);
        return $config;
    }

    /**
     * 载入指定模块的缓存文件，但不使用缓存
     *
     * @param string $module_name
     * @param array $app_config
     *
     * @return array
     */
    static function loadModuleConfig($module_name, array $app_config)
    {
        $run_mode = !empty($app_config['RUN_MODE']) ? $app_config['RUN_MODE'] : Q::RUN_MODE_DEPLOY;
        $extname = !empty($app_config['CONFIG_FILE_EXTNAME']) ? $app_config['CONFIG_FILE_EXTNAME'] : 'yaml';
        $root_dir = $app_config['ROOT_DIR'];

        // 载入配置文件，并替换配置文件中的宏
        if ($module_name != '#default#')
        {
            $module_name = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $module_name));
            $root = "{$root_dir}/config/modules/{$module_name}";

            $files = array
            (
                "{$root}/{$module_name}_module.{$extname}"     => 'global',
                "{$root}/environments/{$run_mode}.{$extname}"  => 'global',
            );
        }
        else
        {
            $root = "{$root_dir}/config";

            $files = array
            (
                "{$root}/environment.{$extname}"               => 'global',
                "{$root}/database.{$extname}"                  => 'db_dsn_pool',
                "{$root}/acl.{$extname}"                       => 'acl_global_act',
                "{$root}/environments/{$run_mode}.{$extname}"  => 'global',
                "{$root}/app.{$extname}"                       => 'appini',
                "{$root}/routes.{$extname}"                    => 'routes',
            );
        }

        $replace = array();
        foreach ($app_config as $key => $value)
        {
            $replace["%{$key}%"] = $value;
        }

        $config = array();
        foreach ($files as $filename => $scope)
        {
            if (!file_exists($filename)) { continue; }
            $contents = Helper_YAML::load($filename, $replace);
            if ($scope == 'global')
            {
                $config = array_merge_recursive($config, $contents);
            }
            else
            {
                if (!isset($config[$scope]))
                {
                    $config[$scope] = array();
                }
                $config[$scope] = array_merge_recursive($config[$scope], $contents);
            }
        }

        if (!empty($config['db_dsn_pool'][$run_mode]))
        {
            $config['db_dsn_pool']['default'] = $config['db_dsn_pool'][$run_mode];
        }

        return $config;
    }

}

