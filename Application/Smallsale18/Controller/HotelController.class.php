<?php
namespace Smallsale18\Controller;
use \Common\Controller\CommonController as CommonController;
class HotelController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }

    public function getHotelList(){
        $m_hotel = new \Common\Model\HotelModel();
        $where = array('state'=>1,'flag'=>0);
        $where['hotel_box_type'] = array('in',array(2,3,6));
        $res_hotels = $m_hotel->getHotelList($where,'id asc','','id,name');

        $m_hotel = new \Common\Model\HotelModel();
        $all_hotels = array();
        foreach ($res_hotels as $v){
            $hotel_has_room = 0;
            $res_room = $m_hotel->getRoomNumByHotelId($v['id']);
            if($res_room){
                $hotel_has_room = 1;
            }
            $v['hotel_has_room'] = $hotel_has_room;
            $letter = getFirstCharter($v['name']);
            $all_hotels[$letter][]=$v;
        }
        ksort($all_hotels);
        $data = array();
        foreach ($all_hotels as $k=>$v){
            $dinfo = array('id'=>ord("$k")-64,'region'=>$k,'items'=>$v);
            $data[]=$dinfo;
        }
        $this->to_back($data);
    }

    public function getExplist(){
        $avg_exp_arr = array(
            'agv_name'=>array('请选择','100以下','100-200','200以上'),
            'agv_lisg'=>array(
                array('id'=>0,'name'=>'请选择'),
                array('id'=>1,'name'=>'100以下'),
                array('id'=>2,'name'=>'100-200'),
                array('id'=>3,'name'=>'200以上')
            ));
        $this->to_back($avg_exp_arr);
    }
}