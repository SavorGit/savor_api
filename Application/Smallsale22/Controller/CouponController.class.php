<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;
class CouponController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'qrcontent'=>1001);
                break;
            case 'scanGoodscode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001);
                break;
            case 'getscanGoods':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'writeoffcoupon':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'qrcontent'=>1001,'idcode'=>1001);
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
        $param_coupon = decrypt_data($qrcontent);
        if(!is_array($param_coupon) || $param_coupon['type']!='coupon'){
            $this->to_back(93203);
        }

        $coupon_user_id = intval($param_coupon['id']);
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $res_usercoupon = $m_user_coupon->getInfo(array('id'=>$coupon_user_id));
        if($res_usercoupon['ustatus']!=1){
            $this->to_back(93204);
        }
        if($res_usercoupon['hotel_id']>0){
            if($res_usercoupon['hotel_id']!=$hotel_id){
                $this->to_back(93205);
            }
        }else{
            $m_couponhotel = new \Common\Model\Smallapp\CouponHotelModel();
            $res_hotel = $m_couponhotel->getALLDataList('*',array('coupon_id'=>$res_usercoupon['coupon_id'],'hotel_id'=>$hotel_id),'id desc','0,1','');
            if(empty($res_hotel)){
                $this->to_back(93205);
            }
        }
        $now_time = date('Y-m-d H:i:s');
        if($now_time>=$res_usercoupon['start_time'] && $now_time<=$res_usercoupon['end_time']){
            $m_coupon = new \Common\Model\Smallapp\CouponModel();
            $res_coupon = $m_coupon->getInfo(array('id'=>$res_usercoupon['coupon_id']));
            $data = array('name'=>$res_coupon['name'],'qrcode'=>$qrcontent,'add_time'=>date('Y-m-d H:i:s'));
            $this->to_back($data);
        }else{
            $this->to_back(93205);
        }
    }

    public function scanGoodscode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $res_coupon = $m_user_coupon->getInfo(array('idcode'=>$idcode));
        if(!empty($res_coupon)){
            $this->to_back(93213);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
        spec.name as spec_name,unit.name as unit_name,a.type,a.status,a.add_time';
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $res_records = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        if($res_records[0]['type']==5){
            $res = array('idcode'=>$idcode,'add_time'=>date('Y-m-d H:i:s'),'goods_id'=>$res_records[0]['goods_id'],
                'goods_name'=>$res_records[0]['goods_name']);
            $this->to_back($res);
        }else{
            if($res_records[0]['type']==7){
                if(in_array($res_records[0]['wo_status'],array(1,3,4))){
                    $this->to_back(93098);
                }else{
                    $this->to_back(93094);
                }
            }elseif($res_records[0]['type']==6){
                $this->to_back(93095);
            }else{
                $this->to_back(93096);
            }
        }
    }

    public function getscanGoods(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,a.add_time';
        $where = array('a.op_openid'=>$openid,'a.type'=>7,'a.wo_status'=>4,'a.dstatus'=>1);
        $res_records = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $datalist = array();
        if(!empty($res_records)){
            $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
            foreach ($res_records as $v){
                $res_coupon = $m_user_coupon->getInfo(array('idcode'=>$v['idcode']));
                if(empty($res_coupon)){
                    $datalist[]=$v;
                }
            }
        }
        $this->to_back($datalist);
    }

    public function writeoffcoupon(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $qrcontent = $this->params['qrcontent'];
        $idcode = $this->params['idcode'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1,'merchant.hotel_id'=>$hotel_id);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.type,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $param_coupon = decrypt_data($qrcontent);
        if(!is_array($param_coupon) || $param_coupon['type']!='coupon'){
            $this->to_back(93203);
        }

        $coupon_user_id = intval($param_coupon['id']);
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $res_usercoupon = $m_user_coupon->getInfo(array('id'=>$coupon_user_id));
        if($res_usercoupon['ustatus']!=1){
            $this->to_back(93204);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$res_usercoupon['openid'],'status'=>1);
        $user_info = $m_user->getOne('id,openid,unionId,mobile',$where,'');
        if(!empty($user_info['unionId'])){
            $where = array('unionId'=>$user_info['unionId'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                $this->to_back(93219);
            }
        }elseif(!empty($user_info['mobile'])){
            $where = array('mobile'=>$user_info['mobile'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                $this->to_back(93219);
            }
        }
        if($res_usercoupon['hotel_id']>0){
            if($res_usercoupon['hotel_id']!=$hotel_id){
                $this->to_back(93205);
            }
        }else{
            $m_couponhotel = new \Common\Model\Smallapp\CouponHotelModel();
            $res_hotel = $m_couponhotel->getALLDataList('*',array('coupon_id'=>$res_usercoupon['coupon_id'],'hotel_id'=>$hotel_id),'id desc','0,1','');
            if(empty($res_hotel)){
                $this->to_back(93205);
            }
        }
        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $res_bindcoupon = $m_user_coupon->getInfo(array('idcode'=>$idcode));
        if(!empty($res_bindcoupon)){
            $this->to_back(93213);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $res_stock_records = $m_stock_record->getALLDataList('*',array('idcode'=>$idcode,'dstatus'=>1),'id desc','0,1','');
        $m_coupon = new \Common\Model\Smallapp\CouponModel();
        $res_couponinfo = $m_coupon->getInfo(array('id'=>$res_usercoupon['coupon_id']));
        if($res_couponinfo['use_range']==2){
            $range_finance_goods_ids = explode(',',trim($res_couponinfo['range_finance_goods_ids'],','));
            if(!in_array($res_stock_records[0]['goods_id'],$range_finance_goods_ids)){
                $this->to_back(93214);
            }
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('id,mobile,vip_level,buy_wine_num,invite_openid,invite_time,invite_gold_openid', array('openid'=>$res_usercoupon['openid']));
        $now_vip_level = 0;
        $buy_wine_num = $res_user['buy_wine_num']+1;
        if($buy_wine_num==1){
            $m_taskhotel = new \Common\Model\Integral\TaskHotelModel();
            $where = array('a.hotel_id'=>$hotel_id,'task.task_type'=>26,'task.status'=>1,'task.flag'=>1);
            $res_hoteltask = $m_taskhotel->getHotelTasks('a.*',$where);
            if(!empty($res_hoteltask)){
                $where = array('a.openid'=>$openid,'a.status'=>1,'task.task_type'=>26,'task.status'=>1,'task.flag'=>1);
                $where["DATE_FORMAT(a.add_time,'%Y-%m-%d')"] = date('Y-m-d');
                $m_task_user = new \Common\Model\Integral\TaskuserModel();
                $fields = "a.id as task_user_id,task.id task_id,task.task_info";
                $res_utask = $m_task_user->getUserTaskList($fields,$where,'a.id desc');
                if(empty($res_utask)){
                    $resp_data = array('incode'=>50,'message'=>'请领取发放优惠券任务，否则无法获得优惠券积分奖励');
                    $this->to_back($resp_data);
                }
            }
        }

        $now_time = date('Y-m-d H:i:s');
        if($now_time>=$res_usercoupon['start_time'] && $now_time<=$res_usercoupon['end_time']){
            $smallapp_config = C('SMALLAPP_CONFIG');
            $pay_wx_config = C('PAY_WEIXIN_CONFIG_1594752111');
            $sslcert_path = APP_PATH.'Payment/Model/wxpay_lib/cert/1594752111_apiclient_cert.pem';
            $sslkey_path = APP_PATH.'Payment/Model/wxpay_lib/cert/1594752111_apiclient_key.pem';
            $payconfig = array(
                'appid'=>$smallapp_config['appid'],
                'partner'=>$pay_wx_config['partner'],
                'key'=>$pay_wx_config['key'],
                'sslcert_path'=>$sslcert_path,
                'sslkey_path'=>$sslkey_path,
            );
            $total_fee = $res_usercoupon['money'];
            $m_order = new \Common\Model\Smallapp\ExchangeModel();
            $add_data = array('openid'=>$res_usercoupon['openid'],'goods_id'=>0,'coupon_user_id'=>$coupon_user_id,'price'=>0,'type'=>5,
                'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
            $order_id = $m_order->add($add_data);

            $trade_info = array('trade_no'=>$order_id,'money'=>$total_fee,'open_id'=>$res_usercoupon['openid']);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $m_order->updateData(array('id'=>$order_id),array('status'=>21));
                $up_data = array('ustatus'=>2,'use_time'=>date('Y-m-d H:i:s'),'op_openid'=>$openid);
                if(!empty($idcode)){
                    $up_data['idcode'] = $idcode;
                }
                $m_user_coupon->updateData(array('id'=>$coupon_user_id),$up_data);

                $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
                if($res_user['vip_level']==0){
                    $sale_openid = $openid;
                    $now_vip_level = 2;
                    $data = array('vip_level'=>$now_vip_level,'buy_wine_num'=>$buy_wine_num,'invite_gold_openid'=>$sale_openid,'invite_gold_time'=>date('Y-m-d H:i:s'));
                    $m_user->updateInfo(array('id'=>$res_user['id']),$data);

                    $m_userintegral->finishInviteVipTask($sale_openid,$idcode,2);
                    $m_message = new \Common\Model\Smallapp\MessageModel();
                    $m_message->recordMessage($sale_openid,$res_user['id'],9);
                }else{
                    $data = array('buy_wine_num'=>$buy_wine_num);
                    if(!empty($res_user['invite_gold_openid'])){
                        $sale_openid = $res_user['invite_gold_openid'];
                    }else{
                        $sale_openid = $openid;
                    }
                    $level_buy_num = C('VIP_3_BUY_WINDE_NUM');
                    if($buy_wine_num==1){
                        $now_vip_level = 2;
                        $data['invite_gold_openid'] = $sale_openid;
                        $data['invite_gold_time'] = date('Y-m-d H:i:s');
                        $data['vip_level'] = $now_vip_level;
                        $m_userintegral->finishInviteVipTask($sale_openid,$idcode,2);
                    }elseif($buy_wine_num==$level_buy_num){
                        $now_vip_level = 3;
                        $data['vip_level'] = $now_vip_level;
                    }
                    $all_day = 180*86400;
                    $reward_end_time = strtotime($res_user['invite_time']) + $all_day;
                    $now_retime = time();
                    if($buy_wine_num>1 && $now_retime<$reward_end_time){
                        $m_userintegral->finishBuyRewardsalerTask($sale_openid,$idcode,1);
                    }
                    $m_user->updateInfo(array('id'=>$res_user['id']),$data);
                }
                if($now_vip_level>0){
                    $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
                    $coupon_list = $m_user_coupon->addVipCoupon($now_vip_level,$res_staff[0]['hotel_id'],$res_usercoupon['openid']);
                    if(!empty($coupon_list)){
                        $cache_key = C('SAPP_VIP_LEVEL_COUPON').$res_usercoupon['openid'].':'.$coupon_user_id;
                        $redis = new \Common\Lib\SavorRedis();
                        $redis->select(1);
                        $cache_data = array('vip_level'=>$now_vip_level,'coupon_list'=>$coupon_list);
                        $redis->set($cache_key,json_encode($cache_data),3600);
                    }
                }
                if($res_stock_records[0]['type']==5){
                    $batch_no = date('YmdHis');
                    $add_data = $res_stock_records[0];
                    unset($add_data['id'],$add_data['update_time']);
                    $add_data['price'] = -abs($add_data['price']);
                    $add_data['total_fee'] = -abs($add_data['total_fee']);
                    $add_data['amount'] = -abs($add_data['amount']);
                    $add_data['total_amount'] = -abs($add_data['total_amount']);
                    $add_data['type'] = 7;
                    $add_data['op_openid'] = $openid;
                    $add_data['batch_no'] = $batch_no;
                    $add_data['wo_reason_type'] = 0;
                    $add_data['wo_data_imgs'] = '';
                    $add_data['wo_status'] = 4;
                    $add_data['wo_num'] = 1;
                    $add_data['update_time'] = date('Y-m-d H:i:s');
                    $add_data['add_time'] = date('Y-m-d H:i:s');
                    $m_stock_record->add($add_data);
                }
            }else{
                if($res['code']==10003){
                    //发送短信
                    $ucconfig = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $params = array('merchant_no'=>1594752111);
                    $template_code = $ucconfig['wx_money_not_enough_templateid'];

                    $phones = C('WEIXIN_MONEY_NOTICE');
                    $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                    foreach ($phones as $vp){
                        $res_sms = $alisms::sendSms($vp,$params,$template_code);
                        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                            'url'=>join(',',$params),'tel'=>$vp,'resp_code'=>$res_sms->Code,'msg_type'=>3
                        );
                        $m_account_sms_log->addData($data);
                    }
                }
            }
            $resp_data = array('incode'=>100,'message'=>'成功使用优惠券');
            $this->to_back($resp_data);
        }else{
            $this->to_back(93205);
        }
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
        $m_user_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $where = array('op_openid'=>$openid,'ustatus'=>2);
        $res_records = $m_user_coupon->getDataList('*',$where,'id desc',$offset,$pagesize);
        $data_list = array();
        if($res_records['total']>0){
            $m_hotel = new \Common\Model\HotelModel();
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            foreach ($res_records['list'] as $v){
                $res_hotel = $m_hotel->getInfoById($v['hotel_id'],'name');
                $expire_time = date('Y.m.d H:i',strtotime($v['end_time']));
                if($v['min_price']>0){
                    $min_price = "满{$v['min_price']}可用";
                }else{
                    $min_price = '无门槛立减券';
                }
                if($v['use_range']==1){
                    $range_str = '全部活动酒水';
                }else{
                    $range_str = '部分活动酒水';
                }
                $res_activity = $m_activity->getInfo(array('id'=>$v['activity_id']));
                $type_str = '幸运抽奖';
                if($res_activity['type']==14){
                    $type_str = '售酒抽奖';
                }
                $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
                spec.name as spec_name,unit.name as unit_name,a.add_time';
                $where = array('a.idcode'=>$res_activity['idcode'],'a.dstatus'=>1);
                $res_srecord = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
                $info = array();
                if(!empty($res_srecord)){
                    $info = $res_srecord[0];
                }
                $info['money'] = $v['money'];
                $info['min_price'] = $min_price;
                $info['expire_time'] = "有效期至{$expire_time}";
                $info['hotel_name'] = $res_hotel['name'];
                $info['range_str'] = $range_str;
                $info['type_str'] = $type_str;
                $info['status_str'] = '已核销';
                $data_list[]=$info;
            }
        }
        $this->to_back($data_list);
    }
}