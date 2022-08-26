<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserIntegralrecordModel extends BaseModel{
	protected $tableName='smallapp_user_integralrecord';

    public function getFinishRecordlist($fileds,$where,$orderby,$start,$size){
        $res = $this->alias('a')
            ->field($fileds)
            ->join('savor_smallapp_user user on user.openid=a.openid','left')
            ->where($where)
            ->order($orderby)
            ->limit($start,$size)
            ->select();
        return $res;
    }

	public function getIntegralBytime($openid,$type,$start_time,$end_time){
        $where = array('openid'=>$openid,'type'=>$type);
        $where['add_time'] = array(array('egt',$start_time), array('elt',$end_time));
        $fields = 'sum(integral) as total_integral';
        $res = $this->field($fields)->where($where)->find();
        $total_integral = 0;
        if(!empty($res)){
            $total_integral = intval($res['total_integral']);
        }
        return $total_integral;
    }

    public function activityPromote($openid,$box_mac,$goods_id,$type){
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $key_integral = C('SAPP_SALE_ACTIVITY_PROMOTE');
        $key_opintegral = $key_integral.date('Ymd').':'.$openid;
        $res_opintegral = $redis->get($key_opintegral);
        $data = array('date'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id,'box_mac'=>$box_mac);
        if(!empty($res_opintegral)){
            $res_opintegral = json_decode($res_opintegral,true);
            $res_opintegral[$type][]=$data;
        }else{
            $res_opintegral=array();
            $res_opintegral[$type][]=$data;
        }
        $redis->set($key_opintegral,json_encode($res_opintegral),86400*7);
    }

    public function activityRewardIntegral($openid,$box_mac,$goods_id=0,$hotel_id=0){
        $feast_time = C('FEAST_TIME');
        $now_date = date('Y-m-d');
        $lunch_stime = $now_date.' '.$feast_time['lunch'][0];
        $lunch_etime = $now_date.' '.$feast_time['lunch'][1];
        $dinner_stime = $now_date.' '.$feast_time['dinner'][0];
        $dinner_etime = $now_date.' '.$feast_time['dinner'][1];
        $now_time = date('Y-m-d H:i');
        $fj_type = 0;//饭局类型0无,1午饭,2晚饭
        if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
            $fj_type = 1;
        }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
            $fj_type = 2;
        }
        if($openid && $fj_type){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $key_integral = C('SAPP_SALE_OPGOODS_INTEGRAL');
            $key_opintegral = $key_integral.date('Ymd').':'.$openid;
            $res_opintegral = $redis->get($key_opintegral);
            $fj_data = array();
            if(!empty($res_opintegral)){
                $res_opintegral = json_decode($res_opintegral,true);
                if(!isset($res_opintegral[$fj_type])){
                    $fj_data = array('date'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id);
                }
            }else{
                $res_opintegral = array();
                $fj_data = array('date'=>date('Y-m-d H:i:s'),'goods_id'=>$goods_id);
            }

            if(!empty($fj_data)){
                $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
                if($hotel_id){
                    $where['merchant.hotel_id']=$hotel_id;
                }
                $m_staff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_staff->getMerchantStaff('merchant.id as merchant_id,merchant.is_integral,a.openid',$where);
                if(!empty($res_staff)){
                    $m_integral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
                    $tmp_date = date('Y-m');
                    $start_time = $tmp_date.'-01 00:00:00';
                    $end_time = $tmp_date.'-31 23:59:59';

                    $max_integral = 3540;
                    $integral = 60;
                    $type = 6;
                    if($res_staff[0]['is_integral']==1){
                        $integralrecord_openid = $openid;
                    }else{
                        $integralrecord_openid = $hotel_id;
                    }

                    $total_integral = $m_integral_record->getIntegralBytime($integralrecord_openid,$type,$start_time,$end_time);
                    if($total_integral<$max_integral){
                        $res_opintegral[$fj_type] = $fj_data;
                        $redis->set($key_opintegral,json_encode($res_opintegral));

                        $m_box = new \Common\Model\BoxModel();
                        $res_box = $m_box->getHotelInfoByBoxMacNew($box_mac);
                        $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_box['area_id'],'area_name'=>$res_box['area_name'],
                            'hotel_id'=>$res_box['hotel_id'],'hotel_name'=>$res_box['hotel_name'],'hotel_box_type'=>$res_box['hotel_box_type'],
                            'room_id'=>$res_box['room_id'],'room_name'=>$res_box['room_name'],'box_id'=>$res_box['box_id'],'box_mac'=>$box_mac,
                            'box_type'=>$res_box['box_type'],'integral'=>$integral,'goods_id'=>$goods_id,'content'=>1,'type'=>$type,
                            'fj_type'=>$fj_type,'integral_time'=>date('Y-m-d H:i:s'));
                        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
                        $m_userintegralrecord->add($integralrecord_data);

                        if($res_staff[0]['is_integral']==1){
                            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                            $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
                            if(!empty($res_integral)){
                                $userintegral = $res_integral['integral']+$integral;
                                $data = array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s'));
                                $m_userintegral->updateData(array('id'=>$res_integral['id']),$data);
                            }else{
                                $data = array('openid'=>$openid,'integral'=>$integral,'update_time'=>date('Y-m-d H:i:s'));
                                $m_userintegral->addData($data);
                            }
                        }else{
                            $m_merchant = new \Common\Model\Integral\MerchantModel();
                            $where = array('id'=>$res_staff[0]['merchant_id']);
                            $m_merchant->where($where)->setInc('integral',$integral);
                        }

                    }
                }
            }
        }
    }

    /**
     * @param $invitation 邀请函信息
     * @param $type 15邀请函发给客人,16邀请函客人扩散
     * @return bool
     */
    public function finishInvitationTask($invitation,$type){
        $task_integral = C('INVITATION_TASK_INTEGRAL');
        if($type==15){
            $now_integral = $task_integral['send_guest'];
        }else{
            $now_integral = $task_integral['guest_to_user'];
        }
        $m_task = new \Common\Model\Integral\TaskuserModel();
        $task_where = array('a.openid'=>$invitation['openid'],'a.status'=>1,'task.type'=>1,'task.task_type'=>6);
        $task_where["DATE_FORMAT(a.add_time,'%Y-%m-%d')"] = date('Y-m-d');
        $res_task = $m_task->getUserTaskList('a.id,task.task_info,task.integral',$task_where,'a.id desc');
        $task_user_id = 0;
        if(!empty($res_task)){
            $task_user_id = $res_task[0]['id'];
            $now_task_integral = $res_task[0]['integral'];
            $task_content = json_decode($res_task[0]['task_info'],true);
            $lunch_start_time = $task_content['lunch_start_time'];
            $lunch_end_time = $task_content['lunch_end_time'];
            $dinner_start_time = $task_content['dinner_start_time'];
            $dinner_end_time = $task_content['dinner_end_time'];
            $week_num = $task_content['user_reward']['week_num'];
            $room_num = $task_content['user_reward']['room_num'];

            $week = date('w') == 0 ? 7 : date('w');
            $week_start = strtotime('today -' . ($week - 1) . 'day');
            $week_end = strtotime('today +' . (8 - $week) . 'day -1second');
            $week_start_time = date('Y-m-d H:i:s',$week_start);
            $week_end_time = date('Y-m-d H:i:s',$week_end);
            $where = array('openid'=>$invitation['openid'],'type'=>$type);
            $where['add_time'] = array(array('egt',$week_start_time),array('elt',$week_end_time), 'and');
            $fields = 'count(id) as num';
            $res_week_num = $this->field($fields)->where($where)->find();
            if($res_week_num['num']>=$week_num){
                $now_integral = 0;
                return true;
            }

            $now_time = date('Y-m-d H:i:s');
            $lunch_stime = date("Y-m-d {$lunch_start_time}:00");
            $lunch_etime = date("Y-m-d {$lunch_end_time}:00");
            $dinner_stime = date("Y-m-d {$dinner_start_time}:00");
            $dinner_etime = date("Y-m-d {$dinner_end_time}:59");
            $meal_stime = $meal_etime = '';
            if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
                $meal_stime = $lunch_stime;
                $meal_etime = $lunch_etime;
            }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
                $meal_stime = $dinner_stime;
                $meal_etime = $dinner_etime;
            }else{
                $now_integral = 0;
                return true;
            }
            $where = array('openid'=>$invitation['openid'],'type'=>$type,'room_id'=>$invitation['room_id']);
            $where['add_time'] = array(array('egt',$meal_stime),array('elt',$meal_etime), 'and');
            $fields = 'count(id) as num';
            $res_room_num = $this->field($fields)->where($where)->find();
            if($res_room_num['num']>=$room_num){
                $now_integral = 0;
                return true;
            }
            $now_integral = $now_task_integral;
        }
        if($now_integral==0){
            return true;
        }
        $where = array('a.openid'=>$invitation['openid'],'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('merchant.id as merchant_id,merchant.is_integral',$where);
        if($res_staff[0]['is_integral']==1){
            $where = array('openid'=>$invitation['openid'],'type'=>$type);
        }else{
            $where = array('openid'=>$invitation['hotel_id'],'type'=>$type);
        }
        $fields = 'sum(integral) as total_integral';
        $res = $this->field($fields)->where($where)->find();
        $total_integral = 0;
        if(!empty($res)){
            $total_integral = intval($res['total_integral']);
        }
        if($total_integral<$task_integral['max_limit']){
            if($total_integral+$now_integral>$task_integral['max_limit']){
                $now_integral = $task_integral['max_limit']-$total_integral>0?$task_integral['max_limit']-$total_integral:0;
            }
            if($now_integral>0){
                if($res_staff[0]['is_integral']==1){
                    $integralrecord_openid = $invitation['openid'];
                    $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                    $res_integral = $m_userintegral->getInfo(array('openid'=>$invitation['openid']));
                    if(!empty($res_integral)){
                        $userintegral = $res_integral['integral']+$now_integral;
                        $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
                    }else{
                        $m_userintegral->add(array('openid'=>$invitation['openid'],'integral'=>$now_integral));
                    }
                    if($task_user_id>0){
                        $m_task->where(array('id'=>$task_user_id))->setInc('integral',$now_integral);
                    }
                }else{
                    $integralrecord_openid = $invitation['hotel_id'];
                    $m_merchant = new \Common\Model\Integral\MerchantModel();
                    $where = array('id'=>$res_staff[0]['merchant_id']);
                    $m_merchant->where($where)->setInc('integral',$now_integral);
                }

                $m_hotel = new \Common\Model\HotelModel();
                $res_hotel = $m_hotel->getHotelInfoById($invitation['hotel_id']);
                $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_hotel['area_id'],'area_name'=>$res_hotel['area_name'],
                    'hotel_id'=>$invitation['hotel_id'],'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                    'room_id'=>$invitation['room_id'],'room_name'=>$invitation['room_name'],'integral'=>$now_integral,'jdorder_id'=>$invitation['id'],'content'=>1,'type'=>$type,
                    'integral_time'=>date('Y-m-d H:i:s'));
                $this->add($integralrecord_data);
            }
        }
        return true;
    }

    public function finishInviteVipTask($sale_openid){
        $task_integral = C('MEMBER_INTEGRAL');
        $now_integral = $task_integral['invite_vip_reward_saler'];

        $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('merchant.id as merchant_id,merchant.is_integral,merchant.hotel_id',$where);
        if(!empty($res_staff) && $now_integral>0){
            if($res_staff[0]['is_integral']==1){
                $integralrecord_openid = $sale_openid;
                $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                $res_integral = $m_userintegral->getInfo(array('openid'=>$sale_openid));
                if(!empty($res_integral)){
                    $userintegral = $res_integral['integral']+$now_integral;
                    $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
                }else{
                    $m_userintegral->add(array('openid'=>$sale_openid,'integral'=>$now_integral));
                }
            }else{
                $integralrecord_openid = $res_staff[0]['hotel_id'];
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $where = array('id'=>$res_staff[0]['merchant_id']);
                $m_merchant->where($where)->setInc('integral',$now_integral);
            }

            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getHotelInfoById($res_staff[0]['hotel_id']);
            $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_hotel['area_id'],'area_name'=>$res_hotel['area_name'],
                'hotel_id'=>$res_staff[0]['hotel_id'],'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                'integral'=>$now_integral,'content'=>1,'type'=>18,'integral_time'=>date('Y-m-d H:i:s'));
            $this->add($integralrecord_data);
        }
        return true;
    }

    public function finishDemandAdvTask($openid,$ads_id,$box_mac){
        $task_user_id = 0;
        $task_integral = 0;
        $m_task = new \Common\Model\Integral\TaskuserModel();
        $task_where = array('a.openid'=>$openid,'task.type'=>2,'task.task_type'=>25);
        $res_task = $m_task->getUserTaskList('a.id,task.task_info,task.integral',$task_where,'a.id desc');
        if(!empty($res_task)){
            $task_user_id = $res_task[0]['id'];
            $task_integral = $res_task[0]['integral'];
            $task_content = json_decode($res_task[0]['task_info'],true);
            $lunch_start_time = $task_content['lunch_start_time'];
            $lunch_end_time = $task_content['lunch_end_time'];
            $dinner_start_time = $task_content['dinner_start_time'];
            $dinner_end_time = $task_content['dinner_end_time'];
            $max_daily_integral = $task_content['max_daily_integral'];

            $now_time = date('Y-m-d H:i:s');
            $lunch_stime = date("Y-m-d {$lunch_start_time}:00");
            $lunch_etime = date("Y-m-d {$lunch_end_time}:00");
            $dinner_stime = date("Y-m-d {$dinner_start_time}:00");
            $dinner_etime = date("Y-m-d {$dinner_end_time}:59");
            $meal_stime = $meal_etime = '';
            if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
                $meal_stime = $lunch_stime;
                $meal_etime = $lunch_etime;
            }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
                $meal_stime = $dinner_stime;
                $meal_etime = $dinner_etime;
            }else{
                $task_integral = 0;
            }
            if($task_integral>0){
                $stime = date('Y-m-d 00:00:00');
                $etime = date('Y-m-d 23:59:59');
                $where = array('openid'=>$openid,'type'=>20);
                $where['add_time'] = array(array('egt',$stime),array('elt',$etime), 'and');
                $fields = 'sum(integral) as total_integral';
                $res = $this->field($fields)->where($where)->find();
                $total_integral = 0;
                if(!empty($res)){
                    $total_integral = intval($res['total_integral']);
                }
                if($total_integral<$max_daily_integral) {
                    if ($total_integral + $task_integral > $max_daily_integral) {
                        $task_integral = $max_daily_integral - $total_integral > 0 ? $max_daily_integral - $total_integral : 0;
                    }
                    if($task_integral>0){
                        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
                        $m_staff = new \Common\Model\Integral\StaffModel();
                        $res_staff = $m_staff->getMerchantStaff('merchant.id as merchant_id,merchant.hotel_id,merchant.is_integral',$where);
                        if($res_staff[0]['is_integral']==1){
                            $integralrecord_openid = $openid;
                            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                            $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
                            if(!empty($res_integral)){
                                $userintegral = $res_integral['integral']+$task_integral;
                                $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
                            }else{
                                $m_userintegral->add(array('openid'=>$openid,'integral'=>$task_integral));
                            }
                        }else{
                            $integralrecord_openid = $res_staff[0]['hotel_id'];
                            $m_merchant = new \Common\Model\Integral\MerchantModel();
                            $where = array('id'=>$res_staff[0]['merchant_id']);
                            $m_merchant->where($where)->setInc('integral',$task_integral);
                        }

                        $m_box = new \Common\Model\BoxModel();
                        $res_box = $m_box->getHotelInfoByBoxMacNew($box_mac);
                        $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_box['area_id'],'area_name'=>$res_box['area_name'],
                            'hotel_id'=>$res_box['hotel_id'],'hotel_name'=>$res_box['hotel_name'],'hotel_box_type'=>$res_box['hotel_box_type'],
                            'room_id'=>$res_box['room_id'],'room_name'=>$res_box['room_name'],'box_id'=>$res_box['box_id'],'box_mac'=>$box_mac,
                            'box_type'=>$res_box['box_type'],'integral'=>$task_integral,'jdorder_id'=>$ads_id,'content'=>1,'type'=>20,
                            'integral_time'=>date('Y-m-d H:i:s'));
                        $this->add($integralrecord_data);
                    }

                }
            }
        }
        return array('task_user_id'=>$task_user_id,'task_integral'=>$task_integral);
    }
}