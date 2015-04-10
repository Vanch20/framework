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

		// 拼接视图位置字符串
		$tpl = is_null($tpl) ? $this->_action : $tpl;
                $tpl = $this->_controller .DS. $tpl;
                
                $suffix = '.html';
                $h5_suffix = '.m';
                
                // 检查是否移动设备访问
                if ($this->chkIsMobile() && file_exists(VIEW_DIR .DS. $tpl . $h5_suffix . $suffix))
                {
                    // 如果是且移动版文件存在，则自动render 移动版本
                    $tpl .= $h5_suffix;
                    if ($layout == 'default')
                    {
                        $layout = 'mobile';
                    }
                }
                $tpl .= $suffix;
                
                // 拼接模板位置字符串
		$layout = 'layouts' .DS. $layout .'.html';
                
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
	public function responseAjax($success = false, $message = '', $extension = '')
	{
		$data = array('success' => boolval($success), 'message' => $message);
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
        
        function chkIsMobile()
        {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4)))
            {
                return true;
            }
            return false;
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