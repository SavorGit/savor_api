<?php
namespace Smallsale23\Controller;
use \Common\Controller\CommonController as CommonController;
class HotelController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelList':
                $this->is_verify = 0;
                break;
            case 'getMerchantHotelList':
                $this->is_verify = 0;
                break;
            case 'tvHelpvideos':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'boot':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'type'=>1);
                break;
            case 'usage':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'type'=>1);
                break;
        }
        parent::_init_();
    }

    public function tvHelpvideos(){
        $hotel_id = intval($this->params['hotel_id']);
        $m_tvvideo = new \Common\Model\TvswitchvideoModel();
        $where = array('hotel_id'=>$hotel_id,'status'=>1);
        $res_videos = $m_tvvideo->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_videos)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_videos as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $res_url = $res_media['oss_addr'];
                $info = array('name'=>$v['name'],'url'=>$res_url);
                $datalist[]=$info;
            }
        }
        $res = array('datalist'=>$datalist);
        $this->to_back($res);
    }

    public function getHotelList(){
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('state'=>1,'flag'=>0);
        $hotel_box_types = C('HEART_HOTEL_BOX_TYPE');
        $box_types = array_keys($hotel_box_types);
        $where['hotel_box_type'] = array('in',$box_types);
        $res_hotels = $m_hotel->getHotelList($where,'id asc','','id,name');

        $m_hotel = new \Common\Model\HotelModel();
        $all_hotels = array();
        foreach ($res_hotels as $v){
            $hotel_has_room = $m_hotel->checkHotelHasRoom($v['id']);
            $v['hotel_has_room'] = $hotel_has_room;
            $letter = getFirstCharter($v['name']);
            $all_hotels[$letter][]=$v;
        }
        ksort($all_hotels);
        $data = array();
        foreach ($all_hotels as $k=>$v){
            $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
            $data[]=$dinfo;
        }
        $this->to_back($data);
    }

    public function getMerchantHotelList(){
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $fields = "hotel.id hotel_id,hotel.name as hotel_name,m.id as merchant_id";
        $where = array('m.status'=>1,'m.type'=>2,'m.mtype'=>1,'hotel.state'=>1,'hotel.flag'=>0);
        $res_data = $m_merchant->getMerchantInfo($fields,$where,'hotel.pinyin asc');
        $this->to_back($res_data);
    }

    public function getExplist(){
        $avg_exp_arr = array(
            'agv_name'=>array('请选择','100以下','100-200','200以上'),
            'agv_lisg'=>array(
                array('id'=>0,'name'=>'请选择'),
                array('id'=>1,'name'=>'100以下'),
                array('id'=>2,'name'=>'100-200'),
                array('id'=>3,'name'=>'200以上')
            ));
        $this->to_back($avg_exp_arr);
    }

    public function boot(){
        $hotel_id = intval($this->params['hotel_id']);
        $type = intval($this->params['type']);
        $map_day = array('1'=>7,'2'=>15,'3'=>30);

        $m_box = new \Common\Model\BoxModel();
        $fields = 'count(box.id) as num';
        $where = array('box.state'=>1,'box.flag'=>0,'hotel.id'=>$hotel_id);
        $res_box = $m_box->getBoxByCondition($fields,$where);
        $box_num = 0;
        if(!empty($res_box)){
            $box_num = $res_box[0]['num'];
        }
        $day_num = $map_day[$type];
        $s_date = date('Y-m-d',strtotime("-$day_num day"));
        $e_date = date('Y-m-d',strtotime("-1 day"));

        $m_basicdata = new \Common\Model\Smallapp\StaticHotelbasicdataModel();
        $fields = 'sum(heart_num) as heart_num';
        $where = array('static_date'=>array(array('EGT',$s_date),array('ELT',$e_date)),'hotel_id'=>$hotel_id);
        $res_data = $m_basicdata->getDatas($fields,$where,'');
        $heart_num = 0;
        if(!empty($res_data)){
            $heart_num = $res_data[0]['heart_num'];
        }
        $boot_time = '';
        if($heart_num && $box_num){
            $boot_time = round((($heart_num/($day_num*$box_num)) * 5)/60,1);
            $boot_time.='小时';
        }
        $box_7day_num=$box_30day_num=0;
        if($type==1){
            $m_heartlog = new \Common\Model\HeartLogModel();
            $s_30time = date('Y-m-d 00:00:00',strtotime("-30 day"));
            $s_7time = date('Y-m-d 00:00:00',strtotime("-7 day"));
            $e_7time = date('Y-m-d 00:00:00',strtotime("-1 day"));
            $where = array('hotel_id'=>$hotel_id,'last_heart_time'=>array(array('EGT',$s_7time),array('ELT',$e_7time)));
            $fields = 'count(*) as num';
            $res_boxnum = $m_heartlog->getHotelHeartBox($where,$fields);
            if(!empty($res_boxnum)){
                $box_7day_num = $res_boxnum[0]['num'];
            }
            $where['last_heart_time'] = array(array('EGT',$s_30time),array('ELT',$s_7time));
            $res_boxnum = $m_heartlog->getHotelHeartBox($where,$fields);
            if(!empty($res_boxnum)){
                $box_30day_num = $res_boxnum[0]['num'];
            }
        }
        $data = array('up_time'=>$e_date,'box_num'=>$box_num,'boot_time'=>$boot_time,
            'box_7day_num'=>$box_7day_num,'box_30day_num'=>$box_30day_num);
        $this->to_back($data);
    }

    public function usage(){
        $hotel_id = intval($this->params['hotel_id']);
        $type = intval($this->params['type']);
        $map_month = array('1'=>1,'2'=>3,'3'=>6);

        $month_num = $map_month[$type];
        $s_date = date('Y-m-d',strtotime("-$month_num month"));
        $e_date = date('Y-m-d',strtotime("-1 day"));
        $m_basicdata = new \Common\Model\Smallapp\StaticHotelbasicdataModel();
        $fields = 'sum(user_num) as user_num,sum(scancode_num) as scancode_num,sum(interact_standard_num+interact_mini_num+interact_game_num) as interact_num,
        sum(user_lunch_zxhdnum) as user_lunch_zxhdnum,sum(user_dinner_zxhdnum) as user_dinner_zxhdnum,
        sum(restaurant_user_num) as restaurant_user_num,sum(restaurant_scancode_num) as restaurant_scancode_num,
        sum(restaurant_interact_standard_num) as restaurant_interact_standard_num,sum(restaurant_user_lunch_zxhdnum) as restaurant_user_lunch_zxhdnum,
        sum(restaurant_user_dinner_zxhdnum) as restaurant_user_dinner_zxhdnum,sum(interact_sale_num) as interact_sale_num,
        sum(sale_lunch_zxhdnum) as sale_lunch_zxhdnum,sum(sale_dinner_zxhdnum) as sale_dinner_zxhdnum';

        $where = array('static_date'=>array(array('EGT',$s_date),array('ELT',$e_date)),'hotel_id'=>$hotel_id);
        $res_data = $m_basicdata->getDatas($fields,$where,'');
        $data = array();
        if(!empty($res_data)){
            $user_num = $res_data[0]['user_num'] - $res_data[0]['restaurant_user_num'];
            $user_scancode_num = $res_data[0]['scancode_num'] - $res_data[0]['restaurant_scancode_num'];
            $user_interact_num = $res_data[0]['interact_num'];
            $user_fj_num = $res_data[0]['user_lunch_zxhdnum'] + $res_data[0]['user_dinner_zxhdnum'];
            $restaurant_user_num = $res_data[0]['restaurant_user_num'];
            $restaurant_scancode_num = $res_data[0]['restaurant_scancode_num'];
            $restaurant_interact_num = $res_data[0]['restaurant_interact_standard_num'];
            $restaurant_fj_num = $res_data[0]['restaurant_user_lunch_zxhdnum'] + $res_data[0]['restaurant_user_dinner_zxhdnum'];
            $sale_interact_num = $res_data[0]['interact_sale_num'];
            $sale_fj_num = $res_data[0]['sale_lunch_zxhdnum'] + $res_data[0]['sale_dinner_zxhdnum'];

            $all_forscreen_actions = C('all_forscreen_actions');
            $m_commonforscreen = new \Common\Model\Smallapp\StaticHotelcommonforscreenModel();
            $where = array('hotel_id'=>$hotel_id,'static_date'=>array(array('EGT',$s_date),array('ELT',$e_date)));
            $fields = 'sum(use_num) as alluse_num,action';
            $res_forscreen = $m_commonforscreen->getDatas($fields,$where,'action');
            sortArrByOneField($res_forscreen,'alluse_num',true);
            $common_forscreen = array();
            $top_num = 5;
            foreach ($res_forscreen as $v){
                if($top_num==0){
                    break;
                }
                $common_forscreen[]=$all_forscreen_actions[$v['action']];
                $top_num--;
            }
            
            $data = array('user_num'=>$user_num,'user_scancode_num'=>$user_scancode_num,'user_interact_num'=>$user_interact_num,
                'user_fj_num'=>$user_fj_num,'restaurant_user_num'=>$restaurant_user_num,'restaurant_scancode_num'=>$restaurant_scancode_num,
                'restaurant_interact_num'=>$restaurant_interact_num,'restaurant_fj_num'=>$restaurant_fj_num,
                'sale_interact_num'=>$sale_interact_num,'sale_fj_num'=>$sale_fj_num,'common_forscreen'=>$common_forscreen,
                'up_time'=>$e_date
            );
        }
        //邀请函打开次数
        $m_invitation_user = new \Common\Model\Smallapp\InvitationUserModel();
        $fields = ' a.id';
        $where  = [];
        $where['i.hotel_id'] = $hotel_id;
        $where['a.add_time'] = array(array('egt',$s_date.' 00:00:00'),array('elt',$e_date.' 23:59:59'));
        //$where['a.type'] = 1;
        $ret = $m_invitation_user->alias('a')
                          ->join('savor_smallapp_invitation as i on a.invitation_id=i.id','left')
                          ->field($fields)
                          ->where($where)
                          ->group('a.invitation_id')
                          ->select();
        
        $invite_open_num = count($ret);
        //邀请函发送次数
        $m_invite = new \Common\Model\Smallapp\InvitationModel();
        
        $fields = 'id';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['add_time'] = array(array('egt',$s_date.' 00:00:00'),array('elt',$e_date.' 23:59:59'));
        $ret  = $m_invite->field($fields)->where($where)->select();
        $send_invite_nums = count($ret);
        $data['invite_open_num']  = $invite_open_num;
        $data['send_invite_nums'] = $send_invite_nums;
        $this->to_back($data);
    }
}