<?php
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);

// 设定各种目录位置
define('FRAMEWORK_DIR'	, dirname(dirname(dirname(__FILE__))) .DS. 'fw3');
define('CONTROLLER_DIR'	, dirname(dirname(__FILE__)) .DS. 'controllers');
define('VIEW_DIR'		, dirname(dirname(__FILE__)) .DS. 'views');
define('MODEL_DIR'		, dirname(dirname(__FILE__)) .DS. 'models');
define('LIBRARY_DIR'	, dirname(dirname(__FILE__)) .DS. 'library');
define('ELEMENT_DIR'	, dirname(dirname(__FILE__)) .DS. 'elements');
define('TEMP_DIR'		, dirname(dirname(__FILE__)) .DS. 'temps');
define('CONFIG_DIR'		, dirname(dirname(__FILE__)) .DS. 'configs');
define('WEBROOT_DIR'	, dirname(dirname(__FILE__)) .DS. 'webroot');
define('STATIC_DIR'		, dirname(dirname(dirname(__FILE__))) .DS. 'static');

set_include_path(FRAMEWORK_DIR .PS. CONTROLLER_DIR .PS. MODEL_DIR .PS. VIEW_DIR .PS. get_include_path());

/**
 * Autoload
 */
function fw_autoload($class_name)
{
	$class_name = strtolower($class_name);
	if ('table' == $class_name || 'database' == $class_name || 'orm' == $class_name)
	{
		$class_name = 'Db' .DS. $class_name;
	}
	include_once($class_name . '.php');
}
spl_autoload_register("fw_autoload", true, true);

/**
 * Exception Handle
 */
function fw_exception_handler($e)
{
	include_once(FRAMEWORK_DIR .DS. 'Exception' .DS. 'common.php');
	throw new CommonException($e->getMessage());
}

/**
 * Get microtime for calc page load time
 */
function fw_getmicrotime()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

// Set Exception Handle
set_exception_handler("fw_exception_handler");

// Set mb functions encoding
mb_internal_encoding("UTF-8");

// Set Default Timezone
date_default_timezone_set('Asia/Shanghai');
?>