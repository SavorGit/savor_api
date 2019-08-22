<?php
namespace Smalldinnerapp\Controller;
use \Common\Controller\CommonController;
class SmsController extends CommonController{
    private  $vcode_valid_time;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'sendverifyCode':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'invite_code'=>1001);
                break;
        }
        parent::_init_();
        $this->vcode_valid_time =  600;
    }

    /**
     * @desc 发送手机验证码
     */
    public function sendverifyCode(){
        $mobile = $this->params['mobile'];
        $invite_code = $this->params['invite_code'];
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(92001);
        }
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array('a.code'=>$invite_code,'a.flag'=>0);
        $invite_code_info = $m_hotel_invite_code->getInfo('a.id invite_id,a.is_import_customer,a.code,b.id hotel_id,b.name hotel_name,c.is_open_customer', $where);
        if(empty($invite_code_info)) {//输入的邀请码不正确
            $this->to_back(92002);
        }
        $code_array = array('1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);
        //发送短信
        $info = array('tel'=>$mobile);
        $param = $verify_code;
        $ret = $this->sendToUcPas($info, $param);
        if($ret){
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = 'smallappdinner_vcode_'.$mobile;
            $redis->set($cache_key, $verify_code,$this->vcode_valid_time);
            $this->to_back(10000);
        }else {
            $this->to_back(92005);
        }
    }

    private function sendToUcPas($info,$param,$type=1){
        $to = $info['tel'];
        $bool = true;
        $ucconfig = C('SMS_CONFIG');
        $options['accountsid'] = $ucconfig['accountsid'];
        $options['token'] = $ucconfig['token'];
        if($type==1){
            $templateId = $ucconfig['dinner_login_templateid'];
        }
        $ucpass= new \Common\Lib\Ucpaas($options);
        $appId = $ucconfig['appid'];
        $sjson = $ucpass->templateSMS($appId,$to,$templateId,$param);
        $sjson = json_decode($sjson,true);
        $code = $sjson['resp']['respCode'];

        $data = array();
        $data['type'] = 5;
        $data['status'] = 1;
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['url'] = $param;
        $data['tel'] = $to;
        $data['resp_code'] = $code;
        $data['msg_type'] = 2;
        $m_account_sms_log =  new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        if($code === '000000') {
            return true;
        }else{
            return false;
        }
    }

}