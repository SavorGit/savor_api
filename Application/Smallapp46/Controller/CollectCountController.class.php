<?php
namespace Smallapp46\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;

class CollectCountController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recCount':
                $this->is_verify =1;
                $this->valid_fields = array('res_id'=>1001);
                break;
            
            
        }
        parent::_init_();
    }
    public function recCount(){
        $res_id = $this->params['res_id'];
        $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $where =array();
        $where['forscreen_id'] = $res_id;
        $nums = $m_public->countNum($where);
        if($nums){
            $where = array();
            $where['res_id'] = $res_id;
            
            $nums = $m_collect_count->countNum($where);
            if($nums){
                $m_collect_count->where($where)->setInc('nums',1);
            }else {
                $data = array();
                $data['res_id'] = $res_id;
                $data['type']   = 2;
                $data['nums']   = 1;
                $m_collect_count->addInfo($data);
            }
        }
        $this->to_back(10000);
        
    }
}