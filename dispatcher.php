<?php
/**
 * 分发类，负责连接控制器
 * 
 * @author linln
 * @version $Id$
 */
class Dispatcher
{
	private $default_ctl = 'index';
	private $default_act = 'index';
	private $act_prefix = 'action';
	private $ctl_suffix = 'Controller';
	
	protected $_ctl = '';
	protected $_act = '';
	protected $_params = '';
	
	public function __construct()
	{
		$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
		$this->parse($url);
	}
	
	/**
	 * 连接到指定的控制器
	 * - 当传入控制器名称或方法名称或参数时，使用传入的值
	 * - 没有传入时，使用parse解析得到的$this->_ctl _act _params
	 * - 如果没有得到解析后的_ctl, 使用全局默认值即:$this->default_ctl
	 * - 如果没有得到解析后的_act, 首先查看ctl中是否指定了默认方法_default,
	 *   如果有则调用$ctl->_default得到在控制器中指定的默认方法,
	 *   如果没有则使用全局默认值即:$this->default_act.
	 * 
	 * @return void
	 */
	public function dispatch($ctl = null, $act = null, $params = null)
	{
		$ctl = $this->generateCtl($ctl);
		$action = $this->generateAct($act);
		
		// 参数
		if (is_null($params)) $params = $this->_params;
		
		Loader::controller($ctl);
		$controller = new $ctl();
		$controller->setController($ctl);
		if (!method_exists($controller, $action) && !method_exists($controller, '__call'))
		{
			// throw new Exception("控制器【{$ctl}】的方法【{$act}】不存在");
			Loader::exception('notfind');
			throw new NotFindException();
		}
		$controller->setAction($action);
		$controller->$action($params);
	}
	
	/**
	 * 生成控制器名称
	 * 
	 * @param string $ctl 控制器名称
	 * @return string 控制器类名称(同时也是文件名)
	 */
	public function generateCtl($ctl)
	{
		if (is_null($ctl))
		{
			$ctl = empty($this->_ctl) ? $this->default_ctl : $this->_ctl;
		}
		$ctl = ucfirst(strtolower($ctl)) . $this->ctl_suffix;
		return $ctl;
	}
	
	/**
	 * 生成控制器方法名称
	 * 
	 * @param string $ctl 方法名称
	 * @return string 方法的函数名
	 */
	public function generateAct($act)
	{
		if (is_null($act))
		{
			$act = empty($this->_act) ? $this->default_act : $this->_act;
		}
		$action = $this->act_prefix . ucfirst(strtolower($act));
		return $action;
	}
	
	/**
	 * 解析Url，确定控制器和方法以及参数
	 * 
	 * @param string $url
	 * @return void
	 */
	public function parse($url)
	{
		if (!empty($url))
		{
			$router = new Router();
			$map = $router->map($url);
			unset($router);
			//print_r($map);
			// 没有使用路由的情况
			if (empty($map))
			{
				$url = explode('/', $url);
				$this->_ctl = array_shift($url);
				$this->_act = array_shift($url);
				if (is_null($this->_act)) $this->_act = $this->default_act;
				if (count($url) > 0) $this->_params = $url;
			}
			else
			{
				$this->_ctl = empty($map['controller']) ? $this->default_ctl : $map['controller'];
				$this->_act = empty($map['action']) ?  $this->default_act : $map['action'];
				$this->_params = isset($map['params']) ? $map['params'] : array();
			}
		}
	}
}
?>