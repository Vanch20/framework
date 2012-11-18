<?php
/**
 * 提示性异常类
 * 
 * @author linln
 * @version $Id: notice.php 11 2008-04-23 06:47:25Z wanchao $
 */
include_once('abstract.php');

class NoticeException extends FwException 
{
	public function __construct($message, $link = null, $text = null)
	{
		$message = array('msg' => $message, 'link' => $link, 'text' => $text);
		$this->output($message, 'notice');
	}
}
?>