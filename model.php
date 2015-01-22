<?php
/**
 * 模型基类
 * 
 * @author linln
 */
abstract class Model
{
	/**
	 * 指定的dsn名称
	 * 在继承类中指定一个dsn，dsn的可选值在config中的dsns数组设定
	 *
	 * @var string
	 */
	public $dsn  = null;
	
	/**
	 * 当前所使用的DBO
	 * 在控制器实例化Model时根据dsn值实例化的一个DBO对象
	 *
	 * @var Object (DBO)
	 */
	protected $_dbo  = null;
	
	/**
	 * 当前的数据库连接
	 *
	 * @var Resource
	 */
	protected $_link = null;

	/**
	 * 已经取得的数据库实例数组
	 * 
	 * @var array
	 */
	static private $_instance = array();
	
	/**
	 * 解析DNS字符串
	 * $dsns数组为config/database中设定的内容
	 * 返回数组的格式详情见config/database
	 *
	 * @param string $dsn_name dsns数组的索引名称
	 * @return array
	 */
	static public function parseDsn($dsn)
	{
		$dsn = parse_url($dsn);
		return $dsn;
	}

	/**
	 * 取得一个model实例
	 * 
	 * @param string $name model名称
	 * @return Object|null
	 */
	static public function getInstance($name)
	{
		$model_class = $name . 'model';
		$model_file = MODEL_DIR .DS. strtolower($model_class) . '.php';

		if (!isset(self::$_instance[$name]))
		{
			if (file_exists($model_file))
			{
				include_once($model_file);
				self::$_instance[$name] = new $model_class();
			}
		}
		
		return @self::$_instance[$name];
	}
	
	/**
	 * 得到一个数据库实例
	 * 在控制器实例化Model时，如果已经指定dsn名称，则根据dsn实例化相应的DBO
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
			$dsn = self::parseDsn($dsns[$dsn_name]);
			$dbo_class = Loader::dbo($dsn['scheme']);
			$this->_dbo = new $dbo_class();
			$this->_dbo->init($dsn);
		}
		
		return $this->_dbo;
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
	
	/**
	 * 框架登录方法
	 * 调用此方法后就可以在控制器中使用框架登录检查方法
	 *
	 * @param array $info 保存在登录状态中的信息 有可能存在于Cookie中 要注意不要包含保密信息
	 * @param string $id 标识
	 * @param boolean $is_persist 是否保存登录状态 如果为true将在Cookie中保存登录状态1年
	 */
	public function fwLogin($info = array(), $id = null, $is_persist = false)
	{
		$name = 'fw_login';
		if (!empty($id)) $name .= '_'.$id;
		$info = json_encode($info);
		
		$_SESSION[$name] = '1';
		$_SESSION[$name.'_info'] = $info;

		$persists = 0;
		if ($is_persist) 
                {
                    $persists = strtotime('+1 year');
                }
                
		setcookie($name, '1', $persists, '/');
		setcookie($name.'_info', $info, $persists, '/');
	}
	
	/**
	 * 框架登出方法
	 *
	 * @return void
	 */
	public function fwLogout($id = null)
	{
		$name = 'fw_login';
		if (!empty($id)) $name .= '_'.$id;
		
		unset($_SESSION[$name]);
		unset($_SESSION[$name.'_info']);
		if (isset($_SESSION['fw_login_backward'])) unset($_SESSION['fw_login_backward']);
		setcookie($name, '', time(), '/');
		setcookie($name.'_info', '', time(), '/');
	}
	
	/**
	 * 记录日志
	 *
	 * @param string $content
	 */
	public function log($content)
	{
		$content = date('Y-m-d H:i:s') . " " . $this->dsn . " : " . $content . "\n";
		$log_file_path = TEMP_DIR .DS. 'logs' .DS. 'model.log';
		$handle = fopen($log_file_path, 'at');
		fwrite($handle, $content);
		fclose($handle);
	}
}
?>