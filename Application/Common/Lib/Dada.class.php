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

    public function queryDeliverFee($hotel_id,$order_no,$area_no,$money,$name,$address,$phone,$lat,$lng,$callback){
        require_once BASE_DIR . 'api/queryDeliverFeeApi.php';

        $orderModel = new \OrderModel();
        $orderModel->setShopNo($hotel_id);
        $orderModel->setOriginId($order_no);
        $orderModel->setCityCode($area_no);
        $orderModel->setCargoPrice($money);
        $orderModel->setIsPrepay(0);
        $orderModel->setReceiverName($name);
        $orderModel->setReceiverAddress($address);
        $orderModel->setReceiverPhone($phone);
        $orderModel->setReceiverLat($lat);
        $orderModel->setReceiverLng($lng);
        $orderModel->setCallback($callback);
        $queryDeliverFeeApi = new \queryDeliverFeeApi(json_encode($orderModel));
        $resp = $this->makeRequest($queryDeliverFeeApi);
        return $resp;
    }

    public function formalCancel($order_id,$cancel_reason_id){
        require_once BASE_DIR . 'api/formalCancelApi.php';
        $orderModel = new \OrderModel();
        $orderModel->setOriginId($order_id);
        $orderModel->setCancelReasonId($cancel_reason_id);
        $formalCancelApi = new \formalCancelApi(json_encode($orderModel));
        $resp = $this->makeRequest($formalCancelApi);
        return $resp;
    }

    public function cancelReasons(){
        require_once BASE_DIR . 'api/cancelReasonsApi.php';
        $cancelReasonsApi = new \cancelReasonsApi('');
        $resp = $this->makeRequest($cancelReasonsApi);
        return $resp;
    }


    public function addOrder($shop_no,$order_id,$area_no,$money,$name,$address,$phone,$lat,$lng,$callback){
        $orderModel = new \OrderModel();
        $orderModel->setShopNo($shop_no);
        $orderModel->setOriginId($order_id);
        $orderModel->setCityCode($area_no);
        $orderModel->setCargoPrice($money);
        $orderModel->setIsPrepay(0);
        $orderModel->setReceiverName($name);
        $orderModel->setReceiverAddress($address);
        $orderModel->setReceiverLat($lat);
        $orderModel->setReceiverLng($lng);
        $orderModel->setReceiverPhone($phone);
        $orderModel->setCallback($callback);

        $addOrderApi = new \AddOrderApi(json_encode($orderModel));
        $resp = $this->makeRequest($addOrderApi);
        return $resp;
    }

    public function reAddOrder($shop_no,$order_id,$area_no,$money,$name,$address,$phone,$lat,$lng,$callback){
        $orderModel = new \OrderModel();
        $orderModel->setShopNo($shop_no);
        $orderModel->setOriginId($order_id);
        $orderModel->setCityCode($area_no);
        $orderModel->setCargoPrice($money);
        $orderModel->setIsPrepay(0);
        $orderModel->setReceiverName($name);
        $orderModel->setReceiverAddress($address);
        $orderModel->setReceiverLat($lat);
        $orderModel->setReceiverLng($lng);
        $orderModel->setReceiverPhone($phone);
        $orderModel->setCallback($callback);

        $readdOrderApi = new \reAddOrderApi(json_encode($orderModel));
        $resp = $this->makeRequest($readdOrderApi);
        return $resp;
    }

    public function queryOrder($order_id){
        $orderModel = new \OrderModel();
        $orderModel->setOrderId($order_id);

        $queryOrderApi = new \queryOrderApi(json_encode($orderModel));
        $resp = $this->makeRequest($queryOrderApi);
        return $resp;
    }

    public function shopDetail($shop_id){
        require_once BASE_DIR . 'model/shopModel.php';
        require_once BASE_DIR . 'api/shopDetailApi.php';
        $shopModel = new \shopModel();
        $shopModel->setOriginShopId($shop_id);

        $shopDetailApi = new \shopDetailApi(json_encode($shopModel));
        $resp = $this->makeRequest($shopDetailApi);
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