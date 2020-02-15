<?php
namespace Smallapp4\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addDishorder':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'amount'=>1001,'openid'=>1001,
                    'contact'=>1001,'phone'=>1001,'address'=>1001,'delivery_time'=>1002,'remark'=>1002);
                break;
            case 'dishOrderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
        }
        parent::_init_();
    }


    public function dishOrderlist(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        if(empty($pagesize)){
            $pagesize =10;
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $where = array('o.openid'=>$openid);
        if($status){
            $where['o.status'] = $status;
        }
        $all_nums = $page * $pagesize;
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $fields = 'o.id as order_id,o.price,o.amount,o.total_fee,o.status,o.contact,o.phone,o.address,o.delivery_time,
        o.remark,o.add_time,goods.id as goods_id,goods.name as goods_name,goods.cover_imgs';
        $res_order = $m_dishorder->getList($fields,$where,'o.id desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $datalist = $res_order['list'];
            $oss_host = "http://".C('OSS_HOST').'/';
            foreach($datalist as $k=>$v){
                $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));
                $cover_imgs_info = explode(',',$v['cover_imgs']);
                $datalist[$k]['goods_img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                unset($datalist[$k]['cover_imgs']);
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function addDishorder(){
        $addorder_num = 30;

        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $openid = $this->params['openid'];
        $contact = $this->params['contact'];
        $phone = $this->params['phone'];
        $address = $this->params['address'];
        $delivery_time = $this->params['delivery_time'];
        $remark = $this->params['remark'];

        $is_check = check_mobile($phone);
        if(!$is_check){
            $this->to_back(93006);
        }
        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'dishorder:'.date('Ymd').':'.$openid;
        $order_space_key = $sale_key.'dishorder:spacetime'.$openid.$goods_id;

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

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods) || $res_goods['status']==2){
            $this->to_back(92020);
        }

        $m_order = new \Common\Model\Smallapp\DishorderModel();
        $amount = $amount>0?$amount:1;
        $total_fee = sprintf("%.2f",$amount*$res_goods['price']);
        $add_data = array('openid'=>$openid,'merchant_id'=>$res_goods['merchant_id'],'staff_id'=>$res_goods['staff_id'],
            'dishgoods_id'=>$goods_id,'price'=>$res_goods['price'],'amount'=>$amount,'total_fee'=>$total_fee,
            'status'=>1,'contact'=>$contact,'phone'=>$phone,'address'=>$address,'pay_type'=>1);
        if(!empty($delivery_time)){
            $add_data['delivery_time'] = $delivery_time;
        }
        if(!empty($remark)){
            $add_data['remark'] = $remark;
        }
        if(!empty($sale_uid)){
            $add_data['sale_uid'] = $sale_uid;
        }
        $order_id = $m_order->add($add_data);

//        $redis->set($order_space_key,$order_id,60);
        $user_order[] = $order_id;
        $redis->set($cache_key,json_encode($user_order),86400);

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$res_goods['merchant_id']));
        $activity_phone = $res_merchant['mobile'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getInfo(array('id'=>$res_goods['staff_id']));
        if(!empty($res_staff['openid'])){
            $where = array('openid'=>$res_staff['openid']);
            $fields = 'id user_id,openid,mobile';
            $res_user = $m_user->getOne($fields, $where);
            if(!empty($res_user) && !empty($res_user['mobile'])){
                $activity_phone = $res_user['mobile'];
            }
        }

        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $params = array();
        $template_code = $ucconfig['dish_send_salemanager'];
        $res_data = $alisms::sendSms($activity_phone,$params,$template_code);
        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>'','tel'=>$activity_phone,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        $params = array('goods_name'=>$res_goods['name']);
        $template_code = $ucconfig['dish_send_buyer'];
        $res_data = $alisms::sendSms($phone,$params,$template_code);
        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$params),'tel'=>$phone,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('name',$res_merchant['hotel_id']);
        $hotel_name = $res_hotel['name'];
        $message1 = "您的订单消息已经通知“{$hotel_name}“餐厅。";
        $message2 = "请等待餐厅人员的电话确认。";
        $res_data = array('message1'=>$message1,'message2'=>$message2);
        $this->to_back($res_data);
    }


}