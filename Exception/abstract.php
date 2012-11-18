<?php
/**
 * 异常处理基类
 * 
 * @author linln
 * @version $Id$
 */

abstract class FwException extends Exception 
{
	/*
	public function __construct($message)
	{
		parent::__construct($message);
	}
	*/

	/**
	 * 记录日志
	 * 
	 * @param string $file 错误日志文件名
	 * @param string $msg  错误内容
	 */
	public function log($message, $file = 'errors')
	{
		$path = TEMP_DIR .DS. 'logs';
		$file = DS. $file . '.log';
		if (!is_dir($path)) mkdir($path, 0755);
		
		$message = date('Y-m-d H:i:s') .' : '. $message.chr(0) . "\n";
		//iconv("UTF-8", "ISO-8859-1", $message);
		
		$handle = fopen($path . $file, 'at');
		fwrite($handle, $message);
		fclose($handle);
	}
	
	/**
	 * 输出异常信息
	 *
	 * @param string $file 显示异常信息的视图模板
	 * @param array $message 异常信息数组，格式: array('msg' => 异常说明, 'link' => 返回链接地址, 'text' => 链接文字);
	 */
	public function output($message, $file = 'default')
	{
		$path = VIEW_DIR .DS. 'exceptions' .DS;
		$file = $file . '.html';
		
		// 检查输入的信息，如果为空则使用默认值
		if (!file_exists($path . $file)) $file = 'default.html';
		if (!isset($message['msg'])) $message['msg'] = '程序出现异常';
		if (!isset($message['link'])) $message['link'] = '/';
		if (!isset($message['text'])) $message['text'] = '转到首页';
		
		// 将输入信息反馈至视图
		$contents = file_get_contents($path . $file);
		$contents = str_replace('<!--{$msg}-->', $message['msg'], $contents);
		$contents = str_replace('<!--{$link}-->', $message['link'], $contents);
		$contents = str_replace('<!--{$text}-->', $message['text'], $contents);
		
		echo $contents;
		exit;
	}
}
?>