<?php
/**
 * Smarty
 *
 * @author  linln
 */

include_once('Smarty/Smarty.class.php');
include_once('interface.php');

class View_Smarty extends Smarty implements View_Interface
{
	public function __construct()
    {
		parent::__construct();

		$this->setTemplateDir(VIEW_DIR);
		$this->compile_dir = TEMP_DIR .DS. 'views_c';
		$this->left_delimiter = '<!--{';
		$this->right_delimiter = '}-->';

		$this->registerPlugin('function', 'url',		array(&$this, '_smarty_url'));
		$this->registerPlugin('function', 'element',	array(&$this, '_smarty_element'));
		$this->registerPlugin('function', 'pager',		array(&$this, '_smarty_pager'));
		$this->registerPlugin('function', 'static', 	array(&$this, '_smarty_static'));
		$this->registerPlugin('function', 'response',	array(&$this, '_smarty_response'));

		$this->registerPlugin('modifier', 'timepassed',	array(&$this, '_smarty_timepassed'));
		$this->registerPlugin('modifier', 'age',		array(&$this, '_smarty_age'));
		$this->registerPlugin('modifier', 'substr',		array(&$this, '_smarty_substr'));
		$this->registerPlugin('modifier', 'clean',		array(&$this, '_smarty_clean'));
    }

    /**
     * 实现html中的url功能，生成url地址
     *
     * @param array $params
     * @return string
     */
	public function _smarty_url($params)
    {
		// TODO : 使用路由类来确定url
    	$ctl = isset($params['ctl']) ? $params['ctl'] : null;
		unset($params['ctl']);

		$act = isset($params['act']) ? $params['act'] : null;
		unset($params['act']);

		$url = "/{$ctl}/{$act}";

		foreach ($params AS $val)
		{
			$url .= "/$val";
		}

		return $url;
    }

    /**
     * 实现html中的element函数，包含element内容
     *
     * @param array $params
     * @return strng | null
     */
    public function _smarty_element($params)
    {
    	$name = isset($params['name']) ? $params['name'] : null;
    	if (is_null($name) || !preg_match("/^[a-zA-Z0-9]+$/i", $name))
    	{
    		return null;
    	}

    	unset($params['name']);

    	$file = ELEMENT_DIR .DS. $name . '.php';
    	if (file_exists($file))
    	{
    		include_once($file);
    		$class_name = $name.'Element';
    		$element = new $class_name;
    		$element->setElement($name);

    		// 设定要执行的方法，如果没有指定就执行默认的render()
    		$act = 'render';
    		if (isset($params['act']) && method_exists($element, $params['act']))
    		{
    			$act = $params['act'];
    			$element->setAction($act);
    			unset($params['act']);
    		}
    		$element->$act($params);
    	}
    	return null;
    }

    /**
     * 分页辅助函数
     * 调用page Element分页
     *
     * @param array $params
     * 结构如下
     * <code>
     * array{
     * 		'total'	=> 总页数
     * 		'page' 	=> 当前所在页
     * 		'size'  => 每页显示数
     * 		'sum'	=> 总记录数
     * }
     * </code>
     */
    public function _smarty_pager($params)
    {
    	$p = isset($params['p']) ? $params['p'] : null;
    	if (empty($p)) return null;

    	$params['total'] = isset($p['total']) ? intval($p['total']) : 0;
    	$params['page']  = isset($p['page'])  ? intval($p['page'])  : 0;
    	$params['size']  = isset($p['size'])  ? intval($p['size'])  : 0;
    	$params['sum']   = isset($p['sum'])   ? intval($p['sum'])   : 0;
    	$action	 = isset($params['style']) ? trim($params['style']) : 'common';
    	$uri	 = isset($params['uri']) ? $params['uri'] : preg_replace("|(?:&)?/page_[0-9]+|i", '', $_SERVER['REQUEST_URI']);
    	$params['uri'] = preg_replace("|[/&]$|", '', $uri);	
		preg_match("|\?.*$|i", $params['uri'], $query);
		$params['query'] = isset($query[0]) ? $query[0] : '';
		$params['uri'] = str_replace($params['query'], '', $params['uri']);
		
    	if ($params['total'] < 1 || $params['page'] < 1 || $params['size'] < 1 || $params['sum'] < 1) return null;

    	$file = ELEMENT_DIR .DS . 'pager.php';

    	if (file_exists($file))
    	{
    		include_once($file);
    		$element = new PagerElement;
    		$element->setElement('pager');

    		if (method_exists($element, $action))
    		{
    			$element->setAction($action);
    			$element->$action($params);
    			unset($action);
    		}
    	}
    	return null;
    }

    public function _smarty_static($params)
    {
    	$src = isset($params['src']) ? trim($params['src'], '/') : '';

    	// 取得主机域名
    	if (preg_match("|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|", $_SERVER['HTTP_HOST'], $matchs))
    	{
    		// 如果是ip地址 （主要是内部测试时使用）
    		$uri = 'http://' . $matchs[0] . '/static';
    		$uri .=  '/' . $src;
    		return $uri;
    	}

    	if (preg_match("|\.?(\w+\.\w{1,5})(:\d*)?$|i", $_SERVER['HTTP_HOST'], $matchs))
    	{
    		$uri = 'http://static.' . $matchs[1];
    		if (isset($matchs[2])) $uri .= $matchs[2];
    		$uri .=  '/' . $src;
    		return $uri;
    	}
    	return null;
    }

    public function _smarty_substr($str, $start = 0, $length = 1, $suffix = '')
    {
    	if (strlen($str) < 1) return $str;
		$start	= intval($start);
		$length	= intval($length);
		$str = strip_tags($str);
		//$str = preg_replace('/(\s+)/', ' ', $str);

		if ($length === 0)
		{
			$sub_str = mb_substr($str, $start);
		}
    	$sub_str = mb_substr($str, $start, $length);

    	if (strlen($str) <= strlen($sub_str))
    	{
    		return $sub_str;
    	}
    	return $sub_str . $suffix;
    }

	/**
	 * 清除内容中的多余空格、HTML标记等影响列表显示字符
	 */
	public function _smarty_clean($str)
    {
		$str = strip_tags($str);
		$str = preg_replace('/\n/', ' ', $str);
		$str = preg_replace('/\t/', ' ', $str);
		$str = preg_replace('/\r/', ' ', $str);
		$str = preg_replace('/(\s+)/', ' ', $str);
		$str = str_replace('&nbsp;', '', $str);
    	return $str;
    }

    public function _smarty_timepassed($datetime)
    {
		$dt = new DateTime($datetime);
		$diff = $dt->diff(new DateTime());

		if ($diff->y > 0)
		{
			$diff_string = $diff->y . '年前';
		}
		elseif ($diff->m > 0)
		{
			$diff_string = $diff->m . '个月前';
		}
		elseif ($diff->d > 0)
		{
			$diff_string = $diff->d . '天前';
		}
		elseif ($diff->h > 0)
		{
			$diff_string = $diff->h . '小时前';
		}
		elseif ($diff->i > 0)
		{
			$diff_string = $diff->i . '分钟前';
		}
		elseif ($diff->s > 0)
		{
			$diff_string = $diff->s . '秒前';
		}
		else
		{
			$diff_string = '刚刚';
		}

		return $diff_string;
    }

	/**
	 * 根据生日计算年纪
	 *
	 * @param date $birthday
	 * @return int
	 */
	public function _smarty_age($birthday)
	{
		if ($birthday == '0000-00-00') return false;
		
		$dt = new DateTime($birthday);
		$diff = $dt->diff(new DateTime());

		return $diff->y;
	}

    public function _smarty_response($params)
    {
    	$t = isset($params['t']) ? $params['t'] : '';
    	$f = isset($params['f']) ? $params['f'] : '';

    	$output = '';
    	$response = $this->getTemplateVars('fw_response');

    	if (!empty($response))
    	{
    		$response = json_decode($response, true);
    		$extension = '';
    		if (count($response) > 2)
    		{
    			$extension = json_encode(array_slice($response, 2));
    		}

    		$output = '<script type="text/javascript">$(function() {';
    		if ($response['success'])
    		{
    			if (empty($t))
    			{
    				$output .= 'alert("'.$response['message'].'");';
    			}
    			else
    			{
    				$output .= $t.'("'.$response['message'].'", \''.$extension.'\');';
    			}
    		}
    		else
    		{
    			if (empty($f))
    			{
    				$output .= 'alert("'.$response['message'].'");';
    			}
    			else
    			{
    				$output .= $f.'("'.$response['message'].'", \''.$extension.'\');';
    			}
    		}

    		$output .= '});</script>';
    	}

    	return $output;
    }
}
?>
