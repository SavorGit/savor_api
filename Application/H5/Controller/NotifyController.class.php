<?php
/**
 *OSS消息队列相关
 * 
 */
namespace H5\Controller;
use Think\Controller;


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
                $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
                $m_forscreen->recordTrackLog($req_id,$params);
            }
        }
    }


}
