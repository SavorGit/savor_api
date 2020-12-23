<?php
namespace H5\Controller;
use Common\Lib\WechatMessage;
use Think\Controller;

class WechatController extends Controller {

    public function message(){
        $params = file_get_contents('php://input');
        $log_file_name = APP_PATH.'Runtime/Logs/'.'wxmessage_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s')."|content|$params \r\n";

        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
        $wx_config = C('WX_MP_CONFIG');
        $options = array('appid'=>$wx_config['appid'],'appsecret'=>$wx_config['appsecret'],'token'=>'2iRIcv5fJyHGonO3eNn4UNID0lpZAspo');

        $weObj = new WechatMessage($options);
        $weObj->valid();
        $revObj = $weObj->getRev();
        $type = $revObj->getRevType();
        $content = '欢迎关注';
        switch($type) {
            case WechatMessage::MSGTYPE_TEXT://回复或关键词
                $content = '欢迎关注';
                /*
                $content_name = $revObj->getRevContent();
                $where = array('replyway'=>1);
                $where['keywords'] = array('like',"%$content_name,%");
                $res_message = $m_wx->getInfo($where);
                if(empty($res_message)){
                    $where = array('replyway'=>2);
                    $res_message = $m_wx->getInfo($where);
                }
                if(!empty($res_message)){
                    $content = $res_message['content'];
                }
                */
                break;
            case WechatMessage::MSGTYPE_EVENT:
                $eventinfo = $revObj->getRevEvent();
                if($eventinfo['event'] == WechatMessage::EVENT_SUBSCRIBE || $eventinfo['event'] == WechatMessage::EVENT_SCAN) {//关注
                }
                if(!empty($eventinfo['key'])){
                    $qrcode = $eventinfo['key'];
                    $wx_mpopenid = $revObj->getRevFrom();
                    $page_url = "pages/forscreen/forscreen?s={$qrcode}&wxmpopenid={$wx_mpopenid}";
                    $content = "请点击此处<a href='http://www.qq.com' data-miniprogram-appid='wxfdf0346934bb672f' data-miniprogram-path='{$page_url}'>跳转热点投屏小程序</a>";
                }
                break;

        }
        $weObj->text($content)->reply();

    }


}