<?php
/**
 * 数据库异常类
 * 
 * @author linln
 * @version $Id$
 */

include_once('abstract.php');

class DbException extends FwException 
{
	public function __construct($sql, $message)
	{
		$message = $sql .'->'. $message;
		$this->log($message, 'db');
		$this->output(array('msg' => '查询数据库时出现错误，请稍后再试。'));
	}
}
?>