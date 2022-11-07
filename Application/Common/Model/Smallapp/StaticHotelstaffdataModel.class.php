<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;
class StaticHotelstaffdataModel extends BaseModel{

    protected $tableName='smallapp_static_hotelstaffdata';

    public function getStaffData($area_id,$maintainer_id,$hotel_id,$start_time,$end_time){
        $start_date = date('Y-m-d',strtotime($start_time));
        $end_date = date('Y-m-d',strtotime($end_time));
        $fields = $this->query_fields();
        $where = array();
        if($area_id){
            $where['area_id'] = $area_id;
        }
        if($maintainer_id){
            $where['maintainer_id'] = $maintainer_id;
        }
        if($hotel_id){
            $where['hotel_id'] = $hotel_id;
        }
        $where['static_date'] = array(array('egt',$start_date),array('elt',$end_date));
        $stat_data = $this->getDataList($fields,$where,'id desc');
        $forscreen_num = $pub_num = $welcome_num = $birthday_num = $signin_num = $get_integral = $money = 0;
        if(!empty($stat_data)){
            $get_integral = intval($stat_data[0]['get_integral']);
            $money = intval($stat_data[0]['money']);
            $forscreen_num = intval($stat_data[0]['forscreen_num']);
            $pub_num = intval($stat_data[0]['pub_num']);
            $welcome_num = intval($stat_data[0]['welcome_num']);
            $birthday_num = intval($stat_data[0]['birthday_num']);
            $signin_num = intval($stat_data[0]['signin_num']);
        }
        $task_data = $this->taskdata($stat_data);
        $res_data = array('forscreen_num'=>$forscreen_num,'pub_num'=>$pub_num,'welcome_num'=>$welcome_num,
            'birthday_num'=>$birthday_num,'signin_num'=>$signin_num,'get_integral'=>$get_integral,'money'=>$money,
            'task_data'=>$task_data);
        return $res_data;
    }

    public function getHotelStaffData($hotel_id,$start_time,$end_time){
        $start_date = date('Y-m-d',strtotime($start_time));
        $end_date = date('Y-m-d',strtotime($end_time));
        $fields = $this->query_fields();
        $where = array('hotel_id'=>$hotel_id);
        $where['static_date'] = array(array('egt',$start_date),array('elt',$end_date));
        $res_stat_data = $this->getALLDataList($fields,$where,'get_integral desc','','openid');
        $res_data = array();
        if(!empty($res_stat_data)){
            foreach ($res_stat_data as $v){
                $stat_data = array($v);
                $task_data = $this->taskdata($stat_data);
                $forscreen_num = intval($stat_data[0]['forscreen_num']);
                $get_integral = intval($stat_data[0]['get_integral']);
                $money = intval($stat_data[0]['money']);
                $info = array('openid'=>$v['openid'],'forscreen_num'=>$forscreen_num,'get_integral'=>$get_integral,'money'=>$money,
                    'task_data'=>$task_data);
                $res_data[] = $info;
            }
        }
        return $res_data;
    }

    public function taskdata($stat_data){
        $stat_task_types = C('STAT_TASK_TYPES');
        $task_data = array();
        foreach ($stat_task_types as $k=>$v){
            switch ($k){
                case 26:
                    $release_num = 0;
                    if(!empty($stat_data[0]['invitevip_release_num'])){
                        $release_num = $stat_data[0]['invitevip_release_num'];
                    }
                    $get_num = 0;
                    if(!empty($stat_data[0]['invitevip_get_num'])){
                        $get_num = $stat_data[0]['invitevip_get_num'];
                    }
                    $sale_num = 0;
                    if(!empty($stat_data[0]['invitevip_sale_num'])){
                        $sale_num = $stat_data[0]['invitevip_sale_num'];
                    }
                    $getcoupon_num = 0;
                    if(!empty($stat_data[0]['invitevip_getcoupon_num'])){
                        $getcoupon_num = $stat_data[0]['invitevip_getcoupon_num'];
                    }
                    $rewardintegral_num = 0;
                    if(!empty($stat_data[0]['invitevip_rewardintegral_num'])){
                        $rewardintegral_num = $stat_data[0]['invitevip_rewardintegral_num'];
                    }
                    $datas = array(
                        array('name'=>'发布','value'=>$release_num),
                        array('name'=>'领取','value'=>$get_num),
                        array('name'=>'核销券','value'=>$sale_num),
                        array('name'=>'领券人','value'=>$getcoupon_num),
                        array('name'=>'奖励','value'=>$rewardintegral_num),
                    );
                    $info = array('name'=>'奖券任务（只统计金卡）','task_type'=>26,'datas'=>$datas);
                    break;
                case 25:
                    $release_num = 0;
                    if(!empty($stat_data[0]['demand_release_num'])){
                        $release_num = $stat_data[0]['demand_release_num'];
                    }
                    $get_num = 0;
                    if(!empty($stat_data[0]['demand_get_num'])){
                        $get_num = $stat_data[0]['demand_get_num'];
                    }
                    $operate_num = 0;
                    if(!empty($stat_data[0]['demand_operate_num'])){
                        $operate_num = $stat_data[0]['demand_operate_num'];
                    }
                    $finish_num = 0;
                    if(!empty($stat_data[0]['demand_finish_num'])){
                        $finish_num = $stat_data[0]['demand_finish_num'];
                    }
                    $rewardintegral_num = 0;
                    if(!empty($stat_data[0]['demand_rewardintegral_num'])){
                        $rewardintegral_num = $stat_data[0]['demand_rewardintegral_num'];
                    }
                    $datas = array(
                        array('name'=>'发布','value'=>$release_num),
                        array('name'=>'领取','value'=>$get_num),
                        array('name'=>'应操作','value'=>$operate_num),
                        array('name'=>'完成','value'=>$finish_num),
                        array('name'=>'奖励','value'=>$rewardintegral_num),
                    );
                    $info = array('name'=>'点播任务','task_type'=>25,'datas'=>$datas);
                    break;
                case 6:
                    $release_num = 0;
                    if(!empty($stat_data[0]['invitation_release_num'])){
                        $release_num = $stat_data[0]['invitation_release_num'];
                    }
                    $get_num = 0;
                    if(!empty($stat_data[0]['invitation_get_num'])){
                        $get_num = $stat_data[0]['invitation_get_num'];
                    }
                    $operate_num = 0;
                    if(!empty($stat_data[0]['invitation_operate_num'])){
                        $operate_num = $stat_data[0]['invitation_operate_num'];
                    }
                    $finish_num = 0;
                    if(!empty($stat_data[0]['invitation_finish_num'])){
                        $finish_num = $stat_data[0]['invitation_finish_num'];
                    }
                    $rewardintegral_num = 0;
                    if(!empty($stat_data[0]['invitation_rewardintegral_num'])){
                        $rewardintegral_num = $stat_data[0]['invitation_rewardintegral_num'];
                    }
                    $datas = array(
                        array('name'=>'发布','value'=>$release_num),
                        array('name'=>'领取','value'=>$get_num),
                        array('name'=>'应操作','value'=>$operate_num),
                        array('name'=>'完成','value'=>$finish_num),
                        array('name'=>'奖励','value'=>$rewardintegral_num),
                    );
                    $info = array('name'=>'邀请函','task_type'=>6,'datas'=>$datas);
                    break;
                default:
                    $info = array();
            }
            $task_data[]=$info;
        }
        return $task_data;
    }

    private function query_fields(){
        $fields = 'sum(forscreen_num) as forscreen_num,sum(pub_num) as pub_num,sum(welcome_num) as welcome_num,sum(birthday_num) as birthday_num,
        sum(signin_num) as signin_num,sum(integral) as get_integral,sum(money) as money,sum(task_invitevip_release_num) as invitevip_release_num,
        sum(task_invitevip_get_num) as invitevip_get_num,sum(task_invitevip_sale_num) as invitevip_sale_num,sum(task_invitevip_getcoupon_num) as invitevip_getcoupon_num,
        sum(task_invitevip_rewardintegral_num) as invitevip_rewardintegral_num,sum(task_demand_release_num) as demand_release_num,sum(task_demand_get_num) as demand_get_num,
        sum(task_demand_operate_num) as demand_operate_num,sum(task_demand_finish_num) as demand_finish_num,sum(task_demand_rewardintegral_num) as demand_rewardintegral_num,
        sum(task_invitation_release_num) as invitation_release_num,sum(task_invitation_get_num) as invitation_get_num,sum(task_invitation_operate_num) as invitation_operate_num,
        sum(task_invitation_finish_num) as invitation_finish_num,sum(task_invitation_rewardintegral_num) as invitation_rewardintegral_num,openid
        ';
        return $fields;
    }
}