<?php
/*
 * H5文件投屏
 */
namespace H5\Controller;
use Think\Controller;
use Common\Lib\AliyunOss;
use Common\Lib\AliyunImm;
class FileforscreenController extends Controller {

    public function index(){
        $openid = I('get.openid','');
        $source = I('get.source','');
        if(empty($openid)){
            die('Parameter error');
        }
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $fields = 'id,imgs,resource_name,resource_size,md5_file';
        $where = array('openid'=>$openid,'action'=>30,'save_type'=>2,'file_conversion_status'=>1);
        $where['md5_file'] = array('neq','');
        $order = 'id desc';
        $res_latest = $m_forscreen->getWhere($fields,$where,$order,4,'md5_file');
        $latest_screen = array();
        $frequent_screen = array();
        if(!empty($res_latest)){
            $img_host = 'https://'.C('OSS_HOST').'/Html5/images/mini-push/pages/forscreen/forfile/';
            $file_ext_images = C('SAPP_FILE_FORSCREEN_IMAGES');
            $latest_md5_file = array();
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $cache_key = C('SAPP_FILE_FORSCREEN');
            foreach ($res_latest as $v){
                $latest_md5_file[] = $v['md5_file'];
                $imgs = json_decode($v['imgs'],true);
                $file_type = pathinfo($imgs[0],PATHINFO_EXTENSION);
                $res_cache = $redis->get($cache_key.':'.$v['md5_file']);
                $page_num = 0;
                if(!empty($res_cache)) {
                    $imgs = json_decode($res_cache, true);
                    $page_num = count($imgs);
                }
                $file_size = formatBytes($v['resource_size']);
                $ext_img = $img_host.$file_ext_images[strtolower($file_type)];
                $info = array('forscreen_id'=>$v['id'],'file_type'=>strtoupper($file_type),'file_name'=>$v['resource_name'],
                    'page_num'=>$page_num,'file_size'=>$file_size,'ext_img'=>$ext_img);
                $latest_screen[] = $info;
            }
            $fields.=',count(id) as num';
            $order = 'num desc';
            $res_frequent = $m_forscreen->getWhere($fields,$where,$order,8,'md5_file');
            foreach ($res_frequent as $v){
                if(in_array($v['md5_file'],$latest_md5_file)){
                    continue;
                }
                if(count($frequent_screen)>=4){
                    break;
                }
                $res_cache = $redis->get($cache_key.':'.$v['md5_file']);
                $page_num = 0;
                if(!empty($res_cache)) {
                    $imgs = json_decode($res_cache, true);
                    $page_num = count($imgs);
                }
                $imgs = json_decode($v['imgs'],true);
                $file_type = pathinfo($imgs[0],PATHINFO_EXTENSION);

                $file_size = formatBytes($v['resource_size']);
                $ext_img = $img_host.$file_ext_images[strtolower($file_type)];
                $info = array('forscreen_id'=>$v['id'],'file_type'=>strtoupper($file_type),'file_name'=>$v['resource_name'],
                    'page_num'=>$page_num,'file_size'=>$file_size,'ext_img'=>$ext_img);
                $frequent_screen[] = $info;
            }
        }
        if($source=='sale'){
            $display_html = 'sale';
        }elseif($source=='new'){
            $display_html = 'indexnew';
        }else{
            $display_html = 'index';
        }
        $file_ext = C('SAPP_FILE_FORSCREEN_TYPES');
        $this->assign('file_ext',join(',',array_keys($file_ext)));
        $this->assign('latest_screen',$latest_screen);
        $this->assign('frequent_screen',$frequent_screen);
        $this->display($display_html);
    }

    public function addlog(){
        $os_agent = $_SERVER['HTTP_USER_AGENT'];
        $wx_browser = (bool) stripos($os_agent,'MicroMessenger');
        $res = array('code'=>10001,'msg'=>'fail');
        if($wx_browser){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $key = C('SAPP_FILE_FORSCREEN');
            $cache_key = $key.':h5file_forscreen_report';
            $res_cache = $redis->get($cache_key);
            if(empty($res_cache)){
                $num = 1;
            }else{
                $num = intval($res_cache)+1;
            }
            $redis->set($cache_key,$num);

            $res['code'] = 10000;
            $res['msg'] = 'success';
        }
        $this->ajaxReturn($res,'JSONP');
    }

    /*
     * 商务宴请MNS通知转换文件
     */
    public function conversion(){
        $content = file_get_contents('php://input');
        $log_file_name = APP_PATH.'Runtime/Logs/'.'file_mns_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s').'|content|'.$content."\r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
        $user_id = 0;
        if(!empty($content)) {
            $res = json_decode($content, true);
            if (!empty($res['Message'])) {
                $message = base64_decode($res['Message']);
                $res_message = json_decode($message,true);
                $user_id = intval($res_message[0]['order_id']);
            }
        }
        $condition = array('user_id'=>$user_id,'type'=>2);
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $condition['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');
        $condition['status'] = 1;
        $condition['file_conversion_status'] = 0;
        $m_userfile = new \Common\Model\Smallapp\UserfileModel();
        $res_userfile = $m_userfile->getDataList('*',$condition,'id desc');
        if(!empty($res_userfile)){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $key = C('SAPP_FILE_FORSCREEN');

            $accessKeyId = C('OSS_ACCESS_ID');
            $accessKeySecret = C('OSS_ACCESS_KEY');
            $endpoint = 'oss-cn-beijing.aliyuncs.com';
            $bucket = C('OSS_BUCKET');
            $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
            $aliyunoss->setBucket($bucket);
            foreach ($res_userfile as $v){
                $oss_addr = $v['file_path'];
                $fileinfo = $aliyunoss->getObject($oss_addr,'');
                if($fileinfo){
                    $md5_file = md5($fileinfo);
                    $cache_key = $key.':'.$md5_file;
                    $res_cache = $redis->get($cache_key);
                    if(empty($res_cache)){
                        $aliyun = new AliyunImm();
                        $res = $aliyun->createOfficeConversion($oss_addr);
                        $task_id = $res->TaskId;
                        if(!empty($task_id)){
                            $data = array('task_id'=>$task_id,'file_conversion_status'=>1,'md5_file'=>$md5_file,
                                'start_time'=>date('Y-m-d H:i:s'));
                            $m_userfile->updateData(array('id'=>$v['id']),$data);
                        }
                    }else{
                        $data = array('file_conversion_status'=>4,'md5_file'=>$md5_file);
                        $m_userfile->updateData(array('id'=>$v['id']),$data);
                    }
                }
            }
        }
    }

}