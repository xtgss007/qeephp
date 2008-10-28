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
 * 定义 QGenerator_Runner_Cli 类
 */

/**
 * QGenerator_Runner_Cli 实现了一个命令行接口的代码生成器入口
 *
 * @package generator
 */
class QGenerator_Runner_Cli extends QGenerator_Runner_Abstract
{
	/**
	 * 命令行参数
	 *
	 * @var array
	 */
	protected $argv;

	/**
	 * 构造函数
	 *
	 * @param array $argv
	 */
	function __construct(array $argv)
	{
		array_shift($argv);
		$this->argv = $argv;

        if (!function_exists('mysql_query'))
        {
            dl('php_mysql.' . PHP_SHLIB_SUFFIX);
        }
	}

	/**
	 * 执行代码生成器
	 */
	function run()
	{
		$params = $this->argv;

		if (empty($params)) {
			$this->help();
			exit(-1);
		}

		$type = reset($params);
		array_shift($params);

		try {
			$generator = $this->getGenerator($type);
			if ($generator->execute($params) === false) {
				$this->help();
			}
		} catch (QGenerator_Exception $ex) {
			echo "\nERROR: ";
			echo $ex->getMessage();
			echo "\n";
			$this->help();
			exit(-1);
		}
	}

	/**
	 * 显示命令行帮助
	 */
	function help()
	{
		echo <<<EOT

scripts/generator <type> <....>

syntax:
	php script/generate.php controller <[namespace::]controller name[@module]>
	php script/generate.php model <class name[@module]> <database table name>

examples:
	php script/generate.php controller posts
	php script/generate.php controller admin::posts
	php script/generate.php controller posts@cms
	php script/generate.php controller admin::posts@cms

	php script/generate.php model post posts
	php script/generate.php model post@cms posts


EOT;

	}
}
