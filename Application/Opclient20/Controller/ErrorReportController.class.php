<?php
namespace Opclient20\Controller;
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
            case 'getNewErrorDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('error_id'=>1001,'pageSize'=>1000,'pageNum'=>1000);
                break;
            
        }
        parent::_init_();
    }

    /**
     * @desc 异常报告列表
     */
    public function getList(){
        $heart_loss_hours = C('HEART_LOSS_HOURS');
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
                            $v['not_normal_smallplat_num'].'个小平台失联超过'.$heart_loss_hours.'小时,'.$v['not_normal_box_num'].'个机顶盒失联超过'.$heart_loss_hours.'小时)';
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


    /*
     * 新的异常详情
     */
    public function getNewErrorDetail(){
        $heart_loss_hours = C('HEART_LOSS_HOURS');
        $id = $this->params['error_id'];    //异常报告主键id
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $pageNum= $this->params['pageNum'] ? $this->params['pageNum'] :1;
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $fields = '*';
        $where['id'] = $id;
        $traceinfo = $this->traceinfo;
        $clientname = $traceinfo['clientname'];
        if($clientname=='android'){
            $where['is_push'] = array('in','1,2,3');
        }else if($clientname =='ios'){
            $where['is_push'] = array('in','1,3,2');
        }

        $info = $m_hotel_error_report->getInfo($fields,$where);


        if(empty($info)){
            $this->to_back('30003');
        }
        $data = array();
        if(empty($detail_id)){
            //print_r($info);exit;
            $report_date = date('m-d',strtotime($info['create_time']));
            $report_time = intval(date('H',strtotime($info['create_time'])));
            $data['info'] = '截止到'.$report_date.' '.$report_time.'点，共有'.$info['hotel_all_num'].'家酒楼('.$info['not_normal_hotel_num'].'家酒楼异常,'.
                $info['not_normal_smallplat_num'].'个小平台失联超过'.$heart_loss_hours.'小时,'.$info['not_normal_box_num'].'个机顶盒失联超过'.$heart_loss_hours.'小时)';
            $data['date'] = $info['create_time'];

        }else {

        }
        //获取数据
        $fileds = 'a.*';
        $order = ' a.small_plat_status asc, a.pla_lost_hour desc,

        a.not_normal_box_num desc,a.box_lost_hour desc,a.id desc';
        $start  = ($pageNum-1)*$pageSize;
        $hotelUnModel = new \Common\Model\HotelUnusualModel();
        
        $h_type = C('HEART_HOTEL_BOX_TYPE');
        $h_type = array_keys($h_type);
        $h_type = implode(',', $h_type);
        
        $where = '1=1 and a.small_plat_status != 2 and hotel.state=1 and hotel.flag=0 and hotel.hotel_box_type in('.$h_type.')';
        //$error_info = $hotelUnModel->getList($fileds, $where, $order,$start, $pageSize);
        $error_info = $hotelUnModel->getErrHotelList($fileds, $where, $order,$start, $pageSize);
        
        $m_hotel = new \Common\Model\HotelModel();
        foreach($error_info as $key=>$v){
            $data['list'][$key]['hotel_id'] = $v['hotel_id'];
            $hotel_info = $m_hotel->getOneById('name',$v['hotel_id']);
            if($hotel_info) {
                $hname = $hotel_info['name'];
            } else {
                $hname = 'xisiid';
            }
            $data['list'][$key]['hotel_info'] = $hname.' 共'.$v['box_num'].'个版位';
            $data['list'][$key]['hotel_name'] = $hname;
            if($v['small_plat_status']==1){
                $data['list'][$key]['small_palt_info'] = '小平台正常,上次上报时间'.$v['small_plat_report_time'].';';
            }else {
                if($v['small_plat_report_time']=='0000-00-00 00:00:00'){
                    $data['list'][$key]['small_palt_info'] = '小平台异常，未找到心跳';
                }else {
                    $data['list'][$key]['small_palt_info'] ='小平台异常，失联时长'.$v['pla_lost_hour'].'小时';
                }
            }
            if(empty($v['not_normal_box_num'])){
                $data['list'][$key]['box_info'] = '机顶盒正常';
            }else if($v['not_normal_box_num']==1){
                if($v['box_report_time'] =='0000-00-00 00:00:00'){
                    $v['box_lost_hour'] = '未找到心跳;';
                }
                $data['list'][$key]['box_info'] = '机顶盒异常1个;'.'失联时长'.$v['box_lost_hour'].'小时';
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

        $this->to_back($data);
    }
    
    /**
     * @desc 异常详情
     */
    public function getErrorDetail(){
        $heart_loss_hours = C('HEART_LOSS_HOURS');
        $id = $this->params['error_id'];    //异常报告主键id
        $pageSize = $this->params['pageSize'] ? $this->params['pageSize'] :15;
        $detail_id = $this->params['detail_id'];     //异常报告详情id
        $m_hotel_error_report = new \Common\Model\HotelErrorReportModel();
        $fields = '*';
        $where['id'] = $id;
        $traceinfo = $this->traceinfo;
        $clientname = $traceinfo['clientname'];
        if($clientname=='android'){
            $where['is_push'] = array('in','1,2,3');
        }else if($clientname =='ios'){
            $where['is_push'] = array('in','1,3,2');
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
                            $info['not_normal_smallplat_num'].'个小平台失联超过'.$heart_loss_hours.'小时,'.$info['not_normal_box_num'].'个机顶盒失联超过'.$heart_loss_hours.'小时)';
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
        $m_box = new \Common\Model\BoxModel();
        foreach($detail_list as $key=>$v){
            $data['list'][$key]['detail_id'] = $v['id'];
            $data['list'][$key]['hotel_id'] = $v['hotel_id'];
            
            $hotel_info = $m_hotel->getOneById('name',$v['hotel_id']);
            
            //$rets = $m_hotel->getStatisticalNumByHotelId($v['hotel_id'],'tv');
            $rets = $m_box->getTvNumsByHotelid($v['hotel_id']);
            //$rets  = $m_tv->getTvNumsByHotelid($v['hotel_id']);
            $data['list'][$key]['hotel_info'] = $hotel_info['name'].' 共'.$rets.'个版位';
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
                /* if($v['box_report_time'] =='0000-00-00 00:00:00'){
                    $data['list'][$key]['box_info'] ='机顶盒异常'.$v['not_normal_box_num'].'个;未找到心跳';
                }else {
                    $data['list'][$key]['box_info'] ='机顶盒异常'.$v['not_normal_box_num'].'个';
                } */
                $data['list'][$key]['box_info'] ='机顶盒异常'.$v['not_normal_box_num'].'个';
                
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