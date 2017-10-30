<?php

namespace Small\Controller;

use Common\Controller\CommonController as CommonController;;


class HotelController extends CommonController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getOneGenHotel':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取一代机顶盒
     */
    public function getOneGenHotel(){

        $h_model = new \Common\Model\HotelModel();
        $where = ' 1=1 and hotel_box_type=1 and flag=0 and state=1 ';
        $field = 'id,name hotel_name';
        $data = $h_model->getHotelList($where, '', '', $field);
        $this->to_back($data);
    }
}