<?php
namespace Smallsale\Controller;
use \Common\Controller\CommonController as CommonController;

class RoomController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getRoomList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'box_mac'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getRoomList(){
        $m_box = new \Common\Model\BoxModel();
        $hotel_id = $this->params['hotel_id'];
        $box_mac  = $this->params['box_mac'];
        $fields = 'a.id,a.mac,c.name room_name ';
        $where  = [];
        $where['d.id']    = $hotel_id;
        $where['d.state'] = 1;
        $where['d.flag']  = 0;
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $order = 'a.id asc';
        $list = $m_box->alias('a')
                      ->join('savor_room c on a.room_id= c.id','left')
                      ->join('savor_hotel d on c.hotel_id=d.id','left')
                      ->field($fields)
                      ->where($where)
                      ->order($order)
                      ->select();
        $room_list = $room_name_list = [];
        $room_index = 0; 
        foreach($list as $key=>$v){
            $room_list[] = $v;
            $room_name_list[] = $v['room_name'];
            if($v['box_mac']==$box_mac){
                $room_index = $key;
            }        
        }
        $data['room_list'] = $room_list;
        $data['room_name_list'] = $room_name_list;
        $data['room_index']= $room_index;
        $this->to_back($data);
    }
}