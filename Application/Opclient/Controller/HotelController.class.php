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
use Common\Lib\SavorRedis;
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
        $vinfo = $hotelModel->getOneById(' id hotel_id, name hotel_name,addr hotel_addr,area_id,iskey is_key,level,state_change_reason,install_date,state hotel_state,contractor,hotel_box_type,maintainer,tel,mobile,remote_id,tech_maintainer,hotel_wifi_pas,hotel_wifi,gps', $hotel_id);
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
        $where['hotel_box_type'] = array('in','2,3');
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
        $field = " sd.`version_name`,sa.`ltime`,sa.`box_mac` small_mac ";
        $rets  = $m_heart_log->getLastHeartVersion($field, $where);
        $redis = SavorRedis::getInstance();
        
        
        $m_hotel_ext = new \Common\Model\HotelExtModel();
        $infos = $m_hotel_ext->getOnerow(array('hotel_id'=>$hotelid));
        if ( empty($rets) ) {
            $dat['last_heart_time'] = array(
                'ltime'=>'',
                'lstate'=>0,
            );
            $dat['last_small'] = '';
            $dat['pla_inner_ip'] = '';
            $dat['pla_out_ip'] = '';
            if( empty($infos['mac_addr']) ) {
                $dat['last_small'] = '没有填写MAC地址';
                $dat['small_mac'] = '';
            }else {
                $dat['small_mac'] = $infos['mac_addr'];
            }
        } else {
            if(!empty($infos['mac_addr'])){
                $redis->select(13);
                $key =  "heartbeat:".'1:'.$infos['mac_addr'];
                $heartbeat = $redis->get($key);
                $heartbeat_arr = json_decode($heartbeat,true);
                $ltime = $heartbeat_arr['date'];
                $dat['small_mac'] = $rets[0]['small_mac'];
                $dat['pla_inner_ip'] = $heartbeat_arr['intranet_ip'];
                $dat['pla_out_ip'] = $heartbeat_arr['outside_ip'];
                //$ltime = $rets[0]['ltime'];
                $diff = ($now-strtotime($ltime));
                if($diff< 3600) {
                    $dp = floor($diff/60).'分钟';
                
                }else if ($diff >= 3600 && $diff <= 86400) {
                    $hour = floor($diff/3600);
                    $min = floor($diff%3600/60);
                    $dp = $hour.'小时'.$min.'分钟';
                }else if ($diff > 86400) {
                    $day = floor($diff/86400);
                    $hour = floor($diff%86400/3600);
                    $dp = $day.'天'.$hour.'小时';
                }
                $lp = strtotime($ltime);
                $dp = $dp.'前';
                if($lp <= $start_time) {
                    $mstate = 0;
                } else {
                    $mstate = 1;
                }
                $dat['last_heart_time'] = array(
                    'ltime'=>$dp,
                    'lstate'=>$mstate,
                );
            }else {
                $dat['last_heart_time'] = array(
                    'ltime'=>'',
                    'lstate'=>0,
                );
            }
            
            
            $dat['last_small'] = $rets[0]['version_name'];
        }
        
        //小平台
        /* $versionModel = new \Common\Model\VersionModel();
        $co['device_type'] = 1;
        $order = 'id desc ';
        $field = 'version_name';
        $infoa = $versionModel->fetchDataWhere($co, $order, $field, 1); */
        
        $m_device_upgrade = new \Common\Model\DeviceUpgradeModel();
        $ret = $m_device_upgrade->getLastSmallPtInfo($hotelid);
        
        if(!empty($ret)){
            $m_device_version = new \Common\Model\DeviceVersionModel();
            $infoa = $m_device_version->getOneByVersionAndDevice($ret['version'], $device_type=1);
        }
        $dat['new_small'] = $infoa['version_name'] ?$infoa['version_name']:'';
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
        if($infos['mac_addr'] =='000000000000'){
            $dat['last_heart_time']['lstate'] = 1;
            $dat['last_small']['last_small_state'] = 1;
        }
        //获取小平台维修记录
        $redMo = new \Common\Model\RepairBoxUserModel();
        $cao['mac'] =  $dat['small_mac'];
        $field = 'sys.remark nickname, date_format(sru.create_time,"%m-%d  %H:%i") ctime ';
        $rinfo = $redMo->getRepairUserInfo($field, $cao);
        if (empty($rinfo)) {
            $dat['repair_record'] = array();
        } else {
            $dat['repair_record'] = $rinfo;
        }
        return $dat;
    }

    public function getLastNginx($mac){
        //获取机顶盒日志
        $ossboxModel = new \Common\Model\OSS\OssBoxModel();
        $last_time = $ossboxModel->getLastTime($mac);

        if( empty($last_time[0]['lastma']) ) {
            $time = '无';

        } else {
            $time =  date("Y-m-d H:i",strtotime($last_time[0]['lastma']));
        }
        return $time;
    }

    public function getHotelVersionById(){


        $hotel_id = intval( $this->params['hotel_id'] );
        $m_heart_log = new \Common\Model\HeartLogModel();
        $this->disposeTips($hotel_id);
        //获取版本信息
        $now = time();
        $start_time = strtotime('-72 hours');
        $version_info = $this->getNewVerson($hotel_id, $now, $start_time);
        $data['list']['version'] = $version_info;


        //获取心跳相关
        $m_box = new \Common\Model\BoxModel();
        $black_model = new \Common\Model\BlacklistModel();
        $where = '';

        $where .=" 1 and room.hotel_id=".$hotel_id.' and a.state =1 and a.flag =0 and room.state =1 and room.flag =0 ';

        $box_lista = $m_box->getList( 'room.name rname, a.name boxname, a.mac,a.id box_id',$where);
        //过滤黑名单
        /*
        $bfield = 'box_id';
        $yestoday_time = strtotime('-1 day');
        $yestoday_start = date('Y-m-d 00:00:00',$yestoday_time);
        $yestoday_end   = date('Y-m-d 23:59:59',$yestoday_time);
        $where = array();
         $bwhere['create_time'] = array(array('EGT',$yestoday_start),
            array('elt',$yestoday_end));
       $bwhere['hotel_id'] = $hotel_id;
        $black_box = $black_model->getAll($bwhere, $bfield);
        if($black_box){
            $black_box = array_column($black_box, 'box_id');
            $black_box = array_flip($black_box);
            $box_list = array_filter($box_lista, function($val)use($black_box){
                    if(array_key_exists($val['box_id'], $black_box)) {
                        return 0;
                    } else {
                        return 1;
                    }
            });
        } else{
            $box_list = $box_lista;
        }*/
        $box_list = $box_lista;
        $unusual_num = 0;
        $box_total_num = count($box_list);
        $m_black_list = new \Common\Model\BlacklistModel();
        $bla_a = 0;
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $key = "heartbeat:".'2:';

        foreach($box_list as $ks=>$vs){
            /* $where = '';
            $where .=" 1 and hotel_id=".$hotel_id." and type=2 and box_id='".$vs['box_id']."'";

            $rets  = $m_heart_log->getHotelHeartBox($where,'max(last_heart_time) ltime', 'box_mac'); */
            $heartbeat = $redis->get($key.$vs['mac']);
            $heartbeat = json_decode($heartbeat,true);

            
            //获取是否是黑名单
            $black_ar = array();
            $black_ar['box_id'] = $vs['box_id'];
            $black_res = $m_black_list->countNums($black_ar);
            if($black_res){
                //黑名单机顶盒
                $box_list[$ks]['blstate'] = 1;
                $bla_a +=1;

            } else {
                $box_list[$ks]['blstate'] = 0;
            }
            if(empty($heartbeat)){
                $unusual_num +=1;
                $box_list[$ks]['ustate'] = 0;
                $box_list[$ks]['last_heart_time'] = '无';
                $box_list[$ks]['ltime'] = '-888';
                $box_list[$ks]['box_ip'] = '';
            }else {
                $box_list[$ks]['box_ip'] = empty($heartbeat['intranet_ip'])
                    ?'':$heartbeat['intranet_ip'];

                $ltime = $heartbeat['date'];
                //echo date('Y-m-d H:i:s').'------';
                //echo $ltime.'----';
                $diff = (time()-strtotime($ltime));
                //echo $diff.'---';
                //echo floor($diff/60).'分钟';
               // exit;
                if($diff< 3600) {
                    $box_list[$ks]['last_heart_time'] = floor($diff/60).'分钟';

                }else if ($diff >= 3600 && $diff <= 86400) {
                    $hour = floor($diff/3600);
                    $min = floor($diff%3600/60);
                    $box_list[$ks]['last_heart_time'] = $hour.'小时'.$min.'分钟';
                }else if ($diff > 86400) {
                    $day = floor($diff/86400);
                    $hour = floor($diff%86400/3600);
                    $box_list[$ks]['last_heart_time'] = $day.'天'.$hour.'小时';
                }
                $box_list[$ks]['last_heart_time'] = $box_list[$ks]['last_heart_time'].'前';
                $box_list[$ks]['ltime'] = strtotime($ltime);
                if($box_list[$ks]['ltime'] <= $start_time) {
                    $unusual_num +=1;
                    $box_list[$ks]['ustate'] = 0;
                } else {
                    $box_list[$ks]['ustate'] = 1;
                }
            }
            $box_list[$ks]['last_nginx'] = $this->getLastNginx($vs['mac']);
        }
        //二维数组排序

        foreach ($box_list as $key => $row)
        {

            $volume[$key]  = $row['ltime'];
            $blc[$key]  =   $row['blstate'];
        }
        array_multisort($blc,SORT_DESC, $volume, SORT_ASC, $box_list);
        $redMo = new \Common\Model\RepairBoxUserModel();

        foreach($box_list as $bk => $bv) {
            //获取机顶盒维修记录
            $field = 'sys.remark nickname, date_format(sru.create_time,"%m-%d  %H:%i") ctime ';
            $co['mac'] = $bv['mac'];
            $rinfo = $redMo->getRepairUserInfo($field, $co);
            if (empty($rinfo)) {
                $box_list[$bk]['repair_record'] = array();
            } else {
                $box_list[$bk]['repair_record'] = $rinfo;
            }
            unset($box_list[$bk]['ltime']);
            //unset($box_list[$bk]['box_id']);
        }
        $data['list']['box_info'] = $box_list;

        $data['list']['banwei'] = '版位信息(共'.$box_total_num.'个,'.'失联超过72个小时'.$unusual_num.'个,黑名单'.$bla_a.'个)';
        $this->to_back($data);
    }
}