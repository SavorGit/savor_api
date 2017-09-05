<?php
namespace Opclient\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class ErrorReportController extends BaseController{ 
    
    /**
     * @构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'report':
                $this->is_verify = 0;
                break;
            case 'getList':
                $this->is_verify = 0;
                $this->valid_fields = array('id'=>1000,'pageSize'=>1000);
                break;
            case 'getErrorDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('id'=>1001,'pageSize'=>1000,'hotel_id'=>1000);
                break;
            
        }
        parent::_init_();
    }
    
    /**
     * @desc 
     */
    public function report(){
        //酒楼总数
        $m_hotel = new \Common\Model\HotelModel();
        $where = array();
        $where['state'] = 1;
        $where['hotel_box_type'] = array('in','2,3');
        $hotel_all_num = $m_hotel->getHotelCount($where);
        
        //正常酒楼 、异常酒楼
        $end_time = date('Y-m-d H:i:s',strtotime('-10 minutes'));
        $m_heart_log = new \Common\Model\HeartLogModel();
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        
        $where['state'] = 1;
        $where['hotel_box_type'] = array('in','2,3');
        $hotel_list = $m_hotel->getHotelList($where,'','','id');
        
        $normal_hotel_num = 0;
        $not_normal_hotel_num = 0;
        
        $normal_small_plat_num = 0;
        $not_normal_small_plat_num = 0;
        
        $normal_box_num = 0;
        $not_normal_box_num = 0;
        $not_normal_hotel_arr = array();
        foreach($hotel_list as $key=>$v){
        
            $where = '';
            $where .=" 1 and hotel_id=".$v['id']." and type=1";
            $where .="  and last_heart_time>='".$end_time."'";
            $ret = $m_heart_log->getOnlineHotel($where,'hotel_id');
            if(!empty($ret)){//小平台有15小时内的心跳 判断机顶盒是否有心跳
                $flag = 0;
                //$normal_hotel_num +=1;
                $where = '';
                $where .=" 1 and room.hotel_id=".$v['id'].' and a.state=1 and a.flag =0';
                $box_list = $m_box->getList( 'a.id, a.mac',$where);
                foreach($box_list as $ks=>$vs){
                    $where = '';
                    $where .=" 1 and hotel_id=".$v['id']." and type=2 and box_mac='".$vs['mac']."'";
                    $where .="  and last_heart_time>='".$end_time."'";
                     
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                    if(empty($rets)){
                        $not_normal_box_num +=1;
                        $flag = 1;
                        //$not_normal_hotel_num +=1;
                        //break;
                    }else {
                        $normal_box_num +=1;
                    }
                }
                if($flag ==1){
                    $not_normal_hotel_arr[] = $v['id'];
                    $not_normal_hotel_num +=1;
                }
            }else {//小平台没有15小时内的心跳 判断机顶盒是否有心跳
                $flag = 0;
                 
                $where = '';
                $where .=" 1 and room.hotel_id=".$v['id'].' and a.state=1 and a.flag =0';
                $box_list = $m_box->getList( 'a.id, a.mac',$where);
                foreach($box_list as $ks=>$vs){
                    $where = '';
                    $where .=" 1 and hotel_id=".$v['id']." and type=2 and box_mac='".$vs['mac']."'";
                    $where .="  and last_heart_time>='".$end_time."'";
                     
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                    if(empty($rets)){
                        $not_normal_box_num +=1;
        
                    }else {
                        $normal_box_num +=1;
                    }
                }
        
                $not_normal_small_plat_num +=1;
                $not_normal_hotel_num +=1;
                $not_normal_hotel_arr[] = $v['id'];
            }
        }
        $data['hotel_all_num']            = $hotel_all_num;               //酒楼总数
        $data['not_normal_hotel_num']     = $not_normal_hotel_num;        //异常酒楼
        $data['not_normal_smallplat_num'] = $not_normal_small_plat_num;   //异常小平台
        $data['not_normal_box_num']       = $not_normal_box_num;          //异常机顶盒
        $data['not_normal_hotel_ids']          = json_encode($not_normal_hotel_arr);
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $ret = $m_hotel_error_report->addInfo($data);
        if($ret){
            echo "ok";
        }else {
            echo "not ok";
        }
    }
    /**
     * @desc 异常报告列表
     */
    public function getList(){
        $id = $this->params['id'];
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $where = array();
        if(!empty($id)){
            $where['id'] = array('LT',$id);
        }   
        $where['is_push'] = 1;
        $fields = 'id,hotel_all_num,not_normal_hotel_num,not_normal_smallplat_num,not_normal_box_num,create_time';
        $order = ' id desc';
        $limit = ' limit '.$pageSize;
        $list = $m_hotel_error_report->getList($fields,$where,$order,$limit);
        
        $data = array();
        $count = count($list);
        $last_error_id = $list[$count-1]['id'];
        
        foreach($list as $key=>$v){
            $report_date = date('m-d',strtotime($v['create_time']));
            $report_time = intval(date('H',strtotime($v['create_time'])));
            $data['list'][$key]['id'] = $v['id'];
            $data['list'][$key]['info'] = '截止到'.$report_date.' '.$report_time.'点，共有'.$v['hotel_all_num'].'家酒楼('.$v['not_normal_hotel_num'].'家酒楼异常,'.
                            $v['not_normal_smallplat_num'].'个小平台失联超过15小时,'.$v['not_normal_box_num'].'个机顶盒失联超过15小时)';
            $data['list'][$key]['date'] = $v['create_time'];
        }
        
        unset($list);
        $where = array();
        $where['id'] = array('LT',$last_error_id);
        $ret = $m_hotel_error_report->where($where)->count();
        if(empty($ret)){
            $data['isNextPage'] = 0;
        }else {
            $data['isNextPage'] = 1;
        }
        $this->to_back($data);
    }
    
    
    
    /**
     * @desc 异常详情
     */
    public function getErrorDetail(){
        $id = $this->params['id'];
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $hotel_id = $this->params['hotel_id'];
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $fields = '*';
        $where['id'] = $id;
        $where['is_push'] =1;
        $info = $m_hotel_error_report->getInfo($fields,$where);
        
        
        if(empty($info)){
            $this->to_back('30003');
        }
        $data = array();
        $hotel_arr = json_decode($info['not_normal_hotel_ids']);
        
        if(empty($hotel_id)){
            //print_r($info);exit;
            $report_date = date('m-d',strtotime($info['create_time']));
            $report_time = intval(date('H',strtotime($info['create_time'])));
            $data['info'] = '截止到'.$report_date.' '.$report_time.'点，共有'.$info['hotel_all_num'].'家酒楼('.$info['not_normal_hotel_num'].'家酒楼异常,'.
                            $info['not_normal_smallplat_num'].'个小平台失联超过15小时,'.$info['not_normal_box_num'].'个机顶盒失联超过15小时)';
            $data['date'] = $info['create_time'];
            
            $count = count($hotel_arr);
            
            $curr_hotel_arr = array_slice($hotel_arr, 0,$pageSize);
            
            
        }else {
            if(!in_array($hotel_id,$hotel_arr)){
                $this->to_back('30004');
            }
            $last_key = array_search($hotel_id,$hotel_arr);
            $curr_key = $last_key+1;
            $curr_hotel_arr = array_slice($hotel_arr, $curr_key,$pageSize);    
        }
        $m_hotel = new \Common\Model\HotelModel();
        foreach($curr_hotel_arr as $v){
            $hotel_info = $m_hotel->getOneById('name',$v);   //酒楼名称
            
        }
        $this->to_back($data);
    }
}