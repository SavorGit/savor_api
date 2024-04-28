<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class RoomController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'roomlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'edit':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'room_id'=>1002,'hotel_id'=>1001,'name'=>1001,'type'=>1001,
                    'people_num'=>1001,'is_device'=>1001,'state'=>1001);
                break;
            case 'config':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function roomlist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $where = array('hotel.id'=>$hotel_id,'room.flag'=>0);
        $fields='room.id as room_id,room.name as room_name,room.type,room.people_num,room.is_device,room.state';
        $m_room = new \Common\Model\RoomModel();
        $res_rooms = $m_room->getRoomByCondition($fields,$where);
        $this->to_back($res_rooms);
    }

    public function edit(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $room_id = intval($this->params['room_id']);
        $name = trim($this->params['name']);
        $type = intval($this->params['type']);
        $people_num = intval($this->params['people_num']);
        $is_device = intval($this->params['is_device']);
        $state = intval($this->params['state']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_room = new \Common\Model\RoomModel();
        $data = array('hotel_id'=>$hotel_id,'name'=>$name,'type'=>$type,'people_num'=>$people_num,'is_device'=>$is_device,'state'=>$state,
            'op_openid'=>$openid);
        if($room_id>0){
            $data['update_time'] = date('Y-m-d H:i:s');
            $m_room->where(array('id'=>$room_id))->save($data);

            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(15);
            $data = $m_room->getOne('*',array('id'=>$room_id));
            $cache_key = 'savor_room_'.$room_id;
            $redis->set($cache_key, json_encode($data));
        }else{
            $room_id = $m_room->add($data);
        }
        $this->to_back(array('room_id'=>$room_id));
    }


    public function config(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $device_types = array(
            array('name'=>'有','value'=>1),
            array('name'=>'无','value'=>0),
        );
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('type',$hotel_id);
        if($res_hotel['type']==4){
            $device_types = array(array('name'=>'无','value'=>0));
        }
        $res_data = array(
            'room_types'=>array(
                array('name'=>'包间','value'=>1),
                array('name'=>'大厅','value'=>2),
                array('name'=>'等候区','value'=>3),
            ),
            'device_types'=>$device_types,
            'freeze_status'=>array(
                array('name'=>'正常','value'=>1),
                array('name'=>'冻结','value'=>2),
                array('name'=>'报损','value'=>3),
            )
        );
        $this->to_back($res_data);
    }

}