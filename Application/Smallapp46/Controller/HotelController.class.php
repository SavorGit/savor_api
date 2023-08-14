<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class HotelController extends CommonController{
    var $avg_exp_arr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'dataList':
                $this->is_verify =1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,'county_id'=>1002,
                    'latitude'=>1002,'longitude'=>1002,'food_style_id'=>1002,'avg_exp_id'=>1002
                );
                break;
            case 'recList':
                $this->is_verify =1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,'count_id'=>1002,
                    'food_style_id'=>1002,'avg_exp_id'=>1002,'latitude'=>1002,'longitude'=>1002
                );
                break;
            case 'getExplist':
                $this->is_verify = 0;
                break;
            case 'hotdrinksHotels':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1002,'room_id'=>1002,'hotel_id'=>1002,'page'=>1001,'pagesize'=>1002);
                break;
            case 'scancode':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'content'=>1001);
                break;
        }
        $this->avg_exp_arr = array(
            'agv_name'=>array('人均价格','100以下','100-200','200以上'),
            'agv_lisg'=>array(
                array('id'=>0,'name'=>'人均价格'),
                array('id'=>1,'name'=>'100以下'),
                array('id'=>2,'name'=>'100-200'),
                array('id'=>3,'name'=>'200以上')
            )
        );
        parent::_init_();
    }

    public function dataList(){
        $page     = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id   = $this->params['avg_exp_id'];
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $pagesize = 10;

        $m_store = new \Common\Model\Smallapp\StoreModel();
        $res_store = $m_store->getHotelStore($area_id,$county_id,$food_style_id,$avg_id);
        if($longitude>0 && $latitude>0){
//            $bd_lnglat = getgeoByTc($latitude, $longitude);
//            $latitude = $bd_lnglat[0]['y'];
//            $longitude = $bd_lnglat[0]['x'];
            $bd_lnglat = gpsToBaidu($longitude, $latitude);
            $latitude = $bd_lnglat['latitude'];
            $longitude = $bd_lnglat['longitude'];
            foreach($res_store as $key=>$v){
                $res_store[$key]['dis'] = '';
                if($v['gps']!='' && $longitude>0 && $latitude>0){
                    $gps_arr = explode(',',$v['gps']);
                    $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                    $res_store[$key]['dis_com'] = $dis;
                    if($dis>1000){
                        $tmp_dis = $dis/1000;
                        $dis = sprintf('%0.2f',$tmp_dis);
                        $dis = $dis.'km';
                    }else{
                        $dis = intval($dis);
                        $dis = $dis.'m';
                    }
                    $res_store[$key]['dis'] = $dis;
                }else {
                    $res_store[$key]['dis'] = '';
                }
            }
            sortArrByOneField($res_store,'dis_com');
        }

        $stock_hotel = array();
        $other_hotel = array();
        foreach ($res_store as $k=>$v){
            if($v['is_salehotel']==1){
                $stock_hotel[]=$v;
            }else{
                $other_hotel[]=$v;
            }
        }
        $res_store = array_merge($stock_hotel,$other_hotel);

        $offset = $page * $pagesize;
        $hotel_list = array_slice($res_store,0,$offset);
        $m_meida = new \Common\Model\MediaModel();
        $datalist = array();
        $oss_host = get_oss_host();
        foreach ($hotel_list as $k=>$v){
            $tag_name = $v['tag_name'];
            if(empty($tag_name)){
                $tag_name = '';
            }
            if($v['media_id']){
                $res_media = $m_meida->getMediaInfoById($v['media_id']);
                $img_url = $res_media['oss_addr'].'?x-oss-process=image/resize,p_50';
                $ori_img_url = $res_media['oss_addr'];
            }else{
                $img_url = $oss_host.'media/resource/kS3MPQBs7Y.png';
                $ori_img_url = $img_url;
            }
            $dis = $v['dis'];
            if(empty($dis)){
                $dis = '';
            }
            $tel = $v['tel'];
            if(empty($tel)){
                $tel = $v['mobile'];
            }
            $datalist[]=array('hotel_id'=>$v['hotel_id'],'name'=>$v['name'],'addr'=>$v['addr'],'tel'=>$tel,'avg_expense'=>$v['avg_expense'],
                'dis'=>$dis,'tag_name'=>$tag_name,'img_url'=>$img_url,'ori_img_url'=>$ori_img_url,'is_salehotel'=>$v['is_salehotel']
            );
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function recList(){
        $oss_host = get_oss_host();
        $hotel_box_type_arr = C('HEART_HOTEL_BOX_TYPE');
        $hotel_box_type_arr = array_keys($hotel_box_type_arr);
        $page     = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id   = $this->params['avg_exp_id'];
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $pagesize = 10;
        $m_hotel = new \Common\Model\HotelModel();
        $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,b.food_style_id,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name,a.gps";
        $where = array();
        if($area_id){
            $where['a.area_id'] = $area_id;
        }
        if($county_id){
            $where['a.county_id'] = $county_id;
        }
        if($food_style_id){
            $where['b.food_style_id'] = $food_style_id;
        }
        if($avg_id){
            $where['avg_expense'] = $this->getAvgWhere($avg_id);
        }
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['a.hotel_box_type'] = array('in',$hotel_box_type_arr);
        $test_hotel_ids = C('TEST_HOTEL');
        $where['a.id'] = array('not in',"$test_hotel_ids");
        $order = " a.id asc";
        $offset = $page * $pagesize;
        $limit = " 0 ,".$offset;
        $hotel_list = $m_hotel->alias('a')
            ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
            ->join('savor_media c on b.hotel_cover_media_id=c.id','left')
            ->join('savor_hotel_food_style d on b.food_style_id=d.id','left')
            ->field($fields)
            ->where($where)
            ->order()
            ->limit()
            ->select();
        $bd_lnglat = array();
        if($longitude>0 && $latitude>0 ) {
            $bd_lnglat = getgeoByTc($latitude, $longitude);
        }
        foreach($hotel_list as $key=>$v){
            $sql ="select id from savor_integral_merchant where hotel_id=".$v['hotel_id']." and status=1";
            $merchant_info = M()->query($sql);
            if(!empty($merchant_info)){
                $merchant_info = $merchant_info[0];
                $hotel_list[$key]['merchant_id'] = $merchant_info['id'];
            }else {
                $hotel_list[$key]['merchant_id'] = 0;
            }
            if(empty($v['food_name'])){
                $hotel_list[$key]['food_name'] = '';
            }
            if($v['img_url']){
                $hotel_list[$key]['img_url'] = $v['img_url'].'?x-oss-process=image/resize,p_20';
                $hotel_list[$key]['ori_img_url'] = $v['img_url'];
            }else {
                $hotel_list[$key]['img_url'] = $oss_host.'media/resource/kS3MPQBs7Y.png';
                $hotel_list[$key]['ori_img_url'] = $oss_host.'media/resource/kS3MPQBs7Y.png';
            }

            $hotel_list[$key]['dis'] = '';
            if($v['gps']!='' && $longitude>0 && $latitude>0){
                $latitude = $bd_lnglat[0]['y'];
                $longitude = $bd_lnglat[0]['x'];

                $gps_arr = explode(',',$v['gps']);
                $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                $hotel_list[$key]['dis_com'] = $dis;
                if($dis>1000){
                    $tmp_dis = $dis/1000;
                    $dis = sprintf('%0.2f',$tmp_dis);
                    $dis = $dis.'km';
                }else{
                    $dis = intval($dis);
                    $dis = $dis.'m';
                }
                $hotel_list[$key]['dis'] = $dis;
            }else {
                $hotel_list[$key]['dis'] = '';
            }
        }
        sortArrByOneField($hotel_list,'dis_com');
        $hotel_list = array_slice($hotel_list,0,$offset);
        $this->to_back($hotel_list);
    }

    public function getExplist(){
        $data = $this->avg_exp_arr;
        $this->to_back($data);
    }

    public function hotdrinksHotels(){
        $box_mac = $this->params['box_mac'];
        $hotel_id = $this->params['hotel_id'];
        $page = intval($this->params['page']);
        $pagesize = isset($this->params['pagesize'])?intval($this->params['pagesize']):10;
        $start = ($page-1)*$pagesize;
        $m_box = new \Common\Model\BoxModel();
        $m_hotel = new \Common\Model\HotelModel();
        if(!empty($box_mac)){
            $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
            $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id";
            $box_info = $m_box->getBoxByCondition($fields,$where);
            $area_id = $box_info[0]['area_id'];
        }else if(!empty($hotel_id)) {
            $hotel_info = $m_hotel->getOneById('area_id',$hotel_id);
            $area_id = $hotel_info['area_id'];
        }

        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'h.hotel_id';
        $where = array('g.type'=>array('in',array(40,43)),'g.status'=>1);
        $not_hotel = array(7,482,504,791,508,844,845,597,201,493,883,53,598,1366);
        $where['h.hotel_id'] = array('not in',$not_hotel);
        $res_ghotels = $m_hotelgoods->getGoodsList($fields,$where,'','','h.hotel_id');
        $hotel_ids = array();
        foreach ($res_ghotels as $v){
            $hotel_ids[]=$v['hotel_id'];
        }
        $datalist = array();
        if(!empty($hotel_ids)){
            $oss_host = get_oss_host();
            
            $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,b.food_style_id,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name,a.gps";
            $where = array('a.area_id'=>$area_id,'a.state'=>1,'a.flag'=>0);
            $where['a.id'] = array('in',$hotel_ids);
            $order = " a.pinyin asc";
            $hotel_list = $m_hotel->alias('a')
                ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
                ->join('savor_media c on b.hotel_cover_media_id=c.id','left')
                ->join('savor_hotel_food_style d on b.food_style_id=d.id','left')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->limit($start,$pagesize)
                ->select();

            foreach($hotel_list as $key=>$v){
                if(empty($v['food_name'])){
                    $hotel_list[$key]['food_name'] = '';
                }
                if($v['img_url']){
                    $hotel_list[$key]['img_url'] = $v['img_url'].'?x-oss-process=image/resize,p_20';
                    $hotel_list[$key]['ori_img_url'] = $v['img_url'];
                }else {
                    $hotel_list[$key]['img_url'] = $oss_host.'media/resource/kS3MPQBs7Y.png';
                    $hotel_list[$key]['ori_img_url'] = $oss_host.'media/resource/kS3MPQBs7Y.png';
                }
            }
            $datalist = $hotel_list;
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $content = intval($this->params['content']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid,vip_level,mobile,is_wx_auth', $where, '');
        $hotel_id = $room_id = 0;
        if($content>C('QRCODE_MIN_NUM')){
            $m_hotelqrcode = new \Common\Model\HotelQrcodeModel();
            $res_hotelqrcode = $m_hotelqrcode->getInfo(array('id'=>$content));
            if(!empty($res_hotelqrcode)){
                $hotel_id = $res_hotelqrcode['hotel_id'];
            }
        }else{
            $m_room = new \Common\Model\RoomModel();
            $res_room = $m_room->getOne('hotel_id',array('id'=>$content));
            if(!empty($res_room)){
                $hotel_id = $res_room['hotel_id'];
                $room_id = $content;
            }
        }
        $res_data = array('hotel_id'=>$hotel_id,'room_id'=>$room_id);
        if($hotel_id){
            $jump_id = 3;
            $all_jump = C('HOTELQRCODE_JUMP_PAGE');
            $m_hotelqrcode_jump = new \Common\Model\HotelQrcodeJumpModel();
            $where = array('hotel_id'=>$hotel_id,'status'=>1);
            $now_time = date('H:i:s');
            $where['start_time'] = array('ELT',$now_time);
            $where['end_time']   = array('EGT',$now_time);
            $res_hoteljump = $m_hotelqrcode_jump->getDataList('*',$where,'id desc');
            if(!empty($res_hoteljump)){
                $jump_id = $res_hoteljump[0]['open_page'];
            }
            $res_data['jump_id'] = $jump_id;
            $res_data['page'] = $all_jump[$jump_id]['page'];
            $res_data['type'] = $all_jump[$jump_id]['type'];
            switch ($jump_id){
                case 1://及时抽奖
                    $activity_id = 0;
                    $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
                    $where = array('hotel_id'=>$hotel_id,'status'=>1,'type'=>4);
                    $orderby = 'id desc';
                    $fields = 'id as syslottery_id,prize as name';
                    $res_syslottery = $m_syslottery->getDataList($fields,$where,$orderby,0,1);
                    if($res_syslottery['total']){
                        $now_syslottery_id = $res_syslottery['list'][0]['syslottery_id'];
                        $prize = $res_syslottery['list'][0]['name'];

                        $m_activity = new \Common\Model\Smallapp\ActivityModel();
                        $res_activity = $m_activity->getInfo(array('hotel_id'=>$hotel_id,'syslottery_id'=>$now_syslottery_id,'type'=>13));
                        if(!empty($res_activity)){
                            $activity_id = $res_activity['id'];
                        }else{
                            $m_lotteryprize = new \Common\Model\Smallapp\SyslotteryPrizeModel();
                            $res_lottery_prize = $m_lotteryprize->getDataList('*',array('syslottery_id'=>$now_syslottery_id),'id desc');
                            $prize_data = array();
                            foreach ($res_lottery_prize as $pv){
                                $prize_data[]=array('name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                                    'probability'=>$pv['probability'],'prizepool_prize_id'=>$pv['prizepool_prize_id'],'type'=>$pv['type']
                                );
                            }
                            $start_time = date('Y-m-d H:i:s');
                            $end_time = '2025-12-31 17:50:57';
                            $add_activity_data = array('hotel_id'=>$hotel_id,'openid'=>'','name'=>'幸运抽奖','prize'=>$prize,
                                'box_mac'=>'','people_num'=>100,'start_time'=>$start_time,'end_time'=>$end_time,
                                'syslottery_id'=>$now_syslottery_id,'type'=>13,'status'=>1);
                            if($room_id){
                                $add_activity_data['room_id'] = $room_id;
                            }
                            $activity_id = $m_activity->add($add_activity_data);

                            $all_prize_data = array();
                            foreach ($prize_data as $pv){
                                $all_prize_data[]=array('activity_id'=>$activity_id,'name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                                    'probability'=>$pv['probability'],'prizepool_prize_id'=>$pv['prizepool_prize_id'],'type'=>$pv['type']
                                );
                            }
                            $m_activityprize = new \Common\Model\Smallapp\ActivityprizeModel();
                            $m_activityprize->addAll($all_prize_data);
                        }
                    }

                    $res_data['page'].="?activity_id={$activity_id}&openid={$openid}&hotel_id={$hotel_id}&room_id={$room_id}&is_share=1";
                    break;
                case 2://本店有售
                    $res_data['page'].="?openid={$openid}&hotel_id={$hotel_id}&room_id={$room_id}&tab=hotel&is_share=1";
                    break;
                case 4://邀请会员
                    $vip_level = 0;
                    $is_wx_auth = 0;
                    $mobile = '';
                    if(!empty($user_info)){
                        $vip_level = $user_info['vip_level'];
                        $mobile = $user_info['mobile'];
                        $is_wx_auth = $user_info['is_wx_auth'];
                    }
                    $coupon_money = 0;
                    $coupon_end_time = '';
                    $coupon_unnum = 0;
                    if($vip_level==0){
                        $m_sys_config = new \Common\Model\SysConfigModel();
                        $sys_info = $m_sys_config->getAllconfig();
                        $vip_coupons = json_decode($sys_info['vip_coupons'],true);
                        $now_vip_level = 1;
                        if(!empty($vip_coupons) && !empty($vip_coupons[$now_vip_level])){
                            $m_coupon = new \Common\Model\Smallapp\CouponModel();
                            $where = array('id'=>array('in',$vip_coupons[$now_vip_level]));
                            $where['end_time'] = array('egt',date('Y-m-d H:i:s'));
                            $res_all_coupon = $m_coupon->getALLDataList('*',$where,'end_time desc','','');
                            $end_time = date('Y年m月d日',strtotime($res_all_coupon[0]['end_time']));
                            $coupon_end_time = $end_time.'到期';
                            foreach ($res_all_coupon as $v){
                                $coupon_money+=$v['money'];
                            }
                        }
                    }else{
                        $where = array('a.openid'=>$openid,'a.ustatus'=>1,'a.status'=>1,'coupon.type'=>2);
                        $where['a.end_time'] = array('egt',date('Y-m-d H:i:s'));
                        $fields = 'count(a.id) as num';
                        $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
                        $res_coupon = $m_coupon_user->getUsercouponDatas($fields,$where,'a.id desc','');
                        if(!empty($res_coupon)){
                            $coupon_unnum = intval($res_coupon[0]['num']);
                        }
                    }
                    $params = array('openid'=>$openid,'vip_level'=>$vip_level,'coupon_money'=>$coupon_money,'coupon_end_time'=>$coupon_end_time,
                        'coupon_unnum'=>$coupon_unnum,'hotel_id'=>$hotel_id,'room_id'=>$content,'mobile'=>$mobile,'is_wx_auth'=>$is_wx_auth,
                        'code_msg'=>'','source'=>3);
                    $res_data['params'] = json_encode($params);
                    break;
            }
        }

        $this->to_back($res_data);
    }



    private function getAvgWhere($avg_id){
        switch ($avg_id){
            case 1:
                $where = array('LT',100);
                break;
            case 2:
                $where = array(array('EGT',100),array('ELT',200));
                break;
            case 3:
                $where = array('GT',200);
                break;
            default:
                $where = array();
        }
        return $where;
        
    }
}