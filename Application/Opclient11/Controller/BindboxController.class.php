<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class BindboxController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBoxList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,
                    'room_id'=>1001);
                break;
            case 'bindBox':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,
                    'room_id'=>1001,'box_id'=>1001,'new_mac'=>1001);
                break;
           
        }
        parent::_init_();
    }
    /**
     * @desc 获取酒楼得版位列表
     */
    public function getBoxList(){
        $hotel_id = $this->params['hotel_id'];
        $hotelModel = new \Common\Model\HotelModel();
        if(!empty($hotelid)){
            if(!is_numeric($hotelid)){
                $this->to_back(10007);
            }
            $hotelinfo = $hotelModel->find($hotelid);
            if(count($hotelinfo) == 0){
                $this->to_back(10007);
            }
        }
        $room_id = $this->params['room_id'];
        $m_tv = new \Common\Model\TvModel();
        $where = " r.hotel_id= ".$hotel_id." and r .id = ".$room_id." ";
        $ret = array();
        $field = 'tv.tv_brand,b.id box_id,r.name room_name,b.name box_name,b.mac box_mac ';
        $ret = $m_tv->isTvInfo($field, $where);

        $this->to_back($ret);

    }

    public function bindBox() {
        $hotel_id = $this->params['hotel_id'];
        $room_id = $this->params['room_id'];
        $box_id = $this->params['box_id'];
        $new_mac = $this->params['new_mac'];
        if( !(preg_match('/^[0-9A-F]{12}$/', $new_mac)) ) {
            $this->to_back(10006);
        }
        $m_tv = new \Common\Model\TvModel();
        $field = 'b.id';
        $where = '1=1 and h.id='.$hotel_id.' and r.id='.$room_id.'
        and b.id='.$box_id;
        $ret = $m_tv->isTvInfo($field, $where);
        $ret = $ret['list'];
        if($ret){
            $b_box = new \Common\Model\BoxModel();
            $info = $b_box->getBoxInfoByMac($new_mac);
            if($info){
                $this->to_back(30111);
            } else{
                $save['mac'] = $new_mac;
                $dat['id'] = $ret[0]['id'];
               $res = $b_box->saveData($save, $dat);
                if($res) {
                    $this->to_back(10000);
                } else {
                    $this->to_back(30112);
                }
            }

        }else{
            $this->to_back(30110);
        }
    }
}
