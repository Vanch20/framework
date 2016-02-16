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
    
    protected $_bind_params = null;
	
	public function __construct($dsn_name)
	{
		if (false === $this->getDbo($dsn_name))
		{
			Loader::exception('db');
			throw new DbException($dsn_name, 'DSN Error, Can\'t Get DBO');
		}
		
		$this->_name = str_replace('/', '', $this->_dsn['path']);
	}
    
    public function find($sql, $params, $page_size = 0, $page = 0)
	{
		$page_size = intval($page_size);
		$page	   = intval($page);

		// 如果要分页
		if ($page_size > 0)
		{
			$sql = $this->paging($sql, $page_size, $page, $params);
		}
        
		$this->_last_sql = $sql;
		$result = $this->query($sql, $params);
		$data = $this->fetch($result);
		return empty($data) ? null : $data;
	}
}
?>