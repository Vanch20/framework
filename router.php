<?php
/**
 * 路由类
 *
 * @author linln
 */

class Router
{
	protected $_url_delimiter = '/';
	protected $_var_delimiter = ':';

    /**
     * 默认的路由
     *
     * @var unknown_type
     */
	protected $_default = array(':controller/:action/*',
								array('controller' => ':controller', 'action' => ':action')
								);

	/**
	 * 重新组合后的路由数组
	 *
	 * @var array
	 */
	private $_divided_routes = array();

	public function __construct()
	{
		$this->divideRoutes();
	}

	/**
	 * 使用给定的路由匹配Url
	 *
	 * @param array $url   url
	 * @param array $route 路由
	 * @param array $rules 匹配规则
	 *
	 * @return boolean 匹配成功返回true 失败返回false
	 */
	public function match($url, $route, $rules)
	{
		//if (count($url) < count($route)) return false;

		foreach ($route AS $pos => $val)
		{
			// url规则如果是变量的情况(以':'开头)
			if (substr($val, 0, 1) == $this->_var_delimiter)
			{
				if ($rules[$val] == '*') $rules[$val] = '.'.$rules[$val];
				$rule = '/' . $rules[$val] . '/i';
				if (isset($url[$pos]))
				{
					if (!preg_match($rule, $url[$pos])) return false;
				}
				else
				{
					if ($rules[$val] != '.*') return false;
				}
			}
			// 是表达式的情况(*或者正则)
			else
			{
				if ('*' == $val) return true;
				if (!preg_match('/' . $val . '/i', $url[$pos])) return false;
				//if ($url[$pos] != $val) return false;
			}
		}

		return true;
	}

	/**
	 * 执行路由
	 *
	 * @param string $url - 访问的Url
	 * @return array 包含使用的控制器和方法的数组
	 */
	public function map($url)
	{
		$url = trim($url, $this->_url_delimiter);
		$url = explode($this->_url_delimiter, $url);

		$routes = $this->_divided_routes;
		$map = null;
		$route = null;

		for ($i = 0; $i < count($routes['routes']); $i++)
		{
			if ($this->match($url, $routes['routes'][$i], $routes['rules'][$i]))
			{
				$map = $routes['maps'][$i];
				$route = $routes['routes'][$i];
				break;
			}
		}


		// 没有任何路由被匹配
		if (empty($map))
		{
			//header("HTTP/1.0 404 Not Found");
			throw new Exception('HTTP 404');
			// TODO : 转向404错误页
		}

		// 将map表中的变量(默认情况下':'开头的字段)，替换为url中的值
		$map_flip = array_flip($map);
		//var_dump($route);
		foreach ($route AS $pos => $val)
		{
			if (substr($val, 0, 1) == $this->_var_delimiter)
			{
				// 如果有在route里定义过的变量，赋值到map
				// 目前其实只有两个: controller和action
				if (isset($map_flip[$val]))
				{
					$map[$map_flip[$val]] = isset($url[$pos]) ? $url[$pos] : '';
					if (isset($url[$pos])) unset($url[$pos]);
				}
				// 如果没有在route字符串里定义过，赋值到参数
				elseif (isset($url[$pos]))
				{
					$params[] = $url[$pos];
					unset($url[$pos]);
				}
			}
		}

		// 如果url还有没被匹配的数据 放在params中
		foreach ($url AS $val)
		{
			$params[] = $val;
		}

		// 参数
		$map['params'] = isset($params) ? $params : array();

		return $map;
	}

	/**
	 * 重新组合路由数组用来便匹配Url
	 */
	public function divideRoutes()
	{
		// 从config中取得设定的路由
		$routes = Loader::route();

		// 将默认路由加在最后
		$routes[] = $this->_default;

		for ($i = 0; $i < count($routes); $i++)
		{
			$route = explode($this->_url_delimiter, trim($routes[$i][0], $this->_url_delimiter));

			// 制作变量规则数组，将没有设定规则的变量使用*规则
			foreach ($route AS $val)
			{
				$rules[$val] = isset($routes[$i][2][$val]) ? $routes[$i][2][$val] : '*';
			}

			$this->_divided_routes['routes'][] = $route;
			$this->_divided_routes['maps'][] = $routes[$i][1];
			$this->_divided_routes['rules'][] = $rules;

			unset($rules);
		}

		//print_r($this->_divided_routes);
	}
}
?>