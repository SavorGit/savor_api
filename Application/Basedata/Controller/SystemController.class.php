<?php
/**
 * @desc 提供小平台城市接口
 */
namespace BaseData\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class SystemController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'systemTime':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    public function systemTime(){
        $data['sys_time'] = time();
        $this->to_back($data); 
    }
}