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
                $all_types = array('nettyapiin','nettyapiout');
                if(in_array($type,$all_types)){
                    if($type=='nettyapiin'){
                        $params = array('netty_receive_phptime'=>$res_message['timestamp']);
                    }else{
                        $params = array('netty_pushbox_time'=>$res_message['timestamp']);
                    }
                    $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
                    $m_forscreen->recordTrackLog($req_id,$params);
                }
            }
        }
    }


}
