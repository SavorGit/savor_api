<?php
/**
 * @desc 餐厅端1.2-发送短信短信
 * @author zhang.yingtao
 * @since  20171201
 */
namespace Dinnerapp\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class SmsController extends BaseController{
    private  $vcode_valid_time ;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getverifyCode':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001);
                break;
        }
        parent::_init_();
        $this->vcode_valid_time =  600;
    }
    /**
     * @desc 发送手机验证码
     */
    public function getverifyCode(){
        $mobile = $this->params['mobile'];
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(60002);
        }
        $m_account_sms_log =  new \Common\Model\AccountMsgLogModel();
        $gztime = date('Y-m-d H:i:s',strtotime('-1 Minute'));
        $where = array();
        $where['status'] =1;
        $where['type'] = 5;
        $where['msg_type'] =2;
        $where['tel'] = $mobile;
        $where['create_time'] = array('gt',$gztime);
        $isSend = $m_account_sms_log->getOne($where);
        if(!empty($isSend)){
            $this->to_back('60003');
        }
        $code_array = array('0','1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'dinner_vcode_'.$mobile;
        $redis->set($cache_key, $verify_code,$this->vcode_valid_time);
        //发送短信
        $info['tel'] = $mobile;
        $param = $verify_code;
        $ret = $this->sendToUcPas($info, $param);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(40012);
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
        if($code === '000000') {
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
            return true;
        }else{
            return false;
        }
    }
}