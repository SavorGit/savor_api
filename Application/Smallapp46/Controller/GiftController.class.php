<?php
namespace Smallapp46\Controller;
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
            case 'give':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'receiveResult':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'confirmReceive':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'receive_num'=>1001,'give_num'=>1001);
                break;
            case 'getsuccess':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
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

        $is_hasget = 0;
        if($res_order['amount']==$res_order['receive_num'] && $res_order['status']==63 && !empty($res_order['address'])){
            $is_hasget = 1;
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
        $receive_type = 1;//1待领取 2已领取 3已领取1个待添加收货地址 4已领完 5已失效
        //6已转赠给好友 7已领取多个(自己未领取且未转赠好友) 8已领取多个(自己已领取或已转赠好友)
        $r_order_id = $order_id;
        $selfreceive_num = $give_num = 0;
        $receive_order_id = $give_order_id = 0;
        $share_title = '';
        $address = array();

        if(isset($receive_data[$openid])){
            $res_rorder = $m_order->getInfo(array('gift_oid'=>$order_id,'openid'=>$openid));
            if($res_rorder['amount']==$res_rorder['receive_num'] && $res_rorder['status']==63 && !empty($res_rorder['address'])){
                $is_hasget = 1;
            }
            $receive_type = 2;
            $now_oid = $res_rorder['id'];
            $res_sunorder = $m_order->getInfo(array('gift_oid'=>$now_oid));
            if($res_rorder['otype']==6 && $receive_data[$openid]['amount']<2){
                if(!empty($res_sunorder)){
                    $gift_where = array('otype'=>7);
                    $trees = ",{$res_sunorder['id']},";
                    $gift_where['gift_oidtrees'] = array('like',"%$trees%");
                    $gift_where['receive_num'] = array('gt',0);
                    $res_ordertree = $m_order->getDataList('id',$gift_where,'id desc',0,1);

                    if(!empty($res_ordertree['total']) || ($res_sunorder['status']==63 && $res_sunorder['receive_num']==$res_sunorder['amount'] && !empty($res_sunorder['address']))){
                        $receive_type = 6;
                    }else{
                        $selfreceive_num = $res_rorder['receive_num'];
                        $receive_type = 3;
                    }
                }else{
                    if(empty($receive_data[$openid]['address'])){
                        $selfreceive_num = $res_rorder['receive_num']>0?$res_rorder['receive_num']:$res_rorder['amount'];
                        $receive_type = 3;
                        $r_order_id = $res_rorder['id'];
                    }
                }
            }else{
                $selfreceive_num = $res_rorder['receive_num'];
                $receive_order_id = $res_rorder['id'];
                if(!empty($res_sunorder)){
                    $give_num = $res_sunorder['amount'];
                    $give_order_id = $res_sunorder['id'];
                }
                if($res_rorder['receive_num']==0){
                    if(!empty($res_sunorder)){
                        $gift_where = array('otype'=>7);
                        $trees = ",{$res_sunorder['id']},";
                        $gift_where['gift_oidtrees'] = array('like',"%$trees%");
                        $gift_where['receive_num'] = array('gt',0);
                        $res_ordertree = $m_order->getDataList('id',$gift_where,'id desc',0,1);
                        if(!empty($res_ordertree['total']) || ($res_sunorder['status']==63 && $res_sunorder['receive_num']==$res_sunorder['amount'] && !empty($res_sunorder['address']))){
                            $receive_type = 6;
                        }else{
                            $receive_type = 3;
                        }
                    }else{
                        $selfreceive_num = $res_rorder['amount'];
                        $receive_type = 7;
                        $r_order_id = $res_rorder['id'];
                    }
                }else{
                    $r_order_id = $res_rorder['id'];
                    $receive_type = 8;
                    if(!empty($res_rorder['address'])){
                        $address = array('contact'=>$res_rorder['contact'],'address'=>$res_rorder['address'],'phone'=>$res_rorder['phone']);
                    }
                    $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
                    $fields = 'goods.id as goods_id,goods.name as goods_name,og.price';
                    $res_ogoods = $m_ordergoods->getOrdergoodsList($fields,array('og.order_id'=>$order_id),'og.id desc');
                    $m_user = new \Common\Model\Smallapp\UserModel();
                    $res_user = $m_user->getOne('*',array('openid'=>$res_order['openid']),'id desc');
                    $share_wx = $m_order->shareWeixin($res_user['nickName'],$res_ogoods[0]['goods_name']);
                    $share_title = $share_wx['title'];
                }
            }
            if($is_hasget){
                $receive_type = 4;
            }
        }else{
            switch ($res_order['status']){
                case 17:
                    $receive_type = 4;
                    break;
                case 62:
                    $receive_type = 5;
                    break;
                case 63:
                    if($res_order['amount']==$res_order['receive_num']){
                        $receive_type = 4;
                    }
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
            'selfreceive_num'=>intval($selfreceive_num),'give_num'=>$give_num,'address'=>$address,'receive_order_id'=>$receive_order_id,'give_order_id'=>$give_order_id,
            'share_title'=>$share_title,
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
        $had_receive_num = 0;
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

                if(!empty($res_order['gift_oid']) && $res_order['otype']==6){
                    $res_order['otype']=7;
                }
                if($res_order['otype']==7){
                    if(empty($res_order['gift_oidtrees'])){
                        $res_order['gift_oidtrees']= ',';
                    }
                    $r_data['gift_oidtrees'] = $res_order['gift_oidtrees']."$order_id,";
                }
                $r_data['otype'] = $res_order['otype'];
                $r_data['person_upnum'] = 1;
                $r_data['message'] = $res_order['message'];
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
                    $had_receive_num = $receive_num;
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
            $message = "恭喜您领取到了{$user_info['nickName']}的赠品";
        }

        $res_data = array('receive_type'=>$receive_type,'order_id'=>$row_id,'receive_num'=>$had_receive_num,'message'=>$message);
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

        if(empty($res_order['gift_oidtrees'])){
            $p_oid = $order_id;
        }else{
            $order_trees = trim($res_order['gift_oidtrees'],',');
            $order_trees_arr = explode(',',$order_trees);
            $p_oid = $order_trees_arr[0];
        }
        $is_self = 0;
        if($res_order['openid']==$openid && $res_order['receive_num']){
            $is_self = 1;
        }
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);

        if($is_self==0){
            $order_getnum_key = C('SAPP_ORDER_GIFT').$p_oid.':getnum';
            $res_getnum_cache = $redis->get($order_getnum_key);
            if(!empty($res_getnum_cache)){
                $get_num = json_decode($res_getnum_cache,true);
            }else{
                $m_order = new \Common\Model\Smallapp\OrderModel();
                $res_porder = $m_order->getInfo(array('id'=>$p_oid));
                $get_num = array('all_num'=>$res_porder['amount']-$res_porder['receive_num'],'num'=>0);
            }
            $get_num['num'] = $get_num['num']+1;
            if($get_num['all_num']-$get_num['num']<0){
                $res_data = array('receive_order_id'=>$res_order['gift_oid']);
                $this->to_back($res_data);
            }
            $redis->set($order_getnum_key,json_encode($get_num),86400*7);

            $gift_where = array('otype'=>7);
            $trees = ",$order_id,";
            $gift_where['gift_oidtrees'] = array('like',"%$trees%");
            $gift_where['receive_num'] = array('gt',0);
            $res_ordertree = $m_order->getDataList('id',$gift_where,'id desc',0,1);
            if($res_ordertree['total']){
                $res_data = array('receive_order_id'=>$res_order['gift_oid']);
                $this->to_back($res_data);
            }
        }

        $res_area = $m_area->find($res_address['area_id']);
        $res_county = $m_area->find($res_address['county_id']);

        $contact = $res_address['consignee'];
        $address = $res_area['region_name'].$res_county['region_name'].$res_address['address'];
        $phone = $res_address['phone'];
        $up_data = array('contact'=>$contact,'address'=>$address,'phone'=>$phone,'status'=>63);
        if($is_self==0 && $res_order['receive_num']==0){
            $up_data['receive_num']=$res_order['amount'];
        }
        $m_order->updateData(array('id'=>$order_id),$up_data);

        if($is_self==0){
            $gift_where = array('otype'=>7);
            $trees = ",$order_id,";
            $gift_where['gift_oidtrees'] = array('like',"%$trees%");
            $gift_where['receive_num'] = array('eq',0);
            $m_order->updateData($gift_where,array('status'=>17));
        }

        $order_receive_key = C('SAPP_ORDER_GIFT').$res_order['gift_oid'].':receive';
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

    public function give(){
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
        if($res_order['otype']==6){
            if($res_order['status']!=63){
                $this->to_back(90149);
            }
            if($res_order['receive_num']){
                $this->to_back(90150);
            }
        }

        if($res_order['otype']==7){
            if(!in_array($res_order['status'],array(63,71))){
                $this->to_back(90151);
            }
            if($res_order['receive_num']){
                $this->to_back(90147);
            }
        }

        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $fields = 'goods.id as goods_id,goods.name as goods_name,og.price';
        $res_ogoods = $m_ordergoods->getOrdergoodsList($fields,array('og.order_id'=>$order_id),'og.id desc');

        $res_gorder = $m_order->getInfo(array('gift_oid'=>$order_id));
        if(!empty($res_gorder)){
            $res_data = array('order_id'=>$res_gorder['id'],'openid'=>$res_gorder['openid'],'amount'=>$res_gorder['amount']);
        }else{
            $gift_oidtrees = ",$order_id,";
            if(!empty($res_order['gift_oidtrees'])){
                $gift_oidtrees = $res_order['gift_oidtrees']."$order_id,";
            }
            $data = array('gift_oid'=>$order_id,'gift_oidtrees'=>$gift_oidtrees,'openid'=>$res_order['openid'],'merchant_id'=>$res_order['merchant_id'],
                'amount'=>$res_order['amount'],'person_upnum'=>$res_order['person_upnum'],'total_fee'=>$res_order['total_fee'],
                'status'=>71,'otype'=>7,'message'=>$res_order['message']
            );
            $now_order_id = $m_order->add($data);
            $order_goods = array('order_id'=>$now_order_id,'goods_id'=>$res_ogoods[0]['goods_id'],
                'price'=>$res_ogoods[0]['price'],'amount'=>$res_order['amount']);
            $m_ordergoods->addData($order_goods);

            $res_data = array('order_id'=>$now_order_id,'openid'=>$res_order['openid'],'amount'=>$res_order['amount']);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('*',array('openid'=>$res_order['openid']),'id desc');

        $share_wx = $m_order->shareWeixin($res_user['nickName'],$res_ogoods[0]['goods_name']);
        $res_data['share_title'] = $share_wx['title'];

        $this->to_back($res_data);
    }

    public function confirmReceive(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);
        $receive_num = intval($this->params['receive_num']);
        $give_num = intval($this->params['give_num']);

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
        $amount = $res_order['amount'];
        if($receive_num+$give_num!=$amount || $amount==1){
            $last_amount = $amount - ($receive_num+$give_num);
            $code = 90152;
            $errorinfo = C('errorinfo');
            $resp_msg = $errorinfo[$code];
            $last_amount = $last_amount>0?$last_amount:$amount;
            $msg = sprintf(L("$resp_msg"),$last_amount);
            $data = array('code'=>$code,'msg'=>$msg);
            $this->to_back($data);
        }
        $receive_order_id = 0;
        if($receive_num && $res_order['receive_num']==0){
            $receive_order_id = $order_id;
            $up_data = array('receive_num'=>$receive_num);
            $m_order->updateData(array('id'=>$order_id),$up_data);
        }
        $give_order_id = 0;
        if($give_num){
            $res_gorder = $m_order->getInfo(array('gift_oid'=>$order_id));
            if(empty($res_gorder)){
                $gift_oidtrees = ",$order_id,";
                if(!empty($res_order['gift_oidtrees'])){
                    $gift_oidtrees = $res_order['gift_oidtrees']."$order_id,";
                }
                $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
                $res_ogoods = $m_ordergoods->getDataList('*',array('order_id'=>$order_id),'id asc');

                $total_fee = sprintf("%.2f",$res_ogoods[0]['price']*$give_num);
                $data = array('gift_oid'=>$order_id,'gift_oidtrees'=>$gift_oidtrees,'openid'=>$res_order['openid'],'merchant_id'=>$res_order['merchant_id'],
                    'amount'=>$give_num,'person_upnum'=>1,'total_fee'=>$total_fee,
                    'status'=>71,'otype'=>7,'message'=>$res_order['message']
                );
                $give_order_id = $m_order->add($data);

                $order_goods = array('order_id'=>$give_order_id,'goods_id'=>$res_ogoods[0]['goods_id'],
                    'price'=>$res_ogoods[0]['price'],'amount'=>$give_num);
                $m_ordergoods->addData($order_goods);
            }else{
                $give_order_id = $res_order['id'];
                $give_num = $res_order['amount'];
            }
        }

        $res_data = array('openid'=>$openid,'receive_order_id'=>$receive_order_id,'receive_num'=>$receive_num,
            'give_order_id'=>$give_order_id,'give_num'=>$give_num);
        $this->to_back($res_data);
    }

    public function getsuccess(){
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
        $selfreceive_num = $res_order['receive_num'];
        $receive_order_id = $res_order['id'];
        $address = array();
        if(!empty($res_order['address'])){
            $address = array('contact'=>$res_order['contact'],'address'=>$res_order['address'],'phone'=>$res_order['phone']);
        }

        $give_order_id = 0;
        $give_num = 0;
        $share_title = '';
        $res_user = $m_user->getOne('*',array('openid'=>$res_order['openid']),'id desc');
        $give_uname = $res_user['nickName'];
        $res_rorder = $m_order->getInfo(array('gift_oid'=>$order_id,'openid'=>$openid));
        if(!empty($res_rorder)){
            $give_num = $res_rorder['amount'];
            $give_order_id = $res_rorder['id'];
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $fields = 'goods.id as goods_id,goods.name as goods_name,og.price';
            $res_ogoods = $m_ordergoods->getOrdergoodsList($fields,array('og.order_id'=>$order_id),'og.id desc');

            $share_wx = $m_order->shareWeixin($res_user['nickName'],$res_ogoods[0]['goods_name']);
            $share_title = $share_wx['title'];
        }
        if(!empty($res_order['gift_oid'])){
            $res_porder = $m_order->getInfo(array('id'=>$res_order['gift_oid']));
            $res_user = $m_user->getOne('*',array('openid'=>$res_porder['openid']),'id desc');
            $give_uname = $res_user['nickName'];
        }

        $order_data = array('order_id'=>$order_id,'merchant_id'=>$res_order['merchant_id'],'amount'=>$res_order['amount'],'message'=>$res_order['message'],
            'openid'=>$res_order['openid'],'nickName'=>$give_uname,'selfreceive_num'=>$selfreceive_num,'give_num'=>$give_num,'address'=>$address,
            'receive_order_id'=>$receive_order_id,'give_order_id'=>$give_order_id,'share_title'=>$share_title,
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
        $this->to_back($order_data);
    }



}