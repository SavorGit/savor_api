<?php
namespace Smallapp\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class ShareController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recLogs':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1001,'type'=>1001);
            break;
        }
        parent::_init_();
    }
    /**
     * 分享记录日志
     */
    public function recLogs(){
        $openid = $this->params['openid'];
        $res_id  = $this->params['res_id'];
        $type    = $this->params['type'];
        $status  = 1;
        $data = array();
        $data['openid'] = $openid;
        $data['res_id']  = $res_id;
        $data['type']    = $type;
        $data['status']  = $status;
        $m_share = new \Common\Model\Smallapp\ShareModel();
        $ret = $m_share->addInfo($data,1);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(90106);
        }
    }
}