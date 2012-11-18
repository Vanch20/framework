<?php
namespace FW\Lib;

/**
 * 上传文件类
 *
 * @example
 * <code>
 * $upload = new \Fw\Lib\Upload();
 * $upload->setType('swf');
 * $result = $upload->process('file', $path);
 * if ($result)
 * {
 *		$filename = $upload->getUploadFileName();
 * }
 * else
 * {
 *		$message = $upload->getError();
 * }
 * </code>
 */
class Upload
{
	static public $TYPE_IMG = 'jpg,gif,png,jpeg';
	static public $TYPE_ALL = 'jpg,gif,png,jpeg,bmp,rar,zip,dat,gz,txt,tar,exe,doc,xls,ppt';

	private $img_type = 'jpg,gif,png,jpeg';
	private $all_type = 'jpg,gif,png,jpeg,bmp,rar,zip,dat,gz,txt,tar,exe,doc,xls,ppt';

	private $_types	  = null;	// 手动设定允许的扩展名
	private $_prefix  = 'R';	// 文件名前缀

	public $file_name;		// 返回的文件名
	public $ori_file_name;	// 原文件名
	public $error;

	/**
	 * 上传文件
	 *
	 * @param string $source FORM中FILE字段的名称
	 * @param string $target 上传到的路径
	 * @param string $type 限制文件扩展名
	 * @return boolean
	 */
	function process($source, $target, $type = '')
	{
		//如果传入参数为空，返回
		if (empty($source) or empty($target))
		{
			$this->error = '传入参数为空';
			return false;
		}
		else
		{
			$tmp_name = $_FILES[$source]['tmp_name'];

			if (is_uploaded_file($tmp_name))
			{
				$source_ext = pathinfo($_FILES[$source]['name']);
				$this->ori_file_name = $source_ext['basename'];
				$source_ext = strtolower($source_ext['extension']);
				$this->file_name = uniqid($this->_prefix) . '.' . $source_ext;

				//限制文件类型
				$is_allowed_type = false;
				switch ($type)
				{
					case 'img' :
						$allowed_type = explode(',', $this->img_type);
						break;
					case 'all' :
						$allowed_type = explode(',', $this->all_type);
						break;
					default :
						$allowed_type = explode(',', $this->_types);
				}

				foreach ($allowed_type as $item)
				{
					if (strtolower($item) == $source_ext) $is_allowed_type = true;
				}

				if (!$is_allowed_type)
				{
					$this->error = '不被允许的文件类型';
					return false;
				}

				//如果目录不存在，创建
				if (!is_dir($target))
				{
					if (!mkdir($target, 0766, true))
					{
						$this->error = '创建目录失败';
						return false;
					}
				}

				//如果目标目录不是以'/'结尾，补'/'
				if (substr($target, -1, 1) != DS)
				{
					$target .= DS;
				}

				if (move_uploaded_file($tmp_name, $target . $this->file_name))
				{
					chmod($target . $this->file_name, 0666);
					return true;
				}
				else
				{
					$this->error = '移动临时文件时出错';
					return false;
				}
			}
			else
			{
				$this->error = '没有找到要上传的文件';
				return false;
			}
		}
	}

	/**
	 * 设定允许的扩展名
	 *
	 * @param string $types
	 */
	public function setType($types = '')
	{
		if (empty($types)) return false;
		$this->_types = $types;
		return true;
	}

	/**
	 * 设定上传后文件名的前缀
	 *
	 * @param string $prefix
	 * @return boolean
	 */
	public function setPrefix($prefix)
	{
		if (!preg_match('/^[a-z0-9_]+$/i'))
		{
			$this->error = '前缀包含不被允许的字符';
			return false;
		}
		$this->_prefix = $prefix;
		return true;
	}

	/**
	 * 取得上一次上传后的文件名称
	 *
	 * @return string
	 */
	public function getUploadFileName()
	{
		return $this->file_name;
	}

	public function getOrignFileName()
	{
		return $this->ori_file_name;
	}

	/**
	 * 取得上一个错误
	 *
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}
}
?>