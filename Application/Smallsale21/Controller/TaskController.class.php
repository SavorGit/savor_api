<?php
namespace Smallsale21\Controller;
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
                $this->valid_fields = array('hotel_id'=>1001,'task_id'=>1001,'openid'=>1001);
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
            case 'finishDemandadvTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'box_mac'=>1001,'ads_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getTaskList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1,'merchant.type'=>3);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $field_staff = 'a.openid,a.level,a.id as staff_id,merchant.type';
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
            $m_staff = new \Common\Model\Integral\StaffModel();
            $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
            if(empty($res_staff)){
                $this->to_back(93014);
            }
        }
        $m_task_user = new \Common\Model\Integral\TaskuserModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $fields = "a.id as task_user_id,task.id task_id,task.name task_name,task.goods_id,task.integral,concat('".$oss_host."',media.`oss_addr`) img_url,concat('".$oss_host."',task.`image_url`) wimg_url,
        task.desc,task.is_shareprofit,task.task_type,task.task_info,task.people_num,task.status,task.flag,task.end_time as task_expire_time,a.people_num as join_peoplenum";
        $activity_task_types = array(22,23,24,25);
        $where = array('a.openid'=>$openid,'a.status'=>1,'task.task_type'=>array('in',$activity_task_types));
        $m_media = new \Common\Model\MediaModel();
        $res_inprogress_task = $m_task_user->getUserTaskList($fields,$where,'a.id desc');
        $inprogress_task = $invalid_task = $finish_task = array();
        $m_dishgoods = new \Common\Model\Smallapp\DishgoodsModel();
        if(!empty($res_inprogress_task)){
            $now_time = date('Y-m-d H:i:s');
            $m_activity = new \Common\Model\Smallapp\ActivityModel();
            $m_ads = new \Common\Model\AdsModel();
            foreach ($res_inprogress_task as $k=>$v){
                $task_info = $v['task_info'];
                unset($v['task_info']);
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
                            $task_info = json_decode($task_info,true);
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
                }
            }
        }
        $m_task_hotel = new \Common\Model\Integral\TaskHotelModel();
        $fields = "task.id task_id,task.name task_name ,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc,task.is_shareprofit,task.task_type";
        $where = array('a.hotel_id'=>$hotel_id,'task.type'=>1,'task.status'=>1,'task.flag'=>1);
        $order = 'task.id asc';
        $all_inprogress_task = $m_task_hotel->getHotelTaskList($fields,$where,$order,0,1000);
        $start_time = date('Y-m-d 00:00:00');
        $end_time   = date('Y-m-d 23:59:59');
        foreach($all_inprogress_task as $key=>$v){
            $map = array('openid'=>$openid,'task_id'=>$v['task_id']);
            $map['add_time'] = array(array('EGT',$start_time),array('ELT',$end_time));
            $rs = $m_task_user->field('integral')->where($map)->find();
            $all_inprogress_task[$key]['integral'] = intval($rs['integral']);
            $all_inprogress_task[$key]['progress'] = '今日获得积分';
        }
        if(!empty($inprogress_task)){
            $all_inprogress_task = array_merge(array_values($inprogress_task),$all_inprogress_task);
        }

        $fields = "task.id task_id,task.name task_name,task.goods_id,task.integral,task.task_type,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc,task.is_shareprofit,task.end_time as task_expire_time,a.staff_id";
        $where = array('a.hotel_id'=>$hotel_id,'task.task_type'=>array('in',$activity_task_types),'task.status'=>1,'task.flag'=>1);
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
                $v['percent'] = ($send_num/$all_people_num)*100;
                $v['remain_percent'] = 100-$v['percent'];
                if($v['task_type']==23){
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
                }else{
                    if($v['task_type']==24) {
                        $res_goods = $m_dishgoods->getInfo(array('id'=>$v['goods_id']));
                        $v['price'] = $res_goods['price'];
                    }
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

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type,merchant.id as merchant_id,merchant.is_integral';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_usertask = new \Common\Model\Integral\TaskuserModel();
        $where = array('openid'=>$openid,'task_id'=>$task_id,'status'=>1);
        $res_usertask = $m_usertask->getInfo($where);
        if(!empty($res_usertask)){
            $this->to_back(93069);
        }
        $m_hoteltask = new \Common\Model\Integral\TaskHotelModel();
        $where = array('a.task_id'=>$task_id,'task.status'=>1,'task.flag'=>1);
        $fileds = 'task.id as task_id,task.name,task.media_id,task.end_time,task.task_integral,task.task_type';
        $res_task = $m_hoteltask->getHotelTasks($fileds,$where);
        if(empty($res_task)){
            $this->to_back(93070);
        }
        $now_time = date('Y-m-d H:i:s');
        if($res_task[0]['end_time']<$now_time){
            $this->to_back(93071);
        }
        $data = array('openid'=>$openid,'task_id'=>$task_id,'integral'=>$res_task[0]['task_integral']);
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
        $this->to_back(array('user_task_id'=>$user_task_id));
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

        $oss_host = C('OSS_HOST');
        $task_user_id = $res_usertask['id'];
        $width_img = 'http://'.$oss_host.'/'.$res_goods['cover_imgs'];
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
        $oss_host = 'http://'. C('OSS_HOST').'/';
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

    public function finishDemandadvTask(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $ads_id = $this->params['ads_id'];
        $hotel_id = $this->params['hotel_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,a.level,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $res_task = $m_userintegral_record->finishDemandAdvTask($openid,$ads_id,$box_mac);

        $this->to_back($res_task);
    }

}