<?php
namespace Opclient\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class IndexController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':
                $this->is_verify = 0;
                $this->is_login = 0;
            break;
        }
        parent::_init_();
    }
    public function index(){
        
        $where = $data =  array();
        //当前在线酒楼 10分钟之内有心跳
        $m_heart_log = new \Common\Model\HeartLogModel();
        $end_time = date('Y-m-d H:i:s',strtotime('-10 minutes'));
        $where['last_heart_time'] = array('EGT',$end_time);
        $where['type'] = 1;
        $online_hotel = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $data['list'][] = '当前在线酒楼(10分钟内在线):'. count($online_hotel);
        
        //当前离线酒楼 超过10分钟 
        $where = '1=1';
        $start_time = date('Y-m-d H:i:s',strtotime('-72 hours'));
        //$where .=" and last_heart_time>='".$start_time."'";
        $where .=" and last_heart_time<='".$end_time."'";
        $where .=" and type=1";
        $not_online_hotel_all = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $not_online_hotel_all_num = count($not_online_hotel_all);
        //当前离线酒楼  超过10分 但离线再15小时以内
        $where .=" and last_heart_time>='".$start_time."'";
        $not_online_hotel_1 = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $not_online_hotel_1_num = count($not_online_hotel_1);
        
        //离线超过15小时
        $where = '';
        $where .="  last_heart_time<='".$start_time."' and type=1";
        $not_online_hotel_2 = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $not_online_hotel_2_num = count($not_online_hotel_2);
        
        
        $data['list'][] = '当前离线酒楼(离线超过10分钟)'.$not_online_hotel_all_num.' (其中'.$not_online_hotel_1_num.'个酒楼离线72小时以内，'.$not_online_hotel_2_num.'个酒楼离线大于72小时)';
        //酒楼总数
        $m_hotel = new \Common\Model\HotelModel();
        $where = '';
        /* $where['a.id'] = array(array('not in',array('7','53')));
        $where['a.state'] = 1;
        $where['a.hotel_box_type'] = array('in','2,3');
        $where['b.mac_addr'] = array('neq',array('','000000000000')); */
        $hotel_box_type_str = $this->getNetHotelTypeStr();
        $where = " a.id not in(7,53)  and a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) and b.mac_addr !='' and b.mac_addr !='000000000000'";
        $hotel_all_num = $m_hotel->getHotelCountNums($where);
        $data['list'][] = '酒楼总数:'.$hotel_all_num;
        //print_r($data);exit;
        //正常酒楼 、异常酒楼
        $m_box = new \Common\Model\BoxModel();
        $where = array();

        /* $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['a.hotel_box_type'] = array('in','2,3');
        $where['b.mac_addr'] = array('neq',''); */
        
        $where = " a.id not in(7,53)  and a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) and b.mac_addr !='' and b.mac_addr !='000000000000'";
        $hotel_list = $m_hotel->getHotelLists($where,'','','a.id');
        
        $normal_hotel_num = 0;
        $not_normal_hotel_num = 0;
        
        $normal_small_plat_num = 0;
        $not_normal_small_plat_num = 0;
        
        $normal_box_num = 0;
        $not_normal_box_num = 0;
        foreach($hotel_list as $key=>$v){
            
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
                        $flag = 1;
                        //$not_normal_hotel_num +=1;
                        //break; 
                     }else {
                         $normal_box_num +=1;
                     }
                }
                if($flag ==1){
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
                    $where .="  and last_heart_time>='".$start_time."'";
                     
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                    if(empty($rets)){
                        $not_normal_box_num +=1;
                        
                    }else {
                        $normal_box_num +=1;
                    }
                }
                
                $not_normal_small_plat_num +=1;
                $not_normal_hotel_num +=1;
                
                
            }
            
            
        }
        /* $m_hote_ext = new \Common\Model\HotelExtModel();
        $map = array();
        $map['mac_addr'] = '000000000000';
        
        
        $counts = $m_hote_ext->where($map)->count(); */
        $counts = 0;
        $normal_hotel_num = $hotel_all_num - $not_normal_hotel_num;
        $data['list'][] = '正常酒楼:'. $normal_hotel_num;               //正常酒楼
        $data['list'][] = '异常酒楼:'. $not_normal_hotel_num;           //异常酒楼
        $not_normal_small_plat_num -= $counts;
        $data['list'][] = '异常小平台:'. $not_normal_small_plat_num;  //异常小平台
        
        $m_black_list = new \Common\Model\BlacklistModel();
        $black_box_num = $m_black_list->countBlackBoxNum();
        
        $real_normal_box_num  = $not_normal_box_num - $black_box_num;
        if($real_normal_box_num<0){
            $real_normal_box_num = 0;
        }
        //$data['list'][] = '异常机顶盒:'. ($not_normal_box_num - $black_box_num);            //异常机顶盒
        $data['list'][] = '异常机顶盒:'.$real_normal_box_num;
        $data['list'][] = '黑名单机顶盒:'.$black_box_num;
        $data['list'][] = '更新时间:'. date('Y-m-d H:i:s');
        $data['remark'] = '注:异常为心跳失联超过72小时以上';
        $this->to_back($data);
    }
    public function testinfo(){
    
        $where = $data =  array();
        //当前在线酒楼 10分钟之内有心跳
        $m_heart_log = new \Common\Model\HeartLogModel();
        $end_time = date('Y-m-d H:i:s',strtotime('-10 minutes'));
        $where['last_heart_time'] = array('EGT',$end_time);
        $where['type'] = 1;
        $online_hotel = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $data['list'][] = '当前在线酒楼(10分钟内在线):'. count($online_hotel);
    
        //当前离线酒楼 超过10分钟
        $where = '1=1';
        $start_time = date('Y-m-d H:i:s',strtotime('-72 hours'));
        //$where .=" and last_heart_time>='".$start_time."'";
        $where .=" and last_heart_time<='".$end_time."'";
        $where .=" and type=1";
        $not_online_hotel_all = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $not_online_hotel_all_num = count($not_online_hotel_all);
        //当前离线酒楼  超过10分 但离线再15小时以内
        $where .=" and last_heart_time>='".$start_time."'";
        $not_online_hotel_1 = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $not_online_hotel_1_num = count($not_online_hotel_1);
    
        //离线超过15小时
        $where = '';
        $where .="  last_heart_time<='".$start_time."' and type=1";
        $not_online_hotel_2 = $m_heart_log->getOnlineHotel($where,'hotel_id');
        $not_online_hotel_2_num = count($not_online_hotel_2);
    
    
        $data['list'][] = '当前离线酒楼(离线超过10分钟)'.$not_online_hotel_all_num.' (其中'.$not_online_hotel_1_num.'个酒楼离线72小时以内，'.$not_online_hotel_2_num.'个酒楼离线大于72小时)';
        //酒楼总数
        $m_hotel = new \Common\Model\HotelModel();
        $where = '';
        /* $where['a.id'] = array(array('not in',array('7','53')));
         $where['a.state'] = 1;
         $where['a.hotel_box_type'] = array('in','2,3');
         $where['b.mac_addr'] = array('neq',array('','000000000000')); */
        $hotel_box_type_str = $this->getNetHotelTypeStr();
        $where = " a.id not in(7,53)  and a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) and b.mac_addr !='' and b.mac_addr !='000000000000'";
        $hotel_all_num = $m_hotel->getHotelCountNums($where);
        $data['list'][] = '酒楼总数:'.$hotel_all_num;
        //print_r($data);exit;
        //正常酒楼 、异常酒楼
        $m_box = new \Common\Model\BoxModel();
        $where = array();
    
        /* $where['a.state'] = 1;
         $where['a.flag']  = 0;
         $where['a.hotel_box_type'] = array('in','2,3');
        $where['b.mac_addr'] = array('neq',''); */
    
        $where = " a.id not in(7,53)  and a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) and b.mac_addr !='' and b.mac_addr !='000000000000'";
        $hotel_list = $m_hotel->getHotelLists($where,'','','a.id');
    
        $normal_hotel_num = 0;
        $not_normal_hotel_num = 0;
    
        $normal_small_plat_num = 0;
        $not_normal_small_plat_num = 0;
    
        $normal_box_num = 0;
        $not_normal_box_num = 0;
        $box_tmp_arr = array();
        foreach($hotel_list as $key=>$v){
    
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
                        $flag = 1;
                        //$not_normal_hotel_num +=1;
                        //break;
                        $box_tmp_arr[] = $vs['mac'];
                    }else {
                        $normal_box_num +=1;
                    }
                }
                if($flag ==1){
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
                    $where .="  and last_heart_time>='".$start_time."'";
                     
                    $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                    if(empty($rets)){
                        $not_normal_box_num +=1;
                        $box_tmp_arr[] = $vs['mac'];
                    }else {
                        $normal_box_num +=1;
                    }
                }
    
                $not_normal_small_plat_num +=1;
                $not_normal_hotel_num +=1;
    
    
            }
    
    
        }
        foreach($box_tmp_arr as $v){
            echo $v."\n";
        }
        exit;
        /* $m_hote_ext = new \Common\Model\HotelExtModel();
         $map = array();
         $map['mac_addr'] = '000000000000';
    
    
         $counts = $m_hote_ext->where($map)->count(); */
        $counts = 0;
        $normal_hotel_num = $hotel_all_num - $not_normal_hotel_num;
        $data['list'][] = '正常酒楼:'. $normal_hotel_num;               //正常酒楼
        $data['list'][] = '异常酒楼:'. $not_normal_hotel_num;           //异常酒楼
        $not_normal_small_plat_num -= $counts;
        $data['list'][] = '异常小平台:'. $not_normal_small_plat_num;  //异常小平台
    
        $m_black_list = new \Common\Model\BlacklistModel();
        $black_box_num = $m_black_list->countBlackBoxNum();
    
        $data['list'][] = '异常机顶盒:'. ($not_normal_box_num - $black_box_num);            //异常机顶盒
        $data['list'][] = '黑名单机顶盒:'.$black_box_num;
        $data['list'][] = '更新时间:'. date('Y-m-d H:i:s');
        $data['remark'] = '注:异常为心跳失联超过72小时以上';
        $this->to_back($data);
    }
}