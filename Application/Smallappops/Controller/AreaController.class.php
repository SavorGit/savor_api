<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class AreaController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'getCityAreaCircleList':
                $this->is_verify = 0;
                break;

        }
        parent::_init_();
    }
    public function getCityAreaCircleList(){
        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name as name";
        $where = array('is_in_hotel'=>1,'is_valid'=>1);
        $city_list = $m_area->field($fields)->where($where)->order('id asc')->select();
        $parent_area_ids = array('1'=>35,'9'=>107);

        $m_business_circle = new \Common\Model\BusinessCircleModel();
        $data_list = array();
        foreach ($city_list as $k=>$v){
            if(isset($parent_area_ids[$v['id']])){
                $parent_id = $parent_area_ids[$v['id']];
            }else{
                $parent_id = $v['id'];
            }
            $fields = 'id,region_name as name';
            $where = array('parent_id'=>$parent_id,'is_valid'=>1);
            $area_list = $m_area->getWhere($fields, $where, 'id asc', '',2);
            foreach ($area_list as $ak=>$av){
                $awhere = array('area_id'=>$v['id'],'county_id'=>$av['id'],'status'=>1);
                $circle_list = $m_business_circle->getDataList('id,name',$awhere,'id asc');
                $area_list[$ak]['circle_list'] = $circle_list;
            }
            $data_list[]=array('id'=>$v['id'],'name'=>$v['name'],'area_list'=>$area_list);
        }
        $this->to_back($data_list);
    }
}