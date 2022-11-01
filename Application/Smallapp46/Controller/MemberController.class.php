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
                $this->valid_fields = array('openid'=>1001,'mobile'=>1001,'source'=>1001,
                    'activity_id'=>1002,'idcode'=>1002,'hotel_id'=>1002,'room_id'=>1002,'staff_id'=>1002,
                    'box_id'=>1002);
                break;
            case 'scanbottlecode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001);
                break;
            case 'getpopupinfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'staff_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function joinvip(){
        $openid = $this->params['openid'];
        $mobile = $this->params['mobile'];
        $source = $this->params['source'];//来源1销售经理发起抽奖 2扫瓶码 3扫易拉宝二维码 4销售端任务邀请会员 5电视二维码
        $activity_id = $this->params['activity_id'];
        $idcode = $this->params['idcode'];
        $hotel_id = intval($this->params['hotel_id']);
        $room_id = intval($this->params['room_id']);
        $box_id = intval($this->params['box_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $sale_openid = '';
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
            $hotel_id = $res_staff[0]['hotel_id'];
        }elseif($source==2){
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
            $hotel_id = $res_staff[0]['hotel_id'];
        }elseif($source==4){
            $where = array('a.id'=>$staff_id,'a.status'=>1,'merchant.status'=>1);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
            $sale_openid = $res_staff[0]['openid'];
            $hotel_id = $res_staff[0]['hotel_id'];
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
            $data = array('mobile'=>$mobile,'vip_level'=>$now_vip_level,'invite_time'=>date('Y-m-d H:i:s'),'invite_type'=>$source);
            if($source==3 || $source==5){
                $data['hotel_id'] = $hotel_id;
                $data['room_id'] = $room_id;
                $data['box_id'] = $box_id;
            }
            if($source==1 || $source==4){
                $data['invite_openid'] = $sale_openid;
                $m_message = new \Common\Model\Smallapp\MessageModel();
                $m_message->recordMessage($sale_openid,$res_user['id'],9);
                if($source==4){
                    $where = array('a.openid'=>$sale_openid,'a.status'=>1,'task.task_type'=>26,'task.status'=>1,'task.flag'=>1);
                    $where["DATE_FORMAT(a.add_time,'%Y-%m-%d')"] = date('Y-m-d');
                    $m_task_user = new \Common\Model\Integral\TaskuserModel();
                    $fields = "a.id as task_user_id,task.id task_id,task.task_info";
                    $res_utask = $m_task_user->getUserTaskList($fields,$where,'a.id desc');
                    if(!empty($res_utask)){
                        $task_user_id = $res_utask[0]['task_user_id'];
                        $m_task_user->where(array('id'=>$task_user_id))->setInc('people_num',1);
                    }
                }
            }
            $m_user->updateInfo(array('id'=>$res_user['id']),$data);
        }

        $coupon_list = array();
        if($now_vip_level>0){
            $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
            $coupon_list = $m_user_coupon->addVipCoupon($now_vip_level,$hotel_id,$openid);
        }

        $resp_data = array('vip_level'=>$res_user['vip_level'].'-'.$now_vip_level,'coupon_list'=>$coupon_list);
        $this->to_back($resp_data);
    }


    public function scanbottlecode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];

        $where = array('openid'=>$openid);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $record_info = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
        if($record_info[0]['type']<5){
            $this->to_back(93096);
        }

        $res = array('idcode'=>$idcode);
        $this->to_back($res);
    }

    public function getpopupinfo(){
        $openid = $this->params['openid'];
        $staff_id = $this->params['staff_id'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid,vip_level,mobile,is_wx_auth', $where, '');

        $vip_level = 0;
        $is_wx_auth = 0;
        $mobile = '';
        if(!empty($user_info)){
            $vip_level = $user_info['vip_level'];
            $mobile = $user_info['mobile'];
            $is_wx_auth = $user_info['is_wx_auth'];
        }
        $coupon_money = 0;
        $coupon_end_time = '';
        $coupon_unnum = 0;
        if($vip_level==0){
            $m_sys_config = new \Common\Model\SysConfigModel();
            $sys_info = $m_sys_config->getAllconfig();
            $vip_coupons = json_decode($sys_info['vip_coupons'],true);
            $now_vip_level = 1;
            if(!empty($vip_coupons) && !empty($vip_coupons[$now_vip_level])){
                $m_coupon = new \Common\Model\Smallapp\CouponModel();
                $res_all_coupon = $m_coupon->getALLDataList('*',array('id'=>array('in',$vip_coupons[$now_vip_level])),'end_time desc','','');
                $end_time = date('Y年m月d日',strtotime($res_all_coupon[0]['end_time']));
                $coupon_end_time = $end_time.'到期';
                foreach ($res_all_coupon as $v){
                    $coupon_money+=$v['money'];
                }
            }
        }else{
            $where = array('a.openid'=>$openid,'a.ustatus'=>1,'a.status'=>1,'coupon.type'=>2);
            $fields = 'count(a.id) as num';
            $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
            $res_coupon = $m_coupon_user->getUsercouponDatas($fields,$where,'a.id desc','');
            if(!empty($res_coupon)){
                $coupon_unnum = intval($res_coupon[0]['num']);
            }
        }
        $params = array('openid'=>$openid,'vip_level'=>$vip_level,'coupon_money'=>$coupon_money,'coupon_end_time'=>$coupon_end_time,
            'coupon_unnum'=>$coupon_unnum,'mobile'=>$mobile,'is_wx_auth'=>$is_wx_auth,
            'hotel_id'=>0,'room_id'=>0,'staff_id'=>intval($staff_id),'code_msg'=>'','source'=>4);
        $res_data = array('params'=>json_encode($params));
        $this->to_back($res_data);
    }
}