<?php
namespace FW\Lib;

/**
 * 购物车类
 * 采用Cookie保存的临时购物车
 *
 * @author wanchao
 */
class Cart
{
	/**
	 * 购物车内容
	 * 数组中至少包含id, quantity, price, name四项数据
	 *
	 * @var array
	 */
	protected $_contents = array();

	protected $_name = 'fw_shoppingcart';

	public function  __construct()
	{
		// 如果cookie中已经存在内容，读取并保存到变量中
		if (isset($_COOKIE[$this->_name]))
		{
			$this->_contents = unserialize($_COOKIE[$this->_name]);
		}
	}

	/**
	 * 将变量中的购物车内容保存到cookie
	 */
	protected function _save()
	{
		setcookie($this->_name, serialize($this->_contents), time() + 3600*24*365, '/');
	}

	/**
	 * 将一条商品信息加入购物车
	 *
	 * @param int $id 商品id
	 * @param int $quantity 数量
	 * @param string $name
	 * @param float $price
	 * @return boolean
	 */
	protected function _add_to_cart($id, $name, $quantity = 1, $price = '', $info = array())
	{
		$id		= trim($id);
		$name	= trim($name);
		$quantity = intval($quantity);
		$price	= floatval($price);
		if (empty($id) || empty($name) || $quantity < 1) return false;
		$this->_contents[$id] = array('id' => $id, 'quantity' => $quantity, 'name' => $name, 'price' => $price, 'info' => $info);
		return true;
	}

	/**
	 * 更新商品数量
	 *
	 * @param int $id
	 * @param array $info 要修改的内容数组可包含name、quantity、price
	 * @return boolean
	 */
	protected function _update_cart($id, $info = array())
	{
		$id	= trim($id);
		if (empty($id) || !isset($this->_contents[$id])) return false;

		if (isset($info['quantity']))	$this->_contents[$id]['quantity'] = $info['quantity'];
		if (isset($info['name']))		$this->_contents[$id]['name'] = $info['name'];
		if (isset($info['price']))		$this->_contents[$id]['price'] = $info['price'];
		return true;
	}

	protected function _chg_quantity($id, $quantity)
	{
		$id	= trim($id);
		$quantity = intval($quantity);
		if (empty($id) || !isset($this->_contents[$id])) return false;

		$this->_contents[$id]['quantity'] += $quantity;
		return true;
	}

	/**
	 * 为购物车增加商品
	 * 如商品已经存在更新数量，商品不存在则增加
	 *
	 * 数组基础结构为：
	 * 'id' => 商品唯一标识, 'name' => 名称, 'quantity' => 数量, 'price' => 价格
	 *
	 * @param array $info 商品附加信息数组
	 * @return boolean
	 */
	public function add($id, $name, $quantity, $price, $info = array())
	{
		if (empty($id)) return false;

		if (isset($this->_contents[$id]))
		{
			$this->_chg_quantity($id, $quantity);
		}
		else
		{
			$this->_add_to_cart($id, $name, $quantity, $price, $info);
		}

		return $this->_save();
	}

	public function get()
	{
		return $this->_contents;
	}

	/**
	 * 检查一个商品是否在购物车中已经存在
	 *
	 * @param int $id
	 * @return boolean
	 */
	public function exist($id)
	{
		if (isset($this->_contents[$id]))
		{
			return true;
		}
		return false;
	}

	public function delete($id)
	{
		if (isset($this->_contents[$id]))
		{
			unset($this->_contents[$id]);
			return true;
		}
		return false;
	}

	/**
	 * 清空
	 */
	public function clear()
	{
		$this->_contents = array();
		$this->_save();
	}

	/**
	 * 获取购物车内商品数量
	 *
	 * @return int
	 */
	public function quantity()
	{
		return count($this->_contents);
	}

	/**
	 * 取得购物车内商品总数量
	 *
	 * @return int
	 */
	public function totalQuantity()
	{
		$total = 0;
		foreach ($this->_contents AS $items)
		{
			$total += $items['quantity'];
		}
		return $total;
	}

	/**
	 * 取得购物车内商品总价格
	 * 结构保留2位小数
	 *
	 * @return float
	 */
	public function totalPrice()
	{
		$total = 0;
		foreach ($this->_contents AS $items)
		{
			$total += $items['quantity']*$items['price'];
		}
		return round($total, 2);
	}
}
?>
