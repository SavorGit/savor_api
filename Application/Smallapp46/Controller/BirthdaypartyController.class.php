<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class BirthdaypartyController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'moduleList':
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
        $welcome = new \stdClass();
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>4);
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
        $videos = $images = array();

        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'status'=>1);
        $condition['type'] = array('in',array(5,6));
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $res_files = $m_userfile->getDataList('*',$condition,'id desc');
        if(!empty($res_files)){
            foreach ($res_files as $v){
                $file_info = pathinfo($v['file_path']);
                $info = array('file_id'=>$v['id'],'name'=>$file_info['basename'],'file_path'=>$v['file_path'],
                    'resource_size'=>$v['resource_size'],'duration'=>intval($v['duration']));
                switch ($v['type']){
                    case 5:
                        $img_url = $v['file_path'].'?x-oss-process=video/snapshot,t_10000,f_jpg,w_450,m_fast';
                        $info['img_url'] = $img_url;
                        $info['video_id'] = $file_info['filename'];
                        $videos[]=$info;
                        break;
                    case 6:
                        $info['img_id'] = $file_info['filename'];
                        $images[]=$info;
                        break;
                }
            }
        }
        $videos_num = count($videos);
        $images_num = count($images);
        $resp_data = array('welcome'=>$welcome,'videos'=>$videos,'videos_num'=>$videos_num,
            'images'=>$images,'images_num'=>$images_num);
        $this->to_back($resp_data);
    }

}