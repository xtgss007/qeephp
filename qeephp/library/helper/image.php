<?php
// $Id$

/**
 * @file
 * 定义 Helper_Image 类
 *
 * @ingroup helper
 *
 * @{
 */

/**
 * Helper_Image 类封装了针对图像的操作
 *
 * 开发者不能直接构造该类的实例，而是应该用 Helper_Image::createFromFile()
 * 静态方法创建一个 Image 类的实例。
 *
 * 操作大图片时，请确保 php 能够分配足够的内存。
 */
abstract class Helper_Image
{
    /**
     * 从指定文件创建 Helper_ImageGD 对象
     *
     * 对于上传的文件，由于其临时文件名中并没有包含扩展名。因此需要采用下面的方法创建 Image 对象：
     *
     * @code
     * $ext = pathinfo($_FILES['postfile']['name'], PATHINFO_EXTENSION);
     * $image = Image::createFromFile($_FILES['postfile']['tmp_name'], $ext);
     * @endcode
     *
     * @param string $filename
     * @param string $fileext
     *
     * @return Helper_ImageGD
     */
    static function createFromFile($filename, $fileext)
    {
        $fileext = trim(strtolower($fileext), '.');
        $ext2functions = array
        (
            'jpg'  => 'imagecreatefromjpeg',
            'jpeg' => 'imagecreatefromjpeg',
            'png'  => 'imagecreatefrompng',
            'gif'  => 'imagecreatefromgif'
        );

        if (!isset($ext2functions[$fileext]))
        {
        	throw new QException_NotImplemented(__('imagecreateform' . $fileext));
        }

        $handle = call_user_func($ext2functions[$fileext], $filename);
        return new Helper_ImageGD($handle);
    }

	/**
	 * 将 16 进制颜色值转换为 rgb 值
	 *
	 * @param string $hex
	 *
	 * @return array
	 */
	static function hex2rgb($color, $defualt = 'ffffff')
	{
        $hex = trim($color, '#&Hh');
        $len = strlen($hex);
        if ($len == 3) {
            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
        } elseif ($len < 6) {
            $hex = $default;
        }
        $dec = hexdec($hex);
        return array(($dec >> 16) & 0xff, ($dec >> 8) & 0xff, $dec & 0xff);
	}
}


class Helper_ImageGD
{

    /**
     * GD 资源句柄
     *
     * @var resource
     */
    protected $_handle = null;

    /**
     * 构造函数
     *
     * @param resource $handle
     */
    function __construct($handle)
    {
        $this->_handle = $handle;
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
    	$this->destroy();
    }

    /**
     * 快速缩放图像到指定大小（质量较差）
     *
     * @param int $width
     * @param int $height
     */
    function resize($width, $height)
    {
        if (is_null($this->_handle))
        {
            return;
        }

        $dest = imagecreatetruecolor($width, $height);
        imagecopyresized($dest, $this->_handle, 0, 0, 0, 0,
                         $width, $height, imagesx($this->_handle), imagesy($this->_handle));
        imagedestroy($this->_handle);
        $this->_handle = $dest;
    }

    /**
     * 缩放图像到指定大小（质量较好，速度比 resize() 慢）
     *
     * @param int $width
     * @param int $height
     */
    function resampled($width, $height)
    {
        if (is_null($this->_handle))
        {
            return;
        }
        $dest = imagecreatetruecolor($width, $height);
        imagecopyresampled($dest, $this->_handle, 0, 0, 0, 0,
                           $width, $height, imagesx($this->_handle), imagesy($this->_handle));
        imagedestroy($this->_handle);
        $this->_handle = $dest;
    }

    /**
     * 调整图像大小，但不进行缩放操作
     *
     * @param int $width
     * @param int $height
     * @param string $pos
     * @param string $bgcolor
     */
    function resizeCanvas($width, $height, $pos = 'center', $bgcolor = '0xffffff')
    {
        if (is_null($this->_handle))
        {
            return;
        }
        $dest = imagecreatetruecolor($width, $height);
        $sx = imagesx($this->_handle);
        $sy = imagesy($this->_handle);

        // 根据 pos 属性来决定如何定位原始图片
        switch (strtolower($pos))
        {
        case 'left':
            $ox = 0;
            $oy = ($height - $sy) / 2;
            break;
        case 'right':
            $ox = $width - $sx;
            $oy = ($height - $sy) / 2;
            break;
        case 'top':
            $ox = ($width - $sx) / 2;
            $oy = 0;
            break;
        case 'bottom':
            $ox = ($width - $sx) / 2;
            $oy = $height - $sy;
            break;
        case 'top-left':
            $ox = $oy = 0;
            break;
        case 'top-right':
            $ox = $width - $sx;
            $oy = 0;
            break;
        case 'bottom-left':
            $ox = 0;
            $oy = $height - $sy;
            break;
        case 'bottom-right':
            $ox = $width - $sx;
            $oy = $height - $sy;
            break;
        default:
            $ox = ($width - $sx) / 2;
            $oy = ($height - $sy) / 2;
        }

        list ($r, $g, $b) = Helper_Image::hex2rgb($bgcolor, '0xffffff');
        $bgcolor = imagecolorallocate($dest, $r, $g, $b);
        imagefilledrectangle($dest, 0, 0, $width, $height, $bgcolor);
        imagecolordeallocate($dest, $bgcolor);

        imagecopy($dest, $this->_handle, $ox, $oy, 0, 0, $sx, $sy);
        imagedestroy($this->_handle);
        $this->_handle = $dest;
    }

    /**
     * 在保持图像长宽比的情况下将图像裁减到指定大小
     *
     * @param int $width
     * @param int $height
     * @param boolean $highQuality
     * @param array $nocut
     */
    function crop($width, $height, $highQuality = true, $nocut = null)
    {
        if (is_null($this->_handle)) { return; }

        $dest = imagecreatetruecolor($width, $height);
        $sx = imagesx($this->_handle);
        $sy = imagesy($this->_handle);
        $ratio = doubleval($width) / doubleval($sx);

        if (! is_array($nocut))
        {
            if ($nocut)
            {
                $nocut = array('enabled' => true, 'pos' => 'center', 'bgcolor' => '0xffffff');
            }
            else
            {
                $nocut = array('enabled' => false);
            }
        }
        else
        {
            $nocut['enabled'] = isset($nocut['enabled']) ? $nocut['enabled'] : true;
            $nocut['pos']     = isset($nocut['pos'])     ? $nocut['pos'] : 'center';
            $nocut['bgcolor'] = isset($nocut['bgcolor']) ? $nocut['bgcolor'] : '0xffffff';
        }

        if ($nocut['enabled'])
        {
            // 求缩放后的最大宽度和高度
            if ($sy * $ratio > $height)
            {
                $ratio = doubleval($height) / doubleval($sy);
            }
            $dx = $sx * $ratio;
            $dy = $sy * $ratio;

            // 根据 pos 属性来决定如何定位原始图片
            switch (strtolower($nocut['pos']))
            {
            case 'left':
                $ox = 0;
                $oy = ($height - $sy * $ratio) / 2;
                break;
            case 'right':
                $ox = $width - $sx * $ratio;
                $oy = ($height - $sy * $ratio) / 2;
                break;
            case 'top':
                $ox = ($width - $sx * $ratio) / 2;
                $oy = 0;
                break;
            case 'bottom':
                $ox = ($width - $sx * $ratio) / 2;
                $oy = $height - $sy * $ratio;
                break;
            case 'top-left':
                $ox = $oy = 0;
                break;
            case 'top-right':
                $ox = $width - $sx * $ratio;
                $oy = 0;
                break;
            case 'bottom-left':
                $ox = 0;
                $oy = $height - $sy * $ratio;
                break;
            case 'bottom-right':
                $ox = $width - $sx * $ratio;
                $oy = $height - $sy * $ratio;
                break;
            default:
                $ox = ($width - $sx * $ratio) / 2;
                $oy = ($height - $sy * $ratio) / 2;
            }

            list ($r, $g, $b) = Helper_Image::hex2rgb($nocut['bgcolor'], '0xffffff');
            $bgcolor = imagecolorallocate($dest, $r, $g, $b);
            imagefilledrectangle($dest, 0, 0, $width, $height, $bgcolor);
            imagecolordeallocate($dest, $bgcolor);

            $args = array(
                $dest, $this->_handle, $ox, $oy, 0, 0, $dx, $dy, $sx, $sy
            );
        }
        else
        {
            // 允许图像溢出
            if ($sy * $ratio < $height)
            {
                // 当按照比例缩放后的图像高度小于要求的高度时，只有放弃原始图像右边的部分内容
                $ratio = doubleval($sy) / doubleval($height);
                $sx = $width * $ratio;
            }
            elseif ($sy * $ratio > $height)
            {
                // 当按照比例缩放后的图像高度大于要求的高度时，只有放弃原始图像底部的部分内容
                $ratio = doubleval($sx) / doubleval($width);
                $sy = $height * $ratio;
            }

            $args = array(
                $dest, $this->_handle, 0, 0, 0, 0, $width, $height, $sx, $sy
            );
        }

        if ($highQuality)
        {
            call_user_func_array('imagecopyresampled', $args);
        }
        else
        {
            call_user_func_array('imagecopyresized', $args);
        }

        imagedestroy($this->_handle);
        $this->_handle = $dest;
    }

    /**
     * 保存为 JPEG 文件
     *
     * @param string $filename
     * @param int $quality
     */
    function saveAsJpeg($filename, $quality = 80)
    {
        imagejpeg($this->_handle, $filename, $quality);
    }

    /**
     * 保存为 PNG 文件
     *
     * @param string $filename
     */
    function saveAsPng($filename)
    {
        imagepng($this->_handle, $filename);
    }

    /**
     * 保存为 GIF 文件
     *
     * @param string $filename
     */
    function saveAsGif($filename)
    {
        imagegif($this->_handle, $filename);
    }

    /**
     * 销毁内存中的图像
     */
    function destroy()
    {
    	if (!$this->_handle)
    	{
            @imagedestroy($this->_handle);
    	}
        $this->_handle = null;
    }

}


/**
 * @}
 */
