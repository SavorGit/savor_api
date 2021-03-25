<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
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
                $this->valid_fields = array('area_id'=>1002,'type'=>1002);
                $this->is_verify = 0;
                break;
            case 'getSecArea':
                $this->is_verify = 1;
                $this->valid_fields = array('area_id'=>1001,'type'=>1002);
                break;
            case 'getCityAreaList':
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

    /**
     * @desc 获取城市列表
     */
    public function getAreaList(){
        $area_id = isset($this->params['area_id'])?intval($this->params['area_id']):0;
        $type = isset($this->params['type'])?intval($this->params['type']):1;//1正常 2商城
        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name";

        if($type==2){
            $is_city = 0;
            $citys = array(1,9,236,246);
            if($area_id && in_array($area_id,$citys)){
                $is_city = 1;
            }
            if($is_city){
                $where = array('is_in_hotel'=>1,'is_valid'=>1);
            }else{
                $where = array('parent_id'=>0,'is_valid'=>1);
            }
        }else{
            $where = array('is_in_hotel'=>1,'is_valid'=>1);
        }

        if($area_id){
            $where['id'] = $area_id;
        }
        $city_list = $m_area->field($fields)->where($where)->order('id asc')->select();
        if($type==2){
            $tmp = array('id'=>0,'region_name'=>'请选择');
            array_unshift($city_list, $tmp);
        }
        $city_name_list = array();
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
        $type = isset($this->params['type'])?intval($this->params['type']):1;//1正常 2商城
        
        $m_area = new \Common\Model\AreaModel();
        $fields = 'id,region_name';
        $where = array();
        $where['parent_id'] = $parent_id;
        $where['is_valid']    = 1;
        if($type==1){
            $m_hotel = new \Common\Model\HotelModel();
            $group = 'county_id';
            $county_arr = $m_hotel->field('county_id')->where(array('area_id'=>$area_id))->group($group)->select();
            $tmps = array();
            foreach($county_arr as $key=>$v){
                $tmps[]= $v['county_id'];
            }
            $where['id'] = array('in',$tmps);
        }

        $order = 'id asc';
        $area_list = $m_area->getWhere($fields, $where, $order, '',2);
        $tmp = array('id'=>0,'region_name'=>'全部区域');
        if($type==2){
            $tmp = array('id'=>0,'region_name'=>'请选择');
        }
        array_unshift($area_list, $tmp);
        $area_name_list = array();
        foreach($area_list as $key=>$v){
            $area_name_list[] = $v['region_name'];
        }
        $data['area_list'] = $area_list;
        $data['area_name_list'] = $area_name_list;
        $this->to_back($data);
    }

    public function getCityAreaList(){
        $area_id = intval($this->params['area_id']);
        $m_area = new \Common\Model\AreaModel();
        $fields = "id,region_name as name";

        $where = array('is_in_hotel'=>1,'is_valid'=>1);
        $city_list = $m_area->field($fields)->where($where)->order('id asc')->select();
        $area_list = array();
        $city_names = $area_names = array();
        $index = 0;
        foreach ($city_list as $k=>$v){
            $city_names[]=$v['name'];

            $parent_id = $this->getParentAreaid($v['id']);
            $fields = 'id,region_name as name';
            $where = array('parent_id'=>$parent_id,'is_valid'=>1);
            $order = 'id asc';
            $city_area_list = $m_area->getWhere($fields, $where, $order, '',2);
            $tmp = array('id'=>0,'name'=>'全部区域');
            array_unshift($city_area_list, $tmp);
            if($v['id']==$area_id){
                $index = $k;
                foreach ($city_area_list as $av){
                    $area_names[]=$av['name'];
                }
            }
            $area_list[]=$city_area_list;
        }
        $names = array($city_names,$area_names);
        $data = array('city_list'=>$city_list,'area_list'=>$area_list,'index'=>array($index,0),'names'=>$names);
        $this->to_back($data);
    }
}