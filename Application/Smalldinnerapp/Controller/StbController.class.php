<?php
namespace Smalldinnerapp\Controller;
use \Common\Controller\CommonController;
class StbController extends CommonController{

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBoxList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }

    /**
     * @desc 获取机顶盒列表
     */
    public function getBoxlist(){
        $hotel_id = intval($this->params['hotel_id']);
        $fields = 'c.id as room_id,c.name as room_name,a.name as box_name,a.mac as box_mac';
        $m_box = new \Common\Model\BoxModel();
        $box_list = $m_box->getBoxListByHotelid($fields,$hotel_id);
        $this->to_back($box_list);
    }
    
}