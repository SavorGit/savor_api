<?php
/**
 * @desc 餐厅端1.2-推荐菜
 * @author zhang.yingtao
 * @since  20171204
 */
namespace Dinnerapp\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class RecfoodController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelRecFoods':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取酒楼下的推荐菜
     */
    public function getHotelRecFoods(){
        $hotel_id = $this->params['hotel_id'];
        $m_hotel_recommend_food = new \Common\Model\HotelRecommendFoodModel();
        $fields = "a.id food_id,b.id , a.name food_name,b.oss_addr as oss_path,b.name as chinese_name,b.md5,'fullMd5' as `md5_type`";
        $where = array();
        $where['a.hotel_id'] = $hotel_id;
        $where['a.state']    = 1;
        $where['a.flag']     = 0 ;
        $list = $m_hotel_recommend_food->getHotelListOne($fields, $where);
        if(empty($list)){
            $this->to_back(60007);
        }
        $oss_host = C('TASK_REPAIR_IMG');
        foreach($list as $key=>$v){
           $list[$key]['suffix'] =  getExt($v['oss_path']);
           $ttp = explode('/', $v['oss_path']);
           $list[$key]['name']   = $ttp[2];
           $list[$key]['oss_path'] = $oss_host.'/'.$v['oss_path'].'?x-oss-process=image/resize,w_200';
        }
        $this->to_back($list);
    }
}