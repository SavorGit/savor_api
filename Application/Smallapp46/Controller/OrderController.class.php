<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getPrepareData':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1002,'type'=>1002);
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
            case 'addActivityOrder':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'amount'=>1001,'openid'=>1001,'buy_type'=>1002,
                    'box_mac'=>1002,'contact'=>1002,'phone'=>1002,'address'=>1002);
                break;
            case 'getpreorder':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_ids'=>1001,'openid'=>1002);
                break;
            case 'addShoporder':
                $this->is_verify = 1;
                $this->valid_fields = array('uid'=>1002,'openid'=>1001,'carts'=>1002,'goods_id'=>1002,'amount'=>1002,
                    'address_id'=>1002,'remark'=>1002,'pay_type'=>1001,'title_type'=>1002,'company'=>1002,'credit_code'=>1002,
                    'email'=>1002,'box_id'=>1002,'box_mac'=>1002,'task_user_id'=>1002,'usercoupon_id'=>1002);
                break;
            case 'addGiftorder':
                $this->is_verify = 1;
                $this->valid_fields = array('uid'=>1002,'openid'=>1001,'goods_id'=>1001,'amount'=>1001,'person_upnum'=>1001,'pay_type'=>1001,'remark'=>1002,
                    'title_type'=>1002,'company'=>1002,'credit_code'=>1002,'email'=>1002,'box_id'=>1002);
                break;
            case 'reserveResult':
                $this->valid_fields = array('order_id'=>1001);
                break;
            case 'modifyMessage':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'message'=>1001);
                break;
            case 'getStatusChange':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'cancel':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'orderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'type'=>1002,'pagesize'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getPrepareData(){
        $type = isset($this->params['type'])?intval($this->params['type']):1;//1外卖 2商城
        $merchant_id = intval($this->params['merchant_id']);

        $pay_types = C('PAY_TYPES');
        if($type==1){
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
            switch ($res_merchant[0]['delivery_platform']){
                case 1:
                    unset($pay_types['20']);
                    break;
                case 2:
                    break;
                default:
                    unset($pay_types['10']);
                    break;
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
        }else{
            unset($pay_types['20']);
            $data = array();
            $data['pay_types'] = array_values($pay_types);
        }
        $this->to_back($data);
    }

    public function getRemarks(){
        $m_remark = new \Common\Model\Smallapp\TagsModel();
        $res_remark = $m_remark->getDataList('*',array('status'=>1,'category'=>2),'id desc');
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
        $now_date = date('Y-m-d');
        if($meal_time){
            $meal_minutes = $meal_time*60;
            $nowtime = time() + $meal_minutes;
            $tmp_hour = date('G',$nowtime);
            $tmp_minutes = date('i',$nowtime);
            if($tmp_minutes<20){
                $nowtime = strtotime("$now_date $tmp_hour:20:00");
            }elseif($tmp_minutes>=20 && $tmp_minutes<40){
                $nowtime = strtotime("$now_date $tmp_hour:40:00");
            }else{
                if($tmp_hour+1<24){
                    $tmp_hour = $tmp_hour+1;
                    $nowtime = strtotime("$now_date $tmp_hour:00:00");
                }else{
                    $nowtime = strtotime("$now_date $tmp_hour:40:00");
                }
            }
            $data[]=array('name'=>date("Y-m-d H:i:00",$nowtime),'value'=>date("Y-m-d H:i:00",$nowtime));
        }else{
            $nowtime = time();
        }

        $hour = date('G',$nowtime);
        $minutes = date('i',$nowtime);
        $minutes = intval($minutes);

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
                }elseif($minutes>=20 && $minutes<40){
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

        $data = array('fee'=>0,'distance'=>0);
        $this->to_back($data);


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
//        $hotel_id = $config['shop_no'];//上线后去除

        $dada = new \Common\Lib\Dada($config);
        $callback = http_host();
        $res = $dada->queryDeliverFee($hotel_id,$order_no,$res_area['area_no'],$money,
            $res_address['consignee'],$address,$res_address['phone'],$res_address['lat'],$res_address['lng'],$callback);
        $data = array('fee'=>0,'distance'=>0);
        if($res['code']==0 && !empty($res['result'])){
            $data['fee'] = $res['result']['fee'];
            $data['distance'] = $res['result']['distance'].'m';
            if($res['result']['distance']>1000){
                $distance = $res['result']['distance']/1000;
                $distance = sprintf("%.2f",$distance);
                $data['distance'] = $distance.'km';
            }
        }
        $this->to_back($data);
    }

    public function getpreorder(){
        $goods_ids = $this->params['goods_ids'];
        $openid = $this->params['openid'];

        $json_str = stripslashes(html_entity_decode($goods_ids));
        $goods_ids_arr = json_decode($json_str,true);
        $ids = array();
        $id_amount = array();
        if(!empty($goods_ids_arr)){
            foreach ($goods_ids_arr as $v){
                if(!empty($v['id'])){
                    $ids[]=intval($v['id']);
                }
                $id_amount[$v['id']]=$v['amount'];
            }
        }
        $datas = array();
        if(!empty($ids)){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $m_media = new \Common\Model\MediaModel();
            $fields = "id,name,attr_name,price,amount,cover_imgs,type,gtype,status,merchant_id,parent_id,model_media_id,is_localsale,is_usecoupon";
            $where = array('id'=>array('in',$ids));
            $res_goods = $m_goods->getDataList($fields,$where,'id desc');
            $res_online = array();
            $total_fee = 0;
            $total_amount = 0;
            $usercoupon_id = 0;
            $coupon_info = '';
            $res_coupons = array();
            if(!empty($openid)){
                $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
                $where = array('a.openid'=>$openid,'a.ustatus'=>1);
                $nowtime = date('Y-m-d H:i:s');
                $where['coupon.status'] = 1;
                $where['coupon.start_time'] = array('elt',$nowtime);
                $where['coupon.end_time'] = array('egt',$nowtime);
                $fields = 'a.id as usercoupon_id,a.money,a.min_price';
                $res_coupon_user = $m_coupon_user->getUsercouponDatas($fields,$where,'a.id desc','0,1');
                if(!empty($res_coupon_user)){
                    $res_coupons = $res_coupon_user[0];
                }
            }
            $oss_host = get_oss_host();
            foreach ($res_goods as $v){
                if($v['status']==2){
                    continue;
                }
                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                $num = $id_amount[$v['id']];
                if($num>$v['amount']){
                    $num = $v['amount'];
                }
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'price'=>$v['price'],'amount'=>$num,'stock_num'=>$v['amount'],
                    'attr_name'=>$v['attr_name'],'type'=>$v['type'],'gtype'=>$v['gtype'],'is_localsale'=>$v['is_localsale'],
                    'img_url'=>$img_url,'status'=>intval($v['status']));
                if($v['gtype']==3){
                    $res_media = $m_media->getMediaInfoById($v['model_media_id']);
                    $dinfo['img_url'] = $res_media['oss_addr']."?x-oss-process=image/resize,p_50/quality,q_80";;

                    $res_gv = $m_goods->getInfo(array('id'=>$v['parent_id']));
                    $dinfo['name'] = $res_gv['name'];
                    $dinfo['gtype'] = $res_gv['gtype'];
                }

                $total_amount = $total_amount+$dinfo['amount'];
                $tmp_money = sprintf("%.2f",$dinfo['amount']*$dinfo['price']);
                if($v['type']==44 && $v['is_usecoupon']==1 && !empty($res_coupons) && !empty($openid)){
                    if($usercoupon_id==0){
                        if($v['price']>=$res_coupons['min_price']){
                            $usercoupon_id = $res_coupons['usercoupon_id'];
                            $coupon_info = "满{$res_coupons['min_price']}减{$res_coupons['money']}";
                            $tmp_money = $tmp_money - $res_coupons['money'];
                        }
                    }
                }
                $dinfo['money'] = $tmp_money;
                $total_fee = $total_fee+$tmp_money;
                $res_online[$v['merchant_id']][]=$dinfo;
            }

            $datas['usercoupon_id'] = $usercoupon_id;
            $datas['coupon_info'] = $coupon_info;
            $datas['total_fee'] = $total_fee;
            $datas['amount'] = $total_amount;

            foreach ($res_online as $k=>$v){
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $res_merchant = $m_merchant->getMerchantInfo('m.id,hotel.name as hotel_name,area.region_name',array('m.id'=>$k));
                $merchant_fee = 0;
                $merchant_amount = 0;
                foreach ($v as $kg=>$vg){
                    $merchant_fee+=$vg['money'];
                    $merchant_amount+=$vg['amount'];
                    $tips = $localsale_str = '';
                    if($vg['type']==22 && $vg['is_localsale']==1){
                        $tips = "当前商品仅售{$res_merchant[0]['region_name']}，其他区域无法配送哦～";
                        $localsale_str = '仅售'.$res_merchant[0]['region_name'];
                    }
                    $v[$kg]['tips'] = $tips;
                    $v[$kg]['localsale_str'] = $localsale_str;

                    unset($v[$kg]['money']);
                }
                $info = array('merchant_id'=>$k,'name'=>$res_merchant[0]['hotel_name'],'money'=>$merchant_fee,'amount'=>$merchant_amount,
                    'goods'=>$v);
                $datas['goods'][]=$info;
            }
        }
        $this->to_back($datas);
    }

    public function addShoporder(){
        $addorder_num = 30;
        $uid = $this->params['uid'];
        $openid = $this->params['openid'];
        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $carts = $this->params['carts'];
        $remark = $this->params['remark'];
        $address_id = intval($this->params['address_id']);
        $pay_type = intval($this->params['pay_type']);//10微信支付 20线下付款
        $company = $this->params['company'];
        $credit_code = $this->params['credit_code'];
        $email = $this->params['email'];
        $title_type = $this->params['title_type'];//发票抬头类型 1企业 2个人
        $box_id = $this->params['box_id'];
        $box_mac = $this->params['box_mac'];
        $task_user_id = intval($this->params['task_user_id']);
        $usercoupon_id = intval($this->params['usercoupon_id']);
        if(empty($goods_id) && empty($carts)){
            $this->to_back(1001);
        }
        $sale_uid = 0;
        if($uid) {
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $decode_info = $hashids->decode($uid);
            if (empty($decode_info)) {
                $sale_uid = 0;
            }else{
                $sale_uid = $decode_info[0];
            }
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,role_id,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $sale_key = C('SAPP_SALE');
        $order_cache_key = $sale_key.'dishorder:'.date('Ymd').':'.$openid;
        $order_space_key = $sale_key.'dishorder:spacetime'.$openid.$goods_id;

        $redis = \Common\Lib\SavorRedis::getInstance();
        $order_location = array();
        $m_area = new \Common\Model\AreaModel();
        if(!empty($box_mac)){
            $m_box = new \Common\Model\BoxModel();
            $bwhere = array('mac'=>$box_mac,'state'=>1,'flag'=>0);
            $res_box = $m_box->getOnerow($bwhere);
            $box_id = $res_box['id'];
        }
        if(!empty($box_id)){
            $box_id = intval($box_id);
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $res_hotel = json_decode($redis_hotel_info, true);

            $order_location = array('hotel_id'=>$room_info['hotel_id'],'hotel_name'=>$res_hotel['name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                'room_id'=>$box_info['room_id'],'room_name'=>$room_info['name'],'box_id'=>$box_id,'box_mac'=>$box_info['mac'],'box_type'=>$box_info['box_type'],
                'area_id'=>$res_hotel['area_id']
            );
            $res_area = $m_area->find($order_location['area_id']);
            $order_location['area_name'] = $res_area['region_name'];
        }

        $redis->select(14);
        $res_ordercache = $redis->get($order_space_key);
        if(!empty($res_ordercache)){
            $this->to_back(92024);
        }
        $res_cache = $redis->get($order_cache_key);
        if(!empty($res_cache)){
            $user_order = json_decode($res_cache,true);
            if(count($user_order)>=$addorder_num){
                $this->to_back(92021);
            }
        }else{
            $user_order = array();
        }
        $contact = '';
        $address = '';
        $phone = '';
        if($goods_id==C('LAIMAO_SECKILL_GOODS_ID')){
            if($pay_type==10 && empty($address_id)){
                $this->to_back(1001);
            }
        }else{
            if(empty($address_id)){
                $this->to_back(1001);
            }
        }
        if(!empty($address_id)){
            $m_address = new \Common\Model\Smallapp\AddressModel();
            $res_address = $m_address->getInfo(array('id'=>$address_id));
            $res_area = $m_area->find($res_address['area_id']);
            $res_county = $m_area->find($res_address['county_id']);
            $contact = $res_address['consignee'];
            $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
            $phone = $res_address['phone'];
        }
        $otype = 5;
        $goods = array();
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        if($goods_id){
            $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
            if($res_goods['type']==42){
                $otype = 8;
            }
            if(empty($res_goods) || $res_goods['amount']==0 || $res_goods['status']==2){
                $this->to_back(90144);
            }
            $amount = $amount>0?$amount:1;
            if($amount>$res_goods['amount']){
                $this->to_back(90143);
            }
            $goods_name = $res_goods['name'];
            if($res_goods['gtype']==3){
                $res_pgoods = $m_goods->getInfo(array('id'=>$res_goods['parent_id']));
                $goods_name = $res_pgoods['name'];
            }
            $ginfo = array('goods_id'=>$goods_id,'price'=>$res_goods['price'],'name'=>$goods_name,
                'type'=>$res_goods['type'],'is_localsale'=>$res_goods['is_localsale'],'merchant_id'=>$res_goods['merchant_id'],
                'staff_id'=>$res_goods['staff_id'],'amount'=>$amount);
            $goods[$res_goods['merchant_id']][] = $ginfo;
        }else{
            $json_str = stripslashes(html_entity_decode($carts));
            $cart_info = json_decode($json_str,true);
            if(!empty($cart_info)){
                foreach ($cart_info as $v){
                    if(!empty($v)){
                        $res_goods = $m_goods->getInfo(array('id'=>$v['id']));
                        if(empty($res_goods) || $res_goods['amount']==0 || $res_goods['status']==2){
                            $this->to_back(90144);
                        }
                        $merchant_id = $res_goods['merchant_id'];
                        $amount = $v['amount']>0?$v['amount']:1;
                        if($amount>$res_goods['amount']){
                            $this->to_back(90143);
                        }
                        $goods_name = $res_goods['name'];
                        if($res_goods['gtype']==3){
                            $res_pgoods = $m_goods->getInfo(array('id'=>$res_goods['parent_id']));
                            $goods_name = $res_pgoods['name'];
                        }
                        $ginfo = array('goods_id'=>$res_goods['id'],'price'=>$res_goods['price'],'name'=>$goods_name,'attr_name'=>$res_goods['attr_name'],
                            'type'=>$res_goods['type'],'is_localsale'=>$res_goods['is_localsale'],'merchant_id'=>$res_goods['merchant_id'],
                            'staff_id'=>$res_goods['staff_id'],'amount'=>$amount);
                        $goods[$merchant_id][] = $ginfo;
                    }
                }
            }
        }
        if(empty($goods)){
            $this->to_back(1001);
        }
        $invoice_data = array();
        if(!empty($company)){
            switch ($title_type){
                case 0:
                    $invoice_data['company'] = $company;
                    $invoice_data['credit_code'] = $credit_code;
                    $invoice_data['title_type'] = 1;
                    if(!empty($email)){
                        $invoice_data['email'] = $email;
                    }
                    break;
                case 1:
                    $invoice_data['company'] = $company;
                    $invoice_data['title_type'] = 2;
                    if(!empty($email)){
                        $invoice_data['email'] = $email;
                    }
                    break;
            }
        }

        $amount = 0;
        $total_fee = 0;
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        foreach ($goods as $v){
            foreach ($v as $gv){
                if($gv['type']==22 && $gv['is_localsale']){
                    $fields = 'hotel.area_id';
                    $res_merchantinfo = $m_merchant->getMerchantInfo($fields,array('m.id'=>$gv['merchant_id']));
                    if($res_address['area_id']!=$res_merchantinfo[0]['area_id']){
                        $this->to_back(90141);
                    }
                }
                $price = sprintf("%.2f",$gv['amount']*$gv['price']);
                $total_fee = $total_fee+$price;
                $amount = $amount+$gv['amount'];
            }
        }
        $coupon_info = array();
        if($usercoupon_id>0){
            $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
            $coupon_info = $m_coupon_user->getInfo(array('id'=>$usercoupon_id,'openid'=>$openid));
            if(!empty($coupon_info)){
                $total_fee = $total_fee - $coupon_info['money'];
            }
        }

        if($total_fee<=0){
            $this->to_back(90142);
        }

        $m_order = new \Common\Model\Smallapp\OrderModel();
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $parent_oid = 0;
        if(count($goods)>1){
            $add_data = array('openid'=>$openid,'amount'=>$amount,'total_fee'=>$total_fee,'contact'=>$contact,'phone'=>$phone,'address'=>$address,
                'status'=>10,'otype'=>127,'pay_type'=>$pay_type);
            if(!empty($order_location)){
                $add_data['box_mac'] = $order_location['box_mac'];
            }
            $parent_oid = $m_order->add($add_data);
        }
        $m_goodsactivity = new \Common\Model\Smallapp\GoodsactivityModel();
        $m_ordergift = new \Common\Model\Smallapp\OrdergiftModel();
        $m_invoice = new \Common\Model\Smallapp\OrderinvoiceModel();
        $m_orderlocation = new \Common\Model\Smallapp\OrderlocationModel();
        $trade_name = '';
        $is_use_coupon = 0;
        foreach ($goods as $k=>$v){
            $amount = 0;
            $total_money = 0;
            $merchant_id = $k;
            foreach ($v as $gv){
                if(empty($trade_name)){
                    $trade_name = $gv['name'];
                }
                $price = sprintf("%.2f",$gv['amount']*$gv['price']);
                $total_money = $total_money+$price;
                $amount = $amount+$gv['amount'];
            }
            $add_data = array('openid'=>$openid,'merchant_id'=>$merchant_id,'amount'=>$amount,'total_fee'=>$total_money,
                'status'=>10,'contact'=>$contact,'phone'=>$phone,'address'=>$address,'otype'=>$otype,'pay_type'=>$pay_type);
            if(!empty($remark)){
                $add_data['remark'] = $remark;
            }
            if($parent_oid){
                $add_data['parent_oid'] = $parent_oid;
            }
            if($sale_uid){
                $add_data['sale_uid'] = $sale_uid;
            }
            if($task_user_id>0){
                $add_data['task_user_id'] = $task_user_id;
            }
            if(!empty($order_location)){
                $add_data['box_mac'] = $order_location['box_mac'];
            }
            if(!empty($coupon_info) && $is_use_coupon==0){
                $is_use_coupon = 1;
                $add_data['usercoupon_id'] = $usercoupon_id;
                $add_data['coupon_fee'] = $coupon_info['money'];
                $add_data['total_fee'] = $total_money - $coupon_info['money'];
            }
            $order_id = $m_order->add($add_data);
            if(!empty($order_location)){
                $location_data = $order_location;
                $location_data['order_id'] = $order_id;
                $m_orderlocation->add($location_data);
            }

//            $redis->set($order_space_key,$order_id,60);
            $user_order[] = $order_id;
            $redis->set($order_cache_key,json_encode($user_order),86400);
            $order_goods = $gifts = array();
            foreach ($v as $ov){
                $order_goods[]=array('order_id'=>$order_id,'goods_id'=>$ov['goods_id'],'price'=>$ov['price'],'amount'=>$ov['amount']);
                $res_activity = $m_goodsactivity->getInfo(array('goods_id'=>$ov['goods_id']));
                if(!empty($res_activity)){
                    $res_agoods = $m_goods->getInfo(array('id'=>$res_activity['gift_goods_id']));
                    if(!empty($res_agoods) && $res_agoods['status']==1){
                        $gifts[]=array('order_id'=>$order_id,'goods_id'=>$ov['goods_id'],'gift_goods_id'=>$res_activity['gift_goods_id'],'amount'=>$ov['amount']);
                    }
                }
            }
            $m_ordergoods->addAll($order_goods);
            if(!empty($gifts)){
                $m_ordergift->addAll($gifts);
            }
            if(!empty($invoice_data)){
                $invoice_adddata = $invoice_data;
                $invoice_adddata['order_id'] = $order_id;
                $m_invoice->add($invoice_adddata);
            }
        }
        $jump_type = 2;
        if($parent_oid){
            $jump_type = 1;
            $oid = $parent_oid;
        }else{
            $oid = $order_id;
        }
        if($goods_id==C('LAIMAO_SECKILL_GOODS_ID')){
            $now_pay_type = $pay_type;
            if($now_pay_type==20){
                $ucconfig = C('ALIYUN_SMS_CONFIG');
                $alisms = new \Common\Lib\AliyunSms();
                $template_code = $ucconfig['send_laimao_order_templateid'];
                $params = array('hotel_name'=>$order_location['hotel_name'],'room_name'=>$order_location['room_name'],'amount'=>1,'order_id'=>$oid);
                $alisms::sendSms(13811966726,$params,$template_code);
            }
        }else{
            $now_pay_type = 10;
        }
        $payinfo = array();
        if($now_pay_type==10){
            $trade_name = text_substr($trade_name,10);
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            $trade_no = $m_ordermap->add(array('order_id'=>$oid,'pay_type'=>10));
            $trade_info = array('trade_no'=>$trade_no,'total_fee'=>$total_fee,'trade_name'=>$trade_name,
                'wx_openid'=>$openid,'redirect_url'=>'','attach'=>40);
            $smallapp_config = C('SMALLAPP_CONFIG');
            $pay_wx_config = C('PAY_WEIXIN_CONFIG_1594752111');
            $payconfig = array(
                'appid'=>$smallapp_config['appid'],
                'partner'=>$pay_wx_config['partner'],
                'key'=>$pay_wx_config['key']
            );
            $m_payment = new \Payment\Model\WxpayModel(3);
            $wxpay = $m_payment->pay($trade_info,$payconfig);
            $payinfo = json_decode($wxpay,true);
        }
        $resp_data = array('pay_type'=>$pay_type,'order_id'=>$oid,'payinfo'=>$payinfo,'jump_type'=>$jump_type);
        if($goods_id==C('LAIMAO_SECKILL_GOODS_ID') && $now_pay_type==20){
            $resp_data['tips'] = '已获得秒杀资格';
            $resp_data['message'] = '请凭此页面直接向餐厅服务员购买！截图也有效哦~';
        }
        $this->to_back($resp_data);
    }

    public function addGiftorder(){
        $addorder_num = 30;
        $openid = $this->params['openid'];
        $goods_id= intval($this->params['goods_id']);
        $amount = intval($this->params['amount']);
        $person_upnum = intval($this->params['person_upnum']);
        $remark = $this->params['remark'];
        $pay_type = intval($this->params['pay_type']);//10微信支付 20线下付款
        $company = $this->params['company'];
        $credit_code = $this->params['credit_code'];
        $title_type = $this->params['title_type'];//发票抬头类型 1企业 2个人
        $email = $this->params['email'];
        $uid = $this->params['uid'];
        $box_id = $this->params['box_id'];
        if($person_upnum>$amount){
            $this->to_back(90145);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $sale_uid = 0;
        if(!empty($uid)) {
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $decode_info = $hashids->decode($uid);
            if (empty($decode_info)) {
                $sale_uid = 0;
            }else{
                $sale_uid = $decode_info[0];
            }
        }
        $sale_key = C('SAPP_SALE');
        $cache_key = $sale_key.'dishorder:'.date('Ymd').':'.$openid;
        $order_space_key = $sale_key.'dishorder:spacetime'.$openid.$goods_id;

        $redis = \Common\Lib\SavorRedis::getInstance();
        $order_location = array();
        $m_area = new \Common\Model\AreaModel();
        if(!empty($box_id)){
            $box_id = intval($box_id);
            $redis->select(15);
            $cache_key = 'savor_box_'.$box_id;
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $res_hotel = json_decode($redis_hotel_info, true);

            $order_location = array('hotel_id'=>$room_info['hotel_id'],'hotel_name'=>$res_hotel['name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                'room_id'=>$box_info['room_id'],'room_name'=>$room_info['name'],'box_id'=>$box_id,'box_mac'=>$box_info['mac'],'box_type'=>$box_info['box_type'],
                'area_id'=>$res_hotel['area_id']
            );
            $res_area = $m_area->find($order_location['area_id']);
            $order_location['area_name'] = $res_area['region_name'];
        }

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

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods) || $res_goods['status']==2){
            $this->to_back(92020);
        }

        $total_fee = sprintf("%.2f",$amount*$res_goods['price']);
        $message = C('GIFT_MESSAGE');
        $add_data = array('openid'=>$openid,'merchant_id'=>$res_goods['merchant_id'],'amount'=>$amount,'person_upnum'=>$person_upnum,
            'total_fee'=>$total_fee,'status'=>10,'otype'=>6,'pay_type'=>$pay_type,'message'=>$message,'sale_uid'=>$sale_uid);
        if(!empty($remark)){
            $add_data['remark'] = $remark;
        }
        if(!empty($order_location)){
            $add_data['box_mac'] = $order_location['box_mac'];
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $order_id = $m_order->add($add_data);

        if(!empty($order_location)){
            $m_orderlocation = new \Common\Model\Smallapp\OrderlocationModel();
            $order_location['order_id'] = $order_id;
            $m_orderlocation->add($order_location);
        }

//        $redis->set($order_space_key,$order_id,60);
        $user_order[] = $order_id;
        $redis->set($cache_key,json_encode($user_order),86400);

        $order_goods = array('order_id'=>$order_id,'goods_id'=>$res_goods['id'],'price'=>$res_goods['price'],'amount'=>$amount);
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $m_ordergoods->add($order_goods);

        $m_goodsactivity = new \Common\Model\Smallapp\GoodsactivityModel();
        $res_activity = $m_goodsactivity->getInfo(array('goods_id'=>$res_goods['id']));
        if(!empty($res_activity)){
            $res_agoods = $m_goods->getInfo(array('id'=>$res_activity['gift_goods_id']));
            if(!empty($res_agoods) && $res_agoods['status']==1){
                $gifts=array('order_id'=>$order_id,'goods_id'=>$res_goods['id'],'gift_goods_id'=>$res_activity['gift_goods_id'],'amount'=>$amount);
                $m_ordergift = new \Common\Model\Smallapp\OrdergiftModel();
                $m_ordergift->addData($gifts);
            }
        }

        $invoice_data = array();
        if(!empty($company)){
            switch ($title_type){
                case 0:
                    $invoice_data['company'] = $company;
                    $invoice_data['email'] = $email;
                    $invoice_data['credit_code'] = $credit_code;
                    $invoice_data['title_type'] = 1;
                    break;
                case 1:
                    $invoice_data['company'] = $company;
                    $invoice_data['email'] = $email;
                    $invoice_data['title_type'] = 2;
                    break;
            }
            if(!empty($invoice_data)){
                $invoice_data['order_id'] = $order_id;
                $m_invoice = new \Common\Model\Smallapp\OrderinvoiceModel();
                $m_invoice->add($invoice_data);
            }
        }

        $resp_data = array('pay_type'=>$pay_type,'order_id'=>$order_id);
        $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
        $trade_no = $m_ordermap->add(array('order_id'=>$order_id,'pay_type'=>10));

        $trade_name = $res_goods['name'];
        if($res_goods['gtype']==3){
            $res_pgoods = $m_goods->getInfo(array('id'=>$res_goods['parent_id']));
            $trade_name = $res_pgoods['name'];
        }
        $trade_info = array('trade_no'=>$trade_no,'total_fee'=>$total_fee,'trade_name'=>$trade_name,
            'wx_openid'=>$openid,'redirect_url'=>'','attach'=>50);
        $smallapp_config = C('SMALLAPP_CONFIG');
        $pay_wx_config = C('PAY_WEIXIN_CONFIG_1594752111');
        $payconfig = array(
            'appid'=>$smallapp_config['appid'],
            'partner'=>$pay_wx_config['partner'],
            'key'=>$pay_wx_config['key']
        );
        $m_payment = new \Payment\Model\WxpayModel(3);
        $wxpay = $m_payment->pay($trade_info,$payconfig);
        $payinfo = json_decode($wxpay,true);
        $resp_data['payinfo'] = $payinfo;
        $this->to_back($resp_data);
    }

    public function reserveResult(){
        $order_id = intval($this->params['order_id']);
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(90134);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('*',array('openid'=>$res_order['openid']),'id desc');
        $amount = $res_order['amount'];

        $day_time = 5*86400;
        $expire_date = date('Y-m-d',strtotime($res_order['add_time'])+$day_time);
        $order_data = array('order_id'=>$order_id,'merchant_id'=>$res_order['merchant_id'],'amount'=>$amount,
            'total_fee'=>$res_order['total_fee'],'type'=>$res_order['otype'],'message'=>$res_order['message'],
            'openid'=>$res_order['openid'],'nickName'=>$res_user['nickName'],'expire_date'=>$expire_date
        );
        $oss_host = get_oss_host();

        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.gtype,goods.attr_name,goods.parent_id,
        goods.model_media_id,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
        $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        $goods = array();
        $m_media = new \Common\Model\MediaModel();
        foreach ($res_goods as $gv){
            $goods_name = $gv['goods_name'];
            $cover_imgs_info = explode(',',$gv['cover_imgs']);
            $img = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
            if($gv['gtype']==3){
                $res_ginfo = $m_goods->getInfo(array('id'=>$gv['parent_id']));
                $goods_name = $res_ginfo['name'];
                $gv['gtype'] = $res_ginfo['gtype'];
                if($gv['model_media_id']){
                    $res_media = $m_media->getMediaInfoById($gv['model_media_id']);
                    $img = $res_media['oss_addr']."?x-oss-process=image/resize,p_50/quality,q_80";
                }
            }

            $ginfo = array('id'=>$gv['goods_id'],'name'=>$goods_name,'price'=>$gv['price'],'amount'=>intval($gv['amount']),
                'attr_name'=>$gv['attr_name'],'img'=>$img,'gtype'=>$gv['gtype']);
            $goods[]=$ginfo;
        }
        $order_data['goods'] = $goods[0];

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $m_media = new \Common\Model\MediaModel();
        $where = array('m.id'=>$res_order['merchant_id']);
        $fields = 'm.id,hotel.id as hotel_id,hotel.name,hotel.mobile,hotel.tel,ext.hotel_cover_media_id,hotel.area_id,hotel.county_id,hotel.addr';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        $merchant = array('name'=>$res_merchant[0]['name'],'mobile'=>$res_merchant[0]['mobile'],'tel'=>$res_merchant[0]['mobile'],
            'merchant_id'=>$res_order['merchant_id'],'img'=>'');
        if(!empty($res_merchant[0]['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
            $merchant['img'] = $res_media['oss_addr'];
        }
        $order_data['merchant'] = $merchant;
        $share_wx = $m_order->shareWeixin($order_data['nickName'],$order_data['goods']['name']);
        $order_data['share_title'] = $share_wx['title'];

        $this->to_back($order_data);
    }

    public function modifyMessage(){
        $openid = $this->params['openid'];
        $message = $this->params['message'];
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
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $m_order->updateData(array('id'=>$order_id),array('message'=>$message));
        $this->to_back(array());
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
            $selfpick_time = date('Y-m-d')." $selfpick_time";
            $tmp_self_time = strtotime($selfpick_time);
            if($tmp_self_time<time()){
                $this->to_back(93044);
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

        $address_area_id = 0;
        if($address_id){
            $m_area = new \Common\Model\AreaModel();
            $m_address = new \Common\Model\Smallapp\AddressModel();
            $res_address = $m_address->getInfo(array('id'=>$address_id));
            $address_area_id = $res_address['area_id'];
            $res_area = $m_area->find($res_address['area_id']);
            $res_county = $m_area->find($res_address['county_id']);

            $contact = $res_address['consignee'];
            $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        }
        if(empty($phone) && !empty($res_address['phone'])){
            $phone = $res_address['phone'];
        }

        if($delivery_type==1 && (empty($contact) || empty($phone) || empty($address))){
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
        $fields = 'm.id as merchant_id,m.is_shopself,m.delivery_platform,m.status,hotel.id as hotel_id,hotel.name as hotel_name,hotel.area_id';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);

        if($res_merchant[0]['delivery_platform']==1 && $pay_type!=10){
            $this->to_back(90135);
        }
        if($delivery_type==1 && $address_area_id && $address_area_id!=$res_merchant[0]['area_id']){
            $this->to_back(90140);
        }

        $amount = 0;
        $total_fee = 0;
        foreach ($goods as $gv){
            $price = sprintf("%.2f",$gv['amount']*$gv['price']);
            $total_fee = $total_fee+$price;
            $amount = $amount+$gv['amount'];
        }
        $delivery_fee = 0;
        /*
        if($res_merchant[0]['delivery_platform']==1 && $delivery_type==1 && $address_id){
            $config = C('DADA');
            $hotel_id = $res_merchant[0]['hotel_id'];
            $order_no= getmicrotime();
            $dada = new \Common\Lib\Dada($config);
            $callback = http_host();
            $res = $dada->queryDeliverFee($hotel_id,$order_no,$res_area['area_no'],$total_fee,
                $contact,$address,$phone,$res_address['lat'],$res_address['lng'],$callback);
            if($res['code']==0 && !empty($res['result'])){
                $delivery_fee = $res['result']['fee'];
            }
        }
        */
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
        if(!empty($company)){
            switch ($title_type){
                case 0:
                    $invoice_data['company'] = $company;
                    $invoice_data['credit_code'] = $credit_code;
                    $invoice_data['title_type'] = 1;
                    break;
                case 1:
                    $invoice_data['company'] = $company;
                    $invoice_data['title_type'] = 2;
                    break;
            }
            if(!empty($invoice_data)){
                $invoice_data['order_id'] = $order_id;
                $m_invoice = new \Common\Model\Smallapp\OrderinvoiceModel();
                $m_invoice->add($invoice_data);
            }
        }

        $resp_data = array('pay_type'=>$pay_type,'order_id'=>$order_id);
        if($pay_type==10){
            $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
            $trade_no = $m_ordermap->add(array('order_id'=>$order_id,'pay_type'=>10));

            $trade_name = $goods[0]['name'];
            $trade_info = array('trade_no'=>$trade_no,'total_fee'=>$total_fee,'trade_name'=>$trade_name,
                'wx_openid'=>$openid,'redirect_url'=>'','attach'=>30);
            $smallapp_config = C('SMALLAPP_CONFIG');
            $pay_wx_config = C('PAY_WEIXIN_CONFIG_1594752111');
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
//                $hotel_id = $config['shop_no'];//上线后去除

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
        $status = intval($this->params['status']);//1待处理 2已完成 3待发货 4已发货 5已取消 6赠送中 7已过期 8我收到
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $type = isset($this->params['type'])?intval($this->params['type']):3;//类型0全部 3普通外卖订单 5全国售订单 6赠送礼品订单 7转赠订单 8团购订单
        if(empty($pagesize)){
            $pagesize =10;
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid,nickName',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $where = array('openid'=>$openid);
        if($type){
            if($type==5){
                $where['otype'] = array('in',array(5,8));
            }else{
                $where['otype'] = $type;
            }
        }else{
            $where['otype'] = 3;
        }

        switch ($status){
            case 1:
                $where['status'] = array('in',array(1,13,14,15,16,51));
                break;
            case 2:
                $where['status'] = array('in',array(2,17,18,19,53));
                break;
            case 3:
                $where['status'] = 52;
                break;
            case 4:
                $where['status'] = 53;
                break;
            case 5:
                $where['status'] = array('in',array(18,19));
                break;
            case 6:
                $where['otype'] = array('in',array(6,7));
                $where['status'] = array('in',array(12,61,71));
                break;
            case 7:
                $where['status'] = 62;
                break;
            case 8:
                $where['otype'] = array('in',array(6,7));
                $where['status'] = 63;
                break;
            default:
                if($type==6){
                    $exclude_status = array(10,11);
                }else{
                    $exclude_status = array(10,11);
                }
                $where['status'] = array('not in',$exclude_status);
        }
        $all_nums = $page * $pagesize;
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $fields = 'id as order_id,gift_oid,merchant_id,price,amount,otype,total_fee,status,contact,phone,address,delivery_time,remark,add_time,finish_time,usercoupon_id';
        $res_order = $m_order->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $m_media = new \Common\Model\MediaModel();
            $datalist = $res_order['list'];
            $oss_host = get_oss_host();
            $all_status = C('ORDER_STATUS');
            foreach($datalist as $k=>$v){
                $datalist[$k]['type'] = $v['otype'];
                $datalist[$k]['status_str'] = $all_status[$v['status']];
                $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));
                if($v['finish_time']=='0000-00-00 00:00:00'){
                    $datalist[$k]['finish_time'] = '';
                }
                $coupon_info = '';
                if(!empty($v['usercoupon_id'])){
                    $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
                    $res_coupon = $m_coupon_user->getInfo(array('id'=>$v['usercoupon_id']));
                    $coupon_info = "满{$res_coupon['min_price']}减{$res_coupon['money']}";
                }
                $datalist[$k]['coupon_info'] = $coupon_info;

                $order_id = $v['order_id'];
                $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.gtype,goods.attr_name,goods.parent_id,
                goods.model_media_id,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
                $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                $goods = array();
                foreach ($res_goods as $gv){
                    $goods_name = $gv['goods_name'];
                    $cover_imgs_info = explode(',',$gv['cover_imgs']);
                    $img = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    if($gv['gtype']==3){
                        $res_ginfo = $m_goods->getInfo(array('id'=>$gv['parent_id']));
                        $goods_name = $res_ginfo['name'];
                        $gv['gtype'] = $res_ginfo['gtype'];
                        if($gv['model_media_id']){
                            $res_media = $m_media->getMediaInfoById($gv['model_media_id']);
                            $img = $res_media['oss_addr']."?x-oss-process=image/resize,p_50/quality,q_80";
                        }
                    }
                    $ginfo = array('id'=>$gv['goods_id'],'name'=>$goods_name,'attr_name'=>$gv['attr_name'],'price'=>$gv['price'],
                        'gtype'=>$gv['gtype'],'amount'=>$gv['amount'],'status'=>$gv['status'],'img'=>$img);
                    $goods[]=$ginfo;
                }
                $datalist[$k]['goods'] = $goods;

                $where = array('m.id'=>$v['merchant_id']);
                $fields = 'm.id,hotel.name,ext.hotel_cover_media_id,hotel.area_id';
                $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
                $merchant = array('name'=>$res_merchant[0]['name'],'merchant_id'=>$v['merchant_id'],'area_id'=>$res_merchant[0]['area_id']);
                $merchant['img'] = '';
                if(!empty($res_merchant[0]['hotel_cover_media_id'])){
                    $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
                    $merchant['img'] = $res_media['oss_addr'];
                }
                $datalist[$k]['merchant'] = $merchant;
                $give_type = 0;
                if($v['otype']==6 || $v['otype']==7){
                    if($v['gift_oid']==0){
                        $give_type = 1;
                    }else{
                        $give_type = 2;
                    }
                }
                $datalist[$k]['give_type'] = $give_type;
                $share_wx = $m_order->shareWeixin($user_info['nickName'],$datalist[$k]['goods'][0]['name']);
                $datalist[$k]['share_title'] = $share_wx['title'];

                unset($datalist[$k]['gift_oid']);

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
        $user_info = $m_user->getOne('id,openid,nickName,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['openid']!=$openid){
            $this->to_back(90134);
        }

        if($res_order['selfpick_time']=='0000-00-00 00:00:00'){
            $res_order['selfpick_time'] = '';
        }

        $order_data = array('order_id'=>$order_id,'merchant_id'=>$res_order['merchant_id'],'amount'=>$res_order['amount'],
            'total_fee'=>$res_order['total_fee'],'status'=>$res_order['status'],'status_str'=>'',
            'contact'=>$res_order['contact'],'phone'=>$res_order['phone'],'address'=>$res_order['address'],'tableware'=>$res_order['tableware'],
            'remark'=>$res_order['remark'],'delivery_type'=>$res_order['delivery_type'],'delivery_time'=>$res_order['delivery_time'],
            'delivery_fee'=>$res_order['delivery_fee'],'selfpick_time'=>$res_order['selfpick_time'],'finish_time'=>$res_order['finish_time'],
            'type'=>$res_order['otype'],'message'=>$res_order['message'],'coupon_info'=>''
        );
        $order_data['add_time'] = date('Y-m-d H:i',strtotime($res_order['add_time']));
        if($res_order['finish_time']=='0000-00-00 00:00:00'){
            $order_data['finish_time'] = '';
        }
        if(!empty($res_order['usercoupon_id'])){
            $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
            $res_coupon = $m_coupon_user->getInfo(array('id'=>$res_order['usercoupon_id']));
            $order_data['coupon_info'] = "满{$res_coupon['min_price']}减{$res_coupon['money']}";
        }

        $order_status_str = C('ORDER_STATUS');
        if(isset($order_status_str[$res_order['status']])){
            $order_data['status_str'] = $order_status_str[$res_order['status']];
        }

        $oss_host = get_oss_host();
        $gifts = array();
        $m_ordergift = new \Common\Model\Smallapp\OrdergiftModel();
        $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.gtype,goods.attr_name,goods.parent_id,
        goods.model_media_id,goods.cover_imgs,og.amount,og.goods_id as buy_goods_id';
        $res_gift_goods = $m_ordergift->getOrderGiftgoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        if(!empty($res_gift_goods)){
            foreach ($res_gift_goods as $v){
                $gifts[$v['buy_goods_id']]=array('id'=>$v['goods_id'],'name'=>$v['goods_name'],'amount'=>intval($v['amount']));
            }
        }
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.gtype,goods.attr_name,goods.parent_id,
        goods.model_media_id,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
        $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        $goods = array();
        $m_media = new \Common\Model\MediaModel();
        foreach ($res_goods as $gv){
            $goods_name = $gv['goods_name'];
            $cover_imgs_info = explode(',',$gv['cover_imgs']);
            $img = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
            if($gv['gtype']==3){
                $res_ginfo = $m_goods->getInfo(array('id'=>$gv['parent_id']));
                $goods_name = $res_ginfo['name'];
                $gv['gtype'] = $res_ginfo['gtype'];
                if($gv['model_media_id']){
                    $res_media = $m_media->getMediaInfoById($gv['model_media_id']);
                    $img = $res_media['oss_addr']."?x-oss-process=image/resize,p_50/quality,q_80";
                }
            }
            $gift_goods_id = 0;
            $gift_name = '';
            if(isset($gifts[$gv['goods_id']])){
                $gift_goods_id = $gifts[$gv['goods_id']]['id'];
                $gift_name = $gifts[$gv['goods_id']]['name'];
            }
            $ginfo = array('id'=>$gv['goods_id'],'name'=>$goods_name,'price'=>$gv['price'],'amount'=>intval($gv['amount']),
                'status'=>$gv['status'],'gtype'=>$gv['gtype'],'attr_name'=>$gv['attr_name'],'img'=>$img,
                'gift_goods_id'=>$gift_goods_id,'gift_name'=>$gift_name);
            $goods[]=$ginfo;
        }
        $order_data['goods'] = $goods;

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $m_media = new \Common\Model\MediaModel();
        $where = array('m.id'=>$res_order['merchant_id']);
        $fields = 'm.id,hotel.id as hotel_id,hotel.name,hotel.mobile,hotel.tel,ext.hotel_cover_media_id,hotel.area_id,hotel.county_id,hotel.addr';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);

        if($order_data['delivery_type']==2){
            $m_area = new \Common\Model\AreaModel();
            $res_area = $m_area->find($res_merchant[0]['area_id']);
            $res_county = $m_area->find($res_merchant[0]['county_id']);
            $order_data['address'] = $res_area['region_name'].$res_county['region_name'].$res_merchant[0]['addr'];
        }

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
            $invoice['email'] = $res_invoice['email'];
            $invoice['title_type'] = intval($res_invoice['title_type']);
        }
        $order_data['invoice'] = $invoice;

        $m_orderexpress = new \Common\Model\Smallapp\OrderexpressModel();
        $express = $m_orderexpress->getExpressList($res_order['id']);
        $order_data['express'] = $express;

        $dada_info = $this->dada_transporter($res_order);
        $order_data['transporter'] = $dada_info['transporter'];
        $order_data['user_location'] = $dada_info['user_location'];
        $order_data['markers'] = $dada_info['markers'];
        $order_data['polyline'] = $dada_info['polyline'];
        $order_data['distance'] = $dada_info['distance'];

        $expire_date = $nickName = '';
        $receive_num = 0;
        $gift_records = array();
        if($res_order['otype']==6 || $res_order['otype']==7){
            $day_time = 5*86400;
            $expire_time = strtotime($res_order['add_time'])+$day_time;
            $expire_date = date('Y-m-d',$expire_time);
            $res_gift_receive = $m_order->getReceiveOrders($order_id);
            if(!empty($res_gift_receive)){
                $receive_num = $res_gift_receive['rnum'];
                $gift_records = $res_gift_receive['list'];
            }
            if(empty($res_order['gift_oid'])){
                $nickName = $user_info['nickName'];
            }else{
                $res_gorder = $m_order->getInfo(array('id'=>$res_order['gift_oid']));
                $where = array('openid'=>$res_gorder['openid'],'status'=>1);
                $user_info = $m_user->getOne('id,openid,nickName,mpopenid',$where,'');
                $nickName = $user_info['nickName'];
            }
        }

        $order_data['nickName'] = $nickName;
        $order_data['expire_date'] = $expire_date;
        $order_data['receive_num'] = $receive_num;
        $order_data['gift_records'] = $gift_records;
        $order_data['service_tel'] = '13810024884';
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
        if(in_array($res_order['status'],array(18,19,53,61,62,63))){
            $this->to_back(90136);
        }
        if(!in_array($res_order['status'],array(12,13,51,52))){
            $this->to_back(90137);
        }
        $message = '取消订单成功';
        $is_cancel = 0;
        if($res_order['pay_type']==10){
            if(!empty($res_order['parent_oid'])){
                $refund_oid = $res_order['parent_oid'];
                $res_porder = $m_order->getInfo(array('id'=>$refund_oid));
                $pay_fee = $res_porder['pay_fee'];
            }else{
                $refund_oid = $order_id;
                $pay_fee = $res_order['pay_fee'];
            }
            $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
            $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$refund_oid));
            if(!empty($res_orderserial)){
                $m_baseinc = new \Payment\Model\BaseIncModel();
                $payconfig = $m_baseinc->getPayConfig(2);
                $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
                $res_ordermap = $m_ordermap->getDataList('id',array('order_id'=>$refund_oid),'id desc',0,1);
                $trade_no = $res_ordermap['list'][0]['id'];

                $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$order_id,'pay_fee'=>$pay_fee,'refund_money'=>$res_order['pay_fee']);
                $m_wxpay = new \Payment\Model\WxpayModel();
                $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                if(isset($res['err_code'])){
                    $payconfig = $m_baseinc->getPayConfigOld(2);
                    $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                }
                if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                    $m_order->updateData(array('id'=>$order_id),array('status'=>19,'finish_time'=>date('Y-m-d H:i:s')));
                    $is_cancel = 1;
                    $message = '取消订单成功,且已经退款.款项在1到7个工作日内,退还到你的支付账户';
                }else{
                    $message = '取消订单失败';
                }
            }else{
                $message = '取消订单失败';
            }
        }else{
            $is_cancel = 1;
            $m_order->updateData(array('id'=>$order_id),array('status'=>19,'finish_time'=>date('Y-m-d H:i:s')));
        }
        if($is_cancel && in_array($res_order['otype'],array(5,6,8))){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $gfields = 'goods.id as goods_id,goods.status,goods.amount as all_amount,og.amount';
            $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
            foreach ($res_goods as $v){
                $now_amount = $v['all_amount'] + $v['amount'];
                $updata = array('amount'=>$now_amount);
                $m_goods->updateData(array('id'=>$v['goods_id']),$updata);
            }
            $m_income = new \Common\Model\Smallapp\UserincomeModel();
            $m_income->delData(array('order_id'=>$order_id));
        }
        $res_data = array('message'=>$message);
        $this->to_back($res_data);
    }

    public function addActivityOrder(){
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
                $sale_uid = 0;
            }else{
                $sale_uid = $decode_info[0];
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

    private function dada_transporter($res_order){
        $transporter = array();
        $user_location = array();
        $markers = array();
        $polyline = array();
        $distance = '';

        if($res_order['delivery_type']==1 && $res_order['status']==16){
            $lnglat_arr = explode(',',$res_order['lnglat']);
            $user_location = array('lng'=>$lnglat_arr[0],'lat'=>$lnglat_arr[1]);

            $config = C('DADA');
            $dada = new \Common\Lib\Dada($config);
            $res = $dada->queryOrder($res_order['id']);
            if($res['code']==0 && !empty($res['result'])){
                $dd_res = $res['result'];
                $ddstatus_code = $dd_res['statusCode'];
                $status_map = array('1'=>14,'2'=>15,'3'=>16,'4'=>17);//待接单＝1 待取货＝2 配送中＝3 已完成＝4 已取消＝5 已过期＝7 指派单=8 妥投异常之物品返回中=9 妥投异常之物品返回完成=10 系统故障订单发布失败=1000
                if(isset($status_map[$ddstatus_code]) && $ddstatus_code==3){
                    $transporter = array('name'=>$dd_res['transporterName'],'phone'=>$dd_res['transporterPhone']);
                    $markers = array(
                        array(
                            'iconPath'=>'/images/icon/takeaway_rider.png',
                            'id'=>0,
                            'latitude'=>$dd_res['transporterLat'],
                            'longitude'=>$dd_res['transporterLng'],
                            'width'=>24,
                            'height'=>24,
                        ),
                        array(
                            'iconPath'=>'/images/icon/takeaway_user.png',
                            'id'=>1,
                            'latitude'=>$user_location['lat'],
                            'longitude'=>$user_location['lng'],
                            'width'=>24,
                            'height'=>24,
                        ),
                    );
                    $res_distance = geo_distance($dd_res['transporterLat'], $dd_res['transporterLng'], $order_data['user_location']['lat'], $order_data['user_location']['lng']);
                    if($res_distance>1000){
                        $distance = $res_distance/1000;
                        $distance = sprintf("%.2f",$distance);
                        $distance = $distance.'km';
                    }else{
                        $distance = sprintf("%.2f",$res_distance);
                        $distance = $distance.'m';
                    }
                    $polyline = array(
                        array(
                            'points'=>array(
                                array('longitude'=>$markers[0]['longitude'],'latitude'=>$markers[0]['latitude']),
                                array('longitude'=>$user_location['lng'],'latitude'=>$user_location['lat']),
                            ),
                            'color'=>'#FF0000DD',
                            'width'=>2,
                            'dottedLine'=>true
                        )
                    );
                }
            }
        }
        $res_data = array('transporter'=>$transporter,'user_location'=>$user_location,'markers'=>$markers,
            'polyline'=>$polyline,'distance'=>$distance);
        return $res_data;

    }

}