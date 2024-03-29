<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;
class InvitationController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'confirminfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'box_mac'=>1002,'name'=>1001,'book_time'=>1001,
                    'people_num'=>1002,'mobile'=>1002,'room_id'=>1002,'contact_name'=>1002,'contact_mobile'=>1002,'theme_id'=>1002);
                break;
            case 'themes':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function confirminfo(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $box_mac = $this->params['box_mac'];
        $book_time = $this->params['book_time'];
        $name = trim($this->params['name']);
        $people_num = $this->params['people_num'];
        $mobile = $this->params['mobile'];
        $room_id = intval($this->params['room_id']);
        $contact_name = trim($this->params['contact_name']);
        $contact_mobile = $this->params['contact_mobile'];
        $theme_id = intval($this->params['theme_id']);

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_hotelinvitation = new \Common\Model\Smallapp\HotelInvitationConfigModel();
        $res_invitation = $m_hotelinvitation->getInfo(array('hotel_id'=>$hotel_id));
        if(empty($res_invitation)){
            $this->to_back(93077);
        }

        if(!empty($box_mac)){
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
                $box_id = $rets[0]['box_id'];
                $box_name = $rets[0]['box_name'];
                $room_name = $rets[0]['room_name'];
            }
        }else{
            $m_room = new \Common\Model\RoomModel();
            $fields = 'room.id as room_id,room.name as room_name,hotel.id as hotel_id,hotel.name as hotel_name';
            $where  = array('room.id'=>$room_id,'hotel.state'=>1,'hotel.flag'=>0,'room.state'=>1,'room.flag'=>0);
            $rets = $m_room->alias('room')
                ->join('savor_hotel hotel on hotel.id=room.hotel_id','left')
                ->field($fields)
                ->where($where)
                ->find();
            $hotel_id = $rets['hotel_id'];
            $hotel_name = $rets['hotel_name'];
            $room_id = $rets['room_id'];
            $box_id = 0;
            $box_name = '';
            $room_name = $rets['room_name'];
        }

        $book_time = date('Y-m-d H:i:s',strtotime($book_time));
        $adata = array('openid'=>$openid,'name'=>$name,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,
            'room_id'=>$room_id,'room_name'=>$room_name,'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,
            'book_time'=>$book_time,'theme_id'=>$theme_id
        );
        if(!empty($people_num)){
            $adata['people_num'] = intval($people_num);
        }
        if(!empty($mobile)){
            $adata['mobile'] = $mobile;
        }
        if(!empty($contact_name)){
            $adata['contact_name'] = $contact_name;
        }
        if(!empty($contact_mobile)){
            $adata['contact_mobile'] = $contact_mobile;
        }
        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $invitation_id = $m_invitation->add($adata);
        if($invitation_id && !empty($mobile)){
            //发送短信
            $ucconfig = C('ALIYUN_SMS_CONFIG');
            $alisms = new \Common\Lib\AliyunSms();
            $book_time = date('Y-m-d H点',strtotime($book_time));
            $params = array('book_time'=>$book_time,'hotel_name'=>$hotel_name,'room_name'=>$room_name);
            if(!empty($contact_mobile)){
                $params['tel'] = $contact_mobile;
                $template_code = $ucconfig['send_invitation_to_user_has_mobile'];
            }else{
                $template_code = $ucconfig['send_invitation_to_user'];
            }
            $is_send = check_sendsms_content($mobile,$params,$template_code);
            if($is_send==0){
                $res_data = $alisms::sendSms($mobile,$params,$template_code);
                $data = array('type'=>15,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                    'url'=>join(',',$params),'tel'=>$mobile,'resp_code'=>$res_data->Code,'msg_type'=>3
                );
                $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
                $m_account_sms_log->addData($data);
            }
        }

        $this->to_back(array('invitation_id'=>$invitation_id));
    }

    public function themes(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $all_themes = C('INVITATION_THEME');
        $all_data = array_values($all_themes);
        foreach ($all_data as $k=>$v){
            $all_data[$k]['bg_img'] = $v['bg_img'].'?x-oss-process=image/resize,p_50/quality,q_60';
        }
        $this->to_back(array('datalist'=>$all_data));
    }

}