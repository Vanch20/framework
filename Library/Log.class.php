<?php
namespace FW\Lib;

/**
 * 日志类
 *
 * @author wanchao
 */
class Log
{
	/**
	 * 记录日志
	 *
	 * @param string $content
	 */
	public function write($content)
	{
		$content = date('Y-m-d H:i:s') . " " . $content . "\n";
		$log_file_path = TEMP_DIR .DS. 'logs' .DS. 'logs.log';
		$handle = fopen($log_file_path, 'at');
		fwrite($handle, $content);
		fclose($handle);
	}

	/**
	 * 日志开始
	 */
	public function begin()
	{
		ob_start();
	}

	/**
	 * 日志结束
	 */
	public function end()
	{
		$this->write(ob_get_contents());
		ob_end_clean();
	}
}
?>
