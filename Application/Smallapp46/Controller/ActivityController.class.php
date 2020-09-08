<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivityController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'lottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function lottery(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $activity_id = intval($this->params['activity_id']);


        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id'])){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info,true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $hotel_id = $room_info['hotel_id'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $rets = $m_box->getBoxInfo('d.id as hotel_id,',$where);
            $hotel_id = $rets[0]['hotel_id'];
        }

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        if($activity_id>0){
            $res_activity = $m_activity->getInfo(array('id'=>$activity_id,'hotel_id'=>$hotel_id));
        }else{
            $where = array('hotel_id'=>$hotel_id,'status'=>1);
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
            $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
            $res_activity = $m_activity->getDataList('*',$where,'id desc',0,1);
            $res_activity = $res_activity['list'][0];
            $activity_id = intval($res_activity['id']);
        }
        if(empty($res_activity)){
            $this->to_back(90157);
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $activity_date = date('Y-m-d',strtotime($res_activity['start_time']));
        $start_hour = date('H:i',strtotime($res_activity['start_time']));
        $end_hour = date('H:i',strtotime($res_activity['end_time']));
        $lottery_hour = date('H:i',strtotime($res_activity['lottery_time']));

        $is_apply = 0;
        $redis = new \Common\Lib\SavorRedis();
        $cache_key = "smallapp:activity:lottery:$activity_id:$openid";
        $redis->select(1);
        $status = 0;
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        if($res_activity['status']==2){
            $where = array('openid'=>$openid,'activity_id'=>$activity_id);
            $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
            if($res_apply['total'] && $res_apply['list'][0]['status']==2){
                $status = 3;
            }else{
                $status = 4;
            }
        }else{
            $res_cache = $redis->get($cache_key);
            if(!empty($res_cache)){
                $status = 1;
            }else{
                $where = array('openid'=>$openid,'activity_id'=>$activity_id);
                $res_apply = $m_activityapply->getDataList('*',$where,'id desc',0,1);
                if($res_apply['total']){
                    $status = 1;
                }
            }
            $end_time = strtotime($res_activity['end_time']);
            $now_time = time();
            if($status==0 && $now_time>$end_time){
                $status = 2;
            }
            $now_time = date('Y-m-d H:i:s');
            if($status==0 && $now_time>$res_activity['start_time'] && $now_time<=$res_activity['end_time']){
                $is_apply = 1;
                $adata = array('activity_id'=>$activity_id,'box_mac'=>$box_mac,'openid'=>$openid,'status'=>1);
                $m_activityapply->add($adata);
                $redis->set($cache_key,date('Y-m-d H:i:s'),10800);
                $status = 1;
            }

        }
        switch ($status){
            case 1:
                $tips = '恭喜您，报名成功';
                $message = "开奖时间为{$activity_date}（今天）{$lottery_hour}，请及时关注中奖结果。详细奖项请看奖品列表";
                break;
            case 2:
                $tips = '已过本轮抽奖时间，请等待下一轮抽奖';
                $message = "本轮报名时间为{$activity_date} {$start_hour}-{$end_hour}。现已超时，请等待新一轮抽奖";
                break;
            case 3:
                $tips = "恭喜您，获得{$res_activity['prize']}";
                $expire_time = date('Y-m-d H:00',strtotime($res_apply['list'][0]['expire_time']));
                $message = "请及时联系餐厅服务人员进行兑换，过期无效。有效时间至：{$expire_time}";
                break;
            case 4:
                $tips = "很遗憾，没有中奖哦，下一轮继续吧～";
                $message = '';
                break;
            default:
                $tips = $message = '';
        }
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $data = array('activity_num'=>$activity_id,'status'=>$status,'tips'=>$tips,'message'=>$message,
            'prize_name'=>$res_activity['prize'],'img_url'=>$oss_host.$res_activity['image_url']);
        $this->to_back($data);
    }


}