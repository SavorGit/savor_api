<?php
namespace H5\Controller;
use Common\Lib\WechatMessage;
use Think\Controller;

class WechatController extends Controller {

    public function message(){
        $params = file_get_contents('php://input');
        $log_file_name = APP_PATH.'Runtime/Logs/'.'wxmessage_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s')."|params|$params \r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

        $wx_config = C('WX_MP_CONFIG');
        $options = array('appid'=>$wx_config['appid'],'appsecret'=>$wx_config['appsecret'],'token'=>'2iRIcv5fJyHGonO3eNn4UNID0lpZAspo');
        $weObj = new WechatMessage($options);
        $weObj->valid();
        $revObj = $weObj->getRev();
        $type = $revObj->getRevType();

        $content = '欢迎关注';
        $smallapp_config = C('SMALLAPP_CONFIG');
        $appid = $smallapp_config['appid'];
        switch($type) {
            case WechatMessage::MSGTYPE_TEXT://回复或关键词
                $page_url = "pages/index/index";

                $content = "<a href='http://www.qq.com' data-miniprogram-appid='{$appid}' data-miniprogram-path='{$page_url}'>欢迎使用热点投屏，请点击此处进行投屏</a>";
                $weObj->text($content)->reply();
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
                    if(!empty($eventinfo['key'])){
                        if($eventinfo['event'] == WechatMessage::EVENT_SUBSCRIBE){
                            $qrcode = str_replace('qrscene_','',$eventinfo['key']);//qrscene_mxxonmaEg
                            $subscribe_time = time();
                        }else{
                            $qrcode = $eventinfo['key'];
                            $subscribe_time = 0;
                        }
                        $wx_mpopenid = $revObj->getRevFrom();
                        $page_url = "pages/forscreen/forscreen?official={$qrcode}&wxmpopenid={$wx_mpopenid}&subscribe_time={$subscribe_time}";
                        $thumb_media_id = 's_KxN5aPbIS1vmNmCnJCpUwtzlzS0vOaibLW9Qs1O-w';
                        $data = array(
                            'touser'=>$wx_mpopenid,
                            'msgtype'=>"miniprogrampage",
                            'miniprogrampage'=>array(
                                'title'=>'欢迎使用热点投屏',
                                'appid'=>$appid,
                                'pagepath'=>$page_url,
                                'thumb_media_id'=>$thumb_media_id,
                            ),
                        );
                        $wechat = new \Common\Lib\Wechat();
                        $res = $wechat->customsend(json_encode($data,JSON_UNESCAPED_UNICODE));
                        if(!empty($res)){
                            $res_info = json_decode($res,true);
                            if($res_info['errcode']!=0){
                                $res = $wechat->customsend(json_encode($data,JSON_UNESCAPED_UNICODE));
                            }
                        }
                        $log_content = date('Y-m-d H:i:s')."|openid|$wx_mpopenid|push_result|$res \r\n";
                        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
                    }
                }elseif($eventinfo['event'] == WechatMessage::EVENT_UNSUBSCRIBE){
                    $wx_mpopenid = $revObj->getRevFrom();
                    $m_user = new \Common\Model\Smallapp\UserModel();
                    $where = array('wx_mpopenid'=>$wx_mpopenid);
                    $userinfo = $m_user->getOne('id,openid,wx_mpopenid', $where);
                    if(!empty($userinfo)){
                        $data = array('wx_mpopenid'=>'','is_subscribe'=>0,'subscribe_time'=>'0000-00-00 00:00:00');
                        $m_user->updateInfo(array('id'=>$userinfo['id']),$data);
                    }
                }
                break;
        }
        $rev_data = $revObj->getRevData();
        if(!empty($rev_data)){
            $data = array('openid'=>$rev_data['FromUserName'],'msgtype'=>$rev_data['MsgType'],'event'=>$rev_data['Event'],
                'event_content'=>$rev_data['EventKey']
            );
            if(!empty($rev_data['CreateTime'])){
                $data['create_time'] = date('Y-m-d H:i:s',$rev_data['CreateTime']);
            }
            $m_official = new \Common\Model\OfficialAccountsLogModel();
            $m_official->add($data);
        }
        echo 'success';
    }


}