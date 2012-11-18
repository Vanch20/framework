<?php
/**
 * ORM数据库对象类 
 * 
 * @author linln
 * @created 2009-07-23 16:22
 */
class Database extends Orm 
{
	/**
	 * 数据库名
	 *
	 * @var string
	 */
	private $_name = '';
	
	/**
	 * 数据库中的表
	 *
	 * @var array
	 */
	private $_tables = array();
	
	public function __construct($dsn_name)
	{
		if (false === $this->getDbo($dsn_name))
		{
			Loader::exception('db');
			throw new DbException($dsn_name, 'DSN Error, Can\'t Get DBO');
		}
		
		$this->_name = str_replace('/', '', $this->_dsn['path']);
		$tables = $this->_getTables($this->_name);
		$this->_tables = $tables;
	}
}
?>