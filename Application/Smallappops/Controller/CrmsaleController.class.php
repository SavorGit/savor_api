<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class CrmsaleController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'visitconfig':
                $this->valid_fields = array('openid'=>1001,'purpose_ids'=>1002,'type_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'getOpsusers':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'staff_ids'=>1002);
                $this->is_verify = 1;
                break;
            case 'getContactUsers':
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1002,'keywords'=>1002);
                $this->is_verify = 1;
                break;
            case 'addrecord':
                $this->valid_fields = array('openid'=>1001,'visit_purpose'=>1001,'visit_type'=>1001,'contact_id'=>1002,
                    'type'=>1001,'content'=>1002,'images'=>1002,'signin_time'=>1002,'signin_hotel_id'=>1002,
                    'signout_time'=>1002,'signout_hotel_id'=>1002,'review_uid'=>1002,'cc_uids'=>1002,'salerecord_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'recordinfo':
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'recordlist':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001,'pagesize'=>1002,'area_id'=>1002,'staff_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'unreadnum':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'area_id'=>1002,'staff_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'hotelcontactlist':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001,'pagesize'=>1002,'hotel_id'=>1002,'contact_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'addcomment':
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001,'content'=>1001,'comment_id'=>1002,'cc_uids'=>1002);
                $this->is_verify = 1;
                break;
            case 'getCommentsByRecordId':
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'getReadList':
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'delcomment':
                $this->valid_fields = array('openid'=>1001,'comment_id'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function visitconfig(){
        $openid = $this->params['openid'];
        $purpose_ids = $this->params['purpose_ids'];
        $type_id = $this->params['type_id'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ids = array();
        if(!empty($type_id)){
            $ids[]=intval($type_id);
        }
        if(!empty($purpose_ids)){
            $purpose_ids_arr = explode(',',$purpose_ids);
            foreach ($purpose_ids_arr as $v){
                $n_id = intval($v);
                if($n_id>0){
                    $ids[]=$n_id;
                }
            }
        }

        $m_category = new \Common\Model\Smallapp\CategoryModel();
        $res_category = $m_category->getDataList('id,name,type',array('type'=>array('in','9,10')),'id desc');
        $purpose = $types = array(array('id'=>0,'name'=>'请选择','checked'=>false));
        foreach ($res_category as $v){
            $checked = false;
            if(!empty($ids)){
                if(in_array($v['id'],$ids)){
                    $checked = true;
                }
            }else{
                if($v['id']==171){
                    $checked = true;
                }
            }
            $info = array('id'=>$v['id'],'name'=>$v['name'],'checked'=>$checked);
            if($v['type']==10){
                $types[]=$info;
            }else{
                $purpose[]=$info;
            }
        }
        $res_data = array('purpose'=>$purpose,'types'=>$types);
        $this->to_back($res_data);
    }

    public function getOpsusers(){
        $openid = $this->params['openid'];
        $staff_ids = $this->params['staff_ids'];
        $type = intval($this->params['type']);//类型1点评人 2抄送人 3评论@人
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $check_staff_ids = array();
        if(!empty($staff_ids)){
            if($type==1){
                $check_staff_ids[]=$staff_ids;
            }else{
                $check_staff_ids = explode(',',$staff_ids);
            }
        }
        $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
        $lately = array();
        $lately_staff_ids = array();
        $rwhere = array();
        if($type==3){
            $m_comment = new \Common\Model\Crm\CommentModel();
            $res_comment = $m_comment->getALLDataList('id',array('ops_staff_id'=>$res_staff['id']),'id desc','0,1','');
            if(!empty($res_comment[0]['id'])){
                $rwhere = array('a.comment_id'=>$res_comment[0]['id'],'a.type'=>$type);
            }
        }else{
            $res_late_remind = $m_salerecord_remind->getList('a.salerecord_id',array('record.ops_staff_id'=>$res_staff['id'],'a.type'=>$type),'a.id desc','0,1','');
            if(!empty($res_late_remind[0]['salerecord_id'])){
                $rwhere = array('a.salerecord_id'=>$res_late_remind[0]['salerecord_id'],'a.type'=>$type);
            }
        }
        if(!empty($rwhere)){
            $fields = 'a.remind_user_id as staff_id,a.type,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
            $res_remind = $m_salerecord_remind->getList($fields,$rwhere,'a.id desc','','a.remind_user_id');
            foreach ($res_remind as $v){
                $checked = false;
                if(in_array($v['staff_id'],$check_staff_ids)){
                    $checked = true;
                }
                $v['checked'] = $checked;
                $lately[]=$v;
                $lately_staff_ids[]=$v['staff_id'];
            }
        }
        $fields = 'a.id as staff_id,a.job,su.remark as staff_name,user.avatarUrl,user.nickName';
        $res_user = $m_opstaff->getStaffUserinfo($fields,array('a.status'=>1,'a.sysuser_id'=>array('gt',0)));
        $all_user = array();
        foreach ($res_user as $v){
            $is_lately = 0;
            if(in_array($v['staff_id'],$lately_staff_ids)){
                $is_lately = 1;
            }
            $v['is_lately'] = $is_lately;
            $checked = false;
            if(in_array($v['staff_id'],$check_staff_ids)){
                $checked = true;
            }
            $v['checked'] = $checked;
            $letter = getFirstCharter($v['staff_name']);
            $all_user[$letter][]=$v;
        }
        ksort($all_user);
        $user_data = array();
        foreach ($all_user as $k=>$v){
            $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
            $user_data[]=$dinfo;
        }
        $res_data = array('lately'=>$lately,'all_user'=>$user_data);
        $this->to_back($res_data);
    }

    public function getContactUsers(){
        $openid = $this->params['openid'];
        $keywords = trim($this->params['keywords']);
        $hotel_id = intval($this->params['hotel_id']);
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_crmuser = new \Common\Model\Crm\ContactModel;
        $where = array('a.status'=>1);
        if(!empty($keywords)){
            if($hotel_id>0){
                $where['a.name'] = array('like',"%$keywords%");
            }else{
                $where['hotel.name'] = array('like',"%$keywords%");
            }
        }
        if($hotel_id>0){
            $where['a.hotel_id'] = $hotel_id;
        }
        if(empty($hotel_id) && empty($keywords)){
            $res_user = array();
        }else{
            $fields = 'a.id,a.name,a.job,a.department,a.hotel_id,a.avatar_url,hotel.name as hotel_name';
            $res_user = $m_crmuser->getUserList($fields,$where,'a.id desc');
        }
        $all_user = array();
        $oss_host = get_oss_host();
        foreach ($res_user as $v){
            $hotel_name = '';
            if(!empty($v['hotel_name'])){
                $hotel_name = $v['hotel_name'];
            }
            $img_avatar_url = '';
            if(!empty($v['avatar_url'])){
                if(substr($v['avatar_url'],0,4)!='http'){
                    $img_avatar_url = $oss_host.$v['avatar_url'];
                }else{
                    $img_avatar_url = $v['avatar_url'];
                }
            }
            $info = array('contact_id'=>$v['id'],'name'=>$v['name'],'hotel_id'=>$v['hotel_id'],'hotel_name'=>$hotel_name,
                'img_avatar_url'=>$img_avatar_url,'job'=>$v['job'],'department'=>$v['department'],'checked'=>false);
            $letter = getFirstCharter($v['name']);
            $all_user[$letter][]=$info;
        }
        ksort($all_user);
        $data = array();
        foreach ($all_user as $k=>$v){
            $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
            $data[]=$dinfo;
        }
        $this->to_back($data);
    }

    public function addrecord(){
        $openid = $this->params['openid'];
        $visit_purpose = $this->params['visit_purpose'];
        $visit_type = intval($this->params['visit_type']);
        $contact_id = intval($this->params['contact_id']);
        $type = intval($this->params['type']);//类型1保存2提交
        $content = trim($this->params['content']);
        $images = $this->params['images'];
        $signin_time = $this->params['signin_time'];
        $signin_hotel_id = intval($this->params['signin_hotel_id']);
        $signout_time = $this->params['signout_time'];
        $signout_hotel_id = intval($this->params['signout_hotel_id']);
        $review_uid = intval($this->params['review_uid']);
        $cc_uids = $this->params['cc_uids'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $status = 1;
        if($type==2){
            unset($this->valid_fields['images'],$this->valid_fields['salerecord_id'],$this->valid_fields['contact_id']);
            if($visit_type!=171){
                unset($this->valid_fields['signin_time'],$this->valid_fields['signin_hotel_id'],
                    $this->valid_fields['signout_time'],$this->valid_fields['signout_hotel_id']);
            }
            foreach ($this->valid_fields as $k=>$v){
                if(empty($this->params["$k"])){
                    $this->to_back(1001);
                }
            }
            $status = 2;
        }
        $ops_staff_id = $res_staff['id'];
        $add_data = array('ops_staff_id'=>$ops_staff_id,'visit_purpose'=>",$visit_purpose,",'visit_type'=>$visit_type,
            'contact_id'=>$contact_id,'status'=>$status);
        if(!empty($content))    $add_data['content'] = $content;
        if(!empty($images))     $add_data['images'] = $images;
        if(!empty($signin_time) && !empty($signin_hotel_id)){
            $add_data['signin_time'] = $signin_time;
            $add_data['signin_hotel_id'] = $signin_hotel_id;
        }
        if(!empty($signout_time) && !empty($signout_hotel_id)){
            if($signin_hotel_id!=$signout_hotel_id){
                $this->to_back(94005);
            }
            $add_data['signout_time'] = $signout_time;
            $add_data['signout_hotel_id'] = $signout_hotel_id;
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $m_saleremind = new \Common\Model\Crm\SalerecordRemindModel();
        $add_remind = array();
        if($salerecord_id){
            $add_data['update_time'] = date('Y-m-d H:i:s');
            $m_salerecord->updateData(array('id'=>$salerecord_id),$add_data);

            $m_saleremind->delData(array('salerecord_id'=>$salerecord_id,'type'=>array('in','1,2')));
            if(!empty($review_uid)){
                $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>1,'remind_user_id'=>$review_uid);
            }
            if(!empty($cc_uids)){
                $arr_cc_uids = explode(',',$cc_uids);
                foreach ($arr_cc_uids as $v){
                    $remind_user_id = intval($v);
                    if($remind_user_id>0){
                        $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>2,'remind_user_id'=>$remind_user_id);
                    }
                }
            }
        }else{
            $salerecord_id = $m_salerecord->add($add_data);
            if(!empty($review_uid)){
                $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>1,'remind_user_id'=>$review_uid);
            }
            if(!empty($cc_uids)){
                $arr_cc_uids = explode(',',$cc_uids);
                foreach ($arr_cc_uids as $v){
                    $remind_user_id = intval($v);
                    if($remind_user_id>0){
                        $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>2,'remind_user_id'=>$remind_user_id);
                    }
                }
            }
            $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>4,'remind_user_id'=>$ops_staff_id);
        }
        if(!empty($add_remind)){
            $m_saleremind->addAll($add_remind);
        }
        $this->to_back(array('salerecord_id'=>$salerecord_id));
    }

    public function recordinfo(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $res_info = $m_salerecord->getInfo(array('id'=>$salerecord_id));
        $fields = 'a.id as staff_id,a.job,su.remark as staff_name,user.avatarUrl,user.nickName';
        $res_staff_user = $m_opstaff->getStaffUserinfo($fields,array('a.id'=>$res_info['ops_staff_id']));
        $res_info['staff_id'] = $res_staff_user[0]['staff_id'];
        $res_info['staff_name'] = $res_staff_user[0]['staff_name'];
        $res_info['avatarUrl'] = $res_staff_user[0]['avatarUrl'];
        $res_info['job'] = $res_staff_user[0]['job'];
        $m_category = new \Common\Model\Smallapp\CategoryModel();
        $visit_purpose = $res_info['visit_purpose'];
        $visit_purpose_str = $visit_type_str = '';
        if(!empty($visit_purpose)){
            $visit_purpose = trim($visit_purpose,',');
            $res_cate = $m_category->getDataList('id,name',array('id'=>array('in',$visit_purpose)),'');
            foreach ($res_cate as $v){
                $visit_purpose_str.="{$v['name']},";
            }
            $visit_purpose_str = trim($visit_purpose_str,',');
        }
        if($res_info['visit_type']){
            $res_cate = $m_category->getInfo(array('id'=>$res_info['visit_type']));
            $visit_type_str = $res_cate['name'];
        }
        $res_info['visit_purpose'] = $visit_purpose;
        $res_info['visit_purpose_str'] = $visit_purpose_str;
        $res_info['visit_type_str'] = $visit_type_str;
        $images_path = $images_url = array();
        if(!empty($res_info['images'])){
            $arr_images_path = explode(',',$res_info['images']);
            $oss_host = get_oss_host();
            foreach ($arr_images_path as $v){
                if(!empty($v)){
                    $images_path[]=$v;
                    $images_url[]=$oss_host.$v.'?x-oss-process=image/quality,Q_80';
                }
            }
        }
        $res_info['images_path'] = $images_path;
        $res_info['images_url'] = $images_url;
        if($res_info['signin_time']=='0000-00-00 00:00:00'){
            $res_info['signin_time'] = '';
        }
        if($res_info['signout_time']=='0000-00-00 00:00:00'){
            $res_info['signout_time'] = '';
        }
        $contact = array();
        if($res_info['contact_id']){
            $m_contact = new \Common\Model\Crm\ContactModel();
            $res_contact = $m_contact->getUserList('a.name,hotel.name as hotel_name',array('a.id'=>$res_info['contact_id']),'a.id desc');
            $contact_uname = $res_contact[0]['name'];
            $contact_hotel_name = $res_contact[0]['hotel_name'];
            $contact = array('contact_id'=>$res_info['contact_id'],'name'=>$contact_uname,'hotel_name'=>$contact_hotel_name);
        }
        $res_info['contact'] = $contact;
        $res_info['add_time'] = date('m月d日 H:i',strtotime($res_info['add_time']));

        unset($res_info['status'],$res_info['update_time']);
        $hotel_name = '';
        $hotel_id   = 0;
        if($res_info['signin_hotel_id']){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('id,name',$res_info['signin_hotel_id']);
            $hotel_name = $res_hotel['name'];
            $hotel_id   = $res_hotel['id'];
        }
        $res_info['hotel_name'] = $hotel_name;
        $res_info['hotel_id']   = $hotel_id;
        $consume_time = '';
        if(!empty($res_info['signin_time']) && !empty($res_info['signout_time'])){
            $consume_time = round((strtotime($res_info['signout_time'])-strtotime($res_info['signin_time']))/60);
            if($consume_time>0){
                $consume_time.='分钟';
            }
        }
        $res_info['consume_time'] = $consume_time;
        $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
        $fields = 'a.remind_user_id as staff_id,a.type,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
        $res_remind = $m_salerecord_remind->getList($fields,array('a.salerecord_id'=>$salerecord_id,'a.type'=>array('in','1,2')),'a.id desc');
        $all_remind_user = array();
        foreach ($res_remind as $v){
            $all_remind_user[$v['type']][]=$v;
        }
        $cc_users = $review_users = array();
        if(isset($all_remind_user[1])){
            $review_users = $all_remind_user[1];
        }
        if(isset($all_remind_user[2])){
            $cc_users = $all_remind_user[2];
        }
        $res_info['cc_users'] = $cc_users;
        $res_info['review_users'] = $review_users;

        $m_salerecord_read = new \Common\Model\Crm\SalerecordReadModel();
        $m_salerecord_read->readRecord($res_staff,$res_info);
        $is_read_button = 1;
        if($res_staff['id']==$res_info['ops_staff_id'] || $res_staff['hotel_role_type']==3){
            $is_read_button = 0;
        }
        $res_info['is_read_button'] = $is_read_button;
        $stock_check_percent='';
        if(!empty($res_info['stock_check_num']) && !empty($res_info['stock_check_hadnum'])){
            $stock_check_percent = intval(($res_info['stock_check_hadnum']/$res_info['stock_check_num'])*100);
            $stock_check_percent = $stock_check_percent.'%';
        }
        $res_info['stock_check_percent'] = $stock_check_percent;
        $this->to_back($res_info);
    }

    public function recordlist(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//类型1全部,2@我的,3销售记录,4盘点记录
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        if(empty($pagesize)){
            $pagesize = 20;
        }
        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
        $orderby = 'a.salerecord_id desc';
        $unread_where = array();
        switch ($type){
            case 1:
                $where1 = array('a.type'=>array('in','4,5'),'record.status'=>2);
                $permission = json_decode($res_staff['permission'],true);
                if(in_array($res_staff['hotel_role_type'],array(5,6))){
                    $where1 = array('a.type'=>5,'record.status'=>2,'record.type'=>2);
                }
                if($res_staff['is_operrator']==0){
                    if($permission['hotel_info']['type']==2 || $permission['hotel_info']['type']==4){
                        $where1['staff.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                    }
                    if($permission['hotel_info']['type']==3){
                        $where1['record.ops_staff_id'] = $ops_staff_id;
                    }
                }else{
                    $where1['record.ops_staff_id'] = $ops_staff_id;
                }
                
                $where2 = array('a.remind_user_id'=>$ops_staff_id);
                $where['_complex'] = array($where1,$where2,'_logic'=>'or');
                $orderby = 'a.salerecord_id desc,record.status asc';
                break;
            case 2:
                $where = array('a.remind_user_id'=>$ops_staff_id,'record.status'=>2,'a.status'=>1);
                if(in_array($res_staff['hotel_role_type'],array(5,6))){
                    $where = array('a.remind_user_id'=>$ops_staff_id,'record.status'=>2,'a.status'=>1,'record.type'=>2);
                }
                $where['a.type'] = 3;
                $orderby = 'read_status asc,a.salerecord_id desc';
                break;
            case 3:
                $where = array('a.type'=>4,'a.status'=>1,'record.status'=>2);
                if($res_staff['is_operrator']==0){
                    if($area_id>0 || $staff_id>0){
                        if($area_id){
                            $where['staff.area_id'] = $area_id;
                        }
                        if($staff_id>0){
                            $where['record.ops_staff_id'] = $staff_id;
                        }
                    }else{
                        $permission = json_decode($res_staff['permission'],true);
                        if($permission['hotel_info']['type']==2 || $permission['hotel_info']['type']==4){
                            $where['staff.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                        }
                        if($permission['hotel_info']['type']==3){
                            $where['record.ops_staff_id'] = $ops_staff_id;
                        }
                    }
                }else{
                    $where['record.ops_staff_id'] = $ops_staff_id;
                }
                $orderby = 'a.salerecord_id desc';
                if(in_array($res_staff['hotel_role_type'],array(1,2,4,6)) && $area_id==0){
                    $unread_where = array('a.remind_user_id'=>$ops_staff_id,'a.read_status'=>1,'a.type'=>4,'a.status'=>1,'record.status'=>2);
                }
                break;
            case 4:
                $where = array('record.type'=>2,'a.status'=>1,'record.status'=>2);
                if($res_staff['is_operrator']==0){
                    if($area_id>0 || $staff_id>0){
                        if($area_id){
                            $where['staff.area_id'] = $area_id;
                        }
                        if($staff_id>0){
                            $where['record.ops_staff_id'] = $staff_id;
                        }
                    }else{
                        $permission = json_decode($res_staff['permission'],true);
                        if(in_array($permission['hotel_info']['type'],array(2,4,6))){
                            $where['staff.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                        }elseif($permission['hotel_info']['type']==3){
                            $where['record.ops_staff_id'] = $ops_staff_id;
                        }
                    }
                }else{
                    $where['record.ops_staff_id'] = $ops_staff_id;
                }
                $orderby = 'a.salerecord_id desc';
                if(in_array($res_staff['hotel_role_type'],array(1,2,4,6)) && $area_id==0){
                    $unread_where = array('a.remind_user_id'=>$ops_staff_id,'a.read_status'=>1,'a.status'=>1,'record.type'=>2,'record.status'=>2);
                }
                break;
            default:
                $where = array();
        }
        $res_unread_mind = array();
        $unread_ids = array();
        if(!empty($unread_where)){
            $unread_fields = 'a.salerecord_id,min(a.read_status) as read_status,count(a.id) as num,record.*,staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
            $res_unread_mind = $m_salerecord_remind->getRemindRecordList($unread_fields,$unread_where,'a.salerecord_id desc','','a.salerecord_id');
            foreach ($res_unread_mind as $v){
                $unread_ids[]=$v['salerecord_id'];
            }
        }
        $fields = 'a.salerecord_id,min(a.read_status) as read_status,count(a.id) as num,record.*,staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
        $res_mind = $m_salerecord_remind->getRemindRecordList($fields,$where,$orderby,$limit,'a.salerecord_id');
        if(!empty($res_unread_mind)){
            $res_mind = array_merge($res_unread_mind,$res_mind);
        }
        $datalist = array();
        if(!empty($res_mind)){
            $os = check_phone_os();
            $format_webp = '';
            if($os==1){
                $format_webp = '/format,webp';
            }
            $m_category = new \Common\Model\Smallapp\CategoryModel();
            $m_comment = new \Common\Model\Crm\CommentModel();
            $m_hotel = new \Common\Model\HotelModel();
            foreach ($res_mind as $v){
                if($v['status']==1 && $v['ops_staff_id']!=$ops_staff_id){
                    continue;
                }
                $salerecord_id = $v['salerecord_id'];
                $record_info = $v;
                $staff_id = $v['staff_id'];
                $staff_name = !empty($v['staff_name']) ? $v['staff_name'] :'小热点';
                $avatarUrl = $v['avatarUrl'];
                $job = $v['job'];
                $now = time();
                $diff_time = $now - strtotime($record_info['add_time']);
                if($diff_time<=86400){
                    $add_time = viewTimes(strtotime($record_info['add_time']));
                }else{
                    $add_time = date('m月d日 H:i',strtotime($record_info['add_time']));
                }
                $hotel_name = '';
                $hotel_id   = 0;
                $consume_time = $signin_time = $signout_time = '';
                if($record_info['visit_type']==171 || $record_info['type']==2){
                    if ($record_info['signin_hotel_id']) {
                        $res_hotel = $m_hotel->getOneById('id,name', $record_info['signin_hotel_id']);
                        $hotel_name = $res_hotel['name'];
                        $hotel_id = $res_hotel['id'];
                    }
                }
                if($record_info['visit_type']==171){
                    if($record_info['signin_time']!='0000-00-00 00:00:00'){
                        $signin_time = $record_info['signin_time'];
                    }
                    if($record_info['signout_time']!='0000-00-00 00:00:00'){
                        $signout_time = $record_info['signout_time'];
                    }
                    if(!empty($signin_time) && !empty($signout_time)){
                        $consume_time = round((strtotime($signout_time)-strtotime($signin_time))/60);
                        if($consume_time>=0){
                            $consume_time.='分钟';
                        }
                    }
                }
                $images_url = array();
                if(!empty($record_info['images'])){
                    $arr_images_path = explode(',',$record_info['images']);
                    $oss_host = get_oss_host();
                    foreach ($arr_images_path as $iv){
                        if(!empty($iv)){
                            $images_url[]=$oss_host.$iv."?x-oss-process=image/resize,m_mfit,h_300,w_300$format_webp";
                        }
                    }
                }
                $visit_purpose = $record_info['visit_purpose'];
                $visit_purpose_str = $visit_type_str = '';
                if(!empty($visit_purpose)){
                    $visit_purpose = trim($visit_purpose,',');
                    $res_cate = $m_category->getDataList('id,name',array('id'=>array('in',$visit_purpose)),'');
                    foreach ($res_cate as $cv){
                        $visit_purpose_str.="{$cv['name']},";
                    }
                    $visit_purpose_str = trim($visit_purpose_str,',');
                }
                if($record_info['visit_type']){
                    $res_cate = $m_category->getInfo(array('id'=>$record_info['visit_type']));
                    $visit_type_str = $res_cate['name'];
                }
                $comment_num = 0;
                $res_comment = $m_comment->getDataList('count(*) as num',array('salerecord_id'=>$salerecord_id,'status'=>1),'');
                if(!empty($res_comment[0]['num'])){
                    $comment_num = intval($res_comment[0]['num']);
                }
                if(!empty($record_info['content'])){
                    $record_info['content'] = text_substr($record_info['content'], 100,'...');
                }
                if(in_array($type,array(3,4)) && !in_array($salerecord_id,$unread_ids)){
                    $res_data = $m_salerecord_remind->getALLDataList('id,read_status',array('remind_user_id'=>$ops_staff_id,'salerecord_id'=>$record_info['id'],'status'=>1),'id desc','0,1','');
                    if(!empty($res_data)){
                        $record_info['read_status'] = $res_data[0]['read_status'];
                    }else{
                        $record_info['read_status'] = 0;
                    }
                }

                $stock_check_percent='';
                if(!empty($record_info['stock_check_num']) && !empty($record_info['stock_check_hadnum'])){
                    $stock_check_percent = intval(($record_info['stock_check_hadnum']/$record_info['stock_check_num'])*100);
                    $stock_check_percent = $stock_check_percent.'%';
                }
                $info = array('salerecord_id'=>$salerecord_id,'staff_id'=>$staff_id,'staff_name'=>$staff_name,'avatarUrl'=>$avatarUrl,'job'=>$job,
                    'add_time'=>$add_time,'visit_purpose_str'=>$visit_purpose_str,'visit_type_str'=>$visit_type_str,'content'=>$record_info['content'],
                    'images_url'=>$images_url,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,'consume_time'=>$consume_time,'signin_time'=>$signin_time,'signout_time'=>$signout_time,
                    'comment_num'=>$comment_num,'status'=>$record_info['status'],'read_status'=>$record_info['read_status'],'record_type'=>$record_info['type'],
                    'stock_check_num'=>$record_info['stock_check_num'],'stock_check_hadnum'=>$record_info['stock_check_hadnum'],'stock_check_percent'=>$stock_check_percent,
                    'stock_check_status'=>$record_info['stock_check_status'],'stock_check_error'=>$record_info['stock_check_error'],
                );
                $datalist[]=$info;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function unreadnum(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//类型2@我的,3销售记录,4盘点记录
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
        $orderby = 'a.salerecord_id desc';
        switch ($type){
            case 2:
                $where = array('a.remind_user_id'=>$ops_staff_id,'a.status'=>1,'a.read_status'=>1);
                if(in_array($res_staff['hotel_role_type'],array(5,6))){
                    $where = array('a.remind_user_id'=>$ops_staff_id,'a.status'=>1,'a.read_status'=>1,'record.type'=>2);
                }
                $where['a.type'] = 3;
                $where['record.status'] = 2;
                break;
            case 3:
                $where = array('a.remind_user_id'=>$ops_staff_id,'a.read_status'=>1,'a.type'=>array('in','1,2'),'a.status'=>1,'record.status'=>2);
                if($res_staff['is_operrator']==0){
                    if($area_id>0 || $staff_id>0){
                        if($area_id){
                            $where['staff.area_id'] = $area_id;
                        }
                        if($staff_id>0){
                            $where['record.ops_staff_id'] = $staff_id;
                        }
                    }else{
                        $permission = json_decode($res_staff['permission'],true);
                        if($permission['hotel_info']['type']==2 || $permission['hotel_info']['type']==4){
                            $where['staff.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                        }
                    }
                }else{
                    $where['record.ops_staff_id'] = $ops_staff_id;
                }
                break;
            case 4:
                $where = array('a.remind_user_id'=>$ops_staff_id,'a.read_status'=>1,'record.type'=>2,'a.status'=>1,'record.status'=>2);
                if($res_staff['is_operrator']==0){
                    if($area_id>0 || $staff_id>0){
                        if($area_id){
                            $where['staff.area_id'] = $area_id;
                        }
                        if($staff_id>0){
                            $where['record.ops_staff_id'] = $staff_id;
                        }
                    }else{
                        $permission = json_decode($res_staff['permission'],true);
                        if(in_array($permission['hotel_info']['type'],array(2,4,6))){
                            $where['staff.area_id'] = array('in',$permission['hotel_info']['area_ids']);
                        }elseif($permission['hotel_info']['type']==3){
                            $where['record.ops_staff_id'] = $ops_staff_id;
                        }
                    }
                }else{
                    $where['record.ops_staff_id'] = $ops_staff_id;
                }
                break;
            default:
                $where = array();
        }
        if($res_staff['hotel_role_type']==3){
            $unread_num = 0;
        }else{
            $fields = 'count(DISTINCT a.salerecord_id) as num';
            $res_mind = $m_salerecord_remind->getRemindRecordList($fields,$where,$orderby,'','');
            $unread_num = intval($res_mind[0]['num']);
        }
        $this->to_back(array('unread_num'=>$unread_num));
    }

    public function readRecord(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $record = $m_salerecord->getInfo(array('id'=>$salerecord_id));
        if($res_staff['id']==$record['ops_staff_id'] || $res_staff['hotel_role_type']==3){
            $this->to_back(array());
        }
        $m_read_record = new \Common\Model\Crm\SalerecordReadModel();
        $res_read = $m_read_record->getInfo(array('salerecord_id'=>$salerecord_id,'user_id'=>$res_staff['id']));
        if(empty($res_read)){
            $add_data = array('salerecord_id'=>$salerecord_id,'user_id'=>$res_staff['id'],'add_time'=>date('Y-m-d H:i:s'));
            $m_read_record->add($add_data);
        }
        $this->to_back(array());
    }

    public function getReadList(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_read = new \Common\Model\Crm\SalerecordReadModel();
        $fields = 'a.user_id,sysuser.remark,user.nickName,user.avatarUrl,a.add_time';
        $datalist = $m_read->getReadDataList($fields,array('a.salerecord_id'=>$salerecord_id),'a.id desc','0,10');
        foreach ($datalist as $k=>$v){
            $datalist[$k]['add_time'] = date('m月d日 H:i',strtotime($v['add_time']));
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function addcomment(){
        $openid = $this->params['openid'];
        $content = trim($this->params['content']);
        $salerecord_id = intval($this->params['salerecord_id']);
        $comment_id = intval($this->params['comment_id']);
        $cc_uids = $this->params['cc_uids'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $add_data = array('ops_staff_id'=>$ops_staff_id,'salerecord_id'=>$salerecord_id,'content'=>$content,'comment_id'=>$comment_id);
        $type = 1;
        if($comment_id>0){
            $type = 2;
        }
        $add_data['type'] = $type;
        $m_comment = new \Common\Model\Crm\CommentModel();
        $now_comment_id = $m_comment->add($add_data);
        if(!empty($cc_uids)){
            $m_saleremind = new \Common\Model\Crm\SalerecordRemindModel();
            $arr_cc_uids = explode(',',$cc_uids);
            $add_remind = array();
            foreach ($arr_cc_uids as $v){
                $remind_user_id = intval($v);
                if($remind_user_id>0){
                    $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>3,'comment_id'=>$now_comment_id,'remind_user_id'=>$remind_user_id);
                }
            }
            $m_saleremind->addAll($add_remind);
        }
        $this->to_back(array('comment_id'=>$now_comment_id));
    }

    public function getCommentsByRecordId(){
        $openid = $this->params['openid'];
        $salerecord_id = intval($this->params['salerecord_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_comment = new \Common\Model\Crm\CommentModel();
        $res_comment = $m_comment->getDataList('*',array('salerecord_id'=>$salerecord_id,'status'=>1),'id desc');
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $m_saleremind = new \Common\Model\Crm\SalerecordRemindModel();
        $datalist = array();
        foreach ($res_comment as $v){
            $cc_users = '';
            $cc_where = array('comment_id'=>$v['id'],'type'=>3,'salerecord_id'=>$salerecord_id);
            $res_cc = $m_saleremind->getDataList('remind_user_id',$cc_where,'');
            if(!empty($res_cc)){
                foreach ($res_cc as $cv){
                    $res_cu = $m_opstaff->getStaffinfo('su.remark as staff_name',array('a.id'=>$cv['remind_user_id']));
                    $cc_users.="{$res_cu[0]['staff_name']},";
                }
                $cc_users = trim($cc_users,",");
                $cc_users = "@$cc_users";
            }
            $replay_user = '';
            if($v['comment_id']){
                $res_recomment = $m_comment->getInfo(array('id'=>$v['comment_id']));
                $res_cu = $m_opstaff->getStaffinfo('su.remark as staff_name',array('a.id'=>$res_recomment['ops_staff_id']));
                $replay_user = "@{$res_cu[0]['staff_name']}";
            }
            $add_time = date('m月d日 H:i',strtotime($v['add_time']));
            $fields = 'a.id as staff_id,a.job,su.remark as staff_name,user.avatarUrl';
            $res_staff_user = $m_opstaff->getStaffUserinfo($fields,array('a.id'=>$v['ops_staff_id']));
            $is_delcomment = 0;
            if($v['ops_staff_id']==$ops_staff_id){
                $res_replaycomment = $m_comment->getInfo(array('comment_id'=>$v['id'],'status'=>1));
                if(empty($res_replaycomment)){
                    $is_delcomment = 1;
                }
            }
            $info = array('comment_id'=>$v['id'],'content'=>$v['content'],'staff_id'=>$res_staff_user[0]['staff_id'],'cc_users'=>$cc_users,
                'replay_user'=>$replay_user,'staff_name'=>$res_staff_user[0]['staff_name'],'avatarUrl'=>$res_staff_user[0]['avatarUrl'],
                'is_delcomment'=>$is_delcomment,'add_time'=>$add_time);
            $datalist[]=$info;
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function delcomment(){
        $openid = $this->params['openid'];
        $comment_id = intval($this->params['comment_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_id = $res_staff['id'];
        $m_comment = new \Common\Model\Crm\CommentModel();
        $res_comment = $m_comment->getInfo(array('id'=>$comment_id));
        if(!empty($res_comment) && $res_comment['ops_staff_id']==$ops_staff_id){
            $res_replaycomment = $m_comment->getInfo(array('comment_id'=>$comment_id,'status'=>1));
            if(empty($res_replaycomment)){
                $m_comment->updateData(array('id'=>$comment_id),array('status'=>2));
                $m_record_remind = new \Common\Model\Crm\SalerecordRemindModel();
                $m_record_remind->updateData(array('type'=>3,'comment_id'=>$comment_id),array('status'=>2));
            }
        }
        $this->to_back(array());
    }

    public function hotelcontactlist(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//类型1酒楼 2个人
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $hotel_id = intval($this->params['hotel_id']);
        $contact_id = intval($this->params['contact_id']);
        if(empty($pagesize)){
            $pagesize = 20;
        }
        $start = ($page-1)*$pagesize;
        $limit = "$start,$pagesize";
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $where = array();
        if($type==1){
            if(empty($hotel_id)){
                $this->to_back(1001);
            }
            $where['record.signin_hotel_id'] = $hotel_id;
        }else{
            if(empty($contact_id)){
                $this->to_back(1001);
            }
            $where['record.contact_id'] = $contact_id;
        }
        $where['record.status'] = 2;

        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $fields = 'record.*,staff.id as staff_id,staff.job,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
        $res_salerecord = $m_salerecord->getRecordList($fields,$where,'id desc',$limit,'');
        $datalist = array();
        if(!empty($res_salerecord)){
            $os = check_phone_os();
            $format_webp = '';
            if($os==1){
                $format_webp = '/format,webp';
            }
            $m_category = new \Common\Model\Smallapp\CategoryModel();
            $m_comment = new \Common\Model\Crm\CommentModel();
            $m_hotel = new \Common\Model\HotelModel();
            foreach ($res_salerecord as $v){
                $salerecord_id = $v['id'];
                $record_info = $v;
                $staff_id = $v['staff_id'];
                $staff_name = !empty($v['staff_name']) ? $v['staff_name'] :'小热点';
                $avatarUrl = $v['avatarUrl'];
                $job = $v['job'];
                $now = time();
                $diff_time = $now - strtotime($record_info['add_time']);
                if($diff_time<=86400){
                    $add_time = viewTimes(strtotime($record_info['add_time']));
                }else{
                    $add_time = date('m月d日 H:i',strtotime($record_info['add_time']));
                }
                $hotel_name = '';
                $consume_time = $signin_time = $signout_time = '';
                if($record_info['visit_type']==171){
                    if($record_info['signin_hotel_id']){
                        $res_hotel = $m_hotel->getOneById('name',$record_info['signin_hotel_id']);
                        $hotel_name = $res_hotel['name'];
                    }
                    if($record_info['signin_time']!='0000-00-00 00:00:00'){
                        $signin_time = $record_info['signin_time'];
                    }
                    if($record_info['signout_time']!='0000-00-00 00:00:00'){
                        $signout_time = $record_info['signout_time'];
                    }
                    if(!empty($signin_time) && !empty($signout_time)){
                        $consume_time = round((strtotime($signout_time)-strtotime($signin_time))/60);
                        if($consume_time>=0){
                            $consume_time.='分钟';
                        }
                    }
                }
                $images_url = array();
                if(!empty($record_info['images'])){
                    $arr_images_path = explode(',',$record_info['images']);
                    $oss_host = get_oss_host();
                    foreach ($arr_images_path as $iv){
                        if(!empty($iv)){
                            $images_url[]=$oss_host.$iv."?x-oss-process=image/resize,m_mfit,h_300,w_300$format_webp";
                        }
                    }
                }
                $visit_purpose = $record_info['visit_purpose'];
                $visit_purpose_str = $visit_type_str = '';
                if(!empty($visit_purpose)){
                    $visit_purpose = trim($visit_purpose,',');
                    $res_cate = $m_category->getDataList('id,name',array('id'=>array('in',$visit_purpose)),'');
                    foreach ($res_cate as $cv){
                        $visit_purpose_str.="{$cv['name']},";
                    }
                    $visit_purpose_str = trim($visit_purpose_str,',');
                }
                if($record_info['visit_type']){
                    $res_cate = $m_category->getInfo(array('id'=>$record_info['visit_type']));
                    $visit_type_str = $res_cate['name'];
                }
                $comment_num = 0;
                $res_comment = $m_comment->getDataList('count(*) as num',array('salerecord_id'=>$salerecord_id,'status'=>1),'');
                if(!empty($res_comment[0]['num'])){
                    $comment_num = intval($res_comment[0]['num']);
                }
                if(!empty($record_info['content'])){
                    $record_info['content'] = text_substr($record_info['content'], 100,'...');
                }
                $info = array('salerecord_id'=>$salerecord_id,'staff_id'=>$staff_id,'staff_name'=>$staff_name,'avatarUrl'=>$avatarUrl,'job'=>$job,
                    'add_time'=>$add_time,'visit_purpose_str'=>$visit_purpose_str,'visit_type_str'=>$visit_type_str,'content'=>$record_info['content'],
                    'images_url'=>$images_url,'hotel_id'=>$record_info['signin_hotel_id'],'hotel_name'=>$hotel_name,'consume_time'=>$consume_time,'signin_time'=>$signin_time,'signout_time'=>$signout_time,
                    'comment_num'=>$comment_num,'status'=>$record_info['status'],
                );
                $datalist[]=$info;

            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

}
