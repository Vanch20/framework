<?php
/**
 * DBO for Oracle Using OCI
 * 
 * @author linln
 * @version $Id$
 */

require_once('interface.php');

class Dbo_Oracle implements Db_Interface
{
	public $connection_string = null;
	
	public $_link = null;
	
	public function init($cs)
	{
		$this->connection_string = $cs;
	}
	
	public function connect()
	{
		$cs = $this->connection_string;
		if (is_null($cs))
		{
			throw new Db_Exception('Can\'t connect to database, connection string is empty.');
		}
		
		$conn = oci_connect($cs['user'], $cs['pass'], '//'.$cs['host'].'/demo1');
		if (!$conn)
		{
		  $e = oci_error();
		  throw new Exception(htmlentities($e['message']));
		}
		
		$this->_link = $conn;
		return $conn;
	}
	
	/**
	 * 查询
	 * 执行一个SQL语句查询并返回Statement Identifier
	 *
	 * @param string $sql
	 * @return Object
	 */
	public function query($sql, $params = null)
	{
		$stmt = oci_parse($this->_link, $sql);
		if (!$stmt)
		{
			$e = oci_error();
			throw new Exception(htmlentities($e['message']));
		}
		return $stmt;
	}
	
	public function fetchAssoc($stmt)
	{
		$result = oci_fetch_assoc($stmt);
		if (!$result)
		{
			$e = oci_error();
			throw new Exception(htmlentities($e['message']));
		}
		return $result;
	}
	
	public function fetchOne($sql)
	{
		$stmt = $this->query($sql);
		
		while ($row = $this->fetchAssoc($stmt))
		{
			$data[] = $row;
		}
		return isset($data[0]) ? null : $data[0];
		
	}
	
	public function fetchAll($sql)
	{
		$stmt = $this->query($sql);
		
		while ($row = $this->fetchAssoc($stmt))
		{
			$data[] = $row;
		}
		return isset($data) ? null : $data;
	}
	
	public function fetchPage($sql, $current = 1, $display = 20)
	{
		$total = $this->resultCount($sql); // 总记录数
		if ($total < 1) return null;
		
		$page  = ceil($total / $display);  // 总页数
		$current = $current > 1 ? $current : 1;
		$current = $current > $page ? $page : $current;
		
		$offset = $display * $current - $display;
		$sql .= ' LIMIT ' . $offset . ', ' . $display;
		$data = $this->fetchAll($sql);
		
		return array('total' => $total, 'page' => $page, 'current' => $current, 'data' =>$data);
	}
	
	public function execute($sql)
	{
		$stmt = $this->query($sql);
		$result = oci_execute($stmt);
		if (!$result)
		{
		  $e = oci_error();
		  throw new Exception(htmlentities($e['message']));
		}
		return $result;
	}
	
	public function resultCount($sql = null)
	{
		if (is_null($sql)) return false;
		
		//当SQL语句中包含GROUP BY语句时 使用mysql_num_rows
		if (preg_match("|GROUP\s*BY|ims", $sql) > 0)
		{
			$result = $this->query($sql);
			return $this->numRows($result);
		}
		else
		{
			$sql = preg_replace("|SELECT (.*) FROM|ims", 'SELECT COUNT(1) FROM', $sql);
			//$sql = str_replace(trim(str_replace('SELECT' , '', substr($sql, 0, strpos($sql, 'FROM')))), ' COUNT(1) ' ,$sql);
			return $this->fetchOne($sql);
		}
	}
	
	public function affectedRows()
	{
		//return oci_num_rows();
	}
	
	public function numRows($stmt)
	{
		return oci_num_fields($stmt);
	}
	
	public function insertId()
	{
		return $this->_link->insert_id;
	}
	
	public function escapeString($value)
	{
		return ($value);
	}
	
	public function close()
	{
		//$this->_link->close();
	}
}
?>