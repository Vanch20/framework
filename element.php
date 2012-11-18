<?php
/**
 * element类 在视图中包含元件
 * 
 * @author linln
 * @version $Id$
 */

class Element
{
	/**
	 * 视图实例
	 *
	 * @var object
	 */
	protected $view;
	
	/**
	 * element实例名称
	 *
	 * @var string
	 */
	protected $element;
	
	/**
	 * 执行的方法名称
	 *
	 * @var string
	 */
	protected $action;
	
	public function setElement($name)
	{
		$this->element = $name;
		$this->view = self::getView();
	}
	
	public function setAction($name)
	{
		$this->action = $name;
	}
	
	/**
	 * 得到视图的实例
	 *
	 * @return class
	 * @return Object (View)
	 */
	protected static function getView()
	{
		return View::getInstance();
	}

	/**
	 * 视图实例display方法的快捷方式
	 *
	 * @param string $tpl
	 * @return void
	 */
	public function display($tpl)
	{
		$this->view->display($tpl);
	}
	
	/**
	 * 视图实例fetch方法的快捷方式
	 *
	 * @param string $tpl
	 */
	public function fetch($tpl)
	{
		return $this->view->fetch($tpl);
	}
	
	/**
	 * 视图实例assign方法的快捷方式
	 *
	 * @param string $field
	 * @param string $$value
	 */
	public function assign($field, $value)
	{
		return $this->view->assign($field, $value);
	}
	
	/**
	 * 默认的方法，将参数赋值到视图并显示视图
	 *
	 * @param string $tpl   要显示的视图: 不传入则显示当前element目录中与当前action同名的视图
	 * @param array $params 视图中的参数: array('参数名称'=>'值')
	 */
	public function render($params = array(), $tpl = null)
	{
		$tpl = is_null($tpl) ? $this->action : $tpl;
		$file = VIEW_DIR .DS. 'elements' .DS. $this->element .DS. $tpl .'.html';
		
		if (file_exists($file))
		{
			if (!empty($params))
			{
				$keys = array_keys($params);
				for ($i = 0; $i < count($params); $i++)
				{
					$this->assign($keys[$i], $params[$keys[$i]]);
				}
			}
			
			$this->display($file);
		}
	}
	
	public function request($ctl, $act, $params = array())
	{
		$dispatcher = new Dispatcher();
		$ctl = $dispatcher->generateCtl($ctl);
		
		Loader::controller($ctl);
		$controller = new $ctl();
		$controller->setController($ctl);
		
		if (!method_exists($controller, $act))
		{
			Loader::exception('notfind');
			throw new NotFindException();
		}
		return $controller->$act($params);
	}
	
	/**
	 * 取得当前登录信息
	 * 注意：返回的信息是当前用户登录时存放在SESSION中的信息
	 * 		如果在登录后修改了这些信息，此方法中返回的内容不会
	 * 		与之同步。
	 */
	public function getLoginInfo($id = null)
	{
		$name = 'fw_login';
		if (!empty($id)) $name .= '_'.$id;

		$login = @$_SESSION[$name.'_info'];
		if (!is_null($login))
		{
			$login = str_replace('\"', '"', $login);
			return json_decode($login, 1);
		}
		return null;
	}
}
?>