<?php
abstract class Dbo
{
	private static $_instance = null;
	
	public static function getInstance($scheme)
	{
		if (null === self::$_instance)
		{
            include_once('dbo_' .$scheme. ".php");
			$class = 'Dbo_'. ucfirst($scheme);
			
			self::$_instance = new $class;
        }

        return self::$_instance;
	}
}
?>