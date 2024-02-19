<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class TaskController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getSalerecordTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'visit_purpose_id'=>1002,'salerecord_id'=>1002,'version'=>1002);
                break;
            case 'filterconditions':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'task_id'=>1001,'page'=>1001,'area_id'=>1002,'staff_id'=>1002,'stat_date'=>1002,'order_type'=>1002,'version'=>1002);
                break;
            case 'getHotelTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'area_id'=>1002,'staff_id'=>1002,'stat_date'=>1002,'is_overdue'=>1002,'residenter_id'=>1002);
                break;
            case 'handletask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'task_record_id'=>1001,'status'=>1001,'content'=>1002,'img'=>1002);
                break;
            case 'pendingTasks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001);
                break;
            case 'getTasks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001,'task_id'=>1002,'area_id'=>1002,'staff_id'=>1002,'stat_date'=>1002);
                break;
            case 'getOverdueTasks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'handleRefuseTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'task_record_id'=>1001,'content'=>1002);
                break;
            case 'addCustomTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'desc'=>1001,'staff_ids'=>1001,'is_upimg'=>1001,'is_check_location'=>1001,
                    'finish_day'=>1001,'start_time'=>1002);
                break;
            case 'myReleaseTasks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001);
                break;
            case 'arrangeTasks':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'task_record_id'=>1001);
                break;
            case 'finishTask':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'task_record_id'=>1001,'hotel_id'=>1002,'img'=>1002,'content'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getSalerecordTask(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $visit_purpose_id = intval($this->params['visit_purpose_id']);
        $salerecord_id = intval($this->params['salerecord_id']);
        $version = $this->params['version'];
        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_hotel = new \Common\Model\HotelExtModel();
        $res_hotel = $m_hotel->getOnerow(array('hotel_id'=>$hotel_id));
        $is_salehotel = intval($res_hotel['is_salehotel']);

        if($salerecord_id>0){
            $m_salerecord_task = new \Common\Model\Crm\SalerecordTaskModel();
            $fileds = 'a.task_record_id,task.name as task_name,a.handle_status,a.content,task.desc,task.type,a.img,task.is_upimg,task.is_check_location,tr.remind_content';
            $task_list = $m_salerecord_task->getSalerecordTask($fileds,array('a.salerecord_id'=>$salerecord_id));
        }else{
            $start_time = date('Y-m-01 00:00:00');
            $end_time = date('Y-m-31 23:59:59');
            $residenter_id = $res_staff['sysuser_id'];
            $fileds = 'a.id as task_record_id,task.name as task_name,a.handle_status,a.content,task.desc,task.type,a.img,task.is_upimg,task.is_check_location,a.remind_content';
            $where = array('a.hotel_id'=>$hotel_id,'a.residenter_id'=>$residenter_id,'a.handle_status'=>array('in','0,1'),
                'a.audit_handle_status'=>array('in','0,1'),'a.status'=>array('in','0,1'));
            $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
            $task_list = $m_crmtask_record->getTaskRecords($fileds,$where,'task.id asc');
        }
        $is_has_task = 0;
        if(!empty($task_list)){
            $is_has_task = 1;
            foreach ($task_list as $k=>$v){
                $task_list[$k]['img_arr'] = explode(',',$v['img']);
            }
        }
        $content_default = array();
        if($is_salehotel){
            if($visit_purpose_id==182){
                $content_default = C('CONTENT_DEFAULT');
            }elseif($visit_purpose_id==183){
                $content_default = C('CONTENT_DEFAULT_RESIDENT');
            }
        }
        $res_data = array('is_has_task'=>$is_has_task,'task_list'=>$task_list,'is_salehotel'=>$is_salehotel,
            'content_default'=>$content_default);
        $this->to_back($res_data);
    }

    public function filterconditions(){
        $openid = $this->params['openid'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务

        $month_list = array(array('name'=>'本月','value'=>date('Y-m')));
        for($i=1;$i<12;$i++){
            $name = date('Y年m月',strtotime("last day of -$i month"));
            $value = date('Y-m',strtotime("last day of -$i month"));
            $month_list[]=array('name'=>$name,'value'=>$value);
        }
        $m_task = new \Common\Model\Crm\TaskModel();
        $task_list = $m_task->getDataList('id,name',array('status'=>1,'type'=>array('neq',11)),'id desc');
        array_unshift($task_list,array('id'=>0,'name'=>'全部任务'));

        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $pending_task_num = 0;
        if(in_array($hotel_role_type,array(2,4))){
            $permission = json_decode($res_staff['permission'],true);
            $area_ids = $permission['hotel_info']['area_ids'];
            $where = array('hotel.area_id'=>array('in',$area_ids),'a.residenter_id'=>array('gt',0));
            $where['a.is_trigger'] = 1;
            $where['a.status'] = array('in','0,1');
            $fileds = "count(a.id) as num";
            $res_task_num = $m_crmtask_record->getTaskRecords($fileds,$where);
            $pending_task_num1 = intval($res_task_num[0]['num']);
            unset($where['a.is_trigger'],$where['a.status']);
            $where['a.handle_status'] = 1;
            $where['a.audit_handle_status'] = 0;
            $res_task_num = $m_crmtask_record->getTaskRecords($fileds,$where);
            $pending_task_num2 = intval($res_task_num[0]['num']);
            $pending_task_num = $pending_task_num1+$pending_task_num2;
        }
        $release_task_num = 0;
        if(in_array($hotel_role_type,array(1,2,4))){
            $res_release_task = $m_task->getDataList('count(id) as num',array('ops_staff_id'=>$res_staff['id']),'');
            $release_task_num = intval($res_release_task[0]['num']);
        }

        $arrange_task_num = 0;
        $arrange_task_name = '';
        if($hotel_role_type==3){
            $where = array('task.type'=>11,'a.residenter_id'=>$res_staff['sysuser_id']);
            $where['task.start_time'] = array('elt',date('Y-m-d H:i:s'));
            $fileds = "count(a.id) as num,a.status";
            $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'','','a.status');
            $all_arrange_num = 0;
            foreach ($res_task as $v){
                $all_arrange_num+=$v['num'];
                if($v['status']!=3){
                    $arrange_task_num+=$v['num'];
                }
            }
            if($all_arrange_num){
                $arrange_task_name = '布置的任务';
                if($arrange_task_num){
                    $where['a.status'] = array('in','0,1,2');
                    $res_task = $m_crmtask_record->getTaskRecords('task.ops_staff_id',$where,'','','task.ops_staff_id');
                    $ops_staff_ids = array();
                    foreach ($res_task as $v){
                        $ops_staff_ids[]=$v['ops_staff_id'];
                    }
                    $fields = 'su.remark as staff_name';
                    $res_all_staff = $m_opstaff->getStaffinfo($fields,array('a.id'=>array('in',$ops_staff_ids)));
                    $all_staff_names = array();
                    foreach ($res_all_staff as $sv){
                        $all_staff_names[]=$sv['staff_name'];
                    }
                    $staff_names = join('、',$all_staff_names);
                    $arrange_task_name = $staff_names.$arrange_task_name;
                }
            }
        }

        $this->to_back(array('task_list'=>$task_list,'month_list'=>$month_list,'pending_task_num'=>$pending_task_num,
            'release_task_num'=>$release_task_num,'arrange_task_num'=>$arrange_task_num,'arrange_task_name'=>$arrange_task_name));
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $task_id = $this->params['task_id'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $page = intval($this->params['page']);
        $pagesize = 20;
        $stat_date = $this->params['stat_date'];
        $order_type = $this->params['order_type'];
        $version   = $this->params['version'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $staff_residenter_id = $res_staff['sysuser_id'];
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);
        $where = array('a.off_state'=>1,'ext.is_salehotel'=>1,'a.status'=>array('in','0,1'),'task.type'=>array('neq',11));
        if($task_id>0){
            $where['a.task_id'] = $task_id;
        }
        if(in_array($hotel_role_type,array(2,4,6))){
            if($res_staff['is_operrator']==1){
                $where['a.residenter_id'] = $res_staff['sysuser_id'];
            }else{
                $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }
        }elseif($hotel_role_type==3){
            $where['a.residenter_id'] = $res_staff['sysuser_id'];
        }
        if($area_id>0){
            $where['hotel.area_id'] = $area_id;
        }
        if($staff_id>0){
            if($staff_id==99999){
                $where['a.residenter_id'] = 0;
            }else{
                $res_residenter = $m_staff->getInfo(array('id'=>$staff_id));
                $where['a.residenter_id'] = $res_residenter['sysuser_id'];
            }
        }
        if(empty($stat_date)){
            $stat_date = date('Y-m');
        }
        $start_time = $stat_date.'-01 00:00:00';
        $end_time = $stat_date.'-31 23:59:59';
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));

        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        if(!empty($version) && $version>='1.0.22'){
            switch ($order_type){
                case 1:
                    $orders = 'ext.sale_cqmoney desc ,ext.sale_ysmoney desc';
                    break;
                case 2:
                    $orders = 'ext.sale_hotel_in_time desc';
                    break;
                case 3:
                    $orders = 'ext.sale_not_day desc';
                    break;
                case 4:
                    $orders = 'ext.sale_decline_percent asc ';
                    break;
                default:
                    $orders = 'hotel.pinyin asc';
            }
        }else {
            $orders = 'hotel.pinyin asc';
        }
        $fileds = "a.hotel_id,hotel.name as hotel_name,ext.residenter_id,count(a.id) as num,group_concat(a.id,'-',a.status,'-',task.type Separator ',') as gtype";
        $res_data = $m_crmtask_record->getTaskRecords($fileds,$where,$orders,'','a.hotel_id');
        $head_data = array();
        $other_data = array();
        $all_types = C('CRM_TASK_TYPES');
        $m_message = new \Common\Model\Smallapp\MessageModel();
        foreach ($res_data as $v){
            $is_head = 0;
            $task_type_name = '';
            $gtype_arr = explode(',',$v['gtype']);
            foreach ($gtype_arr as $gv){
                $garr = explode('-',$gv);
                if($garr[2]==4 && $garr[1]!=3){
                    $is_head = 1;
                    $task_type_name = $all_types[$garr[2]];
                    break;
                }
            }
            $v['task_type_name'] = $task_type_name;
            $is_hotel_residenter = 0;
            $message_num = 0;
            if($v['residenter_id']==$staff_residenter_id){
                $is_hotel_residenter = 1;
                $mwhere = array('ops_staff_id'=>$res_staff['id'],'type'=>19,'hotel_id'=>$v['hotel_id'],'read_status'=>1);
                $res_message = $m_message->getDataList('count(id) as num',$mwhere,'');
                $message_num = intval($res_message[0]['num']);
            }
            $v['is_hotel_residenter'] = $is_hotel_residenter;
            $v['message_num'] = $message_num;
            unset($v['gtype']);
            if($is_head){
                $head_data[]=$v;
            }else{
                $other_data[]=$v;
            }
        }
        $all_datas = array_merge($head_data,$other_data);
        $offset = ($page-1)*$pagesize;
        $data_list = array_slice($all_datas,$offset,$pagesize);
        $this->to_back(array('datalist'=>$data_list));
    }

    public function getHotelTask(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $task_id = $this->params['task_id'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $residenter_id = intval($this->params['residenter_id']);
        $is_overdue = intval($this->params['is_overdue']);
        $stat_date = $this->params['stat_date'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if(empty($stat_date)){
            $stat_date = date('Y-m');
        }
        $start_time = $stat_date.'-01 00:00:00';
        $end_time = $stat_date.'-31 23:59:59';

        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);
        $where = array('a.hotel_id'=>$hotel_id,'a.off_state'=>1);
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        if($is_overdue==0){
            if($task_id>0){
                $where['a.task_id'] = $task_id;
            }
            if(in_array($hotel_role_type,array(2,4,6))){
                if($res_staff['is_operrator']==1){
                    $where['a.residenter_id'] = $res_staff['sysuser_id'];
                }else{
                    $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                }
            }elseif($hotel_role_type==3){
                $where['a.residenter_id'] = $res_staff['sysuser_id'];
            }
            if($area_id>0){
                $where['hotel.area_id'] = $area_id;
            }
            if($staff_id>0){
                if($staff_id==99999){
                    $where['a.residenter_id'] = 0;
                }else{
                    $res_residenter = $m_opsstaff->getInfo(array('id'=>$staff_id));
                    $where['a.residenter_id'] = $res_residenter['sysuser_id'];
                }
            }
            $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        }elseif($is_overdue==1){
            if($residenter_id){
                $where['a.residenter_id'] = $residenter_id;
            }
            $where['a.is_trigger'] = 1;
            $where['a.status'] = array('in','0,1');
        }elseif($is_overdue==2){
            if($task_id>0){
                $where['a.task_id'] = $task_id;
            }
            $where['a.status'] = array('neq',3);
            $where['a.is_trigger'] = 1;
            $where['a.handle_status'] = array('in','0,2');
            $where['a.add_time'] = array('elt',$end_time);
            $where['a.finish_task_record_id'] = 0;
            $where['task.type'] = array('neq',7);
            $res_taskids = $m_crmtask_record->getTaskRecords('a.hotel_id,a.task_id,GROUP_CONCAT(a.id) as ids,max(a.id) as last_id',$where,'','','a.task_id');
            $all_ids = array();
            if(!empty($res_taskids)) {
                foreach ($res_taskids as $v) {
                    $all_ids[] = $v['last_id'];
                }
                $where = array('a.id'=>array('in',$all_ids),'a.residenter_id'=>$residenter_id);
            }
        }

        $fileds = 'a.id as task_record_id,task.name as task_name,a.status,a.content,a.remind_content,a.handle_time,a.finish_time,a.audit_time,task.type,task.desc,a.sale_date,a.add_time';
        $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'a.id desc');
        $unhandle_list = $handle_list = array();
        $all_status_map = array('1'=>'进行中','2'=>'未完成','3'=>'已完成');
        $task_help_desc = C('TASK_HELP_DESC');
        foreach ($res_task as $v){
            if($v['type']==4){
                $sale_date = $v['sale_date'];
                $dateTime = \DateTime::createFromFormat('Ymd',"$sale_date");
                $sale_date_time = $dateTime->format('Y-m-d');
                $v['task_name'] = $v['task_name']."($sale_date_time)";
            }
            $status_str = '';
            if(isset($all_status_map[$v['status']])){
                $status_str = $all_status_map[$v['status']];
            }
            if($v['handle_time']=='0000-00-00 00:00:00'){
                $v['handle_time'] = '';
            }
            if($v['finish_time']=='0000-00-00 00:00:00'){
                $v['finish_time'] = '';
            }
            if($v['audit_time']=='0000-00-00 00:00:00'){
                $v['audit_time'] = '';
            }
            $v['status_str'] = $status_str;
            $v['task_help_desc'] = $task_help_desc[$v['type']];
            if($v['status']==0){
                $unhandle_list[]=$v;
            }else{
                $handle_list[]=$v;
            }
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('name',$hotel_id);
        $res_data = array('hotel_id'=>$hotel_id,'hotel_name'=>$res_hotel['name'],'unhandle_list'=>$unhandle_list,'handle_list'=>$handle_list);
        $this->to_back($res_data);
    }

    public function handletask(){
        $openid = $this->params['openid'];
        $task_record_id = intval($this->params['task_record_id']);
        $handle_status = intval($this->params['status']);
        $content = trim($this->params['content']);
        $img = $this->params['img'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $residenter_id = $res_staff['sysuser_id'];
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $res_data = $m_crmtask_record->getInfo(array('id'=>$task_record_id));
        if($res_data['residenter_id']==$residenter_id){
            if($handle_status==2){
                $status = 1;
            }else{
                $status = 0;
            }
            $updata = array('status'=>$status,'handle_status'=>$handle_status,'handle_time'=>date('Y-m-d H:i:s'));
            if(!empty($content)){
                $updata['content'] = $content;
            }
            if(!empty($img)){
                $updata['img'] = $img;
            }
            $m_crmtask_record->updateData(array('id'=>$task_record_id),$updata);
        }else{
            $task_record_id = 0;
        }
        $this->to_back(array('task_record_id'=>$task_record_id));
    }


    public function pendingTasks(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//1超期,2拒绝申请
        $page = $this->params['page'];
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        if(!in_array($hotel_role_type,array(2,4))){
            $this->to_back(array());
        }
        $permission = json_decode($res_staff['permission'],true);
        $area_ids = $permission['hotel_info']['area_ids'];
        $where = array('hotel.area_id'=>array('in',$area_ids),'a.residenter_id'=>array('gt',0),'a.off_state'=>1);
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $datalist = array();
        $offset = ($page-1)*$pagesize;
        if($type==1){
            $where['a.is_trigger'] = 1;
            $where['a.status'] = array('in','0,1');
            $fileds = "a.residenter_id,a.residenter_name,count(DISTINCT a.hotel_id) as hotel_num,count(a.id) as num,group_concat(a.id) as tids";
            $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'',"$offset,$pagesize",'a.residenter_id');
            $all_types = C('CRM_TASK_TYPES');
            foreach ($res_task as $v){
                $hfileds = "a.hotel_id,hotel.name as hotel_name,count(a.id) as num,group_concat(task.type) as types";
                $res_hoteltask = $m_crmtask_record->getTaskRecords($hfileds,array('a.id'=>array('in',$v['tids'])),'','','a.hotel_id');
                $hotel_list = array();
                foreach ($res_hoteltask as $tv){
                    $types_arr = explode(',',$tv['types']);
                    $task_type_name = '';
                    if(in_array(3,$types_arr)){
                        $task_type_name = $all_types[3];
                    }elseif(in_array(4,$types_arr)){
                        $task_type_name = $all_types[4];
                    }
                    $hotel_list[]=array('hotel_id'=>$tv['hotel_id'],'hotel_name'=>$tv['hotel_name'],'hotel_task_num'=>$tv['num'],'task_type_name'=>$task_type_name);
                }
                $dinfo = array('residenter_id'=>$v['residenter_id'],'residenter_name'=>$v['residenter_name'],
                    'hotel_num'=>$v['hotel_num'],'task_num'=>$v['num'],'task_list'=>$hotel_list);
                $datalist[]=$dinfo;
            }
        }elseif($type==2){
            $where['a.status'] = array('in','0,1');
            $where['a.handle_status'] = 1;
            $where['a.audit_handle_status'] = array('in','0,1');
            $fileds = "a.residenter_id,a.residenter_name,count(a.id) as num,group_concat(a.id) as tids";
            $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'',"$offset,$pagesize",'a.residenter_id');
            foreach ($res_task as $v){
                $hfileds = "a.id as task_record_id,hotel.name as hotel_name,task.name as task_name,a.content";
                $task_list = $m_crmtask_record->getTaskRecords($hfileds,array('a.id'=>array('in',$v['tids'])),'a.hotel_id desc','','');
                $dinfo = array('residenter_id'=>$v['residenter_id'],'residenter_name'=>$v['residenter_name'],
                    'task_num'=>$v['num'],'task_list'=>$task_list);
                $datalist[]=$dinfo;
            }
        }
        $this->to_back(array('type'=>$type,'datalist'=>$datalist));
    }

    public function handleRefuseTask(){
        $openid = $this->params['openid'];
        $task_record_id = intval($this->params['task_record_id']);
        $audit_handle_status = intval($this->params['status']);//审核处理状态1拒绝,2同意

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        if(!in_array($hotel_role_type,array(2,4))){
            $this->to_back(array());
        }
        $updata = array('audit_handle_status'=>$audit_handle_status,'audit_time'=>date('Y-m-d H:i:s'));
        if($audit_handle_status==1){
            $updata['handle_status'] = 0;
        }elseif($audit_handle_status==2){
            $updata['handle_status'] = 1;
            $updata['status'] = 2;
        }
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $m_crmtask_record->updateData(array('id'=>$task_record_id),$updata);
        $this->to_back(array());
    }

    public function addCustomTask(){
        $openid = $this->params['openid'];
        $name = trim($this->params['name']);
        $desc = trim($this->params['desc']);
        $staff_ids = $this->params['staff_ids'];
        $is_upimg = intval($this->params['is_upimg']);
        $is_check_location = intval($this->params['is_check_location']);
        $task_finish_day = intval($this->params['finish_day']);
        $start_time = $this->params['start_time'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        if(empty($start_time)){
            $start_time = date('Y-m-d 00:00:00');
        }else{
            $start_time = date('Y-m-d 00:00:00',strtotime($start_time));
        }
        $end_time = date('Y-m-d 23:59:59',strtotime($start_time)+86400*$task_finish_day);
        $add_data = array('name'=>$name,'desc'=>$desc,'is_upimg'=>$is_upimg,'is_check_location'=>$is_check_location,'ops_staff_id'=>$ops_staff_id,
            'task_finish_day'=>$task_finish_day,'start_time'=>$start_time,'end_time'=>$end_time,'status'=>1,'type'=>11);
        $m_crm_task = new \Common\Model\Crm\TaskModel();
        $task_id = $m_crm_task->add($add_data);

        $staff_ids_arr = explode(',',$staff_ids);
        $fields = 'a.sysuser_id as residenter_id,su.remark as residenter_name';
        $res_all_staff = $m_opsstaff->getStaffinfo($fields,array('a.id'=>array('in',$staff_ids_arr)));

        $trdatas = array();
        foreach ($res_all_staff as $v){
            $trdatas[] = array('task_id'=>$task_id,'residenter_id'=>$v['residenter_id'],'residenter_name'=>$v['residenter_name']);
        }
        if(!empty($trdatas)){
            $m_crm_taskrecord = new \Common\Model\Crm\TaskRecordModel();
            $m_crm_taskrecord->addAll($trdatas);
        }
        $this->to_back(array('task_id'=>$task_id));
    }

    public function myReleaseTasks(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);//1待完成,2已完成
        $page = $this->params['page'];
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $where = array('task.ops_staff_id'=>$res_staff['id'],'task.type'=>11);
        if($status==1){
            $where['a.status'] = array('in','0,1,2');
        }else{
            $where['a.status'] = 3;
        }

        $fileds = "a.residenter_id,a.residenter_name,count(a.id) as num,group_concat(a.id) as tids";
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $offset = ($page-1)*$pagesize;
        $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'',"$offset,$pagesize",'a.residenter_id');
        $datalist = array();
        foreach ($res_task as $v){
            $hfileds = "a.id as task_record_id,task.name as task_name,task.desc,task.start_time,task.end_time";
            $task_list = $m_crmtask_record->getTaskRecords($hfileds,array('a.id'=>array('in',$v['tids'])),'a.id desc','','');
            foreach ($task_list as $tk=>$tv){
                $status_str = '';
                if(time()>strtotime($tv['end_time'])){
                    $status_str = '超期';
                }
                $task_list[$tk]['desc'] = text_substr($tv['desc'],50);
                $task_list[$tk]['status_str'] = $status_str;
            }
            $dinfo = array('residenter_id'=>$v['residenter_id'],'residenter_name'=>$v['residenter_name'],
                'task_num'=>$v['num'],'task_list'=>$task_list);
            $datalist[]=$dinfo;
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function getTasks(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//1超期未完成任务数,2拒绝任务数
        $page = $this->params['page'];
        $task_id = $this->params['task_id'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $stat_date = $this->params['stat_date'];
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);
        $where = array();
        if($task_id>0){
            $where['a.task_id'] = $task_id;
        }
        if(in_array($hotel_role_type,array(2,4,6))){
            $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
        }elseif($hotel_role_type==3){
            $where['a.residenter_id'] = $res_staff['sysuser_id'];
        }
        if($area_id>0){
            $where['hotel.area_id'] = $area_id;
        }
        if($staff_id>0){
            if($staff_id==99999){
                $where['a.residenter_id'] = 0;
            }else{
                $res_residenter = $m_opsstaff->getInfo(array('id'=>$staff_id));
                $where['a.residenter_id'] = $res_residenter['sysuser_id'];
            }
        }
        if(empty($stat_date)){
            $stat_date = date('Y-m');
        }
        $start_time = $stat_date.'-01 00:00:00';
        $end_time = $stat_date.'-31 23:59:59';
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $where['ext.is_salehotel'] = 1;
        $where['a.off_state'] = 1;

        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $datalist = array();
        $offset = ($page-1)*$pagesize;
        if($type==1){
            $where['a.status'] = array('neq',3);
            $where['a.is_trigger'] = 1;
            $where['a.handle_status'] = array('in','0,2');
            $where['a.add_time'] = array('elt',$end_time);
            $where['a.finish_task_record_id'] = 0;
            $where['task.type'] = array('neq',7);
            $residenter_id = -1;
            if(isset($where['a.residenter_id'])){
                $residenter_id = $where['a.residenter_id'];
                unset($where['a.residenter_id']);
            }
            $res_taskids = $m_crmtask_record->getTaskRecords('a.hotel_id,a.task_id,GROUP_CONCAT(a.id) as ids,max(a.id) as last_id',$where,'','','a.hotel_id,a.task_id');
            if(!empty($res_taskids)){
                $all_ids = array();
                foreach ($res_taskids as $v){
                    $all_ids[]=$v['last_id'];
                }
                $fileds = "a.residenter_id,a.residenter_name,count(DISTINCT a.hotel_id) as hotel_num,count(a.id) as num,group_concat(a.id) as tids";
                $twhere = array('a.id'=>array('in',$all_ids));
                if($residenter_id>=0){
                    $twhere['a.residenter_id'] = $residenter_id;
                }
                $res_task = $m_crmtask_record->getTaskRecords($fileds,$twhere,'',"$offset,$pagesize",'a.residenter_id');
                $all_types = C('CRM_TASK_TYPES');
                foreach ($res_task as $v){
                    $hfileds = "a.hotel_id,hotel.name as hotel_name,count(a.id) as num,group_concat(task.type) as types";
                    $res_hoteltask = $m_crmtask_record->getTaskRecords($hfileds,array('a.id'=>array('in',$v['tids'])),'','','a.hotel_id');
                    $hotel_list = array();
                    foreach ($res_hoteltask as $tv){
                        $types_arr = explode(',',$tv['types']);
                        $task_type_name = '';
                        if(in_array(3,$types_arr)){
                            $task_type_name = $all_types[3];
                        }elseif(in_array(4,$types_arr)){
                            $task_type_name = $all_types[4];
                        }
                        $hotel_list[]=array('hotel_id'=>$tv['hotel_id'],'hotel_name'=>$tv['hotel_name'],'hotel_task_num'=>$tv['num'],'task_type_name'=>$task_type_name);
                    }
                    $dinfo = array('residenter_id'=>$v['residenter_id'],'residenter_name'=>$v['residenter_name'],
                        'hotel_num'=>$v['hotel_num'],'task_num'=>$v['num'],'task_list'=>$hotel_list);
                    $datalist[]=$dinfo;
                }
            }
        }elseif($type==2){
            $where['a.handle_status'] = 1;
            $fileds = "a.residenter_id,a.residenter_name,count(a.id) as num,group_concat(a.id) as tids";
            $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'',"$offset,$pagesize",'a.residenter_id');
            foreach ($res_task as $v){
                $hfileds = "a.id as task_record_id,hotel.name as hotel_name,task.name as task_name,a.content,a.handle_time as refuse_time";
                $task_list = $m_crmtask_record->getTaskRecords($hfileds,array('a.id'=>array('in',$v['tids'])),'a.hotel_id desc','','');
                $dinfo = array('residenter_id'=>$v['residenter_id'],'residenter_name'=>$v['residenter_name'],
                    'task_num'=>$v['num'],'task_list'=>$task_list);
                $datalist[]=$dinfo;
            }
        }
        $this->to_back(array('type'=>$type,'datalist'=>$datalist));
    }

    public function arrangeTasks(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);//1待完成,2已完成
        $page = $this->params['page'];
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $where = array('a.residenter_id'=>$res_staff['sysuser_id'],'task.type'=>11);
        if($status==1){
            $where['a.status'] = array('in','0,1,2');
            $where['task.start_time'] = array('elt',date('Y-m-d H:i:s'));
        }else{
            $where['a.status'] = 3;
        }

        $fileds = "a.id as task_record_id,task.name,task.desc,su.remark as user_name,task.start_time,task.end_time";
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $offset = ($page-1)*$pagesize;
        $res_task = $m_crmtask_record->getCustomTasks($fileds,$where,'',"$offset,$pagesize",'');
        $now_time = date('Y-m-d H:i:s');
        foreach ($res_task as $k=>$v){
            $status_str = '';
            if($now_time>$v['end_time']){
                $status_str = '已过期';
            }
            $res_task[$k]['status_str'] = $status_str;
        }
        $this->to_back(array('datalist'=>$res_task));
    }

    public function detail(){
        $openid = $this->params['openid'];
        $task_record_id = intval($this->params['task_record_id']);

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $hfileds = "task.name,task.desc,task.is_upimg,task.end_time,task.is_check_location,a.id as task_record_id,a.img,a.location_hotel_id as hotel_id,a.content as remark,a.finish_time";
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $res_task = $m_crmtask_record->getTaskRecords($hfileds,array('a.id'=>$task_record_id),'','','');
        $imgs = array();
        if(!empty($res_task[0]['img'])){
            $oss_host = get_oss_host();
            $images = explode(',',$res_task[0]['img']);
            foreach ($images as $v){
                if(!empty($v)){
                    $imgs[]=$oss_host.$v;
                }
            }
        }
        unset($res_task[0]['img']);
        $res_task[0]['imgs'] = $imgs;
        $hotel_name = '';
        if(!empty($res_task[0]['hotel_id'])){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('name',$res_task[0]['hotel_id']);
            $hotel_name = $res_hotel['name'];
        }
        $res_task[0]['imgs'] = $imgs;
        $res_task[0]['hotel_name'] = $hotel_name;
        $res_task[0]['end_time'] = date('Y-m-d',strtotime($res_task[0]['end_time']));
        $this->to_back($res_task[0]);
    }

    public function finishTask(){
        $openid = $this->params['openid'];
        $task_record_id = intval($this->params['task_record_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $img = $this->params['img'];
        $content = trim($this->params['content']);

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $updata = array('location_hotel_id'=>$hotel_id,'img'=>$img,'content'=>$content,'status'=>3,'finish_time'=>date('Y-m-d H:i:s'));
        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $m_crmtask_record->updateData(array('id'=>$task_record_id),$updata);

        $this->to_back(array('task_record_id'=>$task_record_id));
    }



}