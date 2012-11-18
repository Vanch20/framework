<?php
/**
 * 视图类
 * 
 * @author linln
 */

abstract class View
{
	private static $_instance = null;
	
	/**
	 * 使用的模板引擎，对应View目录中的模板引擎类文件名
	 */
	public static $engine = "smarty";
	
	public static function getInstance()
	{
		if (null === self::$_instance)
		{
            include_once("View/" .self::$engine. ".php");
			$class = 'View_'. ucfirst(self::$engine);
			
			self::$_instance = new $class;
        }

        return self::$_instance;
	}
}
?>