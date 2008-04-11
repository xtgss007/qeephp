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
 * 定义 QSession_Provider_Cookie 类
 *
 * @package core
 * @version $Id$
 */

/**
 * QSession_Provider_Cookie 用 cookie 存储 session 数据
 *
 * @package core
 */
class QSession_Provider_Cookie
{
    /**
     * 以什么键名在 cookie 中保存 session 数据
     *
     * @var string
     */
    protected $cookie_name;

    /**
     * 构造函数
     */
    function __construct()
    {
        $this->cookie_name = Q::getIni('session_cookie_name');
        QLog::log(__CLASS__ . ' initialized.', QLog::DEBUG);
    }

    function open($path, $name)
    {
        return true;
    }

    function close()
    {
        return true;
    }

    function read($id)
    {
        $data = isset($_COOKIE[$this->cookie_name]) ? $_COOKIE[$this->cookie_name] : null;
        if (empty($data)) { return ''; }

        return $this->decode($data);
    }

    function write($id, $data)
    {
        $data = $this->encode($data);
        if (strlen($data) > 4048) {
            // LC_MSG: Session 数据不能超过 4KB. 放弃写入 session.
            throw new QSession_Exception(__('Session 数据不能超过 4KB. 放弃写入 session.'));
        }
        $_COOKIE[$this->cookie_name] = $data;
    }

    function destroy($id)
    {
        unset($_COOKIE[$this->cookie_name]);
    }

    function regenerate()
    {
        session_regenerate_id(true);
        return session_id();
    }

    function gc($maxlifetime)
    {
        return true;
    }

}
