<?php
/**
 * 装载类 
 * 
 * @author linln
 * @version $Id$
 */

class Loader
{
	static public function loadClass($name)
	{
		if (preg_match("/^[a-zA-Z0-9]+$/i", $name))
	    {
	        include_once(strtolower($name).'.php');
	    }
		else 
		{
			throw new Exception('装载文件失败');
		}
	}

	/**
	 * 装载DSN
	 */
	static public function dsn()
	{
		$path = CONFIG_DIR .DS. 'database.php';
		if (file_exists($path))
		{
			include($path);
			return $dsns;
		}
		
		return null;
	}
	
	/**
	 * 装载路由表
	 */
	static public function route()
	{
		$path = CONFIG_DIR .DS. 'router.php';
		if (file_exists($path))
		{
			include_once($path);
			return isset($routes) ? $routes : array();
		}
		
		return null;
	}
	
	/**
	 * 装载DBO
	 *
	 * @param string $name - DBO名称
	 * @return object | null
	 */
	static public function dbo($name)
	{
		// 检查文件是否存在 
		$dbo_file = FRAMEWORK_DIR .DS. 'Db' .DS. 'dbo_' . $name . '.php';
		if (!file_exists($dbo_file))
		{
			return null;
		}
		
		include_once($dbo_file);
		$dbo_class = 'Dbo_' . $name;
		
		return $dbo_class;
		
		//include_once(FRAMEWORK_DIR .DS. 'Db' .DS. 'dbo.php');
	}
	
	/**
	 * 装载框架中的类库
	 *
	 * @param string $name - 类库名称
	 */
	static public function library($name)
	{
		$file = FRAMEWORK_DIR .DS. 'Library' .DS. ucfirst($name) . '.class.php';
		if (file_exists($file))
		{
			include_once($file);
		}
	}
	
	/**
	 * 装载应用程序中的类库
	 *
	 * @param string $name
	 */
	static public function lib($name)
	{
	    $file = LIBRARY_DIR .DS. strtolower($name) . '.lib.php';
	    if (file_exists($file))
		{
			include_once($file);
		}
	}
	
	static public function controller($name)
	{
		$file = CONTROLLER_DIR .DS. strtolower($name) . '.php';
		if (file_exists($file))
		{
			include_once($file);
		}
		else 
		{
			self::exception('notfind');
			throw new NotFindException();
		}
	}
	
	static public function exception($name)
	{
		$file = FRAMEWORK_DIR .DS. 'Exception' .DS. $name . '.php';
		if (file_exists($file))
		{
			include_once($file);
		}
	}
	
	static public function config($name)
	{
		$file = CONFIG_DIR .DS. $name . '.php';
		if (file_exists($file))
		{
			return(include($file));
		}
	}
}
?>