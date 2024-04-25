<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class ApprovalController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'itemlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'config':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'steps':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'item_id'=>1001,'hotel_id'=>1001);
                break;
            case 'add':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'item_id'=>1001,'hotel_id'=>1001,'op_time'=>1001,
                    'merchant_staff_id'=>1001,'goods_data'=>1002,'content'=>1002,'bottle_num'=>1002);
                break;
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'item_id'=>1001,'area_id'=>1001,'ops_staff_id'=>1001,
                    'status'=>1001,'sdate'=>1002,'edate'=>1002,'page'=>1001);
                break;
            case 'myrelease':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'approval_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function itemlist(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_approval_item = new \Common\Model\Crm\ApprovalItemModel();
        $res_data = $m_approval_item->getDataList('id,name,image',array('status'=>1),'sort_num asc');
        $oss_host = get_oss_host();
        foreach ($res_data as $k=>$v){
            $res_data[$k]['image'] = $oss_host.$v['image'];
        }
        $this->to_back($res_data);
    }

    public function config(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_goods = new \Common\Model\Finance\GoodsModel();
        $gwhere = array('status'=>1);
        $goods_list = $m_goods->getDataList('id as value,name',$gwhere,'category_id asc');
        array_unshift($goods_list,array('value'=>0,'name'=>'请选择'));
        $goods_num = array();
        for($i=0;$i<11;$i++){
            $goods_num[]=array('name'=>$i.'瓶','value'=>$i);
        }
        $delivery_time = time()+86400;
        $delivery_date = date('Y-m-d',$delivery_time);
        $delivery_hour = date('H:i',$delivery_time);

        $res_data = array('goods_list'=>$goods_list,'goods_num'=>$goods_num,'delivery_date'=>$delivery_date,'delivery_hour'=>$delivery_hour);
        $this->to_back($res_data);
    }

    public function steps(){
        $openid = $this->params['openid'];
        $item_id = intval($this->params['item_id']);
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('area_id',$hotel_id);
        $area_id = $res_hotel['area_id'];
        $m_approval_step = new \Common\Model\Crm\ApprovalStepModel();
        $fields = 'id,name,ops_staff_id,step_order,role_type';
        $res_steps = $m_approval_step->getDataList($fields,array('item_id'=>$item_id),'step_order asc');
        $datalist = array();
        foreach ($res_steps as $v){
            $user_name = '';
            if($v['ops_staff_id']){
                $res_ops = $m_staff->getStaffinfo('su.remark as user_name',array('a.id'=>$v['ops_staff_id']));
                $user_name = $res_ops[0]['user_name'];
            }else{
                $hotel_role_type = $v['role_type'];
                if($hotel_role_type==7){
                    $hotel_role_type = 6;
                }
                $owhere = array('a.area_id'=>$area_id,'a.hotel_role_type'=>$hotel_role_type);
                $res_ops = $m_staff->getStaffinfo('su.remark as user_name',$owhere);
                $user_name = $res_ops[0]['user_name'];
                if($item_id==11 && $v['step_order']==2){
                    $user_name = '';
                }
            }
            $datalist[]=array('name'=>$v['name'],'user_name'=>$user_name,'step_order'=>$v['step_order']);
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function add(){
        $openid = $this->params['openid'];
        $item_id = intval($this->params['item_id']);
        $hotel_id = intval($this->params['hotel_id']);
        $merchant_staff_id = intval($this->params['merchant_staff_id']);
        $op_time = $this->params['op_time'];
        $goods_data = $this->params['goods_data'];
        $content = trim($this->params['content']);
        $bottle_num = intval($this->params['bottle_num']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $adata = array('item_id'=>$item_id,'ops_staff_id'=>$res_staff['id'],'hotel_id'=>$hotel_id,
            'merchant_staff_id'=>$merchant_staff_id,'bottle_num'=>$bottle_num,'status'=>1);

        $json_str = stripslashes(html_entity_decode($goods_data));
        $goods_arr = json_decode($json_str,true);
        $wine_data = array();
        switch ($item_id){
            case 10:
                $wine_num = 0;
                foreach ($goods_arr as $v){
                    if($v['id']>0 && $v['num']>0){
                        $wine_num+=$v['num'];
                        $num = 0;
                        if(isset($wine_data[$v['id']])){
                            $num = $wine_data[$v['id']];
                        }
                        $wine_data[$v['id']]=$v['num']+$num;
                    }
                }
                if(empty($wine_data)){
                    $this->to_back(1001);
                }
                $adata['bottle_num'] = $wine_num;
                $adata['wine_data'] = json_encode($wine_data);
                $adata['delivery_time'] = date('Y-m-d H:i:s',strtotime($op_time));
                break;
            case 11:
                $adata['recycle_time'] = date('Y-m-d H:i:s',strtotime($op_time));
                break;

        }
        if(!empty($content)){
            $adata['content'] = $content;
        }
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $approval_id = $m_approval->add($adata);

        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('area_id',$hotel_id);
        $area_id = $res_hotel['area_id'];
        $m_approval_step = new \Common\Model\Crm\ApprovalStepModel();
        $fields = 'id,name,ops_staff_id,step_order,role_type';
        $res_steps = $m_approval_step->getDataList($fields,array('item_id'=>$item_id),'step_order asc');
        $processes_data = array();
        foreach ($res_steps as $v){
            $is_receive = 0;
            if($v['step_order']==1){
                $is_receive = 1;
            }
            $ops_staff_id = $v['ops_staff_id'];
            if($ops_staff_id==0){
                $hotel_role_type = $v['role_type'];
                if($hotel_role_type==7){
                    $hotel_role_type = 6;
                }
                $owhere = array('a.area_id'=>$area_id,'a.hotel_role_type'=>$hotel_role_type);
                $res_ops = $m_staff->getStaffinfo('a.id',$owhere);
                $ops_staff_id = $res_ops[0]['id'];
                if($item_id==11 && $v['step_order']==2){
                    $ops_staff_id = 0;
                }
            }

            $processes_data[] = array('approval_id'=>$approval_id,'step_id'=>$v['id'],'step_order'=>$v['step_order'],'area_id'=>$area_id,
                'is_receive'=>$is_receive,'ops_staff_id'=>$ops_staff_id);
        }
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $m_approval_process->addAll($processes_data);

        $this->to_back(array('approval_id'=>$approval_id));
    }

    pubLic function datalist(){
        $openid = $this->params['openid'];
        $item_id = intval($this->params['item_id']);
        $area_id = intval($this->params['area_id']);
        $ops_staff_id = intval($this->params['ops_staff_id']);
        $status = intval($this->params['status']);
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];
        $page = intval($this->params['page']);
        $page_szie = 10;

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];
        $staff_area_id = $res_staff['area_id'];

        if(!in_array($hotel_role_type,array(1,8,9))){
            $this->to_back(array('datalist'=>array()));
        }
        $offset = ($page-1)*$page_szie;
        $fields = 'approval.id as approval_id,approval.add_time,approval.bottle_num,approval.content,approval.item_id,
        approval.delivery_time,approval.recycle_time,approval.status,approval.hotel_id,hotel.name as hotel_name,
        staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName,item.name as item_name';
        $where = array();
        if($status){
            $where['approval.status'] = $status;
        }
        if($item_id){
            $where['approval.item_id'] = $item_id;
        }
        if(!empty($sdate) && !empty($edate)){
            $stime = date('Y-m-d 00:00:00',strtotime($sdate));
            $etime = date('Y-m-d 23:59:59',strtotime($edate));
            $where['approval.add_time'] = array(array('egt',$stime),array('elt',$etime));
        }
        if($hotel_role_type==9){
            $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
            $where['a.ops_staff_id'] = $res_staff['id'];
            $where['a.is_receive'] = 1;
            $res_data = $m_approval_process->getProcessDatas($fields,$where,'approval.id desc',"$offset,$page_szie",'');
        }else{
            if($hotel_role_type==1){
                if($area_id){
                    $where['hotel.area_id'] = $area_id;
                }
            }else{
                $where['hotel.area_id'] = $staff_area_id;
            }
            if($ops_staff_id){
                $where['approval.ops_staff_id'] = $ops_staff_id;
            }
            $m_approval = new \Common\Model\Crm\ApprovalModel();
            $res_data = $m_approval->getApprovalDatas($fields,$where,'approval.id desc',"$offset,$page_szie",'');
        }
        $all_status = C('APPROVAL_STATUS');
        foreach ($res_data as $k=>$v){
            $res_data[$k]['add_time'] = date('m月d日 H:i',strtotime($v['add_time']));
            $res_data[$k]['status_str'] = $all_status[$v['status']];
            switch ($v['item_id']){
                case 10:
                    $res_data[$k]['op_time'] = date('Y.m.d H:i',strtotime($v['delivery_time']));
                    break;
                case 11:
                    $res_data[$k]['op_time'] = date('Y.m.d H:i',strtotime($v['recycle_time']));
                    break;
            }
        }
        $this->to_back(array('datalist'=>$res_data));
    }

    pubLic function myrelease(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $page_szie = 10;

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $offset = ($page-1)*$page_szie;
        $fields = 'approval.id as approval_id,approval.add_time,approval.bottle_num,approval.content,approval.item_id,
        approval.delivery_time,approval.recycle_time,approval.status,approval.hotel_id,hotel.name as hotel_name,
        staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName,item.name as item_name';
        $where = array('approval.ops_staff_id'=>$res_staff['id']);
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $res_data = $m_approval->getApprovalDatas($fields,$where,'approval.id desc',"$offset,$page_szie",'');
        $all_status = C('APPROVAL_STATUS');
        foreach ($res_data as $k=>$v){
            $res_data[$k]['add_time'] = date('m月d日 H:i',strtotime($v['add_time']));
            $res_data[$k]['status_str'] = $all_status[$v['status']];
            switch ($v['item_id']){
                case 10:
                    $res_data[$k]['op_time'] = date('Y.m.d H:i',strtotime($v['delivery_time']));
                    break;
                case 11:
                    $res_data[$k]['op_time'] = date('Y.m.d H:i',strtotime($v['recycle_time']));
                    break;
            }
        }
        $this->to_back(array('datalist'=>$res_data));
    }

    public function detail(){
        $openid = $this->params['openid'];
        $approval_id = intval($this->params['approval_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $now_ops_staff_id = $res_staff['id'];
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $fields = 'approval.id as approval_id,approval.add_time,approval.bottle_num,approval.content,approval.item_id,
        approval.merchant_staff_id,approval.delivery_time,approval.recycle_time,approval.status,approval.hotel_id,hotel.name as hotel_name,
        staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName,item.name as item_name';
        $where = array('approval.id'=>$approval_id);
        $res_approval = $m_approval->getApprovalDatas($fields,$where,'','','');
        $res_data = $res_approval[0];
        $all_status = C('APPROVAL_STATUS');
        $res_data['add_time'] = date('m月d日 H:i',strtotime($res_data['add_time']));
        $res_data['status_str'] = $all_status[$res_data['status']];
        switch ($res_data['item_id']){
            case 10:
                $res_data['op_time'] = date('Y.m.d H:i',strtotime($res_data['delivery_time']));
                break;
            case 11:
                $res_data['op_time'] = date('Y.m.d H:i',strtotime($res_data['recycle_time']));
                break;
            default:
                $res_data['op_time'] = '';
        }
        $m_merchant_staff = new \Common\Model\Integral\StaffModel();
        $sfileds = 'user.nickName,user.mobile';
        $res_mstaff = $m_merchant_staff->getMerchantStaff($sfileds,array('a.id'=>$res_data['merchant_staff_id']));
        $res_data['merchant_staff_name'] = $res_mstaff[0]['nickName'];
        $res_data['merchant_staff_mobile'] = $res_mstaff[0]['mobile'];
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $fields = 'step.name,sysuser.remark as user_name,a.*';
        $res_process = $m_approval_process->getDatas($fields,array('a.approval_id'=>$approval_id),'a.step_order asc');
        $is_approval = 0;
        $process = array();
        $process_status = array('1'=>'同意','2'=>'不同意','3'=>'接收申请','4'=>'分配','5'=>'快递');
        $now_processes_id = 0;
        foreach ($res_process as $v){
            if($v['is_receive']==1 && $v['ops_staff_id']==$now_ops_staff_id){
                $is_approval = 1;
                $now_processes_id = $v['id'];
            }
            $handle_time = $v['handle_time']=='0000-00-00 00:00:00'?'':$v['handle_time'];
            $approval_content = array();
            $is_handle = $v['is_handle'];
            if($is_handle==1){
                $approval_content[]=array('status_str'=>$process_status[$v['status']],'handle_time'=>$handle_time);
                if($v['stock_out_finish_time']!='0000-00-00 00:00:00'){
                    $approval_content[]=array('status_str'=>'完成出库','handle_time'=>$v['stock_out_finish_time']);
                }
            }
            if(empty($v['user_name'])){
                $v['user_name'] = '';
            }
            $process[]=array('processes_id'=>$v['id'],'step_order'=>$v['step_order'],'name'=>$v['name'],'user_name'=>$v['user_name'],'is_handle'=>$is_handle,'approval_content'=>$approval_content);
        }
        $res_data['is_approval'] = $is_approval;
        $res_data['process'] = $process;
        $res_data['processes_id'] = $now_processes_id;

        $this->to_back($res_data);
    }




}