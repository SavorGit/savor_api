<?php
namespace Smallapp45\Controller;
use \Common\Controller\CommonController as CommonController;

class GiftController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'info':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'receive':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'receive_num'=>1001);
                break;
            case 'confirmAddress':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'address_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function info(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(90134);
        }

        $amount = $res_order['amount'];
        $person_upnum = $res_order['person_upnum'];
        $order_receive_key = C('SAPP_ORDER_GIFT').$order_id.':receive';
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($order_receive_key);
        if(!empty($res_cache)){
            $receive_data = json_decode($res_cache,true);
        }else{
            $res_receive = $m_order->getDataList('id,openid,amount,add_time,address',array('gift_oid'=>$order_id));
            $receive_data = array();
            foreach ($res_receive as $v){
                $receive_data[$v['openid']] = $v;
            }
        }
        $all_receive_num = 0;
        foreach ($receive_data as $v){
            $all_receive_num +=$v['amount'];
        }
        $remain_num = $amount-$all_receive_num;
        $day_time = 5*86400;
        $expire_time = strtotime($res_order['add_time'])+$day_time;
        $receive_type = 1;//1待领取 2已领取 3已领取待添加收货地址 4已领完 5已失效
        $r_order_id = $order_id;
        if(isset($receive_data[$openid])){
            $receive_type = 2;
            if(empty($receive_data[$openid]['address'])){
                $receive_type = 3;
                $res_rorder = $m_order->getInfo(array('gift_oid'=>$order_id,'openid'=>$openid));
                $r_order_id = $res_rorder['id'];
            }
        }else{
            switch ($res_order['status']){
                case 17:
                    $receive_type = 4;
                    break;
                case 62:
                    $receive_type = 5;
                    break;
                case 18:
                case 19:
                    $expire_time = strtotime($res_order['add_time']);
                    $receive_type = 5;
                    break;
                default:
                    if($all_receive_num>=$amount){
                        $receive_type = 4;
                    }
                    if(time()>$expire_time){
                        $receive_type = 5;
                    }
            }
        }

        $person_upnum = $remain_num>=$person_upnum?$person_upnum:$remain_num;

        $expire_date = date('Y-m-d',$expire_time);
        $res_user = $m_user->getOne('*',array('openid'=>$res_order['openid']),'id desc');
        $order_data = array('order_id'=>$r_order_id,'merchant_id'=>$res_order['merchant_id'],'amount'=>$amount,'receive_num'=>$all_receive_num,
            'person_upnum'=>$person_upnum,'total_fee'=>$res_order['total_fee'],'type'=>$res_order['otype'],'message'=>$res_order['message'],
            'openid'=>$res_order['openid'],'nickName'=>$res_user['nickName'],'expire_date'=>$expire_date,'receive_type'=>$receive_type,
        );
        $oss_host = "http://".C('OSS_HOST').'/';

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
                'attr_name'=>$gv['attr_name'],'img'=>$img);
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

        $records = array();
        if(!empty($receive_data)){
            foreach ($receive_data as $v){
                $fields = 'id,openid,avatarUrl,nickName';
                $where = array('openid'=>$v['openid']);
                $res_user = $m_user->getOne($fields,$where,'id desc');
                $info = array('openid'=>$v['openid'],'avatarUrl'=>$res_user['avatarUrl'],'nickName'=>$res_user['nickName'],
                    'amount'=>$v['amount'],'add_time'=>$v['add_time'],'time_str'=>viewTimes(strtotime($v['add_time'])));
                $records[]=$info;
            }
        }
        $order_data['records'] = $records;
        $this->to_back($order_data);
    }


    public function receive(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);
        $receive_num = intval($this->params['receive_num']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(90134);
        }
        if(in_array($res_order['status'],array(17,18,19,62))){
            $this->to_back(90147);
        }

        $amount = $res_order['amount'];
        $person_upnum = $res_order['person_upnum'];
        $order_receive_key = C('SAPP_ORDER_GIFT').$order_id.':receive';
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($order_receive_key);
        if(!empty($res_cache)){
            $receive_data = json_decode($res_cache,true);
        }else{
            $res_receive = $m_order->getDataList('id,openid,amount,add_time,address',array('gift_oid'=>$order_id));
            $receive_data = array();
            foreach ($res_receive as $v){
                $receive_data[$v['openid']] = $v;
            }
        }
        $all_receive_num = 0;
        foreach ($receive_data as $v){
            $all_receive_num +=$v['amount'];
        }
        $remain_num = $amount-$all_receive_num;

        $receive_type = 1;//1待领取 2已领取 3已领取待添加收货地址 4已领完 5已失效
        if(isset($receive_data[$openid])){
            $receive_type = 2;
            if(empty($receive_data[$openid]['address'])){
                $receive_type = 3;
            }
        }else{
            switch ($res_order['status']){
                case 17:
                    $receive_type = 4;
                    break;
                case 62:
                    $receive_type = 5;
                    break;
                case 18:
                case 19:
                    $expire_time = strtotime($res_order['add_time']);
                    $receive_type = 5;
                default:
                    if($all_receive_num>=$amount){
                        $receive_type = 4;
                    }else{
                        $person_upnum = $remain_num>=$person_upnum?$person_upnum:$remain_num;
                        if($receive_num>$person_upnum){
                            $this->to_back(90146);
                        }
                    }
                    $day_time = 5*86400;
                    $expire_time = strtotime($res_order['add_time'])+$day_time;
                    if(time()>$expire_time){
                        $receive_type = 5;
                    }
            }
        }

        if($receive_type==1){
            $redis->select(5);
            $order_receive_num_key = C('SAPP_ORDER_GIFT').$order_id.':receive_num';
            $res_receive_num = $redis->get($order_receive_num_key);
            if(!empty($res_receive_num)){
                $receive_nums = json_decode($res_receive_num,true);
            }else{
                $receive_nums = array();
            }
            $receive_nums[$openid] = array('num'=>$receive_num,'time'=>date('Y-m-d H:i:s'));
            $redis->set($order_receive_num_key,json_encode($receive_nums),86400*7);

            $order_receive_queue_key = C('SAPP_ORDER_GIFT').$order_id.':receive_queue';
            $res_receive_queue = $redis->lgetrange($order_receive_queue_key,0,1000);
            if(!in_array($openid,$res_receive_queue)){
                $redis->rpush($order_receive_queue_key,$openid);
            }
        }
        $res_data = array('receive_type'=>$receive_type,'order_id'=>$order_id,'remain_num'=>$remain_num);
        $this->to_back($res_data);
    }

    public function receiveResult(){
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
        if(empty($res_order)){
            $this->to_back(90134);
        }
        if(in_array($res_order['status'],array(17,18,19,62))){
            $this->to_back(90147);
        }

        $order_receive_key = C('SAPP_ORDER_GIFT').$order_id.':receive';
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($order_receive_key);
        if(!empty($res_cache)){
            $receive_data = json_decode($res_cache,true);
        }else{
            $res_receive = $m_order->getDataList('id,openid,amount,add_time,address',array('gift_oid'=>$order_id));
            $receive_data = array();
            foreach ($res_receive as $v){
                $receive_data[$v['openid']] = $v;
            }
        }
        $all_receive_num = 0;
        foreach ($receive_data as $v){
            $all_receive_num +=$v['amount'];
        }
        $amount = $res_order['amount'];

        $receive_type = 1;//1待领取 2已领取 3已领取待添加收货地址 4已领完 5已失效
        if(isset($receive_data[$openid])){
            $receive_type = 2;
            if(empty($receive_data[$openid]['address'])){
                $receive_type = 3;
            }
        }else{
            switch ($res_order['status']){
                case 17:
                    $receive_type = 4;
                    break;
                case 62:
                    $receive_type = 5;
                    break;
                case 18:
                case 19:
                    $expire_time = strtotime($res_order['add_time']);
                    $receive_type = 5;
                default:
                    if($all_receive_num>=$amount){
                        $receive_type = 4;
                    }
                    $day_time = 5*86400;
                    $expire_time = strtotime($res_order['add_time'])+$day_time;
                    if(time()>$expire_time){
                        $receive_type = 5;
                    }
            }
        }
        $row_id = 0;
        if($receive_type==1){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $m_goodsactivity = new \Common\Model\Smallapp\GoodsactivityModel();
            $m_ordergift = new \Common\Model\Smallapp\OrdergiftModel();
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $res_ogoods = $m_ordergoods->getDataList('*',array('order_id'=>$order_id),'id asc');

            $redis->select(5);
            $order_receive_num_key = C('SAPP_ORDER_GIFT').$order_id.':receive_num';
            $res_receive_num = $redis->get($order_receive_num_key);
            if(!empty($res_receive_num)){
                $receive_nums = json_decode($res_receive_num,true);
            }else{
                $receive_nums = array();
            }
            $order_receive_queue_key = C('SAPP_ORDER_GIFT').$order_id.':receive_queue';
            for ($i=0;$i<$amount;$i++) {
                $receive_openid = $redis->lpop($order_receive_queue_key);
                if (empty($receive_openid)) {
                    break;
                }
                $receive_num = intval($receive_nums[$receive_openid]['num']);
                $tmp_remain_num = $amount-$all_receive_num;
                if($tmp_remain_num<=0){
                    break;
                }
                if($tmp_remain_num>=$receive_num){
                    $all_receive_num +=$receive_num;
                }else{
                    $receive_num = $tmp_remain_num;
                    $all_receive_num +=$receive_num;
                }

                $r_data = array('openid'=>$receive_openid,'amount'=>$receive_num,'add_time'=>date('Y-m-d H:i:s'),'status'=>63);
                $receive_data[$receive_openid] = $r_data;
                $redis->set($order_receive_key,json_encode($receive_data),86400*7);

                $r_data['otype'] = 6;
                $r_data['gift_oid'] = $order_id;
                $r_data['price'] = $res_ogoods[0]['price'];
                $total_fee = sprintf("%.2f",$r_data['amount']*$r_data['price']);
                $r_data['total_fee'] = $total_fee;
                $r_data['merchant_id'] = $res_order['merchant_id'];
                $row_id = $m_order->addData($r_data);

                $order_goods = array('order_id'=>$row_id,'goods_id'=>$res_ogoods[0]['goods_id'],
                    'price'=>$res_ogoods[0]['price'],'amount'=>$receive_num);
                $m_ordergoods->addData($order_goods);

                $res_activity = $m_goodsactivity->getInfo(array('goods_id'=>$order_goods['goods_id']));
                if(!empty($res_activity)){
                    $res_agoods = $m_goods->getInfo(array('id'=>$res_activity['gift_goods_id']));
                    if(!empty($res_agoods) && $res_agoods['status']==1){
                        $gifts=array('order_id'=>$order_goods['order_id'],'goods_id'=>$order_goods['goods_id'],
                            'gift_goods_id'=>$res_activity['gift_goods_id'],'amount'=>$order_goods['amount']);
                        $m_ordergift->add($gifts);
                    }
                }

                if($receive_openid==$openid){
                    $receive_type = 3;
                }
            }
            $remain_num = $amount-$all_receive_num;
            $status = 0;
            if($remain_num>0){
                if($res_order['status']==12){
                    $status = 61;
                }
            }else{
                $status = 17;
            }
            if($status){
                $odata  = array('status'=>$status);
                if($status==17){
                    $odata['finish_time'] = date('Y-m-d H:i:s');
                }
                $m_order->updateData(array('id'=>$order_id),$odata);
            }
        }
        $message = '';
        if($receive_type==3){
            $where = array('openid'=>$res_order['openid']);
            $user_info = $m_user->getOne('id,openid,nickName',$where,'id desc');
            $message = "恭喜您领取到了{$user_info['nickName']}的赠品，请选择您的收货地址";
        }

        $res_data = array('receive_type'=>$receive_type,'order_id'=>$row_id,'message'=>$message);
        $this->to_back($res_data);
    }

    public function confirmAddress(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);
        $address_id = intval($this->params['address_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(90134);
        }
        $m_area = new \Common\Model\AreaModel();
        $m_address = new \Common\Model\Smallapp\AddressModel();
        $res_address = $m_address->getInfo(array('id'=>$address_id));

        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $res_ordergoods = $m_ordergoods->getDataList('*',array('order_id'=>$order_id),'id desc',0,1);
        $goods_id = $res_ordergoods['list'][0]['goods_id'];
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goodsinfo = $m_goods->getGoodsInfo('area.id as goods_areaid,a.is_localsale',array('a.id'=>$goods_id));
        if($res_goodsinfo[0]['is_localsale']==1 && $res_goodsinfo[0]['goods_areaid']!=$res_address['area_id']){
            $this->to_back(90140);
        }

        $res_area = $m_area->find($res_address['area_id']);
        $res_county = $m_area->find($res_address['county_id']);

        $contact = $res_address['consignee'];
        $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        $phone = $res_address['phone'];
        $up_data = array('contact'=>$contact,'address'=>$address,'phone'=>$phone);
        $m_order->updateData(array('id'=>$order_id),$up_data);

        $order_receive_key = C('SAPP_ORDER_GIFT').$res_order['gift_oid'].':receive';
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $res_cache = $redis->get($order_receive_key);
        if(!empty($res_cache)){
            $receive_data = json_decode($res_cache,true);
            $r_data = array('openid'=>$res_order['openid'],'amount'=>$res_order['amount'],'add_time'=>$res_order['add_time'],'address'=>$address);
            $receive_data[$openid] = $r_data;
            $redis->set($order_receive_key,json_encode($receive_data),86400*7);
        }

        $res_data = array('order_id'=>$order_id,'goods_id'=>$goods_id,'message'=>'收货地址已确认，请注意查收');
        $this->to_back($res_data);
    }

}