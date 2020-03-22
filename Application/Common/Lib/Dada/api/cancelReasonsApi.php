<?php
/**
 * 取消原因api
 */
define("BASE_DIR", dirname(__FILE__) . "/");
require_once BASE_DIR . 'api/baseApi.php';
require_once BASE_DIR . 'config/urlConfig.php';

class cancelReasonsApi extends BaseApi{
    
    public function __construct($params) {
        parent::__construct(UrlConfig::ORDER_CANCEL_REASONS_URL, $params);
    }
}
