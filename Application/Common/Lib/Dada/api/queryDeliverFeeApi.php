<?php
/**
 * 查询配送费用api
 */
define("BASE_DIR", dirname(__FILE__) . "/");
require_once BASE_DIR . 'api/baseApi.php';
require_once BASE_DIR . 'config/urlConfig.php';

class queryDeliverFeeApi extends BaseApi{
    
    public function __construct($params) {
        parent::__construct(UrlConfig::ORDER_QUERY_FEE_URL, $params);
    }
}
