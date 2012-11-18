<?php
/**
 * 路由配置文件
 * 
 * 格式: '[]'中为可选内容，没有填写规则的变量将自动用'*'补充
 * <code>
 * array(
 *    '路由规则',
 *    array(
 *       controller => '控制器名称',
 *       action		=> '方法名称'
 *    )
 *    [,
 *    array(
 *       ':var1' => '变量规则(正则表达式)',
 *       ':var2' => ''变量规则(正则表达式)',
 *       ...
 *    )
 *    ]
 * )
 * </code>
 * 
 * 路由规则中变量用':'开头。
 * 例:
 * <code>
 * $routes[] = array(
 *     'blog/:id',
 *     array('controller' => 'blog', 'action' => 'view'),
 *     array(':id' => '^\d+$')
 * </code>
 */

$routes[] = array(
	'login',
	array('controller' => 'auth', 'action' => 'login')
)
?>