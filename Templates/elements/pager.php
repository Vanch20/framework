<?php
/**
 * 分页
 *
 * 传入参数说明
 * array('p' => array('size' => 每页显示的纪录数, 'sum' => 总纪录数, 'page' => 当前页));
 */
class PagerElement extends Element
{
	public function common($params = array())
	{
		$size  = intval($params['p']['size']);
		$sum   = intval($params['p']['sum']);
		$page  = intval($params['p']['page']);
		$total = ceil($sum / $size);
		
		// 如果当前显示的记录数和总数相等，不显示
		if ($size >= $sum) return null;
		
		$start = $page > 9 ? $page : 1;
		$start = $total - $page > 2 ? $start : $start - ($total - $page);
		if ($start < 1) $start = 1;
		
		$loop = $total > 7 ? $start + 7 : $total + 1;
		$this->assign('start',	$start);
		$this->assign('loop', 	$loop);
		$this->assign('size', 	$size);
		$this->assign('sum', 	$sum);
		$this->assign('total', 	$total);
		$this->assign('page', 	$page);
		$this->render($params);
	}
	
	public function full($params = array())
	{
		$size  = intval($params['p']['size']);
		$sum   = intval($params['p']['sum']);
		$page  = intval($params['p']['page']);
		$total = ceil($sum / $size);
		
		// 如果当前显示的记录数和总数相等，不显示
		if ($size >= $sum) return null;
		
		$start = $size * $page - $size + 1;
		$end   = $start + $size - 1;
		$end   = $end > $sum ? $sum : $end;
		$last  = $page - 1 < 1 ? 1 : $page - 1;
		$next  = $page + 1 > $total ? $page : $page + 1;
	
		$params['page'] = $page;
		$this->assign('start', $start);
		$this->assign('end', $end);
		$this->assign('last', $last);
		$this->assign('next', $next);
		$this->render($params);
	}

	/**
	 * 简单风格分页
	 * 显示当前页码左右各几个页码，并使当前页码居中的分页方式
	 */
	public function lite_center($params = array())
	{
		$size  = intval($params['p']['size']);
		$sum   = intval($params['p']['sum']);
		$page  = intval($params['p']['page']);
		$total = ceil($sum / $size);

		// 如果当前显示的记录数和总数相等，不显示
		if ($size >= $sum) return null;

		// 显示几个页码 必须是奇数 保证当前页码居中
		$show = 9;
		$side = (($show-1)/2);

		$start = $page > $side ? $page - $side : 1;
		if ($total - $page < $side) $start =  $total - $show+1;
		if ($start < 1) $start = 1;

		$loop = $start + $show;
		if ($loop > $total) $loop = $total+1;
		
		$this->assign('start',	$start);
		$this->assign('loop', 	$loop);
		$this->assign('size', 	$size);
		$this->assign('sum', 	$sum);
		$this->assign('total', 	$total);
		$this->assign('page', 	$page);
		$this->render($params);
	}
}
?>