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

    public function recList(){
        $oss_host = 'http://'. C('OSS_HOST').'/';
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
        $where['a.id'] = array('not in','7,482,504,791,508,844,845,597,201,493,883,53');
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
                $hotel_list[$key]['img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                $hotel_list[$key]['ori_img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
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
        $where['h.hotel_id'] = array('not in','7,482,504,791,508,844,845,597,201,493,883,53,925');
        $res_ghotels = $m_hotelgoods->getGoodsList($fields,$where,'','','h.hotel_id');
        $hotel_ids = array();
        foreach ($res_ghotels as $v){
            $hotel_ids[]=$v['hotel_id'];
        }
        $datalist = array();
        if(!empty($hotel_ids)){
            $oss_host = 'http://'. C('OSS_HOST').'/';
            
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
                    $hotel_list[$key]['img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                    $hotel_list[$key]['ori_img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
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
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
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
            $where = array('hotel_id'=>$hotel_id);
            $now_time = date('H:i:s');
            $where['start_time'] = array('ELT',$now_time);
            $where['end_time']   = array('EGT',$now_time);
            $res_hoteljump = $m_hotelqrcode_jump->getDataList('*',$where,'id desc');
            if(!empty($res_hoteljump)){
                $jump_id = $res_hoteljump[0]['open_page'];
            }
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
                                'box_mac'=>'','people_num'=>1,'start_time'=>$start_time,'end_time'=>$end_time,
                                'syslottery_id'=>$now_syslottery_id,'type'=>13,'status'=>1);
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