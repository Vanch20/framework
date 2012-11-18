<?php
namespace FW\Lib;
/**
 * 图像处理类
 * 可以使用GD或IM(imagick)处理图像
 *
 * 用法：
 * <code>
 * $i = Imagefactory::produce('IM', 'test.jpg');
 * $i->createThumb(80, 100, 'thumb.jpg', 1);
 * unset($i);
 * </code>
 *
 * @author linln
 * @created 2009-10-22
 */
/**
 * 图像处理抽象工厂
 */
abstract class Imagefactory
{
	/**
	 * 产生一个图像处理类产品
	 * 支持GD或IM(imagick)
	 *
	 * @param string $name 图像处理类名称
	 * @param string $src 图像源文件
	 */
	public static function produce($name, $src)
	{
		if ($name == 'GD') return new Image_GD($src);
		if ($name == 'IM') return new Image_IM($src);
		return null;
	}

	/**
	 * 图片宽度
	 *
	 * @var int
	 */
	protected $_w	= 0;

	/**
	 * 图片高度
	 *
	 * @var int
	 */
	protected $_h	= 0;

	/**
	 * 源文件路径和文件名
	 *
	 * @var string
	 */
	protected $_src	= null;

	/**
	 * 图片对象
	 *
	 * @var int
	 */
	protected $_i	= null;

	/**
	 * 取得图片宽度
	 *
	 * @return int
	 */
	public function getWidth()
	{
		return $this->_w;
	}

	/**
	 * 取得图片高度
	 *
	 * @return int
	 */
	public function getHeight()
	{
		return $this->_h;
	}

	/**
	 * 生成缩略图
	 * 当$force为false时，如果目标长宽大于源文件长宽将不进行处理
	 *
	 * 缩放方式说明
	 *		0: 不保证生成图片的长宽与指定长宽相同 默认
	 *		1: 填充背景以保证生成图片的长宽与指定的长宽相等
	 *		2: 裁切图片以保证生成图片的长宽与指定的长宽相等
	 *
	 * @param int $w 宽度
	 * @param int $h 高度
	 * @param string $dst 目标文件路径和名称
	 * @param int $fit 缩放方式
	 * @param boolean $force 是否强制保证目标文件长宽为指定的长宽 默认否
	 */
	abstract public function createThumb($w, $h, $dst, $fit = 0, $force = false);

	/**
	 * 裁切图片
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $w
	 * @param int $h
	 * @param string $dst
	 */
	abstract public function createCrop($x, $y, $w, $h, $dst);
}

/**
 * 图像处理类 － 使用Imagick
 */
class Image_IM extends Imagefactory
{
	public function __construct($src)
	{
		$this->setSrc($src);
	}

	public function setSrc($src)
	{
		if (file_exists($src))
		{
			$this->_src = $src;
			$this->_i = new \Imagick($src);

			$formet = strtolower($this->_i->getImageFormat());
			if ('gif' == $formet)
			{
				$image_page = $this->_i->getImagePage();
				$this->_w = $image_page['width'];
				$this->_h = $image_page['height'];
			}
			else
			{
				$this->_w = $this->_i->getImageWidth();
				$this->_h = $this->_i->getImageHeight();
			}
			$this->_i->setImageFormat('jpg');

			return true;
		}
		return false;
	}

	public function createThumb($w, $h, $dst, $fit = 0, $force = false)
	{
		$i = $this->_i->clone();
		if (!$force)
		{
			$w = $w > $this->_w ? $this->_w : $w;
			$h = $h > $this->_h ? $this->_h : $h;
		}

		switch ($fit)
		{
			case 1 :
				$i->thumbnailImage($w, $h, 1);
				$tmp_i = new \Imagick();
				$tmp_i->newImage($w, $h, new \ImagickPixel('white'));

				$thumb_w = $i->getImageWidth();
				$thumb_h = $i->getImageHeight();

				$offset_w = 0;
				$offset_h = 0;
				if ($thumb_w != $w) $offset_w = $w / 2 - $thumb_w / 2;
				if ($thumb_h != $h) $offset_h = $h / 2 - $thumb_h / 2;

				$tmp_i->compositeImage($i, \imagick::COMPOSITE_OVER, $offset_w, $offset_h);
				$i = $tmp_i;
				break;

			case 2 :
				$i->cropThumbnailImage($w, $h);
				break;

			case 0:
			default :
				$i->thumbnailImage($w, $h, 1);
		}

		if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0666, true);
		$i->writeImage($dst);
		$i->destroy();
	}

	public function createCrop($x, $y, $w, $h, $dst)
	{
		$i = $this->_i->clone();
		$i->cropImage($w, $h, $x, $y);
		if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0666, true);
		$i->writeImage($dst);
		$i->destroy();
	}

	public function __destruct()
	{
		$this->_i->destroy();
	}
}

/**
 * 图像处理类 － 使用GD
 */
class Image_GD
{
	private $_typelist = array('jpg' => 'imagecreatefromjpeg',
							   'jpeg'=> 'imagecreatefromjpeg',
							   'png' => 'imagecreatefrompng',
							   'gif' => 'imagecreatefromgif');

	private $_type = null;

	private $_error = '';


	public function __construct($src)
	{
		$this->setSrc($src);
	}

	public function setSrc($src)
	{
		if (!file_exists($src))
		{
			return false;
		}

		$this->_src = $src;
		$src = pathinfo($src);
		$ext = strtolower($src['extension']);

		if (isset($this->_typelist[$ext]))
		{
			try
			{
				$this->_i = $this->_typelist[$ext]($this->_src);
			}
			catch (Exception $e)
			{
				$this->_error = '初始化图像失败';
				return false;
			}

			$this->_type = $ext;

			// 取得图片长宽
			$this->_w = imagesx($this->_i);
			$this->_h = imagesy($this->_i);

			return true;
		}
		else
		{
			$this->_error = '不被支持的格式';
			return false;
		}
	}

	public function createThumb($w, $h, $dst, $fit = 0, $force = false)
	{
		if (empty($this->_src) || empty($this->_i))
		{
			$this->error = '没有指定源文件';
			return false;
		}

		if (empty($w)) return false;

		$h = empty($h) ? $w : $h;

        $resize_ratio = $w / $h; //改变后的图象的比例
        $ratio = $this->_w / $this->_h;	//实际图象的比例

		$sw = 0;
		$sh = 0;
		if ($this->_w > $this->_h)
		{
			$sh = 0;
			$sw = ($this->_w / 2) - ($this->_h / 2);
		}
		if ($this->_w < $this->_h)
		{
			$sh = ($this->_h / 2) - ($this->_w / 2);
			$sw = 0;
		}

        if ($fit == 2)
        //裁图
        {
            if ($ratio >= $resize_ratio)
            //高度优先
            {
                $newimg = imagecreatetruecolor($w, $h);
                imagecopyresampled($newimg, $this->_i, 0, 0, $sw, $sh, $w, $h, ($this->_h * $resize_ratio), $this->_h);
            }
            if ($ratio < $resize_ratio)
            //宽度优先
            {
                $newimg = imagecreatetruecolor($w, $h);
                imagecopyresampled($newimg, $this->_i, 0, 0, $sw, $sh, $w, $h, $this->_w, ($this->_w / $resize_ratio));
            }
        }
        else
        //不裁图
        {
            if ($ratio >= $resize_ratio)
            {
                $newimg = imagecreatetruecolor($w, ($w)/$ratio);
                imagecopyresampled($newimg, $this->_i, 0, 0, 0, 0, $w, ($w)/$ratio, $this->_w, $this->_h);
            }
            if ($ratio < $resize_ratio)
            {
                $newimg = imagecreatetruecolor(($h)*$ratio, $h);
                imagecopyresampled($newimg, $this->_i, 0, 0, 0, 0, ($h)*$ratio, $h, $this->_w, $this->_h);
            }
        }

		// 修改图像大小
    	if ($fit == 1)
    	{
    		$bg = imagecreatetruecolor($w, $h);
    		$white = imagecolorallocate($bg, 255, 255, 255);
    		imagefill($bg, 0, 0, $white);

    		// 让图片居中
    		$w = imagesx($newimg);
    		$h = imagesy($newimg);
    		$pos_x = 0;
    		$pos_y = 0;
    		if ($this->_w < $w)
    		{
    			$pos_x = $w / 2 - $this->_w / 2;
    		}
    		if ($this->_h < $h)
    		{
    			$pos_y = $h / 2 - $this->_h / 2;
    		}
    		imagecopymerge($bg, $newimg, $pos_x, $pos_y, 0, 0, $this->_w, $this->_h, 100);
    		$newimg = $bg;
    	}

        $this->output($newimg, $dst);
        return true;
	}

	public function createCrop($x, $y, $w, $h, $dst)
	{
		if (empty($this->_src) || empty($this->_i))
		{
			$this->_error = '没有指定源文件';
			return false;
		}

		$newimg = imagecreatetruecolor($w, $h);
		imagecopy($newimg, $this->_i, 0, 0, $x, $y, $w, $h);

		$bg = imagecreatetruecolor($w, $h);
		$white = imagecolorallocate($bg, 255, 255, 255);
		imagefill($bg, 0, 0, $white);

		$w = imagesx($newimg);
		$h = imagesy($newimg);
		$pos_x = 0;
		$pos_y = 0;
		if ($this->_w < $w)
		{
			$pos_x = $w / 2 - $this->_w / 2;
		}
		if ($this->_h < $h)
		{
			$pos_y = $h / 2 - $this->_h / 2;
		}
		imagecopymerge($bg, $newimg, $pos_x, $pos_y, 0, 0, $this->_w, $this->_h, 100);
		$newimg = $bg;

		$this->output($newimg, $dst);
        return true;
	}

	/**
	 * 输出图像
	 *
	 * @param image $img
	 */
	function output($image, $dst)
	{
		if (empty($this->_src) || empty($this->_i))
		{
			$this->_error = '没有指定源文件';
			return false;
		}

		imagejpeg($image, $dst, 100);
		imagedestroy($this->_i);

		// 为了安全，图片不需要可执行权限
		chmod($dst, 0666);
		return true;
    }
}
?>
