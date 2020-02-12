<?php
namespace Smallsale16\Controller;
use \Common\Controller\CommonController;

class StaffController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'stafflist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'getStaffRoomList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
            case 'setRoomstaff':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'room_id'=>1001,'staff_id'=>1001);
                break;
            case 'setPermission':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'staff_id'=>1001,'is_scangoods'=>1001);
                break;
        }
        parent::_init_();
    }

    public function stafflist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,a.level,a.permission,merchant.type',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        $all_nums = 10000;
        if($hotel_id){
            $staff_where = array('status'=>1,'level'=>3);
            $res_staffs = $m_staff->getDataList('id,openid,parent_id',$staff_where,'id desc');
        }else{
            $res_staffs = $m_staff->getStaffsByOpenid($openid,0,$all_nums);
        }

        $datalist = array();
        $oss_host = C('OSS_HOST');
        if(!empty($res_staffs)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            foreach ($res_staffs as $v){
                $where = array('openid'=>$v['openid']);
                $fields = 'openid,avatarUrl,nickName';
                $res_user = $m_user->getOne($fields, $where);
                $res_user['invite_id'] = $v['parent_id'];
                $condition = array('staff_id'=>$v['id'],'status'=>1);
                $res_score = $m_comment->getCommentInfo('avg(score) as score',$condition);
                if(!empty($res_score)){
                    $res_user['score'] = sprintf("%01.1f",$res_score[0]['score']);
                }else{
                    $res_user['score'] = 0;
                }
                if(strpos($res_user['avatarUrl'],$oss_host)){
                    $res_user['avatarUrl'] = $res_user['avatarUrl']."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                }
                $res_user['staff_id'] = $v['id'];
                $datalist[] = $res_user;
            }
        }
        $user = array();
        if($res_staff[0]['level']==2){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $where = array('openid'=>$res_staff[0]['openid']);
            $user = $m_user->getOne('id as user_id,avatarUrl,nickName',$where,'id desc');
            $user['staff_id'] = $res_staff[0]['id'];
            $is_scangoods = 0;
            if(!empty($res_staff[0]['permission'])){
                $permission = json_decode($res_staff[0]['permission'],true);
                if(isset($permission['is_scangoods'])){
                    $is_scangoods = intval($permission['is_scangoods']);
                }
            }
            $user['is_scangoods'] = $is_scangoods;
            if(strpos($user['avatarUrl'],$oss_host)){
                $user['avatarUrl'] = $user['avatarUrl']."?x-oss-process=image/resize,m_mfit,h_300,w_300";
            }
        }
        $data = array('datalist'=>$datalist,'user'=>$user);
        $this->to_back($data);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id,a.room_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        if(empty($res_staff[0]['hotel_id']) || empty($res_staff[0]['room_id'])){
            $score = 0;
        }else{
            $condition = array('staff_id'=>$res_staff[0]['id'],'status'=>1);
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            $res_score = $m_comment->getCommentInfo('avg(score) as score',$condition);
            if(!empty($res_score) && $res_score[0]['score']>=1){
                $score = sprintf("%01.1f",$res_score[0]['score']);
            }else{
                $score = 0;
            }
        }

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$res_staff[0]['openid']);
        $res_user = $m_user->getOne('id as user_id,avatarUrl,nickName',$where,'id desc');
        $data = array('staff_id'=>$res_staff[0]['id'],'avatarUrl'=>$res_user['avatarUrl'],
            'nickName'=>$res_user['nickName'],'score'=>$score);
        $this->to_back($data);
    }

    public function getStaffRoomList(){
        $hotel_id = intval($this->params['hotel_id']);

        $fields = 'a.id as box_id,c.id as room_id,c.name as room_name,a.name as box_name,a.mac as box_mac';
        $m_box = new \Common\Model\BoxModel();
        $res_box = $m_box->getBoxListByHotelid($fields,$hotel_id);
        $room_list = array();
        if(!empty($res_box)) {
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_staff = new \Common\Model\Integral\StaffModel();
            foreach ($res_box as $k=>$v) {
                $info = array('room_id'=>$v['room_id'],'room_name'=>$v['room_name'],
                    'staff_id'=>0,'staff_name'=>'');
                $where = array('a.hotel_id'=>$hotel_id,'a.room_id'=>$v['room_id'],
                    'a.status'=>1,'merchant.status'=>1);
                $res_staff = $m_staff->getMerchantStaff('a.id,a.openid',$where);
                if(!empty($res_staff)){
                    $where = array('openid'=>$res_staff[0]['openid']);
                    $fields = 'openid,avatarUrl,nickName';
                    $res_user = $m_user->getOne($fields,$where);
                    $info['staff_id'] = intval($res_staff[0]['id']);
                    $info['staff_name'] = $res_user['nickName'];
                }
                $room_list[] = $info;
            }
        }
        $this->to_back($room_list);
    }

    public function setRoomstaff(){
        $openid = $this->params['openid'];
        $room_id = $this->params['room_id'];
        $staff_id = $this->params['staff_id'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid,merchant.type,merchant.hotel_id',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        $hotel_id = $res_staff[0]['hotel_id'];
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(15);
        $cache_key = 'savor_room_'.$room_id;
        $redis_room_info = $redis->get($cache_key);
        $room_info = json_decode($redis_room_info, true);
        if($room_info['hotel_id']!=$hotel_id){
            $this->to_back(93029);
        }
        if($staff_id){
            $where = array('a.id'=>$staff_id);
            $res = $m_staff->getMerchantStaff('a.openid,merchant.hotel_id',$where);
            if(empty($res) || $res[0]['hotel_id']!=$hotel_id){
                $this->to_back(93030);
            }
            $data = array('hotel_id'=>$hotel_id,'room_id'=>$room_id);
            $m_staff->updateData(array('id'=>$staff_id),$data);
        }else{
            $where = array('hotel_id'=>$hotel_id,'room_id'=>$room_id);
            $data = array('hotel_id'=>0,'room_id'=>0);
            $m_staff->updateData($where,$data);
        }
        $this->to_back(array());
    }

    public function setPermission(){
        $openid = $this->params['openid'];
        $is_scangoods = $this->params['is_scangoods'];
        $staff_id = $this->params['staff_id'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid,a.level,merchant.type,merchant.hotel_id',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        if($res_staff[0]['level']!=1){
            $this->to_back(93031);
        }
        $res_data = $m_staff->getInfo(array('id'=>$staff_id));
        if(empty($res_data) || $res_data['status']!=1){
            $this->to_back(93032);
        }
        $permission = array();
        if(!empty($res_data['permission'])){
            $permission = json_decode($res_data['permission'],true);
        }
        if(isset($permission['is_scangoods']) && $permission['is_scangoods']==$is_scangoods){
            $this->to_back(93033);
        }
        $permission['is_scangoods'] = $is_scangoods;
        $m_staff->updateData(array('id'=>$staff_id),array('permission'=>json_encode($permission)));
        $this->to_back(array());
    }

    public function getPermission(){
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid,a.level,merchant.type,merchant.hotel_id',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        if($res_staff[0]['level']!=2){
            $this->to_back(93031);
        }
        $permission = array();
        if(!empty($res_data['permission'])){
            $permission = json_decode($res_data['permission'],true);
        }
        $data = array('permission'=>$permission);
        $this->to_back($data);
    }



}