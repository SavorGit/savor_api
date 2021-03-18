<?php
/**
 *OSS消息队列相关
 * 
 */
namespace H5\Controller;
use Think\Controller;


class AlimnsController extends Controller{

    public function receiveNetty(){
        $content = file_get_contents('php://input');

        $log_file_name = APP_PATH.'Runtime/Logs/'.'mns_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s')."|content|$content \r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

        if(!empty($content)) {
            $res = json_decode($content, true);
            if (!empty($res['Message'])) {
                $message = base64_decode($res['Message']);
                $res_message = json_decode($message,true);
                $req_id = $res_message['context']['requestId'];
                $type = strtolower($res_message['type']);

                $log_file_name = APP_PATH.'Runtime/Logs/'.'mns_'.date("Ymd").".log";
                $log_content = date('Y-m-d H:i:s')."req_id|$req_id|type|$type|message|$message \r\n";
                @file_put_contents($log_file_name, $log_content, FILE_APPEND);

                $all_types = array('nettyapiin','nettyapiout');
                if(in_array($type,$all_types)){
                    $redis = new \Common\Lib\SavorRedis();
                    $redis->select(5);
                    $cache_key = C('SAPP_FORSCREENTRACK').$req_id;

                    $netty_cache_key = C('SAPP_FORSCREENTRACK').$req_id.'netty_time';
                    if($type=='nettyapiin'){
                        $netty_name = 'netty_receive_time';
                    }else{
                        $netty_name = 'netty_pushbox_time';
                    }
                    $redis->hSet($netty_cache_key,$netty_name,$res_message['timestamp']);
                    $redis->expire($netty_cache_key,86400);

                    $redis->watch($cache_key);

                    $res_cache = $redis->get($cache_key);
                    if(!empty($res_cache)){
                        $data = json_decode($res_cache,true);
                    }else{
                        $data = array();
                    }
                    if($type=='nettyapiin'){
                        $data['netty_receive_time']=$res_message['timestamp'];
                    }else{
                        $data['netty_pushbox_time']=$res_message['timestamp'];
                        if(!empty($res_message['context']['nettyChaId'])){
                            $data['netty_pushbox_chaid']=$res_message['context']['nettyChaId'];
                        }
                    }
                    $redis->multi();

                    $redis->set($cache_key,json_encode($data),86400);

                    $redis->exec();

                    $message = json_encode($data);
                    $log_content = date('Y-m-d H:i:s')."req_id|$req_id|type|$type|redis|$message \r\n";
                    @file_put_contents($log_file_name, $log_content, FILE_APPEND);
                    /*
                    if($type=='nettyapiin'){
                        $params = array('netty_receive_time'=>$res_message['timestamp']);
                    }else{
                        $params = array('netty_pushbox_time'=>$res_message['timestamp']);
                    }
                    $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
                    $m_forscreen->recordTrackLog($req_id,$params);
                    */
                }
            }
        }
    }


}
