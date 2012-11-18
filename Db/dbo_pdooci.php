<?php
/**
 * DBO for Oracle Using PDO
 */

require_once('interface.php');

class Dbo_Pdooci implements Db_Interface
{
	public $connection_string = null;
	
	public $_link = null;
	
	public $_stmt = null;
	
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
		
		try
		{
			$pdo = new PDO("oci:dbname={$cs['host']}{$cs['path']};charset=utf8", $cs['user'], $cs['pass']);
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		
		$this->_link = $pdo;
		return $pdo;
	}
	
	public function query($sql, $params = null)
	{
		$this->_stmt = null;
		try
		{
			$stmt = $this->_link->prepare($sql);
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		$this->_stmt = $stmt;
		return $stmt;
	}
	
	public function fetchAssoc($stmt)
	{
		try
		{
			$stmt->execute();
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $result;
	}
	
	public function fetchAll($sql, $params = null)
	{
		$stmt = $this->query($sql);
		
		try
		{
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $data;
	}
	
	public function fetchRow($sql)
	{
		$stmt = $this->query($sql);
		
		try
		{
			$stmt->execute();
			$data = $stmt->fetchColumn();
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $data;
	}
	
	public function fetchOne($sql)
	{
		$data = $this->fetchRow($sql);
		return $data;
	}
	
	public function resultCount($sql = null)
	{
		if (is_null($sql)) return false;
		$data = $this->fetchAll($sql);
		return count($data);
	}
	
	public function fetchPage($sql, $current = 1, $display = 20)
	{
		$total = $this->resultCount($sql); // 总记录数
		if ($total < 1) return null;
		
		$page  = ceil($total / $display);  // 总页数
		$current = $current > 1 ? $current : 1;
		$current = $current > $page ? $page : $current;
		
		$offset = $display * $current - $display;
		$limit	= $display * $current;
		
		$psql = "SELECT * FROM (
					SELECT ttctmptable.*, ROWNUM RN FROM ("
             			. $sql .
					") ttctmptable
					WHERE ROWNUM <= {$limit})
				WHERE RN > {$offset}";
				
		$data = $this->fetchAll($psql);
		return array('total' => $total, 'page' => $page, 'current' => $current, 'data' =>$data);
	}
	
	public function execute($sql)
	{
		try 
		{
			$affected_rows = $this->_link->exec($sql);
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $affected_rows;
	}
	
	public function insertId()
	{
		try 
		{
			$id = $this->_link->lastInsertId();
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $id;
	}
	
	public function affectedRows()
	{
		try 
		{
			$count = $this->_stmt->rowCount();
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $count;
	}
	
	public function numRows($stmt)
	{
		try 
		{
			$count = $stmt->columnCount();
		}
		catch (PDOException $e)
		{
			throw new Exception($e);
		}
		return $count;
	}
	
	public function escapeString($value)
	{
		return ($value);
	}
	
	public function close()
	{
		return true;
	}
}
?>