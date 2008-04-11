# <?php die(); ?>

#############################
# 运行时环境
#############################

# 是否自动打开 session
runtime_session_start:      true

# QeePHP 内部及 cache 系列函数使用的缓存目录
runtime_cache_dir:          %ROOT_DIR%/temp/runtime_cache

# 默认使用的缓存服务
runtime_cache_backend:      QCache_File

# 是否自动输出 Content-Type: text/html; charset=%i18n_response_charset%
runtime_response_header:    true


#############################
# 调度器和访问控制
#############################

# url 参数的传递模式，可以是标准、PATHINFO、URL 重写等模式
dispatcher_url_mode:        standard

# 指示当没有为控制器提供 ACT 时，要使用的默认 ACT
default_act:
    allow: acl_everyone

# 指示 ACL 组件用什么键名在 session 中保存用户数据
acl_session_key:            acl_%APP_NAME%_userdata


#############################
# 视图、国际化和本地化
#############################

# 要使用的模板引擎，'PHP' 表示使用 PHP 语言本身作模板引擎
view_adapter:               QView_Adapter_Gingko

# 指示 QeePHP 应用程序内部处理数据和输出内容要使用的编码
i18n_response_charset:      utf-8

# 指示是否启用多语言支持
i18n_multi_languages:       false

# 默认的时区设置
l10n_default_timezone:      Asia/Shanghai


#############################
# 应用程序设置
#############################

# 在这里添加应用程序需要的设置
