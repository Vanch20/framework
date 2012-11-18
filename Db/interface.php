<?php
/**
 * 数据库接口
 *
 * @author linln
 * @version $Id$
 */
 
interface Db_Interface
{
	const FETCH_BOTH 	= 0;
	
	const FETCH_ASSOC 	= 1;
	
	const FETCH_ROW		= 2;
	
	const FETCH_OBJECT 	= 3;
	
	/**
	 * 初始化Dbo, 设定连接字符串
	 *
	 * @param array $connection_string
	 */
	public function init($connection_string);
	
	public function connect();
	
	/**
	 * 取得一个表中的所有字段
	 * 当字段有默认值时将默认值读出
	 * 返回的数组结构为：
	 * array('Field',	// 字段名称
	 * 		 'Type',	// 类型 (int, float, char, date)
	 * 		 'Null', 	// 是否可为空
	 * 		 'Key', 	// 是否索引
	 * 		 'Default', // 默认值
	 * 		 'Extra') 	// 其他附加信息
	 *
	 * @param string $table_name 表名
	 * @return array
	 */
	public function getFields($table_name);
	
	/**
	 * 执行一条SQL语句并返回
	 * 
	 * 
	 * @param string $sql SQL语句
	 * @param array $params 参数
	 * @return mixed
	 */
	public function query($sql, $params = null);
	
	/**
	 * 执行一条SQL语句
	 * 返回结果由SQL语句的内容决定:
	 *   1.修改或创建性语句执行成功返回true 失败返回false
	 *   2.查询语句(SELECT,SHOW,DESCRIBE,EXPLAIN)返回一个结果集对象 
	 *
	 * @param string $sql SQL语句
	 * @param array $params 参数
	 * @return mixed
	 */
	public function execute($sql, $params = null);
	
	public function fetch($result, $type = 1);
	
	public function close();
}
?>