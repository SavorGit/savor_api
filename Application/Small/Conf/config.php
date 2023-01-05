<?php
//系统配置
$route_rules = array(
    '/^rds\/(\S{0,10})$/'=>'Jump/index?id=:1',
);
$config = array(
    'SHOW_PAGE_TRACE'=>false,

    'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES'=>$route_rules
);
return $config;
