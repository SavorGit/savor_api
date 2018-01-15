<?php
namespace Small\Controller;
use \Common\Controller\CommonController as CommonController;
class DinnerController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelRecFoods':
                $this->is_verify=1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
            
        parent::_init_();
    }
    public function getHotelRecFoods(){
        $hotel_id = $this->params['hotel_id'];
        $m_hotel_recommend_food = new \Common\Model\HotelRecommendFoodModel();
        $fields = "a.id food_id,b.id , a.name food_name,b.oss_addr as oss_path,b.name as chinese_name,b.md5,'fullMd5' as `md5_type`,b.type media_type,a.update_time";
        $where = array();
        $where['a.hotel_id'] = $hotel_id;
        $where['a.state']    = 1;
        $where['a.flag']     = 0 ;
        $list = $m_hotel_recommend_food->getHotelList($fields, $where);
        if(empty($list)){
            $this->to_back(60007);
        }
        foreach($list as $key=>$v){
            $list[$key]['suffix'] =  getExt($v['oss_path']);
            $ttp = explode('/', $v['oss_path']);
            $list[$key]['name']   = $ttp[2];
            $update_time[] = strtotime($v['update_time']);
            unset($list[$key]['update_time']);
        }
        $max = max($update_time);
        $data['period'] = $max;
        $data['media_list'] = $list;
        $this->to_back($data);
    }
}