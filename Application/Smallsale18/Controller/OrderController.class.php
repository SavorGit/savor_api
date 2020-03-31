<?php
namespace Smallsale18\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'dishOrderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'dishorderProcess':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'dishOrderdetail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'orderProcess':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'orderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'orderReceive':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001,'action'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
        }
        parent::_init_();
    }


    public function dishOrderlist(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $type = isset($this->params['type'])?intval($this->params['type']):0;//类型0 全部 1普通订单 2分销订单 3代理人订单
        if(empty($pagesize)){
            $pagesize =10;
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        if($type && $type==3){
            $where = array('openid'=>$openid,'type'=>2);
        }else{
            $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
            $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
            $merchant_id = $res_staff[0]['merchant_id'];
            $where = array('merchant_id'=>$merchant_id);
            if($type){
                $where['type'] = $type;
            }
        }

        if($status){
            $where['status'] = $status;
        }
        $all_nums = $page * $pagesize;
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $fields = 'id as order_id,merchant_id,openid,price,amount,total_fee,status,contact,phone,address,delivery_time,remark,add_time,finish_time';
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

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $res_order = $m_dishorder->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['merchant_id']!=$merchant_id){
            $this->to_back(93036);
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
            $ginfo = array('id'=>$gv['goods_id'],'name'=>$gv['goods_name'],'price'=>$gv['price'],'amount'=>$gv['amount'],
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

    public function dishorderProcess(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $res_order = $m_dishorder->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['merchant_id']!=$merchant_id){
            $this->to_back(93036);
        }
        $m_dishorder->updateData(array('id'=>$order_id),array('status'=>2,'finish_time'=>date('Y-m-d H:i:s')));
        $this->to_back(array());
    }

    public function orderProcess(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['merchant_id']!=$merchant_id){
            $this->to_back(93036);
        }
        $m_order->updateData(array('id'=>$order_id),array('status'=>2,'finish_time'=>date('Y-m-d H:i:s')));
        $this->to_back(array());
    }


    public function orderlist(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);//1待处理 2已完成
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $type = isset($this->params['type'])?intval($this->params['type']):0;//类型0 全部 1普通订单 2分销订单 3代理人订单
        if(empty($pagesize)){
            $pagesize =10;
        }
        $map_types = C('MAP_ORDER_STATUS');//3普通订单 4分销订单
        $m_staff = new \Common\Model\Integral\StaffModel();
        if($type && $type==3){
            $where = array('openid'=>$openid,'otype'=>$map_types[$type]);
        }else{
            $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
            $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
            $merchant_id = $res_staff[0]['merchant_id'];
            $where = array('merchant_id'=>$merchant_id);
            if($type){
                $where['otype'] = $map_types[$type];
            }
        }
        switch ($status){
            case 1:
                $where['status'] = array('in',array(1,13,14,15,16));
                break;
            case 2:
                $where['status'] = array('in',array(2,17,18,19));
                break;
            default:
                $where['status'] = array('not in',array(10,11,12));
        }
        $all_nums = $page * $pagesize;
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $fields = 'id as order_id,merchant_id,openid,price,amount,total_fee,status,otype,contact,phone,address,delivery_type,delivery_time,remark,add_time,finish_time';
        $res_order = $m_order->getDataList($fields,$where,'add_time desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $m_media = new \Common\Model\MediaModel();
            $datalist = $res_order['list'];
            $oss_host = "http://".C('OSS_HOST').'/';
            $all_status = C('ORDER_STATUS');
            $order_types = array('3'=>1,'4'=>2);
            foreach($datalist as $k=>$v){
                if(isset($order_types[$v['otype']])){
                    $otype = $order_types[$v['otype']];
                }else{
                    $otype = 0;
                }
                $datalist[$k]['type']=$otype;
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
                $fields = 'm.id,hotel.name,ext.hotel_cover_media_id,m.delivery_platform';
                $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
                $merchant = array('name'=>$res_merchant[0]['name'],'merchant_id'=>$v['merchant_id']);
                $merchant['img'] = '';
                if(!empty($res_merchant[0]['hotel_cover_media_id'])){
                    $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
                    $merchant['img'] = $res_media['oss_addr'];
                }

                $datalist[$k]['delivery_platform'] = $res_merchant[0]['delivery_platform'];
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

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order) || ($res_order['otype']==3 && $res_order['merchant_id']!=$merchant_id)){
            $this->to_back(93036);
        }
        $order_types = array('3'=>1,'4'=>2);
        if(isset($order_types[$res_order['otype']])){
            $o_type = $order_types[$res_order['otype']];
        }else{
            $o_type = 0;
        }
        if($res_order['selfpick_time']=='0000-00-00 00:00:00'){
            $res_order['selfpick_time'] = '';
        }
        $order_data = array('order_id'=>$order_id,'merchant_id'=>$res_order['merchant_id'],'amount'=>$res_order['amount'],
            'total_fee'=>$res_order['total_fee'],'status'=>$res_order['status'],'status_str'=>'',
            'contact'=>$res_order['contact'],'phone'=>$res_order['phone'],'address'=>$res_order['address'],'tableware'=>$res_order['tableware'],
            'remark'=>$res_order['remark'],'delivery_type'=>$res_order['delivery_type'],'delivery_time'=>$res_order['delivery_time'],'delivery_fee'=>$res_order['delivery_fee'],
            'selfpick_time'=>$res_order['selfpick_time'],'finish_time'=>$res_order['finish_time'],'type'=>$o_type
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
        $order_data['transporter'] = array();
        $order_data['user_location'] = array();
        $order_data['markers'] = array();
        $order_data['polyline'] = array();
        $order_data['distance'] = '';

        if($res_order['delivery_type']==1 && in_array($res_order['status'],array(15,16))){
            $config = C('DADA');
            $dada = new \Common\Lib\Dada($config);
            $res = $dada->queryOrder($order_id);
            if($res['code']==0 && !empty($res['result'])){
                $dd_res = $res['result'];
                $ddstatus_code = $dd_res['statusCode'];
                $status_map = array('1'=>14,'2'=>15,'3'=>16,'4'=>17);//待接单＝1 待取货＝2 配送中＝3 已完成＝4 已取消＝5 已过期＝7 指派单=8 妥投异常之物品返回中=9 妥投异常之物品返回完成=10 系统故障订单发布失败=1000
                if(isset($status_map[$ddstatus_code])){
                    if(in_array($ddstatus_code,array(2,3))){
                        $order_data['transporter'] = array('name'=>$dd_res['transporterName'],'phone'=>$dd_res['transporterPhone']);
                        $takeaway_rider_img = '/images/icon/takeaway_rider.png';
                        $takeaway_user_img = '/images/icon/takeaway_user.png';
                        $takeaway_hotel_img = '/images/icon/takeaway_hotel.png';
                        if($ddstatus_code==2){
                            $shop_no = $res_merchant[0]['hotel_id'];
//                            $shop_no = $config['shop_no'];//上线需删除
                            $res_shop = $dada->shopDetail($shop_no);
                            if($res_shop['code']==0 && !empty($res_shop['result'])){
                                $order_data['user_location'] = array('lng'=>$res_shop['result']['lng'],'lat'=>$res_shop['result']['lat']);
                                $user_img = $takeaway_hotel_img;
                            }else{
                                $user_img = '';
                            }
                        }else{
                            $lnglat_arr = explode(',',$res_order['lnglat']);
                            $order_data['user_location'] = array('lng'=>$lnglat_arr[0],'lat'=>$lnglat_arr[1]);
                            $user_img = $takeaway_user_img;
                        }
                        $order_data['markers'] = array(
                            array(
                                'iconPath'=>$takeaway_rider_img,
                                'id'=>0,
                                'latitude'=>$dd_res['transporterLat'],
                                'longitude'=>$dd_res['transporterLng'],
                                'width'=>24,
                                'height'=>24,
                            ),
                            array(
                                'iconPath'=>$user_img,
                                'id'=>1,
                                'latitude'=>$order_data['user_location']['lat'],
                                'longitude'=>$order_data['user_location']['lng'],
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
                        $order_data['distance']=$distance;

                        //上线需删除
                        /*
                        $order_data['transporter'] = array('name'=>'热达达','phone'=>'13112345678');
                        $order_data['markers'] = array(
                            array(
                                'iconPath'=>'/images/imgs/default-user.png',
                                'id'=>0,
                                'latitude'=>'39.908287',
                                'longitude'=>'116.475783',
                                'width'=>50,
                                'height'=>50,
                            ),
                            array(
                                'iconPath'=>'/images/imgs/default-user.png',
                                'id'=>1,
                                'latitude'=>$order_data['user_location']['lat'],
                                'longitude'=>$order_data['user_location']['lng'],
                                'width'=>50,
                                'height'=>50,
                            ),
                        );
                        $order_data['distance']='1.5km';
                        */
                        //end

                        $order_data['polyline'] = array(
                            array(
                                'points'=>array(
                                    array('longitude'=>$order_data['markers'][0]['longitude'],'latitude'=>$order_data['markers'][0]['latitude']),
                                    array('longitude'=>$order_data['user_location']['lng'],'latitude'=>$order_data['user_location']['lat']),
                                ),
                                'color'=>'#FF0000DD',
                                'width'=>2,
                                'dottedLine'=>true
                            )
                        );
                    }
                }
            }

        }
        $this->to_back($order_data);
    }

    public function orderReceive(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);
        $action = intval($this->params['action']);//1接单 2不接单

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $fields = 'o.id,o.total_fee,o.pay_fee,o.delivery_fee,o.is_atonce,o.contact,o.phone,o.address,o.lnglat,o.area_id,
        o.add_time,o.pay_type,o.status,o.delivery_type,m.id as merchant_id,m.delivery_platform,hotel.id as hotel_id,ext.meal_time';
        $where = array('o.id'=>$order_id);
        $res_order = $m_order->getOrderInfo($fields,$where);
        if(empty($res_order) || $res_order[0]['merchant_id']!=$merchant_id){
            $this->to_back(93036);
        }
        if($res_order[0]['status']!=13){
            $this->to_back(90138);
        }
        switch ($action){
            case 1://接单
                if($res_order[0]['delivery_type']==1 && $res_order[0]['delivery_platform']==1){
                    $config = C('DADA');
                    $hotel_id = $res_order[0]['hotel_id'];
//                    $hotel_id = $config['shop_no'];//上线后去除

                    $m_area = new \Common\Model\AreaModel();
                    $res_area = $m_area->find($res_order[0]['area_id']);
                    $area_no = $res_area['area_no'];
                    $money = $res_order[0]['total_fee'] - $res_order[0]['delivery_fee'];
                    $name = $res_order[0]['contact'];
                    $address = $res_order[0]['address'];
                    $phone = $res_order[0]['phone'];
                    $lnglat = explode(',',$res_order[0]['lnglat']);
                    if($res_order[0]['is_atonce']){
                        $delay_publish_time = '';
                    }elseif($res_order[0]['meal_time']){
                        $add_time = date('Y-m-d H:i:00',strtotime($res_order[0]['add_time']));
                        $delay_publish_time = strtotime($add_time)+$res_order[0]['meal_time']*60-600;
                        $now_time = time();
                        if($delay_publish_time<$now_time){
                            $delay_publish_time = '';
                        }
                    }else{
                        $delay_publish_time = '';
                    }
                    $host_name = 'https://'.$_SERVER['HTTP_HOST'];
                    $callback = $host_name."/h5/dada/orderNotify";
                    $dada = new \Common\Lib\Dada($config);
                    $res = $dada->addOrder($hotel_id,$order_id,$area_no,$money,$name,$address,$phone,$lnglat[1],$lnglat[0],$callback,$delay_publish_time);

                    if($res['code']==0 && !empty($res['result'])){
                        $m_order->updateData(array('id'=>$order_id),array('status'=>14));
                    }else{
                        $this->to_back(90139);
                    }
                }else{
                    $m_order->updateData(array('id'=>$order_id),array('status'=>17,'finish_time'=>date('Y-m-d H:i:s')));
                    $res = array();
                }
                $resp_data = $res;
                break;
            case 2://不接单
                $message = '取消订单成功';
                if($res_order[0]['pay_type']==10){
                    $m_orderserial = new \Common\Model\Smallapp\OrderserialModel();
                    $res_orderserial = $m_orderserial->getInfo(array('trade_no'=>$order_id));
                    if(!empty($res_orderserial)){
                        $m_baseinc = new \Payment\Model\BaseIncModel();
                        $payconfig = $m_baseinc->getPayConfig(2);
                        $m_ordermap = new \Common\Model\Smallapp\OrdermapModel();
                        $res_ordermap = $m_ordermap->getDataList('id',array('order_id'=>$order_id),'id desc',0,1);
                        $trade_no = $res_ordermap['list'][0]['id'];

                        $trade_info = array('trade_no'=>$trade_no,'batch_no'=>$res_orderserial['serial_order'],'pay_fee'=>$res_order[0]['pay_fee'],'refund_money'=>$res_order[0]['pay_fee']);
                        $m_wxpay = new \Payment\Model\WxpayModel();
                        $res = $m_wxpay->wxrefund($trade_info,$payconfig);
                        if($res["return_code"]=="SUCCESS" && $res["result_code"]=="SUCCESS" && !isset($res['err_code'])){
                            $m_order->updateData(array('id'=>$order_id),array('status'=>18,'finish_time'=>date('Y-m-d H:i:s')));
                            $message = '取消订单成功,且已经退款.款项在1到7个工作日内,退还到用户的支付账户';
                        }else{
                            $message = '取消订单失败';
                        }
                    }else{
                        $message = '取消订单失败';
                    }
                }else{
                    $m_order->updateData(array('id'=>$order_id),array('status'=>18,'finish_time'=>date('Y-m-d H:i:s')));
                }
                $resp_data = array('message'=>$message);
                break;
            default:
                $resp_data = array();
        }
        $this->to_back($resp_data);
    }



}