<?php
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class CityController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAreaList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
        
    }
    public function getAreaList(){
        $m_area = new \Common\Model\AreaModel();
        $list = $m_area->getHotelAreaList();
        foreach($list as $key=>$v){
            $list[$key]['region_name'] = str_replace('市', '', $v['region_name']);
        }
        array_unshift( $list,array('id'=>"0",'region_name'=>'全国'));
        $this->to_back($list);
    }

}