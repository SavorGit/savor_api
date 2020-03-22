<?php
/**
 * 查询订单api
 */
define("BASE_DIR", dirname(__FILE__) . "/");
require_once BASE_DIR . 'api/baseApi.php';
require_once BASE_DIR . 'config/urlConfig.php';

class queryOrderApi extends BaseApi{
    
    public function __construct($params) {
        parent::__construct(UrlConfig::ORDER_QUERY_URL, $params);
    }
}
