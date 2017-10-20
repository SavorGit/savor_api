<?php
namespace Dailyknowledge\Controller;
use Think\Controller;
use \Common\Controller\BaseController;
class ConfigController extends BaseController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch (ACTION_NAME){
            case 'getdailyconfig':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }


    public function getdailyconfig(){
        $m_sys_config = new \Common\Model\SysConfigModel();
        //$volume_info = $m_sys_config->getOne('system_default_volume');
        //缓存设置
        $switch_cache_info = $m_sys_config->getOne('daily_cache_config');
        $data['state'] = empty($switch_cache_info['status'])?0:
            $switch_cache_info['status'];
        $this->to_back($data);
    }

}