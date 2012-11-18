<?php
/**
 * http 404错误处理类
 */
include_once('abstract.php');

class NotFindException extends FwException 
{
	public function __construct()
	{
		//$this->log($message);
		$message = array('msg' => $_SERVER['REQUEST_URI']);
		$this->output($message, '404');
	}
}
?>