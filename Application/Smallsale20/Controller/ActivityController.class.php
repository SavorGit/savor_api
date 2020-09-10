<?php
namespace Smallsale20\Controller;
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
                $this->valid_fields = array('hotel_id'=>1001,'activity_name'=>1001,'prize'=>1001,'image'=>1001,'lottery_time'=>1001);
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
        $where = array('hotel_id'=>$hotel_id);
        $fields = 'id,name,image_url,status';
        $res_activity = $m_activity->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if(!empty($res_activity)){
            $oss_host = 'http://'. C('OSS_HOST').'/';
            $all_status_str = C('ACTIVITY_STATUS');
            foreach ($res_activity as $v){
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
        $lottery_time = $this->params['lottery_time'];
        $lottery_stime = strtotime($lottery_time);
        $lottery_time = date('Y-m-d H:i:s',$lottery_stime);

        $tmp_lottery_time = time() + 7200;
        $tmp_lottery_hour = date('H',$tmp_lottery_time);
        $lottery_hour = date('H',$lottery_stime);
        if($lottery_hour!=$tmp_lottery_hour){
            $this->to_back(93053);
        }
        $start_time = date('Y-m-d H:i:s',$lottery_stime-3600);
        $end_time = date('Y-m-d H:i:s',$lottery_stime-300);

        $where = array('hotel_id'=>$hotel_id);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getActivity('*',$where,'id desc',0,1);
        if(!empty($res_activity)){
            $last_lottery_time = strtotime($res_activity[0]['lottery_time']);
            if($lottery_stime==$last_lottery_time){
                $this->to_back(93054);
            }
            $start_stime = strtotime($start_time);
            if($last_lottery_time==$start_stime){
                $start_stime = $start_stime + 600;
                $start_time = date('Y-m-d H:i:s',$start_stime);
            }
        }

        $data = array('hotel_id'=>$hotel_id,'name'=>$activity_name,'prize'=>$prize,'image_url'=>$image_url,
            'start_time'=>$start_time,'end_time'=>$end_time,'lottery_time'=>$lottery_time,'status'=>0);
        $m_activity->add($data);
        $this->to_back(array());
    }



}