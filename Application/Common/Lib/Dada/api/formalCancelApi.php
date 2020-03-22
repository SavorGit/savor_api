<?php
/**
 * 取消订单api
 */
define("BASE_DIR", dirname(__FILE__) . "/");
require_once BASE_DIR . 'api/baseApi.php';
require_once BASE_DIR . 'config/urlConfig.php';

class formalCancelApi extends BaseApi{
    
    public function __construct($params) {
        parent::__construct(UrlConfig::ORDER_FORMAL_CANCEL_URL, $params);
    }
}
