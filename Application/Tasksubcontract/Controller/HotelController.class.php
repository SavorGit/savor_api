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
namespace Tasksubcontract\Controller;
use \Common\Controller\BaseController as BaseController;

class HotelController extends BaseController {
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelMacInfoById':
                $this->is_verify = 1;
                $this->valid_fields=array('hotel_id'=>'1001');
                break;
            case 'searchHotel':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_name'=>'1001');
                break;
            case 'getHotelVersionById':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>'1001');
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
        $hotel_id = intval( $this->params['hotel_id'] );
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
        if(!empty($isRealTv)){
            $data['list']['position'] = $isRealTv;
        } else {
            $data['list']['position'] = array();
        }
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
        $where = $data = array();
        $where['name'] = array('like',"%$hotel_name%");
        $where['state'] = '1';
        $where['flag'] = 0;
        $where['hotel_box_type'] = array('EQ','1');
        $order = ' id desc';
        $limit  = '';
        $fields = 'id,name';
        
        $data = $m_hotel->getHotelList($where,$order,$limit,$fields = 'id,name');
        $list['list'] =$data;
        $this->to_back($list);
    }



    /**
     * @desc 获取酒楼的维修签到信息
     */
    public function getHotelVersionById(){


        $hotel_id = intval( $this->params['hotel_id'] );
        $this->disposeTips($hotel_id);
        //获取心跳相关
        $m_box = new \Common\Model\BoxModel();
        $where = '';

        $where .=" 1 and room.hotel_id=".$hotel_id.' and a.state !=2 and a.flag =0 and room.state !=2 and room.flag =0 ';

        $box_list = $m_box->getList( 'room.name rname, a.name boxname, a.mac,a.id bid ',$where);
        $box_total_num = count($box_list);
        foreach($box_list as $ks=>$vs){
            $sql = "select a.srtype,a.create_time ctime,a.current_location from (select

* from savor_subcontract where bid = '".$vs["bid"]."' order by id desc) as a limit 1";
            $rets  = $m_box->query($sql);
            if(empty($rets)){
                $box_list[$ks]['srtype'] = '无';
                $box_list[$ks]['last_time'] = '99999999999999';
                $box_list[$ks]['last_ctime'] = '';
                $box_list[$ks]['current_location'] = '无';
            }else {
                if($rets[0]['srtype'] == 2) {
                    $box_list[$ks]['srtype'] = '报修';
                } else {
                    $box_list[$ks]['srtype'] = '签到';
                }

                $box_list[$ks]['last_time'] = strtotime($rets[0]['ctime']);
                $box_list[$ks]['last_ctime'] = $rets[0]['ctime'];
                $box_list[$ks]['current_location']  = empty($rets[0]['current_location'])?'无':$rets[0]['current_location'];
            }
        }
        //二维数组排序
die;
        foreach ($box_list as $key => $row)
        {
            $volume[$key]  = $row['ltime'];
        }
        array_multisort($volume, SORT_ASC, $box_list);
        foreach($box_list as $bk => $bv) {
            unset($box_list[$bk]['last_time']);
        }
        $data['list']['box_info'] = $box_list;
        $data['list']['banwei'] = '版位信息(共'.$box_total_num.'个)';
        $this->to_back($data);
    }
}