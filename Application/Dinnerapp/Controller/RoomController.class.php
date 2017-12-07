<?php
/**
 * @desc 餐厅端1.2-包间
 * @author zhang.yingtao
 * @since  20171204
 */
namespace Dinnerapp\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class RoomController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'doLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>'1001');
                break;
        }
        parent::_init_();
    }
    public function getRoomList(){
        $hotel_id = $this->params['hotel_id'];
        $m_room = new \Common\Model\RoomModel();
        $where['hotel_id'] = $hotel_id;
        $where['flag'] = 0;
        $where['state']= 1;
        $field = 'id,name';
        $data = $m_room->getWhere($where,$field);
        $this->to_back($data);
    }
}