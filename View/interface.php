<?php
/**
 * View Interface
 * 
 * @author linln
 * @version $Id$
 */
 
interface View_Interface
{
	public function assign($tpl_var, $value = null);
	public function display($tpl_name);
	public function fetch($tpl_name);
}
?>
