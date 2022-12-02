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
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'getOpsusers':
                $this->valid_fields = array('openid'=>1001,'type'=>1001);
                $this->is_verify = 1;
                break;
            case 'getContactUsers':
                $this->valid_fields = array('openid'=>1001,'keywords'=>1002);
                $this->is_verify = 1;
                break;
            case 'addrecord':
                $this->valid_fields = array('openid'=>1001,'visit_purpose'=>1001,'visit_type'=>1001,'contact_id'=>1001,
                    'type'=>1001,'content'=>1002,'images'=>1002,'signin_time'=>1002,'signin_hotel_id'=>1002,
                    'signout_time'=>1002,'signout_hotel_id'=>1002,'review_uid'=>1002,'cc_uids'=>1002,'salerecord_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'recordinfo':
                $this->valid_fields = array('openid'=>1001,'salerecord_id'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function visitconfig(){
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_category = new \Common\Model\Smallapp\CategoryModel();
        $res_category = $m_category->getDataList('id,name,type',array('type'=>array('in','9,10')),'id desc');
        $purpose = $types = array(array('id'=>0,'name'=>'请选择'));
        foreach ($res_category as $v){
            $info = array('id'=>$v['id'],'name'=>$v['name']);
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
        $type = intval($this->params['type']);//类型1点评人 2抄送人
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_salerecord = new \Common\Model\Crm\SalerecordModel();
        $res_salerecord = $m_salerecord->getALLDataList('*',array('ops_staff_id'=>$res_staff['id']),'id desc','0,1','');
        $lately = array();
        if(!empty($res_salerecord)){
            $salerecord_id = $res_salerecord[0]['id'];
            $m_salerecord_remind = new \Common\Model\Crm\SalerecordRemindModel();
            $fields = 'a.remind_user_id as staff_id,a.type,sysuser.remark as staff_name,user.avatarUrl,user.nickName';
            $res_remind = $m_salerecord_remind->getList($fields,array('a.salerecord_id'=>$salerecord_id,'a.type'=>array('in','1,2')),'a.id desc');
            $all_lately = array();
            foreach ($res_remind as $v){
                $all_lately[$v['type']][]=$v;
            }
            $lately = $all_lately[$type];
        }
        $fields = 'a.id as staff_id,su.remark as staff_name,user.avatarUrl,user.nickName';
        $res_user = $m_opstaff->getStaffUserinfo($fields,array('a.status'=>1,'a.sysuser_id'=>array('gt',0)));
        $all_user = array();
        foreach ($res_user as $v){
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
        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_crmuser = new \Common\Model\Crm\ContactModel;
        $where = array('status'=>1);
        if(!empty($keywords)){
            $where['name'] = array('like',"%$keywords%");
        }
        $res_user = $m_crmuser->getDataList('*',$where,'id desc');
        $oss_host = get_oss_host();
        $all_user = array();
        $m_hotel = new \Common\Model\HotelModel();
        foreach ($res_user as $v){
            $img_avatar_url = '';
            if(!empty($res_info['avatar_url'])){
                $img_avatar_url = $oss_host.$res_info['avatar_url'];
            }
            $hotel_name = '';
            if($v['hotel_id']){
                $res_hotel = $m_hotel->getOneById('name',$v['hotel_id']);
                $hotel_name = $res_hotel['name'];
            }
            $info = array('contact_id'=>$v['id'],'name'=>$v['name'],'hotel_id'=>$v['hotel_id'],'hotel_name'=>$hotel_name,
                'img_avatar_url'=>$img_avatar_url,'job'=>$v['job'],'department'=>$v['department']);
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
            foreach ($this->valid_fields as $k=>$v){
                if($k!='images'){
                    if(empty($this->params["$k"])){
                        $this->to_back(1001);
                    }
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
            if(!empty($review_uid)){
                $res_remind = $m_saleremind->getInfo(array('salerecord_id'=>$salerecord_id,'type'=>1));
                if(empty($res_remind)){
                    $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>1,'remind_user_id'=>$review_uid);
                }
            }
            if(!empty($cc_uids)){
                $res_remind = $m_saleremind->getInfo(array('salerecord_id'=>$salerecord_id,'type'=>2));
                if(empty($res_remind)){
                    $arr_cc_uids = explode(',',$cc_uids);
                    foreach ($arr_cc_uids as $v){
                        $remind_user_id = intval($v);
                        if($remind_user_id>0){
                            $add_remind[] = array('salerecord_id'=>$salerecord_id,'type'=>2,'remind_user_id'=>$remind_user_id);
                        }
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
                    $images_url[]=$oss_host.$v;
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
        unset($res_info['status'],$res_info['update_time'],$res_info['add_time']);
        $hotel_name = '';
        if($res_info['signin_hotel_id']){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('name',$res_info['signin_hotel_id']);
            $hotel_name = $res_hotel['name'];
        }
        $res_info['hotel_name'] = $hotel_name;
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
        $this->to_back($res_info);
    }
}
