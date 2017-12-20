<?php
/**
 * @desc 餐厅端2.0-包间
 * @author zhang.yingtao
 * @since  20171220
 */
namespace Dinnerapp2\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class RoomController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'addRoom':
                $this->is_verify = 1;
                $this->valid_fields = array('invite_id'=>1001,'mobile'=>1001,'room_name'=>1001);
                break;
            case 'getList':
                $this->is_verify = 1;
                $this->valid_fields = array('invite_id'=>1001,'mobile'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 添加包间
     */
    public function addRoom(){
         $invite_id = $this->params['invite_id'];
         $mobile   = $this->params['mobile'];    //用户手机号
         $room_name = $this->params['room_name'];
         if(!check_mobile($mobile)){
             $this->to_back('60002');
         }
         $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
         $where = array();
         $where['id'] = $invite_id;
         $where['state'] = 1;
         $where['flag'] = '0';
         $invite_info = $m_hotel_invite_code->getOne('bind_mobile,hotel_id', $where);
         if(empty($invite_id)){
             $this->to_back(60018);
         }
         if($invite_info['bind_mobile'] != $mobile){
             $this->to_back(60019);
         }
         
         $m_dinner_room = new \Common\Model\DinnerRoomModel(); 
         $where = array();
         $where['name'] = trim($room_name);
         $where['hotel_id'] = $invite_info['hotel_id'];
         $where['flag'] = 0;
         $nums = $m_dinner_room->countNums($where);
         if(!empty($nums)){
             $this->to_back(60022);
         }
         $data = array();
         $data['invite_id'] = $invite_id;
         $data['hotel_id']  = $invite_info['hotel_id'];
         $data['name']      = trim($room_name);
         $ret = $m_dinner_room->add($data);
         if($ret){
             $this->to_back(10000);
         }else {
             $this->to_back(60021);
         }
    }
    /**
     * @desc 获取包间列表
     */
    public function getList(){
        $invite_id = $this->params['invite_id'];
        $mobile   = $this->params['mobile'];    //用户手机号
        
        if(!check_mobile($mobile)){
            $this->to_back('60002');
        }
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $where = array();
        $where['id'] = $invite_id;
        $where['state'] = 1;
        $where['flag'] = '0';
        $invite_info = $m_hotel_invite_code->getOne('bind_mobile,hotel_id', $where);
        if(empty($invite_id)){
            $this->to_back(60018);
        }
        if($invite_info['bind_mobile'] != $mobile){
            $this->to_back(60019);
        }
        $m_room = new \Common\Model\RoomModel();
        $where = array();
        $where['hotel_id'] = $invite_info['hotel_id'];
        $where['flag'] = 0;
        $fields = "`id`,`name`,'1' as `room_type`";
        $room_list = $m_room->getWhere($where,$fields);
        
        $m_dinner_room = new \Common\Model\DinnerRoomModel();
        $fields = "`id`,`name`, '2' as `room_type`";
        $where = array();
        $where['hotel_id'] = $invite_info['hotel_id'];
        $where['flag']     = 0;
        $dinner_room_list =$m_dinner_room->getList($fields, $where);
        
        if(empty($room_list)){
            $room_list = $dinner_room_list;
        }else {
            $room_list = array_merge($room_list,$dinner_room_list);
        }
        $this->to_back($room_list);  
    }
}