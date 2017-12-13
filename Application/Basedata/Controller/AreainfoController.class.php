<?php
/**
 * @desc 提供小平台城市接口
 */
namespace BaseData\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class AreainfoController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getIp':
                $this->is_verify = 0;
                break;
            case 'getCityByAreano':
                $this->is_verify = 1;
                $this->valid_fields = array('area_no'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 根据区号获取城市信息
     */
    public function getCityByAreano(){
        $area_no = $this->params['area_no'];
        $m_area_info = new \Common\Model\AreaModel();
        $where = array();
        $where['area_no'] = $area_no;
        $where['is_in_hotel'] = 1;
        
        $data = $m_area_info->getWhere('id', $where);
        
        $this->to_back($data);
    }
}