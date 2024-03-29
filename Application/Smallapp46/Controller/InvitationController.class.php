<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class InvitationController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('invitation_id'=>1001,'openid'=>1002);
                break;
            case 'accept':
                $this->is_verify = 1;
                $this->valid_fields = array('invitation_id'=>1001,'openid'=>1001);
                break;
            case 'userlist':
                $this->is_verify = 1;
                $this->valid_fields = array('invitation_id'=>1001,'openid'=>1001);
                break;
            case 'receiveintegral':
                $this->is_verify = 1;
                $this->valid_fields = array('invitation_id'=>1001,'openid'=>1002);
                break;
            case 'send':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'invitation_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function accept(){
        $invitation_id = intval($this->params['invitation_id']);
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $now_time = date('Y-m-d H:00:00');
        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $res_info = $m_invitation->getInfo(array('id'=>$invitation_id));
        if(empty($res_info)){
            $this->to_back(93200);
        }
        if($now_time>$res_info['book_time']){
            $this->to_back(93201);
        }
        $m_invitation_user = new \Common\Model\Smallapp\InvitationUserModel();
        $res_data = $m_invitation_user->getInfo(array('invitation_id'=>$invitation_id,'openid'=>$openid));
        if(empty($res_data)){
            $data = array('invitation_id'=>$invitation_id,'openid'=>$openid,'type'=>2);
            $m_invitation_user->add($data);
            /*
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $m_userintegral->finishInvitationTask($res_info,16);
            */
        }
        $this->to_back(array('invitation_id'=>$invitation_id));
    }

    public function userlist(){
        $invitation_id = intval($this->params['invitation_id']);
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_invitation_user = new \Common\Model\Smallapp\InvitationUserModel();
        $where = array('invitation_id'=>$invitation_id,'type'=>2);
        $res_user = $m_invitation_user->getDataList('openid',$where,'id desc');
        $users = array();
        $num = 0;
        if(!empty($res_user)){
            $u_openids = array();
            foreach ($res_user as $v){
                $u_openids[]=$v['openid'];
            }
            $where = array('openid'=>array('in',$u_openids));
            $users = $m_user->getWhere('avatarUrl,nickName',$where,'','','');
            $num = count($users);
        }
        $m_invitation_user = new \Common\Model\Smallapp\InvitationUserModel();
        $res_data = $m_invitation_user->getInfo(array('invitation_id'=>$invitation_id,'openid'=>$openid));
        $type = 1;
        if(!empty($res_data)){
            $type = $res_data['type'];
        }
        $this->to_back(array('type'=>$type,'num'=>$num,'users'=>$users));
    }

    public function receiveintegral(){
        $invitation_id = intval($this->params['invitation_id']);
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid,unionId,mobile', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        if(!empty($user_info['unionId'])){
            $where = array('unionId'=>$user_info['unionId'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                $this->to_back(93219);
            }
        }elseif(!empty($user_info['mobile'])){
            $where = array('mobile'=>$user_info['mobile'],'small_app_id'=>5);
            $res_sale_user = $m_user->getOne('id,openid,unionId',$where,'');
            if(!empty($res_sale_user)){
                $this->to_back(93219);
            }
        }

        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $res_info = $m_invitation->getInfo(array('id'=>$invitation_id));
        if(empty($res_info)){
            $this->to_back(93200);
        }
        $m_invitation_user = new \Common\Model\Smallapp\InvitationUserModel();
        $res_data = $m_invitation_user->getInfo(array('invitation_id'=>$invitation_id,'openid'=>$openid));
        $finish_task_info = array();
        if(empty($res_data)){
            $data = array('invitation_id'=>$invitation_id,'openid'=>$openid,'type'=>1);
            $m_invitation_user->add($data);
        }
        $m_userintegral = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $where = array('openid'=>$res_info['openid'],'jdorder_id'=>$res_info['id'],'type'=>15);
        $res_integral = $m_userintegral->getInfo($where);
        if(empty($res_integral)){
            $res_info['receive_openid'] = $openid;
            $finish_task_info = $m_userintegral->finishInvitationTask($res_info,15);
        }

        $msg = json_encode($finish_task_info);
        $log_content = date("Y-m-d H:i:s").'[invitation_id]'.$invitation_id.'[openid]'.$openid.'[msg]'.$msg."\n";
        $log_file_name = APP_PATH.'Runtime/Logs/'.'invitation_'.date("Ymd").".log";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

        $this->to_back(array('invitation_id'=>$invitation_id,'finish_task_info'=>$finish_task_info));
    }

    public function detail(){
        $invitation_id = intval($this->params['invitation_id']);
        $openid = $this->params['openid'];

        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $res_info = $m_invitation->getInfo(array('id'=>$invitation_id));
        $res_data = array('invitation_id'=>$invitation_id);
        if(!empty($res_info)){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getInfoById($res_info['hotel_id'],'name,tel,addr,gps');
            $gps_arr = explode(',',$res_hotel['gps']);
            $latitude = $gps_arr[1];
            $longitude = $gps_arr[0];
            $tx_lnglat = getgeoByTc($latitude, $longitude,2);
            if($tx_lnglat[0]['x'] && !empty($tx_lnglat[0]['y'])) {
                $latitude = $tx_lnglat[0]['y'];
                $longitude = $tx_lnglat[0]['x'];
            }

            $title = $res_info['name'].'，已成功为您预定包间。';
            $share_title = $res_hotel['name'].'期待您的光临';
            $oss_host = get_oss_host();
            $invitation_hotels = C('INVITATION_HOTEL');
            $themes = C('INVITATION_THEME');
            $mobile = '';
            $m_user = new \Common\Model\Smallapp\UserModel();
            $res_user = $m_user->getOne('mobile',array('openid'=>$res_info['openid']),'id desc');
            if(!empty($res_info['contact_mobile'])){
                $mobile = $res_info['contact_mobile'];
            }elseif(!empty($res_user['mobile'])){
                $mobile = $res_user['mobile'];
            }elseif(!empty($res_hotel['tel'])){
                $mobile = $res_hotel['tel'];
            }

            $res_data['name'] = $res_info['name'];
            $res_data['mobile'] = $mobile;
            $res_data['title'] = $title;
            $res_data['share_title'] = $share_title;
            $res_data['hotel_id'] = $res_info['hotel_id'];
            $res_data['hotel_name'] = $res_hotel['name'];
            $res_data['addr'] = $res_hotel['addr'];
            $res_data['room_id'] = $res_info['room_id'];
            $res_data['box_mac'] = $res_info['box_mac'];
            $res_data['latitude'] = floatval($latitude);
            $res_data['longitude'] = floatval($longitude);
            $res_data['room_name'] = $res_info['room_name'];
            $res_data['book_time'] = $res_info['book_time'];
            $res_data['people_num'] = $res_info['people_num'];
            $res_data['contact_name'] = $res_info['contact_name'];
            $res_data['desc'] = $res_info['desc'];
            $res_data['is_sellwine'] = $res_info['is_sellwine'];
            $res_data['share_img_url'] = $oss_host.$invitation_hotels['share_img'];
            $res_data['calendar'] = array(
                'title'=>$res_info['name'].'的饭局',
                'time'=>strtotime($res_info['book_time']),
                'location'=>$res_hotel['addr'],
                'desc'=>$res_info['hotel_name'].'酒楼'.$res_info['room_name'].'包间',
            );

            $m_invitation_user = new \Common\Model\Smallapp\InvitationUserModel();
            $res_invitationdata = $m_invitation_user->getInfo(array('invitation_id'=>$invitation_id,'openid'=>$openid));
            $is_accept = 1;
            $now_time = date('Y-m-d H:00:00');
            if($now_time>$res_info['book_time'] || !empty($res_invitationdata)){
                $is_accept = 0;
            }
            $res_data['is_accept'] = $is_accept;
            $m_hotelinvitation = new \Common\Model\Smallapp\HotelInvitationConfigModel();
            $res_invitation = $m_hotelinvitation->getInfo(array('hotel_id'=>$res_info['hotel_id']));

            if(isset($themes[$res_info['theme_id']])){
                $theme_info = $themes[$res_info['theme_id']];
                $res_data['backgroundImage'] = $oss_host.$theme_info['bg_img'];
                $res_data['themeColor'] = $theme_info['themeColor'];
                $res_data['themeColor2'] = $theme_info['themeColor2'];
                $res_data['themeContrastColor'] = $theme_info['themeContrastColor'];
                $res_data['backgroundColor'] = $theme_info['backgroundColor'];
                $res_data['buttonBackgroundColor'] = $theme_info['buttonBackgroundColor'];
                $res_data['painColor'] = $theme_info['painColor'];
                $res_data['weakColor'] = $theme_info['weakColor'];
                $res_data['is_open_sellplatform'] = intval($res_invitation['is_open_sellplatform']);
            }else{
                if(!empty($res_invitation)){
                    $res_data['backgroundImage'] = $oss_host.$res_invitation['bg_img'];
                    $res_data['themeColor'] = $res_invitation['theme_color'];
                    $res_data['themeContrastColor'] = $res_invitation['theme_contrast_color'];
                    $res_data['painColor'] = $res_invitation['pain_color'];
                    $res_data['weakColor'] = $res_invitation['weak_color'];
                    $res_data['is_open_sellplatform'] = intval($res_invitation['is_open_sellplatform']);
                }else{
                    $res_data['backgroundImage'] = $oss_host.$invitation_hotels['bg_img'];
                    $res_data['themeColor'] = $invitation_hotels['themeColor'];
                    $res_data['themeContrastColor'] = $invitation_hotels['themeContrastColor'];
                    $res_data['painColor'] = $invitation_hotels['painColor'];
                    $res_data['weakColor'] = $invitation_hotels['weakColor'];
                    $res_data['is_open_sellplatform'] = $invitation_hotels['is_open_sellplatform'];
                }
            }
            $book_date = date("Y/m/d",strtotime($res_info['book_time']));
            $weekarray = array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
            $book_day = $weekarray[date("w",strtotime($res_info['book_time']))];
            $book_hour = date("H:i",strtotime($res_info['book_time']));
            $res_data['book_date'] = $book_date;
            $res_data['book_day'] = $book_day;
            $res_data['book_hour'] = $book_hour;
        }
        $this->to_back($res_data);
    }

    public function send(){
        $invitation_id = intval($this->params['invitation_id']);
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,avatarUrl,nickName,mpopenid', $where, '');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $res_data = $m_invitation->getInfo(array('id'=>$invitation_id));
        if($res_data['send_time']=='0000-00-00 00:00:00'){
            $m_invitation->updateData(array('id'=>$invitation_id),array('send_time'=>date('Y-m-d H:i:s')));
        }
        $this->to_back(array());
    }

}