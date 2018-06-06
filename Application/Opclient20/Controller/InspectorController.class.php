<?php
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class InspectorController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getMyInspect':
                $this->is_verify = 1;
                $this->valid_fields = array('user_id'=>1001,'pageSize'=>1000);
                break;
           
        }
        parent::_init_();
    }
    /**
     * @desc 获取巡视酒楼
     */
    public function getMyInspect(){
        $heart_loss_hours = C('HEART_LOSS_HOURS');
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $pageNum= $this->params['pageNum'] ? $this->params['pageNum'] :1;
        $publish_user_id = $this->params['user_id'];  //发布者用户id
        $m_opuser_role = new \Common\Model\OpuserRoleModel();
        $role_info = $m_opuser_role->getInfoByUserid('role_id,hotel_info hotel_id_str', $publish_user_id);
        if(empty($role_info)){//未设置发布者账号
            $this->to_back(30057);
        }
        if($role_info['role_id'] !=6){ //不是对应的发布者角色
            $this->to_back(30058);
        }
        $start_time = date('Y-m-d H:i:s',strtotime('-'.$heart_loss_hours.' hours'));
        $now = date("Y-m-d H:i:s");
        $now_time = strtotime($now);
        if ($role_info['hotel_id_str']) {
            $h_str  = $role_info['hotel_id_str'];
            //筛选虚拟小平台
            $h_arr = explode(',', $h_str);
            $h_count = count($h_arr);
            $h_arr = array_flip($h_arr);
            $data['count'] = $h_count;
            $where = '1=1';
            $where .= " and hotel_id  in (".$h_str.")";
            //获取数据
            $fileds = '*';
            $order = ' small_plat_status asc, pla_lost_hour desc,not_box_percent desc,box_lost_hour desc';
            $start  = ($pageNum-1)*$pageSize;
            $hotelUnModel = new \Common\Model\HotelUnusualModel();
            $error_info = $hotelUnModel->getList($fileds, $where, $order,$start, $pageSize);
            $m_hotel = new \Common\Model\HotelModel();
            foreach($error_info as $key=>$v){
                $data['list'][$key]['hotel_id'] = $v['hotel_id'];
                $hotel_info = $m_hotel->getPlaMac('a.name,b.mac_addr mac',$v['hotel_id']);
                $hname = $hotel_info['name'];
                $data['list'][$key]['hotel_info'] = $hname.' 共'.$v['box_num'].'个版位';
                $data['list'][$key]['hotel_name'] = $hname;
                if($v['small_plat_status']==1){
                    $data['list'][$key]['small_palt_info'] = '小平台正常,上次上报时间'.$v['small_plat_report_time'].';';
                }else if($v['small_plat_status']==0) {
                    if($v['small_plat_report_time']=='0000-00-00 00:00:00'){
                        $data['list'][$key]['small_palt_info'] = '小平台异常，未找到心跳';
                    }else {
                        $data['list'][$key]['small_palt_info'] ='小平台异常，失联时长'.$v['pla_lost_hour'].'小时';
                    }
                }else {
                    $data['list'][$key]['small_palt_info'] = '虚拟 小平台正常,上次上报时间'.$v['small_plat_report_time'].';';
                }
                if(empty($v['not_normal_box_num'])){
                    $data['list'][$key]['box_info'] = '机顶盒正常';
                }else if($v['not_normal_box_num']==1){
                    if($v['box_report_time'] =='0000-00-00 00:00:00'){
                        $v['box_lost_hour'] = '未找到心跳;';
                    }
                    $data['list'][$key]['box_info'] = '机顶盒异常1个;'.$v['box_lost_hour'];
                }else if($v['not_normal_box_num']>1){

                    $data['list'][$key]['box_info'] ='机顶盒异常'.$v['not_normal_box_num'].'个';

                }

                $count = count($error_info);
                if($count<$pageSize){
                    $data['isNextPage'] = 0;
                }else {
                    $data['isNextPage'] = 1;
                }

            }
        } else {
            $data = array();
        }
        $this->to_back($data);

    }
}

