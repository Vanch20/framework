<?php
/**
 * 图像处理类 使用GD
 *
 * @author linln
 * @version $Id$
 * 
 * @example 
 * 下面代码将会把test.jpg缩略成70*60的new.jpg
 * <code>
 * $img = new Image();
 * $img->setSrc('/test/test.jpg');
 * $img->setDst('/test/new.jpg');
 * $img->createThumb(70, 60);
 * unset($img);
 * </code>
 */

class Image
{
	protected $src = '';
	protected $src_width = 0;
	protected $src_height = 0;
	
	protected $dst = '';
	protected $dst_width = 0;
	protected $dst_height = 0;
	
	/**
	 * 是否裁切图片
	 *
	 * @var boolean
	 */
	public $cut = false;
	
	/**
	 * 是否修正图像为固定大小
	 *
	 * @var boolean
	 */
	public $fix = true;
	
	/**
	 * 生成图片的质量
	 *
	 * @var int 1-100
	 */
	public $quality = 100;
	
	/**
	 * 源图类型
	 *
	 * @var string
	 */
	protected $type = '';
	
	/**
	 * 图片句柄
	 *
	 * @var handle
	 */
	private $_handle;
	
	/**
	 * 设定图像文件格式对应的创建函数
	 *
	 * @var array
	 */
	private $_typelist = array('jpg' => 'imagecreatefromjpeg',
							   'jpeg'=> 'imagecreatefromjpeg',
							   'png' => 'imagecreatefrompng',
							   'gif' => 'imagecreatefromgif',
							   'bmp' => 'imagecreatefrombmp');
	
	/**
	 * 设定源文件并初始化handle
	 *
	 * @param string $src
	 */
	public function setSrc($src)
	{
		if (empty($src) && !file_exists($src))
		{
			return false;
		}
		
		$this->src = $src;
		$src = pathinfo($src);
		$ext = strtolower($src['extension']);
		
		if (isset($this->_typelist[$ext]))
		{
			try 
			{
				$this->_handle = $this->_typelist[$ext]($this->src);
			}
			catch (Exception $e)
			{
				$this->error = '不被支持的格式';
				return false;
			}
			
			$this->type = $ext;
			
			// 取得图片长宽
			$this->src_width = imagesx($this->_handle);
			$this->src_height = imagesy($this->_handle);
			
			return true;
		}
		else
		{
			$this->error = '不被支持的格式';
			return false;
		}
	}
	
	/**
	 * 设定目标文件
	 *
	 * @param string $dst
	 */
	public function setDst($dst)
	{
		if (!empty($dst))
		{
			$path = dirname($dst);
			if (!is_dir($path))
			{
				if (!mkdir($path, 0766, true))
				{
					$this->error = '创建目标目录失败';
					return false;
				}
			}
			$this->dst = $dst;
			return true;
		}
		return false;
	}
	
	/**
	 * 建立缩略图
	 * 
	 * 创建缩略图,当目标文件路径为空时，覆盖源文件
	 * 
	 * @param int $width 缩略图宽度
	 * @param int $height 缩略图高度
	 * 
	 * @return boolean
	 */
	public function createThumb($width = 0, $height = 0)
	{
		if (empty($this->src) || empty($this->_handle))
		{
			$this->error = '没有指定源文件';
			return false;
		}
		
		if (empty($width)) return false;
		
		$height = empty($height) ? $width : $height;
		
		$this->dst_width = $width;
		$this->dst_height = $height;
		
		$w = $this->src_width;
		$h = $this->src_height;
		
        $resize_ratio = $width / $height; //改变后的图象的比例
        $ratio = $w / $h;	//实际图象的比例
		
		$sw = 0;
		$sh = 0;
		if ($w > $h)
		{
			$sh = 0;
			$sw = ($w / 2) - ($h / 2);
		}
		if ($w < $h)
		{
			$sh = ($h / 2) - ($w / 2);
			$sw = 0;
		}
		
        if ($this->cut)
        //裁图
        {
            if ($ratio >= $resize_ratio)
            //高度优先
            {
                $newimg = imagecreatetruecolor($width, $height);
                imagecopyresampled($newimg, $this->_handle, 0, 0, $sw, $sh, $width, $height, ($h * $resize_ratio), $h);
            }
            if ($ratio < $resize_ratio)
            //宽度优先
            {
                $newimg = imagecreatetruecolor($width, $height);
                imagecopyresampled($newimg, $this->_handle, 0, 0, $sw, $sh, $width, $height, $w, ($w / $resize_ratio));
            }
        }
        else
        //不裁图
        {
            if ($ratio >= $resize_ratio)
            {
                $newimg = imagecreatetruecolor($width, ($width)/$ratio);
                imagecopyresampled($newimg, $this->_handle, 0, 0, 0, 0, $width, ($width)/$ratio, $w, $h);
            }
            if ($ratio < $resize_ratio)
            {
                $newimg = imagecreatetruecolor(($height)*$ratio, $height);
                imagecopyresampled($newimg, $this->_handle, 0, 0, 0, 0, ($height)*$ratio, $height, $w, $h);
            }
        }
        
        $this->output($newimg);
        return true;
	}
	
	/**
	 * 裁切图片
	 * 将图片从指定的坐标开始裁切到指定大小
	 *
	 * @param int $x 开始坐标x
	 * @param int $y 开始坐标y
	 * @param int $width 裁切宽度
	 * @param int $height 裁切高度
	 * @return boolean
	 */
    public function createClip($x, $y, $width = 80, $height = 80)
    {
   		if (empty($this->src) || empty($this->_handle))
		{
			$this->error = '没有指定源文件';
			return false;
		}
		
		$this->dst_width = $width;
		$this->dst_height = $height;
		
		$newimg = imagecreatetruecolor($width, $height);
		imagecopy($newimg, $this->_handle, 0, 0, $x, $y, $width, $height);
		
		$this->output($newimg);
        return true;
    }
	
	/**
	 * 输出图像
	 *
	 * @param image $img
	 */
	function output($img)
	{
		if (empty($this->src) || empty($this->_handle))
		{
			$this->error = '没有指定源文件';
			return false;
		}
		
    	$dst = empty($this->dst) ? $this->src : $this->dst;
		
    	// 修改图像大小
    	if ($this->fix)
    	{
    		$bg = imagecreatetruecolor($this->dst_width, $this->dst_height);
    		$white = imagecolorallocate($bg, 255, 255, 255);
    		imagefill($bg, 0, 0, $white);
    		
    		// 让图片居中
    		$w = imagesx($img);
    		$h = imagesy($img);
    		$pos_x = 0;
    		$pos_y = 0;
    		if ($w < $this->dst_width)
    		{
    			$pos_x = $this->dst_width / 2 - $w / 2;
    		}
    		if ($h < $this->dst_height)
    		{
    			$pos_y = $this->dst_height / 2 - $h / 2;
    		}
    		imagecopymerge($bg, $img, $pos_x, $pos_y, 0, 0, $w, $h, 100);
    		$img = $bg;
    	}
    	
		imagejpeg($img, $dst, $this->quality);
		imagedestroy($img);
		imagedestroy($this->_handle);
		
		// 为了安全，图片不需要可执行权限
		chmod($dst, 0666);
		return true;
    }
}
?>