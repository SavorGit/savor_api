<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivityTastewineController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getTastewineinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001);
                break;
            case 'joinTastewine':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001,'mobile'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getTastewineinfo(){
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
        $people_num = $res_activity['people_num'];
        $bwhere = array('activity_id'=>$activity_id,'box_mac'=>$box_mac);

        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_box_apply = $m_activityapply->getApplylist('count(*) as num',$bwhere,'id desc','');
        $taste_wine_apply_num = 0;
        if(!empty($res_activity_box_apply)){
            $taste_wine_apply_num = $res_activity_box_apply[0]['num'];
        }
        $u_fields = 'count(a.id) as num';
        $u_where = array('a.openid'=>$openid,'activity.type'=>7);
        $res_activity_user_apply = $m_activityapply->getApplyDatas($u_fields,$u_where,'a.id desc','','');
        $taste_user_wine_apply_num = 0;
        if(!empty($res_activity_user_apply)){
            $taste_user_wine_apply_num = $res_activity_user_apply[0]['num'];
        }
        $oss_host = C('OSS_HOST');
        $taste_wine = array('is_pop_wind'=>false,'status'=>0,'height_img'=>'http://'.$oss_host.'/'.$res_activity['portrait_image_url'],
            'width_img'=>'http://'.$oss_host.'/'.$res_activity['image_url'],'message'=>'','tips'=>'','qrcode_type'=>41);
        if($people_num>$taste_wine_apply_num && $taste_user_wine_apply_num<3){
            $taste_wine['activity_id'] = $activity_id;
            $taste_wine['is_pop_wind'] = true;
        }
        $where = array('activity_id'=>$activity_id,'openid'=>$openid);
        $res_activity_apply = $m_activityapply->getApplylist('*',$where,'id desc','');
        if(!empty($res_activity_apply) && $res_activity_apply[0]['status']==1){
            $taste_wine['activity_id'] = $activity_id;
            $taste_wine['is_pop_wind'] = true;

            unset($where['openid']);
            $where['box_mac'] = $box_mac;
            $res_activity_apply = $m_activityapply->getApplylist('openid',$where,'id asc','');
            $get_position = 0;
            foreach ($res_activity_apply as $k=>$v){
                if($v['openid']==$openid){
                    $get_position = $k+1;
                    break;
                }
            }
            $taste_wine['status'] = 2;
            $taste_wine['message'] = "恭喜您领到第{$get_position}份品鉴酒";
            $taste_wine['tips'] = '请向服务员出示此页面领取';
        }
        $this->to_back($taste_wine);
    }

    public function joinTastewine(){
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
        $where = array('activity_id'=>$activity_id,'hotel_id'=>$hotel_id,'box_mac'=>$box_mac);
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_apply = $m_activityapply->getApplylist('count(*) as num',$where,'id desc','');
        if($res_activity_apply[0]['num']>=$people_num){
            $this->to_back(90177);
        }
        $u_fields = 'count(a.id) as num';
        $u_where = array('a.openid'=>$openid,'activity.type'=>7);
        $res_activity_apply = $m_activityapply->getApplyDatas($u_fields,$u_where,'a.id desc','','');
        if($res_activity_apply[0]['num']>3){
            $this->to_back(90178);
        }
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
        $where = array('activity_id'=>$activity_id,'box_mac'=>$box_mac);
        $res_activity_apply = $m_activityapply->getApplylist('*',$where,'id asc','');
        $get_position = 0;
        foreach($res_activity_apply as $k=>$v){
            if($v['openid'] == $openid){
                $get_position = $k + 1;
                break;
            }
        }
        if($get_position==0){
            $get_position = count($res_activity_apply)+1;
        }
        $resp_data = array('message'=>"恭喜您领到第{$get_position}份品鉴酒",'tips'=>'请向服务员出示此页面领取');
        //更新任务信息,更新积分
        $m_taskuser = new \Common\Model\Integral\TaskuserModel();
        $fields = 'a.openid,a.task_id,a.integral as user_integral,a.people_num,task.goods_id,task.integral';
        $where = array('a.id'=>$res_activity['task_user_id']);
        $res_task = $m_taskuser->getUserTaskList($fields,$where,'a.id desc');

        $where = array('a.openid'=>$res_task[0]['openid'],'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'merchant.id as merchant_id,merchant.is_integral';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(!empty($res_staff)){
            if($res_staff[0]['is_integral']==1){
                $integralrecord_openid = $res_task[0]['openid'];
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
            }else{
                $integralrecord_openid = $hotel_id;
                $tudata = array('people_num'=>$res_task[0]['people_num']+1);
                $m_taskuser->updateData(array('id'=>$res_activity['task_user_id']),$tudata);

                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $where = array('id'=>$res_staff[0]['merchant_id']);
                $m_merchant->where($where)->setInc('integral',$res_task[0]['integral']);
            }

            $m_goodsstock = new \Common\Model\Smallapp\GoodsstockModel();
            $m_goodsstock->where(array('goods_id'=>$res_task[0]['goods_id'],'hotel_id'=>$hotel_id))->setInc('consume_drink_copies',1);

            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getHotelInfoById($hotel_id);
            $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_hotel['area_id'],'task_id'=>$res_task[0]['task_id'],'area_name'=>$res_hotel['area_name'],
                'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                'room_id'=>$room_id,'room_name'=>$box_name,'box_id'=>$box_id,'box_mac'=>$box_mac,
                'integral'=>$res_task[0]['integral'],'content'=>1,'type'=>11,'integral_time'=>date('Y-m-d H:i:s'));
            $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegralrecord->add($integralrecord_data);
        }
        //end

        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $params = array('name'=>$res_activity['name']);
        $template_code = $ucconfig['send_tastewine_user_templateid'];
        $res_data = $alisms::sendSms($mobile,$params,$template_code);
        $data = array('type'=>13,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$params),'tel'=>$mobile,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        $where = array('openid'=>$res_task[0]['openid'],'status'=>1);
        $staff_user_info = $m_user->getOne('id,openid,mobile', $where, '');
        $tailnum = substr($mobile,-4);
        $params = array('room_name'=>$box_name,'tailnum'=>$tailnum,'name'=>$res_activity['name']);
        $template_code = $ucconfig['send_tastewine_sponsor_templateid'];
        $res_data = $alisms::sendSms($staff_user_info['mobile'],$params,$template_code);
        $data = array('type'=>13,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$params),'tel'=>$staff_user_info['mobile'],'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        $this->to_back($resp_data);
    }


}