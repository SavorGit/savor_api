<?php
namespace Smallsale14\Controller;
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
        $fields = 'a.id,a.mac box_mac,a.name box_name ';
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
        $box_list = $box_name_list = [];
        $box_index = 0; 
        foreach($list as $key=>$v){
            $box_list[] = $v;
            $box_name_list[] = $v['box_name'];
            if($v['box_mac']==$box_mac){
                $box_index = $key;
            }        
        }
        $data['box_list'] = $box_list;
        $data['box_name_list'] = $box_name_list;
        $data['box_index']= $box_index;
        $this->to_back($data);
    }
}