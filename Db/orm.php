<?php
abstract class Orm
{
	/**
	 * 当前使用的解析之后的DSN
	 *
	 * @var array
	 */
	protected $_dsn = array();
	
	/**
	 * 当前所使用的DBO
	 * 在控制器实例化Model时根据dsn值实例化的一个DBO对象
	 *
	 * @var Object (DBO)
	 */
	protected $_dbo  = null;

	/**
	 * 上一次insert操作的id
	 *
	 * @var int
	 */
	protected $_last_insert_id = 0;

	/**
	 * 当前的分页状态
	 * 数组内容说明：
	 * total : 总页数
	 * page  : 当前所在页号
	 * size  : 每页显示数量
	 * sum   : 总记录数
	 *
	 * @var array
	 */
	protected $_pager = array('total' => 0, 'page' => 0, 'size' => 0, 'sum' => 0);

	/**
	 * 最后一次执行查询的SQL语句
	 *
	 * @var string
	 */
	protected $_last_sql = '';
	
	/**
	 * 得到一个数据库实例
	 * 在控制器实例化Model时，如果已经指定dsn名称，则根据dsn实例化相应的DBO
	 * 在得到实例后会连接到数据库
	 *
	 * @param string $dsn_name 
	 * @return Object (Dbo)
	 */
	public function getDbo($dsn_name)
	{
		// 从Config目录中取得数据库配置
		$dsns = Loader::dsn();
		
	    if (empty($dsn_name) || !isset($dsns[$dsn_name]))
		{
			throw new Exception('指定的DSN不存在');
		}
		
		if (is_null($this->_dbo))
		{
			$dsn = $this->parseDsn($dsns[$dsn_name]);
			$dbo_class = Loader::dbo($dsn['scheme']);
			$this->_dbo = new $dbo_class();
			$this->_dbo->init($dsn);
			$this->_dbo->connect();
		}
		
		return $this->_dbo;
	}
	
	/**
	 * 解析DNS字符串
	 * $dsns数组为config/database中设定的内容
	 * 返回数组的格式详情见config/database
	 *
	 * @param string $dsn_name dsns数组的索引名称
	 * @return array
	 */
	public function parseDsn($dsn)
	{
		$dsn = parse_url($dsn);
		$this->_dsn = $dsn;
		return $dsn;
	}
	
	/**
	 * 读取数据表中的字段
	 * 返回数组内容为
	 * array(字段与默认值对应数组, 字段与类型对应数组)
	 *
	 * @param string $table_name 表名
	 * @return array
	 */
	protected function _getFields($table_name)
	{
		$f = array();
		$v = array();
		$pk = '';
		
		$fields = $this->_dbo->getFields($table_name);
		if (!empty($fields))
		{
			foreach ($fields AS $field)
			{
				$f[] = $field['Field'];
				$v[] = is_null($field['Default']) || strlen($field['Default']) == 0 ? null : $field['Default'];
				$t[] = $field['Type'];
				if ($field['PK']) $pk = $field['Field'];
			}
		}

		return array(array_combine($f, $v), array_combine($f, $t), $pk);
	}
	
	/**
	 * 取得一个库中的所有表
	 * 返回的数组为表名组成的一维数组
	 *
	 * @param string $db_name 数据库名
	 * @return array
	 */
	protected function _getTables($db_name)
	{
		$tables = $this->_dbo->getTables($db_name);
		return $tables;
	}
	
	/**
	 * 进行一次SQL语句查询
	 * 如果SQL语句中使用了占位符，要传入的占位符的值
	 *
	 * @param string $sql
	 * @param array $params 占位符的值
	 * @return mixed
	 */
	public function query($sql, $params = null)
	{
		return $this->_dbo->query($sql, $params);
	}
	
	/**
	 * 执行一条SQL语句
	 * 如果SQL语句中使用了占位符，要传入的占位符的值
	 * 一般用来执行UPDATE和DELETE
	 * 执行成功返回true 失败返回false
	 * 
	 * @return boolean
	 */
	public function execute($sql, $params = null)
	{
		if ($this->_dbo->execute($sql, $params))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 取得记录集中的数据
	 * 可以接受stmt方式和普通方式查询返回的记录集
	 * (stmt方式为数组，普通方式为对象)
	 * 记录集不为空返回数组，为空时返回null
	 *
	 * @param object|array $result
	 * @param int $type fetch类型,对于stmt方式只能使用1和2
	 * @return array|null
	 */
	public function fetch($result, $type = 1)
	{
		while ($r = $this->_dbo->fetch($result, $type))
		{
			$data[] = $r;
		}
		return isset($data) ? $data : null;
	}

	public function fetchRow($result)
	{
		return $this->_dbo->fetch($result, 2);
	}

	public function fetchAssoc($result)
	{
		return $this->_dbo->fetch($result, 1);
	}

	/**
	 * 取得上一次查询受影响的行数
	 *
	 * @return int
	 */
	public function affectedRows()
	{
		return $this->_dbo->affectedRows();
	}
        
        /**
	 * 取得最后一次insert操作插入的记录的id
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
            if ($this->_last_insert_id)
            {
		return $this->_last_insert_id;
            }
            else
            {
                return $this->_dbo->insertId();
            }
	}

	/**
	 * 制作分页sql语句
	 *
	 * @param string $sql
	 * @param int $page_size 每页显示的记录数量 如果不分页不用传入
	 * @param int $page 当前页，不为零时框架将根据url来确定当前页，传入数字则手动确定当前页。默认为0
	 *
	 * @return string 增加了分页的sql语句
	 */
	public function paging($sql, $page_size = 0, $page = 0, $params = null)
	{
		// TODO : 去掉当前sql中的limit

		if ($page_size > 0)
		{
			$sum = self::count($sql, $params);
			if ($sum > 0)
			{
				if ($page <= 0)
				{
					$page = 1;
					if (preg_match("|/page_[0-9]+|i", $_SERVER['REQUEST_URI'], $matches))
					{
						$page = str_replace('/page_', '', $matches[0]);
					}
				}

				$total = ceil($sum / $page_size);  // 计算总页数
				$page = $page > 1 ? $page : 1;
				$page = $page > $total ? $total : $page;

				$sql = $this->_dbo->makePagingSql($sql, $page, $page_size);

				$this->_pager = array('total' => $total, 'page' => $page, 'size' => $page_size, 'sum' => $sum);
			}
		}
		
		return $sql;
	}

	/**
	 * 传入sql取得查询结果数
	 *
	 * 注意：将忽略limit和offset
	 *
	 * @param string $sql sql语句
	 * @return int
	 */
        public function count($sql, $params = null)
	{
            return $this->_dbo->resultCount($sql, $params);
	}
        
	/**
	 * 取得上一次查询的分页状态
	 *
	 * @return array
	 */
	public function pager()
	{
		return $this->_pager;
	}

	/**
	 * 开始事务
	 *
	 * @return boolean
	 */
	public function transactionBegin()
	{
		return $this->_dbo->transactionBegin();
	}

	/**
	 * 提交事务
	 *
	 * @return boolean
	 */
	public function transactionCommit()
	{
		return $this->_dbo->transactionCommit();
	}

	/**
	 * 回滚事务
	 *
	 * @return boolean
	 */
	public function transactionRollback()
	{
		return $this->_dbo->transactionRollback();
	}

	/**
	 * 结束事务
	 *
	 * @return boolean
	 */
	public function transactionEnd()
	{
		return $this->_dbo->transactionEnd();
	}
        
        /**
	 * 开启调试模式
	 *
	 */
	public function debug()
	{
		echo $this->_last_sql;
	}
}
?>