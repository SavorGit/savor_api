<?php
namespace Dailyknowledge\Controller;
use Think\Controller;
use \Common\Controller\BaseController;
class LoginController extends BaseController{
    /**
     * @desc 构造函数
     */
    public $vcode_valid_time = 600;

    function _init_(){
        switch (ACTION_NAME){
            case 'weixinLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'mobileLogin':
                $this->is_verify = 0;
                $this->valid_fields = array('tel'=>1001);
                break;
            case 'getverifyCode':
                $this->is_verify = 1;
                $this->valid_fields = array('tel'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getSaveInfo(){
        $traceinfo = $this->traceinfo;
        $now = date("Y-m-d H:i:s");
        $save['device_id'] = $traceinfo['deviceid'];
        $save['mtype'] = $traceinfo['clientname'];
        $save['celltype'] = $traceinfo['model'];
        $save['tel'] = empty($this->params['tel'])?'':$this->params['tel'];
        $save['ptype'] = empty($this->params['ptype'])?0:$this->params['ptype'];
        $save['update_time'] = $now;
        $save['openid'] = empty($this->params['openid'])?'':$this->params['openid'];
        return $save;
    }

    /**
     * @desc 微信登录
     */
    public function weixinLogin(){
        $save = array();
        $save = $this->getSaveInfo();
        $map['openid'] = $this->params['openid'];
        $map['state'] = 1;
        if(empty($save['ptype'])) {
            $this->to_back('40006');
        }
        $dailyUserModel = new \Common\Model\DailyUserModel();
        $user_arr = $dailyUserModel->getOne($map);
        if($user_arr) {
            $us['id'] = $user_arr['id'];
            $rs = $dailyUserModel->saveData($save, $us);
            if($rs){
                $this->to_back(10000);
            } else {
                $this->to_back('40005');
            }

        } else {
            $save['create_time'] = $save['update_time'];
            $ins_id = $dailyUserModel->addData($save);
            if ($ins_id) {
                $this->to_back(10000);
            } else {
                $this->to_back('40005');
            }
        }
    }
    /**
     * @desc 手机登录
     */
    public function mobileLogin(){
        $save = array();
        $save = $this->getSaveInfo();
        $verify_code = $this->params['verifycode'];
        if(!preg_match('/^1[34578]\d{9}$/', $save['tel'])){
            $this->to_back('40008');
        }
        if(empty($save['ptype'])) {
            $this->to_back('40006');
        }
        //判断验证码
        if(empty($verify_code)){
            $this->to_back('40009');
        }
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'daily_vcode_'.$save['tel'];
        $cache_verify_code = $redis->get($cache_key);
        if($verify_code != $cache_verify_code){
            $this->to_back('40010');
        }
        //判断手机号
        $map['tel'] = $save['tel'];
        $map['state'] = 1;
        $dailyUserModel = new \Common\Model\DailyUserModel();
        $field = 'id, openid, tel';
        $daily_tel_arr = $dailyUserModel->getWhere($map, $field);
        if( empty($save['openid']) ) {
            if (empty ($daily_tel_arr)) {
                $save['create_time'] = $save['update_time'];
                $ins_id = $dailyUserModel->addData($save);
                if ($ins_id) {
                    $this->to_back(10000);
                } else {
                    $this->to_back('40005');
                }
            } else {
                //直接返回成功
                $this->to_back(10000);
            }
        } else {
            if (empty ($daily_tel_arr)) {
                $save['create_time'] = $save['update_time'];
                $ins_id = $dailyUserModel->addData($save);
                if ($ins_id) {
                    $this->to_back(10000);
                } else {
                    $this->to_back('40005');
                }
            } else {
                $saveid = 0;
                foreach ( $daily_tel_arr as $k=>$v ) {
                    if( $v['openid'] == $save['openid']) {
                        $saveid = $v['id'];
                        break;
                    }
                }
                if($saveid == 0) {
                    //添加
                    $save['create_time'] = $save['update_time'];
                    $ins_id = $dailyUserModel->addData($save);
                    if ($ins_id) {
                        $this->to_back(10000);
                    } else {
                        $this->to_back('40005');
                    }
                } else {
                    $us = array();
                    $us['id'] = $saveid;
                    $rs = $dailyUserModel->saveData($save, $us);
                    if($rs){
                        $this->to_back(10000);
                    } else {
                        $this->to_back('40005');
                    }
                }

            }
        }

    }

    public function getverifyCode(){
        $save = array();
        $save = $this->getSaveInfo();
        $mobile = $save['tel'];
        if(!preg_match('/^1[34578]\d{9}$/', $mobile)){
            $this->to_back('40008');
        }
        $m_account_sms_log =  new \Common\Model\AccountMsgLogModel();
        $gztime = date('Y-m-d H:i:s',strtotime('-1 Minute'));
        $where = array();
        $where['status'] =1;
        $where['type'] = 4;
        $where['msg_type'] =2;
        $where['tel'] = $mobile;
        $where['create_time'] = array('gt',$gztime);
        $isSend = $m_account_sms_log->getOne($where);
        if(!empty($isSend)){
            $this->to_back('40011');
        }
        $code_array = array('0','1','2','3','4','5','6','7','8','9');
        $verify_code = array_rand($code_array,4);
        $verify_code = implode('', $verify_code);
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = 'daily_vcode_'.$mobile;
        $redis->set($cache_key, $verify_code,$this->vcode_valid_time);
        //发送短信
        $info['tel'] = $mobile;
        $param = $verify_code.','.$this->vcode_valid_time/60;
        $ret = $this->sendToUcPa($info, $param);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(40012);
        }

    }


    private function sendToUcPa($info,$param,$type=1){
        $to = $info['tel'];
        $bool = true;
        $ucconfig = C('SMS_CONFIG');
        $options['accountsid'] = $ucconfig['accountsid'];
        $options['token'] = $ucconfig['token'];
        if($type==1){
            $templateId = $ucconfig['daily_login_templateid'];
        }

        $ucpass= new \Common\Lib\Ucpaas($options);
        $appId = $ucconfig['appid'];
        $sjson = $ucpass->templateSMS($appId,$to,$templateId,$param);
        $sjson = json_decode($sjson,true);
        $code = $sjson['resp']['respCode'];
        if($code === '000000') {
            $data = array();
            $data['type'] = 4;
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