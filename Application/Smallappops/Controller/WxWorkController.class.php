<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\WxWork;
//use \Common\lib\WxMesage\WXBizMsgCrypt;
class WxWorkController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'createChat':
                $this->is_verify = 0;
                
                break;
            case 'sendChatMsg':
                $this->is_verify = 0;
                
                break;
            case 'sendWebhook':
                $this->is_verify = 0;
                break;   
        }
        parent::_init_();
    }
    public function createChat(){
        $m_wx_work = new WxWork();
        
        $token = $m_wx_work->getWxAccessToken();
        
        $data = [];
        $data['name'] = 'test企微群聊';
        $data['owner'] = 'jetz';
        $data['userlist'] = array('jetz','MoNiYuXin');
        $data['chatid']   = 'test1';
        $data = json_encode($data);
        //echo $data;exit;
        
        $ret = $m_wx_work->createChat($token,$data);
        print_r($ret);exit;
    }
    public function sendChatMsg(){
        
        $m_wx_work = new WxWork();
        $token = $m_wx_work->getWxAccessToken();
        
        $data = [];
        $data['chatid'] = 'test1';
        $data['msgtype']= 'text';
        $data['text'] = array('content'=>'您的快递已放到前台1');
        $data['safe'] = 0;
        $data = json_encode($data);
        //echo $data;exit;
        $ret = $m_wx_work->sendChatMsg($token, $data);
        print_r($ret);
    }
    public function sendWebhook(){
        $m_wx_work = new WxWork();
        $data = [];
        //$data['chatid'] = 'test1';
        $data['msgtype']= 'text';
        $data['text'] = array('content'=>'您好，瓶盖已回收，积分即将进入您的账户,请注意查收');
        $data = json_encode($data);
        //echo $data;exit;
        $ret = $m_wx_work->sendWebhook($data);
        print_r($ret);
    }
}