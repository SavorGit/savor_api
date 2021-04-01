<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class BoxincomeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'claim':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'claim_id'=>1001);
                break;


        }
        parent::_init_();
    }

    public function datalist(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);

        $field_staff = 'a.openid,a.level,merchant.type';
        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_boxincome = new \Common\Model\Smallapp\BoxincomeModel();
        $where = array('hotel_id'=>$hotel_id);
        $res_data = $m_boxincome->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_data)){
            foreach ($res_data as $v){
                $num = $v['meal_num'] + $v['comment_num'] + $v['interact_num'];
                $info = array('id'=>$v['id'],'box_name'=>$v['box_name'],'num'=>$num,'is_claim'=>$v['is_claim']);
                $datalist[]=$info;
            }
        }
        $total = count($datalist);
        $res = array('total'=>$total,'datalist'=>$datalist);
        $this->to_back($res);
    }

    public function claim(){
        $hotel_id = intval($this->params['hotel_id']);
        $claim_id = intval($this->params['claim_id']);
        $openid   = trim($this->params['openid']);

        $field_staff = 'a.openid,a.level,merchant.type';
        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_boxincome = new \Common\Model\Smallapp\BoxincomeModel();
        $res_income = $m_boxincome->getInfo(array('id'=>$claim_id));
        if(empty($res_income) || $res_income['hotel_id']!=$hotel_id || $res_income['is_claim']==1){
            $this->to_back(93064);
        }
        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>1);
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        if($res_usertask['total']==0){
            $this->to_back(93065);
        }
        $m_boxincome->updateData(array('id'=>$claim_id),array('openid'=>$openid,'is_claim'=>1,'claim_time'=>date('Y-m-d H:i:s')));
        $user_task_info = $res_usertask['list'][0];

        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $res_hoteltask = $m_hoteltask->getInfo(array('id'=>$user_task_info['task_hotel_id']));
        $task_num = 0;
        $meal_num = $interact_num = $comment_num = 0;
        if($res_hoteltask['meal_num']>0){
            $meal_num = $res_income['meal_num'];
            $task_num++;
        }
        if($res_hoteltask['interact_num']>0){
            $interact_num = $res_income['interact_num'];
            $task_num++;
        }
        if($res_hoteltask['comment_num']>0){
            $comment_num = $res_income['comment_num'];
            $task_num++;
        }
        $meal_money = $interact_money = $comment_money = 0;
        if($res_hoteltask['meal_num']>0 && $meal_num>0){
            $meal_money = 0.3/$task_num/$res_hoteltask['meal_num'] * $meal_num;
        }
        if($res_hoteltask['interact_num']>0 && $interact_num>0){
            $interact_money = 0.3/$task_num/$res_hoteltask['interact_num'] * $interact_num;
        }
        if($res_hoteltask['comment_num']>0 && $comment_num>0){
            $comment_money = 0.3/$task_num/$res_hoteltask['comment_num'] * $comment_num;
        }
        $get_money = $user_task_info['get_money'] + $meal_money + $interact_money + $comment_money;
        $get_money = sprintf("%.2f",$get_money);
        $up_usertask_data = array('meal_num'=>$user_task_info['meal_num']+$meal_num,
            'interact_num'=>$user_task_info['interact_num']+$interact_num,
            'comment_num'=>$user_task_info['comment_num']+$comment_num,'get_money'=>$get_money
        );
        $m_usertask->updateData(array('id'=>$user_task_info['id']),$up_usertask_data);

        $usertask_record = array('openid'=>$openid,'hotel_id'=>$res_income['hotel_id'],'hotel_name'=>$res_income['hotel_name'],
            'room_id'=>$res_income['room_id'],'room_name'=>$res_income['room_name'],'box_id'=>$res_income['box_id'],
            'box_name'=>$res_income['box_name'],'box_mac'=>$res_income['box_mac'],'task_hotel_id'=>$user_task_info['task_hotel_id'],
            'meal_num'=>$meal_num,'interact_num'=>$interact_num,'comment_num'=>$comment_num,'type'=>2
        );
        $m_usertask_record = new \Common\Model\Smallapp\UserTaskRecordModel();
        $m_usertask_record->add($usertask_record);
        $this->to_back(array());
    }

}