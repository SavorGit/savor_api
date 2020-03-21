<?php
namespace Common\Lib;

//header("Content-Type: text/html;charset=utf-8");
define("BASE_DIR", dirname(__FILE__) . "/Dada/");

require_once BASE_DIR . 'client/dadaRequestClient.php';
require_once BASE_DIR . 'client/dadaResponse.php';
require_once BASE_DIR . 'config/config.php';
require_once BASE_DIR . 'api/addOrderApi.php';

require_once BASE_DIR . 'model/orderModel.php';

/**
 * 达达开发平台
 *
 */
class Dada{

    private $app_key;
    private $app_secret;
    private $source_id;
    private $online;
    private $config=null;

    public function __construct($config){
        $this->app_key = $config['app_key'];
        $this->app_secret = $config['app_secret'];
        $this->source_id = $config['source_id'];
        $this->online = $config['online'];

        $this->config = $this->getConfig();
    }

    public function getConfig(){
        $config_obj = new \Config($this->source_id, $this->online);
        $config_obj->setAppKey($this->app_key);
        $config_obj->setAppSecret($this->app_secret);
        return $config_obj;
    }

    public function cityCodeList(){
        require_once BASE_DIR . 'api/cityCodeApi.php';
        $cityCodeApi = new \CityCodeApi('');
        $resp = $this->makeRequest($cityCodeApi);
        return $resp;
    }

    public function addOrder(){
        $orderModel = new \OrderModel();
        $orderModel->setShopNo('xxxxxxxxxxxxorigin_shop_no');	// 第三方门店编号
        $orderModel->setOriginId('xxxxxxxxxxxxxxxxxx');			// 第三方订单号
        $orderModel->setCityCode('xxxxx');						// 城市code(可以参照城市code接口)
        $orderModel->setCargoPrice(10);
        $orderModel->setIsPrepay(0);
        $orderModel->setReceiverName('xxxxxxxxxxxxxxxxxx');
        $orderModel->setReceiverAddress('xxxxxxxxxxxxxxx');
        $orderModel->setReceiverLat(0);
        $orderModel->setReceiverLng(0);
        $orderModel->setReceiverPhone('xxxxxxxxxxxxxxxxxx');
        $orderModel->setCallback('');							// 回调url, 每次订单状态变更会通知该url(参照回调接口)

        $addOrderApi = new \AddOrderApi(json_encode($orderModel));
        $dada_client = new \DadaRequestClient($this->config, $addOrderApi);
        $resp = $dada_client->makeRequest();
        return $resp;
    }

    private function makeRequest($params){
        $dada_client = new \DadaRequestClient($this->config,$params);
        $resp = $dada_client->makeRequest();
        $resp = json_encode($resp);
        $resp = json_decode($resp,true);
        return $resp;
    }

}
?>