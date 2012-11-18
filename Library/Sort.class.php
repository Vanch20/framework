<?php
/**
 * 
 *
 * @author linln
 * @version $Id$
 * @created 2007-11-2 ����04:34:13
 */
 
class Sort
{
    public $sort_field = '';
    
    /**
     * 按照sort函数要求的数组排序执行函数
     */
    public function doSort($a, $b)
    {
        $field = $this->sort_field;
        if (empty($field))
        {
            throw new Exception('Sort Field Error');
        }
        
        if ($a[$field] == $b[$field])
        {
            return 0;
        }
        return ($a[$field] < $b[$field]) ? 1 : -1;
    }
    
    /**
     * 数组排序 - 不改变索引顺序
     *
     * @param array $array
     * @param strig $sort_field - 要排序的字段
     * @return array
     */
    public function uasort($array, $sort_field)
    {
        $this->sort_field = $sort_field;
        uasort($array, array("Sort", "doSort"));
        return $array;
    }
    
    /**
     * 数组排序 - 改变索引顺序
     *
     * @param array $array
     * @param strig $sort_field - 要排序的字段
     * @return array
     */
    public function usort($array, $sort_field)
    {
        $this->sort_field = $sort_field;
        usort($array, array("Sort", "doSort"));
        return $array;
    }
}
?>