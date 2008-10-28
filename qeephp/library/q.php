<?php
// $Id: q.php 351 2008-05-01 17:47:05Z dualface $

/**
 * @file
 * 定义 QeePHP 框架核心类
 *
 * @ingroup core
 *
 * @{
 */

if (defined('Q_VERSION')) { return; }

//! QeePHP 框架的版本号
define('Q_VERSION', '2.0');

//! QeePHP 框架所在绝对路径
define('Q_DIR', dirname(__FILE__));

//! DIRECTORY_SEPARATOR 的简写
define('DS', DIRECTORY_SEPARATOR);

//! CURRENT_TIMESTAMP 定义为当前时间，减少框架调用 time() 的次数
define('CURRENT_TIMESTAMP', time());

// 设置对象的自动载入
Q::import(Q_DIR);
spl_autoload_register(array('Q', 'loadClass'));

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
     * 指示应用程序运行模式
     */
    const RUN_MODE_DEVEL    = 'devel';
    const RUN_MODE_DEPLOY   = 'deploy';
    const RUN_MODE_TEST     = 'test';

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
     * 获取指定模块的配置内容
     *
     * 具体用法请参考 QContext::getIni() 方法。
     *
     * $module_name 参数为 null 或 app 时，则表示读取全局设置。
     *
     * @param string $option
     *   要获取设置项的名称
     * @param mixed $default
     *   当设置不存在时要返回的设置默认值
     * @param string $module_name
     *   要从哪一个模块读取设置
     *
     * @return mixed
     *   返回设置项的值
     */
    static final function getIni($option, $default = null, $module_name = null)
    {
        return QContext::instance($module_name)->getIni($option, $default);
    }

    /**
     * 修改指定模块配置的内容
     *
     * 具体使用方法请参考 QContext::setIni()。
     *
     * $module_name 参数为 null 或 app 时，则表示修改全局设置。
     *
     * @param string $option
     *   要修改的设置项名称，或包含多个设置项目的数组
     * @param mixed $data
     *   指定设置项的新值
     * @param string $module_name
     *   要修改哪一个模块的设置
     */
    static final function setIni($option, $data = null, $module_name = null)
    {
        QContext::instance($module_name)->setIni($option, $data);
    }

    /**
     * 删除指定的配置
     *
     * $option 和 $module_name 参数的用法同 QContext::getIni() 和 QContext::setIni()。
     *
     * @param mixed $option
     *   要删除的设置项名称
     * @param string $module_name
     *   要修改哪一个模块的设置
     */
    static final function unsetIni($option, $module_name = null)
    {
        QContext::instance($module_name)->unsetIni($option);
    }

    /* @} */

    /**
     * @addtogroup loader 类和文件的载入
     * @ingroup core
     *
     * QeePHP 为管理大量类提供了便捷手段，帮助开发者更好地组织应用程序的目录结构。
     *
     * 当 QeePHP 运行过程中需要构造某个尚未定义的对象时，会尝试自动加载类的定义文件。
     * 这种能力既可以简化开发，又可以实现“按需加载”，从而避免无谓的性能影响。
     *
     * <h3>用包来组织大量的类</h3>
     *
     * 要充分利用 QeePHP 提供的类载入机制，需要按照特定的规则对类以及类定义文件进行命名：
     *
     * <ul>
     *   <li>组成类名称的多个单词之间以“_”（下划线）分隔，例如 QDB_Table、Controller_Posts；</li>
     *   <li>将类名称中的“_”（下划线）符号替换为目录分隔符，再加上“.php”就是类定义文件的存储路径；</li>
     *   <li>类定义文件的文件名及其目录名全部为小写。</li>
     * </ul>
     *
     * 例如 QDB_Table 这个类的定义文件存储路径是 qdb/table.php，
     * 而 Controller_Posts 的定义文件存储路径是 controller/posts.php。
     *
     * 在命名时，建议按照“所属包_名称_类型”的规则来进行。
     *
     * 例如：
     *
     * <ul>
     *   <li>QDB_Table 这个类名称表示该类属于 QDB 包，而 Table 则是类的名称；</li>
     *   <li>QDB_ActiveRecord_Abstract 这个类名称在包名和名称后面附加了类型 Abstract，
     *   表示该类是一个抽象类（abstract）；</li>
     *   <li>同理，QDB_ActiveRecord_Interface 表示该类是一个接口（interface）。</li>
     * </ul>
     *
     * 对于包和子包，类名称应该反应出这种包间的结构关系。
     * 如 QDB_ActiveRecord_Behavior_Abstract 表示该类属于 QDB/ActiveRecord 包。
     *
     * 只要掌握了这种命名方式，就可以很容易的将大量类合理的组织起来，
     * 为简化应用程序的开发、维护提供一个良好的基础。
     *
     * <h3>使用模块</h3>
     *
     * 在 QeePHP 中，模块是一个逻辑上的子系统。每个模块都有自己的控制器、模型、视图等内容。
     * 模块放置在应用程序的 modules 目录的一个子目录中。子目录名就是模块名。
     * 例如 /modules/cms/ 目录中的模块名为 cms。
     *
     * 在一个模块中，类名称不需要添加特定前缀。因此在命名时，应该尽量避免多个模块之间的命名冲突。
     * 幸运的是由于 PHP 的脚本语言特性，只要不同时载入两个同名的类，就不会发生错误。
     *
     * QeePHP 中使用一个模块是非常容易的，可以利用 Q::loadClass()、Q::getSingleton() 等方法来使用模块中的类。
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
     * 在查找类定义文件时，类名称中的“_”（下划线）会被替换为目录分隔符，从而确定类定义文件的存储路径。
     *
     * 为了能够适应复杂应用程序的需求，Q::loadClass() 会按照两种方式在特定的目录中查找类定义文件。
     *
     * <ul>
     *   <li>
     *   如果没有提供 $dirs 参数，Q::loadClass() 会按照 QeePHP 内部的一个“类载入路径”来查找指定类的定义文件；
     *
     *   “类载入路径”是 QeePHP 内部的一个目录列表，确定了 Q::loadClass() 会自动到哪些目录中去查找类定义文件。
     *   当 Q::loadClass() 查找类定义文件时，首先将类名称中的“_”（下划线）替换为目录分隔符，
     *   然后按照“类载入路径”一个个目录的去查找类定义文件。
     *
     *   <ul>
     *     <li>假设当前的“类载入路径”为 /www/mysite/app 和 /www/mysite/lib/qeephp；</li>
     *     <li>要载入 QDB_ActiveRecord_Meta 类；</li>
     *     <li>Q::loadClass() 首先从 QDB_ActiveRecord_Meta 类名称获得定义文件的存储路径为 qdb/activerecord/meta.php；</li>
     *     <li>Q::loadClass() 接下来会尝试读取 /www/mysite/app/qdb/activerecord/meta.php 和
     *     /www/mysite/lib/qeephpqdb/activerecord/meta.php 文件。</li>
     *   </ul>
     *
     *   从这个搜索过程可以看出，“类搜索路径”确定了类定义文件所在的父级目录，而类定义文件的确切存储位置是根据
     *   “类搜索路径”和类名称来确定的。可以使用 Q::import() 方法来添加更多的目录到 QeePHP 内部的“类搜索路径”中。
     *   </li>
     *
     *   <li>如果提供了 $dirs 参数，则忽略 QeePHP 内部的“类搜索路径”，而是按照 $dirs 参数指定的“搜索路径”来查找类定义文件。</li>
     * </ul>
     *
     * $dirs 参数可以是一个以 PATH_SEPARATOR 常量分隔的字符串，也可以是一个包含多个目录名的数组。
     *
     * @code
     * Q::loadClass('Table_Posts', array('/www/mysite/app', '/www/mysite/lib'));
     * @endcode
     *
     * 如果要载入特定模块的一个类，可以在类名称后面添加 "@模块名" 来指定，例如：
     *
     * @code
     * // 载入 CMS 模块的 Post 类
     * Q::loadClass('Post@cms');
     * @endcode
     *
     * 模块名是不区分大小写的，但建议使用全小写的模块名。
     *
     * @param string $className
     *   要载入的类
     * @param string|array $dirs
     *   指定载入类的搜索路径
     */
    static final function loadClass($class_name, $dirs = null)
    {
        if (class_exists($class_name, false) || interface_exists($class_name, false))
        {
            return;
        }

        $class_name = strtolower($class_name);
        if (strpos($class_name, '@') !== false)
        {
            list ($class_name, $module_name) = explode('@', $class_name);
            QContext::instance($module_name);
        }
        $filename = str_replace('_', DS, $class_name);

        if ($filename != $class_name)
        {
            $dirname = dirname($filename);
            if (! empty($dirs))
            {
	            if (! is_array($dirs))
                {
    				if (empty($dirs))
                    {
                        $dirs = array();
                    }
                    else
                    {
                        $dirs = explode(PATH_SEPARATOR, $dirs);
                    }
                }
                foreach ($dirs as $offset => $dir)
                {
                    $dirs[$offset] = $dir . DS . $dirname;
                }
            }
            else
            {
                $dirs = array();
                foreach (self::$_class_path as $dir)
                {
                    if ($dir == '.')
                    {
                        $dirs[] = $dirname;
                    }
                    else
                    {
                        $dir = rtrim($dir, '\\/');
                        $dirs[] = $dir . DS . $dirname;
                    }
                }
            }
            $filename = basename($filename) . '.php';
        }
        else
        {
            $dirs = self::$_class_path;
            $filename .= '.php';
        }

        self::loadClassFile($filename, $dirs, $class_name);
    }

    /**
     * 添加一个类搜索路径
     *
     * 如果需要载入的类没有在 QeePHP 和应用程序的默认目录中，必须使用 Q::import() 添加类定义文件所在目录到“类搜索路径”中。
     * 这样 Q::loadClass() 才能找到类定义文件。
     *
     * 要注意，Q::import() 添加的路径和类名称有关系。
     *
     * 例如类的名称为 Vendor_Smarty_Adapter，那么该类的定义文件存储结构就是 vendor/smarty/adapter.php。
     * 因此在用 Q::import() 添加 Vendor_Smarty_Adapter 类的搜索路径时，只能添加 vendor/smarty/adapter.php 的父目录。
     *
     * @code
     * Q::import('/www/app');
     * Q::loadClass('Vendor_Smarty_Adapter');
     * // 实际载入的文件是 /www/app/vendor/smarty/adapter.php
     * @endcode
     *
     * @param string $dir
     *   要添加的搜索路径
     */
    static final function import($dir)
    {
        if (! isset(self::$_class_path[$dir]))
        {
            self::$_class_path[$dir] = $dir;
        }
    }

    /**
     * 载入特定文件，并检查是否包含指定类的定义
     *
     * 该方法从 $dirs 参数提供的目录中查找并载入 $filename 参数指定的文件。
     * 然后检查该文件是否定义了 $class_name 参数指定的类。
     *
     * 如果没有找到指定类，则抛出异常。
     *
     * @code
     * Q::loadClassFile('Smarty.class.php', $dirs, 'Smarty');
     * @endcode
     *
     * @param string $filename
     *   要载入文件的文件名（含扩展名）
     * @param string|array $dirs
     *   文件的搜索路径
     * @param string $class_name
     *   要检查的类
     */
    static final function loadClassFile($filename, $dirs, $class_name)
    {
        if (class_exists($class_name, false) || interface_exists($class_name, false))
        {
            return;
        }
        Q::loadFile($filename, $dirs);
        if (!class_exists($class_name, false) && ! interface_exists($class_name, false))
        {
            throw new QException_ClassNotDefined($class_name, $filename);
        }
    }

    /**
     * 载入指定的文件
     *
     * 该方法从 $dirs 参数提供的目录中查找并载入 $filename 参数指定的文件。
     * 如果文件不存在，则根据 $throw 参数决定是否抛出异常。
     *
     * @code
     * Q::loadFile('my_file.php', $dirs);
     * @endcode
     *
     * @param string $filename
     *   要载入文件的文件名（含扩展名）
     * @param array $dirs
     *   文件的搜索路径
     * @param boolean $throw
     *   在找不到文件时是否抛出异常
     *
     * @return mixed
     */
    static final function loadFile($filename, $dirs = null, $throw = true)
    {
        if (preg_match('/[^a-z0-9\-_.]/i', $filename))
        {
            throw new QException_IllegalFilename($filename);
        }

        if (is_null($dirs))
        {
            $dirs = array();
        }
        elseif (is_string($dirs))
        {
            $dirs = explode(PATH_SEPARATOR, $dirs);
        }
        foreach ($dirs as $dir)
        {
            $path = rtrim($dir, '\\/') . DS . $filename;
            if (is_file($path))
            {
                return include $path;
            }
        }

        if ($throw)
        {
            throw new QException_FileNotFound($filename);
        }

        return false;
    }

    /* @} */

    /**
     * @addtogroup registry 对象注册表
     * @ingroup core
     *
     * 对象注册表为管理大量对象提供了方便。
     *
     * 开发者可以将对象实例登记到对象注册表中，并在后续的执行过程中从注册表中查找登记的对象。
     * 对于一些不需要多次构造的对象，这种方式可以避免不必要的对象构造操作，提高性能。
     *
     * @{
     */

    /**
     * 返回指定对象的唯一实例，如果该对象的类定义无法载入则抛出异常
     *
     * Q::getSingleton() 完成下列工作：
     *
     * <ul>
     *   <li>在对象注册表中查找指定类名称的对象实例是否存在；</li>
     *   <li>如果存在，则返回该对象实例；</li>
     *   <li>如果不存在，则载入类定义文件，并构造一个对象实例；</li>
     *   <li>将新构造的对象以类名称作为对象名登记到对象注册表；</li>
     *   <li>返回查找到的对象实例或新构造的对象实例。</li>
     * </ul>
     *
     * 使用 Q::getSingleton() 的好处在于不管在应用程序有多少个地方需要用到同一个对象，都只需要构造一次对象：
     *
     * @code
     * // 在位置 A 处使用对象 My_Object
     * $obj = Q::getSingleton('My_Object');
     * ...
     * ...
     * // 在位置 B 处使用对象 My_Object
     * $obj2 = Q::getSingleton('My_Object');
     * // $obj 和 $obj2 都是指向同一个对象实例，避免了多次构造，提高了性能
     * @endcode
     *
     * 如果要实例化特定模块的一个类，可以在类名称后面添加 "@模块名" 来指定，例如：
     *
     * @code
     * // 获得 HTML 模块 Helper_Render 类的一个实例
     * Q::getSingleton('Helper_Render@html');
     * @endcode
     *
     * 模块名是不区分大小写的，但建议使用全小写的模块名。
     *
     * @param string $class_name
     *   要获取的对象的类名字
     *
     * @return object
     *   返回对象实例
     */
    static final function getSingleton($class_name)
    {
        if (isset(self::$_objects[$class_name]))
        {
            return self::$_objects[$class_name];
        }
        self::loadClass($class_name);
        return self::register(new $class_name(), $class_name);
    }

    /**
     * 以特定名字在对象注册表中登记一个对象
     *
     * 开发者可以将一个对象登记到对象注册表中，以便在应用程序其他位置使用 Q::registry() 来查询该对象。
     * 登记时，如果没有为对象指定一个名字，则以对象的类名称作为登记名。
     *
     * @code
     * // 注册一个对象
     * Q::register(new MyObject(), 'MyObejct');
     * .....
     * // 稍后取出对象
     * $obj = Q::regitry('MyObject');
     * @endcode
     *
     * 当 $persistent 参数为 true 时，对象将被放入持久存储区。
     * 在下一次执行脚本时，可以通过 Q::registry() 取出放入持久存储区的对象，并且无需重新构造对象。
     *
     * 利用这个特性，开发者可以将一些需要大量构造时间的对象放入持久存储区，
     * 从而避免每一次执行脚本都去做对象构造操作。
     *
     * 使用哪一种持久化存储区来保存对象，由配置 object_persistent_provier 决定。
     * 该设置指定一个提供持久化服务的对象名。
     *
     * @code
     * if (!Q::isRegistered('ApplicationObject')) {
     *      Q::loadClass('Application');
     *      Q::registry(MyApp::instance(), 'ApplicationObject', true);
     * }
     * $app = Q::registry('ApplicationObject');
     * @endcode
     *
     * @param object $obj
     *   要登记的对象
     * @param string $name
     *   用什么名字登记
     * @param boolean $persistent
     *   是否将对象放入持久化存储区
     *
     * @return object
     */
    static final function register($obj, $name = null, $persistent = false)
    {
        if (! is_object($obj))
        {
            // LC_MSG: Type mismatch. $obj expected is object, actual is "%s".
            throw new QException(__('Type mismatch. $obj expected is object, actual is "%s".', gettype($obj)));
        }

        // TODO: 实现对 $persistent 参数的支持
        if (is_null($name))
        {
            $name = get_class($obj);
        }
        self::$_objects[$name] = $obj;
        return $obj;
    }

    /**
     * 查找指定名字的对象实例，如果指定名字的对象不存在则抛出异常
     *
     * @code
     * // 注册一个对象
     * Q::register(new MyObject(), 'MyObejct');
     * .....
     * // 稍后取出对象
     * $obj = Q::regitry('MyObject');
     * @endcode
     *
     * @param string $name
     *   要查找对象的名字
     *
     * @return object
     *   查找到的对象
     */
    static final function registry($name)
    {
        if (isset(self::$_objects[$name]))
        {
            return self::$_objects[$name];
        }
        // LC_MSG: No object is registered of name "%s".
        throw new QException(__('No object is registered of name "%s".', $name));
    }

    /**
     * 检查指定名字的对象是否已经注册
     *
     * @param string $name
     *   要检查的对象名字
     *
     * @return boolean
     *   对象是否已经登记
     */
    static final function isRegistered($name)
    {
        return isset(self::$_objects[$name]);
    }

    /* @} */

    /**
     * @addtogroup cache 缓存服务
     * @ingroup core
     *
     * QeePHP 提供了多样化的缓存服务，并且在框架核心提供了一个统一的接口，让开发者能够用一致的方式使用缓存服务。
     *
     * <ul>
     *   <li>Q::getCache() 尝试读取指定的缓存，成果返回缓存内容，失败返回 false；</li>
     *   <li>Q::setCache() 将内容写入指定的缓存；</li>
     *   <li>Q::removeCache() 删除指定的缓存数据。</li>
     * </ul>
     *
     * 这三个全局缓存方法，让开发者可以根据需要选择不同的缓存策略和缓存服务。
     *
     * @code
     * if (!($data = Q::getCache($cache_id))) {
     *     // 读取缓存失败，因此需要从数据库读取需要的数据
     *     $data = ... // 从数据库读取数据
     *     // 指定缓存策略，并将数据写入缓存
     *     $policy = array(
     *         'life_time' => 300, // 缓存数据失效时间是 5 分钟
     *         'serialize' => true, // 自动序列化和反序列化缓存数据
     *     );
     *     Q::setCache($cache_di, $data, $policy);
     * }
     *
     * // 使用 $data 中包含的数据
     * @endcode
     *
     * QeePHP 的缓存系统提供名为“缓存策略”的机制，让开发者可以为不同的缓存项目指定不同的缓存选项。
     * 并且在读取缓存时，无需再次指定缓存项目的缓存策略。
     *
     * 例如数据 A 的缓存失效时间是 5 分钟，而数据 B 的缓存失效时间是 10 分钟。
     * QeePHP 读取这两项缓存时，能够知道两项数据的失效时间不同，不需要开发者在读取缓存时再指定缓存的失效时间。
     *
     * 这个特性对于大量使用缓存的场合非常方便，避免了由于忘记指定合适选项，导致缓存效果和预期不符的情况。
     *
     * @{
     */

    /**
     * 读取指定的缓存内容，如果内容不存在或已经失效，则返回 false
     *
     * 在操作缓存数据时，必须指定缓存的 ID。每一个缓存内容都有一个唯一的 ID。
     * 例如数据 A 的缓存 ID 是 data-a，而数据 B 的缓存 ID 是 data-b。
     *
     * 在大量使用缓存时，应该采用一定的规则来确定缓存 ID。下面是一个推荐的方案：
     *
     * <ul>
     *   <li>首先按照缓存数据的性质确定前缀，例如 page、db 等；</li>
     *   <li>然后按照数据的唯一索引来确定后缀，并和前缀一起组合成完整的缓存 ID。</li>
     * </ul>
     *
     * 按照这个规则，缓存 ID 看上去类似 page.news.1、db.members.userid。
     *
     * Q::getCache() 可以指定 $policy 参数来覆盖缓存数据本身带有的策略。
     * 具体哪些策略可以使用，请参考不同缓存服务的文档。
     *
     * $backend_class 用于指定要使用的缓存服务对象类名称。例如 QCache_File、QCache_APC 等。
     *
     * @param string $id
     *   缓存的 ID
     * @param array $policy
     *   缓存策略
     * @param string $backend_class
     *   要使用的缓存服务
     *
     * @return mixed
     *   成功返回缓存内容，失败返回 false
     */
    static final function getCache($id, array $policy = null, $backend_class = null)
    {
        static $obj = null;

        if (is_null($backend_class))
        {
            if (is_null($obj))
            {
                $obj = self::getSingleton(self::getIni('runtime_cache_backend'));
            }
            return $obj->get($id, $policy);
        }
        else
        {
            $cache = self::getSingleton($backend_class);
            return $cache->get($id, $policy);
        }
    }

    /**
     * 将变量内容写入缓存，失败抛出异常
     *
     * $data 参数指定要缓存的内容。如果 $data 参数不是一个字符串，则必须将缓存策略 serialize 设置为 true。
     * $policy 参数指定了内容的缓存策略，如果没有提供该参数，则使用缓存服务的默认策略。
     * 其他参数同 Q::getCache()。
     *
     * @param string $id
     *   缓存的 ID
     * @param mixed $data
     *   要缓存的数据
     * @param array $policy
     *   缓存策略
     * @param string $backend_class
     *   要使用的缓存服务
     */
    static final function setCache($id, $data, array $policy = null, $backend_class = null)
    {
        static $obj = null;

        if (is_null($backend_class))
        {
            if (is_null($obj))
            {
                $obj = self::getSingleton(self::getIni('runtime_cache_backend'));
            }
            $obj->set($id, $data, $policy);
        }
        else
        {
            $cache = self::getSingleton($backend_class);
            $cache->set($id, $data, $policy);
        }
    }

    /**
     * 删除指定的缓存内容
     *
     * 通常，失效的缓存数据无需清理。但有时候，希望在某些操作完成后立即清除缓存。
     * 例如更新数据库记录后，希望删除该记录的缓存文件，以便在下一次读取缓存时重新生成缓存文件。
     *
     * @param string $id
     *   缓存的 ID
     * @param array $policy
     *   缓存策略
     * @param string $backend_class
     *   要使用的缓存服务
     */
    static final function removeCache($id, array $policy = null, $backend_class = null)
    {
        static $obj = null;

        if (is_null($backend_class))
        {
            if (is_null($obj))
            {
                $obj = self::getSingleton(self::getIni('runtime_cache_backend'));
            }
            $obj->remove($id, $policy);
        }
        else
        {
            $cache = self::getSingleton($backend_class);
            $cache->remove($id, $policy);
        }
    }

    /* @} */

    /**
     * @addtogroup common 公共服务
     * @ingroup core
     *
     * 公共服务由一些非常基本和常用的方法组成。
     *
     * 包括：
     *
     * <ul>
     *   <li>Q::normalize() 对字符串或数组进行格式化，返回格式化后的数组；</li>
     * </ul>
     *
     * @{
     */

    /**
     * 对字符串或数组进行格式化，返回格式化后的数组
     *
     * $input 参数如果是字符串，则首先以“,”为分隔符，将字符串转换为一个数组。
     * 接下来对数组中每一个项目使用 trim() 方法去掉首位的空白字符。最后过滤掉空字符串项目。
     *
     * 该方法的主要用途是将诸如：“item1, item2, item3” 这样的字符串转换为数组。
     *
     * @code
     * $input = 'item1, item2, item3';
     * $output = Q::normalize($input);
     * // $output 现在是一个数组，结果如下：
     * // $output = array(
     * //   'item1',
     * //   'item2',
     * //   'item3',
     * // );
     *
     * $input = 'item1|item2|item3';
     * // 指定使用什么字符作为分割符
     * $output = Q::normalize($input, '|');
     * @endcode
     *
     * @param array|string $input
     *   要格式化的字符串或数组
     * @param string $delimiter
     *   按照什么字符进行分割
     *
     * @return array
     *   格式化结果
     */
    static final function normalize($input, $delimiter = ',')
    {
        if (!is_array($input))
        {
            $input = explode($delimiter, $input);
        }
        $input = array_map('trim', $input);
        return array_filter($input, 'strlen');
    }

    /* @} */

}

/**
 * @addtogroup internal 内部服务
 * @ingroup core
 *
 * 仅用于 QeePHP 内部的方法。
 *
 * @{
 */

/**
 * QeePHP 内部使用的多语言翻译函数
 *
 * 应用程序应该使用 QTranslate 组件实现多语言界面。
 *
 * @return $msg
 */
function __()
{
    $args = func_get_args();
    $msg = array_shift($args);
    $language = strtolower(Q::getIni('error_language'));
    $messages = Q::loadFile('lc_messages.php', Q_DIR . '/_lang/' . $language, false);
    if (isset($messages[$msg]))
    {
        $msg = $messages[$msg];
    }
    array_unshift($args, $msg);
    return call_user_func_array('sprintf', $args);
}

/* @} */

/* @} */

