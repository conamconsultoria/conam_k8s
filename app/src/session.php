<?php
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', "tcp://redis:6379");
//echo ini_get('session.save_path');
session_start();
$count = isset($_SESSION['count']) ? $_SESSION['count'] : 1;
echo $count;
$_SESSION['count'] = ++$count;
?>