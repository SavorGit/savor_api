<?php
$DB_MASTER_HOST = 'localhost';
$DB_SLAVE_HOST  = 'localhost';
$db_name = 'savoradmin';
$db_user = 'root';
$db_pwd = '';

//redis缓存配置
$redis['db1']['0']['host'] = '';
$redis['db1']['0']['port'] = '';
$redis['db1']['0']['isMaster'] = '1';
$redis['db1']['1']['host'] = ''; 
$redis['db1']['1']['port'] = '';
$redis['db1']['1']['isMaster'] = '0';


	'DB_DEPLOY_TYPE' => 1, //数据库主从支持
    'DB_RW_SEPARATE' => true, //读写分离
    'DB_TYPE' => 'mysql',
    'DB_HOST' => "$DB_MASTER_HOST,$DB_SLAVE_HOST",
    'DB_NAME' => $db_name,
    'DB_USER' => $db_user,
    'DB_PWD' => $db_pwd,
    'DB_PORT' => 3306,
    'DB_CHARSET' => 'UTF8',
    'DB_PREFIX' => 'savor_',

 	'REDIS_CONFIG' => $redis,
);
return $config_db;


    'DB_NAME' => $db_name,
