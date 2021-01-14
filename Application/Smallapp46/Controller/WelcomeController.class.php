<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class WelcomeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'config':
                $this->is_verify = 0;
                break;
            case 'addwelcome':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'images'=>1001,
                    'content'=>1002,'wordsize_id'=>1001,'color_id'=>1001,'font_id'=>1002,'stay_time'=>1001,'type'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'welcome_id'=>1001);
                break;
            case 'demandplay':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'welcome_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function config(){
        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $fields = 'id,name,media_id,color,small_wordsize,type';
        $where = array('status'=>1);
        $res_resource = $m_welcomeresource->getDataList($fields,$where,'id asc');
        $wordsize = $color = $music = $font = array();
        if(!empty($res_resource)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_resource as $v){
                switch ($v['type']){
                    case 1:
                        $wordsize[]=array('id'=>$v['id'],'name'=>$v['name'],'wordsize'=>$v['small_wordsize']);
                        break;
                    case 2:
                        $color[]=array('id'=>$v['id'],'color'=>$v['color']);
                        break;
                    case 3:
                        $res_media = $m_media->getMediaInfoById($v['media_id']);
                        $oss_addr = $res_media['oss_addr'];
                        $music[]=array('id'=>$v['id'],'name'=>$v['name'],'oss_addr'=>$oss_addr);
                        break;
                    case 5:
                        $res_media = $m_media->getMediaInfoById($v['media_id'],'https');
                        $oss_addr = $res_media['oss_addr'];
                        $font[]=array('id'=>$v['id'],'name'=>$v['name'],'oss_addr'=>$oss_addr);
                        break;
                }
            }
        }
        array_unshift($music,array('id'=>0,'name'=>'无音乐'));
        array_unshift($font,array('id'=>0,'name'=>'默认字体'));
        $font_namelist = array();
        foreach ($font as $v){
            $font_namelist[]=$v['name'];
        }
        $stay_times = array(
            array('id'=>2,'name'=>'2分钟','is_select'=>1),
            array('id'=>5,'name'=>'5分钟','is_select'=>0),
            array('id'=>10,'name'=>'10分钟','is_select'=>0),
        );
        $res_data = array('wordsize'=>$wordsize,'color'=>$color,'music'=>$music,
            'stay_times'=>$stay_times,'font'=>$font,'font_namelist'=>$font_namelist);
        $this->to_back($res_data);
    }

    public function addwelcome(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $images = $this->params['images'];
        $content = trim($this->params['content']);
        $wordsize_id = $this->params['wordsize_id'];
        $color_id = $this->params['color_id'];
        $font_id = intval($this->params['font_id']);
        $stay_time = $this->params['stay_time'];
        $type = intval($this->params['type']);//3商务宴请 4生日聚会

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $images_arr = explode(',',$images);
        $all_images = array();
        foreach ($images_arr as $v){
            if(!empty($v)){
                $all_images[]=$v;
            }
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0);
        $res_box = $m_box->getBoxInfo('d.id as hotel_id',$where);
        $hotel_id = 0;
        if(!empty($res_box)){
            $hotel_id = $res_box[0]['hotel_id'];
        }
        $user_id = $res_user['id'];
        $data = array('user_id'=>$user_id,'content'=>$content,'wordsize_id'=>$wordsize_id,'font_id'=>$font_id,
            'color_id'=>$color_id,'hotel_id'=>$hotel_id,'box_mac'=>$box_mac,'type'=>$type,'stay_time'=>$stay_time);
        $data['image'] = join(',',$all_images);

        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $condition = array('user_id'=>$user_id,'box_mac'=>$box_mac,'type'=>$type);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $res_welcome = $m_welcome->getInfo($condition);
        if(empty($res_welcome)){
            $welcome_id = $m_welcome->addData($data);
        }else{
            $welcome_id = $res_welcome['id'];
            $m_welcome->updateData(array('id'=>$res_welcome['id']),$data);
        }
        $this->to_back(array('welcome_id'=>$welcome_id));
    }

    public function detail(){
        $openid = $this->params['openid'];
        $welcome_id = intval($this->params['welcome_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $res_welcome = $m_welcome->getInfo(array('id'=>$welcome_id));
        $data = array();
        if(!empty($res_welcome)){
            $data = array('welcome_id'=>$welcome_id,'content'=>$res_welcome['content'],'wordsize_id'=>$res_welcome['wordsize_id'],
                'color_id'=>$res_welcome['color_id'],'font_id'=>$res_welcome['font_id'],'stay_time'=>$res_welcome['stay_time']
            );
            $oss_host = "https://".C('OSS_HOST').'/';
            $image = $image_path = array();
            $imgs = explode(',',$res_welcome['image']);
            if(!empty($imgs)){
                foreach ($imgs as $v){
                    if(!empty($v)){
                        $img_url = $oss_host.$v."?x-oss-process=image/resize,m_mfit,h_400,w_750";
                        $image[] = $img_url;
                        $image_path[] = $v;
                    }
                }
            }
            $data['image_path'] = $image;
            $data['images'] = $image_path;
        }
        $this->to_back($data);
    }

    public function demandplay(){
        $openid = $this->params['openid'];
        $welcome_id = intval($this->params['welcome_id']);
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $res_welcome = $m_welcome->getInfo(array('id'=>$welcome_id));
        if($res_welcome['user_id']!=$res_user['id']){
            $this->to_back(93020);
        }
        $res_push = $this->push_welcome($res_welcome);
        if($res_push['error_code']){
            $this->to_back($res_push['error_code']);
        }else{
            $this->to_back(array());
        }
    }

    private function push_welcome($res_welcome){
        $action = 137;
        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $wordsize_id = $res_welcome['wordsize_id'];
        $color_id = $res_welcome['color_id'];
        $backgroundimg_id = $res_welcome['backgroundimg_id'];
        $font_id = $res_welcome['font_id'];

        $ids = array($wordsize_id,$color_id);
        if($font_id){
            $ids[]=$font_id;
        }
        if($backgroundimg_id){
            $ids[]=$backgroundimg_id;
        }
        $where = array('id'=>array('in',$ids));
        $res_resource = $m_welcomeresource->getDataList('*',$where,'id asc');
        $resource_info = array();
        foreach ($res_resource as $v){
            $resource_info[$v['id']]=$v;
        }
        $finish_time = time() + $res_welcome['stay_time']*60;
        $message = array('action'=>$action,'id'=>$res_welcome['id'],'forscreen_char'=>$res_welcome['content'],
            'wordsize'=>$resource_info[$wordsize_id]['tv_wordsize'],'color'=>$resource_info[$color_id]['color'],
            'finish_time'=>date('Y-m-d H:i:s',$finish_time));
        $img_list = array();
        $imgs = explode(',',$res_welcome['image']);
        if(!empty($imgs)){
            foreach ($imgs as $v){
                if(!empty($v)){
                    $file_info = pathinfo($v);
                    $img_list[] = array('url'=>$v,'filename'=>$file_info['basename']);
                }
            }
        }
        $message['img_list'] = $img_list;

        $m_media = new \Common\Model\MediaModel();
        if(isset($resource_info[$font_id])){
            $res_media = $m_media->getMediaInfoById($resource_info[$font_id]['media_id']);
            $message['font_id'] = intval($font_id);
            $message['font_oss_addr'] = $res_media['oss_addr'];
        }else{
            $message['font_id'] = 0;
            $message['font_oss_addr'] = '';
        }
        $playtime = intval($res_welcome['stay_time']*60);
        $message['play_times'] = $playtime;

        $m_netty = new \Common\Model\NettyModel();
        $res_push = $m_netty->pushBox($res_welcome['box_mac'],json_encode($message));
        return $res_push;
    }
}