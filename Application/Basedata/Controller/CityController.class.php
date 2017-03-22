<?php
namespace BaseData\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CityController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getCityList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * 获取全部城市列表
     */
    public function getCityList(){
        $m_region = new \Common\Model\Basedata\RegionModel();
        $res = $m_region->getAllRegion();
        $data = array();
        foreach ($res as $k => $v){
            $cityResp = new \CityObj();
            $cityResp->area_id = $v['area_id'];
            $cityResp->parent_id= $v['parent_id'];
            $cityResp->area_name = $v['area_name'];
            $cityResp->area_type = $v['area_type'];
            $cityResp->is_hotcity= $v['is_hotcity'];
            $cityResp->sort_order= $v['sort_order'];
            $cityResp->pinyin    = $v['pinyin'];
            $cityResp->first     = $v['first'];
           
            $data[] = $cityResp;
        }
        $this->to_back($data);
    }
}