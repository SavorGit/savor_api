<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class LotterypoolController extends CommonController{
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
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1002,'activity_id'=>1001,'prize_id'=>1001,
                    'mobile'=>1002,'room_id'=>1002,'hotel_id'=>1002);
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
        if(!in_array($res_activity['type'],array(3,11,12,13))){
            $this->to_back(90172);
        }
        $res_data = array('activity_id'=>$activity_id);
        $lottery_status = 0;//1待抽奖 2已中奖 3未中奖 4已中奖未完成 5已中奖已完成待领取
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $awhere = array('activity_id'=>$activity_id,'openid'=>$openid);
        if($res_activity['type']==13){
            $awhere['DATE(add_time)'] = date('Y-m-d');
        }
        $res_activity_apply = $m_activity_apply->getInfo($awhere);
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
            $fileds = 'a.id,a.name,a.probability,a.prizepool_prize_id,a.image_url,prize.amount,prize.send_amount,prize.type';
            $where = array('a.activity_id'=>$activity_id);
            $res_prize = $m_prize->getActivityPoolprize($fileds,$where,'a.probability asc');
            $prize_list = array();
            if(!empty($res_prize)){
                $redis = \Common\Lib\SavorRedis::getInstance();
                $redis->select(1);
                $cache_prize_user = C('SAPP_LUCKYLOTTERY_PRIZEUSER').$activity_id;
                $res_allprize = $redis->get($cache_prize_user);
                $user_prize = array();
                if(!empty($res_allprize)){
                    $user_prize = json_decode($res_allprize,true);
                }
                $lucky_info = array();
                if(isset($user_prize[$openid])){
                    $lucky_info = $user_prize[$openid];
                }else{
                    $lottery_num = $res_activity['people_num'];
                    $cache_key = C('SAPP_LUCKYLOTTERY_USERQUEUE').$activity_id;
                    for ($i=0;$i<100;$i++) {
                        $now_openid = $redis->lpop($cache_key);
                        if(empty($now_openid)){
                           break;
                        }
                        $user_lucky_info = $this->luckdraw($now_openid,$activity_id,$lottery_num,$res_prize);
                        if($openid==$now_openid){
                            $lucky_info = $user_lucky_info;
                        }
                    }
                }
                foreach ($res_prize as $k=>$v){
                    $color_index = $k+1;
                    $probability = 0;
                    if(!empty($lucky_info)){
                        if($v['prizepool_prize_id']==$lucky_info['lucky_id']){
                            $probability = 100;
                        }
                    }else{
                        if($v['type']==3){
                            $probability = 100;
                        }
                    }
                    $info = array('id'=>$v['id'],'name'=>$v['name'],'probability'=>$probability,'type'=>$v['type'],
                        'pic'=>$oss_host.$v['image_url'],'color'=>$colors[$color_index]);
                    $prize_list[]=$info;
                }
                $tmp_all_probability = 0;
                foreach ($prize_list as $k=>$v){
                    $tmp_all_probability+=$v['probability'];
                }
                if($tmp_all_probability==0){
                    foreach ($prize_list as $k=>$v){
                        if($v['type']==3){
                            $prize_list[$k]['probability'] = 100;
                        }
                    }
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
        $room_id = intval($this->params['room_id']);
        $hotel_id = intval($this->params['hotel_id']);

        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        if(empty($res_activity)){
            $this->to_back(90171);
        }
        $now_time = date('Y-m-d H:i:s');
        if(!in_array($res_activity['type'],array(3,11,12,13)) || $now_time>$res_activity['end_time']){
            $this->to_back(90172);
        }
        $m_prize = new \Common\Model\Smallapp\ActivityprizeModel();
        $res_prize = $m_prize->getInfo(array('id'=>$prize_id));

        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $awhere = array('activity_id'=>$activity_id,'openid'=>$openid);
        if($res_activity['type']==13){
            $awhere['DATE(add_time)'] = date('Y-m-d');
        }
        $res_activity_apply = $m_activity_apply->getInfo($awhere);

        if(empty($res_prize) || $res_prize['activity_id']!=$activity_id || !empty($res_activity_apply)){
            $this->to_back(90173);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,avatarUrl,mobile,nickName,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $key_winuser = C('SAPP_LUCKYLOTTERY_WINUSER').$activity_id;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(1);
        $res_cache = $redis->get($key_winuser);
        $now_lottery_num = 0;
        if(!empty($res_cache)){
            $win_users = json_decode($res_cache,true);
            $now_lottery_num = count($win_users);
        }
        $key_pool = C('SAPP_PRIZEPOOL');
        $res_pool = $redis->get($key_pool.$res_prize['prizepool_prize_id']);
        $send_amount = 0;
        if(!empty($res_pool)){
            $send_pool = json_decode($res_pool,true);
            $send_amount = count($send_pool);
        }
        $m_prizepool_prize = new \Common\Model\Smallapp\PrizepoolprizeModel();
        $res_prizepool = $m_prizepool_prize->getInfo(array('id'=>$res_prize['prizepool_prize_id']));
        if($now_lottery_num>$res_activity['people_num'] || $send_amount>$res_prizepool['amount'] || $res_prizepool['send_amount']>$res_prizepool['amount']){
            $res_prize = $m_prize->getDataList('*',array('activity_id'=>$activity_id,'type'=>3),'id desc');
            $res_prize = $res_prize[0];
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
        if(!empty($box_mac)){
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
        }else{
            $box_id = 0;
            $box_name = '';
            if($room_id>C('QRCODE_MIN_NUM')){
                $room_id = 0;
            }
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('id,name',$hotel_id);
            $hotel_id = $res_hotel['id'];
            $hotel_name = $res_hotel['name'];
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
        $activityapply_id = $m_activity_apply->add($add_data);
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(1);
        if(!empty($res_prize['prizepool_prize_id'])){
            $prizepool_prize_id = $res_prize['prizepool_prize_id'];
            $key_pool = C('SAPP_PRIZEPOOL');
            $lucky_pool_key = $key_pool.$prizepool_prize_id;
            $res_pool = $redis->get($lucky_pool_key);
            $pools = array();
            if(!empty($res_pool)){
                $pools = json_decode($res_pool,true);
            }
            $pools[$openid.$activity_id]=2;
            $redis->set($lucky_pool_key,json_encode($pools));
            $m_prizepool_prize = new \Common\Model\Smallapp\PrizepoolprizeModel();
            $m_prizepool_prize->where(array('id'=>$prizepool_prize_id))->setInc('send_amount',1);
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
            if(in_array($res_activity['type'],array(11,12,13))){
                if(in_array($res_prize['type'],array(1,2))){
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
                    if(!empty($res_prize['prizepool_prize_id'])) {
                        $money_queue = C('SAPP_PRIZEPOOL_MONEYQUEUE').$res_prize['prizepool_prize_id'];
                        $money = $redis->lpop($money_queue);
                        if(!empty($money)){
                            $res_prize['money'] = $money;
                        }
                    }else{
                        $res_prize['money'] = 0;
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

                    if($res_activity['type']==11){
                        $head_pic = '';
                        if(!empty($user_info['avatarUrl'])){
                            $head_pic = base64_encode($user_info['avatarUrl']);
                        }
                        $barrage = "恭喜{$box_name}包间的客人抽中了{$res_prize['name']}";
                        $user_barrage = array('nickName'=>$user_info['nickName'],'headPic'=>$head_pic,'avatarUrl'=>$user_info['avatarUrl'],'barrage'=>$barrage);

                        $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
                        $m_syslottery->send_common_lottery($hotel_id,$box_mac,$activity_id,$user_barrage);
                    }
                }
            }
        }
        $this->to_back($res_data);
    }

    private function luckdraw($openid,$activity_id,$lottery_num,$res_prize){
        $key_winuser = C('SAPP_LUCKYLOTTERY_WINUSER').$activity_id;
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(1);
        $res_cache = $redis->get($key_winuser);
        $now_lottery_num = 0;
        if(!empty($res_cache)){
            $win_users = json_decode($res_cache,true);
            $now_lottery_num = count($win_users);
        }else{
            $win_users = array();
        }

        $normal_probability = $normal_pnum = 0;
        $normal_prize = array();
        $prizes = array();
        $key_pool = C('SAPP_PRIZEPOOL');
        $not_win_lucky_id = 0;
        foreach ($res_prize as $pk=>$pv){
            $res_pool = $redis->get($key_pool.$pv['prizepool_prize_id']);
            $send_amount = 0;
            if(!empty($res_pool)){
                $send_pool = json_decode($res_pool,true);
                $send_amount = count($send_pool);
            }
            if($pv['probability']>0 && $pv['amount']-$send_amount>0){
                $normal_probability+=$pv['probability'];
                $normal_pnum++;
                $normal_prize[] = $pv;
            }
            $prizes[$pv['prizepool_prize_id']] = $pv;
            if($pv['type']==3){
                $not_win_lucky_id = $pv['prizepool_prize_id'];
            }
        }
        if($now_lottery_num<$lottery_num){
            $now_probability = 0;
            foreach ($normal_prize as $nk=>$nv){
                $probability = sprintf("%.2f",$nv['probability']/$normal_probability)*100;
                $normal_prize[$nk]['probability'] = $probability;
                $now_probability = $now_probability+$probability;
            }
            $surplus_num = 100-$now_probability;
            $normal_prize[$normal_pnum-1]['probability'] = $normal_prize[$normal_pnum-1]['probability']+$surplus_num;

            $arr_pro = array();
            foreach ($normal_prize as $lk=>$lv){
                $arr_pro[$lv['prizepool_prize_id']] = $lv['probability'];
            }
            $arr_num = array_sum($arr_pro);
            $lucky_id = 0;
            foreach($arr_pro as $key=>$vv){
                $randNum = mt_rand(1,$arr_num);
                if($randNum<=$vv){
                    $lucky_id = $key;
                    break;
                }else{
                    $arr_num -= $vv;
                }
            }
        }else{
            $lucky_id = $not_win_lucky_id;
        }
        $is_lottery = 1;
        if($prizes[$lucky_id]['type']==3){
            $is_lottery = 0;
        }
        $lucky_pool_key = $key_pool.$lucky_id;
        $res_pool = $redis->get($lucky_pool_key);
        $pools = array();
        if(!empty($res_pool)){
            $pools = json_decode($res_pool,true);
        }
        $pools[$openid.$activity_id]=1;
        $redis->set($lucky_pool_key,json_encode($pools));

        if($is_lottery){
            $win_users[$openid]=$lucky_id;
            $redis->set($key_winuser,json_encode($win_users),86400);
        }

        $cache_prize_user = C('SAPP_LUCKYLOTTERY_PRIZEUSER').$activity_id;
        $res_puser = $redis->get($cache_prize_user);
        $puser = array();
        if(!empty($res_puser)){
            $puser = json_decode($res_puser,true);
        }
        $puser[$openid] = array('lucky_id'=>$lucky_id);
        $redis->set($cache_prize_user,json_encode($puser),86400);

        return array('lucky_id'=>$lucky_id,'is_lottery'=>$is_lottery);
    }


}