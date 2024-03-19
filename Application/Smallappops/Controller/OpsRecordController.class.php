<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class OpsRecordController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addrecord':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'ops_type'=>1001,'task_source'=>1001,'box_handle_num'=>1001,
                    'images'=>1002,'signin_time'=>1002,'signin_hotel_id'=>1002,'signout_time'=>1002,'signout_hotel_id'=>1002,
                    'review_uid'=>1002,'cc_uids'=>1002,'salerecord_id'=>1002,'content'=>1002,'type'=>1001);
                break;
        }
        parent::_init_();
    }

    public function addrecord(){
        $openid = $this->params['openid'];
        $visit_type = intval($this->params['ops_type']);
        $task_source = intval($this->params['task_source']);
        $box_handle_num = intval($this->params['box_handle_num']);
        $signin_time = $this->params['signin_time'];
        $signin_hotel_id = intval($this->params['signin_hotel_id']);
        $signout_time = $this->params['signout_time'];
        $signout_hotel_id = intval($this->params['signout_hotel_id']);
        $images = $this->params['images'];
        $content = $this->params['content'];
        $review_uid = $this->params['review_uid'];
        $cc_uids = $this->params['cc_uids'];
        $salerecord_id = intval($this->params['salerecord_id']);
        $type = intval($this->params['type']);//类型1保存2提交

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $status = 1;
        if($type==2){
            unset($this->valid_fields['images'],$this->valid_fields['salerecord_id']);
            foreach ($this->valid_fields as $k=>$v){
                if(empty($this->params["$k"])){
                    $this->to_back(1001);
                }
            }
            $status = 2;
        }

        $ops_staff_id = $res_staff['id'];
        $add_data = array('ops_staff_id'=>$ops_staff_id,'visit_type'=>$visit_type,'task_source'=>$task_source,'box_handle_num'=>$box_handle_num,
            'status'=>$status,'type'=>3);
        if(!empty($content)){
            $add_data['content'] = $content;
        }
        if(!empty($images))     $add_data['images'] = $images;
        if(!empty($signin_time) && !empty($signin_hotel_id)){
            $add_data['signin_time'] = $signin_time;
            $add_data['signin_hotel_id'] = $signin_hotel_id;
        }
        if(!empty($signout_time) && !empty($signout_hotel_id)){
            if($signin_hotel_id!=$signout_hotel_id){
                $this->to_back(94005);
            }
            $add_data['signout_time'] = $signout_time;
            $add_data['signout_hotel_id'] = $signout_hotel_id;
        }

        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $m_saleremind = new \Common\Model\Crm\SalerecordRemindModel();
        if($salerecord_id){
            $add_data['update_time'] = date('Y-m-d H:i:s');
            $m_salerecord->updateData(array('id'=>$salerecord_id),$add_data);
            $m_saleremind->delData(array('salerecord_id'=>$salerecord_id,'type'=>array('in','1,2')));
            $add_remind = array();
        }else{
            $salerecord_id = $m_salerecord->add($add_data);
            $add_remind = array(array('salerecord_id'=>$salerecord_id,'type'=>6,'remind_user_id'=>$ops_staff_id));
        }
        if(!empty($review_uid)){
            $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>1,'remind_user_id'=>$review_uid);
        }
        if(!empty($cc_uids)){
            $arr_cc_uids = explode(',',$cc_uids);
            foreach ($arr_cc_uids as $v){
                $remind_user_id = intval($v);
                if($remind_user_id>0){
                    $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>2,'remind_user_id'=>$remind_user_id);
                }
            }
        }
        $m_saleremind->addAll($add_remind);

        $this->to_back(array('salerecord_id'=>$salerecord_id));
    }
}