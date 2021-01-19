<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class BusinessdinnersController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'moduleList':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
            case 'cardDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'shareCardOnTv':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;

        }
        parent::_init_();

    }

    public function moduleList(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $oss_host = "https://".C('OSS_HOST').'/';
        $m_usercard = new \Common\Model\Smallapp\UsercardModel();
        $res_usercard = $m_usercard->getInfo(array('user_id'=>$user_info['id']));
        $card = new \stdClass();
        if(!empty($res_usercard)){
            $card = array('name'=>$res_usercard['name'],'job'=>$res_usercard['job'],'mobile'=>$res_usercard['mobile'],'company'=>$res_usercard['company'],
                'head_img'=>'','head_img_path'=>$res_usercard['head_img'],'qrcode_img'=>'','qrcode_img_path'=>$res_usercard['qrcode_img']);
            if(!empty($res_usercard['head_img'])){
                $card['head_img'] = $oss_host.$res_usercard['head_img'];
            }
            if(!empty($res_usercard['qrcode_img'])){
                $card['qrcode_img'] = $oss_host.$res_usercard['qrcode_img'];
            }
        }
        $welcome = new \stdClass();
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>3);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $res_welcome = $m_welcome->getInfo($condition);
        if(!empty($res_welcome)){
            $welcome = array('welcome_id'=>$res_welcome['id'],'content'=>$res_welcome['content']);
            $imgs = explode(',',$res_welcome['image']);
            $image_path = array();
            if(!empty($imgs)){
                foreach ($imgs as $v){
                    if(!empty($v)){
                        $image_path[] = $v;
                    }
                }
            }
            $welcome['images'] = $image_path;
        }
        $share_file = $forscreen_file = $videos = $images = array();

        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>1,'status'=>1);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $res_files = $m_userfile->getDataList('*',$condition,'id desc');
        if(!empty($res_files)){
            foreach ($res_files as $v){
                $file_info = pathinfo($v['file_path']);
                $info = array('file_id'=>$v['id'],'name'=>$file_info['basename'],'file_path'=>$v['file_path']);
                switch ($v['type']){
                    case 1:
                        $share_file[] = $info;
                        break;
                    case 2:
                        $file_conversion_status = $v['file_conversion_status'];
                        if(in_array($file_conversion_status,array(2,4))){
                            $file_conversion_status = 2;
                        }
                        if($file_conversion_status==2){
                            $forscreen_status = 2;
                        }else{
                            $forscreen_status = 1;
                        }
                        $info['forscreen_status'] = $forscreen_status;
                        $forscreen_file[]=$info;
                        break;
                    case 3:
                        $videos[]=$info;
                        break;
                    case 4:
                        $images[]=$info;
                        break;
                }
            }
        }
        $share_file_num = count($share_file);
        $forscreen_file_num = count($forscreen_file);
        $videos_num = count($videos);
        $images_num = count($images);
        $resp_data = array('card'=>$card,'welcome'=>$welcome,'share_file'=>$share_file,'share_file_num'=>$share_file_num,
            'forscreen_file'=>$forscreen_file,'forscreen_file_num'=>$forscreen_file_num,'videos'=>$videos,'videos_num'=>$videos_num,
            'images'=>$images,'images_num'=>$images_num,
        );
        $this->to_back($resp_data);
    }

    public function cardDetail(){
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $oss_host = "https://".C('OSS_HOST').'/';
        $m_usercard = new \Common\Model\Smallapp\UsercardModel();
        $res_usercard = $m_usercard->getInfo(array('user_id'=>$user_info['id']));
        $card = array();
        $is_card = 0;
        if(!empty($res_usercard)){
            $card = array('name'=>$res_usercard['name'],'job'=>$res_usercard['job'],'mobile'=>$res_usercard['mobile'],'company'=>$res_usercard['company'],
                'head_img'=>$res_usercard['head_img'],'head_img_path'=>'','qrcode_img'=>$res_usercard['qrcode_img'],'qrcode_img_path'=>'');
            if(!empty($res_usercard['head_img'])){
                $card['head_img_path'] = $oss_host.$res_usercard['head_img'];
            }
            if(!empty($res_usercard['qrcode_img'])){
                $card['qrcode_img_path'] = $oss_host.$res_usercard['qrcode_img'];
            }
            $is_card = 1;
        }
        $card['is_card'] = $is_card;
        $this->to_back($card);
    }


    public function shareCardOnTv(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $share_countdown = 60;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_box = new \Common\Model\BoxModel();
        $condition = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
        if(empty($res_box)){
            $this->to_back(70001);
        }

        $oss_host = "https://".C('OSS_HOST').'/';
        $m_usercard = new \Common\Model\Smallapp\UsercardModel();
        $res_usercard = $m_usercard->getInfo(array('user_id'=>$user_info['id']));
        $res_push = array();
        if(!empty($res_usercard)){
            $message = array('action'=>160,'nickName'=>$res_usercard['name'],'mobile'=>$res_usercard['mobile'],
                'job'=>$res_usercard['job'],'company'=>$res_usercard['company'],'codeUrl'=>'',
                'countdown'=>$share_countdown
            );
            if(!empty($res_usercard['qrcode_img'])){
                $message['codeUrl'] = $oss_host.$res_usercard['qrcode_img'];
            }
            $m_netty = new \Common\Model\NettyModel();
            $res_push = $m_netty->pushBox($box_mac,json_encode($message));
            if($res_push['error_code']){
                $this->to_back($res_push['error_code']);
            }
        }
        $this->to_back($res_push);
    }

}