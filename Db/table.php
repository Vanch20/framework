<?php
/**
 * ORM数据表对象类
 *
 * @author linln
 * @created 2009-03-05 18:06
 */
class Table extends Orm
{
	/**
	 * 数据表名
	 *
	 * @var string
	 */
	private $_name = '';

	/**
	 * 数据表的主键名称
	 *
	 * @var string
	 */
	private $_pk = '';

	/**
	 * 数据表中的字段
	 *
	 * @var array
	 */
	protected $_fields = array();

	/**
	 * 数据表中字段的默认值
	 *
	 * @var array
	 */
	protected $_field_defaults = array();

	/**
	 * 数据表中字段的类型
	 *
	 * @var array
	 */
	protected $_field_types = array();

	protected $_relations = array();

	public function __construct($name)
	{
		$dsn = Loader::config('table');

		if (false !== strpos($name, '.'))
		{
			$table = explode('.', $name);
			$name  = $table[1];
			$db	   = $table[0];
			unset($table);

			$dsn = @$dsn[$db][$name];
		}
		else
		{
			$dsn = @$dsn[$name];
		}

		if (empty($dsn))
		{
			Loader::exception('db');
			throw new DbException($name, 'Database Table Config Error, Can\'t Get DSN');
		}

		if (false === $this->getDbo($dsn))
		{
			Loader::exception('db');
			throw new DbException($dsn, 'DSN Error, Can\'t Get DBO');
		}

		if (isset($db)) $this->_dbo->selectDb($db);

		$this->_name = $name;
		$fields = $this->_getFields($name);

		//$this->_fields = array_fill_keys(array_keys($fields[0]), null);
		$this->_field_defaults = $fields[0];
		$this->_field_types = $fields[1];
		$this->_initFields();
		$this->_pk = $fields[2];
	}

	public function __set($field, $value)
	{
		if (key_exists($field, $this->_fields))
		{
			switch ($this->_field_types[$field])
			{
				case 'int' :
					$value = intval($value);
					break;
				case 'float' :
					$value = floatval($value);
					break;
			}
			$this->_fields[$field] = $value;
		}
		else
		{
			Loader::exception('db');
			throw new DbException($field, 'Field Doesn\'t Exist In The Table "'.$this->_name.'"');
		}
	}

	public function __get($field)
	{
		if (!empty($field) && isset($this->_fields[$field]))
		{
			return $this->_fields[$field];
		}
		return null;
	}

	/**
	 * 魔术方法
	 * findByName($value, $page_size, $options);
	 * 		查询并返回记录集中的数据, 可以传入每页显示数量来分页
	 * findRowByName($value, $options);
	 * 		查询并返回记录集中的第一行数据
	 * findOneByName($value, $field, $options);
	 * 		$value : 要匹配的值
	 * 		$field : 返回的字段或名称 如果不指定则返回主键字段
	 * 		查询并返回记录集中的第一行第一列的数据
	 * updateByName();
	 * 		根据$this->_fields中内容，更新记录集中的数据
	 * ($options中的内容详见find方法说明)
	 *
	 * @param string $func
	 * @param array $params
	 */
	public function __call($func, $params = null)
	{
		if (preg_match('/^findBy(\w+)$/', $func))
		{
			$value = $params[0];
			$page_size = isset($params[1]) ? $params[1] : null;
			$options   = isset($params[2]) ? $params[2] : null;
			$field     = strtolower(substr($func, 6));

			if (array_key_exists($field, $this->_fields))
			{
				$options['matchs'][][$field] = ' = ?';
				$options['params'][] = $value;
				return $this->find($options, $page_size);
			}
		}
		elseif (preg_match('/^findOneBy(\w+)$/', $func))
		{
			$value = $params[0];
			$options = isset($params[2]) ? $params[2] : null;
			$return	 = isset($params[1]) ? $params[1] : null;
			$field   = strtolower(substr($func, 9));

			if (empty($return)) $return = $this->_pk;

			if (array_key_exists($field, $this->_fields))
			{
				$options['matchs'][][$field] = ' = ?';
				$options['params'][] = $value;
				$options['fields'] = array($return);
				$options['limit'] = 1;
				if ($row = $this->find($options))
				{
					return $row[0][$return];
				}
				return null;
			}
		}
		elseif (preg_match('/^findRowBy(\w+)$/', $func))
		{
			$value = $params[0];
			$options = isset($params[1]) ? $params[1] : null;
			$field   = strtolower(substr($func, 9));

			if (array_key_exists($field, $this->_fields))
			{
				$options['matchs'][][$field] = ' = ?';
				$options['params'][] = $value;
				$options['limit'] = 1;
				if ($row = $this->find($options))
				{
					return $row[0];
				}
				return null;
			}
		}
		elseif (preg_match('/^updateBy(\w+)$/', $func))
		{
			//$options   = isset($params[0]) ? $params[0] : null;
			$field     = strtolower(substr($func, 8));

			if (array_key_exists($field, $this->_fields))
			{
				$options['fields'][] = $field;
				return $this->update($options);
			}
		}
	}

	/**
	 * 初始化/重置 $this->_fields
	 */
	private function _initFields()
	{
		$this->_fields = array_fill_keys(array_keys($this->_field_defaults), null); // 所有字段默认null
	}

	/**
	 * 条件查询
	 * 注意：如果传入了$page_size则$options中的limit和offset将被忽略
	 *
	 * options中可以使用的参数:
	 * 		prefix	(string): 查询前缀 如：DISTINCT, ALL
	 * 		matchs	(array)	: 查询条件数组，格式为：matchs['字段名称'] = '条件' 如不指定默认为所有字段(*)
	 * 		params	(array)	: 替换matchs中的?变量的值
	 * 		fields	(array)	: 返回的字段列表 如：fields('字段名一', '字段名二') 如不指定默认为所有字段(*)
	 * 		methods	(array)	: 指定每个字段间的链接关系 如：AND，OR；此数组第一个值指示第一和第二个参数见关系，往后以此类推。如果留空或缺少的位置将默认为AND
	 * 		group	(string): 分组查询的字段名
	 * 		having	(string): HAVING语句
	 * 		orders	(array)	: 排序方式数组 格式为：orders['字段名称'] = '排序方式(如ASC)' 如果使用函数如RAND()则字段留空 如不指定默认为数据库自动排序
	 * 		limit	(int)	: 数量限制
	 * 		offset	(int)	: 开始位置 默认为0
	 *
	 * @param array $options 其他查询选项
	 * @param int $page_size 每页显示的记录数量 如果不分页不用传入
	 * @param int $page 当前页，为零时框架将根据url来确定当前页，传入数字则手动确定当前页。默认为0
	 *
	 * @todo options 类化
	 *
	 * @return array|null
	 */
	public function find($options, $page_size = 0, $page = 0)
	{
		$page_size = intval($page_size);
		$page	   = intval($page);
		$params	   = @$options['params'];

		$sql = $this->_dbo->generateSelectSql($this->_name, $options);

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

	/**
	 * 查询并返回查询结果的第一行
	 * 此方法为find的快捷方式
	 *
	 * @param array $options 参数，详见find方法
	 * @return string|null
	 */
	public function findRow($options)
	{
		$options['limit'] = 1;
		$r = $this->find($options);
		return $r ? array_shift($r) : null;
	}

	/**
	 * 查询并返回查询结果的第一行第一列的值
	 * 此方法为find的快捷方式
	 *
	 * @param array $options 参数，详见find方法
	 * @return string|null
	 */
	public function findOne($options)
	{
		$options['limit'] = 1;
		$r = $this->find($options);
		return $r ? array_shift(array_shift($r)) : null;
	}

	/**
	 * 直接使用SQL语句查询
	 *
	 * @param string $sql
	 * @param int $page_size 每页显示的记录数量 如果不分页不用传入
	 * @param int $page 当前页，不为零时框架将根据url来确定当前页，传入数字则手动确定当前页。默认为0
	 *
	 * @return array|null
	 */
	public function findSql($sql, $page_size = 0, $page = 0)
	{
		$page_size = intval($page_size);
		$page	   = intval($page);

		if ($page_size > 0)
		{
			$sql = $this->paging($sql, $page_size, $page);
		}

		$this->_last_sql = $sql;
		$result = $this->query($sql);
		$data = $this->fetch($result);
		return empty($data) ? null : $data;
	}

	/**
	 * 执行UPDATE操作
	 * 	 使用方法：直接对类的变量进行赋值，然后通过fields是数组指定哪个变量是条件字段，
	 * 			 其他字段将被更新为类变量指定的值 例如：
	 * 			 <code>
	 * 			 $t->name = $name;
	 * 			 $t->type = $type;
	 * 			 $options['fields'][] = 'name';
	 * 		 	 $t->update($options);
	 * 			 将update xx set type = $type where name = $name;
	 * 			 </code>
	 *
	 * 	 options中可以使用的参数:
	 * 		fields	(array)	: UPDATE的条件字段
	 *		limit	(int)	: 数量限制
	 *
	 * @param array $options
	 * @return boolean
	 */
	public function update($options)
	{
		if (isset($options['fields']) && count($options['fields']) > 0)
		{
			foreach ($options['fields'] AS $field)
			{
				if (!isset($this->_fields[$field]))
				{
					Loader::exception('db');
					throw new DbException($field, '字段不存在');
				}
			}

			$keys  = array_keys($this->_fields);

			$sql = "UPDATE `{$this->_name}` SET ";
			foreach ($keys AS $key)
			{
				if ($this->_pk != $key && !is_null($this->_fields[$key]) && !in_array($key, $options['fields'])
					&& $this->_fields[$key] !== $this->_field_defaults[$key])
				{
					$sql .= "`{$key}` = '{$this->_fields[$key]}', ";
				}
			}
			$sql = substr($sql, 0, strlen($sql) - 2);
			$sql .= " WHERE ";

			foreach ($options['fields'] AS $field)
			{
				$sql .= "`{$field}` = '{$this->_fields[$field]}' AND ";
			}
			$sql = substr($sql, 0, strlen($sql) - 4);

			if (isset($options['limit']))
			{
				$sql .= " LIMIT {$options['limit']}";
			}

			$this->_last_sql = $sql;
			$this->_initFields();

			return $this->execute($sql);
		}

		return false;
	}

	/**
	 * 删除数据表中的数据
	 * 可以传入要删除的字段名和值来删除
	 * $matchs和$fields可以使用数组来进行匹配多个条件的删除
	 * 当不使用数组作为参数时，可以只传入$matchs来删除；$fields默认为表的主键
	 *
	 * @param int|array $matchs 要删除的值
	 * @param string|array $fields 字段
	 * @param int $limit 数量限制 默认0 不限
	 * @return boolean
	 */
	public function delete($matchs, $fields = '', $limit = 0)
	{
		$limit = intval($limit);

		if (!is_array($matchs))
		{
			if (empty($matchs)) return false;
			$field = empty($fields) ? $this->_pk : $fields;
			$sql = "DELETE FROM `{$this->_name}` WHERE `{$field}` = ?";
			if ($limit != 0) $sql .= " LIMIT {$limit}";
			$this->_last_sql = $sql;
			$result = $this->execute($sql, array($matchs));
		}
		else
		{
			if (count($matchs) < 1 || count($matchs) != count($fields)) return false;

			$sql = "DELETE FROM `{$this->_name}` WHERE ";
			foreach ($fields AS $field)
			{
				$sql .= " `{$field}` = ? AND ";
			}
			$sql = substr($sql, 0, strlen($sql) - 4);
			if ($limit != 0) $sql .= " LIMIT {$limit}";
			$this->_last_sql = $sql;
			$result = $this->execute($sql, $matchs);
		}

		return $result;
	}

	/**
	 * 检查一个字段是否存在指定值
	 * 存在返回true 不存在返回false
	 *
	 * options中可以使用的参数:
	 *		matchs	(array)	: 条件字段
	 *		params	(array) : 值
	 *
	 * @param array $options
	 * @return boolean
	 */
	public function exist($options)
	{
		$keys = array_keys($this->_fields);

		foreach ($keys AS $key)
		{
			if ($this->_pk != $key && !is_null($this->_fields[$key]))
			{
				$f .= ", `{$key}`";
				$v .= ", ?";
				$p[] = $this->_fields[$key];
			}
		}
	}

	/**
	 * 将fields中的值存入数据库
	 * 如果主键的值为数据表中已存在的值则UPDATE此主键对应的记录
	 * 如果主键的值为数据表中不存在的值则返回false
	 * 如果主键的值为空则INSERT一条新纪录
	 * 如果某一字段的值为null则不更新此字段
	 * 保存成功返回true, 失败返回false
	 *
	 * @return boolean
	 */
	public function save()
	{
		$keys = array_keys($this->_fields);

		if (isset($this->_fields[$this->_pk]) && !empty($this->_fields[$this->_pk]))
		{
			$options = array();
			$options['fields'][] = 'COUNT(1)';
			$options['matchs'][][$this->_pk] = '=?';
			$options['params'][] = $this->_fields[$this->_pk];
			$pk = $this->find($options);
			unset($options);

			if (empty($pk))
			{
				return false;
			}
			else
			{
				$params = array();
				$sql = "UPDATE `{$this->_name}` SET ";
				foreach ($keys AS $key)
				{
					if ($this->_pk != $key && !is_null($this->_fields[$key]) ) //&& $this->_fields[$key] !== $this->_field_defaults[$key]
					{
						$sql .= "`{$key}` = ?, ";
						$params[] = $this->_fields[$key];
					}
				}
				$sql = substr($sql, 0, strlen($sql) - 2);
				$sql .= " WHERE `{$this->_pk}` = ?";
				$params[] = $this->_fields[$this->_pk];

				$this->_last_sql = $sql;
				$result = $this->execute($sql, $params);
			}
		}
		else
		{
			$sql = "INSERT INTO `{$this->_name}`";
			$f = '';
			$v = '';
			$p = array();
			foreach ($keys AS $key)
			{
				if ($this->_pk != $key && !is_null($this->_fields[$key]))
				{
					$f .= ", `{$key}`";
					$v .= ", ?";
					$p[] = $this->_fields[$key];
				}
			}
			$sql .= '('. substr($f, 2) . ') VALUES(' . substr($v, 2) . ')';

			$this->_last_sql = $sql;
			$result = $this->execute($sql, $p);
			$this->_last_insert_id = $this->_dbo->insertId();
		}
		$this->_initFields();

		return $result;
	}

	/**
	 * 取得最后一次insert操作插入的记录的id
	 *
	 * @return int
	 */
//	public function lastInsertId()
//	{
//		return $this->_last_insert_id;
//	}

	/**
	 * 根据条件取得表中记录数
	 * 可以传入sql或option数组
	 *
	 * 注意：将忽略limit和offset
	 *
	 * @param array|string $options 数组时为option 字符串时为sql语句
	 * @return int
	 */
	public function countUsingOptions($options = null)
	{
            $count = 0;
            
            if (is_array($options))
            {
                    if (isset($options['limit'])) unset($options['limit']);
                    if (isset($options['offset'])) unset($options['offset']);

                    // 当SQL语句中包含GROUP BY语句时 使用mysql_num_rows,效率较低
                    if (isset($options['group']) && !empty($options['group']))
                    {
                            $sql = $this->_dbo->generateSelectSql($this->_name, $options);
                            $result = $this->query($sql, @$options['params']);
                            $count = $this->_dbo->getRowCount($result);
                    }
                    else
                    {
                            $options['fields'] = array('COUNT(1)');
                            $sql = $this->_dbo->generateSelectSql($this->_name, $options);
                            $result = $this->query($sql, @$options['params']);
                            $result = $this->fetch($result, 2);
                            $count = $result[0][0];
                    }
            }

            return $count;
	}
}
?>