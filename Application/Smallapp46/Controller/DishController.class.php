<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class DishController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'goodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('merchant_id'=>1001,'page'=>1001,'type'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'box_mac'=>1002,'task_user_id'=>1002,'expire_time'=>1002,'openid'=>1002);
                break;
            case 'invitationgoods':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'invitation_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function goodslist(){
        $merchant_id = intval($this->params['merchant_id']);
        $page = intval($this->params['page']);
        $type = isset($this->params['type'])?intval($this->params['type']):21;//类型21商家外卖商品 22商家售全国商品

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('id'=>$merchant_id));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }

        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$merchant_id,'status'=>1,'type'=>$type);
        $where['gtype'] = array('in',array(1,2));
        $orderby = 'is_top desc,status asc,id desc';
        $res_goods = $m_goods->getDataList('*',$where,$orderby,0,$all_nums);

        $datalist = array();
        if($res_goods['total']){
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            foreach ($res_goods['list'] as $v){
                $price = $v['price'];
                $line_price = $v['line_price'];
                $stock_num = $v['amount'];
                $goods_id = $v['id'];

                $gtype = $v['gtype'];
                $attrs = array();
                $model_img = '';
                if($gtype==2){
                    $res_attrs = $m_goods->getGoodsAttr($v['id']);
                    $price = $res_attrs['default']['price'];
                    $line_price = $res_attrs['default']['line_price'];
                    $stock_num = $res_attrs['default']['amount'];
                    $goods_id = $res_attrs['default']['id'];
                    $attrs = $res_attrs['attrs'];
                    $model_img = $res_attrs['default']['model_img'];
                }

                $img_url = '';
                if(!empty($v['cover_imgs'])){
                    $oss_host = "https://".C('OSS_HOST').'/';
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    }
                }
                $dinfo = array('id'=>$goods_id,'name'=>$v['name'],'price'=>$price,'line_price'=>$line_price,'type'=>$v['type'],'gtype'=>$gtype,
                    'model_img'=>$model_img,'stock_num'=>$stock_num,'img_url'=>$img_url,'is_localsale'=>intval($v['is_localsale']),
                    'status'=>intval($v['status']),'attrs'=>$attrs);
                $dinfo['qrcode_url'] = $host_name."/smallsale18/qrcode/dishQrcode?data_id={$v['id']}&type=25";
                $datalist[] = $dinfo;
            }
        }
        $this->to_back($datalist);
    }

    public function detail(){
        $goods_id = intval($this->params['goods_id']);
        $box_mac = $this->params['box_mac'];
        $task_user_id = $this->params['task_user_id'];
        $expire_time = $this->params['expire_time'];
        $openid = $this->params['openid'];

        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }
        if($res_goods['status']!=1){
            $this->to_back(93037);
        }
        $price = $res_goods['price'];
        $line_price = $res_goods['line_price'];
        $stock_num = $res_goods['amount'];

        $specification_goods = array();
        $attrs = array();
        $model_img = '';
        if($res_goods['type']==22 && in_array($res_goods['gtype'],array(2,3))){
            if($res_goods['gtype']==2){
                $res_data = $m_goods->getDataList('*',array('parent_id'=>$goods_id,'status'=>1),'id asc',0,1);
                if(!$res_data['total']){
                    $this->to_back(93037);
                }
                $res_goods['parent_id'] = $goods_id;
                $goods_id = $res_data['list'][0]['id'];
            }
            $res_attrs = $m_goods->getGoodsAttr($res_goods['parent_id'],$goods_id);
            $all_goods = $res_attrs['all_goods'];
            foreach ($all_goods as $v){
                $name = str_replace('_',',',$v['attr_name']);
                $specification_goods[]=array('goods_id'=>$v['id'],'name'=>$name,'attr_ids'=>$v['attr_ids']);
            }

            $price = $res_attrs['default']['price'];
            $line_price = $res_attrs['default']['line_price'];
            $stock_num = $res_attrs['default']['amount'];
            $model_img = $res_attrs['default']['model_img'];
            $attrs = $res_attrs['attrs'];
            $res_goods = $m_goods->getInfo(array('id'=>$res_goods['parent_id']));
        }

        $data = array('goods_id'=>$goods_id,'name'=>$res_goods['name'],'price'=>$price,'line_price'=>$line_price,
            'is_localsale'=>$res_goods['is_localsale'],'stock_num'=>$stock_num,'type'=>$res_goods['type'],'notice'=>$res_goods['notice'],
            'gtype'=>$res_goods['gtype'],'attrs'=>$attrs,'model_img'=>$model_img);

        if($res_goods['type']==42){
            if(!empty($task_user_id) && !empty($expire_time)){
                $m_usertask = new \Common\Model\Integral\TaskuserModel();
                $fields = "a.openid,task.id task_id,task.name task_name,task.goods_id,task.integral,
                task.task_type,task.status,task.flag,task.end_time as task_expire_time";
                $where = array('a.id'=>$task_user_id);
                $res_usertask = $m_usertask->getUserTaskList($fields,$where,'a.id desc');
                if(empty($res_usertask)){
                    $this->to_back(93037);
                }
                $now_time = time();
                $group_buy_time = $expire_time-$now_time>0?$expire_time-$now_time:0;
                $where = array('a.openid'=>$res_usertask[0]['openid'],'a.status'=>1,'merchant.status'=>1);
                $field_staff = 'a.openid,user.mobile,user.nickName';
                $m_staff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
                if($res_usertask[0]['status']==0){
                    $group_buy_time = 0;
                }
                $group_buy_tips = '优惠已结束，请联系销售经理（'.$res_staff[0]['nickName'].' 电话：'.$res_staff[0]['mobile'].'）';
                $data['group_buy_time'] = $group_buy_time;
                $data['group_buy_tips'] = $group_buy_tips;
            }
        }

        $oss_host = "https://".C('OSS_HOST').'/';
        $cover_imgs = $detail_imgs =array();
        if(!empty($res_goods['cover_imgs'])){
            $cover_imgs_info = explode(',',$res_goods['cover_imgs']);
            if(!empty($cover_imgs_info)){
                foreach ($cover_imgs_info as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/resize,m_mfit,h_400,w_750";
                        $cover_imgs[] = $img_url;
                    }
                }
            }
        }
        if(!empty($res_goods['detail_imgs'])){
            $detail_imgs_info = explode(',',$res_goods['detail_imgs']);
            if(!empty($detail_imgs_info)){
                foreach ($detail_imgs_info as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/quality,Q_60";
                        $detail_imgs[] = $img_url;
                    }
                }
            }
        }
        $data['cover_imgs'] = $cover_imgs;
        $data['detail_imgs'] = $detail_imgs;
        $data['intro'] = $res_goods['intro'];
        $data['video_img'] = '';
        $data['video_url'] = '';
        $data['localsale_str'] = '';
        $data['specification_goods'] = $specification_goods;

        $merchant = $gift = array();
        $merchant['merchant_id'] = $res_goods['merchant_id'];
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $fields = 'hotel.name,hotel.mobile,hotel.tel,ext.hotel_cover_media_id,hotel.area_id,area.region_name,m.mtype';
        $res_merchantinfo = $m_merchant->getMerchantInfo($fields,array('m.id'=>$res_goods['merchant_id']));
        if($res_goods['type']==22){
            if($res_goods['is_localsale']){
                $data['localsale_str'] = '仅售'.$res_merchantinfo[0]['region_name'];
            }
            if(!empty($res_goods['video_intromedia_id'])){
                $m_media = new \Common\Model\MediaModel();
                $media_info = $m_media->getMediaInfoById($res_goods['video_intromedia_id']);
                $data['video_img'] = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450,m_fast';
                $data['video_url'] = $media_info['oss_addr'];
            }
            $m_goodsactivity = new \Common\Model\Smallapp\GoodsactivityModel();
            $res_activity = $m_goodsactivity->getInfo(array('goods_id'=>$goods_id));
            if(!empty($res_activity)){
                $res_ginfo = $m_goods->getInfo(array('id'=>$res_activity['gift_goods_id']));
                $gift = array('id'=>$res_ginfo['id'],'name'=>$res_ginfo['name']);
            }
        }
        if(empty($gift)){
            $gift = new \stdClass();
        }
        $data['gift'] = $gift;

        $merchant['name'] = $res_merchantinfo[0]['name'];
        $merchant['mobile'] = $res_merchantinfo[0]['mobile'];
        $merchant['tel'] = $res_merchantinfo[0]['tel'];
        $merchant['area_id'] = $res_merchantinfo[0]['area_id'];
        $merchant['mtype'] = $res_merchantinfo[0]['mtype'];
        $merchant['img'] = '';
        if(!empty($res_merchantinfo[0]['hotel_cover_media_id'])){
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_merchantinfo[0]['hotel_cover_media_id'],'https');
            $merchant['img'] = $res_media['oss_addr'];
        }
        $where = array('merchant_id'=>$merchant['merchant_id'],'status'=>1);
        $merchant['num'] = $m_goods->countNum($where);
        $data['merchant'] = $merchant;
        $data['is_tvdemand'] = 0;
        if($res_goods['tv_media_id']){
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            $m_media = new \Common\Model\MediaModel();
            $media_info = $m_media->getMediaInfoById($res_goods['tv_media_id']);
            $oss_path = $media_info['oss_path'];
            $oss_path_info = pathinfo($oss_path);

            $data['is_tvdemand'] = 1;
            $data['duration'] = $media_info['duration'];
            $data['tx_url'] = $media_info['oss_addr'];
            $data['filename'] = $oss_path_info['basename'];
            $data['forscreen_url'] = $oss_path;
            $data['resource_size'] = $media_info['oss_filesize'];
            $data['qrcode_url'] = $host_name."/smallsale21/qrcode/dishQrcode?data_id={$res_goods['id']}&type=32";
        }
        $data['store_buy_btn'] = '本店有售，请联系服务员';
        if($res_goods['type']==44){
            $coupon_id = $discount_price = 0;
            $coupon_info = '';
            if($res_goods['is_usecoupon'] && !empty($openid)){
                $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
                $where = array('a.openid'=>$openid,'a.ustatus'=>1);
                $where['a.min_price'] = array('elt',$res_goods['price']);
                $nowtime = date('Y-m-d H:i:s');
                $where['coupon.status'] = 1;
                $where['coupon.start_time'] = array('elt',$nowtime);
                $where['coupon.end_time'] = array('egt',$nowtime);
                $fields = 'a.coupon_id,a.money,a.min_price';
                $res_coupon_user = $m_coupon_user->getUsercouponDatas($fields,$where,'a.id desc','0,1');
                if(!empty($res_coupon_user)){
                    $coupon_id = $res_coupon_user[0]['coupon_id'];
                    $discount_price = intval($res_goods['price'] - $res_coupon_user[0]['money']);
                    $coupon_info = "满{$res_coupon_user[0]['min_price']}-{$res_coupon_user[0]['money']}";
                }
            }

            $data['coupon_id'] = $coupon_id;
            $data['coupon_info'] = $coupon_info;
            $data['discount_price'] = $discount_price;
        }
        $message = '';
        $sellwine_activity_id = 0;
        if($res_goods['type']==45){
            $distribution_config = json_decode($res_goods['distribution_config'],true);
            $prices = array();
            $now_price = intval($res_goods['price']);
            $unit = '箱';
            if($goods_id==11757){
                $unit = '瓶';
            }
            $price_list = array("原价：￥{$now_price}/$unit");
            foreach ($distribution_config as $ck=>$cv){
                $prices[]=$cv['price'];
                if($ck<2){
                    $price_list[]="{$cv['min']}-{$cv['max']}{$unit}到手价：￥{$cv['price']}/$unit";
                }
            }
            $min_price = min($prices);
            $data['min_price'] = $min_price;
            if($goods_id==11757){
                $price_list = array();
            }
            $data['price_list'] = $price_list;
        }else{
            if($res_goods['finance_goods_id']>0){
                $fields = "hotel.id as hotel_id,hotel.area_id";
                if(!empty($box_mac)){
                    $m_box = new \Common\Model\BoxModel();
                    $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
                    $box_info = $m_box->getBoxByCondition($fields,$where);
                    $hotel_id = $box_info[0]['hotel_id'];
                    $m_hotel = new \Common\Model\HotelModel();
                    $res_hotel = $m_hotel->getOneById('area_id',$hotel_id);
                    $m_goods_price_hotel = new \Common\Model\Smallapp\GoodsPriceHotelModel();
                    $res_price = $m_goods_price_hotel->getGoodsPrice($goods_id,$res_hotel['area_id'],$hotel_id);
                    if(!empty($res_price['price'])){
                        $data['price'] = $res_price['price'];
                        $data['line_price'] = $res_price['line_price'];
                    }
//                    $m_sellwine_activity_hotel = new \Common\Model\Smallapp\SellwineActivityHotelModel();
//                    $sellwine_activity = $m_sellwine_activity_hotel->getSellwineActivity($hotel_id,$openid,2,$res_goods['finance_goods_id']);
//                    if(!empty($sellwine_activity)){
//                        $message = $sellwine_activity['message'];
//                        $sellwine_activity_id = $sellwine_activity['activity_id'];
//                    }

                }
            }
        }
        $data['sellwine_activity_id'] = $sellwine_activity_id;
        $data['message'] = $message;

        $this->to_back($data);
    }

    public function getPlatform(){
        $merchant_id = intval($this->params['merchant_id']);

        $m_dishplatform = new \Common\Model\Smallapp\DishplatformModel();
        $where = array('merchant_id'=>$merchant_id);
        $res_platform = $m_dishplatform->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_platform)){
            $oss_host = get_oss_host();
            foreach ($res_platform as $v){
                $img_url = $oss_host.'/'.$v['img_path'];
                $info = array('id'=>$v['id'],'name'=>$v['name'],
                    'img_path'=>$v['img_path'],'img_url'=>$img_url);
                $datalist[]=$info;
            }
        }
        $this->to_back($datalist);
    }

    public function invitationgoods(){
        $hotel_id = intval($this->params['hotel_id']);
        $invitation_id = intval($this->params['invitation_id']);

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }
        $res_goods = array('total'=>0);
        if($invitation_id>0){
            $m_invitation = new \Common\Model\Smallapp\InvitationModel();
            $res_data = $m_invitation->getInfo(array('id'=>$invitation_id));
            if(!empty($res_data['images'])){
                $all_images = explode(',',$res_data['images']);
                $list = array();
                foreach ($all_images as $v){
                    if(!empty($v)){
                        $img_url = $v.'?x-oss-process=image/resize,m_mfit,h_200,w_300';
                        $list[]=array('id'=>0,'name'=>'','intro'=>'','img_url'=>$img_url);
                    }
                }
                $res_goods['total'] = count($list);
                $res_goods['list'] = $list;
            }
        }
        if($res_goods['total']==0){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $where = array('merchant_id'=>$res_merchant['id'],'status'=>1,'type'=>24);
            $orderby = 'sort desc,id desc';
            $res_goods = $m_goods->getDataList('*',$where,$orderby,0,5);
        }
        $datalist = array();
        if($res_goods['total']){
            $m_media = new \Common\Model\MediaModel();
            $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
            $hotel_goods = $m_hotelgoods->getStockGoodsList($hotel_id,0,100);
            $oss_host = get_oss_host();
            foreach ($res_goods['list'] as $k=>$v){
                $img_url = '';
                if(!empty($v['img_url'])){
                    $img_url = $oss_host.$v['img_url'];
                }elseif(!empty($v['cover_imgs'])){
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_url = $oss_host.$cover_imgs_info[0];
                    }
                }
                $stock_goods_id = 0;
                $stock_goods_img = '';
                if(isset($hotel_goods[$k])){
                    $stock_goods_id = $hotel_goods[$k]['id'];
                    if($hotel_goods[$k]['advright_media_id']>0){
                        $res_media = $m_media->getMediaInfoById($hotel_goods[$k]['advright_media_id']);
                        $stock_goods_img = $res_media['oss_addr'];
                    }
                }
                $dinfo = array('id'=>$v['id'],'name'=>$v['name'],'img_url'=>$img_url,'intro'=>$v['intro'],
                    'stock_goods_id'=>$stock_goods_id,'stock_goods_img'=>$stock_goods_img);
                $datalist[] = $dinfo;
            }
        }

        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

}