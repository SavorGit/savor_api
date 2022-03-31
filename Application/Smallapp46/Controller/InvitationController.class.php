<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class InvitationController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('invitation_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function detail(){
        $invitation_id = intval($this->params['invitation_id']);

        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $res_info = $m_invitation->getInfo(array('id'=>$invitation_id));
        $res_data = array('invitation_id'=>$invitation_id);
        if(!empty($res_info)){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getInfoById($res_info['hotel_id'],'name,tel,addr,gps');
            $gps_arr = explode(',',$res_hotel['gps']);
            $latitude = $gps_arr[1];
            $longitude = $gps_arr[0];
            $title = $res_info['name'].'，已成功为您预定包间。';
            $share_title = $res_hotel['name'].'期待您的光临';
            $oss_host = 'http://'.C('OSS_HOST').'/';
            $invitation_hotels = C('INVITATION_HOTEL');
            $mobile = '';
            $m_user = new \Common\Model\Smallapp\UserModel();
            $res_user = $m_user->getOne('mobile',array('openid'=>$res_info['openid']),'id desc');
            if(!empty($res_user['mobile'])){
                $mobile = $res_user['mobile'];
            }elseif(!empty($res_hotel['tel'])){
                $mobile = $res_hotel['tel'];
            }

            $res_data['name'] = $res_info['name'];
            $res_data['mobile'] = $mobile;
            $res_data['title'] = $title;
            $res_data['share_title'] = $share_title;
            $res_data['hotel_name'] = $res_hotel['name'];
            $res_data['addr'] = $res_hotel['addr'];
            $res_data['box_mac'] = $res_info['box_mac'];
            $res_data['latitude'] = floatval($latitude);
            $res_data['longitude'] = floatval($longitude);
            $res_data['room_name'] = $res_info['room_name'];
            $res_data['book_time'] = $res_info['book_time'];
            $res_data['book_time'] = $res_info['book_time'];
            $res_data['backgroundImage'] = $oss_host.$invitation_hotels[$res_info['hotel_id']]['bg_img'];
            $res_data['themeColor'] = $invitation_hotels[$res_info['hotel_id']]['themeColor'];
            $res_data['themeContrastColor'] = $invitation_hotels[$res_info['hotel_id']]['themeContrastColor'];
            $res_data['painColor'] = $invitation_hotels[$res_info['hotel_id']]['painColor'];
            $res_data['weakColor'] = $invitation_hotels[$res_info['hotel_id']]['weakColor'];
            $res_data['share_img_url'] = $oss_host.$invitation_hotels['share_img'];
            $res_data['is_open_sellplatform'] = $invitation_hotels[$res_info['hotel_id']]['is_open_sellplatform'];
        }
        $this->to_back($res_data);
    }



}