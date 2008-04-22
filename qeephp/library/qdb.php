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
 * 定义 QDB 类
 *
 * @package database
 * @version $Id: qdb.php 955 2008-03-16 23:52:44Z dualface $
 */

/**
 * QDB 类提供了管理数据库驱动的接口
 *
 * @package database
 */
abstract class QDB
{
    /**
     * 参数占位符类型
     */
    const PARAM_QM          = '?'; // 问号作为参数占位符
    const PARAM_CL_NAMED    = ':'; // 冒号开始的命名参数
    const PARAM_DL_SEQUENCE = '$'; // $符号开始的序列
    const PARAM_AT_NAMED    = '@'; // @开始的命名参数

    /**
     * 可用的查询结果集返回形式
     */
    const FETCH_MODE_ARRAY  = 1; // 返回的每一个记录就是一个索引数组
    const FETCH_MODE_ASSOC  = 2; // 返回的每一个记录就是一个以字段名作为键名的数组

    /**
     * 定义四种关联关系
     */
    const HAS_ONE       = 'has_one';
    const HAS_MANY      = 'has_many';
    const BELONGS_TO    = 'belongs_to';
    const MANY_TO_MANY  = 'many_to_many';

    /**
     * 开发者必须通过该方法获得数据库访问对象实例
     *
     * @param string $dsn_name
     *
     * @return QDB_Adapter_Abstract
     */
    static function getConn($dsn_name = null)
    {
        $default = empty($dsn_name);
        if ($default && Q::isRegistered('dbo_default')) {
            return Q::registry('dbo_default');
        }

        if (empty($dsn_name)) {
            $dsn = Q::getIni('db_dsn_pool/default');
        } else {
            $dsn = Q::getIni('db_dsn_pool/' . $dsn_name);
        }
        if (empty($dsn)) {
            // LC_MSG: Invalid DSN.
            throw new QException(__('Invalid DSN.'));
        }

        $dbtype = $dsn['driver'];
        $objid = "dbo_{$dbtype}_" .  md5(serialize($dsn));
        if (Q::isRegistered($objid)) {
            return Q::registry($objid);
        }

        $class_name = 'QDB_Adapter_' . ucfirst($dbtype);
        Q::loadClass($class_name);
        $dbo = new $class_name($dsn, $objid);
        Q::register($dbo, $objid);
        if ($default) {
            Q::register($dbo, 'dbo_default');
        }
        return $dbo;
    }

    /**
     * 将字符串形式的 DSN 转换为数组
     *
     * @param string $dsn
     *
     * @return array
     */
    static function parseDSN($dsn)
    {
        $dsn = str_replace('@/', '@localhost/', $dsn);
        $parse = parse_url($dsn);
        if (empty($parse['scheme'])) { return false; }

        $dsn = array();
        $dsn['host']     = isset($parse['host']) ? $parse['host'] : 'localhost';
        $dsn['port']     = isset($parse['port']) ? $parse['port'] : '';
        $dsn['login']    = isset($parse['user']) ? $parse['user'] : '';
        $dsn['password'] = isset($parse['pass']) ? $parse['pass'] : '';
        $dsn['driver']   = isset($parse['scheme']) ? strtolower($parse['scheme']) : '';
        $dsn['database'] = isset($parse['path']) ? substr($parse['path'], 1) : '';

        return $dsn;
    }
}
