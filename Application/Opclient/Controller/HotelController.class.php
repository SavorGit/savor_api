<?php
/**
 * @AUTHOR: baiyutao.
 * @PROJECT: PhpStorm
 * @FILE: HotelController.class.php
 * @CREATE ON: 2017/9/4 13:25
 * @VERSION: X.X
 * @desc:运维端酒店信息获取
 * @purpose:HotelController
 */
namespace Opclient\Controller;
use \Common\Controller\BaseController as BaseController;

class HotelController extends BaseController {
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelMacInfoById':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelId'=>'1001');
                break;
            case 'searchHotel':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_name'=>'1001');
                break;
        }
        parent::_init_();
    }

    /*
     * @desc 根据酒店id获取酒楼信息
     * @method getHotelMacInfoById
     * @access public
     * @http get
     * @param hotelId int
     * @return json
     */
    public function getHotelMacInfoById() {
        $hotel_id = intval( $this->params['hotelId'] );
        $this->disposeTips($hotel_id);
        $hotelModel = new \Common\Model\HotelModel();
        $menuHoModel = new \Common\Model\MenuHotelModel();
        $menlistModel = new \Common\Model\MenuListModel();
        $tvModel = new \Common\Model\TvModel();
        $vinfo = $hotelModel->getOneById(' name hotel_name,addr hotel_addr,area_id,iskey is_key,level,state_change_reason,install_date,state hotel_state,contractor,hotel_box_type,maintainer,tel,mobile,remote_id,tech_maintainer,hotel_wifi_pas,hotel_wifi,gps', $hotel_id);
        $vinfoa[] = $vinfo;
        $vinfo = $hotelModel->changeIdinfoToName($vinfoa);

        $res_hotelext = $hotelModel->getMacaddrByHotelId($hotel_id);
        $vinfo[0]['mac_addr'] = $res_hotelext['mac_addr'];
        $vinfo[0]['server_location'] = $res_hotelext['server_location'];
        $condition['hotel_id'] = $hotel_id;
        $order = 'id desc';
        $field = 'menu_id';
        $arr = $menuHoModel->fetchDataWhere($condition, $order,   $field, 1);
        $menuid = $arr['menu_id'];
        if($menuid){
            $men_arr = $menlistModel->find($menuid);
            $menuname = $men_arr['menu_name'];
            $vinfo[0]['menu_name'] = $menuname;

        }else{
            $vinfo[0]['menu_name'] = '';
        }
        $nums = $hotelModel->getStatisticalNumByStateHotelId($hotel_id);
        $vinfo[0]['room_num'] = $nums['room_num'];
        $vinfo[0]['box_num'] = $nums['box_num'];
        $vinfo[0]['tv_num'] = $nums['tv_num'];
        $data['list']['hotel_info'] = $vinfo;
        //获取批量版位
        $where = " h.id = ".$hotel_id;
        $list = $tvModel->isTvInfo('r.name as room_name,b.name as bmac_name,b.mac as bmac_addr,b.state as bstate  ', $where);
        $isHaveTv = $list['list'];
        if(!empty($isHaveTv)){
            $isRealTv = $tvModel->changeBoxTv($isHaveTv);
        }
        $data['list']['position'] = $isRealTv;
        $this->to_back($data);
    }

    /*
     * @desc 酒楼信息错误提示
     * @method disposeTips
     * @access public
     * @http null
     * @param hotelId int
     * @return json
     */
    public function disposeTips($hotel_id) {
        //检测酒楼是否存在且正常
        $m_hotel = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getInfoById($hotel_id, 'id');
        if( empty($hotel_info) ) {
            $this->to_back('16100');   //该酒楼不存在或被删除
        }
    }
    /**
     * @desc 搜索酒楼
     */
    public function searchHotel(){
        $hotel_name = $this->params['hotel_name'];
        $m_hotel = new \Common\Model\HotelModel();
        $where = array();
        $where['name'] = array('like',"%$hotel_name%");
        $order = ' id desc';
        $limit  = '';
        $fields = 'id,name';
        
        $data = $m_hotel->getHotelList($where,$order,$limit,$fields = 'id,name');
        $this->to_back($data);
    }
}