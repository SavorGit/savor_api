<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class BoxController extends BaseController{ 
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
     * @desc 获取酒楼得版位列表
     */
    public function getBoxList(){
        $hotel_id = $this->params['hotel_id'];
        $m_box = new \Common\Model\BoxModel();
        $data = $m_box->getInfoByHotelid($hotel_id,'box.id as box_id,box.mac,box.name as box_name ','  and box.flag=0 and box.state !=3');
        $this->to_back($data);
    }
}