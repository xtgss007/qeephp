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
 * 定义 Behavior_Acluser 类
 *
 * @package database
 * @version $Id$
 */

/**
 * Behavior_Acluser 类实现了基于 ACL 的用户访问控制
 *
 * @package database
 */
class Behavior_Acluser extends QDB_ActiveRecord_Behavior_Abstract
{
    /**
     * 插件的设置信息
     *
     * @var array
     */
    protected $_settings = array(
        // 密码加密方式
        'encode_type'       => 'crypt',
        // 用户名属性名
        'username_prop'     => 'username',
        // 密码属性名
        'password_prop'     => 'password',
        // 电子邮件属性名
        'email_prop'        => 'email',
        // 账户注册 IP 属性名
        'register_ip_prop'  => 'register_ip',
        // 角色属性名
        'roles_prop'        => 'roles',
        // 角色名属性名
        'rolename_prop'     => 'name',
        // getAclData() 方法要获取的属性值
        'acldata_props'     => 'username',
        // 累计登录次数属性名，例如 login_count
        'login_count_prop'  => null,
        // 最后登录日期属性名，例如 login_at
        'login_at_prop'     => null,
        // 最后登录 IP 属性名，例如 login_ip
        'login_ip_prop'     => null,

        // 是否检查用户名的唯一性
        'unique_username'   => true,
        // 是否检查电子邮件的唯一性
        'unique_email'      => false,
        // 用户名重复时的错误信息
        'err_duplicate_username' => 'Duplicate username "%s".',
        // 电子邮件重复时的错误信息
        'err_duplicate_email'    => 'Duplicate email "%s".',
    );

    /**
     * 绑定行为插件
     */
    function bind()
    {
        $this->_meta->addEventHandler(self::before_create, array($this, '_before_create'));
        $this->_meta->addDynamicMethod('encodePassword',   array($this, 'encodePassword'));
        $this->_meta->addDynamicMethod('checkPassword',    array($this, 'checkPassword'));
        $this->_meta->addDynamicMethod('changePassword',   array($this, 'changePassword'));
        $this->_meta->addDynamicMethod('updateLogin',      array($this, 'updateLogin'));
        $this->_meta->addDynamicMethod('getAclData',       array($this, 'getAclData'));
        $this->_meta->addDynamicMethod('getAclRoles',      array($this, 'getAclRoles'));
        $this->_meta->update_reject[$this->_settings['password_prop']] = true;
    }

    /**
     * 在新建的 ActiveRecord 保存到数据库前，加密密码并填充 register_ip 属性
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function _before_create(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        $table = $this->_meta->table;
        if ($this->_settings['unique_username']) {
            $pn = $this->_settings['username_prop'];
            $username = $obj->{$pn};
            $row = $table->find(array($pn => $username))->count()->query();
            if (!empty($row) && $row['row_count'] > 0) {
                // 找到同名用户
                throw new QACL_User_Exception(sprintf($this->_settings['err_duplicate_username'], $username));
            }
        }

        if ($this->_settings['unique_email']) {
            $pn = $this->_settings['email_prop'];
            if (isset($this->_meta->props[$pn])) {
                $email = $obj->{$pn};
                $row = $table->find(array($pn => $email))->count()->query();
                if (!empty($row) && $row['row_count'] > 0) {
                    // 找到相同的 EMAIL
                    throw new QACL_User_Exception(sprintf($this->_settings['err_duplicate_email'], $email));
                }
            }
        }

        $pn = $this->_settings['password_prop'];
        $props[$pn] = $this->encodePassword($obj->{$pn});

        $pn = $this->_settings['register_ip_prop'];
        if (isset($this->_meta->props[$pn])) {
            $props[$pn] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'none';
        }
    }

    /**
     * 对密码字段加密
     *
     * @param string $password
     *
     * @return string;
     */
    function encodePassword($password)
    {
        switch ($this->_settings['encode_type']) {
        case 'md5':
            return md5($password);
        case 'crypt':
        default:
        }
        return crypt($password);
    }

    /**
     * 检查密码是否符合要求
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param string $password
     *
     * @return boolean
     */
    function checkPassword(QDB_ActiveRecord_Abstract $obj, array $props, $password)
    {
        $pn = $this->_settings['password_prop'];
        $encoded = $obj->{$pn};
        switch ($this->_settings['encode_type']) {
        case 'md5':
            return md5($password) == $encoded;
        case 'crypt':
        default:
        }
        return crypt($password, $encoded) == rtrim($encoded);
    }

    /**
     * 修改用户的密码
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param string $old_password
     * @param string $new_password
     */
    function changePassword(QDB_ActiveRecord_Abstract $obj, array & $props, $old_password, $new_password)
    {
        if ($obj->checkPassword($old_password)) {
            $pn = $this->_settings['password_prop'];
            $new_password = $this->encodePassword($new_password);
            $row = array(
                $obj->idname() => $obj->id(),
                $this->_meta->prop2fields[$pn] => $new_password,
            );
            $this->_meta->table->update($row, 0);
            $props[$pn] = $new_password;
        } else {
            // LC_MSG: Change user password failed.
            throw new QACL_User_Exception(__('Change user password failed.'));
        }
    }

    /**
     * 更新用户登录信息
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     */
    function updateLogin(QDB_ActiveRecord_Abstract $obj, array & $props)
    {
        $row = array();
        $pn = $this->_settings['login_count_prop'];
        if (!empty($pn) && isset($this->_meta->props[$pn])) {
            $fn = $this->_meta->prop2fields[$pn];
            $row[$fn] = $obj->{$pn} + 1;
            $props[$pn] = $row[$fn];
        }

        $pn = $this->_settings['login_at_prop'];
        if (!empty($pn) && isset($this->_meta->props[$pn])) {
            $fn = $this->_meta->prop2fields[$pn];
            if (!empty($this->_meta->table_meta[$fn])) {
                if ($this->_meta->table_meta[$fn]['ptype'] == 'i') {
                    $time = time();
                } else {
                    $time = $this->_meta->table->conn->dbTimestamp(time());
                }
                $props[$pn] = $time;
                $row[$fn] = $time;
            }
        }

        $pn = $this->_settings['login_ip_prop'];
        if (!empty($pn) && isset($this->_meta->props[$pn])) {
            $ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $props[$pn] = $row[$this->_meta->prop2fields[$pn]] = $ip;
        }

        if (!empty($row)) {
            $this->_meta->table->updateWhere($row, array($obj->idname() => $obj->id()));
        }
    }

    /**
     * 获得用户的 ACL 数据
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     * @param string $acldata_props
     *
     * @return array
     */
    function getAclData(QDB_ActiveRecord_Abstract $obj, array $props, $acldata_props = null)
    {
        if (is_null($acldata_props)) {
            $acldata_props = $this->_settings['acldata_props'];
        }
        $acldata_props = Q::normalize($acldata_props);
        $data = array();
        foreach ($acldata_props as $pn) {
            if (isset($this->_meta->props[$pn])) {
                $data[$pn] = $obj->{$pn};
            }
        }
        $data['id'] = $obj->id();
        return $data;
    }

    /**
     * 获得包含用户所有角色名的数组
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param array $props
     *
     * @return array
     */
    function getAclRoles(QDB_ActiveRecord_Abstract $obj, array $props)
    {
        $roles_prop = $this->_settings['roles_prop'];
        $rolename_prop = $this->_settings['rolename_prop'];
        $roles = array();
        if (!isset($obj->{$roles_prop})) {
            return array();
        }

        foreach ($obj->{$roles_prop} as $role) {
            $roles[] = $role->{$rolename_prop};
        }
        return $roles;
    }
}
