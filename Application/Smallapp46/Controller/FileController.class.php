<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class FileController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addFile':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'file_path'=>1001,'type'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'type'=>1001);
                break;
            case 'delfile':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'file_ids'=>1001);
                break;
            case 'shareFileOnTv':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'file_id'=>1001);
                break;
        }
        parent::_init_();

    }


    public function addFile(){
        $max_file = 6;

        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $file_path = $this->params['file_path'];
        $type = $this->params['type'];//1分享文件 2投屏文件

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>$type);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $condition['status'] = 1;
        $res_userfile = $m_userfile->getDataList('*',$condition,'id desc');
        $has_file_count = count($res_userfile);
        $file_path_arr = explode(',',$file_path);
        $now_files = array();
        foreach ($file_path_arr as $v){
            if(!empty($v)){
                $now_files[]=$v;
            }
        }
        $now_file_count = count($now_files);
        if($has_file_count+$now_file_count > $max_file){
            $this->to_back(90159);
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0);
        $res_box = $m_box->getBoxInfo('d.id as hotel_id',$where);
        $hotel_id = 0;
        if(!empty($res_box)){
            $hotel_id = $res_box[0]['hotel_id'];
        }
        $add_data = array();
        foreach ($now_files as $v){
            $data = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'hotel_id'=>$hotel_id,'type'=>$type,'file_path'=>$v,'status'=>1);
            $add_data[]=$data;
        }
        $m_userfile->addAll($add_data);
        $resp_data = array();
        $this->to_back($resp_data);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];//1分享文件 2投屏文件

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>$type);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $condition['status'] = 1;
        $res_files = $m_userfile->getDataList('*',$condition,'id desc');
        $share_file = array();
        if(!empty($res_files)){
            foreach ($res_files as $v){
                $file_info = pathinfo($v['file_path']);
                $share_file[] = array('file_id'=>$v['id'],'name'=>$file_info['basename'],'extension'=>$file_info['extension']);
            }
        }
        $this->to_back($share_file);
    }

    public function delfile(){
        $openid = $this->params['openid'];
        $file_ids = $this->params['file_ids'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $file_ids_arr = explode(',',$file_ids);
        $now_file_ids = array();
        foreach ($file_ids_arr as $v){
            $file_id = intval($v);
            if($file_id>0){
                $now_file_ids[]=$v;
            }
        }
        if(!empty($now_file_ids)){
            $m_userfile = new \Common\Model\Smallapp\UserfileModel();
            $condition = array('user_id'=>$user_info['id']);
            $condition['id'] = array('in',$now_file_ids);
            $condition['status'] = 1;
            $res_files = $m_userfile->getDataList('*',$condition,'id desc');
            if(count($now_file_ids) != count($res_files)){
                $this->to_back(90160);
            }
            $where = array('id'=>array('in',$now_file_ids));
            $m_userfile->updateData($where,array('status'=>2));
        }
        $this->to_back(array());
    }

    public function shareFileOnTv(){
        $openid = $this->params['openid'];
        $file_id = intval($this->params['file_id']);
        $share_countdown = 60;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id,nickName,avatarUrl', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $res_file = $m_userfile->getInfo(array('id'=>$file_id,'user_id'=>$user_info['id']));
        if(empty($res_file)){
            $this->to_back(90161);
        }
        $file_info = pathinfo($res_file['file_path']);

        $m_box = new \Common\Model\BoxModel();
        $condition = array('box.mac'=>$res_file['box_mac'],'box.state'=>1,'box.flag'=>0);
        $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
        if(empty($res_box)){
            $this->to_back(70001);
        }

        $host_name = C('HOST_NAME');
        $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_id={$res_box['box_id']}&type=34&data_id={$file_id}";
        $message = array('action'=>170,'nickName'=>$user_info['nickName'],'headPic'=>base64_encode($user_info['avatarUrl']),
            'filename'=>$file_info['basename'],'codeUrl'=>$qrcode_url,'countdown'=>$share_countdown
        );

        $m_netty = new \Common\Model\NettyModel();
        $m_netty->pushBox($res_file['box_mac'],json_encode($message));
        $this->to_back(array());
    }

}