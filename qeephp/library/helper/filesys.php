<?php
// $Id$

/**
 * @file
 * 定义一组用于简化文件系统操作的函数
 *
 * @ingroup helper
 *
 * @{
 */

/**
 * Helper_Filesys 类提供了一组简化文件系统操作的方法 
 */
abstract class Helper_Filesys
{
    /**
     * 创建一个目录树
     *
     * 用法：
     * @code
     * Helper_Filesys::mkdirs('/top/second/3rd');
     * @endcode
     *
     * @param string $dir
     * @param int $mode
     */
    static function mkdirs($dir, $mode = 0777)
    {
        if (!is_dir($dir))
        {
            self::mkdirs(dirname($dir), $mode);
            $ret = @mkdir($dir, $mode);
            if (!$ret)
            {
                throw new Q_CreateDirFailedException($dir);
            }
        }
        return true;
    }

    /**
     * 删除指定目录及其下的所有文件和子目录
     *
     * 用法：
     * @code
     * // 删除 my_dir 目录及其下的所有文件和子目录
     * Helper_Filesys::rmdirs('/path/to/my_dir');
     * @endcode
     *
     * 注意：使用该函数要非常非常小心，避免意外删除重要文件。
     *
     * @param string $dir
     */
    static function rmdirs($dir)
    {
        $dir = realpath($dir);
        if ($dir == '' || $dir == '/' || (strlen($dir) == 3 && substr($dir, 1) == ':\\'))
        {
            // 禁止删除根目录
            throw new Q_RemoveDirFailedException($dir);
        }

        // 遍历目录，删除所有文件和子目录
        if(false !== ($dh = opendir($dir))) 
        {
            while(false !== ($file = readdir($dh))) 
            {
                if($file == '.' || $file == '..')
                {
                    continue;
                }

                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) 
                {
                    self::rmdirs($path);
                } 
                else 
                {
                    unlink($path);
                }
            }
            closedir($dh);
            if (@rmdir($dir) == false)
            {
                throw new Q_RemoveDirFailedException($dir);
            }
        }
        else 
        {
            throw new Q_RemoveDirFailedException($dir);
        }
    }
}

/**
 * @}
 */

