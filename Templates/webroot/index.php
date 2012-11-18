<?php
require_once('config.inc.php');

session_start();

$Dispatcher = new Dispatcher();
$Dispatcher->dispatch();
?>
