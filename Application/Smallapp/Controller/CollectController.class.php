<?php
namespace Smallapp\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class CollectController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recLogs':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'res_id'=>1001,'type'=>1001,'only_co'=>1000,'status'=>1000);
            break;
        }
        parent::_init_();
    }
    /**
     * 收藏、取消收藏
     */
    public function recLogs(){
        $openid = $this->params['openid'];
        $res_id  = $this->params['res_id'];
        $type    = $this->params['type'];
        $status  = $this->params['status'];
        $only_co = $this->params['only_co'] ? $this->params['only_co']:1;
        
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $data = array();
        $data['openid'] = $openid;
        $data['res_id']  = $res_id;
        $data['type']    = $type;
        $info = $m_collect->getOne('status', $data);
        if(!empty($info)){
            if($only_co==1){
                $this->to_back(90131);
            }else {
                $m_collect->updateInfo($data, array('status'=>$status));
                $map['res_id']  = $res_id;
                $map['status']  = 1;
                $nums = $m_collect->countNum($map);
                $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
                $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
                $nums +=$ret['nums'];
                $this->to_back(array('nums'=>$nums));
            }
            
        }else {
            $data['status']  = $status;
            
            $ret = $m_collect->addInfo($data,1);
            if($ret){
                $map['res_id']  = $res_id;
                $map['status']  = 1;
                $nums = $m_collect->countNum($map);
                
                $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
                $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
                $nums +=$ret['nums'];
                $this->to_back(array('nums'=>$nums));
            }else {
                $this->to_back(90106);
            }
        }
        
        
    }
}