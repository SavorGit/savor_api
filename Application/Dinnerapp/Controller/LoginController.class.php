<?php
/**
 * @desc 餐厅端1.2-用户登录
 * @author zhang.yingtao
 * @since  20171201
 */
namespace Dinnerapp\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class LoginController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'doLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('mobile'=>1001,'invite_code'=>1001,'verify_code'=>1000);
                break;
        }
        parent::_init_();
    }
    public function doLogin(){
        $mobile = $this->params['mobile'];           //手机号
        $verify_code = $this->params['verify_code']; //手机验证码
        $invite_code = $this->params['invite_code']; //邀请码
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(60002);
        }
        if($verify_code){//如果是第一次登录
            $redis  =  \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = 'dinner_vcode_'.$mobile;
            $cache_verify_code = $redis->get($cache_key);
            
            if($verify_code != $cache_verify_code){
                $this->to_back('60004');
            }
        }
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array();
        
        $where['a.code'] = $invite_code;
        
            //$where['a.state'] = 0;
        
        
        $where['a.flag'] = 0;
        $info = $m_hotel_invite_code->getInfo('a.state,b.id hotel_id,b.name hotel_name', $where);
        if(empty($info)){
            $this->to_back(60005);
        }
        if($info['state']==0){
            $where = array();
            $where['code'] = $invite_code;
            $where['flag'] = 0;
            $data = array();
            $data['state'] = 1;
            $data['bind_time'] = date('Y-m-d H:i:s');
            $data ['bind_mobile'] = $mobile;
            $ret = $m_hotel_invite_code->saveInfo($where,$data);
            if($ret){
                if($verify_code){
                    $redis->remove($cache_key);
                }
                unset($info['state']);
                $this->to_back($info);
            }else {
                $this->to_back(60006);
            }
        }else {
            if($verify_code){
                $redis->remove($cache_key);
            }
            unset($info['state']);
            $this->to_back($info);
        }
        
        
        
        
    }
}