<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class TaskController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'getHotelTastList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'page'=>1001);
                break;
            case 'getShareprofit':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'task_id'=>1001);
                break;
            case 'setShareprofit':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'task_id'=>1001,
                    'level1'=>1001,'level2'=>1001,'level3'=>1002);
                break;
            case 'getCashTaskList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'receiveCashTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001,'activityapply_id'=>1002);
                break;
            case 'getInProgressCashTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
            case 'getCashTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;
            case 'receiveTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;
            case 'checkStock':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
                break;
            case 'delTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_user_id'=>1001,'openid'=>1001);
                break;
            case 'scanTastewine':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'task_user_id'=>1001,'openid'=>1001,'idcode'=>1001);
                break;
            case 'finishDemandadvTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'box_mac'=>1001,'ads_id'=>1001,'task_id'=>1001);
                break;
            case 'demandadvTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'ads_id'=>1001,'task_id'=>1001,
                    'dtype'=>1001,'box_mac'=>1002,'play_time'=>1002,'mobile_brand'=>1002,'mobile_model'=>1002,);
                break;
            case 'getPopupTask':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getTaskList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $field_staff = 'a.openid,a.level,a.id as staff_id,merchant.type';
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_task_user = new \Common\Model\Integral\TaskuserModel();
        $oss_host = get_oss_host();
        $fields = "a.id as task_user_id,task.id task_id,task.name task_name,task.goods_id,task.integral,concat('".$oss_host."',media.`oss_addr`) img_url,concat('".$oss_host."',task.`image_url`) wimg_url,
        task.desc,task.is_shareprofit,task.type,task.task_type,task.task_info,task.people_num,task.status,task.flag,task.end_time as task_expire_time,a.people_num as join_peoplenum,a.activityapply_id,a.idcode";
        $where = array('a.openid'=>$openid,'a.status'=>1,'task.task_type'=>array('not in','29'));
        $where["DATE_FORMAT(a.add_time,'%Y-%m-%d')"] = date('Y-m-d');
        $m_media = new \Common\Model\MediaModel();
        $res_inprogress_task = $m_task_user->getUserTaskList($fields,$where,'a.id desc');
        $where = array('a.openid'=>$openid,'a.status'=>1,'task.task_type'=>29,'task.status'=>1);
        $res_inprogress_check_task = $m_task_user->getUserTaskList($fields,$where,'a.id desc','0,1');
        if(!empty($res_inprogress_check_task)){
            $res_inprogress_task = array_merge($res_inprogress_check_task,$res_inprogress_task);
        }
        $inprogress_task = $invalid_task = $finish_task = array();
        $m_dishgoods = new \Common\Model\Smallapp\DishgoodsModel();
        $m_stock_check = new \Common\Model\Smallapp\StockcheckModel();
        if(!empty($res_inprogress_task)){
            $host_name = C('HOST_NAME');
            $now_time = date('Y-m-d H:i:s');
            $start_time = date('Y-m-d 00:00:00');
            $end_time   = date('Y-m-d 23:59:59');
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $m_ads = new \Common\Model\AdsModel();
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $m_activityhotel = new \Common\Model\Smallapp\ActivityhotelModel();
            $m_activitytaste = new \Common\Model\Smallapp\ActivityTastewineModel();
            $m_finance_goods_config = new \Common\Model\Finance\GoodsConfigModel();
            foreach ($res_inprogress_task as $k=>$v){
                $task_info = json_decode($v['task_info'],true);
                unset($v['task_info']);
                $tuwhere = array('openid'=>$openid,'task_id'=>$v['task_id'],'status'=>1);
                $tuwhere['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
                $res_tu = $m_task_user->field('integral')->where($tuwhere)->find();
                $v['today_integral'] = intval($res_tu['integral']);
                $v['progress'] = '今日获得积分';
                $tinfo = $v;
                if($now_time>=$v['task_expire_time']){
                    $v['status']=0;
                }
                switch ($v['task_type']){
                    case 22:
                        $res_goods = $m_dishgoods->getInfo(array('id'=>$v['goods_id']));
                        $media_info = $m_media->getMediaInfoById($res_goods['video_intromedia_id']);
                        $oss_path = $media_info['oss_path'];
                        $oss_path_info = pathinfo($oss_path);
                        $tinfo['is_tvdemand'] = 1;
                        $tinfo['price'] = $res_goods['price'];
                        $tinfo['duration'] = $media_info['duration'];
                        $tinfo['tx_url'] = $media_info['oss_addr'];
                        $tinfo['filename'] = $oss_path_info['basename'];
                        $tinfo['forscreen_url'] = $oss_path;
                        $tinfo['resource_size'] = $media_info['oss_filesize'];
                        $tinfo['people_num'] = $v['people_num']-$v['join_peoplenum']>0?$v['people_num']-$v['join_peoplenum']:0;
                        if($v['status']==1 && $v['flag']==1){
                            if($tinfo['people_num']>0){
                                $inprogress_task[$v['task_id']]=$tinfo;
                            }else{
                                $finish_task[]=$v['task_id'];
                                $tinfo['itype'] = 2;
                                $invalid_task[]=$tinfo;
                            }
                        }else{
                            if($tinfo['people_num']>0){
                                $tinfo['itype'] = 1;
                            }else{
                                $tinfo['itype'] = 2;
                            }
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 23:
                    case 24:
                        if($v['task_type']==24) {
                            $res_goods = $m_dishgoods->getInfo(array('id'=>$v['goods_id']));
                            $tinfo['price'] = $res_goods['price'];
                        }
                        $res_ainfo = $m_activity->getInfo(array('task_user_id'=>$v['task_user_id']));
                        if($v['status']==1 && $v['flag']==1){
                            if(!empty($res_ainfo) && $res_ainfo['status']==2){
                                $finish_task[]=$v['task_id'];
                                $tinfo['itype'] = 2;
                                $invalid_task[]=$tinfo;
                            }else{
                                $activity_status = 0;//0待发起 1已发起未开始(可修改) 2进行中
                                $activity_id = 0;
                                if(!empty($res_ainfo)){
                                    $activity_id = $res_ainfo['id'];
                                    if($res_ainfo['status']==0){
                                        $activity_status = 1;
                                    }else{
                                        $activity_status = 2;
                                    }
                                    $start_hour = date('G',strtotime($res_ainfo['start_time']))-10;
                                    $start_minute = date('i',strtotime($res_ainfo['start_time']));
                                    $activity_start_time = array(intval($start_hour),intval($start_minute/10));

                                    $lottery_start_hour = date('G',strtotime($res_ainfo['lottery_time']))-10;
                                    $lottery_start_minute = date('i',strtotime($res_ainfo['lottery_time']));
                                    $activity_lottery_time = array(intval($lottery_start_hour),intval($lottery_start_minute/10));

                                    $tinfo['activity_start_time'] = $activity_start_time;
                                    $tinfo['activity_lottery_time'] = $activity_lottery_time;
                                    $tinfo['activity_scope'] = $res_ainfo['scope'];
                                }
                                $tinfo['task_expire_time'] = date('Y.m.d-H:i',strtotime($v['task_expire_time']));
                                $tinfo['activity_id'] = $activity_id;
                                $tinfo['activity_status'] = $activity_status;
                                $inprogress_task[$v['task_id']]=$tinfo;
                            }
                        }else{
                            if($res_ainfo['status']==2){
                                $tinfo['itype'] = 2;
                            }else{
                                $tinfo['itype'] = 1;
                            }
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 25:
                        if($v['status']==1 && $v['flag']==1){
                            if(!empty($task_info['ads_id'])){
                                $res_ads = $m_ads->getWhere(array('id'=>$task_info['ads_id']), '*');
                                $media_info = $m_media->getMediaInfoById($res_ads[0]['media_id']);
                                $oss_path = $media_info['oss_path'];
                                $oss_path_info = pathinfo($oss_path);
                                $tinfo['duration'] = $media_info['duration'];
                                $tinfo['tx_url'] = $media_info['oss_addr'];
                                $tinfo['resource_size'] = $media_info['oss_filesize'];
                                $tinfo['filename'] = $oss_path_info['basename'];
                                $tinfo['forscreen_url'] = $oss_path;
                                $tinfo['ads_id'] = $task_info['ads_id'];
                            }
                            $inprogress_task[$v['task_id']]=$tinfo;
                        }else{
                            $tinfo['itype'] = 1;
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 26:
                        $tinfo['integral'] = $task_info['invite_vip_reward_saler'];
                        $code_url = $host_name."/basedata/forscreenQrcode/getBoxQrcode?box_id=0&box_mac=0&data_id={$res_staff[0]['staff_id']}&type=50";
                        if($v['status']==1 && $v['flag']==1){
                            $tinfo['code_url'] = $code_url;
                            $inprogress_task[$v['task_id']]=$tinfo;
                        }else{
                            $tinfo['itype'] = 1;
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 27:
                        if($v['status']==1 && $v['flag']==1){
                            $res_ahotel = $m_activityhotel->getHotelTastewineActivity($hotel_id);
                            if(!empty($res_ahotel)){
                                $res_applay_info = $m_activityapply->getInfo(array('id'=>$v['activityapply_id']));
                                if($res_applay_info['status']==5){
                                    $end_mobile = substr($res_applay_info['mobile'],-4);
                                    $tinfo['message'] = "{$res_applay_info['box_name']} 手机尾号{$end_mobile}";
                                    $inprogress_task[]=$tinfo;
                                }
                            }else{
                                $tinfo['itype'] = 1;
                                $invalid_task[]=$tinfo;
                            }
                        }else{
                            $tinfo['itype'] = 1;
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 28:
                        if($v['status']==1 && $v['flag']==1){
                            $res_ataste = $m_activitytaste->getInfo(array('idcode'=>$v['idcode']));
                            $res_gconfig = $m_finance_goods_config->getDataList('name',array('goods_id'=>$res_ataste['finance_goods_id'],'type'=>20,'status'=>1),'id asc');
                            $gconfig = array();
                            foreach ($res_gconfig as $gv){
                                $gconfig[]=$gv['name'];
                            }
                            $message = '';
                            if(!empty($gconfig)){
                                $message = join('、',$gconfig);
                            }
                            $tinfo['message'] = $message;
                            $tinfo['remark'] = '物料回收成功后，任务自动完成';
                            $inprogress_task[]=$tinfo;
                        }else{
                            $tinfo['itype'] = 1;
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 6:
                        if($v['status']==1 && $v['flag']==1){
                            $inprogress_task[$v['task_id']]=$tinfo;
                        }else{
                            $tinfo['itype'] = 1;
                            $invalid_task[]=$tinfo;
                        }
                        break;
                    case 29:
                        $res_check = $m_stock_check->getInfo(array('hotel_id'=>$hotel_id,'task_id'=>$v['task_id']));
                        if(empty($res_check)){
                            if($v['status']==1 && $v['flag']==1){
                                $inprogress_task[$v['task_id']]=$tinfo;
                            }else{
                                $tinfo['itype'] = 1;
                                $invalid_task[]=$tinfo;
                            }
                        }
                        break;
                }
            }
        }
        $all_inprogress_task = array();
        if(!empty($inprogress_task)){
            $all_inprogress_task = array_values($inprogress_task);
        }

        $fields = "task.id task_id,task.name task_name,task.goods_id,task.integral,task.task_info,task.task_type,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc,task.is_shareprofit,task.end_time as task_expire_time,a.staff_id";
        $where = array('a.hotel_id'=>$hotel_id,'task.status'=>1,'task.flag'=>1);
        $no_task_ids = array();
        if(!empty($inprogress_task)){
            $no_task_ids = array_keys($inprogress_task);
        }
        if(!empty($finish_task)){
            $no_task_ids = array_merge($no_task_ids,$finish_task);
        }
        if(!empty($no_task_ids)){
            $where['task.id'] = array('not in',$no_task_ids);
        }
        $order = 'task.id asc';
        $m_task_hotel = new \Common\Model\Integral\TaskHotelModel();
        $rescanreceive_task = $m_task_hotel->getHotelTaskList($fields,$where,$order,0,1000);
        $canreceive_task = array();
        $all_prizes = array('1'=>'一等奖','2'=>'二等奖','3'=>'三等奖');
        if(!empty($rescanreceive_task)){
            $m_taskprize = new \Common\Model\Integral\TaskPrizeModel();
            $send_num_key = C('SAPP_SALE_TASK_SENDNUM');
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(14);
            $all_people_num = 1000;
            $now_time = date('Y-m-d H:i:s');

            foreach ($rescanreceive_task as $k=>$v){
                $task_info = json_decode($v['task_info'],true);
                unset($v['task_info']);

                if($now_time>$v['task_expire_time']){
                    continue;
                }

                $send_num_cache_key = $send_num_key.$v['task_id'];
                $res_send_num = $redis->get($send_num_cache_key);
                if(!empty($res_send_num)){
                    $send_num = $res_send_num + rand(1,3);
                }else{
                    $send_num = 800 + rand(1,3);
                }
                if($send_num>$all_people_num){
                    $send_num = 800 + rand(1,3);
                }
                $redis->set($send_num_cache_key,$send_num,86400*30);
                $v['get_num'] = $send_num;
                $v['remain_num'] = $all_people_num-$send_num;
                $v['percent'] = round(($send_num/$all_people_num)*100,2);
                $v['remain_percent'] = round(100-$v['percent'],2);

                switch ($v['task_type']){
                    case 23:
                        if($v['staff_id']==$res_staff[0]['staff_id']){
                            $res_prizes = $m_taskprize->getDataList('*',array('task_id'=>$v['task_id'],'status'=>1),'level asc');
                            $prize_list = array();
                            foreach ($res_prizes as $pv){
                                $name = $all_prizes[$pv['level']].$pv['amount'].'人';
                                $prize_list[]=array('name'=>$name,'prize'=>$pv['name']);
                            }
                            $v['prize_list'] = $prize_list;
                            $canreceive_task[]=$v;
                        }
                        break;
                    case 24:
                        $res_goods = $m_dishgoods->getInfo(array('id'=>$v['goods_id']));
                        $v['price'] = $res_goods['price'];
                        $canreceive_task[]=$v;
                        break;
                    case 26:
                        $v['integral'] = $task_info['invite_vip_reward_saler'];
                        $canreceive_task[]=$v;
                        break;
                    case 27:
                        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
                        $res_apply = $m_activityapply->getTastewine($hotel_id);
                        if(!empty($res_apply) && $v['goods_id']==$res_apply['goods']['finance_goods_id']){
                            foreach ($res_apply['apply_list'] as $av){
                                $v['activityapply_id'] = $av['activityapply_id'];
                                $end_mobile = substr($av['mobile'],-4);
                                $intro = "{$av['box_name']} 手机尾号{$end_mobile}";
                                $v['message'] = $intro;
                                $canreceive_task[]=$v;
                            }
                        }
                        break;
                    case 28:
                        break;
                    case 29:
                        $res_utask = $m_task_user->getInfo(array('openid'=>$openid,'task_id'=>$v['task_id']));
                        if(empty($res_utask)){
                            $res_check = $m_stock_check->getInfo(array('hotel_id'=>$hotel_id,'task_id'=>$v['task_id']));
                            if(empty($res_check)){
                                $canreceive_task[]=$v;
                            }
                        }
                        break;
                    default:
                        $canreceive_task[]=$v;
                }
            }
        }
        $res_data = array('inprogress'=>$all_inprogress_task,'canreceive'=>$canreceive_task,'invalid'=>$invalid_task);
        $this->to_back($res_data);
    }

    public function receiveTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = $this->params['task_id'];
        $activityapply_id = intval($this->params['activityapply_id']);

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type,merchant.id as merchant_id,merchant.is_integral';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $now_time = date('Y-m-d H:i:s');
        $get_start_task_time = date('Y-m-d 06:00:00');
        if($get_start_task_time>$now_time){
            $this->to_back(93220);
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $where = array('a.task_id'=>$task_id,'a.hotel_id'=>$hotel_id,'task.status'=>1,'task.flag'=>1);
        $fileds = 'task.id as task_id,task.name,task.media_id,task.end_time,task.task_integral,task.task_type,task.integral';
        $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
        if(empty($res_task)){
            $this->to_back(93070);
        }
        if($res_task[0]['end_time']<$now_time){
            $this->to_back(93071);
        }

        $m_usertask = new \Common\Model\Integral\TaskuserModel();
        $where = array('openid'=>$openid,'task_id'=>$task_id,'status'=>1);
        $where["DATE_FORMAT(add_time,'%Y-%m-%d')"] = date('Y-m-d');
        if($res_task[0]['task_type']==27){
            if(empty($activityapply_id)){
                $this->to_back(1001);
            }
            $where['activityapply_id'] = $activityapply_id;
            $res_has_task = $m_usertask->getInfo(array('task_id'=>$task_id,'activityapply_id'=>$activityapply_id));
            if(!empty($res_has_task) && $res_has_task['openid']!=$openid){
                $this->to_back(93221);
            }
        }
        $res_usertask = $m_usertask->getInfo($where);
        if(!empty($res_usertask)){
            $this->to_back(93069);
        }

        $data = array('openid'=>$openid,'task_id'=>$task_id,'hotel_id'=>$hotel_id,'integral'=>$res_task[0]['task_integral']);
        if(!empty($activityapply_id)){
            $data['activityapply_id'] = $activityapply_id;
        }
        $user_task_id = $m_usertask->add($data);
        if(in_array($res_task[0]['task_type'],array(22,23))){
            //增加积分
            if($res_staff[0]['is_integral']==1){
                $integralrecord_openid = $openid;
                $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
                if(!empty($res_integral)){
                    $userintegral = $res_integral['integral']+$res_task[0]['task_integral'];
                    $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
                }else{
                    $uidata = array('openid'=>$openid,'integral'=>$res_task[0]['task_integral']);
                    $m_userintegral->add($uidata);
                }
            }else{
                $integralrecord_openid = $hotel_id;
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $where = array('id'=>$res_staff[0]['merchant_id']);
                $m_merchant->where($where)->setInc('integral',$res_task[0]['task_integral']);
            }

            $type = 10;
            if($res_task[0]['task_type']==23){
                $type = 12;
            }
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getHotelInfoById($hotel_id);
            $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_hotel['area_id'],'task_id'=>$task_id,'area_name'=>$res_hotel['area_name'],
                'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
                'integral'=>$res_task[0]['task_integral'],'content'=>1,'type'=>$type,'integral_time'=>date('Y-m-d H:i:s'));
            $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegralrecord->add($integralrecord_data);
            //end
        }
        $res_data = array('user_task_id'=>$user_task_id);
        if($res_task[0]['task_type']==27){
            $m_acitvityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $m_acitvityapply->updateData(array('id'=>$activityapply_id),array('status'=>5));

            $res_activity_apply = $m_acitvityapply->getInfo(array('id'=>$activityapply_id));
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $res_activity = $m_activity->getInfo(array('id'=>$res_activity_apply['activity_id']));
            $wine_ml = $res_activity['wine_ml'];
            $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
            $fields = 'g.id,g.finance_goods_id,g.name,g.detail_imgs';
            $res_goods = $m_hotelgoods->getGoodsList($fields,array('h.hotel_id'=>$hotel_id,'g.finance_goods_id'=>$res_activity['finance_goods_id']),'','0,1');
            $detail_imgs_info = explode(',',$res_goods[0]['detail_imgs']);
            $img_url = '';
            if(!empty($detail_imgs_info)){
                $img_url = get_oss_host().$detail_imgs_info[0]."?x-oss-process=image/quality,Q_60";
            }
            $end_mobile = substr($res_activity_apply['mobile'],-4);
            $res_data['message'] = "请及时为{$res_activity_apply['box_name']}包间手机尾号{$end_mobile}的客人送酒";
            $res_data['goods_name'] = $res_goods[0]['name'];
            $res_data['wine_ml'] = $wine_ml;
            $res_data['integral'] = $res_task[0]['integral'];
            $res_data['img_url'] = $img_url;
        }
        $this->to_back($res_data);
    }

    public function delTask(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $task_id = $this->params['task_user_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_usertask = new \Common\Model\Integral\TaskuserModel();
        $where = array('id'=>$task_id,'openid'=>$openid);
        $res_usertask = $m_usertask->getInfo($where);
        if(empty($res_usertask)){
            $this->to_back(93069);
        }
        $m_usertask->updateData(array('id'=>$task_id),array('status'=>2));
        $this->to_back(array());
    }

    public function getPopupTask(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $where = array('a.hotel_id'=>$hotel_id,'task.status'=>1,'task.flag'=>1,'task.task_type'=>27);
        $where['task.end_time'] = array('EGT',date('Y-m-d H:i:s'));
        $fileds = 'task.id as task_id,task.name,task.media_id,task.end_time,task.task_integral,task.task_type,task.integral';
        $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
        $res_data = array('is_popup'=>0);
        if(empty($res_task)){
            $this->to_back($res_data);
        }
        $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_apply = $m_activityapply->getTastewine($hotel_id);
        if(!empty($res_apply)){
            $task_id = $res_task[0]['task_id'];
            $activityapply_id = $res_apply['apply_list'][0]['activityapply_id'];
            $integral = $res_task[0]['integral'];
            $box_name = $res_apply['apply_list'][0]['box_name'];
            $end_mobile = substr($res_apply['apply_list'][0]['mobile'],-4);
            $wine_ml = $res_apply['activity']['wine_ml'];
            $message= "{$box_name}包间手机尾号{$end_mobile}的客人";

            $detail_imgs_info = explode(',',$res_apply['goods']['detail_imgs']);
            $img_url = '';
            if(!empty($detail_imgs_info)){
                $img_url = get_oss_host().$detail_imgs_info[0]."?x-oss-process=image/quality,Q_60";
            }
            $res_data = array('is_popup'=>1,'box_name'=>$box_name,'message'=>$message,'goods_name'=>$res_apply['goods']['name'],
                'wine_ml'=>$wine_ml,'integral'=>$integral,'img_url'=>$img_url,'task_id'=>$task_id,'activityapply_id'=>$activityapply_id);
        }
        $this->to_back($res_data);
    }

    public function scanTastewine(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $task_user_id = $this->params['task_user_id'];
        $idcode = $this->params['idcode'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type,merchant.is_integral';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_usertask = new \Common\Model\Integral\TaskuserModel();
        $where = array('id'=>$task_user_id,'openid'=>$openid);
        $res_usertask = $m_usertask->getInfo($where);
        if(empty($res_usertask)){
            $this->to_back(93069);
        }
        $apply_id = $res_usertask['activityapply_id'];
        $m_activity_apply = new \Common\Model\Smallapp\ActivityapplyModel();
        $res_apply = $m_activity_apply->getInfo(array('id'=>$apply_id));
        if($res_apply['status']==6){
            $this->to_back(93222);
        }
        $m_activity_hotel = new \Common\Model\Smallapp\ActivityhotelModel();
        $res_activity = $m_activity_hotel->getHotelTastewineActivity($hotel_id,$res_apply['add_time']);
        if(empty($res_activity)){
            $this->to_back(93223);
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
        }elseif($record_info[0]['type']==6){
            $this->to_back(93095);
        }elseif($record_info[0]['type']==7){
            $this->to_back(93098);
        }
        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$record_info[0]['stock_id']));
        if($res_stock['hotel_id']!=$hotel_id){
            $this->to_back(93105);
        }
        if($res_activity['activity']['finance_goods_id']!=$record_info[0]['goods_id']){
            $this->to_back(93224);
        }
        $bottle_num = $res_activity['activity']['bottle_num'];
        $m_activity_tastewine = new \Common\Model\Smallapp\ActivityTastewineModel();
        $res_tastewine = $m_activity_tastewine->getInfo(array('hotel_id'=>$hotel_id,'finance_goods_id'=>$res_activity['activity']['finance_goods_id'],'status'=>1));
        $is_finish = 0;
        if(empty($res_tastewine)){
            $res_num = $m_activity_tastewine->getALLDataList('count(id) as num',array('hotel_id'=>$hotel_id,'finance_goods_id'=>$res_activity['activity']['finance_goods_id'],'status'=>2),'','','');
            $taste_num = intval($res_num[0]['num']);
            if($taste_num>=$bottle_num){
                $this->to_back(93225);
            }
            $res_tastewine = $m_activity_tastewine->getInfo(array('idcode'=>$idcode));
            if(!empty($res_tastewine)){
                $this->to_back(93227);
            }
            $add_data = array('task_id'=>$res_usertask['task_id'],'hotel_id'=>$hotel_id,'finance_goods_id'=>$res_activity['activity']['finance_goods_id'],
                'idcode'=>$idcode,'num'=>$res_activity['activity']['people_num'],'join_num'=>1,'status'=>1);
            $m_activity_tastewine->add($add_data);
        }else{
            if($res_tastewine['idcode']!=$idcode){
                $this->to_back(93226);
            }
            if($res_tastewine['status']==2){
                $this->to_back(93227);
            }
            $join_num = $res_tastewine['join_num']+1;
            if($join_num>$res_tastewine['num']){
                $this->to_back(93227);
            }
            $updata = array('join_num'=>$join_num);
            if($join_num==$res_tastewine['num']){
                $updata['status'] = 2;
                $updata['finish_time'] = date('Y-m-d H:i:s');
                $is_finish = 1;
            }
            $m_activity_tastewine->updateData(array('id'=>$res_tastewine['id']),$updata);
        }
        $m_activity_apply->updateData(array('id'=>$apply_id),array('status'=>6,'idcode'=>$idcode,'op_openid'=>$openid,'wo_time'=>date('Y-m-d H:i:s')));
        $m_usertask->updateData(array('id'=>$task_user_id),array('idcode'=>$idcode));
        //增加积分
        $m_task = new \Common\Model\Integral\TaskModel();
        $res_task = $m_task->getInfo(array('id'=>$res_usertask['task_id']));
        if($res_staff[0]['is_integral']==1){
            $integralrecord_openid = $openid;
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
            $res_integral = $m_userintegral->getInfo(array('openid'=>$openid));
            if(!empty($res_integral)){
                $userintegral = $res_integral['integral']+$res_task['integral'];
                $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
            }else{
                $uidata = array('openid'=>$openid,'integral'=>$res_task['integral']);
                $m_userintegral->add($uidata);
            }
        }else{
            $integralrecord_openid = $hotel_id;
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $where = array('id'=>$res_staff[0]['merchant_id']);
            $m_merchant->where($where)->setInc('integral',$res_task['integral']);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelInfoById($hotel_id);
        $m_box = new \Common\Model\BoxModel();
        $res_box = $m_box->getOnerow(array('id'=>$res_apply['box_id']));

        $integralrecord_data = array('openid'=>$integralrecord_openid,'area_id'=>$res_hotel['area_id'],'task_id'=>$task_user_id,'area_name'=>$res_hotel['area_name'],
            'hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
            'room_id'=>$res_apply['room_id'],'room_name'=>$res_apply['box_name'],'box_id'=>$res_apply['box_id'],'box_mac'=>$res_apply['box_mac'],'box_type'=>$res_box['box_type'],
            'integral'=>$res_task['integral'],'content'=>1,'type'=>22,'integral_time'=>date('Y-m-d H:i:s'));
        $m_userintegralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $m_userintegralrecord->add($integralrecord_data);
        //end
        $message = "你已经完成为{$res_apply['box_name']}包间的客人送完品鉴酒";
        $remark = '';
        if($is_finish){
            $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
            $where = array('a.hotel_id'=>$hotel_id,'task.status'=>1,'task.flag'=>1,'task.task_type'=>28);
            $where['task.end_time'] = array('EGT',date('Y-m-d H:i:s'));
            $fileds = 'task.id as task_id,task.name,task.integral,task.goods_id';
            $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
            if(!empty($res_task)){
                $m_finance_goods_config = new \Common\Model\Finance\GoodsConfigModel();
                $res_gconfig = $m_finance_goods_config->getDataList('name',array('goods_id'=>$res_task[0]['goods_id'],'type'=>20,'status'=>1),'id asc');
                if(!empty($res_gconfig)){
                    $remark = "回收任务，完成后获得额外{$res_task[0]['integral']}积分奖励。";
                    $data = array('openid'=>$openid,'task_id'=>$res_task[0]['task_id'],'hotel_id'=>$hotel_id,'idcode'=>$idcode,'activityapply_id'=>$apply_id);
                    $user_task_id = $m_usertask->add($data);
                }
            }
        }
        $this->to_back(array('message'=>$message,'remark'=>$remark));
    }

    public function checkStock(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = $this->params['task_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_usertask = new \Common\Model\Integral\TaskuserModel();
        $where = array('openid'=>$openid,'task_id'=>$task_id);
        $res_usertask = $m_usertask->getInfo($where);
        if(empty($res_usertask)){
            $this->to_back(93073);
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $where = array('a.task_id'=>$task_id,'task.status'=>1,'task.flag'=>1);
        $fileds = 'task.id as task_id,task.name,task.goods_id,task.people_num,task.end_time';
        $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
        if(empty($res_task)){
            $this->to_back(93070);
        }
        $now_time = date('Y-m-d H:i:s');
        if($res_task[0]['end_time']<$now_time){
            $this->to_back(93071);
        }
        $send_num = $res_task[0]['people_num'] - $res_usertask['people_num'];
        $m_dishgoods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_dishgoods->getInfo(array('id'=>$res_task[0]['goods_id']));

        $oss_host = get_oss_host();
        $task_user_id = $res_usertask['id'];
        $width_img = $oss_host.$res_goods['cover_imgs'];
        $res_data = array('task_user_id'=>$task_user_id,'width_img'=>$width_img,
            'goods_id'=>$res_goods['id'],'price'=>$res_goods['price'],'send_num'=>$send_num);
        $this->to_back($res_data);
    }

    public function getHotelTastList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);
        $page     = $this->params['page'] ? $this->params['page'] : 1;
        $pagesize = 20;

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1,'merchant.type'=>3);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $field_staff = 'a.openid,a.level,merchant.type';
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
            $m_staff = new \Common\Model\Integral\StaffModel();
            $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
            if(empty($res_staff)){
                $this->to_back(93014);
            }
        }
        
        $m_task_hotel = new \Common\Model\Integral\TaskHotelModel();
        $oss_host = get_oss_host();
        $fields = "task.id task_id,task.name task_name ,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc,task.is_shareprofit";
        $where = array('a.hotel_id'=>$hotel_id,'task.type'=>1,'task.status'=>1,'task.flag'=>1);
        $order = 'task.id asc';
        $size = ($page-1) * $pagesize;
        $task_list = $m_task_hotel->getHotelTaskList($fields,$where,$order,0,$size);
        $m_task_user = new \Common\Model\Integral\TaskuserModel();
        $start_time = date('Y-m-d 00:00:00');
        $end_time   = date('Y-m-d 23:59:59');
        $m_media = new \Common\Model\MediaModel();
        foreach($task_list as $key=>$v){
            $map = array('openid'=>$openid,'task_id'=>$v['task_id']);
            $map['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $rs = $m_task_user->field('integral')->where($map)->find();
            $task_list[$key]['integral'] = intval($rs['integral']);
            $task_list[$key]['progress'] = '今日获得积分';

            if($v['video_intromedia_id']){
                $media_info = $m_media->getMediaInfoById($v['video_intromedia_id']);
                $oss_path = $media_info['oss_path'];
                $oss_path_info = pathinfo($oss_path);

                $dinfo['is_tvdemand'] = 1;
                $dinfo['duration'] = $media_info['duration'];
                $dinfo['tx_url'] = $media_info['oss_addr'];
                $dinfo['filename'] = $oss_path_info['basename'];
                $dinfo['forscreen_url'] = $oss_path;
                $dinfo['resource_size'] = $media_info['oss_filesize'];
            }
        }
        $this->to_back($task_list);
    }

    public function getShareprofit(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = intval($this->params['task_id']);

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $res_staff = $res_staff[0];

        $level1 = $level2 = $level3 = $last_num = 0;
        if($res_staff['level']==1){
            $where = array('task_id'=>$task_id);
            $where['hotel_id'] = array('in',array(0,$hotel_id));
            $m_task_shareprofit = new \Common\Model\Integral\TaskShareprofitModel();
            $res_shareprofit = $m_task_shareprofit->getTaskShareprofit('level1,level2,level3',$where,'id desc',0,1);

            $level1 = intval($res_shareprofit[0]['level1']);
            $level2 = intval($res_shareprofit[0]['level2']);
            $level3 = intval($res_shareprofit[0]['level3']);

            $where = array('task_id'=>$task_id,'hotel_id'=>$hotel_id,'openid'=>$openid);
            $start_time = date('Y-m-01 00:00:00');
            $end_time   = date('Y-m-31 23:59:59');
            $where['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $res_num = $m_task_shareprofit->getTaskShareprofit('count(id) as num',$where,'id desc',0,1);
            $num = intval($res_num[0]['num']);
            $all_num = 3;
            $last_num = $all_num-$num>0?$all_num-$num:0;
        }
        $res = array('level1'=>$level1,'level2'=>$level2,'level3'=>$level3,'num'=>$last_num);
        $this->to_back($res);
    }

    public function setShareprofit(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = intval($this->params['task_id']);
        $level1 = intval($this->params['level1']);
        $level2 = intval($this->params['level2']);
        $level3 = !empty($this->params['level3'])?intval($this->params['level3']):0;

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        if($res_staff[0]['level']!=1){
           $this->to_back(93027);
        }
        if($level1+$level2+$level3!=100){
            $this->to_back(93028);
        }

        $where = array('task_id'=>$task_id,'hotel_id'=>$hotel_id,'openid'=>$openid);
        $start_time = date('Y-m-01 00:00:00');
        $end_time   = date('Y-m-31 23:59:59');
        $where['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
        $m_task_shareprofit = new \Common\Model\Integral\TaskShareprofitModel();
        $res_num = $m_task_shareprofit->getTaskShareprofit('count(id) as num',$where,'id desc',0,1);
        $num = intval($res_num[0]['num']);
        if($num>=3){
            $this->to_back(93026);
        }
        $add_data = array('level1'=>$level1,'level2'=>$level2,'level3'=>$level3,
            'task_id'=>$task_id,'hotel_id'=>$hotel_id,'openid'=>$openid);
        $m_task_shareprofit->add($add_data);

        $all_num = 3;
        $last_num = $all_num - $num -1>0?$all_num - $num -1:0;
        $res = array('level1'=>$level1,'level2'=>$level2,'level3'=>$level3,'num'=>$last_num);
        $this->to_back($res);
    }

    public function getCashTaskList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(1,2)));
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        $is_has_task = 0;
        if($res_usertask['total']>0){
            $is_has_task = 1;
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $fields = 'a.id as task_hid,a.task_id,task.name,task.media_id,task.end_time';
        $where = array('a.hotel_id'=>$hotel_id,'task.type'=>2,'task.task_type'=>21,'task.status'=>1,'task.flag'=>1);
        $where['task.end_time'] = array('egt',date('Y-m-d H:i:s'));
        $res_task = $m_hoteltask->getHotelTaskList($fields,$where,'task.money desc',0,100);
        $datalist = array();
        if(!empty($res_task)){
            $m_media = new \Common\Model\MediaModel();
            $send_num_key = C('SAPP_SALE_TASK_SENDNUM');
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(14);
            foreach ($res_task as $v){
                $send_num_cache_key = $send_num_key.$v['task_id'];
                $res_send_num = $redis->get($send_num_cache_key);
                if(!empty($res_send_num)){
                    $send_num = $res_send_num + rand(10,20);
                }else{
                    $send_num = 500 + rand(10,20);
                }
                $redis->set($send_num_cache_key,$send_num,86400*30);
                if($send_num>9999){
                    $send_num = '9999+';
                }
                $status = 1;
                if($is_has_task){
                    $status = 2;
                }
                $img_url = '';
                if(!empty($v['media_id'])){
                    $res_media = $m_media->getMediaInfoById($v['media_id']);
                    $img_url = $res_media['oss_addr'];
                }
                $info = array('task_id'=>$v['task_hid'],'name'=>$v['name'],'img_url'=>$img_url,
                    'send_num'=>$send_num,'status'=>$status,'end_time'=>$v['end_time']);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function getInProgressCashTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(1,2)));
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        $task_id = $percent = 0;
        $status = 0;//0无领取任务 1进行中 2已完成
        $name = $img_url = $end_time = '';

        if($res_usertask['total']>0){
            $task_id = $res_usertask['list'][0]['id'];
            $task_hotel_id = $res_usertask['list'][0]['task_hotel_id'];
            $status = $res_usertask['list'][0]['status'];
            if($status==1){
                $now_time = date('Y-m-d H:i:s');
                $m_task = new \Common\Model\Integral\TaskModel();
                $res_otask = $m_task->getInfo(array('id'=>$res_usertask['list'][0]['task_id']));
                if($res_otask['end_time']<$now_time || $res_otask['status']==0 || $res_otask['flag']==0){
                    $m_usertask->updateData(array('id'=>$res_usertask['list'][0]['id']),array('status'=>3));
                    $task_id = 0;
                    $status = 0;
                    $percent = 0;
                }else{
                    $percent = sprintf("%.4f",$res_usertask['list'][0]['get_money']/$res_usertask['list'][0]['money']);
                    $percent = $percent * 100;
                }
            }else{
                $percent = 100;
            }
            if($task_id && $task_hotel_id){
                $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
                $where = array('a.id'=>$task_hotel_id);
                $fileds = 'a.meal_num,a.interact_num,a.comment_num,a.finish_num,task.name,task.media_id,task.end_time';
                $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
                $name = $res_task[0]['name'];
                $end_time = $res_task[0]['end_time'];
                if(!empty($res_task[0]['media_id'])){
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($res_task[0]['media_id']);
                    $img_url = $res_media['oss_addr'];
                }
            }
        }
        $data = array('task_id'=>$task_id,'status'=>$status,'percent'=>$percent,
            'name'=>$name,'img_url'=>$img_url,'end_time'=>$end_time);
        $this->to_back($data);
    }

    public function getCashTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);
        $openid = $this->params['openid'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,a.hotel_id,a.room_ids,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $staff_info = $res_staff[0];
        $is_bind_room = 0;
        if($staff_info['hotel_id']==$hotel_id && !empty($staff_info['room_ids'])){
            $is_bind_room = 1;
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('id'=>$task_id,'openid'=>$openid);
        $res_usertask = $m_usertask->getInfo($where);
        if(empty($res_usertask)){
            $this->to_back(93060);
        }
        if($res_usertask['status']==3){
            $this->to_back(93061);
        }
        if(in_array($res_usertask['status'],array(4,5))){
            $this->to_back(93062);
        }
        $task_id = $percent = $send_num = 0;
        $status = 0;//0无领取任务 1进行中 2已完成
        $name = $img_url = $end_time = '';
        $money = $get_money = 0;
        $content = array();
        if(in_array($res_usertask['status'],array(1,2))){
            $task_id = $res_usertask['id'];
            $status = $res_usertask['status'];
            if($status==1){
                $now_time = date('Y-m-d H:i:s');
                $m_task = new \Common\Model\Integral\TaskModel();
                $res_otask = $m_task->getInfo(array('id'=>$res_usertask['task_id']));
                if($res_otask['end_time']<$now_time || $res_otask['status']==0 || $res_otask['flag']==0){
                    $m_usertask->updateData(array('id'=>$res_usertask['id']),array('status'=>3));
                    $task_id = 0;
                    $status = 0;
                    $percent = 0;
                }else{
                    $percent = sprintf("%.2f",$res_usertask['get_money']/$res_usertask['money']);
                    $percent = $percent*100;
                    $money = $res_usertask['money'];
                    $get_money = $res_usertask['get_money'];
                }
            }else{
                $percent = 100;
            }
            if($task_id){
                $send_num_key = C('SAPP_SALE_TASK_SENDNUM');
                $redis = new \Common\Lib\SavorRedis();
                $redis->select(14);
                $send_num_cache_key = $send_num_key.$res_usertask['task_id'];
                $res_send_num = $redis->get($send_num_cache_key);
                if(!empty($res_send_num)){
                    $send_num = $res_send_num;
                    if($send_num>9999){
                        $send_num = '9999+';
                    }
                }

                $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
                $where = array('a.id'=>$res_usertask['task_hotel_id']);
                $fileds = 'a.meal_num,a.interact_num,a.comment_num,a.lottery_num,a.finish_num,task.name,task.media_id,task.end_time';
                $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
                $name = $res_task[0]['name'];
                $end_time = $res_task[0]['end_time'];
                if(!empty($res_task[0]['media_id'])){
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($res_task[0]['media_id']);
                    $img_url = $res_media['oss_addr'];
                }
                $content = $m_hoteltask->getTaskinfo($res_task[0],$res_usertask);
            }
        }
        $diff_money = $money-$get_money>0?$money-$get_money:0;
        $data = array('task_id'=>$task_id,'status'=>$status,'percent'=>$percent,'money'=>$money,
            'get_money'=>$get_money,'diff_money'=>$diff_money,'name'=>$name,'img_url'=>$img_url,'end_time'=>$end_time,
            'send_num'=>$send_num,'is_bind_room'=>$is_bind_room,'content'=>$content
        );
        $this->to_back($data);
    }

    public function receiveCashTask(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid = $this->params['openid'];
        $task_id = $this->params['task_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertask = new \Common\Model\Smallapp\UserTaskModel();
        $where = array('openid'=>$openid,'status'=>array('in',array(1,2)));
        $res_usertask = $m_usertask->getDataList('*',$where,'id desc',0,1);
        if($res_usertask['total']>0){
           $this->to_back(93063);
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $where = array('a.id'=>$task_id,'task.status'=>1,'task.flag'=>1);
        $fileds = 'a.meal_num,a.interact_num,a.comment_num,a.finish_num,task.id as task_id,task.money,task.name,task.media_id,task.end_time';
        $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
        if(empty($res_task)){
            $this->to_back(93060);
        }
        $now_time = date('Y-m-d H:i:s');
        if($res_task[0]['end_time']<$now_time){
            $this->to_back(93061);
        }
        $money = $res_task[0]['money'];
        $get_money = $money * 0.7;
        $data = array('openid'=>$openid,'task_id'=>$res_task[0]['task_id'],'task_hotel_id'=>$task_id,'money'=>$money,
            'get_money'=>$get_money,'status'=>1,'type'=>1
        );
        $user_task_id = $m_usertask->add($data);
        $this->to_back(array('user_task_id'=>$user_task_id));
    }

    public function demandadvTask(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $ads_id = $this->params['ads_id'];
        $hotel_id = $this->params['hotel_id'];
        $task_id = $this->params['task_id'];
        $dtype = $this->params['dtype'];//1立即播放 2定时播放
        $play_time = $this->params['play_time'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];
        if($dtype==2){
            if(empty($play_time)){
                $this->to_back(1001);
            }
            $t_time = strtotime(date("Y-m-d $play_time"));
            if($t_time<time()){
                $this->to_back(93024);
            }
            $now_paly_time = date('Y-m-d H:i:00',$t_time);
        }else{
            $now_paly_time = date('Y-m-d H:i:s');
        }

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_task = new \Common\Model\Integral\TaskuserModel();
        $task_where = array('a.openid'=>$openid,'a.task_id'=>$task_id,'a.status'=>1,'task.type'=>2,'task.task_type'=>25);
        $task_where['task.end_time'] = array('EGT',date('Y-m-d H:i:s'));
        $start_time = date('Y-m-d 00:00:00');
        $end_time   = date('Y-m-d 23:59:59');
        $task_where['a.add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
        $res_task = $m_task->getUserTaskList('a.id,task.id as task_id,task.task_info,task.integral',$task_where,'a.id desc');
        if(empty($res_task)){
            $this->to_back(93070);
        }
        $task_user_id = $res_task[0]['id'];
        $task_content = json_decode($res_task[0]['task_info'],true);
        $lunch_start_time = $task_content['lunch_start_time'];
        $lunch_end_time = $task_content['lunch_end_time'];
        $dinner_start_time = $task_content['dinner_start_time'];
        $dinner_end_time = $task_content['dinner_end_time'];
        $box_finish_num = $task_content['box_finish_num'];
        $interval_time = $task_content['interval_time'];

        if($ads_id!=$task_content['ads_id']){
            $this->to_back(93215);
        }

        $lunch_stime = date("Y-m-d {$lunch_start_time}:00");
        $lunch_etime = date("Y-m-d {$lunch_end_time}:00");
        $dinner_stime = date("Y-m-d {$dinner_start_time}:00");
        $dinner_etime = date("Y-m-d {$dinner_end_time}:59");
        $meal_stime = $meal_etime = '';
        if($now_paly_time>=$lunch_stime && $now_paly_time<=$lunch_etime){
            $meal_stime = $lunch_stime;
            $meal_etime = $lunch_etime;
        }elseif($now_paly_time>=$dinner_stime && $now_paly_time<=$dinner_etime){
            $meal_stime = $dinner_stime;
            $meal_etime = $dinner_etime;
        }
        if(empty($meal_stime) && empty($meal_etime)){
            $this->to_back(93216);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_duser = $m_user->getOne('*',array('openid'=>$openid),'');
        $m_ads = new \Common\Model\AdsModel();
        $res_ads = $m_ads->getWhere(array('id'=>$ads_id), '*');
        $m_media = new \Common\Model\MediaModel();
        $media_info = $m_media->getMediaInfoById($res_ads[0]['media_id']);
        $url = $media_info['oss_path'];
        $oss_path_info = pathinfo($url);
        $filename = $oss_path_info['basename'];
        $resource_type = 2;
        $duration = $media_info['duration'];
        $resource_size = $media_info['oss_filesize'];
        $m_box = new \Common\Model\BoxModel();
        if($dtype==1){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $m_usertask_record = new \Common\Model\Smallapp\UserTaskRecordModel();
            if(!empty($box_mac)){
                $nowtime = getMillisecond();
                $message = array('action'=>5,'url'=>$url,'filename'=>$filename,'openid'=>$openid,'resource_type'=>$resource_type,
                    'avatarUrl'=>$res_duser['avatarUrl'],'nickName'=>$res_duser['nickName'],'forscreen_id'=>$nowtime,
                    'resource_size'=>$resource_size);
                $m_netty = new \Common\Model\NettyModel();
                $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
                if(isset($res_netty['error_code'])){
                    $this->to_back($res_netty['error_code']);
                }
                $imgs = array($url);
                $data = array('action'=>59,'box_mac'=>$box_mac,'duration'=>$duration,'forscreen_char'=>'','forscreen_id'=>$nowtime,
                    'imgs'=>json_encode($imgs),'mobile_brand'=>$mobile_brand,'mobile_model'=>$mobile_model,
                    'openid'=>$openid,'resource_id'=>$nowtime,'resource_size'=>$resource_size,'create_time'=>date('Y-m-d H:i:s'),
                    'small_app_id'=>5);
                $redis->select(5);
                $cache_key = C('SAPP_SCRREN').":".$box_mac;
                $redis->rpush($cache_key, json_encode($data));

                $where = array('hotel_id'=>$hotel_id,'task_id'=>$task_id,'box_mac'=>$box_mac);
                $where['add_time'] = array(array('EGT',$meal_stime),array('ELT',$meal_etime));
                $res_record = $m_usertask_record->getALLDataList('openid,add_time',$where,'id asc','','');
                $now_box_finish_num = 0;
                $last_demand_time = 0;
                $demand_openid = '';
                if(!empty($res_record)){
                    foreach ($res_record as $k=>$v){
                        $str_demand_time = strtotime($v['add_time']);
                        if($k==0){
                            $demand_openid = $v['openid'];
                            $now_box_finish_num++;
                            $last_demand_time = $str_demand_time + $interval_time*60;
                        }else{
                            if($str_demand_time>=$last_demand_time){
                                $demand_openid = $v['openid'];
                                $now_box_finish_num++;
                                $last_demand_time = $str_demand_time + $interval_time*60;
                            }
                        }
                    }
                }
                if($now_box_finish_num>=$box_finish_num){
                    $msg = '当前电视此任务已被完成，请使用其他电视。';
                    $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
                    $this->to_back($res_pdata);
                }
                $now_time = time();
                if($last_demand_time>$now_time){
                    $res_user = $m_user->getOne('*',array('openid'=>$demand_openid),'');
                    $uname = $res_user['nickName'];
                    $countdown_time = $last_demand_time - $now_time;
                    $mtime = round($countdown_time/60);
                    $msg = '本次任务已被'.$uname.'完成，新任务'.$mtime.'分钟后开始';
                    $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
                    $this->to_back($res_pdata);
                }
                $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $fields = 'a.id as box_id,a.name as box_name,c.id as room_id,c.name as room_name,d.id as hotel_id,d.name as hotel_name';
                $rets = $m_box->getBoxInfo($fields, $where);
                $hotel_id = $rets[0]['hotel_id'];
                $hotel_name = $rets[0]['hotel_name'];
                $room_id = $rets[0]['room_id'];
                $room_name = $rets[0]['room_name'];
                $box_id = $rets[0]['box_id'];
                $box_name = $rets[0]['box_name'];

                $usertask_record = array('openid'=>$openid,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,
                    'room_id'=>$room_id,'room_name'=>$room_name,'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,
                    'usertask_id'=>$task_user_id,'task_id'=>$task_id,'task_type'=>25,'type'=>1
                );
                $m_usertask_record->add($usertask_record);
            }else{
                $where = array('d.id'=>$hotel_id,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $fields = 'a.id as box_id,a.name as box_name,a.mac,c.id as room_id,c.name as room_name,d.id as hotel_id,d.name as hotel_name';
                $res_boxs = $m_box->getBoxInfo($fields, $where);
                $error_code = 0;
                $is_send = 0;
                foreach ($res_boxs as $bv){
                    $box_mac = $bv['mac'];
                    $nowtime = getMillisecond();
                    $message = array('action'=>5,'url'=>$url,'filename'=>$filename,'openid'=>$openid,'resource_type'=>$resource_type,
                        'avatarUrl'=>$res_duser['avatarUrl'],'nickName'=>$res_duser['nickName'],'forscreen_id'=>$nowtime,
                        'resource_size'=>$resource_size);
                    $m_netty = new \Common\Model\NettyModel();
                    $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
                    if(isset($res_netty['error_code'])){
                        $error_code = $res_netty['error_code'];
                        continue;
                    }else{
                        $is_send = 1;
                    }

                    $imgs = array($url);
                    $data = array('action'=>59,'box_mac'=>$box_mac,'duration'=>$duration,'forscreen_char'=>'','forscreen_id'=>$nowtime,
                        'imgs'=>json_encode($imgs),'mobile_brand'=>$mobile_brand,'mobile_model'=>$mobile_model,
                        'openid'=>$openid,'resource_id'=>$nowtime,'resource_size'=>$resource_size,'create_time'=>date('Y-m-d H:i:s'),
                        'small_app_id'=>5);
                    $redis->select(5);
                    $cache_key = C('SAPP_SCRREN').":".$box_mac;
                    $redis->rpush($cache_key, json_encode($data));

                    $hotel_id = $bv['hotel_id'];
                    $hotel_name = $bv['hotel_name'];
                    $room_id = $bv['room_id'];
                    $room_name = $bv['room_name'];
                    $box_id = $bv['box_id'];
                    $box_name = $bv['box_name'];
                    $usertask_record = array('openid'=>$openid,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,
                        'room_id'=>$room_id,'room_name'=>$room_name,'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,
                        'usertask_id'=>$task_user_id,'task_id'=>$task_id,'task_type'=>25,'type'=>1
                    );
                    $m_usertask_record->add($usertask_record);
                }
                if($is_send==0 && $error_code>0){
                    $this->to_back($error_code);
                }
            }
        }else{
            $fields = 'a.id as box_id,a.name as box_name,a.mac,c.id as room_id,c.name as room_name,d.id as hotel_id,d.name as hotel_name';
            if(!empty($box_mac)){
                $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $res_boxs = $m_box->getBoxInfo($fields, $where);
            }else{
                $where = array('d.id'=>$hotel_id,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
                $res_boxs = $m_box->getBoxInfo($fields, $where);
            }
            $add_datas = array();
            foreach ($res_boxs as $bv){
                $hotel_id = $bv['hotel_id'];
                $hotel_name = $bv['hotel_name'];
                $room_id = $bv['room_id'];
                $room_name = $bv['room_name'];
                $box_id = $bv['box_id'];
                $box_mac = $bv['mac'];
                $box_name = $bv['box_name'];
                $timedata = array('openid'=>$openid,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,
                    'room_id'=>$room_id,'room_name'=>$room_name,'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,
                    'usertask_id'=>$task_user_id,'task_id'=>$task_id,'task_type'=>25,'ads_id'=>$ads_id,'timing'=>$now_paly_time,
                    'status'=>1,'mobile_brand'=>$mobile_brand,'mobile_model'=>$mobile_model
                );
                $add_datas[]=$timedata;
            }
            $m_timeplay = new \Common\Model\Smallapp\TimeplayModel();
            $m_timeplay->addAll($add_datas);
        }
        $this->to_back(array('is_pop_tips_wind'=>0));
    }

    public function finishDemandadvTask(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $ads_id = $this->params['ads_id'];
        $hotel_id = $this->params['hotel_id'];
        $task_id = $this->params['task_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_task = new \Common\Model\Integral\TaskuserModel();
        $task_where = array('a.openid'=>$openid,'a.task_id'=>$task_id,'a.status'=>1,'task.type'=>2,'task.task_type'=>25);
        $start_time = date('Y-m-d 00:00:00');
        $end_time   = date('Y-m-d 23:59:59');
        $task_where['a.add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
        $res_task = $m_task->getUserTaskList('a.id,task.id as task_id,task.task_info,task.integral',$task_where,'a.id desc');
        if(empty($res_task)){
            $this->to_back(93070);
        }
        $task_user_id = $res_task[0]['id'];
        $task_content = json_decode($res_task[0]['task_info'],true);
        $lunch_start_time = $task_content['lunch_start_time'];
        $lunch_end_time = $task_content['lunch_end_time'];
        $dinner_start_time = $task_content['dinner_start_time'];
        $dinner_end_time = $task_content['dinner_end_time'];
        $box_finish_num = $task_content['box_finish_num'];
        $interval_time = $task_content['interval_time'];

        if($ads_id!=$task_content['ads_id']){
            $this->to_back(93215);
        }
        $now_time = date('Y-m-d H:i:s');
        $lunch_stime = date("Y-m-d {$lunch_start_time}:00");
        $lunch_etime = date("Y-m-d {$lunch_end_time}:00");
        $dinner_stime = date("Y-m-d {$dinner_start_time}:00");
        $dinner_etime = date("Y-m-d {$dinner_end_time}:59");
        $meal_stime = $meal_etime = '';
        if($now_time>=$lunch_stime && $now_time<=$lunch_etime){
            $meal_stime = $lunch_stime;
            $meal_etime = $lunch_etime;
        }elseif($now_time>=$dinner_stime && $now_time<=$dinner_etime){
            $meal_stime = $dinner_stime;
            $meal_etime = $dinner_etime;
        }
        if(empty($meal_stime) && empty($meal_etime)){
            $this->to_back(93216);
        }
        $m_usertask_record = new \Common\Model\Smallapp\UserTaskRecordModel();
        $where = array('hotel_id'=>$hotel_id,'task_id'=>$task_id,'box_mac'=>$box_mac);
        $where['add_time'] = array(array('EGT',$meal_stime),array('ELT',$meal_etime));
        $res_record = $m_usertask_record->getALLDataList('openid,add_time',$where,'id asc','','');
        $now_box_finish_num = 0;
        $last_demand_time = 0;
        $demand_openid = '';
        if(!empty($res_record)){
            foreach ($res_record as $k=>$v){
                $str_demand_time = strtotime($v['add_time']);
                if($k==0){
                    $demand_openid = $v['openid'];
                    $now_box_finish_num++;
                    $last_demand_time = $str_demand_time + $interval_time*60;
                }else{
                    if($str_demand_time>=$last_demand_time){
                        $demand_openid = $v['openid'];
                        $now_box_finish_num++;
                        $last_demand_time = $str_demand_time + $interval_time*60;
                    }
                }
            }
        }
        if($now_box_finish_num>=$box_finish_num){
            $msg = '当前电视此任务已被完成，请使用其他电视。';
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
        }
        $now_time = time();
        if($last_demand_time>$now_time){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $res_user = $m_user->getOne('*',array('openid'=>$demand_openid),'');
            $uname = $res_user['nickName'];
            $countdown_time = $last_demand_time - $now_time;
            $mtime = round($countdown_time/60);
            $msg = '本次任务已被'.$uname.'完成，新任务'.$mtime.'分钟后开始';
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
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
            $room_name = $room_info['name'];
            $box_id = $forscreen_info['box_id'];
            $box_name = $box_info['name'];
        }else{
            $where = array('a.mac'=>$box_mac,'a.flag'=>0,'a.state'=>1,'d.flag'=>0,'d.state'=>1);
            $fields = 'a.id as box_id,a.name as box_name,c.id as room_id,c.name as room_name,d.id as hotel_id,d.name as hotel_name';
            $rets = $m_box->getBoxInfo($fields, $where);
            $hotel_id = $rets[0]['hotel_id'];
            $hotel_name = $rets[0]['hotel_name'];
            $room_id = $rets[0]['room_id'];
            $room_name = $rets[0]['room_name'];
            $box_id = $rets[0]['box_id'];
            $box_name = $rets[0]['box_name'];
        }
        $usertask_record = array('openid'=>$openid,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,
            'room_id'=>$room_id,'room_name'=>$room_name,'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,
            'usertask_id'=>$task_user_id,'task_id'=>$task_id,'task_type'=>25,'type'=>1
        );
        $m_usertask_record->add($usertask_record);
        $this->to_back(array('is_pop_tips_wind'=>0));
    }

}