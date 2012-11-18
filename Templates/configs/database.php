<?php
/**
 * 数据库配置文件
 */

/**
 * 数据库连接字符串
 * 
 * 这是一个数组，每个数据库连接的规则是:
 * <code>
 * Array['连接名称'] => '连接方法://用户名:密码@主机:端口/数据库名称?其他参数'
 * </code>
 * 
 * 连接字符串内容将由parse_url()函数解析成如下形式:
 * <code>
 * Array
 * (
 * 	   [scheme] => 连接方法 
 * 	   [host] => 主机:端口
 *     [user] => 用户名
 *     [pass] => 密码
 *     [path] => /数据库名称
 *     [query] => arg=value
 *     [fragment] => anchor
 * )
 * </code>
 */
$dsns['dns_name'] = 'mysqli://user:pass@host/db';
?>