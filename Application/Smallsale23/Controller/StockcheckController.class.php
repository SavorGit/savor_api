<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class StockcheckController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'idcode'=>1001,'hotel_id'=>1001,'task_id'=>1001);
                break;
            case 'checkidcodes':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'idcodes'=>1001,'task_id'=>1001);
                break;
            case 'addcheckrecord':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'idcodes'=>1001,'task_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);

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
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_stock_check = new \Common\Model\Smallapp\StockcheckModel();
        $res_check = $m_stock_check->getInfo(array('hotel_id'=>$hotel_id,'task_id'=>$task_id));
        if(!empty($res_check)){
            $where = array('a.id'=>$res_check['staff_id']);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,user.nickName';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            $msg = "任务已失效!盘点任务已被{$res_staff[0]['nickName']}完成，下次要快点哦~";
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $fileds = 'a.id,a.type,stock.hotel_id,goods.id as goods_id,goods.name as goods_name';
        $res_stock = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $goods_id = 0;
        if(!empty($res_stock[0]['goods_id'])){
            $goods_id = $res_stock[0]['goods_id'];
        }
        $goods_name = '';
        if(!empty($res_stock[0]['goods_name'])){
            $goods_name = $res_stock[0]['goods_name'];
        }
        $this->to_back(array('idcode'=>$idcode,'goods_id'=>$goods_id,'goods_name'=>$goods_name,'is_pop_tips_wind'=>0));
    }

    public function checkidcodes(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);
        $idcodes = $this->params['idcodes'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock_check = new \Common\Model\Smallapp\StockcheckModel();
        $res_check = $m_stock_check->getInfo(array('hotel_id'=>$hotel_id,'task_id'=>$task_id));
        if(!empty($res_check)){
            $where = array('a.id'=>$res_check['staff_id']);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,user.nickName';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            $msg = "任务已失效!盘点任务已被{$res_staff[0]['nickName']}完成，下次要快点哦~";
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
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

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_SALE').'tasklock:'.$hotel_id.'_'.$task_id;

        $task_completed_key = $cache_key.':completed';
        $redis->set($task_completed_key, 0);
        $task_lock_key = $cache_key.':lock';
        $isLocked = $redis->setnx($task_lock_key, 1);
        if($isLocked){
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $srwhere = array('stock.hotel_id'=>$hotel_id,'a.dstatus'=>1,'a.type'=>7,'a.wo_status'=>array('in','1,2,4'));
            $res_wo = $m_stock_record->getStockRecordList('a.idcode',$srwhere,'','','a.idcode');
            $writeoff_idcodes = array();
            foreach ($res_wo as $v){
                $writeoff_idcodes[]=$v['idcode'];
            }
            $where = array('stock.hotel_id'=>$hotel_id,'stock.type'=>20,'stock.io_type'=>22,'a.dstatus'=>1);
            if(!empty($writeoff_idcodes)){
                $where['a.idcode'] = array('not in',$writeoff_idcodes);
            }
            $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,GROUP_CONCAT(a.type) as all_type';
            $res_allidcodes = $m_stock_record->getStockRecordList($fileds,$where,'','','a.idcode');
            $stock_check_num=$stock_check_hadnum=0;
            $now_idcodes = explode(',',$idcodes);
            $no_check_list = array();
            foreach ($res_allidcodes as $v){
                $all_types = explode(',',$v['all_type']);
                if(count($all_types)==1 && $all_types[0]==3){
                    continue;
                }
                if(!in_array(6,$all_types) && !in_array(7,$all_types)){
                    $stock_check_num++;
                    $is_check = 0;
                    if(in_array($v['idcode'],$now_idcodes)){
                        $is_check = 1;
                        $stock_check_hadnum++;
                    }
                    if($is_check==0){
                        $no_check_list[$v['goods_id']][]=array('goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'idcode'=>$v['idcode']);
                    }
                }else{
                    if(in_array(7,$all_types)){
                        $cwhere = array('a.idcode'=>$v['idcode'],'a.dstatus'=>1);
                        $res_code = $m_stock_record->getStockRecordList('a.type,a.wo_status',$cwhere,'a.id desc','0,1');
                        if($res_code[0]['wo_status']==3){
                            $stock_check_num++;
                            $is_check = 0;
                            if(in_array($v['idcode'],$now_idcodes)){
                                $is_check = 1;
                                $stock_check_hadnum++;
                            }
                            if($is_check==0){
                                $no_check_list[$v['goods_id']][]=array('goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'idcode'=>$v['idcode']);
                            }
                        }
                    }
                }
            }
            $no_check_num = 0;
            $datalist = array();
            foreach ($no_check_list as $k=>$v){
                $num = count($v);
                $no_check_num+=$num;
                $datalist[]=array('goods_id'=>$k,'goods_name'=>$v[0]['goods_name'],'num'=>$num);
            }
            $redis->set($task_completed_key, $openid,86400);
            $redis->remove($task_lock_key);

            $this->to_back(array('no_check_num'=>$no_check_num,'datalist'=>$datalist,'is_pop_tips_wind'=>0));
        }else{
            $msg = "任务已失效!盘点任务已被其他人完成，下次要快点哦~";
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
        }
    }

    public function addcheckrecord(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = intval($this->params['task_id']);
        $idcodes = $this->params['idcodes'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock_check = new \Common\Model\Smallapp\StockcheckModel();
        $res_check = $m_stock_check->getInfo(array('hotel_id'=>$hotel_id,'task_id'=>$task_id));
        if(!empty($res_check)){
            $where = array('a.id'=>$res_check['staff_id']);
            $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id,user.nickName';
            $res_staff = $m_staff->getMerchantStaff($fields,$where);
            $msg = "任务已失效!盘点任务已被{$res_staff[0]['nickName']}完成，下次要快点哦~";
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
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

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_SALE').'tasklock:'.$hotel_id.'_'.$task_id;
        $task_completed_key = $cache_key.':completed';
        $cache_finish_user = $redis->get($task_completed_key);
        if(!empty($cache_finish_user) && $openid!=$cache_finish_user){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $res_user = $m_user->getOne('nickName',array('openid'=>$cache_finish_user),'');
            $msg = "任务已失效!盘点任务已被{$res_user['nickName']}完成，下次要快点哦~";
            $res_pdata = array('is_pop_tips_wind'=>1,'msg'=>$msg);
            $this->to_back($res_pdata);
        }

        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $srwhere = array('stock.hotel_id'=>$hotel_id,'a.dstatus'=>1,'a.type'=>7,'a.wo_status'=>array('in','1,2,4'));
        $res_wo = $m_stock_record->getStockRecordList('a.idcode',$srwhere,'','','a.idcode');
        $writeoff_idcodes = array();
        foreach ($res_wo as $v){
            $writeoff_idcodes[]=$v['idcode'];
        }
        $where = array('stock.hotel_id'=>$hotel_id,'stock.type'=>20,'stock.io_type'=>22,'a.dstatus'=>1);
        if(!empty($writeoff_idcodes)){
            $where['a.idcode'] = array('not in',$writeoff_idcodes);
        }
        $fileds = 'a.idcode,goods.id as goods_id,GROUP_CONCAT(a.type) as all_type';
        $res_allidcodes = $m_stock_record->getStockRecordList($fileds,$where,'','','a.idcode');
        $stock_check_num=$stock_check_hadnum=0;
        $now_idcodes = explode(',',$idcodes);
        $check_list = array();
        foreach ($res_allidcodes as $v){
            $all_types = explode(',',$v['all_type']);
            if(count($all_types)==1 && $all_types[0]==3){
                continue;
            }
            if(!in_array(6,$all_types) && !in_array(7,$all_types)){
                $stock_check_num++;
                $is_check = 0;
                if(in_array($v['idcode'],$now_idcodes)){
                    $is_check = 1;
                    $stock_check_hadnum++;
                }
                $check_list[$v['idcode']]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'hotel_id'=>$hotel_id,'idcode_hotel_id'=>$hotel_id,
                    'is_check'=>$is_check,'type'=>1,'desc'=>'');
            }else{
                if(in_array(7,$all_types)){
                    $cwhere = array('a.idcode'=>$v['idcode'],'a.dstatus'=>1);
                    $res_code = $m_stock_record->getStockRecordList('a.type,a.wo_status',$cwhere,'a.id desc','0,1');
                    if($res_code[0]['wo_status']==3){
                        $stock_check_num++;
                        $is_check = 0;
                        if(in_array($v['idcode'],$now_idcodes)){
                            $is_check = 1;
                            $stock_check_hadnum++;
                        }
                        $check_list[$v['idcode']]=array('idcode'=>$v['idcode'],'goods_id'=>$v['goods_id'],'hotel_id'=>$hotel_id,'idcode_hotel_id'=>$hotel_id,
                            'is_check'=>$is_check,'type'=>1,'desc'=>'');
                    }
                }
            }
        }
        $now_other_idcodes = array();
        foreach ($now_idcodes as $v){
            if(!isset($check_list[$v])){
                $now_other_idcodes[]=$v;
            }
        }
        $stock_check_error = 1;
        if(!empty($now_other_idcodes)){
            $stock_check_error = 2;
            $m_hotel = new \Common\Model\HotelModel();
            foreach ($now_other_idcodes as $v){
                $where = array('a.idcode'=>$v,'a.dstatus'=>1);
                $fileds = 'a.id,a.type,stock.hotel_id,goods.id as goods_id';
                $res_srecord = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
                $desc = '';
                if($res_srecord[0]['type']==1){
                    $desc = '未出库到餐厅';
                }elseif($res_srecord[0]['type']==7){
                    $desc = '酒水已经核销';
                }elseif($res_srecord[0]['hotel_id']!=$hotel_id){
                    $res_hotel = $m_hotel->getOneById('id,name',$res_srecord[0]['hotel_id']);
                    $desc = "属于{$res_hotel['name']}的酒水";
                }
                $check_list[$v]=array('idcode'=>$v,'goods_id'=>$res_srecord[0]['goods_id'],'hotel_id'=>$hotel_id,
                    'idcode_hotel_id'=>$res_srecord[0]['hotel_id'],'is_check'=>0,'type'=>2,'desc'=>$desc);
            }
        }
        $stock_check_errornum = count($now_other_idcodes);
        $stock_check_success_status = 0;
        $stock_check_status = 2;
        if($stock_check_error==2){
            if($stock_check_num==$stock_check_hadnum){
                $stock_check_success_status = 22;
            }else{
                if($stock_check_errornum==0){
                    $stock_check_success_status = 23;
                }else{
                    $stock_check_success_status = 24;
                }
            }
        }else{
            if($stock_check_num==$stock_check_hadnum){
                if($stock_check_errornum==0){
                    $stock_check_success_status = 21;
                }else{
                    $stock_check_success_status = 22;
                }
            }else{
                $stock_check_success_status = 23;
            }
        }
        $task_user_id = $res_usertask['id'];
        $add_data = array('staff_id'=>$res_staff[0]['id'],'hotel_id'=>$hotel_id,'task_user_id'=>$task_user_id,'task_id'=>$task_id,
            'stock_check_num'=>$stock_check_num,'stock_check_hadnum'=>$stock_check_hadnum,'stock_check_status'=>$stock_check_status,'stock_check_success_status'=>$stock_check_success_status,
            'stock_check_error'=>$stock_check_error,'stock_check_errornum'=>$stock_check_errornum);
        $stockcheck_id = $m_stock_check->add($add_data);
        if($stock_check_num>0){
            $record_data = array();
            foreach ($check_list as $v){
                $v['stockcheck_id'] = $stockcheck_id;
                $record_data[]=$v;
            }
            $m_stock_check_record = new \Common\Model\Smallapp\StockcheckRecordModel();
            $m_stock_check_record->addAll($record_data);
        }
        if($stock_check_success_status==21){
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $now_integral = $m_userintegral->finishStockCheckTask($openid,$stockcheck_id,$task_user_id);
            if($now_integral>0){
                $m_stock_check->updateData(array('id'=>$stockcheck_id),array('integral'=>$now_integral,'is_get_integral'=>1,
                    'get_time'=>date('Y-m-d H:i:s')));
                $m_usertask->updateData(array('id'=>$res_usertask['id']),array('status'=>3));
            }
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelById('hotel.area_id,ext.maintainer_id',array('hotel.id'=>$hotel_id));
        $area_id = $res_hotel['area_id'];

        $owhere = array('area_id'=>$area_id,'hotel_role_type'=>array('in','2,4'),'is_operrator'=>0,'status'=>1);
        $no_ids = array('7');
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $mssage_data = array();
        if($res_hotel['maintainer_id']>0){
            $res_ops_staf = $m_opstaff->getInfo(array('sysuser_id'=>$res_hotel['maintainer_id'],'status'=>1));
            if(!empty($res_ops_staf)){
                $no_ids[]=$res_ops_staf['id'];
                $mssage_data[] = array('staff_openid'=>$openid,'ops_staff_id'=>$res_ops_staf['id'],
                    'hotel_id'=>$hotel_id,'content_id'=>$stockcheck_id,'type'=>13,'read_status'=>1);
            }
        }
        $owhere['id'] = array('not in',$no_ids);
        $res_mdata = $m_opstaff->getDataList('id,openid',$owhere,'id desc');
        foreach ($res_mdata as $v){
            $mssage_data[] = array('staff_openid'=>$openid,'ops_staff_id'=>$v['id'],'hotel_id'=>$hotel_id,
                'content_id'=>$stockcheck_id,'type'=>13,'read_status'=>1);
        }
        $m_message = new \Common\Model\Smallapp\MessageModel();
        $m_message->addAll($mssage_data);
        $this->to_back(array('stockcheck_id'=>$stockcheck_id,'is_pop_tips_wind'=>0));
    }

}