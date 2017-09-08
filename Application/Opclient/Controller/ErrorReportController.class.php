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
                $this->valid_fields = array('error_id'=>1001,'pageSize'=>1000,'detail_id'=>1000);
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
        $start_time = date('Y-m-d H:i:s',strtotime('-15 hours'));
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
            $small_plat_status = 1;
            $crr_box_not_normal_num = 0;
            $box_last_report_time = '';
            
            $where = '';
            $where .=" 1 and hotel_id=".$v['id']." and type=1";
            $where .="  and last_heart_time>='".$start_time."'";
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
                    $where .="  and last_heart_time>='".$start_time."'";
                     
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                    if(empty($rets)){
                        $not_normal_box_num +=1;
                        $crr_box_not_normal_num +=1;
                        $flag = 1;
                        //$not_normal_hotel_num +=1;
                        //break;
                    }else {
                        $normal_box_num +=1;
                    }
                    $where = '';
                    $where .=" 1 and hotel_id=".$v['id']." and type=2 and box_mac='".$vs['mac']."'";
                    $rets  = $m_heart_log->getOnlineHotel($where,'last_heart_time');
                    $box_last_report_time = strtotime($box_last_report_time);
                    if(!empty($rets)){
                        $crr_box_report_time = strtotime($rets[0]['last_heart_time']);
                        if($crr_box_report_time>$box_last_report_time){
                            $box_last_report_time = $crr_box_report_time;
                        }
                    }
                    $box_last_report_time = date('Y-m-d H:i:s',$box_last_report_time);
                }
                
                if($flag ==1){
                    $not_normal_hotel_arr[] = $v['id'];
                    $not_normal_hotel_num +=1;
                }
            }else {//小平台没有15小时内的心跳 判断机顶盒是否有心跳
                $small_plat_status = 0; 
                $flag = 0;
                $where = '';
                $where .=" 1 and room.hotel_id=".$v['id'].' and a.state=1 and a.flag =0';
                $box_list = $m_box->getList( 'a.id, a.mac',$where);
                foreach($box_list as $ks=>$vs){
                    $where = '';
                    $where .=" 1 and hotel_id=".$v['id']." and type=2 and box_mac='".$vs['mac']."'";
                    $where .="  and last_heart_time>='".$start_time."'";
                     
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                    if(empty($rets)){
                        $not_normal_box_num +=1;
                        $crr_box_not_normal_num +=1;
        
                    }else {
                        $normal_box_num +=1;
                    }
                    $where = '';
                    $where .=" 1 and hotel_id=".$v['id']." and type=2 and box_mac='".$vs['mac']."'";
                    $rets  = $m_heart_log->getOnlineHotel($where,'last_heart_time');
                    $box_last_report_time = strtotime($box_last_report_time);
                    if(!empty($rets)){
                        $crr_box_report_time = strtotime($rets[0]['last_heart_time']);
                        if($crr_box_report_time>$box_last_report_time){
                            $box_last_report_time = $crr_box_report_time;
                        }
                    }
                    $box_last_report_time = date('Y-m-d H:i:s',$box_last_report_time);
                }
        
                $not_normal_small_plat_num +=1;
                $not_normal_hotel_num +=1;
                $not_normal_hotel_arr[] = $v['id'];
            }
            $rets = $m_hotel->getStatisticalNumByHotelId($v['id'],'tv');
            $result[$key]['hotel_id'] = $v['id'];
            $result[$key]['tv_num'] = $rets['tv_num'];
            $result[$key]['small_plat_status'] = $small_plat_status;
            $where = array();
            $where['hotel_id'] = $v['id'];
            $where['type']  =1;
            
            $dt = $m_heart_log->getInfo('last_heart_time',$where);
            if(!empty($dt)){
                $result[$key]['small_plat_report_time'] = $dt['last_heart_time'];
            }else {
                $result[$key]['small_plat_report_time'] = '';
            }
            $result[$key]['not_normal_box_num'] = $crr_box_not_normal_num;
            if($box_last_report_time =='1970-01-01 08:00:00'){
                $box_last_report_time = '';
            }
            $result[$key]['box_report_time'] = $box_last_report_time;
            $result[$key]['create_time'] = date('Y-m-d H:i:s');
        }
        
        $data['hotel_all_num']            = $hotel_all_num;               //酒楼总数
        $data['not_normal_hotel_num']     = $not_normal_hotel_num;        //异常酒楼
        $data['not_normal_smallplat_num'] = $not_normal_small_plat_num;   //异常小平台
        $data['not_normal_box_num']       = $not_normal_box_num;          //异常机顶盒
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $id = $m_hotel_error_report->addInfo($data);
        if($id){
            $m_hotel_error_report_detail = new \Common\Model\HotelErrorReportDetailModel();
            
            foreach($result as $key=> $v){
                $result[$key]['error_id'] = $id;
                
            }
           $m_hotel_error_report_detail->addInfo($result,2);
           
           echo 'OK';
        }else {
           echo 'NOT OK';   
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
        $fields = '*';
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
        $id = $this->params['error_id'];    //异常报告主键id
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $detail_id = $this->params['detail_id'];     //异常报告详情id
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $fields = '*';
        $where['id'] = $id;
        $traceinfo = $this->traceinfo;
        $clientname = $traceinfo['clientname'];
        if($clientname=='android'){
            $where['is_push'] = array('in','1,2');
        }else if($clientname =='ios'){
            $where['is_push'] = array('in','1,3');
        }
        
        $info = $m_hotel_error_report->getInfo($fields,$where);
        
        
        if(empty($info)){
            $this->to_back('30003');
        }
        $data = array();
       
        $m_hotel_error_report_detial = new \Common\Model\HotelErrorReportDetailModel();
        if(empty($detail_id)){
            //print_r($info);exit;
            $report_date = date('m-d',strtotime($info['create_time']));
            $report_time = intval(date('H',strtotime($info['create_time'])));
            $data['info'] = '截止到'.$report_date.' '.$report_time.'点，共有'.$info['hotel_all_num'].'家酒楼('.$info['not_normal_hotel_num'].'家酒楼异常,'.
                            $info['not_normal_smallplat_num'].'个小平台失联超过15小时,'.$info['not_normal_box_num'].'个机顶盒失联超过15小时)';
            $data['date'] = $info['create_time'];
            
            
            
            
            $fileds = '*';
            $where = ' 1 and error_id='.$id;
            $order = ' id asc';
            $limit  = $pageSize;
            
            $detail_list = $m_hotel_error_report_detial->getList($fileds,$where,$order,$limit);
            
            
        }else {
            $fields = '*';
            $where = ' 1 and error_id='.$id.' and id>'.$detail_id;
            $order = ' id asc';
            $limit = $pageSize;
            $detail_list = $m_hotel_error_report_detial->getList($fileds,$where,$order,$limit);
        }
        $m_hotel = new \Common\Model\HotelModel();
        foreach($detail_list as $key=>$v){
            $data['list'][$key]['detail_id'] = $v['id'];
            $data['list'][$key]['hotel_id'] = $v['hotel_id'];
            
            $hotel_info = $m_hotel->getOneById('name',$v['hotel_id']);
            
            $rets = $m_hotel->getStatisticalNumByHotelId($v['hotel_id'],'tv');
            $data['list'][$key]['hotel_info'] = $hotel_info['name'].' 共'.$rets['tv_num'].'个版位';
            $data['list'][$key]['hotel_name'] = $hotel_info['name'];
            if($v['small_plat_status']==1){
                $data['list'][$key]['small_palt_info'] = '小平台正常,上次上报时间'.$v['small_plat_report_time'].';';
            }else {
                
                if($v['small_plat_report_time']=='0000-00-00 00:00:00'){
                    $data['list'][$key]['small_palt_info'] = '小平台异常，未找到心跳';
                }else {
                    $diff_time = strtotime($v['create_time']) - strtotime($v['small_plat_report_time']);
                    $diff_hours = floor($diff_time/3600);
                    $data['list'][$key]['small_palt_info'] ='小平台异常，失联时长'.$diff_hours.'小时';
                }
            }
            if(empty($v['not_normal_box_num'])){
                $data['list'][$key]['box_info'] = '机顶盒正常';
            }else if($v['not_normal_box_num']==1){
                if($v['box_report_time'] =='0000-00-00 00:00:00'){
                    $diff_hours = '未找到心跳;';
                }else {
                    $diff_time = strtotime($v['create_time']) - strtotime($v['box_report_time']);
                    $diff_hours = floor($diff_time/3600);
                    $diff_hours = '失联时长'.$diff_hours.'小时';
                }
                
                $data['list'][$key]['box_info'] = '机顶盒异常1个;'.$diff_hours; 
            }else if($v['not_normal_box_num']>1){
                if($v['box_report_time'] =='0000-00-00 00:00:00'){
                    $data['list'][$key]['box_info'] ='机顶盒异常'.$v['not_normal_box_num'].'个;未找到心跳';
                }else {
                    $data['list'][$key]['box_info'] ='机顶盒异常'.$v['not_normal_box_num'].'个'.'最后上报时间 '.$v['box_report_time'];
                }
                
            }
            
            $count = count($detail_list);
            
            if($count<$pageSize){
                $data['isNextPage'] = 0;
            }else {
                $last_detail_id = $detail_list[$count-1]['id'];
                $where = array();
                $where['error_id'] = $id;
                $where['id'] = array('GT',$last_detail_id);
                $order = ' id asc';
                $info = $m_hotel_error_report_detial->where($where)->order($order)->find();
                if(!empty($info)){
                    $data['isNextPage'] = 1;
                }else {
                    $data['isNextPage'] = 0;
                }
            }
            
        }
       
        $this->to_back($data);
    }
  
}