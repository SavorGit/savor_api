<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class ActivityPolicyController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'statdata':
                $this->params = array('openid'=>1001);
                $this->is_verify = 1;
            case 'confirm':
                $this->params = array('openid'=>1001,'confirm_month'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function statdata(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,a.level';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $now_month = date('Ym');
        $m_award_hotel_data = new \Common\Model\Finance\AwardHoteldataModel();
        $ahwhere = array('award_openid'=>$openid,'hotel_id'=>$res_staff[0]['hotel_id']);
        $ahfields = 'id,num,integral,step_num,step_integral,jt_policy_id,static_date,is_confirm';
        $res_awdata = $m_award_hotel_data->getALLDataList($ahfields,$ahwhere,'id desc','0,1','');
        $is_show = 0;
        $num = $integral = 0;
        $step_award_process = array('end_step_num'=>0);
        $confirm_data = array('month'=>0);
        if(!empty($res_awdata[0]['id'])){
            $is_show = 1;
            if($res_awdata[0]['static_date']==$now_month){
                $num = $res_awdata[0]['num'];
                $integral = $res_awdata[0]['integral'];
                $step_num = $res_awdata[0]['step_num'];
                $step_integral = $res_awdata[0]['step_integral'];
                if($step_num){
                    $m_activity_policy = new \Common\Model\Finance\ActivityPolicyModel();
                    $res_policy = $m_activity_policy->getInfo(array('id'=>$res_awdata[0]['jt_policy_id']));
                    $integral_config = json_decode($res_policy['integral_config'],true);
                    $next_num = 0;
                    $end_step_num = 0;
                    foreach ($integral_config as $k=>$v){
                        $end_step_num = $v['n'];
                        if($step_num<$v['n']){
                            if($next_num==0){
                                $next_num = $v['n'];
                            }
                        }
                    }
                    $tips = '';
                    if($next_num>0){
                        $next_last_num = $next_num-$step_num;
                        $tips = "当前已开瓶{$step_num}瓶，可收益{$step_integral}积分，再开瓶{$next_last_num}瓶可获得下一个奖励";
                    }
                    $step_award_process = array('process'=>$integral_config,'tips'=>$tips,'now_step_num'=>$step_num,'end_step_num'=>$end_step_num+10);
                }
            }else{
                $is_confirm_data = 0;
                if($res_awdata[0]['is_confirm']==0){
                    $is_confirm_data = 1;
                }else{
                    $ahwhere['static_date'] = array('lt',$now_month);
                    $ahwhere['is_confirm'] = 0;
                    $res_awdata = $m_award_hotel_data->getALLDataList($ahfields,$ahwhere,'id desc','0,1','');
                    if(!empty($res_awdata[0]['id'])){
                        $is_confirm_data = 1;
                    }
                }
                if($is_confirm_data){
                    $month_number = strtotime($res_awdata[0]['static_date'].'01');
                    $month = date('n',strtotime($month_number));
                    $sdate = date('Y-m-01',$month_number);
                    $edate = date('Y-m-t',$month_number);
                    $confirm_data['month'] = $month;
                    $confirm_data['confirm_month'] = date('Ym',$month_number);
                    $confirm_data['sdate'] = $sdate;
                    $confirm_data['edate'] = $edate;
                    $confirm_data['num'] = $res_awdata[0]['num'];
                    $confirm_data['integral'] = $res_awdata[0]['integral'];
                    $confirm_data['step_num'] = $res_awdata[0]['step_num'];
                    $confirm_data['step_integral'] = $res_awdata[0]['step_integral'];
                }
            }
        }
        $res_data = array('is_show'=>$is_show,'num'=>$num,'integral'=>$integral,'step_award_process'=>$step_award_process,'confirm_data'=>$confirm_data);
        $this->to_back($res_data);
    }

    public function confirm(){
        $openid = $this->params['openid'];
        $confirm_month = intval($this->params['confirm_month']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,a.level';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_award_hotel_data = new \Common\Model\Finance\AwardHoteldataModel();
        $ahwhere = array('award_openid'=>$openid,'hotel_id'=>$res_staff[0]['hotel_id'],'static_date'=>$confirm_month,'is_confirm'=>0);
        $res_award = $m_award_hotel_data->getInfo($ahwhere);
        $award_hoteldata_id = intval($res_award['id']);
        if(!empty($res_award)){
            if($res_award['overdue_money']>0){
                $integral_status = 2;
            }else{
                $integral_status = 1;
            }
            $m_award_hotel_data->updateData(array('id'=>$award_hoteldata_id),array('status'=>$integral_status,'is_confirm'=>1,'confirm_time'=>date('Y-m-d H:i:s')));

            $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegral_record->confirmActivityAward($res_award,$integral_status);
        }
        $this->to_back(array('award_hoteldata_id'=>$award_hoteldata_id));
    }

}
