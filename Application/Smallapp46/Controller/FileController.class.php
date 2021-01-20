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
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'file_path'=>1002,'type'=>1001,'file_ids'=>1002,'file_info'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'type'=>1001);
                break;
            case 'shareFileOnTv':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'file_id'=>1001);
                break;
            case 'info':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'file_id'=>1001);
                break;
            case 'getFileForscreenInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'file_id'=>1001);
                break;
            case 'getConversionResult':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
        }
        parent::_init_();

    }


    public function addFile(){
        $max_file = 6;

        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $file_path = $this->params['file_path'];
        $type = $this->params['type'];//1分享文件 2投屏文件 3视频(商务宴请) 4图片(商务宴请) 5视频(生日聚会) 6图片(生日聚会)
        $file_ids = $this->params['file_ids'];//如果有值则删除
        $file_info = $this->params['file_info'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        if(!empty($file_ids)){
            $file_ids_arr = explode(',',$file_ids);
            $now_file_ids = array();
            foreach ($file_ids_arr as $v){
                $file_id = intval($v);
                if($file_id>0){
                    $now_file_ids[]=$v;
                }
            }
            if(!empty($now_file_ids)){
                $condition = array('user_id'=>$user_info['id']);
                $condition['id'] = array('in',$now_file_ids);
                $condition['status'] = 1;
                $res_files = $m_userfile->getDataList('id',$condition,'id desc');
                if(!empty($res_files)){
                    $del_ids = array();
                    foreach ($res_files as $v){
                        $del_ids[]=$v['id'];
                    }
                    $where = array('id'=>array('in',$del_ids));
                    $m_userfile->updateData($where,array('status'=>2));
                }
            }
        }

        if(!empty($file_path)){
            $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>$type);
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
            $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
            $condition['status'] = 1;
            $res_userfile = $m_userfile->getDataList('*',$condition,'id desc');
            $has_files = array();
            if(!empty($res_userfile)){
                foreach ($res_userfile as $v){
                    $has_files[]=$v['file_path'];
                }
            }
            $has_file_count = count($has_files);

            $now_files = array();
            $file_path_arr = explode(',',$file_path);
            foreach ($file_path_arr as $v){
                if(!empty($v) && !in_array($v,$has_files)){
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
            $file_resource_size = array();
            if(!empty($file_info)){
                $json_str = stripslashes(html_entity_decode($file_info));
                $file_message = json_decode($json_str,true);
                foreach ($file_message as $v){
                    if($v['file_id']==0){
                        $info = array('resource_size'=>$v['resource_size'],'duration'=>0);
                        if($v['duration']>0){
                            $info['duration'] = $v['duration'];
                        }
                        $file_resource_size[$v['oss_file_path']] = $info;
                    }
                }
            }
            $file_data = array();
            if(!empty($now_files)){
                foreach ($now_files as $v){
                    $data = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'hotel_id'=>$hotel_id,'type'=>$type,'file_path'=>$v,'status'=>1);
                    if(isset($file_resource_size[$v])){
                        $data['resource_size'] = $file_resource_size[$v]['resource_size'];
                        $data['duration'] = $file_resource_size[$v]['duration'];
                    }
                    $res_file_id = $m_userfile->add($data);
                    $data['id'] = $res_file_id;
                    $file_data[]=$data;
                }
                if($type==2){
                    sendTopicMessage($user_info['id'],40);
                }
                if(in_array($type,array(3,4,5,6))){
                    $m_userfile->pushDwonloadFile($file_data,$type);
                }
            }
        }

        $resp_data = array();
        $this->to_back($resp_data);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];//1分享文件 2投屏文件 3视频(商务宴请) 4图片(商务宴请) 5视频(生日聚会) 6图片(生日聚会)

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
        $all_file = array();
        if(!empty($res_files)){
            $oss_host = 'http://'.C('OSS_HOST').'/';
            foreach ($res_files as $v){
                $filename = str_replace('forscreen/resource/','',$v['file_path']);
                $file_info = pathinfo($v['file_path']);
                $tmp_file_name = str_replace(".{$file_info['extension']}",'',$filename);
                $img_url = '';
                if($type==1){
                    $view_file_name = text_substr($tmp_file_name, 11,'***');
                    $view_file_name = $view_file_name.'.'.$file_info['extension'];
                }else{
                    $view_file_name = $filename;
                    if($type==3 || $type==5){
                        $img_url = $v['file_path'].'?x-oss-process=video/snapshot,t_10000,f_jpg,w_450,m_fast';
                    }elseif($type==4 || $type==6){
                        $img_url = $v['file_path']."?x-oss-process=image/quality,Q_50";
                    }
                }
                $info = array('file_id'=>$v['id'],'name'=>$filename,'view_file_name'=>$view_file_name,
                    'img_url'=>$img_url,'oss_file_path'=>$v['file_path'],'extension'=>$file_info['extension'],
                    'resource_size'=>$v['resource_size'],'duration'=>$v['duration']
                    );
                if($type==3 || $type==5){
                    $info['percent'] = 0;
                }
                $all_file[] = $info;
            }
        }
        $file_num = count($all_file);
        switch ($type){
            case 1:
                $res = array('share_file'=>$all_file,'share_file_num'=>$file_num);
                break;
            case 2:
                $res = array('forscreen_file'=>$all_file,'forscreen_file_num'=>$file_num);
                break;
            case 3:
            case 5:
                $res = array('videos'=>$all_file,'videos_num'=>$file_num);
                break;
            case 4:
            case 6:
                $res = array('images'=>$all_file,'images_num'=>$file_num);
                break;
            default:
                $res = array();
        }
        $this->to_back($res);
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

        $m_box = new \Common\Model\BoxModel();
        $condition = array('box.mac'=>$res_file['box_mac'],'box.state'=>1,'box.flag'=>0);
        $res_box = $m_box->getBoxByCondition('box.id as box_id',$condition);
        if(empty($res_box)){
            $this->to_back(70001);
        }
        $filename = str_replace('forscreen/resource/','',$res_file['file_path']);
        $host_name = C('HOST_NAME');
        $qrcode_url = $host_name."/smallapp46/qrcode/getBoxQrcode?box_id={$res_box['box_id']}&type=34&data_id={$file_id}";
        $message = array('action'=>170,'nickName'=>$user_info['nickName'],'headPic'=>base64_encode($user_info['avatarUrl']),
            'filename'=>$filename,'codeUrl'=>$qrcode_url,'countdown'=>$share_countdown
        );

        $m_netty = new \Common\Model\NettyModel();
        $res_push = $m_netty->pushBox($res_file['box_mac'],json_encode($message));
        if($res_push['error_code']){
            $this->to_back($res_push['error_code']);
        }else{
            $this->to_back(array());
        }
    }

    public function info(){
        $openid = $this->params['openid'];
        $file_id = intval($this->params['file_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id,nickName,avatarUrl', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $res_file = $m_userfile->getInfo(array('id'=>$file_id));
        if(empty($res_file)){
            $this->to_back(90161);
        }
        $filename = str_replace('forscreen/resource/','',$res_file['file_path']);
        $oss_host = "https://".C('OSS_HOST').'/';
        $file_info = pathinfo($res_file['file_path']);
        $open_file_ext = C('SHARE_FILE_TYPES');
        $extension =  $file_info['extension'];
        $is_open = 0;
        if(in_array($extension,$open_file_ext)){
            $is_open = 1;
        }
        $where = array('id'=>$res_file['user_id']);
        $user_info = $m_user->getOne('id,nickName,avatarUrl', $where, 'id desc');

        $res = array('nickName'=>$user_info['nickName'],'avatarUrl'=>$user_info['avatarUrl'],
            'file_id'=>$res_file['id'],'file_path'=>$res_file['file_path'],'name'=>$filename,
            'oss_file_path'=>$oss_host.$res_file['file_path'],'extension'=>$extension,'is_open'=>$is_open);
        $this->to_back($res);
    }

    public function getConversionResult(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }

        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $condition = array('user_id'=>$user_info['id'],'box_mac'=>$box_mac,'type'=>2);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $condition['status'] = 1;
        $res_files = $m_userfile->getDataList('*',$condition,'id desc');
        $is_stop = 1;
        $forscreen_file = array();
        $forscreen_file_num = 0;
        if(!empty($res_files)){
            foreach ($res_files as $v){
                $file_info = pathinfo($v['file_path']);
                $info = array('file_id'=>$v['id'],'name'=>$file_info['basename'],'file_path'=>$v['file_path']);
                $file_conversion_status = $v['file_conversion_status'];
                if(in_array($file_conversion_status,array(2,4))){
                    $file_conversion_status = 2;
                    $m_userfile->pushDwonloadFile($v,$v['type']);
                }
                if($file_conversion_status==0 || $file_conversion_status==1){
                    $is_stop = 0;
                }
                if($file_conversion_status==2){
                    $forscreen_status = 2;
                }else{
                    $forscreen_status = 1;
                }
                $info['forscreen_status'] = $forscreen_status;
                $forscreen_file[]=$info;
            }
            $forscreen_file_num = count($forscreen_file);
        }
        $res = array('is_stop'=>$is_stop,'forscreen_file_num'=>$forscreen_file_num,'forscreen_file'=>$forscreen_file);
        $this->to_back($res);
    }

    public function getFileForscreenInfo(){
        $openid = $this->params['openid'];
        $file_id = intval($this->params['file_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id,nickName,avatarUrl', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $res_file = $m_userfile->getInfo(array('id'=>$file_id));
        if(empty($res_file)){
            $this->to_back(90161);
        }
        $md5_file = $res_file['md5_file'];
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SAPP_FILE_FORSCREEN');
        $cache_key = $key.':'.$md5_file;
        $res_cache = $redis->get($cache_key);
        if(empty($res_cache)) {
            $imgs = array();
        }else{
            $imgs = json_decode($res_cache, true);
        }
        $img_num = count($imgs);
        $oss_host = C('OSS_HOST');
        $result = array('oss_host'=>"http://$oss_host",'oss_suffix'=>'?x-oss-process=image/resize,p_20','imgs'=>$imgs,'img_num'=>$img_num);
        $this->to_back($result);
    }
}