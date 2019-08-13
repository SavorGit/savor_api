<?php
namespace Box\Controller;
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
            case 'addPlaylog':
                $this->is_verify =1;
                $this->valid_fields = array('vid'=>1001,'type'=>1001);
                break;
        }
        parent::_init_(); 
    }
    public function getConfig(){
        $box_min_version_code = C('SAPP_FORSCREEN_VERSION_CODE');
        $box_mac = $this->params['box_mac'];
        $versionCode = intval($this->params['versionCode']);
        $data = array();
        
        if(empty($versionCode) || $versionCode <$box_min_version_code){  //上线前替换1234
            $data['is_sapp_forscreen'] = 0;
            $data['is_simple_sapp_forscreen'] = 0;
            $data['is_open_interactscreenad'] = 0;
            $this->to_back($data);
        }else if($versionCode>=$box_min_version_code){//上线前替换1234
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
            $sys_info = $m_sys_config->getAllconfig();
            
            $data['is_sapp_forscreen']         = intval($box_info['is_sapp_forscreen']);
            $data['is_simple_sapp_forscreen']  = intval($box_info['is_open_simple']);
            $data['is_open_interactscreenad']  = intval($box_info['is_open_interactscreenad']);
            $data['system_sapp_forscreen_nums']= intval($sys_info['system_sapp_forscreen_nums']);
            $data['qrcode_type']               = intval($box_info['qrcode_type']);
            $data['is_open_signin']            = intval($box_info['is_open_signin']);
            $data['activity_adv_playtype']     = intval($sys_info['activity_adv_playtype']);;//1替换 2队列

            $this->to_back($data);
        }
    }

    public function addPlaylog(){
        $vid = $this->params['vid'];
        $type = $this->params['type'];

        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $res_play = $m_play_log->getOne('id,create_time',array('res_id'=>$vid,'type'=>4),'id desc');
        if(!empty($res_play)){
            $m_play_log->where(array('id'=>$res_play['id']))->setInc('nums',1);
            $m_config = new \Common\Model\SysConfigModel();
            $res_config = $m_config->getAllconfig();
            $play_time = intval($res_config['content_play_time'])*3600;
            $last_time = strtotime($res_play['create_time'])+$play_time;
            $now_time = time();
            if($now_time>$last_time){
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(5);
                $allkeys  = $redis->keys('smallapp:selectcontent:program:*');
                foreach ($allkeys as $program_key){
                    $period = getMillisecond();
                    $redis->set($program_key,$period);
                }
            }
        }
        $this->to_back(array());
    }

}