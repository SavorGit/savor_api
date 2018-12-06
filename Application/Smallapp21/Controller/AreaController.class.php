<?php
namespace Smallapp21\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class AreaController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAreaid':
                $this->is_verify = 1;
                $this->valid_fields = array('latitude'=>1001,'longitude'=>1001);
                break;
            case 'getAreaList':
                $this->is_verify = 0;
                break;
            case 'getSecArea':
                $this->is_verify = 1;
                $this->valid_fields = array('area_id'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 根据经纬度获取用户所在城市
     */
    public function getAreaid(){
        $latitude = $this->params['latitude'];  //纬度
        $longitude= $this->params['longitude']; //经度
        
        $ret = getgeoByloa($latitude,$longitude);
        
        if(empty($ret)){
            $area_id = 1;
            $region_name = '北京';
        }else {
            $city_name = $ret['addressComponent']['city'];
            $m_area = new \Common\Model\AreaModel();
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
    /**
     * @desc 获取城市列表
     */
    public function getAreaList(){
        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name";
        $where['is_in_hotel'] = 1;
        $where['is_valid']    = 1;
        $city_list = $m_area->field($fields)->where($where)->order('id asc')->select();
        foreach($city_list as $key=>$v){
            $city_name_list[] = $v['region_name'];
        }
        $data['city_name_list'] = $city_name_list;
        $data['city_list'] = $city_list;
        $this->to_back($data);
    }
    /**
     * @desc 
     */
    public function getSecArea(){
        $area_id = $this->params['area_id'];
        $parent_id = $this->getParentAreaid($area_id);
        $m_area = new \Common\Model\AreaModel();
        $fields = 'id,region_name';
        $where = array();
        $where['parent_id'] = $parent_id;
        $where['is_valid']    = 1;
        $order = 'id asc';
        $area_list = $m_area->getWhere($fields, $where, $order, '',2);
        $tmp = array('id'=>0,'region_name'=>'全部区域');
        array_unshift($area_list, $tmp);
        foreach($area_list as $key=>$v){
            $area_name_list[] = $v['region_name'];
        }
        $data['area_list'] = $area_list;
        $data['area_name_list'] = $area_name_list;
        $this->to_back($data);
    }
}