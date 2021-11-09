<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivityTasklotteryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getLotteryinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001);
                break;
            case 'joinLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001,'mobile'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getLotteryinfo(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));

        $oss_host = C('OSS_HOST');
        $resp_data = array('is_pop_wind'=>false,'status'=>0,'height_img'=>'http://'.$oss_host.'/'.$res_activity['portrait_image_url'],
            'width_img'=>'http://'.$oss_host.'/'.$res_activity['image_url'],'message'=>'','tips'=>'','desc'=>'','qrcode_type'=>42);

        $where = array('activity_id'=>$activity_id,'openid'=>$openid);
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_apply = $m_activityapply->getApplylist('*',$where,'id desc','');
        if(!empty($res_activity_apply) && $res_activity_apply[0]['status']==1){
            $lottery_hour = date('H:i',strtotime($res_activity['lottery_time']));
            $resp_data['activity_id'] = $activity_id;
            $resp_data['is_pop_wind'] = true;
            $resp_data['status'] = 2;
            $resp_data['message'] = '您已成功参与';
            $resp_data['tips'] = '抽奖将在'.$lottery_hour.'开始，请关注餐厅电视';
            $resp_data['desc'] = '参与人数不小于'.$res_activity['people_num'].'人即可正常开奖否则本轮抽奖活动无效';
        }
        $this->to_back($resp_data);
    }

    public function joinLottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);
        $mobile = $this->params['mobile'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_invalidlist = new \Common\Model\Smallapp\ForscreenInvalidlistModel();
        $res_invalid = $m_invalidlist->getInfo(array('invalidid'=>$openid,'type'=>2));
        if(!empty($res_invalid)){
            $resp_data = array('message'=>'无法领取','tips'=>'请联系管理员');
            $this->to_back($resp_data);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'status'=>1));
        if(empty($res_activity)){
            $this->to_back(90175);
        }
        $people_num = $res_activity['people_num'];
        $now_time = date('Y-m-d H:i:s');
        if($res_activity['start_time']>$now_time || $res_activity['end_time']<$now_time){
            $this->to_back(90182);
        }
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_' . $forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info, true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $hotel_info = json_decode($redis_hotel_info, true);
            $hotel_id = $room_info['hotel_id'];
            $hotel_name = $hotel_info['name'];
            $room_id = $box_info['room_id'];
            $box_id = $forscreen_info['box_id'];
            $box_name = $box_info['name'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $fields = 'a.id as box_id,a.name as box_name,c.id as room_id,d.id as hotel_id,d.name as hotel_name';
            $rets = $m_box->getBoxInfo($fields, $where);
            $hotel_id = $rets[0]['hotel_id'];
            $hotel_name = $rets[0]['hotel_name'];
            $room_id = $rets[0]['room_id'];
            $box_id = $rets[0]['box_id'];
            $box_name = $rets[0]['box_name'];
        }
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $where = array('activity_id'=>$activity_id,'openid'=>$openid);
        $res_activity_apply = $m_activityapply->getApplylist('*',$where,'id desc','');
        if($res_activity_apply[0]['status']==1){
            $this->to_back(90179);
        }
        $data = array('activity_id'=>$activity_id,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,'room_id'=>$room_id,
            'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1,'mobile'=>$mobile,
            'add_time'=>date('Y-m-d H:i:s')
        );
        $m_activityapply->addData($data);

        $lottery_hour = date('H:i',strtotime($res_activity['lottery_time']));
        $resp_data = array('message'=>'您已成功参与','tips'=>'抽奖将在'.$lottery_hour.'开始，请关注餐厅电视',
            'desc'=>'参与人数不小于'.$res_activity['people_num'].'人即可正常开奖否则本轮抽奖活动无效');

        //更新任务信息,更新积分
        $m_taskuser = new \Common\Model\Integral\TaskuserModel();
        $fields = 'a.openid,a.task_id,a.integral as user_integral,a.people_num,task.integral';
        $where = array('a.id'=>$res_activity['task_user_id']);
        $res_task = $m_taskuser->getUserTaskList($fields,$where,'a.id desc');
        $tudata = array('people_num'=>$res_task[0]['people_num']+1,'integral'=>$res_task[0]['user_integral']+$res_task[0]['integral']);
        $m_taskuser->updateData(array('id'=>$res_activity['task_user_id']),$tudata);

        $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
        $res_integral = $m_userintegral->getInfo(array('openid'=>$res_task[0]['openid']));
        if(!empty($res_integral)){
            $userintegral = $res_integral['integral']+$res_task[0]['integral'];
            $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
        }else{
            $uidata = array('openid'=>$openid,'integral'=>$res_task[0]['integral']);
            $m_userintegral->add($uidata);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelInfoById($hotel_id);
        $integralrecord_data = array('openid'=>$res_task[0]['openid'],'area_id'=>$res_hotel['area_id'],'task_id'=>$res_task[0]['task_id'],'area_name'=>$res_hotel['area_name'],
            'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
            'room_id'=>$room_id,'room_name'=>$box_name,'box_id'=>$box_id,'box_mac'=>$box_mac,
            'integral'=>$res_task[0]['integral'],'content'=>1,'type'=>13,'integral_time'=>date('Y-m-d H:i:s'));
        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $m_userintegralrecord->add($integralrecord_data);
        //end
        $this->to_back($resp_data);
    }


}