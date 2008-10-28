<?php
// $Id$

/**
 * @file
 * 定义 Behavior_AclUser 类
 *
 * @ingroup behavior
 *
 * @{
 */

/**
 * Behavior_AclUser 实现基于 ACL 的用户访问控制
 */
class Model_Behavior_AclUser extends QDB_ActiveRecord_Behavior_Abstract
{
    /**
     * 插件的设置信息
     *
     * @var array
     */
    protected $_settings = array
    (
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
     * 保存状态
     *
     * @var array
     */
    protected $_saved_state = array();

    /**
     * 绑定行为插件
     */
    function bind()
    {
        $this->_addEventHandler(self::BEFORE_CREATE, array($this, '_before_create'));
        $this->_addDynamicMethod('encodeText',       array($this, 'encodeText'));
        $this->_addDynamicMethod('checkPassword',    array($this, 'checkPassword'));
        $this->_addDynamicMethod('updateLogin',      array($this, 'updateLogin'));
        $this->_addDynamicMethod('getAclData',       array($this, 'getAclData'));
        $this->_addDynamicMethod('getAclRoles',      array($this, 'getAclRoles'));

        $this->_addStaticMethod('changePassword',    array($this, 'changePassword'));
        $this->_addStaticMethod('validateLogin',     array($this, 'validateLogin'));

        $pn = $this->_settings['password_prop'];
        if (!isset($this->_meta->update_reject[$pn]))
        {
            $this->_saved_state['password_prop'] = null;
        }
        else
        {
            $this->_saved_state['password_prop'] = $this->_meta->update_reject[$pn];
        }
        //$this->_meta->update_reject[$this->_settings['password_prop']] = true;
    }

    /**
     * 撤销插件绑定
     */
    function unbind()
    {
    	parent::unbind();
        $pn = $this->_settings['password_prop'];
        if (is_null($this->_saved_state['password_prop']))
        {
            unset($this->_meta->update_reject[$pn]);
        }
        else
        {
            $this->_meta->update_reject[$pn] = $this->_saved_state['password_prop'];
        }
    }

    /**
     * 验证用户登录并返回用户对象，如果成功更新用户登录信息，否则返回一个空对象
     *
     * @param string $username
     * @param string $password
     * @param boolean $update_login
     *
     * @return QDB_ActiveRecord_Abstract
     */
    function validateLogin($username, $password, $update_login = true)
    {
        $pn = $this->_settings['username_prop'];
        $member = $this->_meta->find(array($pn => $username))->query();
        if (! $member->id())
        {
            // 没找到用户，直接返回空对象
            return $member;
        }
        elseif (! $member->checkPassword($password))
        {
            // 密码不正确，返回一个空对象
            return $this->_meta->newObject();
        }

        if ($update_login)
        {
            // 用户名和密码验证通过，更新登录信息
            $member->updateLogin();
        }
        return $member;
    }

    /**
     * 在新建的 ActiveRecord 保存到数据库前，加密密码并填充 register_ip 属性
     *
     * @param QDB_ActiveRecord_Abstract $obj
     */
    function _before_create(QDB_ActiveRecord_Abstract $obj)
    {
        if ($this->_settings['unique_username'] || $this->_settings['unique_email'])
        {
            $select = $this->_meta->find();

            if ($this->_settings['unique_username'])
            {
                // 确保没有同名用户
                $pn = $this->_settings['username_prop'];
                $username = $obj->{$pn};
                $select->where(array($pn => $username))->count($pn, "duplicate_username_count");
            }

            if ($this->_settings['unique_email'])
            {
                // 确保没有重复的电子邮件地址
                $pn = $this->_settings['email_prop'];
                $email = $obj->{$pn};
                $select->where(array($pn => $email))->count($pn, "duplicate_email_count");
            }

            $row = $select->query();

            if (! empty($row['duplicate_username_count']) && $row['duplicate_username_count'] > 0)
            {
                // 找到同名用户
                throw new QACL_User_Exception(sprintf($this->_settings['err_duplicate_username'], $username));
            }

            if (! empty($row['duplicate_email_count']) && $row['duplicate_email_count'] > 0)
            {
                // 找到相同的 EMAIL
                throw new QACL_User_Exception(sprintf($this->_settings['err_duplicate_email'], $email));
            }
        }

        // 加密密码
        $pn = $this->_settings['password_prop'];
        $obj->changePropForce($pn, $this->_encodePassword($obj->{$pn}));

        // 是否记录注册时的 IP
        $pn = $this->_settings['register_ip_prop'];
        if ($pn)
        {
            if ($this->_meta->props[$pn]['ptype'] == 'i')
            {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? ip2long($_SERVER['REMOTE_ADDR']) : 0;
            }
            else
            {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
            }
            $obj->changePropForce($pn, $ip);
        }
    }

    /**
     * 获得加密后的密码
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param string $password
     *
     * @return string
     */
    function encodeText(QDB_ActiveRecord_Abstract $obj, $password)
    {
        return $this->_encodePassword($password);
    }

    /**
     * 检查密码是否符合要求
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param string $password
     *
     * @return boolean
     */
    function checkPassword(QDB_ActiveRecord_Abstract $obj, $password)
    {
        $pn = $this->_settings['password_prop'];
        $encoded = rtrim($obj->{$pn});
        switch ($this->_settings['encode_type'])
        {
        case 'md5':
            return md5($password) == $encoded;
        case 'crypt':
        default:
            return crypt($password, $encoded) == $encoded;
        }
    }

    /**
     * 修改用户的密码
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param string $old_password
     * @param string $new_password
     */
    function changePassword(QDB_ActiveRecord_Abstract $obj, $old_password, $new_password, $ignoreoldpw=false )
    {
        if($ignoreoldpw)
        {
            $pn = $this->_settings['password_prop'];
            $obj->changePropForce($pn, $this->_encodePassword($new_password));
            $obj->save(0, 'update');
        }
        else
        {
            if ($obj->checkPassword($old_password))
            {
                $pn = $this->_settings['password_prop'];
                $obj->changePropForce($pn, $this->_encodePassword($new_password));
                $obj->save(0, 'update');
            }
            else
            {
                // LC_MSG: Change user password failed.
                throw new QACL_User_Exception('Change user password failed.');
            }
        }
    }

    /**
     * 更新用户登录信息
     *
     * @param QDB_ActiveRecord_Abstract $obj
     */
    function updateLogin(QDB_ActiveRecord_Abstract $obj)
    {
        $changed = false;

        $pn = $this->_settings['login_count_prop'];
        if ($pn)
        {
            $obj->changePropForce($pn, $obj->{$pn} + 1);
            $changed = true;
        }

        $pn = $this->_settings['login_at_prop'];
        if ($pn)
        {
            if ($this->_meta->props[$pn]['ptype'] == 'i')
            {
                $obj->changePropForce($pn, time());
            }
            else
            {
                $obj->changePropForce($pn, $this->_meta->table->getConn()->dbTimestamp(time()));
            }
            $changed = true;
        }

        $pn = $this->_settings['login_ip_prop'];
        if ($pn)
        {
            if ($this->_meta->props[$pn]['ptype'] == 'i')
            {
                $obj->changePropForce($pn, ! empty($_SERVER['REMOTE_ADDR']) ? ip2long($_SERVER['REMOTE_ADDR']) : 0);
            }
            else
            {
                $obj->changePropForce($pn, ! empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            }
            $changed = true;
        }

        if ($changed)
        {
            $obj->save(0, 'update');
        }
    }

    /**
     * 获得用户的 ACL 数据
     *
     * @param QDB_ActiveRecord_Abstract $obj
     * @param string $acldata_props
     *
     * @return array
     */
    function getAclData(QDB_ActiveRecord_Abstract $obj, $acldata_props = null)
    {
        if (is_null($acldata_props))
        {
            $acldata_props = $this->_settings['acldata_props'];
        }
        $acldata_props = Q::normalize($acldata_props);
        $data = array();
        foreach ($acldata_props as $pn)
        {
            $data[$pn] = $obj->{$pn};
        }
        $data['id'] = $obj->id();
        return $data;
    }

    /**
     * 获得包含用户所有角色名的数组
     *
     * @param QDB_ActiveRecord_Abstract $obj
     *
     * @return array
     */
    function getAclRoles(QDB_ActiveRecord_Abstract $obj)
    {
        $roles_prop = $this->_settings['roles_prop'];
        $rolename_prop = $this->_settings['rolename_prop'];
        $roles = array();

        foreach ($obj->{$roles_prop} as $role)
        {
            $roles[] = $role->{$rolename_prop};
        }
        return $roles;
    }

    /**
     * 获得加密后的密码
     *
     * @param string $password
     *
     * @return string
     */
    protected function _encodePassword($password)
    {
        switch ($this->_settings['encode_type'])
        {
        case 'md5':
            return md5($password);
        case 'crypt':
        default:
        }
        return crypt($password);
    }

}

/* @} */
