<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController as CommonController;

class ExpressController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'autonumber':
                $this->is_verify = 1;
                $this->valid_fields = array('enum'=>1001);
                break;
            case 'addExpressnum':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'enum'=>1001,'comcode'=>1001);
                break;
        }
        parent::_init_();
    }

    public  function autonumber(){
        $num = $this->params['enum'];
        $config = C('KUAIDI_100');
        $key = $config['key'];
        $url = "http://www.kuaidi100.com/autonumber/auto?num=$num&key=$key";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL=>$url,
            CURLOPT_TIMEOUT=>2,
            CURLOPT_HEADER=>0,
            CURLOPT_RETURNTRANSFER=>1,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response,true);
        $data = array();
        if(!empty($res)){
            $express = new \Common\Lib\Express();
            $all_company = $express->getCompany();
            foreach ($res as $v){
                $data[]=array('comcode'=>$v['comCode'],'name'=>$all_company[$v['comCode']]['name']);
            }
        }
        $this->to_back($data);
    }

    public function addExpressnum(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);
        $enum = $this->params['enum'];
        $comcode = $this->params['comcode'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(93036);
        }
        if($res_order['status']!=52){
            $this->to_back(93048);
        }
        $m_orderexpress = new \Common\Model\Smallapp\OrderexpressModel();
        $res_express = $m_orderexpress->getInfo(array('order_id'=>$order_id));
        if(!empty($res_express)){
            $this->to_back(93047);
        }
        $data = array('order_id'=>$order_id,'comcode'=>$comcode,'enum'=>$enum);
        $m_orderexpress->add($data);

        $this->to_back(array());
    }

    public function getExpress(){
        $order_id = intval($this->params['order_id']);
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(93036);
        }
        $m_orderexpress = new \Common\Model\Smallapp\OrderexpressModel();
        $res_express = $m_orderexpress->getExpress($order_id);
        $this->to_back($res_express);
    }

}