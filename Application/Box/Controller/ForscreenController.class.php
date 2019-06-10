<?php
namespace Box\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class ForscreenController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getConfig':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'versionCode'=>1000);
                break;
        }
        parent::_init_(); 
    }
    public function getConfig(){
        $box_min_version_code = C('SAPP_FORSCREEN_VERSION_CODE');
        $box_mac = $this->params['box_mac'];
        $versionCode = intval($this->params['versionCode']);
        $data = array();
        
        if(empty($versionCode)|| $versionCode <$box_min_version_code ){  //上线前替换1234
            $data['is_sapp_forscreen'] = 0;
            
            $data['is_simple_sapp_forscreen'] = 0;
            $data['is_open_interactscreenad'] = 0;
            
            $this->to_back($data);
        }else if($versionCode>=$box_min_version_code){                   //上线前替换1234
            $m_box = new \Common\Model\BoxModel();
            $where = array();
            $where['mac'] = $box_mac;
            $where['state'] = 1;
            $where['flag']  = 0 ;
            $box_info = $m_box->getOnerow($where);
            if(empty($box_info)){
                $this->to_back(70001);
            }
            $m_sys_config = new \Common\Model\SysConfigModel();
            $sys_info = $m_sys_config->getInfo("'system_sapp_forscreen_nums'");
            
            $data['is_sapp_forscreen']         = intval($box_info['is_sapp_forscreen']);
            $data['is_simple_sapp_forscreen']  = intval($box_info['is_open_simple']);
            $data['is_open_interactscreenad']  = intval($box_info['is_open_interactscreenad']);
            $data['system_sapp_forscreen_nums']= intval($sys_info[0]['config_value']);
            $data['qrcode_type']               = intval($box_info['qrcode_type']);
            $this->to_back($data);
        }
    }
}