<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class SmsController extends CommonController{
    private  $vcode_valid_time;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'sendbindmobileverifyCode':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001);
                break;
            case 'sendRegisterCode':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'type'=>1002);
                break;
            case 'sendverifyCode':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'invite_code'=>1001);
                break;

        }
        parent::_init_();
        $this->vcode_valid_time =  600;
    }
    public function sendverifyCode(){
        $mobile = $this->params['mobile'];
        $invite_code = $this->params['invite_code'];
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(92001);
        }
        //$m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('code'=>$invite_code,'mobile'=>$mobile,'status'=>1);
        $invite_code_info = $m_merchant->field('id')->where($where)->find();

        //$invite_code_info = $m_hotel_invite_code->getInfo('a.id invite_id,a.is_import_customer,a.code,b.id hotel_id,b.name hotel_name,c.is_open_customer', $where);
        if(empty($invite_code_info)) {//输入的邀请码不正确
            $this->to_back(92002);
        }
        $code_array = array('1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);
        //发送短信
        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $params = array('code'=>$verify_code);
        $template_code = $ucconfig['send_login_merchant'];
        $res_data = $alisms::sendSms($mobile,$params,$template_code);
        $data = array('type'=>5,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>$verify_code,'tel'=>$mobile,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        if($res_data->Code == 'OK'){
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = 'smallappdinner_vcode_'.$mobile;
            $redis->set($cache_key, $verify_code,$this->vcode_valid_time);
            $this->to_back(10000);
        }else {
            $this->to_back(92005);
        }
    }
    function sendbindmobileverifyCode(){
        $mobile = $this->params['mobile'];
        $openid = $this->params['openid'];
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $code_array = array('1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);
        //发送短信
        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $params = array('code'=>$verify_code);
        $template_code = $ucconfig['send_login_merchant'];
        $res_data = $alisms::sendSms($mobile,$params,$template_code);
        $data = array('type'=>5,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>$verify_code,'tel'=>$mobile,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);

        if($res_data->Code == 'OK'){
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = 'smallappsale_bindmobile_vcode_'.$mobile;
            $redis->set($cache_key, $verify_code,$this->vcode_valid_time);
            $this->to_back(10000);
        }else {
            $this->to_back(93011);
        }
    }

    public function sendRegisterCode(){
        $mobile = $this->params['mobile'];
        $type = isset($this->params['type'])?$this->params['type']:1;
        if(!check_mobile($mobile)){
            $this->to_back(92001);
        }

        $code_array = array('1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $sale_key = C('SAPP_SALE');
        if($type==1){
            $register_key = $sale_key.'register:'.$mobile;
        }else{
            $register_key = $sale_key.'purchaseregister:'.$mobile;
        }
        $register_key = $sale_key.'register:'.$mobile;

        $repeat_key = $sale_key.'repeatsend:'.$mobile;
        $res_repeat = $redis->get($repeat_key);
        if(!empty($res_repeat)){
            $this->to_back(93039);
        }
        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $params = array('code'=>$verify_code);
        $template_code = $ucconfig['send_register_merchant'];
        $res_data = $alisms::sendSms($mobile,$params,$template_code);
        $data = array('type'=>10,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>'','tel'=>$mobile,'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_account_sms_log->addData($data);
        if($res_data->Code == 'OK'){
            $redis->set($repeat_key,$mobile,60);
            $redis->set($register_key,$verify_code,1800);
            $this->to_back(10000);
        }else{
            $this->to_back(93011);
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