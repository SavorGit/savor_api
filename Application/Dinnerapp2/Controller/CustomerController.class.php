<?php
/**
 * @desc 客户信息管理
 * @author baiyutao
 * @date  20171219
 */
namespace Dinnerapp2\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CustomerController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addCustom':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'invite_id'     =>1001,
                    'mobile'        =>1001,
                    'usermobile'    =>1001,
                    'name'          =>1001,
                );
                break;
            default:
                 break;
        }
        parent::_init_();
        $this->vcode_valid_time =  600;
    }

    public function addCustom() {
        $mobile = $this->params['mobile'];
        //验证手机格式
        if(!check_mobile($mobile)){
            $this->to_back(60002);
        }
        $invite_code = $this->params['invite_id'];
        if(!is_numeric($invite_code)) {
            $this->to_back(60100);
        }
        //判断用户名是否存在
        //invite_id  查出得数据为空 60018

        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
        $username    = $this->params['name'];
    }


}