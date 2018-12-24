<?php
namespace BaseData\Controller;
use \Common\Controller\CommonController as CommonController;
class SmsController extends CommonController{

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'sendSms':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }

    public function sendSms(){
        $tel = I('tel',0,'intval');
        $templateId = I('templateid',0,'intval');

        if(empty($tel) || empty($templateId)){
            $this->to_back(1001);
        }
        $param = null;

        $ucconfig = C('SMS_CONFIG');
        $options = array('accountsid'=>$ucconfig['accountsid'],'token'=>$ucconfig['token']);
        $ucpass= new \Common\Lib\Ucpaas($options);
        $appId = $ucconfig['appid'];
        $res_json = $ucpass->templateSMS($appId,$tel,$templateId,$param);
        $res_data = json_decode($res_json,true);
        if(is_array($res_data) && $res_data['resp']['respCode']=== '000000') {
            $data = array();
            $data['type'] = 7;//检测netty
            $data['status'] = 1;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            $data['url'] = '';
            $data['tel'] = $tel;
            $data['resp_code'] = $res_data['resp']['respCode'];
            $data['msg_type'] = 3;//通知类型
            $m_account_sms_log =  new \Common\Model\AccountMsgLogModel();
            $m_account_sms_log->addData($data);
            $res = array('message'=>'发送短信成功');
            $this->to_back($res);
        }else{
            $this->to_back(21001);
        }

    }
}