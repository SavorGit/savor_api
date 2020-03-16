<?php
namespace Smallapp4\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getPreOrder':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'cart_ids'=>1002,'goods_id'=>1002);
                break;
            case 'addDishorder':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'carts'=>1002,'goods_id'=>1002,'amount'=>1002,
                    'contact'=>1002,'phone'=>1002,'address'=>1002,'delivery_time'=>1002,'remark'=>1002,
                    'address_id'=>1002,'type'=>1002);
                break;
            case 'dishOrderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getPreOrder(){
        $openid = $this->params['openid'];
        $goods_id= intval($this->params['goods_id']);
        $cart_ids = $this->params['cart_ids'];

        if(empty($goods_id) && empty($cart_ids)){
            $this->to_back(1001);
        }
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
            $amount = 1;
            $ginfo = array('goods_id'=>$goods_id,'price'=>$res_goods['price'],'name'=>$res_goods['name'],
                'cover_imgs'=>$res_goods['cover_imgs'],'amount'=>$amount);
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
                            'cover_imgs'=>$res_goods['cover_imgs'],'amount'=>$res_cart['amount']);
                        $goods[$res_cart['merchant_id']][] = $ginfo;
                    }
                }
            }
        }
        if(empty($goods)){
            $this->to_back(1001);
        }
        $all_goods = array();
        $merchant_id = 0;
        $amount = 0;
        $total_fee = 0;
        $oss_host = "http://".C('OSS_HOST').'/';
        foreach ($goods as $k=>$v){
            $merchant_id = $k;
            foreach ($v as $gv){
                $price = sprintf("%.2f",$gv['amount']*$gv['price']);
                $total_fee = $total_fee+$price;
                $amount = $amount+$gv['amount'];
                $ginfo = array('goods_id'=>$gv['goods_id'],'goods_name'=>$gv['name'],'price'=>$gv['price'],'amount'=>$gv['amount']);
                $cover_imgs_info = explode(',',$gv['cover_imgs']);
                $ginfo['goods_img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                $all_goods[]=$ginfo;
            }
        }
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('m.id'=>$merchant_id);
        $fields = 'm.id as merchant_id,hotel.id as hotel_id,hotel.name as hotel_name';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        $data = array('merchant_id'=>$merchant_id,'hotel_name'=>$res_merchant[0]['hotel_name'],'amount'=>$amount,'total_fee'=>$total_fee);
        $data['goods'] = $all_goods;
        $this->to_back($data);
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
        $fields = 'id as order_id,merchant_id,price,amount,total_fee,status,contact,phone,address,delivery_time,remark,add_time,finish_time';
        $res_order = $m_dishorder->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $m_media = new \Common\Model\MediaModel();
            $datalist = $res_order['list'];
            $oss_host = "http://".C('OSS_HOST').'/';
            foreach($datalist as $k=>$v){
                $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));
                if($v['finish_time']=='0000-00-00 00:00:00'){
                    $datalist[$k]['finish_time'] = '';
                }
                $order_id = $v['order_id'];
                $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
                $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                $goods = array();
                foreach ($res_goods as $gv){
                    $ginfo = array('id'=>$gv['goods_id'],'name'=>$gv['goods_name'],'price'=>$gv['price'],'amount'=>$gv['amount'],
                        'status'=>$gv['status']);
                    $cover_imgs_info = explode(',',$gv['cover_imgs']);
                    $ginfo['img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    $goods[]=$ginfo;
                }

                $where = array('m.id'=>$v['merchant_id']);
                $fields = 'm.id,hotel.name,ext.hotel_cover_media_id';
                $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
                $merchant = array('name'=>$res_merchant[0]['name'],'merchant_id'=>$v['merchant_id']);
                $merchant['img'] = '';
                if(!empty($res_merchant[0]['hotel_cover_media_id'])){
                    $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
                    $merchant['img'] = $res_media['oss_addr'];
                }

                $datalist[$k]['merchant'] = $merchant;
                $datalist[$k]['goods'] = $goods;
                $datalist[$k]['goods_id']=$goods[0]['id'];
                $datalist[$k]['goods_name']=$goods[0]['name'];
                $datalist[$k]['goods_img'] = $goods[0]['img'];
                $datalist[$k]['price'] = $v['total_fee'];
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function dishOrderdetail(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $res_order = $m_dishorder->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['openid']!=$openid){
            $this->to_back(90134);
        }
        $res_order['order_id'] = $order_id;
        unset($res_order['id'],$res_order['openid'],$res_order['staff_id'],$res_order['dishgoods_id'],$res_order['price'],$res_order['pay_type']);

        $oss_host = "http://".C('OSS_HOST').'/';
        $res_order['add_time'] = date('Y-m-d H:i',strtotime($res_order['add_time']));
        if($res_order['finish_time']=='0000-00-00 00:00:00'){
            $res_order['finish_time'] = '';
        }
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
        $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        $goods = array();
        foreach ($res_goods as $gv){
            $ginfo = array('id'=>$gv['goods_id'],'name'=>$gv['goods_name'],'price'=>$gv['price'],'amount'=>intval($gv['amount']),
                'status'=>$gv['status']);
            $cover_imgs_info = explode(',',$gv['cover_imgs']);
            $ginfo['img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
            $goods[]=$ginfo;
        }
        $res_order['goods'] = $goods;

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $m_media = new \Common\Model\MediaModel();
        $where = array('m.id'=>$res_order['merchant_id']);
        $fields = 'm.id,hotel.name,ext.hotel_cover_media_id';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        $merchant = array('name'=>$res_merchant[0]['name'],'merchant_id'=>$res_order['merchant_id']);
        $merchant['img'] = '';
        if(!empty($res_merchant[0]['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
            $merchant['img'] = $res_media['oss_addr'];
        }
        $res_order['merchant'] = $merchant;
        $this->to_back($res_order);
    }

    public function addDishorder(){
        $addorder_num = 30;

        $openid = $this->params['openid'];
        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $carts = $this->params['carts'];
        $contact = $this->params['contact'];
        $phone = $this->params['phone'];
        $address = $this->params['address'];
        $delivery_time = $this->params['delivery_time'];
        $remark = $this->params['remark'];
        $address_id = intval($this->params['address_id']);
        $type = isset($this->params['type'])?intval($this->params['type']):1;//类型1普通订单 2代理订单

        if(empty($goods_id) && empty($carts)){
            $this->to_back(1001);
        }
        if(!empty($delivery_time)){
            $tmp_dtime = strtotime($delivery_time);
            if($tmp_dtime<time()){
                $this->to_back(93038);
            }
        }

        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'dishorder:'.date('Ymd').':'.$openid;
        $order_space_key = $sale_key.'dishorder:spacetime'.$openid.$goods_id;

        $redis = \Common\Lib\SavorRedis::getInstance();
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
        if($address_id){
            $m_area = new \Common\Model\AreaModel();
            $m_address = new \Common\Model\Smallapp\AddressModel();
            $res_address = $m_address->getInfo(array('id'=>$address_id));
            $res_area = $m_area->find($res_address['area_id']);
            $res_county = $m_area->find($res_address['county_id']);

            $contact = $res_address['consignee'];
            $phone = $res_address['phone'];
            $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        }
        if(empty($contact) || empty($phone) || empty($address)){
           $this->to_back(1001);
        }
        $is_check = check_mobile($phone);
        if(!$is_check){
            $this->to_back(93006);
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
            $json_str= stripslashes(html_entity_decode($carts));
            $cart_info = json_decode($json_str,true);
            if(!empty($cart_info)){
                foreach ($cart_info as $v){
                    if(!empty($v)){
                        $res_goods = $m_goods->getInfo(array('id'=>$v['id']));
                        if(!empty($res_goods) && $res_goods['status']==1){
                            $ginfo = array('goods_id'=>$res_goods['id'],'price'=>$res_goods['price'],'name'=>$res_goods['name'],
                                'staff_id'=>$res_goods['staff_id'],'amount'=>$v['amount']);
                            $goods[$res_goods['merchant_id']][] = $ginfo;
                        }
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
            if($type){
                $add_data['type']=$type;
            }
            if(!empty($delivery_time)){
                $add_data['delivery_time'] = $delivery_time;
            }
            if(!empty($remark)){
                $add_data['remark'] = $remark;
            }
            $order_id = $m_order->add($add_data);

//            $redis->set($order_space_key,$order_id,60);
            $user_order[] = $order_id;
            $redis->set($cache_key,json_encode($user_order),86400);

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
        if($goods_id){
            $params = array('goods_name'=>$goods_name[0]);
            $template_code = $ucconfig['dish_send_buyer'];
        }else{
            $params = array('hotel_name'=>$hotel_name[0]);
            $template_code = $ucconfig['dish_send_cartsbuyer'];
        }

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