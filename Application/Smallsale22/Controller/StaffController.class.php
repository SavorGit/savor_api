<?php
namespace Smallsale22\Controller;
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
            case 'hotelstafflist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'page'=>1001,'pagesize'=>1002);
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
            case 'getAssigninfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'assignMoney':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'staff_id'=>1001,'money'=>1002,'integral'=>1002);
                break;
            case 'addRestaurantStaff':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'staff_openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function addRestaurantStaff(){
        $openid = $this->params['openid'];
        $staff_openid = $this->params['staff_openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,a.merchant_id,merchant.type',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $where = array('a.openid'=>$staff_openid,'a.merchant_id'=>$res_staff[0]['merchant_id'],'a.status'=>1);
        $res_room_staff = $m_staff->getMerchantStaff('a.id,a.openid',$where);
        if(empty($res_room_staff)){
            $this->to_back(93030);
        }
        $data = array('level'=>2,'parent_id'=>$res_staff[0]['id'],'update_time'=>date('Y-m-d H:i:s'));
        $m_staff->updateData(array('id'=>$res_room_staff[0]['id']),$data);
        $this->to_back(array('staff_id'=>$res_room_staff[0]['id']));
    }

    public function stafflist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,a.level,a.permission,a.merchant_id,merchant.type,merchant.hotel_id',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        $all_nums = 10000;
        if($hotel_id){
            if($hotel_id!=$res_staff[0]['hotel_id']){
                $this->to_back(93001);
            }
            $staff_where = array('merchant_id'=>$res_staff[0]['merchant_id'],'status'=>1);
            if($res_staff[0]['level']==0 || $res_staff[0]['level']==1){
                $staff_where['level'] = array('in',array(2,3));
            }elseif($res_staff[0]['level']==2){
                $staff_where['level'] = array('in',array(2,3));
            }elseif($res_staff[0]['level']==3){
                $staff_where = array('id'=>$res_staff[0]['id']);
            }
            $res_staffs = $m_staff->getDataList('id,openid,parent_id,level',$staff_where,'level asc');
            if($res_staff[0]['level']==1 || $res_staff[0]['level']==2){
                $is_self = 0;
                foreach ($res_staffs as $v){
                    if($v['id']==$res_staff[0]['id']){
                        $is_self = 1;
                        break;
                    }
                }
                if($is_self==0){
                    $self_staffs = $m_staff->getDataList('id,openid,parent_id,level',array('id'=>$res_staff[0]['id']),'id desc');
                    $res_staffs = array_merge($self_staffs,$res_staffs);
                }
            }
        }else{
            if($res_staff[0]['level']==0 || $res_staff[0]['level']==1){
                $staff_where = array('merchant_id'=>$res_staff[0]['merchant_id'],'status'=>1);
                $staff_where['level'] = array('in',array(2,3));
                $res_staffs = $m_staff->getDataList('id,openid,parent_id,level',$staff_where,'id desc');
            }elseif($res_staff[0]['level']==2){
                $staff_where = array('merchant_id'=>$res_staff[0]['merchant_id'],'status'=>1);
                $staff_where['parent_id'] = $res_staff[0]['id'];
                $res_staffs = $m_staff->getDataList('id,openid,parent_id,level',$staff_where,'id desc');
            }else{
                $res_staffs = $m_staff->getStaffsByOpenid($openid,0,$all_nums);
            }
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
                $res_user['level'] = intval($v['level']);
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

    public function hotelstafflist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $page = intval($this->params['page']);
        $pagesize = intval($this->params['pagesize']);
        if(empty($pagesize)){
            $pagesize = 15;
        }

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,a.level,a.permission,a.merchant_id,merchant.type,merchant.hotel_id',$where);
        if(empty($res_staff) || $res_staff[0]['type']!=3){
            $this->to_back(93001);
        }
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
        $merchant_id = intval($res_merchant['id']);

        $start = ($page-1)*$pagesize;
        $staff_where = array('merchant_id'=>$merchant_id,'status'=>1);
        $res_staffs = $m_staff->getDataList('id,openid,parent_id,level',$staff_where,'level asc',$start,$pagesize);
        $datalist = array();
        if(!empty($res_staffs['total'])){
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_staffs['list'] as $v){
                $where = array('openid'=>$v['openid']);
                $fields = 'openid,avatarUrl,nickName,mobile';
                $res_user = $m_user->getOne($fields,$where);
                $res_user['staff_id'] = $v['id'];
                $res_user['level'] = intval($v['level']);
                $datalist[] = $res_user;
            }
        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id,a.room_ids';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        if(empty($res_staff[0]['hotel_id']) || empty($res_staff[0]['room_ids'])){
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
        $res_user = $m_user->getOne('id as user_id,openid,avatarUrl,nickName',$where,'id desc');
        $data = array('staff_id'=>$res_staff[0]['id'],'avatarUrl'=>$res_user['avatarUrl'],
            'nickName'=>$res_user['nickName'],'openid'=>$res_user['openid'],'score'=>$score);
        $this->to_back($data);
    }

    public function getStaffRoomList(){
        $hotel_id = intval($this->params['hotel_id']);

        $fields = 'a.id as box_id,c.id as room_id,c.name as room_name,a.name as box_name,a.mac as box_mac';
        $m_box = new \Common\Model\BoxModel();
        $res_box = $m_box->getBoxListByHotelRelation($fields,$hotel_id);
        $room_list = array();
        if(!empty($res_box)) {
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_staff = new \Common\Model\Integral\StaffModel();
            foreach ($res_box as $k=>$v) {
                $info = array('room_id'=>$v['room_id'],'room_name'=>$v['box_name'],
                    'staff_id'=>0,'staff_name'=>'');
                $where = array('a.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
                $where['a.room_ids'] = array('like',"%,{$v['room_id']},%");
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
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $hotel_id = $res_staff[0]['hotel_id'];
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(15);
        $cache_key = 'savor_room_'.$room_id;
        $redis_room_info = $redis->get($cache_key);
        $room_info = json_decode($redis_room_info, true);
        if(!empty($room_info) && $room_info['hotel_id']!=$hotel_id){
            $cache_key = C('SMALLAPP_HOTEL_RELATION');
            $redis->select(2);
            $res_cache = $redis->get($cache_key.$hotel_id);
            if(empty($res_cache) || $res_cache!=$room_info['hotel_id']){
                $this->to_back(93029);
            }
        }
        if($staff_id){
            $where = array('a.id'=>$staff_id);
            $res_staff = $m_staff->getMerchantStaff('a.id,a.openid,merchant.hotel_id,a.room_ids',$where);
            if(empty($res_staff) || $res_staff[0]['hotel_id']!=$hotel_id){
                $this->to_back(93030);
            }
            $where = array('hotel_id'=>$hotel_id);
            $where['room_ids'] = array('like',"%,$room_id,%");
            $res = $m_staff->getInfo($where);
            if(!empty($res)){
                if($res['id']!=$staff_id){
                    $room_ids = trim($res['room_ids'],',');
                    $room_ids = explode(',',$room_ids);
                    $id_key = array_search($room_id,$room_ids);
                    unset($room_ids[$id_key]);
                    if(!empty($room_ids)){
                        $room_ids = join(',',$room_ids);
                        $room_ids = ",$room_ids,";
                        $data = array('hotel_id'=>$hotel_id,'room_ids'=>$room_ids);
                    }else{
                        $data = array('hotel_id'=>0,'room_ids'=>'');
                    }
                    $m_staff->updateData(array('id'=>$res['id']),$data);

                    if(!empty($res_staff[0]['room_ids'])){
                        $room_ids = trim($res_staff[0]['room_ids'],',');
                        $room_ids = explode(',',$room_ids);
                        $id_key = array_search($room_id,$room_ids);
                        if($id_key===false){
                            $room_ids = $res_staff[0]['room_ids']."$room_id,";
                        }else{
                            $room_ids = $res_staff[0]['room_ids'];
                        }
                    }else{
                        $room_ids = ",$room_id,";
                    }
                    $data = array('hotel_id'=>$hotel_id,'room_ids'=>$room_ids);
                    $m_staff->updateData(array('id'=>$staff_id),$data);
                }

            }else{
                if(!empty($res_staff[0]['room_ids'])){
                    $room_ids = $res_staff[0]['room_ids']."$room_id,";
                }else{
                    $room_ids = ",$room_id,";
                }
                $data = array('hotel_id'=>$hotel_id,'room_ids'=>$room_ids);
                $m_staff->updateData(array('id'=>$staff_id),$data);
            }
        }else{
            $where = array('hotel_id'=>$hotel_id);
            $where['room_ids'] = array('like',"%,$room_id,%");
            $res = $m_staff->getInfo($where);
            if(!empty($res)){
                $room_ids = trim($res['room_ids'],',');
                $room_ids = explode(',',$room_ids);
                $id_key = array_search($room_id,$room_ids);
                unset($room_ids[$id_key]);
                if(!empty($room_ids)){
                    $room_ids = join(',',$room_ids);
                    $room_ids = ",$room_ids,";
                }else{
                    $hotel_id = 0;
                    $room_ids = '';
                }
                $data = array('hotel_id'=>$hotel_id,'room_ids'=>$room_ids);
                $m_staff->updateData(array('id'=>$res['id']),$data);
            }
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

    public function getAssigninfo(){
        $openid = $this->params['openid'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.openid,a.level,merchant.type,merchant.hotel_id,merchant.money,merchant.integral';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        if($res_staff[0]['level']!=1){
            $this->to_back(93031);
        }
        $m_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $fields = 'sum(integral) as total_integral';
        $freezewhere = array('openid'=>$res_staff[0]['hotel_id'],'type'=>array('in',array(17,18,19)),'status'=>2);
        $res_integral = $m_integralrecord->getALLDataList($fields,$freezewhere,'','','');
        $freeze_integral = 0;
        if(!empty($res_integral)){
            $freeze_integral = intval($res_integral[0]['total_integral']);
        }

        $res = array('money'=>intval($res_staff[0]['money']),'integral'=>intval($res_staff[0]['integral']),'freeze_integral'=>$freeze_integral);
        $this->to_back($res);
    }

    public function assignMoney(){
        $openid = $this->params['openid'];
        $staff_id = $this->params['staff_id'];
        $money = intval($this->params['money']);
        $integral = intval($this->params['integral']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.openid,a.level,a.merchant_id,merchant.type,merchant.hotel_id,merchant.money,merchant.integral';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        if($res_staff[0]['level']!=1){
            $this->to_back(93031);
        }
        $res_data = $m_staff->getInfo(array('id'=>$staff_id));
        if(empty($res_data) || $res_data['status']!=1 || $res_data['merchant_id']!=$res_staff[0]['merchant_id']){
            $this->to_back(93030);
        }
        $total_money = $res_staff[0]['money'];
        $total_integral = $res_staff[0]['integral'];
        if(!$money && !$integral){
            $this->to_back(93056);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $field = 'hotel.id as hotel_id,hotel.name as hotel_name,hotel.hotel_box_type,area.id as area_id,area.region_name as area_name';
        $res_hotel = $m_hotel->getHotelById($field,array('hotel.id'=>$res_staff[0]['hotel_id']));

        $add_data = array('openid'=>$res_data['openid'],'area_id'=>$res_hotel['area_id'],'area_name'=>$res_hotel['area_name'],
            'hotel_id'=>$res_hotel['hotel_id'],'hotel_name'=>$res_hotel['hotel_name'],'hotel_box_type'=>$res_hotel['hotel_box_type'],
            'type'=>9,'integral_time'=>date('Y-m-d H:i:s')
            );
        $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        if($money){
            if($money>$total_money){
                $this->to_back(93057);
            }
            $m_baseinc = new \Payment\Model\BaseIncModel();
            $payconfig = $m_baseinc->getPayConfig(5);
            $add_data['money'] = $money;
            $order_id = $m_userintegral_record->add($add_data);

            $trade_info = array('trade_no'=>$order_id,'money'=>$money,'open_id'=>$res_data['openid']);
            $m_wxpay = new \Payment\Model\WxpayModel();
            $res = $m_wxpay->mmpaymkttransfers($trade_info,$payconfig);
            if($res['code']==10000){
                $now_money = $total_money - $money;
                $m_merchant->updateData(array('id'=>$res_staff[0]['merchant_id']),array('money'=>$now_money));
                $total_money = $now_money;
            }else{
                $m_userintegral_record->updateData(array('id'=>$order_id),array('status'=>2));
                $this->to_back(93058);
            }
        }
        if($integral){
            if($integral>$total_integral){
                $this->to_back(93059);
            }
            $add_data['money'] = 0;
            $add_data['integral'] = $integral;
            $m_userintegral_record->add($add_data);
            $now_integral = $total_integral - $integral;
            $m_merchant->updateData(array('id'=>$res_staff[0]['merchant_id']),array('integral'=>$now_integral));
            $total_integral = $now_integral;

            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
            $res_integral = $m_userintegral->getInfo(array('openid'=>$res_data['openid']));
            if(!empty($res_integral)){
                $userintegral = $res_integral['integral']+$integral;
                $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
            }else{
                $uidata = array('openid'=>$res_data['openid'],'integral'=>$integral);
                $m_userintegral->add($uidata);
            }
        }
        $res = array('money'=>intval($total_money),'integral'=>intval($total_integral));
        $this->to_back($res);
    }



}