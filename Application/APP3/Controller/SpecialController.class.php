<?php
/**
 * @desc 专题接口
 * @author zhang.yingtao
 * @since  2017-07-07
 */
namespace APP3\Controller;
use Think\Controller;
use Common\Controller\CommonController;
class SpecialController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getSpecialName':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取专题名称
     */
    public function getSpecialName(){
        $m_sys_config = new \Common\Model\SysConfigModel();
        $where = "'system_special_title'";
        $info = $m_sys_config->getInfo($where);
        $data =  array();
        if(!empty($info)){
            $data['specialName'] = $info[0]['config_value'];
        }else {
            $data['specialName'] = '';
        }
       
        $this->to_back($data);
    }
}