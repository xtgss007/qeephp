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
 * 定义 Chili_Runner_Cli 类
 *
 * @package chili
 * @version $Id$
 */

/**
 * Chili_Runner_Cli 是命令行的应用程序构造器
 *
 * @package chili
 */
class Chili_Runner_Cli extends Chili_Runner_Abstract
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
    }

    /**
     * 运行构造器
     */
    function run()
    {
        $dir = reset($this->argv);
        $appname = next($this->argv);
        if (empty($appname)) {
            $this->help();
            exit(-1);
        }
        $theme = next($this->argv);
        if (empty($theme)) {
            $theme = 'tianchi';
        }

        try {
            $this->buildApp($appname, $dir, $theme);
        } catch (QException $ex) {
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

chili <app name>

EOT;

    }
}