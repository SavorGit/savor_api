<?php
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class PubtaskController extends BaseController{
    private $pagesize;
    private $task_state_arr ;
    private $task_emerge_arr;
    private $option_user_skill_arr;
    private $option_user_skill_bref_arr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getPubUser':
                $this->is_verify = 1;
                $this->valid_fields = array('publish_user_id'=>'10001');
                break;
            case 'getMytaskHotel';
                $this->is_verify = 1;
                $this->valid_fields = array('publish_user_id'=>'1001',);
                break;
                
        }
        parent::_init_();
    }

    public function getPubUser(){
        $publish_user_id = $this->params['publish_user_id'];  //发布者用户id
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id', $publish_user_id);
        if(empty($role_info)){//未设置发布者账号
            $this->to_back(30057);
        }
        if($role_info['role_id'] !=1){ //不是对应的发布者角色
            $this->to_back(30058);
        }
        //获取发布者列表
        $fields = 'a.user_id publish_user_id,user.remark ';
        $map['state']   = 1;
        $map['role_id']   = 1;
        $user_info = $m_opuser_role->getList($fields,$map,'' );
        $data['list'] = $user_info;
        $this->to_back($data);

    }

    public function getMytaskHotel() {
        //只显示心跳的
        $h_type = C('HEART_HOTEL_BOX_TYPE');
        $h_type = array_keys($h_type);
        $h_type = implode(',', $h_type);
        $publish_user_id = $this->params['publish_user_id'];  //发布者用户id
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id', $publish_user_id);
        if(empty($role_info)){//未设置发布者账号
            $this->to_back(30057);
        }
        if($role_info['role_id'] !=1){ //不是对应的发布者角色
            $this->to_back(30058);
        }
        $start_time = date('Y-m-d H:i:s',strtotime('-72 hours'));
        $m_hotel = new \Common\Model\HotelModel();
        $field = ' a.id hid, a.state hstate';
        $map['b.maintainer_id'] = $publish_user_id;
        $map['a.hotel_box_type'] = array('in',$h_type);
        $map['a.flag'] = 0;
        $map['a.state'] = 1;

        $hotel_info = $m_hotel->getHotelLists($map, '','', $field);

        $h_all_num = count($hotel_info);
        $nor_h = 0;
        $freez_h = 0;
        $normal_box_num = 0;
        $not_normal_box_num = 0;
        $black_box_num = 0;
        $m_box = new \Common\Model\BoxModel();
        $m_heart_log = new \Common\Model\HeartLogModel();

        $m_black_list = new \Common\Model\BlacklistModel();
        //黑名单酒楼板位数
        $black_hotel_boxn = array();
        foreach($hotel_info as $hv) {
            //正常
            if ( $hv['hstate'] == 1) {
                $nor_h++;
                $where = '';
                $where .=" 1 and room.hotel_id=".$hv['hid'].'  and a.flag =0 and  room.flag=0 and room.state =1 ';
                $box_list = $m_box->getList( 'a.id, a.mac',$where);

                foreach($box_list as $ks=>$vs){
                    $where = '';
                    $where .=" 1 and hotel_id=".$hv['hid']." and type=2 and box_id='".$vs['id']."'";
                    $where .="  and last_heart_time>='".$start_time."'";
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');

                    $black_ar = array();
                    $black_ar['box_id'] = $vs['id'];
                    $black_res = $m_black_list->countNums($black_ar);
                    if(empty($rets)){
                        //判断异常机顶盒
                        if(empty($black_res)){
                            //不在72小时，有可能在黑名单，因为存的是5天不正常
                            $not_normal_box_num +=1;
                        }else{
                            $black_box_num +=1;
                            $black_hotel_boxn[$hv['hid']][] = 1;
                        }

                    }else {
                        //判断正常机顶盒
                        //在黑名单但有可能刚开机有心跳
                        if(empty($black_res)){
                            $normal_box_num +=1;
                        }else{
                            $black_box_num +=1;
                            $black_hotel_boxn[$hv['hid']][] = 1;
                        }

                    }


                }
            }
            //冻结
            if ( $hv['hstate'] == 2) {
                $freez_h++;
            }
        }

        $data['list']['heart']['hotel_all_nums'] = $h_all_num;
        $data['list']['heart']['hotel_all_normal_nums'] = $nor_h;
        $data['list']['heart']['hotel_all_freeze_nums'] = $freez_h;
        $data['list']['heart']['box_normal_num'] = $normal_box_num;
        $data['list']['heart']['box_not_normal_num'] = $not_normal_box_num;
        $data['list']['heart']['black_box_num'] = $black_box_num;
        $data['list']['heart']['remark'] = "在线为心跳72小时以内;异常指大于72小时;黑名单为连续三天失联的版位";

        //获取第二块牌位信息
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $pageNum= $this->params['pageNum'] ? $this->params['pageNum'] :1;
        //获取酒楼
        if($hotel_info) {
            $hotel_id_arr = array_column($hotel_info, 'hid');
            $h_id_str = implode(',', $hotel_id_arr);
            $where = '1=1';
            $where .= " and hotel_id  in (".$h_id_str.")";
            //获取数据
            $fileds = '*';
            $order = ' small_plat_status asc, pla_lost_hour desc,not_box_percent desc,box_lost_hour desc';
            $start  = ($pageNum-1)*$pageSize;
            $hotelUnModel = new \Common\Model\HotelUnusualModel();
            $error_info = $hotelUnModel->getList($fileds, $where, $order,$start, $pageSize);
            $m_hotel = new \Common\Model\HotelModel();
            foreach($error_info as $key=>$v){
                $box_str = '';
                $data['list']['hotel'][$key]['hotel_id'] = $v['hotel_id'];
                $hotel_info = $m_hotel->getPlaMac('a.name,b.mac_addr mac',$v['hotel_id']);
                $hname = $hotel_info['name'];
                $data['list']['hotel'][$key]['hotel_info'] = $hname.' 共'.$v['box_num'].'个版位';
                $data['list']['hotel'][$key]['hotel_name'] = $hname;
                if($v['small_plat_status']==1){
                    $data['list']['hotel'][$key]['small_palt_info'] = '小平台正常,上次上报时间'.$v['small_plat_report_time'].';';
                }else if($v['small_plat_status']==0) {
                    if($v['small_plat_report_time']=='0000-00-00 00:00:00'){
                        $data['list']['hotel'][$key]['small_palt_info'] = '小平台异常，未找到心跳';
                    }else {
                        $data['list']['hotel'][$key]['small_palt_info'] ='小平台异常，失联时长'.$v['pla_lost_hour'].'小时';
                    }
                }else {
                    $data['list']['hotel'][$key]['small_palt_info'] = '虚拟 小平台正常,上次上报时间'.$v['small_plat_report_time'].';';
                }
                if(empty($v['not_normal_box_num'])){
                    $box_str = '机顶盒正常';
                }else if($v['not_normal_box_num']==1){
                    if($v['box_report_time'] =='0000-00-00 00:00:00'){
                        $v['box_lost_hour'] = '未找到心跳;';
                    }
                    $box_str = '机顶盒异常1个;'.'失联时长'.$v['box_lost_hour'].'小时';
                }else if($v['not_normal_box_num']>1){

                    $box_str ='机顶盒异常'.$v['not_normal_box_num'].'个';

                }
                $count = count($error_info);
                if($count<$pageSize){
                    $data['list']['isNextPage'] = 0;
                }else {
                    $data['list']['isNextPage'] = 1;
                }
                //获取黑名单板位数
                $blk_bo = count($black_hotel_boxn[$v['hotel_id']]);
                if($blk_bo) {
                    $data['list']['hotel'][$key]['box_info'] = $box_str.','.'黑名单'.$blk_bo.'个';
                } else {
                    $data['list']['hotel'][$key]['box_info'] = $box_str;
                }


            }
        } else {
            $data['list']['hotel'] = array();
        }


        $this->to_back($data);
    }

}