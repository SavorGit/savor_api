<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;
class ActivityController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'getActivityList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'page'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
            case 'cancel':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
            case 'addActivity':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'activity_name'=>1001,'prize'=>1001,'image'=>1001,'lottery_day'=>1001,'lottery_hour'=>1001,
                    'lottery_minute'=>1002,'wait_time'=>1002);
                break;
            case 'addJuactivity':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'box_mac'=>1001,'activity_name'=>1001,'prize1'=>1001,'prize2'=>1001,'rcontent'=>1001,'is_compareprice'=>1002);
                break;
            case 'getJuactivityList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'page'=>1001);
                break;
            case 'startJuactivity':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'activity_id'=>1001);
                break;
            case 'judetail':
                $this->is_verify = 1;
                $this->valid_fields = array('activity_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getActivityList(){
        $hotel_id = intval($this->params['hotel_id']);
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $where = array('hotel_id'=>$hotel_id,'type'=>1);
        $fields = 'id,name,image_url,status';
        $res_activity = $m_activity->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_activity['total']>0){
            $oss_host = 'http://'. C('OSS_HOST').'/';
            $all_status_str = C('ACTIVITY_STATUS');
            foreach ($res_activity['list'] as $v){
                $image_url = $oss_host.$v['image_url'];
                $status_str = $all_status_str[$v['status']];
                $info = array('activity_id'=>$v['id'],'name'=>$v['name'],'status'=>$v['status'],'status_str'=>$status_str,'image_url'=>$image_url);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function detail(){
        $activity_id = intval($this->params['activity_id']);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        $nickName = $avatarUrl = '';
        if($res_activity['status']==2){
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $res_lottery = $m_activityapply->getInfo(array('activity_id'=>$activity_id,'status'=>2));
            if(!empty($res_lottery)){
                $m_user = new \Common\Model\Smallapp\UserModel();
                $fields = 'nickName,avatarUrl';
                $where = array('openid'=>$res_lottery['openid']);
                $res_user = $m_user->getOne($fields,$where,'');
                $nickName = $res_user['nickName'];
                $avatarUrl = $res_user['avatarUrl'];
            }
        }

        $oss_host = 'http://'. C('OSS_HOST').'/';
        $all_status_str = C('ACTIVITY_STATUS');
        $image_url = $oss_host.$res_activity['image_url'];
        $status_str = $all_status_str[$res_activity['status']];
        $lottery_time = date("Y.m.d-H:i",strtotime($res_activity['lottery_time']));

        $data = array('activity_id'=>$res_activity['id'],'name'=>$res_activity['name'],'prize'=>$res_activity['prize'],
            'status'=>$res_activity['status'],'status_str'=>$status_str,'image_url'=>$image_url,'lottery_time'=>$lottery_time,
            'nickName'=>$nickName,'avatarUrl'=>$avatarUrl);
        $this->to_back($data);
    }

    public function cancel(){
        $activity_id = intval($this->params['activity_id']);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if($res_activity['status']==0){
            $m_activity->updateData(array('id'=>$activity_id),array('status'=>3));
            $this->to_back(array());
        }else{
            $this->to_back(93055);
        }
    }

    public function addActivity(){
        $hotel_id = intval($this->params['hotel_id']);
        $activity_name = trim($this->params['activity_name']);
        $prize = trim($this->params['prize']);
        $image_url = $this->params['image'];
        $lottery_day = intval($this->params['lottery_day']);
        $lottery_hour = $this->params['lottery_hour'];
        $lottery_minute = intval($this->params['lottery_minute']);
        $wait_time = intval($this->params['wait_time']);
        if($lottery_minute>0){
            $lottery_hour = "$lottery_hour:$lottery_minute";
        }
        if($lottery_day==0){
            $lottery_time = date("Y-m-d $lottery_hour:00");
        }else{
            $lottery_time = date("Y-m-d $lottery_hour:00",strtotime("+$lottery_day day"));
        }
        $lottery_stime = strtotime($lottery_time);
        $lottery_time = date('Y-m-d H:i:s',$lottery_stime);

        $now_time = time();
        $now_date = date('Ymd');
        $lottery_date = date('Ymd',$lottery_stime);
        if($wait_time>0){
            if($now_time+60>$lottery_stime || $lottery_date<$now_date){
                $this->to_back(93067);
            }
            $start_time = $lottery_time;
            $end_time = date('Y-m-d H:i:s',$lottery_stime+($wait_time*60)-300);
            $lottery_time = date('Y-m-d H:i:s',$lottery_stime+($wait_time*60));
        }else{
            if($now_time>$lottery_stime || $lottery_date<$now_date){
                $this->to_back(93053);
            }
            if($lottery_date==$now_date){
                $tmp_lottery_time = $now_time + 7200;
                $tmp_lottery_hour = date('G',$tmp_lottery_time);
                $lottery_hour = date('G',$lottery_stime);
                if($lottery_hour<$tmp_lottery_hour){
                    $this->to_back(93053);
                }
            }
            $start_time = date('Y-m-d H:i:s',$lottery_stime-3600);
            $end_time = date('Y-m-d H:i:s',$lottery_stime-300);
        }

        $where = array('hotel_id'=>$hotel_id,'status'=>array('in',array('0','1')));
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getActivity('*',$where,'id desc',0,100);
        if(!empty($res_activity)){
            $start_stime = strtotime($start_time);
            foreach ($res_activity as $v){
                $last_lottery_time = strtotime($v['lottery_time']);
                if($lottery_stime==$last_lottery_time){
                    $this->to_back(93054);
                }
                if($last_lottery_time==$start_stime){
                    $start_stime = $start_stime + 600;
                    $start_time = date('Y-m-d H:i:s',$start_stime);
                }

            }
        }

        $data = array('hotel_id'=>$hotel_id,'name'=>$activity_name,'prize'=>$prize,'image_url'=>$image_url,
            'start_time'=>$start_time,'end_time'=>$end_time,'lottery_time'=>$lottery_time,'status'=>0,'type'=>1);
        $m_activity->add($data);
        $this->to_back(array());
    }

    public function addJuactivity(){
        $hotel_id = intval($this->params['hotel_id']);
        $activity_name = trim($this->params['activity_name']);
        $prize = trim($this->params['prize1']);
        $attach_prize = trim($this->params['prize2']);
        $rule_content = trim($this->params['rcontent']);
        $box_mac = $this->params['box_mac'];
        $is_compareprice = $this->params['is_compareprice'];
        if(!empty($is_compareprice)){
            $is_compareprice = intval($is_compareprice);
        }else{
            $is_compareprice = 0;
        }
        $data = array('hotel_id'=>$hotel_id,'box_mac'=>$box_mac,'name'=>$activity_name,'prize'=>$prize,'attach_prize'=>$attach_prize,
            'rule_content'=>$rule_content,'status'=>0,'is_compareprice'=>$is_compareprice,'type'=>5);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $m_activity->add($data);
        $this->to_back(array());
    }

    public function getJuactivityList(){
        $hotel_id = intval($this->params['hotel_id']);
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $where = array('hotel_id'=>$hotel_id,'type'=>5);
        $fields = 'id,name,box_mac,status,add_time';
        $res_activity = $m_activity->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_activity['total']>0){
            $all_status_str = C('ACTIVITY_STATUS');
            $m_box = new \Common\Model\BoxModel();
            $now_time = time();
            foreach ($res_activity['list'] as $v){
                if($v['status']==1){
                    $expire_time = strtotime($v['add_time']) + 3600;
                    if($now_time>$expire_time){
                        $v['status'] = 2;
                        $m_activity->updateData(array('id'=>$v['id']),array('status'=>2));
                    }
                }
                $box_mac = $v['box_mac'];
                $res_box = $m_box->getHotelInfoByBoxMacNew($box_mac);
                $room_name = '';
                if(!empty($res_box)){
                    $room_name = $res_box['box_name'];
                }
                $status_str = $all_status_str[$v['status']];
                $info = array('activity_id'=>$v['id'],'name'=>$v['name'],'room_name'=>$room_name,'status'=>$v['status'],'status_str'=>$status_str);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function startJuactivity(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if($res_activity['status']==0){
            $m_hotel = new \Common\Model\HotelModel();
            $hfield = 'hotel.name as hotel_name,ext.hotel_cover_media_id';
            $res_hotel_ext = $m_hotel->getHotelById($hfield,array('hotel.id'=>$res_activity['hotel_id']));
            $m_media = new \Common\Model\MediaModel();
            $res_media = $m_media->getMediaInfoById($res_hotel_ext['hotel_cover_media_id']);
            $headPic = base64_encode($res_media['oss_addr']);

            $m_box = new \Common\Model\BoxModel();
            $res_box = $m_box->getHotelInfoByBoxMacNew($res_activity['box_mac']);
            $host_name = C('HOST_NAME');
            $code_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_id={$res_box['box_id']}&box_mac={$res_activity['box_mac']}&data_id={$activity_id}&type=39";
            $message = array('action'=>151,'countdown'=>120,'nickName'=>$res_hotel_ext['hotel_name'],'headPic'=>$headPic,'codeUrl'=>$code_url);
            $m_netty = new \Common\Model\NettyModel();
            $res_netty = $m_netty->pushBox($res_activity['box_mac'],json_encode($message));
            if(isset($res_netty['error_code'])){
                $this->to_back($res_netty['error_code']);
            }
//            $m_activity->updateData(array('id'=>$activity_id),array('status'=>1));
        }
        $this->to_back(array());
    }

    public function judetail(){
        $activity_id = intval($this->params['activity_id']);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        $lottery_time = '';
        if($res_activity['status']==2){
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $res_lottery = $m_activityapply->getApplylist('*',array('activity_id'=>$activity_id,'status'=>2),'id asc','');
            if(!empty($res_lottery)){
                $lottery_time = $res_lottery[0]['add_time'];
            }
        }
        $m_box = new \Common\Model\BoxModel();
        $res_box = $m_box->getHotelInfoByBoxMacNew($res_activity['box_mac']);
        $box_name = '';
        if(!empty($res_box)){
            $box_name = $res_box['box_name'];
        }
        $all_status_str = C('ACTIVITY_STATUS');
        $status_str = $all_status_str[$res_activity['status']];

        $data = array('activity_id'=>$res_activity['id'],'name'=>$res_activity['name'],'prize1'=>$res_activity['prize'],
            'prize2'=>$res_activity['attach_prize'],'rule_content'=>$res_activity['rule_content'],'status'=>$res_activity['status'],'status_str'=>$status_str,
            'lottery_time'=>$lottery_time,'box_name'=>$box_name);
        $this->to_back($data);
    }



}