<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class HotelController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'searchHotel':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_name'=>'1001','area_id'=>'1001');
                break;
           
        }
        parent::_init_();
       
    }
    /**
     * @desc 搜索酒楼
     */
    public function searchHotel(){
        $hotel_name = $this->params['hotel_name'];
        $area_id = $this->params['area_id'];
        $m_hotel = new \Common\Model\HotelModel();
        $where = $data = array();
        if($area_id!=9999){
            $where['area_id'] = $area_id;
        }
        $where['name'] = array('like',"%$hotel_name%");
        $where['state'] = '1';
        $where['flag'] = 0;
        $where['hotel_box_type'] = array('in','2,3');
        $order = ' id desc';
        $limit  = '';
        $fields = 'id,name,contractor,mobile,addr,area_id';
        
        $data = $m_hotel->getHotelList($where,$order,$limit,$fields );
        $list['list'] =$data;
        $this->to_back($list);
    }
}