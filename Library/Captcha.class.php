<?php
namespace FW\Lib;

/**
 * 验证码类
 *
 * 依赖GD
 */
class Captcha
{
	/**
	 * 初始化一个验证码
	 *
	 * @param string $id 验证码标识
	 * @return boolean
	 */
	public function init($id)
	{
		if (empty($id)) return false;
		$_SESSION['fw_captcha_'.$id] = rand(1000, 9999);
		return true;
	}
	
	/**
	 * 取得一个已经初始化了的验证码的值
	 *
	 * @param string $id
	 * @return string
	 */
	public function get($id)
	{
		return @$_SESSION['fw_captcha_'.$id];
	}
	
	/**
	 * 输出一个已经初始化的验证码
	 *
	 * @param string $id 验证码标识
	 * @param int $type 输出类型 1-swf 2-img
	 * @param int $w 输出图像宽度
	 * @param int $h 输出图像高度
	 * @return void
	 */
	public function output($id, $type = '2', $w = 60, $h = 20)
	{
		if (empty($id)) exit;
		
		$code = @$_SESSION['fw_captcha_'.$id];
		switch ($type)
		{
			case '1' :
				Loader::library('swf');
				$swf = new Swf();
				$swf->output($_SESSION['fw_captcha_'.$id]);
				unset($swf);
				break;
				
			case '2' :
				Header("Content-type: image/PNG");
				$im = imagecreate($w, $h);
				$back = ImageColorAllocate($im, 245, 245, 245);
				imagefill($im, 0, 0, $back); //背景
				
				$font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255));
				imagestring($im, 5, 12, 1, $code, $font);
				
				/*for ($i = 0; $i < count($code); $i++)
				{
					$font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255));
					imagestring($im, 5, 2+$i*10, 1, $code[$i], $font);
				}*/
				
				// 干扰象素
				for ($i = 0; $i < 200; $i++)
				{ 
					$randcolor = ImageColorallocate($im, rand(0,255), rand(0,255), rand(0,255));
					imagesetpixel($im, rand()%70 , rand()%30 , $randcolor);
				} 
				ImagePNG($im);
				ImageDestroy($im);
				
				break;
		}
		exit;
	}
	
	/**
	 * 清除验证码
	 *
	 * @param int $id
	 * @return boolean
	 */
	public function clean($id)
	{
		if (empty($id)) return false;
		unset($_SESSION['fw_captcha_'.$id]);
		return true;
	}
}
?>