<?php
/**
 * Mysqli 连接MySQL类
 *
 * 支持两种使用方式：
 * 1. 普通方式
 *    执行query,execute,fetchAll,fetchOne时只要传入sql语句即可。
 * 2. prepare方式
 *    执行query,execute,fetchAll,fetchOne时传入sql语句和params参数
 * params定义如下：
 *   a. 当params是一维数组时: params作为prepare的参数直接使用。
 *   b. 当params是二维数组时: params的每一维将分别作为prepare的参数使用
 *
 * @author linln
 */

require_once('interface.php');

class Dbo_Mysqli implements Db_Interface
{
	/**
	 * 使用的连接字符串
	 *
	 * @var array
	 */
	public $connection_string = null;

	/**
	 * Mysqli resource
	 *
	 * @var resource
	 */
	public $_link = null;

	/**
	 * Mysqli statement handle
	 *
	 * @var statement
	 */
	public $_stmt = null;

	/**
	 * 初始化Dbo, 设定连接字符串
	 *
	 * @param array $connection_string
	 */
	public function init($cs)
	{
		$this->connection_string = $cs;
	}

	/**
	 * 建立连接
	 *
	 * @return boolean
	 */
	public function connect()
	{
		$cs = $this->connection_string;
		Loader::exception('db');

		if (is_null($cs))
		{
			throw new DbException('Can\'t connect to database, connection string is empty.');
		}
		$this->_link = mysqli_init();

		// Can add some option here

		if (isset($cs['path']) && !empty($cs['path'])) $db = str_replace('/', '', $cs['path']);

		if (isset($db))
		{
			$this->_link->real_connect($cs['host'], $cs['user'], $cs['pass'], $db);
		}
		else
		{
			$this->_link->real_connect($cs['host'], $cs['user'], $cs['pass']);
		}

		if ($this->_link->errno)
		{
			throw new DbException('', $this->_link->error);
		}

		if (isset($cs['fragment']))
		{
			$sql = "SET NAMES {$cs['fragment']}";
			$this->_link->query($sql);
			if ($this->_link->errno)
			{
				throw new DbException('', $this->_link->error);
			}
		}

		return $this->_link;
	}

	public function selectDb($db)
	{
		return $this->_link->select_db($db);
	}

	/**
	 * 取得一个表中的所有字段
	 * 当字段有默认值时将默认值读出
	 * 类型为timestamp的字段将被忽略
	 * 返回的数组结构为：
	 * array('Field', 'Type', 'Null', 'Key', 'Default', 'Extra', 'PK')
	 *
	 * @param string $table_name 表名
	 * @return array
	 */
	public function getFields($table_name)
	{
		$table_name = trim($table_name);
		$sql = "SHOW COLUMNS FROM {$table_name}";
		$result = $this->query($sql);
		while ($row = $this->fetch($result, self::FETCH_ASSOC))
		{
			// 判断是否是主键
			$row['PK'] = false;
			if ($row['Key'] == 'PRI')
			{
				$row['PK'] = true;
			}

			if ($row['Default'] == 'CURRENT_TIMESTAMP')
			{
				$row['Default'] = '';
			}

			// 根据返回的数据类型生成通用类型
			if (preg_match('/^(\w*)int\(/i', $row['Type']))
			{
				$type = 'int';
			}
			elseif (preg_match('/^(demical|float|double|real)\(/i', $row['Type']))
			{
				$type = 'float';
			}
			elseif (preg_match('/^(date\(|datetime\(|timestamp)/i', $row['Type']))
			{
				$type = 'date';
			}
			else
			{
				$type = 'char';
			}
			$row['Type'] = $type;

			$columns[] = $row;
		}
		return $columns;
	}

	/**
	 * 取得一个库中的所有表
	 * 返回的数组为表名组成的一维数组
	 *
	 * @param string $db_name 数据库名
	 * @return array
	 */
	public function getTables($db_name)
	{
		$db_name = trim($db_name);
		$sql = "SHOW TABLES FROM {$db_name}";
		$result = $this->query($sql);
		while ($row = $this->fetch($result, self::FETCH_ROW))
		{
			$tables[] = $row;
		}
		return $tables;
	}

	private function bindParam($params)
	{
		$params_count = $this->_stmt->param_count;
		if ($params_count < 1) return false;

		if ($params_count != count($params))
		{
			throw new Exception('绑定的参数个数不符');
		}

		$types = '';
		$vals = array();
		foreach ($params AS $k => $val)
		{
			if (is_int($val))
				$types .= 'i';
			elseif (is_double($val))
				$types .= 'd';
			else
				$types .= 's';

			// 自PHP5.3起，mysqli_stmt_bind_param强制要求传入的参数为引用值
			$vals[$k] = $val;
			$params[$k] = &$vals[$k];
		}

		array_unshift($params, $types);
		array_unshift($params, $this->_stmt);
		return call_user_func_array('mysqli_stmt_bind_param', $params);
	}

	/**
	 * 开始事务
	 *
	 * @return boolean
	 */
	public function transactionBegin()
	{
		return $this->_link->autocommit(false);
	}

	/**
	 * 提交事务
	 *
	 * @return boolean
	 */
	public function transactionCommit()
	{
		return $this->_link->commit();
	}

	/**
	 * 回滚事务
	 *
	 * @return boolean
	 */
	public function transactionRollback()
	{
		return $this->_link->rollback();
	}

	/**
	 * 结束事务
	 *
	 * @return boolean
	 */
	public function transactionEnd()
	{
		return $this->_link->autocommit(true);
	}

	/**
	 * 执行一次SQL语句查询
	 * 如果传入参数将使用stmt方式查询。
	 * 返回结果由SQL语句的内容决定:
	 *   1.修改或创建性语句执行成功返回true 失败返回false
	 *   2.查询语句(SELECT,SHOW,DESCRIBE,EXPLAIN)返回一个包含查询结果的数组
	 *
	 * @param string $sql SQL语句
	 * @param array $params 参数
	 * @return mixed
	 */
	public function query($sql, $params = null)
	{
		$result = $this->execute($sql, $params);

		if (!is_null($params))
		{
			$sql = trim($sql);
			if ('SELECT' == strtoupper((substr($sql, 0, 6))) || 'SHOW' == strtoupper((substr($sql, 0, 4))) || 'DESCRIBE' == strtoupper((substr($sql, 0, 8))) || 'EXPLAIN' == strtoupper((substr($sql, 0, 7))))
			{
				unset($result);
				$this->_stmt->store_result();

				if ($this->_stmt->num_rows > 0)
				{
					// 为提高查询效率 在此进行bind_result
					$meta = $this->_stmt->result_metadata();
					while ($columnName = $meta->fetch_field())
					{
						$columns[] = &$result[$columnName->name];
					}
					call_user_func_array(array($this->_stmt, 'bind_result'), $columns);
				}
				else
				{
					$result = null;
				}

				if ($this->_stmt->errno)
				{
					Loader::exception('db');
					throw new DbException($sql, $this->_stmt->error);
				}
			}
		}

		return $result;
	}

	/**
	 * 执行一条SQL语句
	 * 如果传入参数将使用stmt方式查询。
	 * 返回结果由SQL语句的内容决定:
	 *   1.修改或创建性语句执行成功返回true 失败返回false
	 *   2.查询语句(SELECT,SHOW,DESCRIBE,EXPLAIN)返回一个结果集对象
	 *
	 * @param string $sql SQL语句
	 * @param array $params 参数
	 * @return mixed
	 */
	public function execute($sql, $params = null)
	{
		//$this->connect();

		if (!is_null($params))
		{
			Loader::exception('db');
			$this->_stmt = $this->_link->prepare($sql);
			if ($this->_link->errno) throw new DbException($sql, $this->_link->error);

			$this->bindParam($params);
			$result = $this->_stmt->execute();
			if ($this->_stmt->errno) throw new DbException($sql, $this->_stmt->error);
		}
		else
		{
			$result = $this->_link->query($sql);

			if ($this->_link->errno)
			{
				Loader::exception('db');
				throw new DbException($sql, $this->_link->error);
			}
		}
		return $result;
	}

	/**
	 * 取得记录集中的数据
	 * 可以接收stmt和普通两种记录集
	 * 抓取类型详情查看dbo_interface中的常量
	 * 如果是stmt方式则只能接受1和2两种类型
	 *
	 * @param resource $result 记录集
	 * @param int $type 抓取类型
	 * @return mixed
	 */
	public function fetch($result, $type = 1)
	{
		// stmt方式
		if (is_array($result))
		{
			if (!$this->_stmt->fetch())
			{
				$data = null;
			}
			else
			{
				if ($type == 2)
				{
					foreach ($result AS $v) $data[] = $v;
				}
				else
				{
					foreach ($result AS $k => $v) $data[$k] = $v;
				}
			}
		}

		// 普通方式
		elseif (is_object($result))
		{
			switch ($type)
			{
				case 1 :
					$data = $result->fetch_assoc();
					break;
				case 2 :
					$data = $result->fetch_row();
					break;
				case 3 :
					$data = $result->fetch_object();
					break;
				case 0 :
				default:
					$data = $result->fetch_array();
			}
		}
		else
		{
			return null;
		}
		return $data;
	}

	public function affectedRows()
	{
		return $this->_link->affected_rows;
	}

	public function getRowCount($result)
	{
		if (is_object($result) && 'mysqli_result' == get_class($result))
		{
			return $result->num_rows;
		}
		else
		{
			return $this->_stmt->num_rows;
		}
	}

	/**
	 * 取得上一个INSERT语句插入的记录ID
	 *
	 */
	public function insertId()
	{
		return $this->_link->insert_id;
	}

	/**
	 * 取得一个SELECT语句查询结果的个数，主要是分页用
	 *
	 * @param string $sql
	 * @return false | int
	 */
	public function resultCount($sql, $params = null)
	{
		if (empty($sql)) return false;

		//当SQL语句中包含GROUP BY语句时 使用mysql_num_rows
		if (preg_match("/(GROUP\s*BY)|(DISTINCT)/ims", $sql) > 0)
		{
			$result = $this->query($sql, $params);
			return $this->getRowCount($result);
		}
		else
		{
			$sql = preg_replace("|[\n\r\t]|ims", ' ', $sql);
			$sql = preg_replace("|SELECT ((?!FROM).)* FROM|ims", 'SELECT COUNT(1) FROM', $sql, 1);
			//$sql = str_replace(trim(str_replace('SELECT' , '', substr($sql, 0, strpos($sql, 'FROM')))), ' COUNT(1) ' ,$sql);
			$result = $this->query($sql, $params);
			$data = $this->fetch($result, self::FETCH_ROW);
			return $data[0];
		}
	}

	/**
	 * 生成SELECT用sql语句
	 *
	 * @param string $table 表名
	 * @param array $options
	 */
	public function generateSelectSql($table, $options = null)
	{
		// 拼接前缀
		$prefix = isset($options['prefix']) ? $options['prefix'] : '';

		// 拼接返回字段列表
		$fields = '*';
		if (isset($options['fields']) && is_array($options['fields']))
		{
			$fields = '';
			foreach ($options['fields'] AS $field)
			{
				if (false === strpos($field, '('))
				{
					$fields .= ", `{$field}`";
				}
				else
				{
					$fields .= ", {$field}";
				}
			}
			$fields = substr($fields, 2);
		}

		// 拼接查询条件
		$matchs = '';
		if (isset($options['matchs']) && is_array($options['matchs']))
		{
			$matchs = ' WHERE ';
			$i = 0;
			foreach ($options['matchs'] AS $k => $match_array)
			{
				// 判断连接方式（AND或OR）
				$method = 'AND';
				if (isset($options['methods'][$i]) && !empty($options['methods'][$i]))
				{
					$method = $options['methods'][$i];
				}

				$key = key($match_array);
				$match = $match_array[$key];
				if (false === strpos($key, '('))
				{
					$matchs .= "`{$key}` {$match} {$method} ";
				}
				else
				{
					// 如果不是字段名 不加``
					$matchs .= "{$key} {$match} {$method} ";
				}
				$i++;
			}
			$matchs = substr($matchs, 0, strlen($matchs)-4);
		}

		// 拼接Group条件
		$group = isset($options['group']) ? ' GROUP BY `'.$options['group'].'`' : '';

		// 拼接排序条件
		$orders = '';
		if (isset($options['orders']) && is_array($options['orders']))
		{
			$orders = ' ORDER BY ';
			foreach ($options['orders'] AS $key => $order)
			{
				// 当使用函数作为排序条件时 主键为空
				if (!empty($key))
				{
					$orders .= "`{$key}` {$order}, ";
				}
				else
				{
					$orders .= "{$order}, ";
				}
			}
			$orders = substr($orders, 0, strlen($orders)-2);
		}

		$sql = "SELECT {$prefix} {$fields} FROM `{$table}` {$matchs} {$group} {$orders}";

		if (isset($options['having'])) $sql .= ' HAVING '.$options['having'];

		if (isset($options['offset']))
		{
			$sql .= ' LIMIT '.$options['offset'];
			if (isset($options['limit'])) $sql .= ' ,'.$options['limit'];
		}
		else
		{
			if (isset($options['limit'])) $sql .= ' LIMIT '.$options['limit'];
		}

		return $sql;
	}

	/**
	 * 制作Model中fetchPage方法所用得sql语句
	 * 根据所要显示的页数和每页显示记录数制作分页用的sql语句
	 *
	 * @param string $sql 原始要查询的sql语句
	 * @param int $page 当前所在页数
	 * @param int $size 每页显示的记录数
	 *
	 * @return string
	 */
	public function makePagingSql($sql, $page, $size)
	{
		$offset = $size * $page - $size;
		$sql .= ' LIMIT ' . $offset . ', ' . $size;
		return $sql;
	}

	public function getErrorCode()
	{
		return $this->_link->errno;
	}

	public function getErrorInfo()
	{
		return $this->_link->error;
	}

	public function close()
	{
		$this->_link->close();
	}
}
?>