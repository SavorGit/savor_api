<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class CustomerController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getPopup':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'addCustomer':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'name'=>1001,'mobile'=>1001,'mobile1'=>1002,'mobile2'=>1002,'gender'=>1002,
                    'avg_expense'=>1002,'avatar_url'=>1002,'birthday'=>1002,'native_place'=>1002,'customer_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'datalist':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'keywords'=>1002);
                $this->is_verify = 1;
                break;
            case 'detail':
                $this->valid_fields = array('openid'=>1001,'customer_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'perfectList':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'addExpenseRecord':
                $this->valid_fields = array('openid'=>1001,'name'=>1002,'mobile'=>1002,'customer_id'=>1002,
                    'room_id'=>1001,'meal_time'=>1001,'people_num'=>1001,'money'=>1001,'images'=>1001,'remark'=>1002,'labels'=>1002);
                $this->is_verify = 1;
                break;
            case 'getLabels':
                $this->valid_fields = array('openid'=>1001,'customer_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'getRecordList':
                $this->valid_fields = array('openid'=>1001,'customer_id'=>1001,'page'=>1001,'pagesize'=>1002);
                $this->is_verify = 1;
                break;
            case 'recordinfo':
                $this->valid_fields = array('openid'=>1001,'expense_record_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'editRemark':
                $this->valid_fields = array('openid'=>1001,'customer_id'=>1001,'remark'=>1002);
                $this->is_verify = 1;
                break;
            case 'editLabels':
                $this->valid_fields = array('openid'=>1001,'customer_id'=>1001,'labels'=>1002);
                $this->is_verify = 1;
                break;
            case 'getOplogs':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function getPopup(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $start_time = date('Y-m-d H:i:s',strtotime('-3 hour'));
        $end_time = date('Y-m-d H:i:s');
        $where = array('hotel_id'=>$hotel_id,'book_time'=>array(array('egt',$start_time),array('elt',$end_time)));
        $fields = 'id,openid,name,mobile,room_id,room_name,book_time';
        $res_data = $m_invitation->getDataList($fields,$where,'id desc');
        $is_popup = 0;
        $message='';
        if(!empty($res_data)){
            $m_customer = new \Common\Model\Smallapp\CustomerModel();
            $m_customer_record = new \Common\Model\Smallapp\CustomerExpenseRecordModel();
            foreach ($res_data as $v){
                $mobile = $v['mobile'];
                $where = array('CONCAT(mobile,mobile1,mobile2)'=>array('like',"%$mobile%"));
                $res_customer = $m_customer->getInfo($where);
                if(!empty($res_customer)){
                    $customer_id = $res_customer['id'];
                    $rwhere = array('customer_id'=>$customer_id,'hotel_id'=>$hotel_id,'room_id'=>$v['room_id']);
                    $rwhere['add_time'] = array('egt',$start_time);
                    $res_record = $m_customer_record->getInfo($rwhere);
                    if(empty($res_record)){
                        $is_popup = 1;
                        $message = "{$v['room_name']}包间的客人{$v['name']}已完成就餐，是否要完善消费记录？";
                        break;
                    }
                }else{
                    $is_popup = 1;
                    $message = "{$v['room_name']}包间的客人{$v['name']}已完成就餐，是否要完善消费记录？";
                }
            }
        }
        $this->to_back(array('is_popup'=>$is_popup,'message'=>$message));
    }

    public function perfectList(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $start_time = date('Y-m-d H:i:s',strtotime('-3 hour'));
        $end_time = date('Y-m-d H:i:s');
        $where = array('hotel_id'=>$hotel_id,'book_time'=>array(array('egt',$start_time),array('elt',$end_time)));
        $fields = 'id,openid,name,mobile,room_id,room_name,book_time';
        $res_data = $m_invitation->getDataList($fields,$where,'id desc');
        $datalist = array();
        if(!empty($res_data)){
            $m_customer = new \Common\Model\Smallapp\CustomerModel();
            $m_customer_record = new \Common\Model\Smallapp\CustomerExpenseRecordModel();
            foreach ($res_data as $v){
                $mobile = $v['mobile'];
                $where = array('CONCAT(mobile,mobile1,mobile2)'=>array('like',"%$mobile%"));
                $res_customer = $m_customer->getInfo($where);
                if(!empty($res_customer)){
                    $customer_id = $res_customer['id'];
                    $rwhere = array('customer_id'=>$customer_id,'hotel_id'=>$hotel_id,'room_id'=>$v['room_id']);
                    $rwhere['add_time'] = array('egt',$start_time);
                    $res_record = $m_customer_record->getInfo($rwhere);
                    if(empty($res_record)){
                        $datalist[]=array('room_name'=>$v['room_name'],'book_time'=>$v['book_time'],'room_id'=>$v['room_id'],
                            'name'=>$v['name'],'mobile'=>$v['mobile'],'customer_id'=>$customer_id);
                    }
                }else{
                    $datalist[]=array('room_name'=>$v['room_name'],'book_time'=>$v['book_time'],'room_id'=>$v['room_id'],
                        'name'=>$v['name'],'mobile'=>$v['mobile'],'customer_id'=>0);
                }
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function getLabels(){
        $openid = $this->params['openid'];
        $customer_id = intval($this->params['customer_id']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_label = new \Common\Model\Smallapp\CustomerLabelModel();
        $res_data = $m_label->getDataList('id,name',array('customer_id'=>$customer_id),'id desc');
        $this->to_back(array('datalist'=>$res_data));
    }

    public function addCustomer(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $name = trim($this->params['name']);
        $mobile = trim($this->params['mobile']);
        $mobile1 = trim($this->params['mobile1']);
        $mobile2 = trim($this->params['mobile2']);
        $gender = intval($this->params['gender']);
        $avg_expense = intval($this->params['avg_expense']);
        $avatar_url = $this->params['avatar_url'];
        $birthday = $this->params['birthday'];
        $native_place = trim($this->params['native_place']);
        $customer_id = intval($this->params['customer_id']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $q_mobiles = array($mobile);
        if(!empty($mobile1)){
            $q_mobiles[]=$mobile1;
        }else{
            $mobile1='';
        }
        if(!empty($mobile2)){
            $q_mobiles[]=$mobile2;
        }else{
            $mobile2='';
        }
        $avatar_url = !empty($avatar_url)?$avatar_url:'';
        $native_place = !empty($native_place)?$native_place:'';
        $data = array('hotel_id'=>$hotel_id,'name'=>$name,'mobile'=>$mobile,'mobile1'=>$mobile1,'mobile2'=>$mobile2,
            'gender'=>$gender,'avg_expense'=>$avg_expense,'avatar_url'=>$avatar_url,'native_place'=>$native_place);
        if(!empty($birthday)){
            $data['birthday'] = $birthday;
        }
        if($customer_id>0){
            $data['op_openid'] = $openid;
            $data['update_time'] = date('Y-m-d H:i:s');
        }else{
            $data['openid'] = $openid;
        }
        $m_customer = new \Common\Model\Smallapp\CustomerModel();
        foreach ($q_mobiles as $v){
            $mwhere = array('CONCAT(mobile,mobile1,mobile2)'=>array('like',"%$v%"));
            if($customer_id>0){
                $mwhere['id']=array('neq',$customer_id);
            }
            $res_customer = $m_customer->getInfo($mwhere);
            if(!empty($res_customer)){
                $this->to_back(93228);
            }
        }
        $m_customerlog = new \Common\Model\Smallapp\CustomerLogModel();
        if($customer_id>0){
            $m_customer->updateData(array('id'=>$customer_id),$data);
            $m_customerlog->add(array('hotel_id'=>$hotel_id,'customer_id'=>$customer_id,'action'=>3,'action_name'=>'修改'));
        }else{
            $customer_id = $m_customer->add($data);
            $m_customerlog->add(array('hotel_id'=>$hotel_id,'customer_id'=>$customer_id,'action'=>2,'action_name'=>'新增'));
        }
        $this->to_back(array('customer_id'=>$customer_id));
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $keywords = trim($this->params['keywords']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $m_customer = new \Common\Model\Smallapp\CustomerModel();
        $where = array('hotel_id'=>$hotel_id);
        if(!empty($keywords)){
            $where['CONCAT(name,mobile,mobile1,mobile2)'] = array('like',"%$keywords%");
        }
        $res_data = $m_customer->getDataList('id,name,mobile,mobile1,avatar_url',$where,'id desc');
        $datalist = array();
        $total_num = 0;
        if(!empty($res_data)){
            $total_num = count($res_data);
            $oss_host = get_oss_host();
            $all_customer = array();
            foreach ($res_data as $v){
                $letter = getFirstCharter($v['name']);
                $phone = $v['mobile'];
                if(!empty($v['mobile1'])){
                    $phone = $phone.'/'.$v['mobile1'];
                }
                $avatar_url = '';
                if(!empty($v['avatar_url'])){
                    $avatar_url = $oss_host.$v['avatar_url'];
                }
                $all_customer[$letter][]=array('id'=>$v['id'],'name'=>$v['name'],'phone'=>$phone,'avatarUrl'=>$avatar_url);
            }
            ksort($all_customer);
            foreach ($all_customer as $k=>$v){
                $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
                $datalist[]=$dinfo;
            }
        }
        $this->to_back(array('total_num'=>$total_num,'datalist'=>$datalist));
    }

    public function addExpenseRecord(){
        $openid = $this->params['openid'];
        $name = $this->params['name'];
        $mobile = $this->params['mobile'];
        $customer_id = intval($this->params['customer_id']);
        $room_id = intval($this->params['room_id']);
        $meal_time = $this->params['meal_time'];
        $people_num = intval($this->params['people_num']);
        $money = intval($this->params['money']);
        $images = $this->params['images'];
        $remark = trim($this->params['remark']);
        $labels = $this->params['labels'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_room = new \Common\Model\RoomModel();
        $fields = 'room.id as room_id,room.name as room_name,hotel.id as hotel_id,hotel.name as hotel_name';
        $res_room = $m_room->getRoomByCondition($fields,array('room.id'=>$room_id));

        $m_customer = new \Common\Model\Smallapp\CustomerModel();
        $is_new_customer = 0;
        if(empty($customer_id)){
            if(!empty($name) && !empty($mobile)){
                $mwhere = array('CONCAT(mobile,mobile1,mobile2)'=>array('like',"%$mobile%"));
                $res_customer = $m_customer->getInfo($mwhere);
                if(!empty($res_customer)){
                    $customer_id = $res_customer['id'];
                }else{
                    $customer_id = $m_customer->add(array('openid'=>$openid,'hotel_id'=>$res_room[0]['hotel_id'],'name'=>$name,'mobile'=>$mobile));
                    $m_customerlog = new \Common\Model\Smallapp\CustomerLogModel();
                    $m_customerlog->add(array('hotel_id'=>$res_room[0]['hotel_id'],'customer_id'=>$customer_id,'action'=>2,'action_name'=>'新增'));
                    $is_new_customer = 1;
                }
            }
        }
        if(empty($customer_id)){
            $this->to_back(1001);
        }
        $m_label = new \Common\Model\Smallapp\CustomerLabelModel();
        if(!empty($labels)){
            $all_label = explode(',',$labels);
            $label_data = array();
            if($is_new_customer==1){
                foreach ($all_label as $v){
                    if(!empty($v)){
                        $label_data[]=array('customer_id'=>$customer_id,'name'=>trim($v));
                    }
                }
            }else{
                $label_ids = array();
                foreach ($all_label as $v){
                    if(!empty($v)){
                        $ldata = array('customer_id'=>$customer_id,'name'=>trim($v));
                        $res_label = $m_label->getInfo($ldata);
                        if(!empty($res_label)){
                            $label_ids[]=$res_label['id'];
                        }else{
                            $label_data[]=$ldata;
                        }
                    }
                }
            }
            if(!empty($label_ids)){
                $m_label->delData(array('customer_id'=>$customer_id,'id'=>array('not in',$label_ids)));
            }else{
                $m_label->delData(array('customer_id'=>$customer_id));
            }
            if(!empty($label_data)){
                $m_label->addAll($label_data);
            }
        }else{
            if($is_new_customer==0){
                $m_label->delData(array('customer_id'=>$customer_id));
            }
        }

        $add_data = array('customer_id'=>$customer_id,'hotel_id'=>$res_room[0]['hotel_id'],'hotel_name'=>$res_room[0]['hotel_name'],
            'room_id'=>$res_room[0]['room_id'],'room_name'=>$res_room[0]['room_name'],'meal_time'=>$meal_time,'people_num'=>$people_num,
            'money'=>$money,'images'=>$images);
        if(!empty($remark)){
            $add_data['remark'] = $remark;
        }
        $m_expense_record = new \Common\Model\Smallapp\CustomerExpenseRecordModel();
        $expense_record_id = $m_expense_record->add($add_data);
        $this->to_back(array('expense_record_id'=>$expense_record_id));
    }

    public function recordinfo(){
        $openid = $this->params['openid'];
        $expense_record_id = intval($this->params['expense_record_id']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_expense_record = new \Common\Model\Smallapp\CustomerExpenseRecordModel();
        $res_info = $m_expense_record->getInfo(array('id'=>$expense_record_id));
        $images = explode(',',$res_info['images']);
        $all_images = array();
        $oss_host = get_oss_host();
        foreach ($images as $v){
            $all_images[]=$oss_host.$v;
        }
        $res_info['images'] = $all_images;
        if($res_info['meal_time']=='0000-00-00 00:00:00'){

        }
        $this->to_back($res_info);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $customer_id = intval($this->params['customer_id']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_customer = new \Common\Model\Smallapp\CustomerModel();
        $res_detail = $m_customer->getInfo(array('id'=>$customer_id));
        unset($res_detail['op_openid'],$res_detail['update_time']);

        $gender_map = array('1'=>'男','2'=>'女');
        $gender_str = '';
        if(isset($gender_map[$res_detail['gender']])){
            $gender_str = $gender_map[$res_detail['gender']];
        }
        if($res_detail['birthday']=='0000-00-00'){
            $birthday_str = '';
            $res_detail['birthday'] = '';
        }else{
            $birthday_str = date('m月d日',strtotime($res_detail['birthday']));
        }
        $oss_avatar_url = '';
        if(!empty($res_detail['avatar_url'])){
            $oss_host = get_oss_host();
            $oss_avatar_url = $oss_host.$res_detail['avatar_url'];
        }
        $m_expense_record = new \Common\Model\Smallapp\CustomerExpenseRecordModel();
        $rwhere = array('customer_id'=>$customer_id);
        $res_record = $m_expense_record->getDataList('count(id) as num',$rwhere,'');
        $expense_num = intval($res_record[0]['num']);
        $expense_msg = '';
        if($expense_num>0){
            $rwhere['add_time'] = array('egt',date('Y-m-d 00:00:00',strtotime('-30 day')));
            $res_record = $m_expense_record->getDataList('count(id) as num',$rwhere,'');
            if($res_record[0]['num']>0){
                $expense_msg = "近1个月内消费{$res_record[0]['num']}次";
            }else{
                $rwhere['add_time'] = array(array('egt',date('Y-m-d 00:00:00',strtotime('-90 day'))),array('elt',date('Y-m-d 23:59:59',strtotime('-31 day'))));
                $res_record = $m_expense_record->getDataList('count(id) as num',$rwhere,'');
                if($res_record[0]['num']>0){
                    $expense_msg = "近3个月内消费{$res_record[0]['num']}次";
                }else{
                    $rwhere['add_time'] = array('egt',date('Y-m-d 00:00:00',strtotime('-90 day')));
                    $res_record = $m_expense_record->getDataList('id,add_time',$rwhere,'id asc');
                    if(!empty($res_record[0]['id'])){
                        $day = intval((time()-strtotime($res_record[0]['add_time']))/86400);
                        $expense_msg = "{$day}天前消费过";
                    }else{
                        $expense_msg = '最近三个月没有消费';
                    }
                }
            }
        }
        $res_detail['gender_str'] = $gender_str;
        $res_detail['birthday_str'] = $birthday_str;
        $res_detail['oss_avatar_url'] = $oss_avatar_url;
        $res_detail['expense_num'] = $expense_num;
        $res_detail['expense_msg'] = $expense_msg;

        $m_customerlog = new \Common\Model\Smallapp\CustomerLogModel();
        $m_customerlog->add(array('hotel_id'=>$res_detail['hotel_id'],'customer_id'=>$customer_id,'action'=>1,'action_name'=>'查看'));

        $this->to_back($res_detail);
    }

    public function getRecordList(){
        $openid = $this->params['openid'];
        $customer_id = intval($this->params['customer_id']);
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        if(empty($pagesize)){
            $pagesize = 100;
        }

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $start = ($page-1)*$pagesize;
        $m_expense_record = new \Common\Model\Smallapp\CustomerExpenseRecordModel();
        $rwhere = array('customer_id'=>$customer_id);
        $res_record = $m_expense_record->getDataList('id,room_name,images,add_time',$rwhere,'id desc',$start,$pagesize);
        $datalist = array();
        $oss_host = get_oss_host();
        foreach ($res_record['list'] as $v){
            $images = explode(',',$v['images']);
            $image = $oss_host.$images[0];

            $add_time = date('Y-m-d H:i',strtotime($v['add_time']));
            $datalist[]=array('expense_record_id'=>$v['id'],'room_name'=>$v['room_name'],'image'=>$image,'add_time'=>$add_time);
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function editRemark(){
        $openid = $this->params['openid'];
        $customer_id = intval($this->params['customer_id']);
        $remark = !empty($this->params['remark'])?trim($this->params['remark']):'';

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_customer = new \Common\Model\Smallapp\CustomerModel();
        $m_customer->updateData(array('id'=>$customer_id),array('remark'=>$remark,'op_openid'=>$openid,'update_time'=>date('Y-m-d H:i:s')));
        $this->to_back(array('customer_id'=>$customer_id));
    }

    public function editLabels(){
        $openid = $this->params['openid'];
        $customer_id = intval($this->params['customer_id']);
        $labels = $this->params['labels'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_label = new \Common\Model\Smallapp\CustomerLabelModel();
        if(!empty($labels)){
            $label_data = array();
            $label_ids = array();
            $all_label = explode(',',$labels);
            foreach ($all_label as $v){
                if(!empty($v)){
                    $ldata = array('customer_id'=>$customer_id,'name'=>trim($v));
                    $res_label = $m_label->getInfo($ldata);
                    if(!empty($res_label)){
                        $label_ids[]=$res_label['id'];
                    }else{
                        $label_data[]=$ldata;
                    }
                }
            }
            if(!empty($label_ids)){
                $m_label->delData(array('customer_id'=>$customer_id,'id'=>array('not in',$label_ids)));
            }else{
                $m_label->delData(array('customer_id'=>$customer_id));
            }
            if(!empty($label_data)){
                $m_label->addAll($label_data);
            }
        }else{
            $m_label->delData(array('customer_id'=>$customer_id));
        }

        $this->to_back(array('customer_id'=>$customer_id));
    }

    public function getOplogs(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_customer_log = new \Common\Model\Smallapp\CustomerLogModel();
        $fileds = 'a.customer_id,max(a.id) as last_id,customer.name,customer.mobile';
        $where = array('a.hotel_id'=>$hotel_id);
        $where['a.add_time'] = array('egt',date('Y-m-d H:i:s',strtotime('-3 day')));
        $res_log = $m_customer_log->getCustomerLogs($fileds,$where,'last_id desc','0,10','a.customer_id');
        $datalist = array();
        foreach ($res_log as $v){
            $res_linfo = $m_customer_log->getInfo(array('id'=>$v['last_id']));
            $add_time = $res_linfo['add_time'];
            $time_str = viewTimes(strtotime($res_linfo['add_time']));
            $datalist[]=array('customer_id'=>$v['customer_id'],'name'=>$v['name'],'mobile'=>$v['mobile'],
                'action'=>$res_linfo['action_name'],'add_time'=>$add_time,'time_str'=>$time_str);
        }
        $this->to_back(array('datalist'=>$datalist));
    }

}