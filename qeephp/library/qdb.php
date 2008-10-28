<?php
// $Id$

/**
 * @file
 * 定义 QDB 类
 *
 * @ingroup database
 *
 * @{
 */

/**
 * QDB 类为数据库架构提供基础服务
 *
 * 包含下列功能：
 *
 * - 定义数据库架构使用的常量
 * - 为管理数据库连接提供了接口
 * - 分析 DSN 的辅助方法
 */
abstract class QDB
{
	//! 问号作为参数占位符
	const PARAM_QM          = '?';
	//! 冒号开始的命名参数
	const PARAM_CL_NAMED    = ':';
	//! $符号开始的序列
	const PARAM_DL_SEQUENCE = '$';
	//! @开始的命名参数
	const PARAM_AT_NAMED    = '@';

	//! 返回的每一个记录就是一个索引数组
	const FETCH_MODE_ARRAY  = 1;
	//! 返回的每一个记录就是一个以字段名作为键名的数组
	const FETCH_MODE_ASSOC  = 2;

	//! 一对一关联
	const HAS_ONE       = 'has_one';
	//! 一对多关联
	const HAS_MANY      = 'has_many';
	//! 从属关联
	const BELONGS_TO    = 'belongs_to';
	//! 多对多关联
	const MANY_TO_MANY  = 'many_to_many';


	//! 字段
	const FIELD = 'field';
	//! 属性
	const PROP = 'prop';

	/**
	 * 获得一个数据库连接对象
	 *
	 * $dsn_name 参数指定要使用应用程序设置中的哪一个项目作为创建数据库连接的 DSN 信息。
	 * 对于同样的 DSN 信息，只会返回一个数据库连接对象。
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
			trigger_error('invalid dsn');
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

/* @} */
