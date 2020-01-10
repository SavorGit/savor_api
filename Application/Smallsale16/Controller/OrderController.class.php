<?php
namespace Smallsale16\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addOrder':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'amount'=>1001,'openid'=>1001,'buy_type'=>1002,
                    'box_mac'=>1002,'contact'=>1002,'phone'=>1002,'address'=>1002);
                break;
        }
        parent::_init_();
    }

    public function addOrder(){
        $addorder_num = 20;

        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $contact = $this->params['contact'];
        $phone = $this->params['phone'];
        $address = $this->params['address'];
        $uid = $this->params['uid'];
        $buy_type = intval($this->params['buy_type']);

        $sale_uid = '';
        if(!empty($uid)){
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $decode_info = $hashids->decode($uid);
            if(empty($decode_info)){
                $this->to_back(90101);
            }
            $sale_uid = $decode_info[0];
        }

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
        if($buy_type==1){
            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['a.mac'] = $box_mac;
            $map['a.state'] = 1;
            $map['a.flag']  = 0;
            $map['d.state'] = 1;
            $map['d.flag']  = 0;
            $fields = 'a.id as box_id,d.id as hotel_id,ext.activity_contact,ext.activity_phone,c.name as room_name';
            $box_info = $m_box->getBoxInfo($fields, $map);
            if(empty($box_info)){
                $this->to_back(93008);
            }
            $box_info = $box_info[0];

            $sale_key = C('SAPP_SALE');
            $cache_key = $sale_key.'addorder:'.$openid;
            $order_space_key = $sale_key.'addorder:spacetime'.$openid.$goods_id;

            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $res_ordercache = $redis->get($order_space_key);
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
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $amount = $amount>0?$amount:1;
        $total_fee = sprintf("%.2f",$amount*$res_goods['price']);
        $add_data = array('openid'=>$openid,'box_mac'=>$box_mac,'goods_id'=>$goods_id,
            'price'=>$res_goods['price'],'amount'=>$amount,'total_fee'=>$total_fee,
            'status'=>10,'otype'=>1,'buy_type'=>$buy_type);
        if(!empty($sale_uid)){
            $add_data['sale_uid'] = $sale_uid;
        }
        if($res_goods['type']==31){
            if(empty($contact) || empty($phone) || empty($address)){
                $this->to_back(1001);
            }
            $is_check = check_mobile($phone);
            if(!$is_check){
                $this->to_back(93006);
            }
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
            $res_userintegral = $m_userintegral->getInfo(array('openid'=>$openid));
            $integral = 0;
            if(!empty($res_userintegral)){
                $integral = intval($res_userintegral['integral']);
            }
            $excharge_integral = $res_goods['rebate_integral']*$amount;
            if($excharge_integral>$integral){
                $this->to_back(93007);
            }
            $add_data['status'] = 20;
            $add_data['otype'] = 2;
            $add_data['contact'] = $contact;
            $add_data['phone'] = $phone;
            $add_data['address'] = $address;
        }
        $order_id = $m_order->add($add_data);

        if($buy_type==1){
            $redis->set($order_space_key,$order_id,180);

            $user_order[] = $order_id;
            $redis->set($cache_key,json_encode($user_order),18000);
        }

        if($buy_type==1 && in_array($res_goods['type'],array(10,20))){
            $activity_phone = $box_info['activity_phone'];
            if($sale_uid){
                $m_user = new \Common\Model\Smallapp\UserModel();
                $where = array('id'=>$sale_uid);
                $fields = 'id user_id,openid,mobile';
                $res_user = $m_user->getOne($fields, $where);
                if(!empty($res_user)){
                    $m_merchant = new \Common\Model\Integral\MerchantModel();
                    $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$box_info['hotel_id'],'status'=>1));
                    if(!empty($res_merchant)){
                        $activity_phone = $res_merchant['mobile'];
                        $m_staff = new \Common\Model\Integral\StaffModel();
                        $res_staff = $m_staff->getInfo(array('merchant_id'=>$res_merchant['id'],'openid'=>$res_user['openid'],'status'=>1));
                        if(!empty($res_staff) && !empty($res_user['mobile'])){
                            $activity_phone = $res_user['mobile'];
                        }
                    }
                }
            }
            if(!empty($activity_phone)){
                if(empty($res_goods['name'])){
                    $res_goods['name'] = '您发布的商品';
                }

                $hash_ids_key = C('HASH_IDS_KEY');
                $hashids = new \Common\Lib\Hashids($hash_ids_key);
                $encode_oid = $hashids->encode($order_id);

                $ucconfig = C('ALIYUN_SMS_CONFIG');
                $alisms = new \Common\Lib\AliyunSms();
                if($res_goods['type']==10){
                    $params = array('room_name'=>$box_info['room_name'],'goods_name'=>$res_goods['name'],'amount'=>$amount,'enoid'=>$encode_oid);
                    $template_code = $ucconfig['activity_goods_send_salemanager'];
                }else{
                    $params = array('room_name'=>$box_info['room_name'],'goods_name'=>$res_goods['name'],'amount'=>$amount);
                    $template_code = $ucconfig['activity_goods_send_salemanager_nolink'];
                }

                $res_data = $alisms::sendSms($activity_phone,$params,$template_code);
                $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                    'url'=>join(',',$params),'tel'=>$activity_phone,'resp_code'=>$res_data->Code,'msg_type'=>3
                );
                $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                $m_account_sms_log->addData($data);
            }

        }
        $res_data = array('message'=>'购买成功');
        if($res_goods['type']==31){
            $res_data['message'] = '申请兑换成功';
        }
        $this->to_back($res_data);
    }



}