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
 * 定义 Helper_Sendfile 类
 *
 * @package helper
 * @version $Id$
 */

/**
 * Helper_Sendfile 发送一个文件到浏览器
 *
 * @package helper
 */
class Helper_Sendfile
{
    /**
     * 将服务器上的一个文件发送到浏览器
     *
     * @param string $server_file_path 文件在服务器上的路径（绝对或者相对路径）
     * @param string $filename 发送给浏览器的文件名（尽可能不要使用中文）
     * @param string $mime_type 指示文件类型
     */
    function file($server_file_path, $filename = '', $mime_type = 'application/octet-stream')
    {
        if (empty($filename)) {
            $filename = pathinfo($server_file_path, PATHINFO_BASENAME);
        }
        $this->headers($filename, $mime_type);
        $filesize = filesize($server_file_path);
        header("Content-Length: {$filesize}");
        readfile($server_file_path);
        exit;
    }

    /**
     * 将字符串发送到浏览器
     *
     * @param string $content
     * @param string $filename
     * @param string $mime_type
     */
    function stream($content, $filename, $mime_type = 'application/octet-stream')
    {
        $this->headers($filename, $mime_type);
        echo $content;
        exit;
    }

    /**
     * 输出必须的头信息
     *
     * @param string $filename
     * @param string $mime_type
     */
    protected function headers($filename, $mime_type)
    {
        $filename = '"' . htmlspecialchars($filename) . '"';
        header("Content-Type: {$mime_type}");
        $charset = Q::getIni('i18n_response_charset');
        header("Content-Disposition: attachment; filename={$filename}; charset={$charset}");
        header('Pragma: cache');
        header('Cache-Control: public, must-revalidate, max-age=0');
    }
}
