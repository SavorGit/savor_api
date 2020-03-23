<?php
/**
 *达达快递订单相关
 * 
 */
namespace H5\Controller;
use Think\Controller;


class DadaController extends Controller{

    public function orderNotify(){
        $content = file_get_contents('php://input');

        $log_file_name = APP_PATH.'Runtime/Logs/'.'dadaorder_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s')."|content|$content \r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

        if(!empty($content)) {
            $res = json_decode($content, true);
        }
        echo 'ok';
    }


}
