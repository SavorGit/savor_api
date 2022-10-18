<?php
namespace Smallsale22\Controller;
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
        $res_box = $m_box->getBoxListByHotelRelation($fields,$hotel_id);
        $box_list = $box_name_list = array();
        if(!empty($res_box)) {
            array_unshift($res_box,array('box_name'=>'请选择包间电视','box_mac'=>''));
            foreach ($res_box as $k=>$v) {
                $box_list[] = array('id'=>$k,'name'=>$v['box_name'],'box_mac'=>$v['box_mac']);
                $box_name_list[] = $v['box_name'];
            }
        }
        $res_data = array('box_list'=>$box_list,'box_name_list'=>$box_name_list);
        $this->to_back($res_data);
    }
    
}