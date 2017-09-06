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
        $where = $data = array();
        $where['name'] = array('like',"%$hotel_name%");
        $order = ' id desc';
        $limit  = '';
        $fields = 'id,name';
        
        $data = $m_hotel->getHotelList($where,$order,$limit,$fields = 'id,name');
        $list['list'] =$data;
        $this->to_back($list);
    }

    public function getNewVerson($hotelid, $now, $start_time) {
        $m_heart_log = new \Common\Model\HeartLogModel();
        //获得小平台最后心跳时间
        $where = '';
        $where .=" 1 and hotel_id=".$hotelid." and type=1";
        $field = " sd.`version_name`,sa.`ltime` ";
        $rets  = $m_heart_log->getLastHeartVersion($field, $where);
        if ( empty($rets) ) {
            $dat['last_heart_time'] = array(
                'ltime'=>'',
                'lstate'=>0,
            );
            $dat['last_small'] = '';
        } else {
            $ltime = $rets[0]['ltime'];
            $diff = ($now-strtotime($ltime));
            if($diff< 3600) {
                $dp = floor($diff/60).'分';

            }else if ($diff >= 3600 && $diff <= 86400) {
                $hour = floor($diff/3600);
                $min = floor($diff%3600/60);
                $dp = $hour.'小时'.$min.'分';
            }else if ($diff > 86400) {
                $day = floor($diff/86400);
                $hour = floor($diff%86400/3600);
                $dp = $day.'天'.$hour.'小时';
            }
            $lp = strtotime($ltime);
            if($lp <= $start_time) {
                $mstate = 0;
            } else {
                $mstate = 1;
            }
            $dat['last_heart_time'] = array(
                'ltime'=>$dp,
                'lstate'=>$mstate,
            );
            $dat['last_small'] = $rets[0]['version_name'];
        }
        //小平台
        $versionModel = new \Common\Model\VersionModel();
        $co['device_type'] = 1;
        $order = 'id desc ';
        $field = 'version_name';
        $infoa = $versionModel->fetchDataWhere($co, $order, $field, 1);
        $dat['new_small'] = $infoa['version_name'];
        //获取小平台心跳最后版本号
        if ($dat['new_small'] == $dat['last_small']) {
            $dat['last_small'] = array(
                'last_small_pla' =>$dat['last_small'],
                'last_small_state' =>1,
            );
        } else {
            $dat['last_small'] = array(
                'last_small_pla' =>$dat['last_small'],
                'last_small_state' =>0,
            );
        }
        return $dat;
    }

    public function getHotelVersionById(){
        $hotel_id = intval( $this->params['hotel_id'] );
        $m_heart_log = new \Common\Model\HeartLogModel();
        $this->disposeTips($hotel_id);
        //获取版本信息
        $now = time();
        $start_time = strtotime('-15 hours');
        $version_info = $this->getNewVerson($hotel_id, $now, $start_time);
        $data['list']['version'] = $version_info;


        //获取心跳相关
        $m_box = new \Common\Model\BoxModel();
        $where = '';
        $where .=" 1 and room.hotel_id=".$hotel_id.' and a.state=1 and a.flag =0';
        $box_list = $m_box->getList( 'room.name rname, a.name boxname, a.mac',$where);
        $unusual_num = 0;
        $box_total_num = count($box_list);
        foreach($box_list as $ks=>$vs){
            $where = '';
            $where .=" 1 and hotel_id=".$hotel_id." and type=2 and box_mac='".$vs['mac']."'";

            $rets  = $m_heart_log->getHotelHeartBox($where,'max(last_heart_time) ltime', 'box_mac');
            if(empty($rets)){
                $unusual_num +=1;
                $box_list[$ks]['ustate'] = 0;
                $box_list[$ks]['last_heart_time'] = '无';
                $box_list[$ks]['ltime'] = '-888';
            }else {
                $ltime = $rets[0]['ltime'];
                $diff = ($now-strtotime($ltime));
                if($diff< 3600) {
                    $box_list[$ks]['last_heart_time'] = floor($diff/60).'分';

                }else if ($diff >= 3600 && $diff <= 86400) {
                    $hour = floor($diff/3600);
                    $min = floor($diff%3600/60);
                    $box_list[$ks]['last_heart_time'] = $hour.'小时'.$min.'分';
                }else if ($diff > 86400) {
                    $day = floor($diff/86400);
                    $hour = floor($diff%86400/3600);
                    $box_list[$ks]['last_heart_time'] = $day.'天'.$hour.'小时';
                }
                $box_list[$ks]['ltime'] = strtotime($ltime);
                if($box_list[$ks]['ltime'] <= $start_time) {
                    $unusual_num +=1;
                    $box_list[$ks]['ustate'] = 0;
                } else {
                    $box_list[$ks]['ustate'] = 1;
                }
            }
        }
        //二维数组排序

        foreach ($box_list as $key => $row)
        {
            $volume[$key]  = $row['ltime'];
        }
        array_multisort($volume, SORT_ASC, $box_list);
        $redMo = new \Common\Model\RepairBoxUserModel();

        foreach($box_list as $bk => $bv) {
            //获取机顶盒维修记录
            $field = 'sys.remark nickname, sru.create_time ctime ';
            $co['mac'] = $bv['mac'];
            $rinfo = $redMo->getRepairUserInfo($field, $co);
            if (empty($rinfo)) {
                $box_list[$bk]['repair_record'] = array();
            } else {
                $box_list[$bk]['repair_record'] = $rinfo;
            }
            unset($box_list[$bk]['ltime']);
        }
        $data['list']['box_info'] = $box_list;
        $data['list']['banwei'] = '版位信息(共'.$box_total_num.'个,'.'失联超过15个小时'.$unusual_num.'个)';
        $this->to_back($data);
    }
}