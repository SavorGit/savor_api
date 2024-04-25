<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class ApprovalHandleController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'process10':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'approval_id'=>1001,'processes_id'=>1001,'status'=>1001,'work_staff_id'=>1002,'receipt_img'=>1002);
                break;
            case 'process11':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'approval_id'=>1001,'processes_id'=>1001,'status'=>1001,'work_staff_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function process10(){
        $openid = $this->params['openid'];
        $approval_id = intval($this->params['approval_id']);
        $processes_id = intval($this->params['processes_id']);
        $work_staff_id = intval($this->params['work_staff_id']);
        $status = intval($this->params['status']);
        $receipt_img = $this->params['receipt_img'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $res_approval = $m_approval->getInfo(array('id'=>$approval_id));
        $item_id = $res_approval['item_id'];
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $res_process = $m_approval_process->getInfo(array('id'=>$processes_id,'approval_id'=>$approval_id,'ops_staff_id'=>$ops_staff_id,'is_receive'=>1));
        $message='';
        if(!empty($res_process)){
            $step_order = $res_process['step_order']+1;
            $res_next = $m_approval_process->getInfo(array('approval_id'=>$approval_id,'step_order'=>$step_order));
            $where = array('id'=>$processes_id);
            $message = '处理完毕';
            switch ($status){
                case 1:
                    $m_stock = new \Common\Model\Finance\StockModel();
                    $is_out = $m_stock->checkHotelThreshold($res_approval['hotel_id'],1);
                    if($is_out==0){
                        $this->to_back(94103);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s')));
                    if(!empty($res_next)){
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1));
                    }
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>3));
                    break;
                case 2:
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>2));
                    break;
                case 3:
                    if($res_process['step_order']==2){
                        $m_stock = new \Common\Model\Finance\StockModel();
                        $is_out = $m_stock->checkHotelThreshold($res_approval['hotel_id'],1);
                        if($is_out==0){
                            $this->to_back(94103);
                        }
                        $res_approval['now_staff_sysuser_id'] = $res_staff['sysuser_id'];
                        $stock_id = $m_stock->createOut($res_approval);
                        $m_approval->updateData(array('id'=>$approval_id),array('status'=>4,'stock_id'=>$stock_id));
                    }else{
                        $m_approval->updateData(array('id'=>$approval_id),array('status'=>6));
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s')));
                    break;
                case 4:
                    if(empty($work_staff_id)){
                        $this->to_back(1001);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s'),
                        'allot_time'=>date('Y-m-d H:i:s'),'allot_ops_staff_id'=>$work_staff_id
                        ));
                    if(empty($res_next)){
                        $next_process = array('approval_id'=>$approval_id,'step_id'=>0,'step_order'=>$step_order,'area_id'=>$res_staff['area_id'],
                            'is_receive'=>1,'ops_staff_id'=>$work_staff_id);
                        $m_approval_process->add($next_process);
                    }else{
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'ops_staff_id'=>$work_staff_id));
                    }

                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>10));
                    break;
                case 5:
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>8));
                    break;
                case 8:
                    if(empty($receipt_img)){
                        $this->to_back(1001);
                    }
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>9,'receipt_img'=>$receipt_img,'receipt_time'=>date('Y-m-d H:i:s')));
                    break;
                case 10:
                    $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_handle'=>1,'status'=>3,'handle_time'=>date('Y-m-d H:i:s')));
                    break;
            }
        }
        $this->to_back(array('message'=>$message));
    }

    public function process11(){
        $openid = $this->params['openid'];
        $approval_id = intval($this->params['approval_id']);
        $processes_id = intval($this->params['processes_id']);
        $work_staff_id = intval($this->params['work_staff_id']);
        $status = intval($this->params['status']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $res_process = $m_approval_process->getInfo(array('id'=>$processes_id,'approval_id'=>$approval_id,'ops_staff_id'=>$ops_staff_id,'is_receive'=>1));
        $message='';
        if(!empty($res_process)){
            $step_order = $res_process['step_order']+1;
            $res_next = $m_approval_process->getInfo(array('approval_id'=>$approval_id,'step_order'=>$step_order));
            $where = array('id'=>$processes_id);
            $message = '处理完毕';
            switch ($status){
                case 1:
                    if(empty($work_staff_id)){
                        $this->to_back(1001);
                    }
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s'),
                        'allot_time'=>date('Y-m-d H:i:s'),'allot_ops_staff_id'=>$work_staff_id
                        ));
                    if(!empty($res_next)){
                        $m_approval_process->updateData(array('id'=>$res_next['id']),array('is_receive'=>1,'ops_staff_id'=>$work_staff_id));
                    }
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>10));
                    break;
                case 2:
                    $m_approval_process->updateData($where,array('status'=>$status,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>2));
                    break;
                case 11:
                    $m_approval_process->updateData($where,array('status'=>3,'is_handle'=>1,'handle_time'=>date('Y-m-d H:i:s')));
                    $m_approval->updateData(array('id'=>$approval_id),array('status'=>11));
                    break;

            }
        }
        $this->to_back(array('message'=>$message));
    }



}