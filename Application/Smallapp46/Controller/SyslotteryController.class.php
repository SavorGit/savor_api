<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class SyslotteryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'enterQueue':
                $this->valid_fields = array('openid'=>1001,'activity_id'=>1002);
                break;
            case 'join':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'activity_id'=>1001,'prize_id'=>1001,'mobile'=>1002);
                break;
            case 'getResult':
                $this->valid_fields = array('openid'=>1001,'activity_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function enterQueue(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,avatarUrl,nickName,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if(empty($res_activity)){
            $this->to_back(90171);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(1);
        $cache_key = C('SAPP_LUCKYLOTTERY_USERQUEUE').$activity_id;
        $res = $redis->lgetrange($cache_key,0,-1);
        if(empty($res)){
            $redis->rpush($cache_key,$openid);
        }else{
            if(!in_array($openid,$res)){
                $redis->rpush($cache_key,$openid);
            }
        }
        $this->to_back(array('activity_id'=>$activity_id));
    }

    public function getResult(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,avatarUrl,nickName,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if(empty($res_activity)){
            $this->to_back(90171);
        }
        $now_time = date('Y-m-d H:i:s');
        if(!in_array($res_activity['type'],array(3,11))){
            $this->to_back(90172);
        }
        $res_data = array('activity_id'=>$activity_id);
        $lottery_status = 0;//1待抽奖 2已中奖 3未中奖 4已中奖未完成 5已中奖已完成待领取
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_apply = $m_activity_apply->getInfo(array('activity_id'=>$activity_id,'openid'=>$openid));
        $oss_host = 'http://'. C('OSS_HOST').'/';
        if(!empty($res_activity_apply)){
            switch ($res_activity_apply['status']){
                case 2:
                case 4:
                case 5:
                    $res_data['avatarUrl'] = $user_info['avatarUrl'];
                    $res_data['nickName'] = $user_info['nickName'];
                    $res_data['message'] = '恭喜您中奖了';
                    $res_data['activity_name'] = $res_activity['name'];
                    $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
                    $res_prize = $m_prize->getInfo(array('id'=>$res_activity_apply['prize_id']));
                    $res_data['prize_name'] = $res_prize['name'];
                    $res_data['prize_image_url'] = $oss_host.$res_prize['image_url'];
                    $task_content = $m_prize->getTaskinfo($res_prize,$res_activity_apply);
                    $res_data['task_content'] = $task_content;
                    $res_data['lottery_time'] = $res_activity_apply['add_time'];
                    $lottery_status = $res_activity_apply['status'];
                    break;
                case 3:
                    $lottery_status = 3;
                    break;
            }
        }else{
            $colors = array('1'=>'#f5c287','2'=>'#ffe6b1','3'=>'#f7896c','4'=>'#f5c287','5'=>'#ffe6b1','6'=>'#f7896c',
                '7'=>'#f5c287','8'=>'#ffe6b1','9'=>'#f7896c');
            $lottery_status=1;
            $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
            $res_prize = $m_prize->getDataList('*',array('activity_id'=>$activity_id),'probability asc');
            $prize_list = array();
            if(!empty($res_prize)){
                $all_probability = array();
                if($res_activity['type']==11){
                    $redis = \Common\Lib\SavorRedis::getInstance();
                    $redis->select(1);
                    $pcache_key = C('SAPP_LUCKYLOTTERY_POSITION').$activity_id;
                    $res_position = $redis->get($pcache_key);
                    $all_position = array();
                    if(!empty($res_position)){
                        $all_position = json_decode($res_position,true);
                    }
                    $is_lottery = 0;
                    $cache_key = C('SAPP_LUCKYLOTTERY_USERQUEUE').$activity_id;
                    $res = $redis->lgetrange($cache_key,0,-1);

                    foreach ($res as $k=>$v){
                        $p_num = $k+1;
                        if(in_array($p_num,$all_position) && $openid==$v){
                            $is_lottery = 1;
                        }
                    }
                    $success_rate=$fail_rate=$success_num=$fail_num = 0;
                    foreach ($res_prize as $v){
                        if($v['type']==3){
                            $fail_rate+=$v['probability'];
                            $fail_num++;
                        }else{
                            $success_rate+=$v['probability'];
                            $success_num++;
                        }
                        $all_probability[$v['id']]=array('probability'=>$v['probability'],'type'=>$v['type']);
                    }
                    if($is_lottery){
                        $amount = $success_num;
                        $rate = $fail_rate;
                    }else{
                        $amount = $fail_num;
                        $rate = $success_rate;
                    }
                    $avg_num = intval($rate/$amount);
                    $last_num = fmod($rate,$amount);
                    $all_nums = array();
                    for($i=1;$i<=$amount;$i++){
                        $all_nums[]=$avg_num;
                    }
                    if($last_num){
                        $all_nums[$amount-1] = $all_nums[$amount-1]+$last_num;
                    }
                    foreach ($all_probability as $k=>$v){
                        if($is_lottery){
                            if($v['type']==3){
                                $all_probability[$k]['probability']=0;
                            }else{
                                $now_avg_num = array_shift($all_nums);
                                $all_probability[$k]['probability'] = $v['probability'] + intval($now_avg_num);
                            }
                        }else{
                            if($v['type']==3){
                                $now_avg_num = array_shift($all_nums);
                                $all_probability[$k]['probability'] = $v['probability'] + intval($now_avg_num);
                            }else{
                                $all_probability[$k]['probability']=0;
                            }
                        }
                    }
                }

                foreach ($res_prize as $k=>$v){
                    $color_index = $k+1;
                    $probability = $v['probability'];
                    if(!empty($all_probability) && $all_probability[$v['id']]){
                        $probability = $all_probability[$v['id']]['probability'];
                    }
                    $info = array('id'=>$v['id'],'name'=>$v['name'],'probability'=>$probability,'type'=>$v['type'],
                        'pic'=>$oss_host.$v['image_url'],'color'=>$colors[$color_index]);
                    $prize_list[]=$info;
                }
            }
            $res_data['activity_name'] = $res_activity['name'];
            $res_data['prize_list'] = $prize_list;
        }
        $res_data['lottery_status'] = $lottery_status;
        $this->to_back($res_data);
    }

    public function join(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $mobile = $this->params['mobile'];
        $activity_id = intval($this->params['activity_id']);
        $prize_id = intval($this->params['prize_id']);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if(empty($res_activity)){
            $this->to_back(90171);
        }
        $now_time = date('Y-m-d H:i:s');
        if(!in_array($res_activity['type'],array(3,11)) || $now_time>$res_activity['end_time']){
            $this->to_back(90172);
        }
        $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
        $res_prize = $m_prize->getInfo(array('id'=>$prize_id));
        if(empty($res_prize) || $res_prize['activity_id']!=$activity_id){
            $this->to_back(90173);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,avatarUrl,mobile,nickName,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        if($res_prize['type']==3){
            $status = 3;
            $lottery_status = 3;
        }else{
            $status = 4;
            $lottery_status = 4;
            if($res_activity['type']==11){
                $all_ptype_status = array('1'=>5,'2'=>2);
                if(isset($all_ptype_status[$res_prize['type']])){
                    $status = $all_ptype_status[$res_prize['type']];
                }
            }
        }
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(isset($forscreen_info['box_id']) && $forscreen_info['box_id']>0){
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $cache_key = 'savor_box_' . $forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info, true);
            $cache_key = 'savor_room_' . $box_info['room_id'];
            $redis_room_info = $redis->get($cache_key);
            $room_info = json_decode($redis_room_info, true);
            $cache_key = 'savor_hotel_' . $room_info['hotel_id'];
            $redis_hotel_info = $redis->get($cache_key);
            $hotel_info = json_decode($redis_hotel_info, true);
            $hotel_id = $room_info['hotel_id'];
            $hotel_name = $hotel_info['name'];
            $room_id = $box_info['room_id'];
            $box_id = $forscreen_info['box_id'];
            $box_name = $box_info['name'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $fields = 'a.id as box_id,a.name as box_name,c.id as room_id,d.id as hotel_id,d.name as hotel_name';
            $rets = $m_box->getBoxInfo($fields, $where);
            $hotel_id = $rets[0]['hotel_id'];
            $hotel_name = $rets[0]['hotel_name'];
            $room_id = $rets[0]['room_id'];
            $box_id = $rets[0]['box_id'];
            $box_name = $rets[0]['box_name'];
        }

        $add_data = array('activity_id'=>$activity_id,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,'room_id'=>$room_id,
            'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,'openid'=>$openid,'prize_id'=>$prize_id,'status'=>$status,
            'interact_num'=>0,'demand_hotplay_num'=>0,'demand_banner_num'=>0);
        if(empty($mobile)){
            $mobile = $user_info['mobile'];
        }
        if(!empty($mobile)){
            $add_data['mobile'] = $mobile;
        }
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_apply = $m_activity_apply->getInfo(array('activity_id'=>$activity_id,'openid'=>$openid));
        if(empty($res_activity_apply)){
            $activityapply_id = $m_activity_apply->add($add_data);
        }else{
            $activityapply_id = $res_activity_apply['id'];
        }

        $res_data = array('activity_id'=>$activity_id,'lottery_status'=>$lottery_status);
        if($lottery_status==4){
            $oss_host = 'http://'. C('OSS_HOST').'/';
            $res_data['avatarUrl'] = $user_info['avatarUrl'];
            $res_data['nickName'] = $user_info['nickName'];
            $res_data['message'] = '恭喜您中奖了';
            $res_data['activity_name'] = $res_activity['name'];
            $res_data['prize_name'] = $res_prize['name'];
            $res_data['prize_image_url'] = $oss_host.$res_prize['image_url'];
            $res_data['lottery_time'] = date('Y-m-d H:i:s');

            if($res_activity['type']==3){
                $task_content = $m_prize->getTaskinfo($res_prize,$add_data);
                $res_data['task_content'] = $task_content;

                $redis = new \Common\Lib\SavorRedis();
                $redis->select(1);
                $cache_key = C('SAPP_LOTTERY_TASK').$openid;
                $task = array('prize_id'=>$prize_id,'interact_num'=>$res_prize['interact_num'],
                    'demand_hotplay_num'=>$res_prize['demand_hotplay_num'],'demand_banner_num'=>$res_prize['demand_banner_num']);

                $cdata = array('activityapply_id'=>$activityapply_id,'task'=>$task,'cache_time'=>date('Y-m-d H:i:s'),
                    'start_time'=>$res_activity['start_time'],'end_time'=>$res_activity['end_time']);
                $redis->set($cache_key,json_encode($cdata),3600*3);
            }
            if($res_activity['type']==11){
                if($res_prize['type']==2){
                    $ucconfig = C('ALIYUN_SMS_CONFIG');
                    $alisms = new \Common\Lib\AliyunSms();
                    $params = array('name'=>$res_prize['name']);
                    $template_code = $ucconfig['send_tastewine_user_templateid'];
                    $res_sms = $alisms::sendSms($mobile,$params,$template_code);
                    $data = array('type'=>13,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                        'url'=>join(',',$params),'tel'=>$mobile,'resp_code'=>$res_sms->Code,'msg_type'=>3
                    );
                    $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                    $m_account_sms_log->addData($data);

                    $where = array('openid'=>$res_activity['openid'],'status'=>1);
                    $staff_user_info = $m_user->getOne('id,openid,mobile', $where, '');
                    $tailnum = substr($mobile,-4);
                    $params = array('room_name'=>$box_name,'tailnum'=>$tailnum,'name'=>$res_prize['name']);
                    $template_code = $ucconfig['send_tastewine_sponsor_templateid'];
                    $res_sms = $alisms::sendSms($staff_user_info['mobile'],$params,$template_code);
                    $data = array('type'=>13,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                        'url'=>join(',',$params),'tel'=>$staff_user_info['mobile'],'resp_code'=>$res_sms->Code,'msg_type'=>3
                    );
                    $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                    $m_account_sms_log->addData($data);

                    $res_data['message'] = '请联系服务员领奖';
                }
                if($res_prize['type']==1 && $res_prize['money']>0){
                    $res_activity_apply = $m_activity_apply->getInfo(array('id'=>$activityapply_id));
                    if($res_activity_apply['status']==5){
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
                        $total_fee = $res_prize['money'];
                        $m_order = new \Common\Model\Smallapp\ExchangeModel();
                        $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>4,
                            'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
                        $order_id = $m_order->add($add_data);

                        $trade_info = array('trade_no'=>$order_id,'money'=>$total_fee,'open_id'=>$openid);
                        $m_wxpay = new \Payment\Model\WxpayModel();
                        $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
                        if($res['code']==10000){
                            $m_order->updateData(array('id'=>$order_id),array('status'=>21));
                            $m_activity_apply->updateData(array('id'=>$res_activity_apply['id']),array('status'=>2));
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
                    }
                }
            }
        }
        $this->to_back($res_data);
    }

    public function getPrizeMoney(){
        $openid = $this->params['openid'];
        $activity_id = intval($this->params['activity_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,avatarUrl,nickName,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if(empty($res_activity)){
            $this->to_back(90171);
        }
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_activity_apply = $m_activity_apply->getInfo(array('activity_id'=>$activity_id,'openid'=>$openid));
        if($res_activity_apply['status']!=5){
            $this->to_back(90174);
        }
        $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
        $res_prize = $m_prize->getInfo(array('id'=>$res_activity_apply['prize_id']));
        if($res_prize['type']==1 && $res_prize['money']>0){
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
            $total_fee = $res_prize['money'];
            $m_order = new \Common\Model\Smallapp\ExchangeModel();
            $add_data = array('openid'=>$openid,'goods_id'=>0,'price'=>0,'type'=>4,
                'amount'=>1,'total_fee'=>$total_fee,'status'=>20);
            $order_id = $m_order->add($add_data);

            $trade_info = array('trade_no'=>$order_id,'money'=>$total_fee,'open_id'=>$openid);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $m_order->updateData(array('id'=>$order_id),array('status'=>21));
                $m_activity_apply->updateData(array('id'=>$res_activity_apply['id']),array('status'=>2));
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
                        $res_data = $alisms::sendSms($vp,$params,$template_code);
                        $data = array('type'=>8,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                            'url'=>join(',',$params),'tel'=>$vp,'resp_code'=>$res_data->Code,'msg_type'=>3
                        );
                        $m_account_sms_log->addData($data);
                    }
                }
            }
        }
        $resp_data = array('activity_apply_id'=>$res_activity_apply['id']);
        $this->to_back($resp_data);
    }


}