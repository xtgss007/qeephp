<?php
// $Id$

/**
 * QeePHP Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://qeephp.org/license/new-bsd
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to supprt@qeeyuan.com so we can send you a copy immediately.
 *
 * Copyright (c) 2006-2008 QeeYuan Technologies Ltd. Inc. (http://www.qeeyuan.com)
 *
 */

/**
 * @file
 * @mainpage
 * 
 * <b>QeePHP 是一个快速、灵活的开发框架。应用各种成熟的架构模式和创新的设计，帮助开发者提高开发效率、降低开发难度。</b>
 *
 * 
 * <h3>API 手册主要内容：</h3>
 * 
 * API 手册按照功能划分为多个区域，分别是：
 * 
 * - @ref core
 *   - @ref config
 *   - @ref loader
 *   - @ref registry
 *   - @ref cache
 *   - @ref common
 * - @ref mvc
 *   - @ref application
 *   - @ref controller
 *   - @ref helper
 *   - @ref response
 * - @ref view
 *   - @ref template
 *   - @ref webcontrols
 *   - @ref ajax
 * - @ref database
 *   - @ref connection
 *   - @ref adapter
 *   - @ref select
 *   - @ref table
 *   - @ref activerecord
 *
 * <hr>
 *  
 * <h3>QeePHP 主要目标：</h3>
 * 
 * QeePHP 的主要目标是为开发者创建更复杂、更灵活、更大规模的 Web 应用程序提供一个基础解决方案。 
 * 
 * 运用 QeePHP 创建应用程序，开发者可以获得更高的开发效率，并且更容易保持应用程序良好的整体架构和细节实现，
 * 同时为今后的扩展提供了充分的灵活性。
 *  
 * 
 * <h3>QeePHP 的主要特征包括：</h3>
 * 
 * - 完全“面向接口”架构；
 * - 具有一个微内核。该内核提供各种基础服务，帮助开发者将各个组件组装起来形成完整的应用程序；
 * - 简单易用、功能强大、高性能的数据库抽象层和表数据入口； 
 * - 为面向对象应用量身订造的 ActiveRecord 实现，让 PHP 应用程序充分利用面向对象技术带来的优势； 
 * - WebControls 机制，提供了将用户界面组件化的能力，帮助开发者创建复杂易用的用户界面； 
 * - 支持多种流行的模板引擎，保护开发者现有的知识和技能；
 * - 基于角色的访问控制，以及高度可定制的访问控制组件；
 * - 丰富的辅助功能，解决开发中最常见的问题；
 * - 采用限制最少的 BSD 协议，让企业可以充分利用 QeePHP 带来的利益。
 *  
 * 
 * <h3>QeePHP 拥有丰富的文档和活跃的社区：</h3>
 * 
 * - 完全中文化的文档，最大程度降低中文开发者的学习成本；
 * - 活跃的社区，让开发者可以自由交流，并且及时获得帮助。
 * 
 * 
 * <h3>QeePHP 与 FleaPHP 的关系：</h3>
 * 
 * - QeePHP 是起源科技在成功研发 FleaPHP，并推广应用后。总结经验，并且运用各种最新技术开发的新一代框架。
 * QeePHP 充分利用 PHP5 的优势，为企业和开发者提供标准化、规范化、可控制的开发基础平台。
 * 
 * 详情请访问 http://qeephp.org/
 * 
 * @addtogroup core 框架核心
 * @{
 */

//! QeePHP 框架的版本号
define('Q_VERSION', '2.0');

//! QeePHP 框架所在绝对路径
define('Q_DIR', dirname(__FILE__));

//! DIRECTORY_SEPARATOR 的简写
define('DS', DIRECTORY_SEPARATOR);

Q::import(Q_DIR);
spl_autoload_register(array('Q', 'loadClass'));
Q::setIni(require(Q_DIR . '/_config/default_config.php'));


/**
 * 类 Q 提供 QeePHP 框架的基本服务
 * 
 * 包括：
 *   - @ref config
 *   - @ref loader
 *   - @ref registry
 *   - @ref cache
 *   - @ref common
 */
abstract class Q
{
    /**
     * 配置存储容器
     *
     * @var array
     */
    private static $_config = array();

    /**
     * 对象注册表
     *
     * @var array
     */
    private static $_objects = array();

    /**
     * 类搜索路径
     *
     * @var array
     */
    private static $_class_path = array();

    /** 
     * @addtogroup config 配置管理
     * @ingroup core
     * 
     * QeePHP 提供一组接口，让框架和应用程序可以处理不同来源的配置信息。
     * 
     * 不管配置信息的来源是什么，所有的配置信息都会被保存在一个内部的配置存储容器中。
     * 然后框架和应用程序可以利用 Q::getIni() 、 Q::setIni() 等方法读取或修改容器中的配置信息。
     * 
     * @{
     */
    
    /**
     * 获取指定的配置内容
     * 
     * $option 参数指定要获取的设置名。
     * 如果指定名称的设置不存在，则返回由 $default 参数指定的值。
     * 
     * @code
     * $option_value = Q::getIni('my_option');
     * @endcode
     * 
     * 对于层次化的配置信息（类似 PHP 的嵌套数组），可以通过在 $option 中使用“/”符号来指定。
     * 
     * 例如有一个名为 option_group 的设置项，其中包含三个子项目。现在要查询其中的 my_option 设置项的内容。
     * 
     * @code
     * // +--- option_group
     * //    +-- my_option  = this is my_option 
     * //    +-- my_option2 = this is my_option2
     * //    \-- my_option3 = this is my_option3
     * 
     * // 查询 option_group 设置组里面的 my_option 项
     * $option_value = Q::getIni('option_group/my_option');
     * 
     * // 将会显示 this is my_option
     * echo $option_value;
     * 
     * @endcode
     * 
     * 要读取更深层次的设置项，可以使用更多的“/”符号。但太多层次会导致读取速度变慢。
     * 
     * 如果要获得所有设置项的内容，将 $option 参数指定为 '/' 即可：
     * 
     * @code
     * // 获取所有设置项的内容
     * $all = Q::getIni('/');
     * @endcode
     *
     * @param string $option
     *   要获取设置项的名称
     * @param mixed $default
     *   当设置不存在时要返回的设置默认值
     * @return mixed
     *   返回设置项的值
     */
    static final function getIni($option, $default = null)
    {
        if ($option == '/') {
            return self::$_config;
        }
        if (strpos($option, '/') === false) {
            return isset(self::$_config[$option]) ? self::$_config[$option] : $default;
        }
        $parts = explode('/', $option);
        $pos =& self::$_config;
        foreach ($parts as $part) {
            if (!isset($pos[$part])) { return $default; }
            $pos =& $pos[$part];
        }
        return $pos;
    }

    /**
     * 修改指定配置的内容
     *
     * 当 $option 参数是字符串时，$option 指定了要修改的设置项，$data 则是要为该设置项指定的新数据。
     * 
     * @code
     * // 修改一个设置项
     * Q::setIni('option_group/my_option2', 'new value');
     * @endcode
     * 
     * 如果 $option 是一个数组，则假定要修改多个设置项。那么 $option 则是一个由设置项名称和设置值组成的名值对，
     * 或者是一个嵌套数组。
     * 
     * @code
     * // 修改多个设置项
     * $arr = array(
     *      'option_1' => 'value 1',
     *      'option_2' => 'value 2',
     *      'option_group/my_option3' => 'new value', 
     * );
     * Q::setIni($arr);
     * 
     * // 修改多个设置项，以及 option_group 中的多个设置项 
     * $arr = array(
     *      'option_group' => array(
     *          'my_option'  => '1',
     *          'my_option2' => '2',
     *      ),
     *      'option_1' => 'value 1',
     *      'option_2' => 'value 2',
     * );
     * Q::setIni($arr); 
     * @endcode
     * 
     * 在修改包含子项目的设置项时，不管使用哪种方式，都不会清空设置项的所有内容，而只是替换要修改的内容。
     * 因此如果要清空某个设置项，应该使用 Q::unsetIni() 方法。
     * 
     * @param string $option
     *   要修改的设置项名称，或包含多个设置项目的数组
     * @param mixed $data
     *   指定设置项的新值
     */
    static final function setIni($option, $data = null)
    {
        if (is_array($option)) {
            foreach ($option as $key => $value) {
                self::setIni($key, $value);
            }
            return;
        }
        
        if (!is_array($data)) {
            if (strpos($option, '/') === false) {
                self::$_config[$option] = $data;
                return;
            }
            
            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos =& self::$_config;
            for ($i = 0; $i <= $max; $i++) {
                $part = $parts[$i];
                if ($i < $max) {
                    if (!isset($pos[$part])) {
                        $pos[$part] = array();
                    }
                    $pos =& $pos[$part];
                } else {
                    $pos[$part] = $data;
                }
            }
            return;
        }
        
        foreach ($data as $key => $value) {
            self::setIni($option . '/' . $key, $value);
        }
    }
    
    /**
     * 删除指定的配置
     * 
     * $option 参数的用法同 Q::getIni() 和 Q::setIni()。
     * 
     * @param mixed $option
     *   要删除的设置项名称
     */
    static final function unsetIni($option)
    {
        if (strpos($option, '/') === false) {
            unset(self::$_config[$option]);
        } else {
            $parts = explode('/', $option);
            $max = count($parts) - 1;
            $pos =& self::$_config;
            for ($i = 0; $i <= $max; $i++) {
                $part = $parts[$i];
                if ($i < $max) {
                    if (!isset($pos[$part])) {
                        $pos[$part] = array();
                    }
                    $pos =& $pos[$part];
                } else {
                    unset($pos[$part]);
                }
            }
        }
    }

    /* @} */
    
    /**
     * @addtogroup loader 类和文件的载入
     * @ingroup core
     * 
     * QeePHP 为管理大量类提供了便捷手段，帮助开发者更好得组织应用程序的目录结构。
     * 
     * 当 QeePHP 运行过程中需要构造某个尚未载入定义的对象时，会尝试自动加载类的定义文件。
     * 这种能力既可以简化开发，又可以实现“按需加载”，从而避免无谓的性能影响。
     *
     * 要充分利用 QeePHP 提供的类载入机制，需要按照特定的规则对类以及类定义文件进行命名：
     * 
     * - 组成类名称的多个单词之间以“_”符号分隔，例如 QDB_Table、Controller_Posts；
     * - 将类名称中的“_”符号替换为目录分隔符，再加上“.php”就是类定义文件的存储结构；
     * - 类定义文件的文件名及其目录名全部为小写。
     * 
     * 例如 QDB_Table 这个类的定义文件存储结构是 qdb/table.php，
     * 而 Controller_Posts 的定义文件存储结构是 controller/posts.php。
     * 
     * 在命名时，建议按照“所属模块_名称_类型”的规则来进行。
     * 
     * 例如：
     * 
     * - QDB_Table 这个类名称表示该类属于“QDB”模块，而“Table”则是类的名称；
     * - QDB_ActiveRecord_Abstract 这个类名称在模块名和名称后面附加了类型“Abstract”，
     *   表示该类是一个抽象类（abstract）；
     * - 同理，QDB_ActiveRecord_Interface 表示该类是一个接口（interface）。
     * 
     * 对于模块和子模块，类名称应该反应出这种模块间的结构关系。如 QDB_ActiveRecord_Behavior_Abstract 
     * 表示该类属于“QDB/ActiveRecord”模块，类型是抽象类。
     * 
     * 只要掌握了这种命名方式，就可以很容易的将大量类合理的组织起来，为简化应用程序的开发、
     * 维护提供一个良好的基础。
     * 
     * @{
     */

    /**
     * 载入指定类的定义文件，如果载入失败抛出异常
     *
     * @code
     * Q::loadClass('Table_Posts');
     * @endcode
     *
     * Q::loadClass() 会按照
     *  
     * 在查找类定义文件时，类名称中的“_”会被替换为目录分隔符，
     * 从而确定类名称和类定义文件的映射关系 (例如：Table_Posts 的定义文件为 table/posts.php)
     *
     * loadClass() 会首先尝试从开发者指定的搜索路径中查找类的定义文件。
     * 搜索路径可以用 Q::import() 添加。
     *
     * @param string $className 要载入的类名字
     * @param string|array $dirs
     */
    static final function loadClass($class_name, $dirs = null)
    {
        if (class_exists($class_name, false) || interface_exists($class_name, false)) {
            return;
        }

        $class_name = strtolower($class_name);
        $filename = str_replace('_', DS, $class_name);

        if ($filename != $class_name) {
            $dirname = dirname($filename);
            if (!empty($dirs)) {
                if (!is_array($dirs)) {
                    if (empty($dirs)) {
                        $dirs = array();
                    } else {
                        $dirs = explode(PATH_SEPARATOR, $dirs);
                    }
                }
                foreach ($dirs as $offset => $dir) {
                    $dirs[$offset] = $dir . DS . $dirname;
                }
            } else {
                $dirs = array();
                foreach (self::$_class_path as $dir) {
                    if ($dir == '.') {
                        $dirs[] = $dirname;
                    } else {
                        $dir = rtrim($dir, '\\/');
                        $dirs[] = $dir . DS . $dirname;
                    }
                }
            }
            $filename = basename($filename) . '.php';
        } else {
            $dirs = self::$_class_path;
            $filename .= '.php';
        }

        self::loadClassFile($filename, $dirs, $class_name);
    }

    /**
     * 载入指定名称的第三方库
     *
     * @param string $name
     */
    static final function loadVendor($name)
    {
        Q::loadFile($name . '.php', true, (array)self::getIni('runtime_vendor_dirs'));
    }
    
    /**
     * 导入文件搜索路径
     *
     * 在使用 loadClass() 时，会通过 import() 指定的搜索路径查找类定义文件。
     *
     * 当 loadClass('Service_Products') 时，由于类名称映射出来的类定义文件已经包含了目录名
     * （Service_Products 映射为 Service/Products.php）。
     * 所以只能将 Service 子目录所在目录添加到搜索路径，而不是直接将 Service 目录添加到搜索路径。
     *
     * example:
     * <code>
     * // 假设要载入的文件完整路径为 /www/app/Service/Products.php
     * Q::import('/www/app');
     * Q::loadClass('Service_Products');
     * </code>
     *
     * @param string $dir
     */
    static final function import($dir)
    {
        if (!isset(self::$_class_path[$dir]) || !is_dir($dir)) {
            self::$_class_path[$dir] = realpath($dir);
        }
    }
    
    /**
     * 载入特定文件，并检查是否包含指定类的定义
     *
     * @param string $filename
     * @param string|array $dirs
     * @param string $class_name
     */
    static final function loadClassFile($filename, $dirs, $class_name)
    {
        if (class_exists($class_name, false) || interface_exists($class_name, false)) {
            return;
        }
        Q::loadFile($filename, true, $dirs);
        if (!class_exists($class_name, false) && !interface_exists($class_name, false)) {
            // LC_MSG: %s "%s" not defined in file "%s".
            throw new QException(__('Class "%s" not defined in file "%s".', $class_name, $filename));
        }
    }
    
    /**
     * 载入指定的文件
     *
     * $filename 参数必须是一个包含扩展名的完整文件名。
     * loadFile() 会首先从 $dirs 参数指定的路径中查找文件，
     * 找不到时再从 PHP 的 include_path 搜索路径中查找文件。
     *
     * $once 参数指示同一个文件是否只载入一次。
     *
     * @param string $filename 要载入的文件名
     * @param boolean $once 指示对于同一个文件是否只载入一次
     * @param array $dirs 搜索目录
     * @param boolean $throw 在找不到文件时是否抛出异常
     *
     * @return mixed
     */
    static final function loadFile($filename, $once = false, $dirs = null, $throw = true)
    {
        static $loaded = array();

        $id = $filename . implode(':', (array)$dirs);

        if ($once && isset($loaded[$id])) { return null; }
        if (preg_match('/[^a-z0-9\-_.]/i', $filename)) {
            // LC_MSG: Security check: Illegal character in filename "%s".
            throw new QException(__('Security check: Illegal character in filename "%s".', $filename));
        }

        if (is_null($dirs)) {
            $dirs = array();
        } elseif (is_string($dirs)) {
            $dirs = explode(PATH_SEPARATOR, $dirs);
        }
        foreach ($dirs as $dir) {
            $path = rtrim($dir, '\\/') . DS . $filename;
            if (self::isReadable($path)) {
                if ($once) {
                    $loaded[$id] = true;
                    return include_once $path;
                } else {
                    return include $path;
                }
            }
        }

        // include 会在 include_path 中寻找文件
        if (self::isReadable($filename)) {
            if ($once) {
                $loaded[$id] = true;
                return include $filename;
            } else {
                return include $filename;
            }
        }

        if ($throw) {
            // LC_MSG: File "%s" not found.
            throw new QException(__('File "%s" not found.', $filename));
        }

        return false;
    }
    
    /* @} */
    
    /**
     * @addtogroup registry 对象注册表
     * @ingroup core
     * @{
     */

    /**
     * 返回指定对象的唯一实例，如果指定类无法载入或不存在，则抛出异常
     *
     * @param string $class_name 要获取的对象的类名字
     *
     * @return object
     */
    static final function getSingleton($class_name)
    {
        if (isset(self::$_objects[$class_name])) {
            return self::$_objects[$class_name];
        }
        self::loadClass($class_name);
        return self::register(new $class_name(), $class_name);
    }

    /**
     * 获得指定名字的服务对象实例
     *
     * @param string $service_name
     *
     * @return object
     */
    static final function getService($service_name)
    {
        $class_name = self::getIni('service_' . strtolower($service_name));
        if (empty($class_name)) {
            // LC_MSG: 未配置的服务名 "%s".
            throw new QException(__('未配置的服务名 "%s".', $service_name));
        }

        return self::getSingleton($class_name);
    }

    /**
     * 以特定名字注册一个对象，以便稍后用 registry() 方法取回该对象。
     * 如果指定名字已经被使用，则抛出异常
     *
     * example:
     * @code
     * // 注册一个对象
     * Q::register(new MyObject(), 'MyObejct');
     * .....
     * // 稍后取出对象
     * $obj = Q::regitry('MyObject');
     * @endcode
     *
     * QEE 提供一个对象注册表，开发者可以将一个对象以特定名称注册到其中。
     * 当没有提供 $name 参数时，则以对象的类名称作为注册名。
     *
     * 当 $persistent 参数为 true 时，对象将被放入持久存储区。在下一次执行脚本时，
     * 可以通过 Q::registry() 取出放入持久存储区的对象，无需重新构造对象。
     * 利用这个特性，开发者可以将一些需要大量构造时间的对象放入持久存储区，
     * 从而避免每一次执行脚本都去做对象构造操作。
     *
     * 使用哪一种持久化存储区来保存对象，由配置 object_persistent_provier 决定。
     * 该设置指定一个提供持久化服务的对象名。
     *
     * example:
     * @code
     * if (!Q::isRegistered('ApplicationObject')) {
     *      Q::loadClass('Application');
     *      Q::registry(new Application(), 'ApplicationObject', true);
     * }
     * $app = Q::registry('ApplicationObject');
     * @endcode
     *
     * @param object $obj 要注册的对象
     * @param string $name 注册的名字
     * @param boolean $persistent 是否将对象放入持久化存储区
     *
     * @return object
     */
    static final function register($obj, $name = null, $persistent = false)
    {
        if (!is_object($obj)) {
            // LC_MSG: Type mismatch. $obj expected is object, actual is "%s".
            throw new QException(__('Type mismatch. $obj expected is object, actual is "%s".', gettype($obj)));
        }

        // TODO: 实现对 $persistent 参数的支持
        if (is_null($name)) {
            $name = get_class($obj);
        }
        self::$_objects[$name] = $obj;
        return $obj;
    }

    /**
     * 取得指定名字的对象实例，如果指定名字的对象不存在则抛出异常
     *
     * @param string $name
     *
     * @return object
     */
    static final function registry($name)
    {
        if (isset(self::$_objects[$name])) {
            return self::$_objects[$name];
        }
        // LC_MSG: No object is registered of name "%s".
        throw new QException(__('No object is registered of name "%s".', $name));
    }

    /**
     * 检查指定名字的对象是否已经注册
     *
     * @param string $name
     *
     * @return boolean
     */
    static final function isRegistered($name)
    {
        return isset(self::$_objects[$name]);
    }
    
    /* @} */
    
    /**
     * @addtogroup cache 缓存服务
     * @ingroup core
     * @{
     */
    
    /**
     * 读取指定缓存的内容，如果缓存内容不存在或已经失效，则返回 false
     *
     * @param string $id
     * @param array $policy
     * @param string $backend
     *
     * @return mixed
     */
    static final function getCache($id, array $policy = null, $backend = null)
    {
        static $obj = null;

        if (is_null($backend)) {
            if (is_null($obj)) {
                $obj = self::getSingleton(self::getIni('runtime_cache_backend'));
            }
            return $obj->get($id, $policy);
        } else {
            $cache = self::getSingleton($backend);
            return $cache->get($id, $policy);
        }
    }

    /**
     * 将变量内容写入缓存
     *
     * @param string $id
     * @param mixed $data
     * @param array $policy
     * @param string $backend
     */
    static final function setCache($id, $data, array $policy = null, $backend = null)
    {
        static $obj = null;

        if (is_null($backend)) {
            if (is_null($obj)) {
                $obj = self::getSingleton(self::getIni('runtime_cache_backend'));
            }
            $obj->set($id, $data, $policy);
        } else {
            $cache = self::getSingleton($backend);
            $cache->set($id, $data, $policy);
        }
    }

    /**
     * 删除指定的缓存内容
     *
     * @param string $id
     * @param array $policy
     * @param string $backend
     */
    static final function removeCache($id, array $policy = null, $backend = null)
    {
        if (is_null($backend)) {
            $cache = self::getSingleton(self::getIni('runtime_cache_backend'));
        } else {
            $cache = self::getSingleton($backend);
        }
        $cache->remove($id, $policy);
    }
    
    /* @} */

    /**
     * @addtogroup common 公共服务
     * @ingroup core
     * @{
     */
    
    /**
     * 对输入的数组或字符串进行规格化
     *
     * @param array|string $input
     *
     * @return array
     */
    static final function normalize($input)
    {
        if (!is_array($input)) {
            $input = explode(',', $input);
        }
        $input = array_map('trim', $input);
        return array_filter($input, 'strlen');
    }

    /**
     * 载入 YAML 文件，返回分析结果
     *
     * loadYAML() 会自动使用缓存，只有当 YAML 文件被改变后，缓存才会更新。
     *
     * 关于 YAML 的详细信息,请参考 www.yaml.org 。
     *
     * 用法：
     * <code>
     * $data = Q::loadYAML('myData.yaml');
     * </code>
     *
     * 注意：为了安全起见，不要将 yaml 文件置于浏览器能够访问的目录中。
     * 或者将 YAML 文件的扩展名设置为 .yaml.php，并且在每一个 YAML 文件开头添加“exit()”。
     * 例如：
     * <code>
     * # <?php exit(); ?>
     *
     * invoice: 34843
     * date   : 2001-01-23
     * bill-to: &id001
     * ......
     * </code>
     *
     * 这样可以确保即便浏览器直接访问该 .yaml.php 文件，也无法看到内容。
     *
     * @param string $filename
     * @param array $replace 对于 YAML 内容要进行自动替换的字符串对
     *
     * @return array
     */
    static final function loadYAML($filename, $replace = null)
    {
        static $callback;

        if (!Q::isReadable($filename)) {
            // LC_MSG: File "%s" not found.
            throw new QException(__('File "%s" not found.', $filename));
        }

        Q::loadVendor('spyc');
        $yaml = Spyc::YAMLLoad($filename);

        if (is_null($callback)) {
            $callback = create_function('& $v, $key, $replace', 'foreach ($replace as $search => $rep) { $v = str_replace($search, $rep, $v); }; return $v;');
        }
        array_walk_recursive($yaml, $callback, $replace);
        return $yaml;
    }
    
    /**
     * 检查指定文件是否可读
     *
     * 这个方法会在 PHP 的搜索路径中查找文件。
     *
     * @param string $filename
     *
     * @return boolean
     */
    static final function isReadable($filename)
    {
        if (is_readable($filename)) { return true; }

        $path = get_include_path();
        $dirs = explode(PATH_SEPARATOR, $path);

        foreach ($dirs as $dir) {
            if ('.' == $dir) { continue; }
            if (@is_readable($dir . DS . $filename)) {
                return true;
            }
        }

        return false;
    }
    
    /* @} */
}

/**
 * @addtogroup internal 内部服务
 * @ingroup core
 * @{
 */

/**
 * QeePHP 内部使用的多语言翻译函数
 *
 * 应用程序应该使用 Translate 组件实现多语言界面。
 *
 * @return $msg
 */
function __()
{
    $args = func_get_args();
    $msg = array_shift($args);
    $language = strtolower(Q::getIni('error_language'));
    $messages = Q::loadFile('LC_MESSAGES.php', false, Q_DIR . '/_lang/' . $language, false);
    if (isset($messages[$msg])) {
        $msg = $messages[$msg];
    }
    array_unshift($args, $msg);
    return call_user_func_array('sprintf', $args);
}

/* @} */

/* @} */
