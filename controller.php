<?php
/**
 * 控制器基类
 *
 * @author linln
 * @created 2007-11-28 AM10:46:36
 */

abstract class Controller
{
	/**
	 * 当前的控制器名称
	 */
	protected $_controller = null;

	/**
	 * 当前的方法
	 */
	protected $_action = null;

	/**
	 * 与当前控制器所匹配的Model
	 *
	 * @var Object
	 */
	protected $model  = null;

	/**
	 * 所有已实例化的Model数组
	 *
	 * @var array
	 */
	protected $models = null;

	protected $view = null;

	public function __construct()
	{
		$this->view = self::getView();
	}

	public function setController($controller)
	{
		$this->_controller = str_replace('controller', '', strtolower($controller));
		$this->model = $this->getModel();
		//$this->view = self::getView();
	}

	public function setAction($action)
	{
		$this->_action = str_replace('action', '', strtolower($action));
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
	 * 得到数据库模型实例
	 *
	 * @param string $name 控制器名
	 * @return Object (Model)
	 */
	protected function getModel($name = null)
	{
		$model_name  = is_null($name) ? $this->_controller : $name;

		// 检查Model池中是否已经有这个Model
		if (isset($this->models[$model_name]))
		{
			return $this->models[$model_name];
		}

		// 拼接Model文件名
		$model_class = $model_name . 'model';
		$model_file = MODEL_DIR .DS. strtolower($model_class) . '.php';

		// 模型类文件如果存在，就实例化
		if (file_exists($model_file))
		{
			include_once($model_file);

			// 取得模型实例
			$model = new $model_class();

			// 如果指定了数据源名称就加载数据源
			if (isset($model->dsn))
			{
				if (false === $model->getDbo($model->dsn))
				{
					throw new Exception('Can\'t get DBO');
				}
			}

			// 将Model放入Model池
			$this->models[] = array('name' => $model_name, 'obj' => $model);

			return $model;
		}
		return null;
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
	 * 根据变量名称取得已经assign的变量值
	 * 如果不传入变量名称则返回所有已经assign的变量数组
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function assigned($field = '')
	{
		return $this->view->getTemplateVars($field);
	}

	/**
	 * 自动套用布局并显示视图
	 * 不传入layout时将使用默认布局
	 *
	 * @param string $tpl
	 * @param string $layout
	 */
	public function render($layout = 'default', $tpl = null)
	{
		$layout = empty($layout) ? 'default' : $layout;
		$header = '';
		$this->assign('layout', $layout);

		// 检查是否有与控制器名称相同的css文件，如有自动包含
		$style_file = WEBROOT_DIR .DS. 'styles' .DS. $layout .DS. $this->_controller . '.css';
		if (file_exists($style_file))
		{
			$header .= $this->renderStyle($this->_controller, $layout);
		}

		// 检查是否有与控制器名称相同的js文件，如有自动包含
		$js_file = WEBROOT_DIR .DS. 'js' .DS. $layout .DS. $this->_controller . '.js';
		if (file_exists($js_file))
		{
			$header .= $this->renderScript($this->_controller, $layout);
		}

		// 处理elements
		$header .= $this->renderElement('header');
		$footer = $this->renderElement('footer');

		// 拼接模板位置字符串
		$layout = 'layouts' .DS. $layout .'.html';

		// 拼接视图位置字符串
		$tpl = is_null($tpl) ? $this->_action : $tpl;
		$tpl = $this->_controller .DS. $tpl . '.html';

		$contents = $this->fetch($tpl);
		$this->assign('contents', $contents);

		$this->assign('header', $header);
		$this->assign('footer', $footer);
		$this->display($layout);
	}

	/**
	 * 制作包含样式表的字符串
	 *
	 * @param string $style - css文件名
	 * @return string
	 */
	public function renderStyle($style, $layout)
	{
		$style = '<link href="/styles/'.$layout.'/'.$style.'.css" rel="stylesheet" type="text/css" />'."\n";
		return $style;
	}

	/**
	 * 制作包含js的字符串
	 *
	 * @param string $script - js文件名
	 * @return string
	 */
	public function renderScript($script, $layout)
	{
		$script = '<script type="text/javascript" src="/js/'.$layout.'/'.$script.'.js"></script>'."\n";
		return $script;
	}

	/**
	 * 在视图中包含element。
	 *
	 * @param string $element - 要包含的element文件名，不传入则包含header和footer
	 * @return string
	 */
	public function renderElement($element)
	{
		$file = VIEW_DIR .DS. 'elements' .DS. $this->_controller .DS. $element . '.html';
		if (file_exists($file)) $contents = file_get_contents($file);
		return isset($contents) ? $contents : '';
	}

	/**
	 * 显示一个反馈信息给用户
	 *
	 * @param string $msg 反馈信息
	 * @param string $text 链接文字
	 * @param string $url 链接地址
	 */
	public function feedback($msg, $text = '确定', $url = null)
	{
		if (is_null($url))
		{
			$url = '/'. $this->_controller .'/'. $this->_action;
		}

		$this->assign('msg',  $msg);
		$this->assign('text', $text);
		$this->assign('url',  $url);
		$this->render('feedback', '');
		exit;
	}

	/**
	 * 在一个控制器中请求另一个控制器的方法
	 *
	 * @param string $ctl
	 * @param string $act
	 * @param array $params
	 */
	public function request($ctl, $act, $params = array())
	{
		$dispatcher = new Dispatcher();
		$ctl = $dispatcher->generateCtl($ctl);

		Loader::controller($ctl);
		$controller = new $ctl();
		$controller->setController($ctl);

		if (!method_exists($controller, $act))
		{
			throw new Exception("控制器{$ctl}中不存在方法{$act}");
		}

		return call_user_func_array(array($controller, $act), $params);
	}

	/**
	 * 转向另一个控制器
	 *
	 * @param string $ctl
	 * @param string $act
	 * @param array $params
	 */
	public function forward($ctl, $act, $params = array())
	{
		$dispatcher = new Dispatcher();
		$dispatcher->dispatch($ctl, $act, $params);
	}

	/**
	 * 给View返回一个处理结果数组
	 * 这里为了统一返回的结构，定义了一个方法，所有要返回给View的成功与否结果都通过这个方法返回。
	 * 一次action调用中只有第一次response的内容能够返回给view，其后的response将被忽略。
	 *
	 * @param boolean $success 返回的是否成功标示
	 * @param string $message 返回的文字描述
	 * @param array $extension 可自定义的扩展
	 *
	 * @return boolean
	 */
	public function response($success = '0', $message = '', $extension = '')
	{
		$response_name = 'fw_response';

		if (!$this->assigned($response_name))
		{
			$data = array('success' => $success, 'message' => $message);
			if (is_array($extension) && count($extension) > 0)
			{
				foreach ($extension AS $k => $v)
				{
					$data[$k] = $v;
				}
			}
			$this->assign($response_name, json_encode($data));
			return true;
		}
		return false;
	}

	/**
	 * 以Ajax方式返回给View的 response
	 *
	 * @param boolean $success 返回的是否成功标示
	 * @param string $message 返回的文字描述
	 * @param array $extension 可自定义的扩展
	 *
	 * @return boolean
	 */
	public function responseAjax($success = '0', $message = '', $extension = '')
	{
		$data = array('success' => $success, 'message' => $message);
		if (is_array($extension) && count($extension) > 0)
		{
			foreach ($extension AS $k => $v)
			{
				$data[$k] = $v;
			}
		}

		exit(json_encode($data));
	}

	public function fail($message)
	{
		Loader::exception('fail');
		throw new FailException($message);
	}

	/**
	 * 检查当前是否已经登录
	 * 已经登录返回true 没有登录返回false
	 *
	 * @return boolean
	 */
	public function isLogin($id = null)
	{
		$name = 'fw_login';
		if (!empty($id)) $name .= '_'.$id;

		// 先检查是否有保存在Cookie中的登录状态 再检查SESSION
		if (isset($_COOKIE[$name]) && isset($_COOKIE[$name.'_info']))
		{
			// 如果session已过期 复制到session
			if (!isset($_SESSION[$name]))
			{
				$_SESSION[$name] = '1';
				$_SESSION[$name.'_info'] = $_COOKIE[$name.'_info'];
			}
			return true;
		}

		return isset($_SESSION[$name]);
	}

	/**
	 * 要求登录
	 * 使用此方法将未登陆用户转向登录页
	 * 如果传入了message则显示，不传入将直接跳转
	 *
	 * @param string $uri 转向的uri
	 * @param string $message 要显示的消息
	 * @param string $id 登录标识 当一个网站有多种用户时使用 如:user,admin
	 * @param string $backward 登录后返回的地址 不传入则返回上一页
	 * @param boolean $sso 是否是集中式登录 集中式登录会影响返回上一页的url 如果此处传入true则不修改返回的url
	 */
	public function restrictLogin($uri = '/', $message = null, $id = null, $backward = null, $sso = false)
	{
		if (!$this->isLogin($id))
		{
			if (!$sso)
			{
				if (isset($_SESSION['fw_login_backward'])) unset($_SESSION['fw_login_backward']);
				$_SESSION['fw_login_backward'] = empty($backward) ? 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : $backward;
			}

			if (is_null($message))
			{
				echo '<script>window.top.location.href = "'.$uri.'";</script>';
				//header('Location: '.$uri);
			}
			else
			{
				$this->feedback($message, $uri);
			}
			exit;
		}
	}

	/**
	 * 登录后返回到登录前的页面
	 *
	 * @param boolean $direct 是否直接跳转 如果为false则返回url
	 */
	public function loginBackward($direct = true)
	{
		$back = isset($_SESSION['fw_login_backward']) ? $_SESSION['fw_login_backward'] : '/';
		unset($_SESSION['fw_login_backward']);
		if ($direct)
		{
			header('Location: '.$back);
		}
		else
		{
			return $back;
		}
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

		if ($this->isLogin($id))
		{
			$login = @$_SESSION[$name.'_info'];
			if (!is_null($login))
			{
				$login = str_replace('\"', '"', $login);
				$login = str_replace('\\\\', '\\', $login);
				return json_decode($login, 1);
			}
		}

		return null;
	}

	/**
	 * 记录日志
	 *
	 * @param string $content
	 */
	public function log($content)
	{
		$content = date('Y-m-d H:i:s') . " " . $this->_controller .'->'. $this->_action . " : " . $content . "\n";
		$log_file_path = TEMP_DIR .DS. 'logs' .DS. 'controller.log';
		$handle = fopen($log_file_path, 'at');
		fwrite($handle, $content);
		fclose($handle);
	}

	public function logBegin()
	{
		ob_start();
	}

	public function logEnd()
	{
		$this->log(ob_get_contents());
		ob_end_clean();
	}
}
?>