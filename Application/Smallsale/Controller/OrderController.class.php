<?php
namespace Smallsale\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addOrder':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'amount'=>1001,'buy_type'=>1001,'box_mac'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function addOrder(){
        $addorder_num = 5;

        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $buy_type = intval($this->params['buy_type']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92020);
        }
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $fields = 'a.id as box_id,ext.activity_contact,ext.activity_phone,c.name as room_name';
        $box_info = $m_box->getBoxInfo($fields, $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        if($buy_type==1){
            $sale_key = C('SAPP_SALE');
            $order_key = $sale_key.'addorder:'.$goods_id.$openid;
            $cache_key = $sale_key.'addorder:'.$openid;
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $res_ordercache = $redis->get($order_key);
            if(!empty($res_ordercache)){
                $this->to_back(92024);
            }

            $res_cache = $redis->get($cache_key);
            if(!empty($res_cache)){
                $user_order = json_decode($res_cache,true);
                if(count($user_order)>=$addorder_num){
                    $this->to_back(92021);
                }
            }else{
                $user_order = array();
            }
        }

        $buy_time = date('Y-m-d H:i:s');
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $total_fee = sprintf("%.2f",$amount*$res_goods['price']);
        $add_data = array('openid'=>$openid,'box_mac'=>$box_mac,'goods_id'=>$goods_id,
            'price'=>$res_goods['price'],'amount'=>$amount,'total_fee'=>$total_fee,
            'status'=>10,'otype'=>1,'buy_type'=>$buy_type);
        $order_id = $m_order->add($add_data);

        if($buy_type==1){
            $redis->set($order_key,$order_id,43200);

            $user_order[] = $order_id;
            $redis->set($cache_key,json_encode($user_order),43200);
        }

        if(!empty($box_info['activity_phone'])){
            $ucconfig = C('SMS_CONFIG');
            $options = array('accountsid'=>$ucconfig['accountsid'],'token'=>$ucconfig['token']);
            $ucpass= new \Common\Lib\Ucpaas($options);
            $appId = $ucconfig['appid'];
            $param = "{$box_info['room_name']},{$res_goods['name']},$amount,$buy_time";
            $res_json = $ucpass->templateSMS($appId,$box_info['activity_phone'],$ucconfig['activity_goods_addorder_templateid'],$param);
            $res_data = json_decode($res_json,true);
            if(isset($res_data['resp']['respCode'])) {
                $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                    'url'=>$param,'tel'=>$box_info['activity_phone'],'resp_code'=>$res_data['resp']['respCode'],'msg_type'=>3
                    );
                $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                $m_account_sms_log->addData($data);
            }
        }

        $res_data = array('message'=>'购买成功');
        $this->to_back($res_data);
    }



}