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
                $this->valid_fields = array('openid'=>1001,'cart_ids'=>1002,'goods_id'=>1002,'amount'=>1002,
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
        $where = array('openid'=>$openid);
        if($status){
            $where['status'] = $status;
        }
        $all_nums = $page * $pagesize;
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $fields = 'id as order_id,price,amount,total_fee,status,contact,phone,address,delivery_time,remark,add_time,finish_time';
        $res_order = $m_dishorder->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $datalist = $res_order['list'];
            $oss_host = "http://".C('OSS_HOST').'/';
            foreach($datalist as $k=>$v){
                $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));
                if($v['finish_time']=='0000-00-00 00:00:00'){
                    $datalist[$k]['finish_time'] = '';
                }
                $order_id = $v['order_id'];
                $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.cover_imgs,goods.merchant_id';
                $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                $goods = array();
                foreach ($res_goods as $gv){
                    $ginfo = array('goods_id'=>$gv['goods_id'],'goods_name'=>$gv['goods_name']);
                    $cover_imgs_info = explode(',',$gv['cover_imgs']);
                    $ginfo['goods_img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    $goods[]=$ginfo;
                }
                $datalist[$k]['goods'] = $goods;
                $datalist[$k]['merchant_id']=$res_goods[0]['merchant_id'];
                $datalist[$k]['goods_id']=$goods[0]['goods_id'];
                $datalist[$k]['goods_name']=$goods[0]['goods_name'];
                $datalist[$k]['goods_img'] = $goods[0]['goods_img'];
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function addDishorder(){
        $addorder_num = 30;

        $openid = $this->params['openid'];
        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $cart_ids = $this->params['cart_ids'];
        $contact = $this->params['contact'];
        $phone = $this->params['phone'];
        $address = $this->params['address'];
        $delivery_time = $this->params['delivery_time'];
        $remark = $this->params['remark'];

        if(empty($goods_id) && empty($cart_ids)){
            $this->to_back(1001);
        }
        if(!empty($delivery_time)){
            $tmp_dtime = strtotime($delivery_time);
            if($tmp_dtime<time()){
                $this->to_back(93038);
            }
        }
        $is_check = check_mobile($phone);
        if(!$is_check){
            $this->to_back(93006);
        }
        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'dishorder:'.date('Ymd').':'.$openid;
        $order_space_key = $sale_key.'dishorder:spacetime'.$openid.$goods_id;

//        $redis = \Common\Lib\SavorRedis::getInstance();
//        $redis->select(14);
//        $res_ordercache = $redis->get($order_space_key);
//        if(!empty($res_ordercache)){
//            $this->to_back(92024);
//        }
//        $res_cache = $redis->get($cache_key);
//        if(!empty($res_cache)){
//            $user_order = json_decode($res_cache,true);
//            if(count($user_order)>=$addorder_num){
//                $this->to_back(92021);
//            }
//        }else{
//            $user_order = array();
//        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $goods = array();
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        if($goods_id){
            $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
            if(empty($res_goods) || $res_goods['status']==2){
                $this->to_back(92020);
            }
            $amount = $amount>0?$amount:1;
            $ginfo = array('goods_id'=>$goods_id,'price'=>$res_goods['price'],'name'=>$res_goods['name'],
                'staff_id'=>$res_goods['staff_id'],'amount'=>$amount);
            $goods[$res_goods['merchant_id']][] = $ginfo;
        }else{
            $tmp_cardids = explode(',',$cart_ids);
            $m_cart = new \Common\Model\Smallapp\CartModel();
            foreach ($tmp_cardids as $v){
                if(!empty($v)){
                    $res_cart = $m_cart->getInfo(array('id'=>intval($v)));
                    if(empty($res_cart) || $res_cart['openid']!=$openid){
                        $this->to_back(90133);
                    }
                    $res_goods = $m_goods->getInfo(array('id'=>$res_cart['goods_id']));
                    if(!empty($res_goods) && $res_goods['status']==1){
                        $ginfo = array('goods_id'=>$res_goods['id'],'price'=>$res_goods['price'],'name'=>$res_goods['name'],
                            'staff_id'=>$res_goods['staff_id'],'amount'=>$res_cart['amount']);
                        $goods[$res_cart['merchant_id']][] = $ginfo;
                    }
                }
            }
        }
        if(empty($goods)){
            $this->to_back(1001);
        }
        $m_order = new \Common\Model\Smallapp\DishorderModel();
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $m_staff = new \Common\Model\Integral\StaffModel();
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_hotel = new \Common\Model\HotelModel();
        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $hotel_name = $goods_name = array();
        foreach ($goods as $k=>$v){
            $amount = 0;
            $total_fee = 0;
            $merchant_id = $k;
            foreach ($v as $gv){
                $price = sprintf("%.2f",$gv['amount']*$gv['price']);
                $total_fee = $total_fee+$price;
                $amount = $amount+$gv['amount'];
            }
            $add_data = array('openid'=>$openid,'merchant_id'=>$merchant_id,'amount'=>$amount,'total_fee'=>$total_fee,
                'status'=>1,'contact'=>$contact,'phone'=>$phone,'address'=>$address,'pay_type'=>1);
            if(!empty($delivery_time)){
                $add_data['delivery_time'] = $delivery_time;
            }
            if(!empty($remark)){
                $add_data['remark'] = $remark;
            }
            $order_id = $m_order->add($add_data);

//            $redis->set($order_space_key,$order_id,60);
            $user_order[] = $order_id;
//            $redis->set($cache_key,json_encode($user_order),86400);

            $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
            $activity_phone = $res_merchant['mobile'];
            $res_hotel = $m_hotel->getOneById('name',$res_merchant['hotel_id']);
            $hotel_name[] = $res_hotel['name'];

            $order_goods = array();
            $send_staff = array();
            foreach ($v as $ov){
                $order_goods[]=array('order_id'=>$order_id,'goods_id'=>$ov['goods_id'],'price'=>$ov['price'],'amount'=>$ov['amount']);

                if(!in_array($ov['staff_id'],$send_staff)){
                    $res_staff = $m_staff->getInfo(array('id'=>$ov['staff_id']));
                    if(!empty($res_staff['openid'])){
                        $where = array('openid'=>$res_staff['openid']);
                        $fields = 'id user_id,openid,mobile';
                        $res_user = $m_user->getOne($fields, $where);
                        if(!empty($res_user) && !empty($res_user['mobile'])){
                            $activity_phone = $res_user['mobile'];
                        }
                    }
                    $params = array();
                    $template_code = $ucconfig['dish_send_salemanager'];
                    $res_data = $alisms::sendSms($activity_phone,$params,$template_code);
                    $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                        'url'=>'','tel'=>$activity_phone,'resp_code'=>$res_data->Code,'msg_type'=>3
                    );
                    $m_account_sms_log->addData($data);
                }else{
                    $send_staff[]=$ov['staff_id'];
                }
                $goods_name[]=$ov['name'];
            }
            $m_ordergoods->addAll($order_goods);
        }

        $params = array('goods_name'=>$goods_name[0]);
        $template_code = $ucconfig['dish_send_buyer'];
        $res_data = $alisms::sendSms($phone,$params,$template_code);
        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$params),'tel'=>$phone,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log->addData($data);

        $hotel_name = join(',',$hotel_name);
        $message1 = "您的订单消息已经通知“{$hotel_name}“餐厅。";
        $message2 = "请等待餐厅人员的电话确认。";
        $res_data = array('message1'=>$message1,'message2'=>$message2);
        $this->to_back($res_data);
    }


}