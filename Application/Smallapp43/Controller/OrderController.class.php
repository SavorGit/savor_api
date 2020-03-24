<?php
namespace Smallapp43\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getPrepareData':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001);
                break;
            case 'getRemarks':
                $this->is_verify = 0;
                break;
            case 'getDeliveryTime':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001);
                break;
            case 'getDeliveryfee':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'address_id'=>1001,'merchant_id'=>1001,'money'=>1001);
                break;
            case 'addOrder':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'carts'=>1002,'goods_id'=>1002,'amount'=>1002,
                    'contact'=>1002,'phone'=>1002,'address'=>1002,'delivery_time'=>1002,'remark'=>1002,
                    'address_id'=>1002,'delivery_type'=>1001,'pay_type'=>1001,'tableware'=>1002,
                    'company'=>1002,'credit_code'=>1002,'selfpick_time'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'getStatusChange':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'cancel':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
                break;
            case 'orderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getPrepareData(){
        $merchant_id = intval($this->params['merchant_id']);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('m.id'=>$merchant_id);
        $fields = 'm.id as merchant_id,m.is_shopself,m.delivery_platform,m.status,hotel.id as hotel_id';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        if(empty($res_merchant) || $res_merchant[0]['status']!=1){
            $this->to_back(93035);
        }

        $delivery_types = C('DELIVERY_TYPES');
        if($res_merchant[0]['is_shopself']==0){
            unset($delivery_types['2']);
        }
        $pay_types = C('PAY_TYPES');
        if($res_merchant[0]['delivery_platform']==1){
            unset($pay_types['20']);
        }else{
            unset($pay_types['10']);
        }
        $data = array('delivery_platform'=>intval($res_merchant[0]['delivery_platform']));
        $data['delivery_types'] = array_values($delivery_types);
        $data['pay_types'] = array_values($pay_types);
        $tableware = array();
        for($i=0;$i<11;$i++){
            if($i==0){
                $info = array('id'=>0,'name'=>'未选择');
            }else{
                $info = array('id'=>$i,'name'=>$i.'份');
            }
            $tableware[]=$info;
        }
        $data['tableware'] = $tableware;
        $this->to_back($data);
    }

    public function getRemarks(){
        $m_remark = new \Common\Model\Smallapp\TagsModel();
        $res_remark = $m_remark->getDataList('*',array('status'=>1),'id desc');
        $remark = array();
        foreach ($res_remark as $v){
            $remark[]=array('id'=>$v['id'],'name'=>$v['name']);
        }
        $this->to_back($remark);
    }

    public function getDeliveryTime(){
        $merchant_id = intval($this->params['merchant_id']);
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('m.id'=>$merchant_id);
        $fields = 'm.id as merchant_id,m.is_shopself,m.delivery_platform,m.status,hotel.id as hotel_id,ext.meal_time';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        if(empty($res_merchant) || $res_merchant[0]['status']!=1){
            $this->to_back(93035);
        }
        $meal_time = intval($res_merchant[0]['meal_time']);
        $data = array(
            array('name'=>'立即配送','value'=>0)
        );
        if($meal_time){
            $meal_minutes = $meal_time*60;
            $nowtime = time() + $meal_minutes;
            $data[]=array('name'=>date("Y-m-d H:i:00",$nowtime),'value'=>date("Y-m-d H:i:00",$nowtime));
        }else{
            $nowtime = time();
        }

        $hour = date('G',$nowtime);
        $minutes = date('i',$nowtime);
        $minutes = intval($minutes);
        $now_date = date('Y-m-d');
        for ($i=$hour;$i<24;$i++){
            $tmp_hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            if($i==$hour){
                if($minutes<20){
                    $time1 = strtotime("$now_date $tmp_hour:20:00");
                    $info1 = array('name'=>date("Y-m-d H:i:00",$time1),'value'=>date("Y-m-d H:i:00",$time1));
                    $data[]=$info1;

                    $time2 = strtotime("$now_date $tmp_hour:40:00");
                    $info2 = array('name'=>date("Y-m-d H:i:00",$time2),'value'=>date("Y-m-d H:i:00",$time2));
                    $data[]=$info2;
                }elseif($minutes>20 && $minutes<40){
                    $time = strtotime("$now_date $tmp_hour:40:00");
                    $info = array('name'=>date("Y-m-d H:i:00",$time),'value'=>date("Y-m-d H:i:00",$time));
                    $data[]=$info;
                }
            }else{
                $time = strtotime("$now_date $tmp_hour:00:00");
                $info = array('name'=>date("Y-m-d H:i:00",$time),'value'=>date("Y-m-d H:i:00",$time));
                $data[]=$info;

                $time1 = strtotime("$now_date $tmp_hour:20:00");
                $info1 = array('name'=>date("Y-m-d H:i:00",$time1),'value'=>date("Y-m-d H:i:00",$time1));
                $data[]=$info1;

                $time2 = strtotime("$now_date $tmp_hour:40:00");
                $info2 = array('name'=>date("Y-m-d H:i:00",$time2),'value'=>date("Y-m-d H:i:00",$time2));
                $data[]=$info2;
            }
        }
        $this->to_back($data);
    }

    public function getDeliveryfee(){
        $openid = $this->params['openid'];
        $address_id = $this->params['address_id'];
        $merchant_id = $this->params['merchant_id'];
        $money = $this->params['money'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $res_address = $m_address->getInfo(array('id'=>$address_id));
        $m_area = new \Common\Model\AreaModel();
        $res_area = $m_area->find($res_address['area_id']);
        $res_county = $m_area->find($res_address['county_id']);
        $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];

        if(empty($res_address) || $res_address['openid']!=$openid){
            $this->to_back(90132);
        }
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('m.id'=>$merchant_id,'m.status'=>1);
        $fields = 'm.id,hotel.id as hotel_id';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        if(empty($res_merchant)){
            $this->to_back(93035);
        }

        $order_no = getmicrotime();
        $config = C('DADA');
        $hotel_id = $res_merchant[0]['hotel_id'];
        $hotel_id = $config['shop_no'];//上线后去除

        $dada = new \Common\Lib\Dada($config);
        $callback = http_host();
        $res = $dada->queryDeliverFee($hotel_id,$order_no,$res_area['area_no'],$money,
            $res_address['consignee'],$address,$res_address['phone'],$res_address['lat'],$res_address['lng'],$callback);
        $data = array('fee'=>0,'distance'=>0);
        if($res['code']==0 && !empty($res['result'])){
            $data['fee'] = $res['result']['fee'];
            $data['fee'] = 0;
            $data['distance'] = $res['result']['distance'].'m';
            if($res['result']['distance']>1000){
                $distance = $res['result']['distance']/1000;
                $distance = sprintf("%.2f",$distance);
                $data['distance'] = $distance.'km';
            }
        }
        $this->to_back($data);
    }

    public function addOrder(){
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
        $delivery_type = intval($this->params['delivery_type']);//配送类型1外卖配送 2到店自取
        $pay_type = intval($this->params['pay_type']);//10微信支付 20线下付款
        $tableware = intval($this->params['tableware']);
        $company = $this->params['company'];
        $credit_code = $this->params['credit_code'];
        $title_type = $this->params['title_type'];//发票抬头类型 1企业 2个人
        $selfpick_time = $this->params['selfpick_time'];

        if(empty($goods_id) && empty($carts)){
            $this->to_back(1001);
        }
        if($delivery_type==2){
            if(empty($selfpick_time)){
                $this->to_back(1001);
            }
        }
        if($delivery_time>0){
            $tmp_dtime = strtotime($delivery_time);
            if($tmp_dtime<time()){
                $this->to_back(93038);
            }
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
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

        if($address_id){
            $m_area = new \Common\Model\AreaModel();
            $m_address = new \Common\Model\Smallapp\AddressModel();
            $res_address = $m_address->getInfo(array('id'=>$address_id));
            $res_area = $m_area->find($res_address['area_id']);
            $res_county = $m_area->find($res_address['county_id']);

            $contact = $res_address['consignee'];
            $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        }
        if(empty($phone) && !empty($res_address['phone'])){
            $phone = $res_address['phone'];
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
            $goods[] = $ginfo;
            $merchant_id = $res_goods['merchant_id'];
        }else{
            $json_str = stripslashes(html_entity_decode($carts));
            $cart_info = json_decode($json_str,true);
            if(!empty($cart_info)){
                foreach ($cart_info as $v){
                    if(!empty($v)){
                        $res_goods = $m_goods->getInfo(array('id'=>$v['id']));
                        if(!empty($res_goods) && $res_goods['status']==1){
                            $merchant_id = $res_goods['merchant_id'];
                            $ginfo = array('goods_id'=>$res_goods['id'],'price'=>$res_goods['price'],'name'=>$res_goods['name'],
                                'staff_id'=>$res_goods['staff_id'],'amount'=>$v['amount']);
                            $goods[] = $ginfo;
                        }
                    }
                }
            }
        }
        if(empty($goods)){
            $this->to_back(1001);
        }
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('m.id'=>$merchant_id);
        $fields = 'm.id as merchant_id,m.is_shopself,m.delivery_platform,m.status,hotel.id as hotel_id,hotel.name as hotel_name';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);

        if($res_merchant[0]['delivery_platform']==1 && $pay_type!=10){
            $this->to_back(90135);
        }

        $amount = 0;
        $total_fee = 0;
        foreach ($goods as $gv){
            $price = sprintf("%.2f",$gv['amount']*$gv['price']);
            $total_fee = $total_fee+$price;
            $amount = $amount+$gv['amount'];
        }
        $delivery_fee = 0;
//        if($res_merchant[0]['delivery_platform']==1 && $delivery_type==1 && $address_id){
//            $config = C('DADA');
//            $hotel_id = $res_merchant[0]['hotel_id'];
//            $hotel_id = $config['shop_no'];//上线需去除
//            $order_no= getmicrotime();
//            $dada = new \Common\Lib\Dada($config);
//            $callback = http_host();
//            $res = $dada->queryDeliverFee($hotel_id,$order_no,$res_area['area_no'],$total_fee,
//                $contact,$address,$phone,$res_address['lat'],$res_address['lng'],$callback);
//            if($res['code']==0 && !empty($res['result'])){
//                $delivery_fee = $res['result']['fee'];
//            }
//        }
        $total_fee = $total_fee+$delivery_fee;
        $add_data = array('openid'=>$openid,'merchant_id'=>$merchant_id,'amount'=>$amount,'total_fee'=>$total_fee,'delivery_fee'=>$delivery_fee,
            'status'=>10,'contact'=>$contact,'phone'=>$phone,'address'=>$address,'otype'=>3,'delivery_type'=>$delivery_type,'pay_type'=>$pay_type);
        if($address_id){
            $add_data['area_id'] = $res_address['area_id'];
            $add_data['lnglat'] = "{$res_address['lng']},{$res_address['lat']}";
        }
        if($tableware){
            $add_data['tableware'] = $tableware;
        }
        if($delivery_time==0){
            $add_data['is_atonce'] = 1;
            $delivery_time = date('Y-m-d H:i:s');
        }
        if(!empty($delivery_time)){
            $add_data['delivery_time'] = $delivery_time;
        }
        if(!empty($remark)){
            $add_data['remark'] = $remark;
        }
        if($delivery_type==2){
            $add_data['selfpick_time'] = $selfpick_time;
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $order_id = $m_order->add($add_data);

//        $redis->set($order_space_key,$order_id,60);
        $user_order[] = $order_id;
        $redis->set($cache_key,json_encode($user_order),86400);

        $order_goods = array();
        foreach ($goods as $ov){
            $order_goods[]=array('order_id'=>$order_id,'goods_id'=>$ov['goods_id'],'price'=>$ov['price'],'amount'=>$ov['amount']);
        }
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $m_ordergoods->addAll($order_goods);

        $invoice_data = array();
        switch ($title_type){
            case 1:
                $invoice_data['company'] = $company;
                $invoice_data['credit_code'] = $credit_code;
                $invoice_data['title_type'] = $title_type;
                break;
            case 2:
                $invoice_data['company'] = $company;
                $invoice_data['title_type'] = $title_type;
                break;
        }
        if(!empty($invoice_data)){
            $invoice_data['order_id'] = $order_id;
            $m_invoice = new \Common\Model\Smallapp\OrderinvoiceModel();
            $m_invoice->add($invoice_data);
        }

        $resp_data = array('pay_type'=>$pay_type,'order_id'=>$order_id);
        if($pay_type==10){
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            $trade_no = $m_ordermap->add(array('order_id'=>$order_id,'pay_type'=>10));

            $trade_name = $goods[0]['name'];
            $trade_info = array('trade_no'=>$trade_no,'total_fee'=>$total_fee,'trade_name'=>$trade_name,
                'wx_openid'=>$openid,'redirect_url'=>'','attach'=>30);
            $smallapp_config = C('SMALLAPP_CONFIG');
            $pay_wx_config = C('PAY_WEIXIN_CONFIG_1554975591');
            $payconfig = array(
                'appid'=>$smallapp_config['appid'],
                'partner'=>$pay_wx_config['partner'],
                'key'=>$pay_wx_config['key']
            );
            $m_payment = new \Payment\Model\WxpayModel(3);
            $wxpay = $m_payment->pay($trade_info,$payconfig);
            $payinfo = json_decode($wxpay,true);
            $resp_data['payinfo'] = $payinfo;
        }else{
            $hotel_name = $res_merchant[0]['hotel_name'];

            $message1 = "您的订单消息已经通知“{$hotel_name}“餐厅。";
            $message2 = "请等待餐厅人员的电话确认。";
            $resp_data['message1'] = $message1;
            $resp_data['message2'] = $message2;

            $is_notify_merchant = $m_order->sendMessage($order_id);
            if($is_notify_merchant){
                $m_order->updateData(array('id'=>$order_id),array('status'=>13));
            }
        }
        $this->to_back($resp_data);
    }

    public function getStatusChange(){
        $order_id = intval($this->params['order_id']);

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $fields = 'o.id,o.status,o.lnglat,m.hotel_id,m.delivery_platform,hotel.name as hotel_name';
        $where = array('o.id'=>$order_id);
        $res_order = $m_order->getOrderInfo($fields,$where);
        $data = array();
        if(!empty($res_order)){
            $status = $res_order[0]['status'];
            $all_status = C('ORDER_STATUS');
            $data = array('status'=>$status,'status_str'=>$all_status[$status],
                'hotel_location'=>array(),'transporter_location'=>array(),'user_location'=>array());

            if(in_array($status,array(14,15,16,17))){
                $config = C('DADA');
                $hotel_id = $res_order[0]['hotel_id'];
                $hotel_id = $config['shop_no'];//上线后去除

                $dada = new \Common\Lib\Dada($config);
                $res = $dada->queryOrder($order_id);
                if($res['code']==0 && !empty($res['result'])){
                    $dd_res = $res['result'];
                    $ddstatus_code = $dd_res['statusCode'];
                    $status_map = array('1'=>14,'2'=>15,'3'=>16,'4'=>17);//待接单＝1 待取货＝2 配送中＝3 已完成＝4 已取消＝5 已过期＝7 指派单=8 妥投异常之物品返回中=9 妥投异常之物品返回完成=10 系统故障订单发布失败=1000
                    $status_code = 0;
                    if(isset($status_map[$ddstatus_code])){
                        $status_code = $status_map[$ddstatus_code];
                        if(in_array($ddstatus_code,array(2,3))){
                            if($dd_res['distance']>1000){
                                $distance = $dd_res['distance']/1000;
                                $distance = sprintf("%.2f",$distance);
                                $distance = $distance.'km';
                            }else{
                                $distance = sprintf("%.2f",$dd_res['distance']);
                                $distance = $distance.'m';
                            }
                            $data['transporter_location'] = array('name'=>$dd_res['transporterName'],'phone'=>$dd_res['transporterPhone'],
                                'lng'=>$dd_res['transporterLng'],'lat'=>$dd_res['transporterLat'],'distance'=>$distance);
                        }
                        if($status!=$status_code){
                            $data['status'] = $status_code;
                            $data['status_str'] = $all_status[$status_code];
                            $m_order->updateData(array('status'=>$status_code),array('id'=>$order_id));
                        }

                        $res_shop = $dada->shopDetail($hotel_id);
                        if($res_shop['code']==0 && !empty($res_shop['result'])){
                            $lnglat = explode(',',$res_order[0]['lnglat']);
                            $data['hotel_location'] = array('name'=>$res_order[0]['hotel_name'],'lng'=>$lnglat[0],
                                'lat'=>$lnglat[1]);
                        }
                        $m_address = new \Common\Model\Smallapp\AddressModel();
                        $res_address = $m_address->getInfo(array('id'=>$res_order[0]['address_id']));
                        $data['user_location'] = array('lng'=>$res_address['lng'],'lat'=>$res_address['lat']);
                    }
                }
            }
        }
        $this->to_back($data);
    }


    public function orderlist(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);//1待处理 2已完成
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
        $where = array('openid'=>$openid,'otype'=>3);
        switch ($status){
            case 1:
                $where['status'] = array('lt',17);
                break;
            case 2:
                $where['status'] = array('in',array(17,18,19));
                break;
        }
        $all_nums = $page * $pagesize;
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $fields = 'id as order_id,merchant_id,price,amount,total_fee,status,contact,phone,address,delivery_time,remark,add_time,finish_time';
        $res_order = $m_order->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $m_media = new \Common\Model\MediaModel();
            $datalist = $res_order['list'];
            $oss_host = "http://".C('OSS_HOST').'/';
            $all_status = C('ORDER_STATUS');
            foreach($datalist as $k=>$v){
                $datalist[$k]['status_str'] = $all_status[$v['status']];
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

    public function detail(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['openid']!=$openid){
            $this->to_back(90134);
        }

        $order_data = array('order_id'=>$order_id,'merchant_id'=>$res_order['merchant_id'],'amount'=>$res_order['amount'],
            'total_fee'=>$res_order['total_fee'],'status'=>$res_order['status'],'status_str'=>'',
            'contact'=>$res_order['contact'],'phone'=>$res_order['phone'],'address'=>$res_order['address'],
            'remark'=>$res_order['remark'],'delivery_time'=>$res_order['delivery_time'],'delivery_fee'=>$res_order['delivery_fee'],
            'type'=>$res_order['otype']
        );
        $order_status_str = C('ORDER_STATUS');
        if(isset($order_status_str[$res_order['status']])){
            $order_data['status_str'] = $order_status_str[$res_order['status']];
        }

        $oss_host = "http://".C('OSS_HOST').'/';
        $order_data['add_time'] = date('Y-m-d H:i',strtotime($res_order['add_time']));
        if($res_order['finish_time']=='0000-00-00 00:00:00'){
            $order_data['finish_time'] = '';
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
        $order_data['goods'] = $goods;

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $m_media = new \Common\Model\MediaModel();
        $where = array('m.id'=>$res_order['merchant_id']);
        $fields = 'm.id,hotel.id as hotel_id,hotel.name,hotel.mobile,hotel.tel,ext.hotel_cover_media_id';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        $merchant = array('name'=>$res_merchant[0]['name'],'mobile'=>$res_merchant[0]['mobile'],'tel'=>$res_merchant[0]['mobile'],
            'merchant_id'=>$res_order['merchant_id'],'img'=>'');
        if(!empty($res_merchant[0]['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
            $merchant['img'] = $res_media['oss_addr'];
        }
        $order_data['merchant'] = $merchant;
        $invoice = array();
        $m_invoice = new \Common\Model\Smallapp\OrderinvoiceModel();
        $res_invoice = $m_invoice->getInfo(array('order_id'=>$order_id));
        if(!empty($res_invoice)){
            $invoice['company'] = $res_invoice['company'];
            $invoice['credit_code'] = $res_invoice['credit_code'];
            $invoice['title_type'] = intval($res_invoice['title_type']);
        }
        $order_data['invoice'] = $invoice;
        $order_data['hotel_location'] = array();
        $order_data['transporter_location'] = array();
        $order_data['user_location'] = array();
        $order_data['markers'] = array();
        $order_data['distance'] = '';

        if(in_array($res_order['status'],array(14,15,16,17))){
            $config = C('DADA');
            $hotel_id = $res_merchant[0]['hotel_id'];
            $hotel_id = $config['shop_no'];//上线后去除

            $dada = new \Common\Lib\Dada($config);
            $res = $dada->queryOrder($order_id);
            if($res['code']==0 && !empty($res['result'])){
                $dd_res = $res['result'];
                $ddstatus_code = $dd_res['statusCode'];
                $status_map = array('1'=>14,'2'=>15,'3'=>16,'4'=>17);//待接单＝1 待取货＝2 配送中＝3 已完成＝4 已取消＝5 已过期＝7 指派单=8 妥投异常之物品返回中=9 妥投异常之物品返回完成=10 系统故障订单发布失败=1000
                if(isset($status_map[$ddstatus_code])){
                    if(in_array($ddstatus_code,array(2,3))){
                        if($dd_res['distance']>1000){
                            $distance = $dd_res['distance']/1000;
                            $distance = sprintf("%.2f",$distance);
                            $distance = $distance.'km';
                        }else{
                            $distance = sprintf("%.2f",$dd_res['distance']);
                            $distance = $distance.'m';
                        }
                        $order_data['transporter_location'] = array('name'=>$dd_res['transporterName'],'phone'=>$dd_res['transporterPhone'],
                            'lng'=>$dd_res['transporterLng'],'lat'=>$dd_res['transporterLat']);
                        $order_data['distance']=$distance;
                        $order_data['markers'] = array(
                            array(
                                'iconPath'=>'/images/imgs/default-user.png',
                                'id'=>0,
                                'latitude'=>$dd_res['transporterLat'],
                                'longitude'=>$dd_res['transporterLng'],
                                'width'=>50,
                                'height'=>50,
                            )
                        );
                    }
                    $order_data['transporter_location'] = array('name'=>'热达达','phone'=>'13112345678',
                        'lng'=>'116.475783','lat'=>'39.908287');
                    $order_data['markers'] = array(
                        array(
                            'iconPath'=>'/images/imgs/default-user.png',
                            'id'=>0,
                            'latitude'=>'39.908287',
                            'longitude'=>'116.475783',
                            'width'=>50,
                            'height'=>50,
                        )
                    );
                    $order_data['distance']='1.5km';
                }
            }

            $res_shop = $dada->shopDetail($hotel_id);
            if($res_shop['code']==0 && !empty($res_shop['result'])){
                $order_data['hotel_location'] = array('name'=>$res_merchant[0]['name'],'lng'=>$res_shop['result']['lng'],
                    'lat'=>$res_shop['result']['lat']);
            }
            $lnglat_arr = explode(',',$res_order['lnglat']);
            $order_data['user_location'] = array('lng'=>$lnglat_arr[0],'lat'=>$lnglat_arr[1]);
        }
        $this->to_back($order_data);
    }

    public function cancel(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['openid']!=$openid){
            $this->to_back(90134);
        }
        if(in_array($res_order['status'],array(18,19))){
            $this->to_back(90136);
        }
        if($res_order['status']!=13){
            $this->to_back(90137);
        }
        $message = '取消订单成功';
        if($res_order['pay_type']==10){
            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
            if(!empty($res_orderserial)){
                $m_baseinc = new \Payment\Model\BaseIncModel();
                $payconfig = $m_baseinc->getPayConfig(2);
                $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
                $res_ordermap = $m_ordermap->getDataList('id',array('order_id'=>$order_id),'id desc',0,1);
                $trade_no = $res_ordermap['list'][0]['id'];

                $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$res_order['pay_fee'],'refund_money'=>$res_order['pay_fee']);
                $m_wxpay = new \Payment\Model\WxpayModel();
                $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                    $m_order->updateData(array('id'=>$order_id),array('status'=>19));
                    $message = '取消订单成功,且已经退款.款项在1到7个工作日内,退还到你的支付账户';
                }else{
                    $message = '取消订单失败';
                }
            }
        }else{
            $m_order->updateData(array('id'=>$order_id),array('status'=>19));
        }
        $res_data = array('message'=>$message);
        $this->to_back($res_data);
    }


}