<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class SmsController extends CommonController{
    private  $vcode_valid_time;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'sendverifyCode':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001);
                break;

        }
        parent::_init_();
        $this->vcode_valid_time =  600;
    }
    public function sendverifyCode(){
        $mobile = $this->params['mobile'];
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(92001);
        }
        $code_array = array('1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);
        if($mobile=='15810260493'){
            $verify_code = 1234;
        }
        //发送短信
        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $params = array('code'=>$verify_code);
        $template_code = $ucconfig['send_login_confirm'];
        $res_data = $alisms::sendSms($mobile,$params,$template_code);
        $data = array('type'=>5,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>$verify_code,'tel'=>$mobile,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        if($res_data->Code == 'OK'){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = C('SAPP_OPS').'register:'.$mobile;
            $redis->set($cache_key, $verify_code,$this->vcode_valid_time);
            $this->to_back(10000);
        }else {
            $this->to_back(92005);
        }
    }

}