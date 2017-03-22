<?php
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

define('SAVOR_M_TP_PATH' , __DIR__);
define('APP_DEBUG',True);
// 定义应用目录
print_r($_SERVER);exit;
define('APP_PATH', SAVOR_M_TP_PATH . '/Application/');
define('APP_STATUS','config_test');

require './ThinkPHP/ThinkPHP.php';