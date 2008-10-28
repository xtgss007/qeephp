<?php
// $Id$


/**
 * @file
 * QeePHP 框架运行时设置的默认值
 *
 * @ingroup core
 *
 * @{
 */

return array
(
    // {{{ 运行环境相关


    /**
     * 要使用的 session 服务
     */
    'runtime_session_provider' => null,

    /**
     * 是否自动打开 session
     */
    'runtime_session_start' => false,

    /**
     * QeePHP 内部及 cache 系列函数使用的缓存目录
     * 应用程序必须设置该选项才能使用 cache 功能。
     */
    'runtime_cache_dir' => null,

    /**
     * 默认使用的缓存服务
     */
    'runtime_cache_backend' => 'QCache_File',

    /**
     * 是否自动输出 Content-Type: text/html; charset=%i18n_response_charset%
     */
    'runtime_response_header' => true,

    // }}}


    // {{{ 错误处理相关


    /**
     * 指示是否显示错误信息（有一定安全风险）
     *
     * 在生产环境建议关闭此功能。
     */
    'error_display' => true,

    /**
     * 指示是否显示友好的错误信息（有安全风险）
     *
     * 在生产环境必须关闭此功能。
     */
    'error_display_friendly' => true,

    /**
     * 指示是否在错误信息中显示出错位置的源代码（有安全风险）
     *
     * 在生产环境必须关闭此功能。
     */
    'error_display_source' => true,

    /**
     * 错误信息的默认语言
     */
    'error_language' => 'zh_cn',

    /**
     * 是否允许 QDbug::assert()
     */
    'assert_enabled' => true,

    /**
     * 断言为 false 时，是否产生一个警告信息
     */
    'assert_warning' => true,

    /**
     * 断言为 false 时，是否抛出 QDebug_Assert_Failed 异常
     */
    'assert_exception' => false,

    // }}}


    // {{{ 调度器相关


    /**
     * 指示控制器的 url 参数名和默认控制器名
     *
     * 控制器名字只能是a-z字母和0-9数字，以及“_”下划线。
     */
    'dispatcher_controller_accessor' => 'controller',
    'dispatcher_default_controller' => 'default',

    /**
     * 指示 动作方法的 url 参数名和默认 动作方法名
     */
    'dispatcher_action_accessor' => 'action',
    'dispatcher_default_action' => 'index',

    /**
     * 指示命名空间的 url 参数名和默认命名空间
     */
    'dispatcher_namespace_accessor' => 'namespace',

    /**
     * 指示模块的 url 参数名和默认模块名
     */
    'dispatcher_module_accessor' => 'module',

    /**
     * 当无权访问请求的动作时要调用的处理例程
     */
    'dispatcher_on_access_denied' => null,

    /**
     * 当请求的动作不存在时要调用的处理例程
     */
    'dispatcher_on_action_not_found' => null,

    /**
     * url 参数的传递模式，可以是标准、PATHINFO、URL 重写等模式
     */
    'dispatcher_url_mode' => 'standard',

    // }}}


    // {{{ 访问控制相关


    /**
     * 指示当没有为控制器提供 ACT 时，要使用的默认 ACT
     */
    'acl_default_act' => array('allow' => 'ACL_EVERYONE'),

    /**
     * 全局 ACT，当没有指定 ACT 时则从全局 ACT 中查找指定控制器的 ACT
     */
    'acl_global_act' => null,

    /**
     * 指示 ACL 组件用什么键名在 session 中保存用户数据
     *
     * 如果在一个域名下同时运行多个应用程序，
     * 请务必为每一个应用程序使用自己独一无二的键名
     */
    'acl_session_key' => 'acl_userdata',

    /**
     * 指示 ACL 组件用什么键名在 session 中保存用户角色信息
     */
    'acl_roles_key' => 'acl_roles',

    // }}}


    // {{{ 数据库相关

    /**
     * 数据库查询是否写入日志
     */
    'db_log_enabled' => false,

    /**
     * 数据库连接设置
     */
    'db_default_dsn' => null,

    /**
     * 数据表元数据缓存时间（秒），如果 db_meta_cached 设置为 false，则不会缓存数据表元数据
     */
    'db_meta_lifetime' => 0,

    /**
     * 指示是否缓存数据表的元数据
     */
    'db_meta_cached' => false,

    /**
     * 缓存元数据使用的缓存服务
     */
    'db_meta_cache_backend' => 'QCache_File',

    // }}}


    // {{{ View 相关


    /**
     * 要使用的模板引擎
     */
    'view_adapter' => 'QView_Adapter_Gingko',

    /**
     * 模板引擎要使用的配置信息
     */
    'view_config' => array
    (
        /**
         * 视图文件所在目录，如果不指定则由框架自行决定
         */
        'view_dir' => null,

        /**
         * 布局视图文件所在目录，如果不指定则由框架自行决定
         */
        'view_layouts_dir' => null,

        /**
         * 是否使用平面的目录结构布局
         */
        'flat_dir' => false,
    ),

    // }}}


    // {{{ 国际化（I18N）和本地化（L10N）相关


    /**
     * 指示 QeePHP 应用程序内部处理数据和输出内容要使用的编码
     */
    'i18n_response_charset' => 'utf-8',

    /**
     * 指示是否启用多语言支持
     */
    'i18n_multi_languages' => false,

    /**
     * 默认的时区设置
     */
    'l10n_default_timezone' => 'Asia/ChongQing',

    // }}}


    // {{{ 日志和错误处理


    /**
     * 指示是否允许记录日志
     */
    'log_enabled' => true,

    /**
     * 指示记录哪些优先级的日志（不符合条件的会直接过滤）
     */
    'log_priorities' => 'NOTICE, DEBUG, WARN, ERR, INFO, CRIT, ALERT',

    /**
     * 是否使用延迟的日志写入
     *
     * 允许日志延迟写入可以显著减少IO操作，但可能在应用程序出现不可捕获错误时丢失未写入的日志。
     */
    'log_cached' => true,

    /**
     * 日志缓存块大小（单位KB）
     *
     * 更小的缓存块可以节约内存，但写入日志的次数更频繁，性能更低。
     */
    'log_cache_chunk_size' => 512,  // 512KB

    /**
     * 保存日志文件的目录
     */
    'log_writer_dir' => null,

    /**
     * 日志文件的文件名
     */
    'log_writer_filename' => 'access.log'
);

// }}}

/**
 * @}
 */

