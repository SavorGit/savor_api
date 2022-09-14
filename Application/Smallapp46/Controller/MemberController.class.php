<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class MemberController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'joinvip':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'mobile'=>1001,'source'=>1001,'activity_id'=>1002,'idcode'=>1002);
                break;
        }
        parent::_init_();
    }

    public function joinvip(){
        $openid = $this->params['openid'];
        $mobile = $this->params['mobile'];
        $source = $this->params['source'];//来源1销售经理发起抽奖 2扫瓶码
        $activity_id = $this->params['activity_id'];
        $idcode = $this->params['idcode'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        if($source==1){
            if(empty($activity_id)){
                $this->to_back(1001);
            }
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
            $sale_openid = $res_activity['openid'];
            $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
        }else{
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $record_info = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
            if($record_info[0]['type']<5){
                $this->to_back(93096);
            }
            $sale_openid = $record_info[0]['op_openid'];
            $where = array('a.openid'=>$sale_openid,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
        }
        $where = array('openid'=>$openid);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile,vip_level,buy_wine_num', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $now_vip_level = 0;
        if($res_user['vip_level']==0){
            $now_vip_level = 1;
            $data = array('mobile'=>$mobile,'vip_level'=>$now_vip_level,'invite_openid'=>$sale_openid,'invite_time'=>date('Y-m-d H:i:s'));
            $m_user->updateInfo(array('id'=>$res_user['id']),$data);

            $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegral->finishInviteVipTask($sale_openid);
            $m_message = new \Common\Model\Smallapp\MessageModel();
            $m_message->recordMessage($sale_openid,$res_user['id'],9);
        }

        $coupon_list = array();
        if($now_vip_level>0){
            $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
            $coupon_list = $m_user_coupon->addVipCoupon($now_vip_level,$res_staff[0]['hotel_id'],$openid);
        }

        $resp_data = array('vip_level'=>$res_user['vip_level'].'-'.$now_vip_level,'coupon_list'=>$coupon_list);
        $this->to_back($resp_data);
    }
}