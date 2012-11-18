<?php
/**
 * 通用异常类
 * 
 * @author linln
 * @version $Id$
 */

include_once('abstract.php');

class CommonException extends FwException 
{
	public function __construct($message, $link = null, $text = null)
	{
		$this->log($message);
		$message = array('msg' => $message, 'link' => $link, 'text' => $text);
		$this->output($message);
	}
}
?>