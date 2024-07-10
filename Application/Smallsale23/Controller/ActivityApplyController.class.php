<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;
class ActivityApplyController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'qrcontent'=>1001);
                break;
            case 'writeoff':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'qrcontent'=>1001);
                break;
            case 'getWriteoffList':
                $this->params = array('openid'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $qrcontent = $this->params['qrcontent'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $param_goods = decrypt_data($qrcontent);
        if(!is_array($param_goods) || $param_goods['type']!='goods'){
            $this->to_back(93203);
        }

        $activity_apply_id = intval($param_goods['id']);
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_apply = $m_activity_apply->getInfo(array('id'=>$activity_apply_id));
        if($res_apply['status']!=2 || $res_apply['hotel_id']!=$hotel_id){
            $this->to_back(93211);
        }
        $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
        $res_prize = $m_prize->getInfo(array('id'=>$res_apply['prize_id']));

        $data = array('name'=>$res_prize['name'],'qrcode'=>$qrcontent,'add_time'=>date('Y-m-d H:i:s'));
        $this->to_back($data);
    }

    public function writeoff(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $qrcontent = $this->params['qrcontent'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $param_goods = decrypt_data($qrcontent);
        if(!is_array($param_goods) || $param_goods['type']!='goods'){
            $this->to_back(93203);
        }

        $activity_apply_id = intval($param_goods['id']);
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_apply = $m_activity_apply->getInfo(array('id'=>$activity_apply_id));
        if($res_apply['status']!=2 || $res_apply['hotel_id']!=$hotel_id){
            $this->to_back(93211);
        }
        $up_data = array('status'=>6,'wo_time'=>date('Y-m-d H:i:s'),'op_openid'=>$openid);
        $m_activity_apply->updateData(array('id'=>$activity_apply_id),$up_data);

        $this->to_back(array('message'=>'核销成功'));
    }

    public function getWriteoffList(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $offset = ($page-1)*$pagesize;
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $where = array('op_openid'=>$openid,'status'=>6);
        $res_records = $m_activity_apply->getDataList('*',$where,'id desc',$offset,$pagesize);
        $data_list = array();
        if($res_records['total']>0){
            $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            foreach ($res_records['list'] as $v){
                $res_activity = $m_activity->getInfo(array('id'=>$v['activity_id']));
                $type_str = '幸运抽奖';
                if($res_activity['type']==14){
                    $type_str = '售酒抽奖';
                }
                $res_prize = $m_prize->getInfo(array('id'=>$v['prize_id']));
                $oss_host = 'http://'. C('OSS_HOST').'/';
                $img_url = '';
                if(!empty($res_prize['img_url'])){
                    $img_url = $oss_host.$res_prize['img_url'];
                }
                $info = array('name'=>$res_prize['name'],'img_url'=>$img_url,
                    'lottery_time'=>$v['add_time'],'type_str'=>$type_str,'status_str'=>'已核销');
                $data_list[]=$info;
            }
        }
        $this->to_back($data_list);
    }
}