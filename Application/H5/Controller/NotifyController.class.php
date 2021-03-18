<?php
/**
 *OSS消息队列相关
 * 
 */
namespace H5\Controller;
use Common\Lib\AliyunImm;
use Think\Controller;
use Common\Lib\AliyunOss;


class NotifyController extends Controller{

    public function netty(){
        $content = file_get_contents('php://input');

        $log_file_name = APP_PATH.'Runtime/Logs/'.'nettycallback_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s')."|content|$content \r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

        if(!empty($content)) {
            $res_message = json_decode($content, true);
            if(is_array($res_message) && !empty($res_message)){
                $req_id = $res_message['req_id'];
                $params = array('netty_callback_result'=>$res_message,'netty_callback_time'=>$res_message['timestamp']);
                if(!empty($res_message['nettyChaId'])){
                    $params['netty_callback_chaid']=$res_message['nettyChaId'];
                }
                $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
                $m_forscreen->recordTrackLog($req_id,$params);
            }
        }
    }

    public function ossVideoSecCheck(){
        $content = file_get_contents('php://input');

        $log_file_name = SAVOR_M_TP_PATH.'/forscreenillegallogs/'.'videocheck_'.date("Ymd").".log";

        if(!empty($content)) {
            $res_message = json_decode($content, true);
            if(is_array($res_message) && !empty($res_message)){
                $redis = new \Common\Lib\SavorRedis();
                $redis->select(1);
                $cache_key = "smallapp:videocheck";
                $res_videocheck = $redis->get($cache_key);
                $videocheck_data = array();
                if(!empty($res_videocheck)){
                    $videocheck_data = json_decode($res_videocheck,true);
                }
                $accessKeyId = C('OSS_ACCESS_ID');
                $accessKeySecret = C('OSS_ACCESS_KEY');
                $endpoint = 'oss-cn-beijing.aliyuncs.com';
                $bucket = C('OSS_BUCKET');
                $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
                $aliyunoss->setBucket($bucket);

                $oss_host = 'http://'.C('OSS_HOST');
                $data_unit = 1048576;//1024*1024 1M
                $message = base64_decode($res_message['Message']);
                $oss_info = json_decode($message,true);

                foreach ($oss_info['events'] as $v){
                    $file_name = $v['oss']['object']['key'];
                    $file_size = $v['oss']['object']['size'];
                    $file_info_arr = pathinfo($file_name);
                    $resource_id = $file_info_arr['filename'];
                    $log_content = date('Y-m-d H:i:s').'|file|'.$file_name.'|size|'.$file_size.'|resource_id|'.$resource_id;

                    if($file_size<=$data_unit*10){
                        $duration = 10;
                    }elseif($file_size>$data_unit*10 && $file_size<=$data_unit*30){
                        $duration = 15;
                    }elseif($file_size>$data_unit*30 && $file_size<=$data_unit*50){
                        $duration = 20;
                    }elseif($file_size>$data_unit*50 && $file_size<=$data_unit*70){
                        $duration = 25;
                    }elseif($file_size>$data_unit*70 && $file_size<=$data_unit*90){
                        $duration = 30;
                    }elseif($file_size>$data_unit*90 && $file_size<=$data_unit*110){
                        $duration = 40;
                    }else{
                        $duration = 50;
                    }

                    $url = $oss_host.'/'.$file_name;
                    $res_check = wx_sec_check($url,$duration);
                    if($res_check[0]['errcode']==87014 && $res_check[1]['errcode']==87014 && $res_check[2]['errcode']==87014){
                        $videocheck_data[$resource_id] = date('Y-m-d H:i:s');
                        $redis->set($cache_key,json_encode($videocheck_data));

//                        $res_delete = $aliyunoss->deleteObject($file_name);
                        $res_delete = 0;
                        $log_content.="|sec_result|".json_encode($res_check).'|oss_delete|'.$res_delete;
                    }
                    $log_content.= "\r\n";
                    @file_put_contents($log_file_name, $log_content, FILE_APPEND);
                }
            }
        }
    }


    public function ossImageSecCheck(){
        $content = file_get_contents('php://input');

        $log_file_name = SAVOR_M_TP_PATH.'/forscreenillegallogs/'.'imagecheck_'.date("Ymd").".log";

        if(!empty($content)) {
            $res_message = json_decode($content, true);
            if(is_array($res_message) && !empty($res_message)){
                $redis = new \Common\Lib\SavorRedis();
                $redis->select(1);
                $cache_key = "smallapp:imagecheck";
                $res_imagecheck = $redis->get($cache_key);
                $imagecheck_data = array();
                if(!empty($res_imagecheck)){
                    $imagecheck_data = json_decode($res_imagecheck,true);
                }
                $accessKeyId = C('OSS_ACCESS_ID');
                $accessKeySecret = C('OSS_ACCESS_KEY');
                $endpoint = 'oss-cn-beijing.aliyuncs.com';
                $bucket = C('OSS_BUCKET');
                $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
                $aliyunoss->setBucket($bucket);

                $oss_host = 'http://'.C('OSS_HOST');
                $message = base64_decode($res_message['Message']);
                $oss_info = json_decode($message,true);

                foreach ($oss_info['events'] as $v){
                    $file_name = $v['oss']['object']['key'];
                    $file_size = $v['oss']['object']['size'];
                    $file_info_arr = pathinfo($file_name);
                    if($file_info_arr['dirname']=='forscreen/resource'){
                        $resource_id = $file_info_arr['filename'];
                        $log_content = date('Y-m-d H:i:s').'|file|'.$file_name.'|size|'.$file_size.'|resource_id|'.$resource_id;

                        $url = $oss_host.'/'.$file_name;
                        $res_check = wx_sec_check($url);
                        if($res_check[0]['errcode']==87014){
                            $imagecheck_data[$resource_id] = date('Y-m-d H:i:s');
                            $redis->set($cache_key,json_encode($imagecheck_data));

//                            $res_delete = $aliyunoss->deleteObject($file_name);
                            $res_delete = 0;
                            $log_content.="|sec_result|".json_encode($res_check).'|oss_delete|'.$res_delete;
                        }
                        $log_content.= "\r\n";
                        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
                    }
                }
            }
        }
    }


    public function fileConversion(){
        $content = file_get_contents('php://input');
        $log_file_name = APP_PATH.'Runtime/Logs/'.'filecnv_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s').'|content|'.$content."\r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
        if(!empty($content)) {
            $res_message = json_decode($content,true);
            if(is_array($res_message) && !empty($res_message)){
                $redis = \Common\Lib\SavorRedis::getInstance();
                $redis->select(5);
                $key = C('SAPP_FILE_FORSCREEN');
                $m_userfile = new \Common\Model\Smallapp\UserfileModel();
                foreach ($res_message['events'] as $v){
                    $task_id = $v['imm']['taskId'];
                    $log_content = date('Y-m-d H:i:s').'|task_id|'.$task_id.'|imm|'.json_encode($v['imm'])."\r\n";
                    @file_put_contents($log_file_name, $log_content, FILE_APPEND);
                    $id = 0;
                    $res_ufile = $m_userfile->getInfo(array('task_id'=>$task_id));
                    if(!empty($res_ufile)){
                        $md5_file = $res_ufile['md5_file'];
                        $id = $res_ufile['id'];
                    }else{
                        $task_key = $key.':'.$task_id;
                        $md5_file = $redis->get($task_key);
                    }
                    $percent = intval($v['imm']['percent']);
                    if($v['imm']['code']=='NoError' && $percent==100){
                        $aliyun = new AliyunImm();
                        $res = $aliyun->getImgResponse($task_id);
                        $response = print_r($res,true);
                        $log_content = date('Y-m-d H:i:s').'|task_id|'.$task_id.'|id|'.$id.'|response|'.$response."\r\n";
                        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

                        $result = $m_userfile->getCreateOfficeConversionResult($res);
                        $log_content = date('Y-m-d H:i:s').'|task_id|'.$task_id.'|id|'.$id.'|response_result|'.json_encode($result)."\r\n";
                        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
                        if($result['status']==2){
                            if(!empty($md5_file)){
                                $cache_key = $key.':'.$md5_file;
                                $redis->set($cache_key,json_encode($result['imgs']));
                            }
                        }
                        $file_conversion_status = 2;
                    }else{
                        $file_conversion_status = 3;
                    }
                    if($id){
                        $data = array('file_conversion_status'=>$file_conversion_status,'end_time'=>date('Y-m-d H:i:s'));
                        $m_userfile->updateData(array('id'=>$id),$data);
                    }

                }
            }
        }
    }


}
