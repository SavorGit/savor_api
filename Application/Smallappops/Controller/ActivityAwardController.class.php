<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivityAwardController extends CommonController{


    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'filter':
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001,
                    'hotel_id'=>1002,'day'=>1002);
                $this->is_verify = 1;
                break;
            case 'statdata':
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001,
                    'start_date'=>1001,'end_date'=>1001,'hotel_id'=>1002,'status'=>1002);
                $this->is_verify = 1;
                break;
            case 'datalist':
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001,
                    'start_date'=>1001,'end_date'=>1001,'hotel_id'=>1002,'status'=>1002,'page'=>1001);
                $this->is_verify = 1;
                break;

        }
        parent::_init_();
    }

    public function filter(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $day = intval($this->params['day']);
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $m_staff->checkStaffpermission($res_staff,$area_id,$staff_id);
        if($type==1001){
            $this->to_back(1001);
        }

        $start_time = date('Y-m-d 00:00:00');
        switch ($day){
            case 1:
                $start_time = date('Y-m-d 00:00:00');
                break;
            case 2:
                $start_time = date('Y-m-d 00:00:00',strtotime('-6day'));
                break;
            case 3:
                $start_time = date('Y-m-01 00:00:00');
                break;
        }
        $end_time = date('Y-m-d 23:59:59');

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $merchant_where = array('m.status'=>1,'hotel.state'=>1,'hotel.flag'=>0);
        $test_hotels = C('TEST_HOTEL');
        $merchant_where['hotel.id'] = array('not in',$test_hotels);
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            if($area_id>0){
                $merchant_where['hotel.area_id'] = $area_id;
            }
        }elseif($area_id>0 && $staff_id>0){
            $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            $merchant_where['hotel.area_id'] = $area_id;
        }elseif($area_id==0 && $staff_id>0){
            $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
        }else{
            $is_data = 0;
        }
        $merchant_fields = 'hotel.id as hotel_id,hotel.name as hotel_name';
        $res_hotel_list = $m_merchant->getMerchantInfo($merchant_fields,$merchant_where);
        $hotel_list = array(array('hotel_id'=>0,'hotel_name'=>'全部餐厅','is_check'=>0));
        foreach ($res_hotel_list as $k=>$v){
            $is_check = 0;
            if($v['hotel_id']==$hotel_id){
                $is_check = 1;
            }
            $v['is_check'] = $is_check;
            $hotel_list[]=$v;
        }
        $award_status = array(array('name'=>'奖励状态','status'=>0));
        $all_activity_award_status = C('ACTIVITY_AWARD_STATUS');
        foreach ($all_activity_award_status as $k=>$v){
            $award_status[]=array('name'=>$v,'recycle_status'=>$k);
        }
        $start_date = date('Y-m-d',strtotime($start_time));
        $end_date = date('Y-m-d',strtotime($end_time));
        $res_data = array('start_date'=>$start_date,'end_date'=>$end_date,'hotel_list'=>$hotel_list,'award_status'=>$award_status);
        $this->to_back($res_data);
    }

    public function statdata(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date = $this->params['end_date'];
        $status = intval($this->params['status']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $m_staff->checkStaffpermission($res_staff,$area_id,$staff_id);
        if($type==1001){
            $this->to_back(1001);
        }
        $start_time = "$start_date 00:00:00";
        $end_time = "$end_date 23:59:59";

        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $static_maintainer_id = $static_area_id = 0;
        $is_data = 1;
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            if($area_id>0){
                $static_area_id = $area_id;
            }
        }elseif($area_id>0 && $staff_id>0){
            $static_area_id = $area_id;
            $static_maintainer_id = $res_staff['sysuser_id'];
        }elseif($area_id==0 && $staff_id>0){
            $static_maintainer_id = $res_staff['sysuser_id'];
        }else{
            $is_data = 0;
        }

        $res_data = array();
        if($is_data){
            $fileds = 'sum(a.id) as hotel_num';
            $where = array('a.add_time'=>array(array('egt',$start_time),array('elt',$end_time)));
            if($static_area_id){
                $where['a.area_id'] = $static_area_id;
            }
            if($static_maintainer_id){
                $where['ext.maintainer_id'] = $static_maintainer_id;
            }
            if($hotel_id){
                $where['a.hotel_id'] = $hotel_id;
            }
            if($status){
                $where['a.status'] = $status;
            }
            $m_award_hotel_data = new \Common\Model\Finance\AwardHoteldataModel();
            $res_award_hotel_data = $m_award_hotel_data->getData($fileds,$where);
            $all_hotel_num = intval($res_award_hotel_data[0]['hotel_num']);

            $fileds = 'sum(a.id) as hotel_num,a.status';
            $where['a.integral'] = array('gt',0);
            $res_award_hotel_data = $m_award_hotel_data->getData($fileds,$where,'a.status');
            $hotel_num1 = 0;
            $hotel_num_status1=$hotel_num_status3=0;
            foreach ($res_award_hotel_data as $v){
                $hotel_num1+=$v['hotel_num'];
                if($v['status']==1 || $v['status']==2){
                    $hotel_num_status1+=$v['hotel_num'];
                }else{
                    $hotel_num_status3+=$v['hotel_num'];
                }
            }
            unset($where['a.integral']);
            $where['a.step_integral'] = array('gt',0);
            $res_award_hotel_data = $m_award_hotel_data->getData($fileds,$where,'a.status');
            $hotel_num2 = 0;
            $hotel_num2_status1=$hotel_num2_status3=0;
            foreach ($res_award_hotel_data as $v){
                $hotel_num2+=$v['hotel_num'];
                if($v['status']==1 || $v['status']==2){
                    $hotel_num2_status1+=$v['hotel_num'];
                }else{
                    $hotel_num2_status3+=$v['hotel_num'];
                }
            }
            $res_data = array('all_hotel_num'=>$all_hotel_num,'hotel_num1'=>$hotel_num1,'hotel_num_status1'=>$hotel_num_status1,'hotel_num_status3'=>$hotel_num_status1,
                'hotel_num2'=>$hotel_num1,'hotel_num2_status1'=>$hotel_num2_status1,'hotel_num2_status3'=>$hotel_num2_status1,
            );
        }
        $this->to_back($res_data);
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date = $this->params['end_date'];
        $status = intval($this->params['status']);
        $page = $this->params['page'];
        $pagesize = 10;

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $m_staff->checkStaffpermission($res_staff,$area_id,$staff_id);
        if($type==1001){
            $this->to_back(1001);
        }
        $start_time = "$start_date 00:00:00";
        $end_time = "$end_date 23:59:59";

        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $static_maintainer_id = $static_area_id = 0;
        $is_data = 1;
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            if($area_id>0){
                $static_area_id = $area_id;
            }
        }elseif($area_id>0 && $staff_id>0){
            $static_area_id = $area_id;
            $static_maintainer_id = $res_staff['sysuser_id'];
        }elseif($area_id==0 && $staff_id>0){
            $static_maintainer_id = $res_staff['sysuser_id'];
        }else{
            $is_data = 0;
        }

        $datalist = array();
        if($is_data){
            $where = array('a.add_time'=>array(array('egt',$start_time),array('elt',$end_time)));
            if($static_area_id){
                $where['a.area_id'] = $static_area_id;
            }
            if($static_maintainer_id){
                $where['ext.maintainer_id'] = $static_maintainer_id;
            }
            if($hotel_id){
                $where['a.hotel_id'] = $hotel_id;
            }
            if($status){
                $where['a.status'] = $status;
            }
            $offset = ($page-1)*$pagesize;
            $limit = "$offset,$pagesize";
            $m_award_hotel_data = new \Common\Model\Finance\AwardHoteldataModel();
            $fileds = 'a.hotel_id,hotel.name as hotel_name,a.num,a.integral,a.step_num,a.step_integral,a.jt_policy_id,
            a.overdue_money,a.status,user.nickName,user.avatarUrl';
            $res_award_data = $m_award_hotel_data->getAwardList($fileds,$where,'a.id desc',$limit);
            $all_activity_award_status = C('ACTIVITY_AWARD_STATUS');
            $m_activity_policy = new \Common\Model\Finance\ActivityPolicyModel();
            foreach ($res_award_data as $v){
                $overdue_str = '';
                if($v['overdue_money']>0){
                    $overdue_str = '超期欠款';
                }
                $status_str = $all_activity_award_status[$v['status']];
                $step_no=$step_num=0;
                if($v['jt_policy_id']>0){
                    $res_policy = $m_activity_policy->getInfo(array('id'=>$v['jt_policy_id']));
                    $integral_config = json_decode($res_policy['integral_config'],true);
                    foreach ($integral_config as $ik=>$iv){
                        if($v['step_num']>=$iv['n']){
                            $step_no = $ik+1;
                            $step_num = $iv['n'];
                        }
                    }
                }
                $step_str = '';
                if($step_no){
                    $step_str = '第'.$step_no."阶段(>{$step_num}瓶)";
                }
                $v['overdue_str'] = $overdue_str;
                $v['status_str'] = $status_str;
                $v['step_str'] = $step_str;
                $datalist[]=$v;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }



}