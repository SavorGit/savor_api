<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class AreaController extends CommonController{

    public $parent_area_ids = array('1'=>35,'9'=>107);

    function _init_() {
        switch(ACTION_NAME) {
            case 'getAreaid':
                $this->is_verify = 1;
                $this->valid_fields = array('latitude'=>1001,'longitude'=>1001);
                break;
            case 'getCityAreaCircleList':
                $this->is_verify = 0;
                break;
            case 'getProvinceList':
                $this->is_verify = 0;
                $this->valid_fields = array('province_id'=>1002);
                break;
            case 'getCityList':
                $this->is_verify = 0;
                $this->valid_fields = array('province_id'=>1002,'city_id'=>1002);
                break;
            case 'getAreaList':
                $this->is_verify = 0;
                $this->valid_fields = array('city_id'=>1002,'area_id'=>1002);
                break;

        }
        parent::_init_();
    }

    public function getAreaid(){
        $latitude = $this->params['latitude'];  //纬度
        $longitude= $this->params['longitude']; //经度

        $ret = getgeoByloa($latitude,$longitude);
        $m_area = new \Common\Model\AreaModel();
        if(empty($ret)){
            $area_id = 1;
            $region_name = '北京';
        }else {
            $city_name = $ret['addressComponent']['city'];
            $fields = "id,region_name";
            $where['region_name'] = $city_name;
            $where['is_in_hotel'] = 1;
            $where['is_valid']    = 1;
            $city_info = $m_area->field($fields)->where($where)->order('id asc')->find();
            if(empty($city_info)){
                $area_id = 1;
                $region_name = '北京';
            }else {
                $area_id = $city_info['id'];
                $region_name = str_replace('市', '', $city_info['region_name']);
            }

        }
        $fields = "id,region_name";
        $where['is_in_hotel'] = 1;
        $where['is_valid']    = 1;
        $city_list = $m_area->field($fields)->where($where)->order('id asc')->select();
        $cityindex = 0;
        foreach($city_list as $key=>$v){
            if($v['id'] == $area_id){
                $cityindex = $key;
                break;
            }
        }
        $data['area_id']     = $area_id;
        $data['region_name'] = $region_name;
        $data['cityindex']   = $cityindex;
        $this->to_back($data);
    }

    public function getCityAreaCircleList(){
        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name as name";
        $where = array('is_in_hotel'=>1,'is_valid'=>1);
        $city_list = $m_area->field($fields)->where($where)->order('id asc')->select();
        $parent_area_ids = $this->parent_area_ids;

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

    public function getProvinceList(){
        $province_id = intval($this->params['province_id']);
        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name as name";
        $where = array('parent_id'=>0,'is_valid'=>1);
        $res_provinces = $m_area->field($fields)->where($where)->order('id asc')->select();
        if($province_id){
            $p_is_select = 0;
        }else{
            $p_is_select = 1;
        }
        $res_data = array(array('id'=>0,'name'=>'省份','is_select'=>$p_is_select));
        foreach ($res_provinces as $k=>$v){
            $is_select = 0;
            if($v['id']==$province_id){
                $is_select = 1;
            }
            $res_data[]=array('id'=>$v['id'],'name'=>$v['name'],'is_select'=>$is_select);
        }
        $this->to_back($res_data);
    }

    public function getCityList(){
        $province_id = intval($this->params['province_id']);
        $city_id = intval($this->params['city_id']);

        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name as name";
        $where = array('parent_id'=>$province_id,'is_valid'=>1);
        $res_citys = $m_area->field($fields)->where($where)->order('id asc')->select();
        if($city_id){
            $p_is_select = 0;
        }else{
            $p_is_select = 1;
        }
        $res_data = array(array('id'=>0,'name'=>'城市','is_select'=>$p_is_select));
        foreach ($res_citys as $k=>$v){
            $is_select = 0;
            if($v['id']==$city_id){
                $is_select = 1;
            }
            $res_data[]=array('id'=>$v['id'],'name'=>$v['name'],'is_select'=>$is_select);
        }
        $this->to_back($res_data);
    }

    public function getAreaList(){
        $city_id = intval($this->params['city_id']);
        $area_id = intval($this->params['area_id']);

        if(isset($parent_area_ids[$city_id])){
            $parent_id = $parent_area_ids[$city_id];
        }else{
            $parent_id = $city_id;
        }
        $fields = 'id,region_name as name';
        $where = array('parent_id'=>$parent_id,'is_valid'=>1);
        $m_area = new \Common\Model\AreaModel();
        $res_area = $m_area->field($fields)->where($where)->order('id asc')->select();
        if($area_id){
            $p_is_select = 0;
        }else{
            $p_is_select = 1;
        }
        $res_data = array(array('id'=>0,'name'=>'区/县','is_select'=>$p_is_select));
        foreach ($res_area as $k=>$v){
            $is_select = 0;
            if($v['id']==$area_id){
                $is_select = 1;
            }
            $res_data[]=array('id'=>$v['id'],'name'=>$v['name'],'is_select'=>$is_select);
        }
        $this->to_back($res_data);
    }


}