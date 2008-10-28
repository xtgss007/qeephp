<?php
// $Id$

/**
 * @file
 * 定义 Helper_Uploader 类和 Helper_UploadFile 类
 *
 * @ingroup helper
 *
 * @{
 */

/**
 * Helper_Uploader 实现了一个简单的、可扩展的文件上传助手
 */
class Helper_Uploader extends QController_Helper_Abstract
{

	/**
     * 所有的 UploadFile 对象实例
     *
     * @var array of Helper_UploadFile
	 */
	static protected $_files = array();

    /**
     * 指示是否已经完成了初始化
     *
     * @var boolean
     */
    static protected $_init = false;

    /**
     * 确定最大上传限制（字节数）
     *
     * @return int
     */
    function getUploadMaxFilesize()
    {
        $val = trim(ini_get('upload_max_filesize'));
        $last = strtolower($val{strlen($val) - 1});
        switch ($last)
        {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
        }

        return $val;
    }

	/**
     * 可用的上传文件对象数量
     *
	 * @return int
	 */
	function getCount()
	{
        $this->_init();
		return count(self::$_files);
	}

    /**
     * 获得所有上传文件对象
     *
     * @return array
     */
    function getAllFiles()
    {
        $this->_init();
        return self::$_files;
    }

    /**
     * 检查指定名字的上传对象是否存在
     *
     * @param string $name
     *
     * @return boolean
     */
    function existsFile($name)
    {
        $this->_init();
        return isset(self::$_files[$name]);
    }

    /**
     * 取得指定名字的上传文件对象
     *
     * @param string $name
     *
     * @return Helper_UploadFile
     */
    function getFile($name)
    {
        $this->_init();
        if (!isset(self::$_files[$name]))
        {
            throw new QException(__('Upload file field "%s" not found.', $name));
        }
        return self::$_files[$name];
    }

    /**
     * 移动所有上传文件到指定目录
     *
     * @param string $dest_dir
     */
    function move($dest_dir)
    {
        $this->_init();
        foreach (self::$_files as $file)
        {
            $dest = $dest_dir . DS . $file->getFilename();
            $file->move($dest);
        }
    }

    /**
     * 初始化上传
     */
    protected function _init()
    {
        if (self::$_init) { return; }
        self::$_init = true;
        if (!$this->context->isPOST()) { return; }

        if (empty($_FILES))
        {
            self::$_files = array();
            return;
        }

        foreach ($_FILES as $field_name => $postinfo)
        {
            if (!isset($postinfo['error'])) { continue; }
            if (is_array($postinfo['error']))
            {
                // 多文件上传
                foreach ($postinfo['error'] as $offset => $error)
                {
                    if ($error == UPLOAD_ERR_OK)
                    {
                        $file = new Helper_UploadFile($postinfo, $field_name, $offset);
                        self::$_files["{$field_name}{$offset}"] = $file;
                    }
                }
            }
            else
            {
                if ($postinfo['error'] == UPLOAD_ERR_OK)
                {
                    self::$_files[$field_name] = new Helper_UploadFile($postinfo, $field_name);
                }
            }

        }
    }

}

/**
 * 封装一个上传的文件
 */
class Helper_UploadFile
{
	/**
	 * 上传文件信息
	 *
	 * @var array
	 */
	protected $_file = array();

	/**
	 * 上传文件对象的名字
	 *
	 * @var string
	 */
	protected $_name;

	/**
	 * 构造函数
	 *
	 * @param array $struct
	 * @param string $name
	 * @param int $ix
	 *
	 * @return Helper_UploadFile
	 */
	function __construct($struct, $name, $ix = false)
	{
        if ($ix !== false)
        {
			$this->_file = array(
				'name' => $struct['name'][$ix],
				'type' => $struct['type'][$ix],
				'tmp_name' => $struct['tmp_name'][$ix],
				'error' => $struct['error'][$ix],
				'size' => $struct['size'][$ix],
			);
        }
        else
        {
			$this->_file = $struct;
		}

        $this->_file['full_path'] = $this->_file['tmp_name'];
        $this->_file['is_moved'] = false;
        $this->_name = $name;
	}

	/**
	 * 返回上传文件对象的名字
	 *
	 * @return string
	 */
	function getPostFieldName()
	{
		return $this->_name;
	}

	/**
	 * 指示上传是否成功
	 *
	 * @return boolean
	 */
	function isSuccessed()
	{
		return $this->_file['error'] == UPLOAD_ERR_OK;
	}

	/**
	 * 返回上传错误代码
	 *
	 * @return int
	 */
	function getError()
	{
		return $this->_file['error'];
	}

	/**
	 * 指示上传文件是否已经从临时目录移出
	 *
	 * @return boolean
	 */
	function isMoved()
	{
		return $this->_file['is_moved'];
	}

	/**
	 * 返回上传文件的原名
	 *
	 * @return string
	 */
	function getFilename()
	{
		return $this->_file['name'];
	}

	/**
	 * 返回上传文件不带"."的扩展名
	 *
	 * @return string
	 */
	function getExtname()
	{
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
	}

	/**
	 * 返回上传文件的大小（字节数）
	 *
	 * @return int
	 */
	function getSize()
	{
		return $this->_file['size'];
	}

	/**
	 * 返回上传文件的 MIME 类型（由浏览器提供，不可信）
	 *
	 * @return string
	 */
	function getMimeType()
	{
		return $this->_file['type'];
	}

	/**
	 * 返回上传文件的临时文件名
	 *
	 * @return string
	 */
	function getTmpName()
	{
		return $this->_file['tmp_name'];
	}

	/**
	 * 获得文件的新路径（通常是移动后的新路径，包括文件名）
	 *
	 * @return string
	 */
	function getFullPath()
	{
		return $this->_file['full_path'];
	}

	/**
	 * 检查上传的文件是否成功上传，并符合检查条件（文件类型、最大尺寸）
	 *
	 * 文件类型以扩展名为准，多个扩展名以 , 分割，例如 .jpg,.jpeg,.png。
	 *
	 * @param string $allow_exts 允许的扩展名
	 * @param int $max_size 允许的最大上传字节数
	 *
	 * @return boolean
	 */
	function check($allow_exts = null, $max_size = null)
	{
		if (!$this->isSuccessed()) { return false; }

		if ($allow_exts)
        {
            if (is_array($allow_exts))
            {
                $allow_exts = Q::normalize($allow_exts);
            }
            elseif (strpos($allow_exts, '/') !== false)
            {
                $allow_exts = Q::normalize($allow_exts, '/');
            }
            elseif (strpos($allow_exts, '|') !== false)
            {
                $allow_exts = Q::normalize($allow_exts, '|');
            }
            else
            {
                $allow_exts = Q::normalize($allow_exts, ',');
            }

            foreach ($allow_exts as $offset => $extname)
            {
                if ($extname{0} == '.')
                {
                    $extname = substr($extname, 1);
                }
                $allow_exts[$offset] = strtolower($extname);
            }
            $allow_exts = array_flip($allow_exts);

            $filename = strtolower(basename($this->getFilename()));
            $extnames = Q::normalize($filename, '.');
            array_shift($extnames);
            $passed = false;

            for ($i = count($extnames) - 1; $i >= 0; $i--)
            {
                $checking_ext = implode('.', array_slice($extnames, $i));
                if (isset($allow_exts[$checking_ext]))
                {
                    $passed = true;
                    break;
                }
            }

            if (!$passed) { return false; }
		}

		if ($max_size && ($this->getSize() > $max_size))
        {
			return false;
		}

		return true;
	}

	/**
	 * 移动上传文件到指定位置和文件名
	 *
	 * @param string $destPath
	 */
	function move($dest_path)
	{
        if ($this->_file['is_moved'])
        {
            $ret = rename($this->_file['full_path'], $dest_path);
        }
        else
        {
            $this->_file['is_moved'] = true;
            $ret = move_uploaded_file($this->_file['full_path'], $dest_path);
        }
        if ($ret)
        {
            $this->_file['full_path'] = $dest_path;
        }
        return $ret;
	}

    /**
     * 复制上传文件
     *
     * @param string $dest_path
     */
    function copy($dest_path)
    {
        copy($this->_file['full_path'], $dest_path);
    }

    /**
     * 删除上传文件
     */
    function unlink()
    {
        unlink($this->_file['full_path']);
    }
}

/**
 * @}
 */

