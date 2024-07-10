<?php
namespace Smallsale22\Controller;
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
            case 'getExpress':
                $this->is_verify = 1;
                $this->valid_fields = array('order_id'=>1001,'express_id'=>1002);
                break;
            case 'getExpressList':
                $this->is_verify = 1;
                $this->valid_fields = array('order_id'=>1001);
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
            if(!isset($res['returnCode'])){
                $express = new \Common\Lib\Express();
                $all_company = $express->getCompany();
                foreach ($res as $v){
                    if(!empty($v['comCode'])){
                        $data[]=array('comcode'=>$v['comCode'],'name'=>$all_company[$v['comCode']]['name']);
                    }
                }
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

        $res = $m_order->updateData(array('id'=>$order_id),array('status'=>53));
        if($res && $res_order['otype']==5 && !empty($res_order['sale_uid'])){
            $m_config = new \Common\Model\SysConfigModel();
            $res_config = $m_config->getAllconfig();
            $profit = $res_config['distribution_profit'];

            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $fields = 'og.goods_id,og.price,og.amount,goods.supply_price,goods.distribution_profit';
            $where = array('og.order_id'=>$res_order['id']);
            $res_ordergoods = $m_ordergoods->getOrdergoodsList($fields,$where,'og.id asc');

            $add_data = array();
            foreach ($res_ordergoods as $v){
                if($v['distribution_profit']>0){
                    $profit = $v['distribution_profit'];
                }
                $income_fee = 0;
                if($v['price']>$v['supply_price']){
                    $income_fee = ($v['price']-$v['supply_price'])*$profit;
                    $income_fee = sprintf("%.2f",$income_fee);
                }
                $total_fee = sprintf("%.2f",$v['price']*$v['amount']);
                $add_data[] = array('user_id'=>$res_order['sale_uid'],'openid'=>$res_order['openid'],'order_id'=>$res_order['id'],'goods_id'=>$v['goods_id'],
                    'price'=>$v['price'],'supply_price'=>$v['supply_price'],'amount'=>$v['amount'],'total_fee'=>$total_fee,'income_fee'=>$income_fee,
                    'profit'=>$profit
                    );
            }
            $m_income = new \Common\Model\Smallapp\UserincomeModel();
            $m_income->addAll($add_data);
        }
        $this->to_back(array());
    }

    public function getExpress(){
        $order_id = intval($this->params['order_id']);
        $express_id = isset($this->params['express_id'])?intval($this->params['express_id']):0;
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(93036);
        }
        $m_orderexpress = new \Common\Model\Smallapp\OrderexpressModel();
        $res_express = $m_orderexpress->getExpress($order_id,$express_id);
        $this->to_back($res_express);
    }

    public function getExpressList(){
        $order_id = intval($this->params['order_id']);
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(93036);
        }
        $m_orderexpress = new \Common\Model\Smallapp\OrderexpressModel();
        $express = $m_orderexpress->getExpressList($order_id);
        $res_data = array('contact'=>$res_order['contact'],'phone'=>$res_order['phone'],'address'=>$res_order['address'],'express'=>$express);
        $this->to_back($res_data);
    }
}