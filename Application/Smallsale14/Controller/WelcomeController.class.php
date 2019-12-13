<?php
namespace Smallsale14\Controller;
use \Common\Controller\CommonController as CommonController;

class WelcomeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'categorylist':
                $this->is_verify = 0;
                $this->valid_fields = array('openid'=>1002);
                break;
            case 'imglist':
                $this->is_verify = 1;
                $this->valid_fields = array('category_id'=>1001);
                break;
            case 'config':
                $this->is_verify = 0;
                break;
            case 'addwelcome':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'image'=>1002,'rotate'=>1002,
                    'backgroundimg_id'=>1002,'content'=>1001,'wordsize_id'=>1001,'color_id'=>1001,
                    'music_id'=>1001,'play_type'=>1001,'play_date'=>1002,'timing'=>1002);
                break;
            case 'getwelcomelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'page'=>1001);
                break;
            case 'demandplay':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'welcome_id'=>1001,'type'=>1001);
                break;
        }
        parent::_init_();
    }

    public function categorylist(){
        $openid = $this->params['openid'];
        $m_category = new \Common\Model\Smallapp\CategoryModel();
        $where = array('type'=>6,'status'=>1,'level'=>1);
        $res_category = $m_category->getDataList('id,name',$where,'id desc');
        $category_name_list = array();
        foreach ($res_category as $v){
            $category_name_list[]=$v['name'];
        }
        $data = array('category_list'=>$res_category,'category_name_list'=>$category_name_list);
        $this->to_back($data);
    }

    public function imglist(){
        $category_id = $this->params['category_id'];
        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $where = array('category_id'=>$category_id,'type'=>4);
        $res_imgs = $m_welcomeresource->getDataList('id,media_id',$where,'id desc');
        $datalist = array();
        if(!empty($res_imgs)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_imgs as $v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $oss_addr = $res_media['oss_addr'];
                $datalist[] = array('id'=>$v['id'],'oss_addr'=>$oss_addr);
            }
        }
        $this->to_back($datalist);
    }

    public function config(){
        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $fields = 'id,name,media_id,color,small_wordsize,type';
        $where = array('status'=>1);
        $res_resource = $m_welcomeresource->getDataList($fields,$where,'id asc');
        $wordsize = $color = $music = array();
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
                }
            }
        }
        array_unshift($music,array('id'=>0,'name'=>'无音乐'));
        $m_sys_config = new \Common\Model\SysConfigModel();
        $sys_info = $m_sys_config->getAllconfig();
        $playtime = $sys_info['welcome_playtime'];
        $res_data = array('playtime'=>$playtime,'wordsize'=>$wordsize,'color'=>$color,'music'=>$music);
        $this->to_back($res_data);
    }

    public function addwelcome(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $image = $this->params['image'];
        $rotate = $this->params['rotate'];
        $backgroundimg_id = intval($this->params['backgroundimg_id']);
        $content = $this->params['content'];
        $wordsize_id = $this->params['wordsize_id'];
        $color_id = $this->params['color_id'];
        $music_id = intval($this->params['music_id']);
        $play_type = $this->params['play_type'];
        $play_date = $this->params['play_date'];
        $timing = $this->params['timing'];

        if(empty($image) && empty($backgroundimg_id)){
            $this->to_back(1001);
        }

        if($play_type==2){
            if(empty($play_date) || empty($timing)){
                $this->to_back(1001);
            }
        }

        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $field = 'a.id as box_id,d.id as hotel_id';
        $box_info = $m_box->getBoxInfo($field, $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $user_id = $res_user['id'];
        $data = array('user_id'=>$user_id,'rotate'=>$rotate,'content'=>$content,'wordsize_id'=>$wordsize_id,
            'color_id'=>$color_id,'music_id'=>$music_id,'play_type'=>$play_type,'hotel_id'=>$hotel_id,'box_mac'=>$box_mac);
        if($image){
            $data['image'] = $image;
        }
        if($backgroundimg_id){
            $data['backgroundimg_id'] = $backgroundimg_id;
        }
        $play_hour = 2*3600;
        if($play_type==2){
            $data['play_date'] = $play_date;
            $data['timing'] = $timing;
            $data['status'] = 2;
            $play_time = "$play_date $timing";
            $stime = strtotime($play_time);
        }else{
            $data['status'] = 1;
            $stime = time();
        }
        $data['finish_time'] = date('Y-m-d H:i:s',$stime+$play_hour);
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $res_welcome = $m_welcome->addData($data);
        if($res_welcome && $play_type==1){
            $this->push_welcome($res_welcome,130);
        }
        $this->to_back(array());
    }

    public function getwelcomelist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $page = intval($this->params['page']);
        $pagesize = 10;
        $all_nums = $page * $pagesize;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $user_id = $res_user['id'];

        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $fields = 'id,content,status,box_mac,play_type,play_date,timing,add_time';
        $where = array('user_id'=>$user_id,'hotel_id'=>$hotel_id);
        $where['status'] = array('in',array(1,2));
        $where['finish_time'] = array('egt',date('Y-m-d H:i:s'));
        $res_welcome = $m_welcome->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_welcome['total']){
            $m_box = new \Common\Model\BoxModel();
            foreach ($res_welcome['list'] as $k=>$v){
                $box_mac = $v['box_mac'];
                $res_box = $m_box->getInfoByHotelid($hotel_id,'room.name'," and box.mac='$box_mac' and box.state=1 and box.flag=0");
                if(empty($res_box)){
                    continue;
                }
                if($v['play_type']==1){
                    $start_time = date('Y-m-d H:i',strtotime($v['add_time']));
                }else{
                    $start_time = date('Y-m-d H:i',strtotime($v['play_date'].' '.$v['timing']));
                }
                $datalist[]=array('id'=>$v['id'],'room_name'=>$res_box[0]['name'],'content'=>$v['content'],
                    'start_time'=>$start_time,'status'=>$v['status']);
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function startplay(){
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
        if(in_array($res_welcome['status'],array(1,3,4))){
            $this->to_back(93019);
        }
        if($res_welcome['user_id']!=$res_user['id']){
            $this->to_back(93020);
        }
        $res = $m_welcome->updateData(array('id'=>$welcome_id),array('status'=>1));
        if($res){
            $this->push_welcome($welcome_id,130);
        }
        $this->to_back(array());
    }

    public function stopplay(){
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
        if(in_array($res_welcome['status'],array(3,4))){
            $this->to_back(93021);
        }
        if($res_welcome['user_id']!=$res_user['id']){
            $this->to_back(93020);
        }
        $res = $m_welcome->updateData(array('id'=>$welcome_id),array('status'=>3));
        if($res){
            $this->push_welcome($welcome_id,131);
        }
        $this->to_back(array());
    }

    public function removeplay(){
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
        if($res_welcome['status']==1){
            $this->to_back(93023);
        }
        if($res_welcome['status']==4){
            $this->to_back(93022);
        }
        if($res_welcome['user_id']!=$res_user['id']){
            $this->to_back(93020);
        }
        $res = $m_welcome->updateData(array('id'=>$welcome_id),array('status'=>4));
        if($res){
            $this->push_welcome($welcome_id,132);
        }
        $this->to_back(array());
    }



    private function push_welcome($id,$action){
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $res_welcome = $m_welcome->getInfo(array('id'=>$id));

        $m_welcomeresource = new \Common\Model\Smallapp\WelcomeresourceModel();
        $wordsize_id = $res_welcome['wordsize_id'];
        $color_id = $res_welcome['color_id'];
        $backgroundimg_id = $res_welcome['backgroundimg_id'];
        $music_id = $res_welcome['music_id'];

        $ids = array($wordsize_id,$color_id);
        if($music_id){
            $ids[]=$music_id;
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
        $message = array('action'=>$action,'id'=>$id,'forscreen_char'=>$res_welcome['content'],'rotation'=>intval($res_welcome['rotate']),
            'wordsize'=>$resource_info[$wordsize_id]['tv_wordsize'],'color'=>$resource_info[$color_id]['color'],
            'finish_time'=>$res_welcome['finish_time']);
        $m_media = new \Common\Model\MediaModel();
        if(isset($resource_info[$backgroundimg_id])){
            $res_media = $m_media->getMediaInfoById($resource_info[$backgroundimg_id]['media_id']);
            $message['img_id'] = intval($backgroundimg_id);
            $message['img_oss_addr'] = $res_media['oss_addr'];
        }else{
            $message['img_id'] = 0;
            $img_oss_addr = $res_welcome['image'];
            $message['img_oss_addr'] = $img_oss_addr;
        }
        if(isset($resource_info[$music_id])){
            $res_media = $m_media->getMediaInfoById($resource_info[$music_id]['media_id']);
            $message['music_id'] = intval($music_id);
            $message['music_oss_addr'] = $res_media['oss_addr'];
        }else{
            $message['music_id'] = 0;
            $message['music_oss_addr'] = '';
        }
        $m_sys_config = new \Common\Model\SysConfigModel();
        $sys_info = $m_sys_config->getAllconfig();
        $playtime = $sys_info['welcome_playtime'];
        $playtime = intval($playtime*60);
        $message['play_times'] = $playtime;
        $m_netty = new \Common\Model\NettyModel();
        $res_netty = $m_netty->pushBox($res_welcome['box_mac'],json_encode($message));
        if(isset($res_netty['error_code']) && $res_netty['error_code']==90109){
            $m_netty->pushBox($res_welcome['box_mac'],json_encode($message));
        }
        return $message;
    }
}