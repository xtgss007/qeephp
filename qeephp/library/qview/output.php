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
 * 定义 QView_Output 类
 *
 * @package mvc
 * @version $Id: sendfile.php 228 2008-03-07 18:06:06Z dualface $
 */

/**
 * QView_Output 类输出一个浏览器能够识别的下载文件
 *
 * @package mvc
 */
class QView_Output
{
    /**
     * 所有要输出的内容
     *
     * @var array
     */
    protected $_output = array();

    /**
     * 输出文件名
     *
     * @var string
     */
    protected $_output_filename;

    /**
     * 输出类型
     *
     * @var string
     */
    protected $_mime_type;

    /**
     * 输出文件名的字符集
     *
     * @var string
     */
    protected $_filename_charset = 'utf-8';

    /**
     * 允许客户端缓存输出的文件
     *
     * @var boolean
     */
    protected $_enabled_client_cache = true;

    /**
     * 构造函数
     *
     * @param string $output_filename
     * @param string $mime_type
     * @param string $content
     */
    function __construct($output_filename, $mime_type = 'application/octet-stream', $content = null)
    {
        $this->_output_filename  = $output_filename;
        $this->_mime_type        = $mime_type;
        if ($content) { $this->appendData($content); }
    }

    /**
     * 添加一个要输出的文件
     *
     * @param string $filename
     *
     * @return QView_Output
     */
    function addFile($filename)
    {
        $this->_output[] = array('file', $filename);
        return $this;
    }

    /**
     * 追加要输出的数据
     *
     * @param string $content
     *
     * @return QView_Output
     */
    function appendData($content)
    {
        $this->_output[] = array('raw', $content);
        return $this;
    }

    /**
     * 设置输出文件名
     *
     * @param string $output_filename
     *
     * @return QView_Output
     */
    function setOutputFilename($output_filename)
    {
        $this->_output_filename = $output_filename;
        return $this;
    }

    /**
     * 设置输出文件名的编码
     *
     * @param string $charset
     *
     * @return QView_Output
     */
    function setOutputFilenameCharset($charset)
    {
        $this->_filename_charset = $charset;
        return $this;
    }

    /**
     * 设置是否允许客户端缓存输出的文件
     *
     * @param boolean $enabled
     *
     * @return QView_Output
     */
    function enableClientCache($enabled = true)
    {
        $this->_enabled_client_cache = $enabled;
        return $this;
    }

    /**
     * 设置输出类型
     *
     * @param string $mime_type
     *
     * @return QView_Output
     */
    function setMimeType($mime_type)
    {
        $this->_mime_type = $mime_type;
        return $this;
    }

    /**
     * 执行响应
     */
    function execute()
    {
        header("Content-Type: {$this->_mime_type}");
        $filename = '"' . htmlspecialchars($this->_output_filename) . '"';

        $filesize = 0;
        foreach ($this->_output as $output) 
        {
            list($type, $data) = $output;
            if ($type == 'file') 
            {
                $filesize += filesize($data);
            }
            else
            {
                $filesize += strlen($data);
            }
        }

        header("Content-Disposition: attachment; filename={$filename}; charset={$this->_filename_charset}");
        if ($this->_enabled_client_cache) {
            header('Pragma: cache');
        }
        header('Cache-Control: public, must-revalidate, max-age=0');
        header("Content-Length: {$filesize}");

        foreach ($this->_output as $output) {
            list($type, $data) = $output;
            if ($type == 'file') {
                readfile($data);
            } else {
                echo $data;
            }
        }
    }
}
