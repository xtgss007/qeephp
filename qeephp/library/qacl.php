<?php
// $Id$

/**
 * 定义 QACL 类
 */

/**
 * QACL 提供基于角色的权限检查服务
 */
class QACL
{
    /**
     * 预定义角色及相关常量
     */
    const ACL_EVERYONE = 'acl_everyone';
    const ACL_NULL     = 'acl_null';
    const ACL_NO_ROLE  = 'acl_no_role';
    const ACL_HAS_ROLE = 'acl_has_role';
    const ALL_ACTIONS     = 'all_actions';
    const ALL_CONTROLLERS = 'all_controllers';

    /**
     * 检查访问控制表是否允许指定的角色访问
     *
     * @param array $roles
     * @param array $act
     *
     * @return boolean
     */
    function check($roles, $act)
    {
        $roles = array_map('strtolower', $roles);
        if ($act['allow'] == self::ACL_EVERYONE)
        {
            // 如果 allow 允许所有角色，deny 没有设置，则检查通过
            if ($act['deny'] == self::ACL_NULL)
            {
                return true;
            }

            // 如果 deny 为 acl_no_role，则只要用户具有角色就检查通过
            if ($act['deny'] == self::ACL_NO_ROLE)
            {
                if (empty($roles))
                {
                    return false;
                }
                return true;
            }

            // 如果 deny 为 acl_has_role，则只有用户没有角色信息时才检查通过
            if ($act['deny'] == self::ACL_HAS_ROLE)
            {
                if (empty($roles))
                {
                    return true;
                }
                return false;
            }

            // 如果 deny 也为 acl_everyone，则表示 ACT 出现了冲突
            if ($act['deny'] == self::ACL_EVERYONE)
            {
                throw new QACL_Exception('Invalid ACT');
            }

            // 只有 deny 中没有用户的角色信息，则检查通过
            foreach ($roles as $role)
            {
                if (in_array($role, $act['deny'], true))
                {
                    return false;
                }
            }
            return true;
        }

        do {
            // 如果 allow 要求用户具有角色，但用户没有角色时直接不通过检查
            if ($act['allow'] == self::ACL_HAS_ROLE)
            {
                if (!empty($roles))
                {
                    break;
                }
                return false;
            }

            // 如果 allow 要求用户没有角色，但用户有角色时直接不通过检查
            if ($act['allow'] == self::ACL_NO_ROLE)
            {
                if (empty($roles))
                {
                    break;
                }
                return false;
            }

            if ($act['allow'] != self::ACL_NULL)
            {
                // 如果 allow 要求用户具有特定角色，则进行检查
                $passed = false;
                foreach ($roles as $role)
                {
                    if (in_array($role, $act['allow'], true))
                    {
                        $passed = true;
                        break;
                    }
                }
                if (!$passed)
                {
                    return false;
                }
            }
        } while (false);

        // 如果 deny 没有设置，则检查通过
        if ($act['deny'] == self::ACL_NULL)
        {
            return true;
        }

        // 如果 deny 为 acl_no_role，则只要用户具有角色就检查通过
        if ($act['deny'] == self::ACL_NO_ROLE)
        {
            if (empty($roles))
            {
                return false;
            }
            return true;
        }
        // 如果 deny 为 acl_has_role，则只有用户没有角色信息时才检查通过
        if ($act['deny'] == self::ACL_HAS_ROLE)
        {
            if (empty($roles))
            {
                return true;
            }
            return false;
        }

        // 如果 deny 为 acl_everyone，则检查失败
        if ($act['deny'] == self::ACL_EVERYONE)
        {
            return false;
        }

        // 只有 deny 中没有用户的角色信息，则检查通过
        foreach ($roles as $role)
        {
            if (in_array($role, $act['deny'], true))
            {
                return false;
            }
        }
        return true;
    }

    /**
     * 对原始 ACT 进行分析和整理，返回整理结果
     *
     * @param array $act
     *
     * @return array
     */
    function formatACT($act)
    {
        $act = array_change_key_case($act, CASE_LOWER);
        $ret = array();
        $arr = array('allow', 'deny');
        foreach ($arr as $key)
        {
            do {
                if (!isset($act[$key]))
                {
                    $value = self::ACL_NULL;
                    break;
                }

                $act[$key] = strtolower($act[$key]);
                if ($act[$key] == self::ACL_EVERYONE || $act[$key] == self::ACL_HAS_ROLE
                    || $act[$key] == self::ACL_NO_ROLE || $act[$key] == self::ACL_NULL)
                {
                    $value = $act[$key];
                    break;
                }

                $value = explode(',', $act[$key]);
                $value = array_filter(array_map('trim', $value), 'trim');

                if (empty($value))
                {
                    $value = self::ACL_NULL;
                }
            } while (false);
            $ret[$key] = $value;
        }

        return $ret;
    }
}

