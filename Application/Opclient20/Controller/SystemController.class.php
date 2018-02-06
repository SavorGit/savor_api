<?php
/**
 * @desc 运维端2.0系统状态
 * @author zhang.yingtao
 * @since  2018-01-16
 */
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class SystemController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':
                $this->is_verify = 0;
                $this->valid_fields = array('city_id'=>1000);
            break;
        }
        parent::_init_();
    }
    
    /**
     * @desc 系统状态页面
     */
    public function index(){
        $city_id = $this->params['city_id'];
        $where = $data =  array();
        //1.1当前在线酒楼 10分钟之内有心跳
        $m_heart_log = new \Common\Model\HeartLogModel();
        $end_time = date('Y-m-d H:i:s',strtotime('-10 minutes'));
        $where['a.last_heart_time'] = array('EGT',$end_time);
        $where['a.type'] = 1;
        $where['b.state'] = 1;
        $where['b.flag'] = 0;
        $where['a.hotel_id'] = array('gt',0);
        if($city_id) $where['b.area_id'] = $city_id;
        $online_hotel = $m_heart_log->getHotelList('a.hotel_id',$where,'','','a.hotel_id');
        $data['list']['heart']['update_time'] = date('Y-m-d H:i');
        $data['list']['heart']['hotel_online'] = count($online_hotel);  

        //1.2当前离线酒楼 
        $where = '1=1';
        $start_time = date('Y-m-d H:i:s',strtotime('-72 hours'));
        //$where .=" and last_heart_time>='".$start_time."'";
        $where .=" and a.last_heart_time<='".$end_time."'";
        $where .=" and a.type=1  and a.hotel_id>0 and  b.state=1 and b.flag=0";
        if($city_id) $where .=" and b.area_id=".$city_id;
        $not_online_hotel_all = $m_heart_log->getHotelList('a.hotel_id',$where,'','','a.hotel_id');
        $not_online_hotel_all_num = count($not_online_hotel_all);

        $data['list']['heart']['hotel_not_onlie'] = $not_online_hotel_all_num;
        //1.3当前离线酒楼  超过10分 但离线在72小时以内
        $where .=" and a.last_heart_time>='".$start_time."'";
        $not_online_hotel_1 = $m_heart_log->getHotelList('a.hotel_id',$where,'','','a.hotel_id');
        $not_online_hotel_1_num = count($not_online_hotel_1);
        
        //1.4离线超过72小时
        $where = '';
        $where .="  a.last_heart_time<='".$start_time."' and a.type=1 and a.hotel_id>0 and b.state=1 and b.flag=0 ";
        if($city_id) $where .=" and b.area_id=".$city_id;
        $not_online_hotel_2 = $m_heart_log->getHotelList('a.hotel_id',$where,'','','a.hotel_id');
        $not_online_hotel_2_num = count($not_online_hotel_2);
        $data['list']['heart']['hotel_10_72_not_onlie'] = "(离线小于72小时:$not_online_hotel_1_num   离线大于72小时:$not_online_hotel_2_num)";
        
        //2.1正常小平台
        $where ="";
        $where .="  a.last_heart_time>='".$start_time."' and a.type=1 and a.hotel_id>0 and b.state=1 and b.flag=0";
        if($city_id) $where .=" and b.area_id=".$city_id;
        $normal_small_plat_num = $m_heart_log->getHotelList('a.hotel_id',$where,'','','a.hotel_id');

        $data['list']['heart']['small_plat_normal_num'] = count($normal_small_plat_num);
       
        $hotel_box_type_str = $this->getNetHotelTypeStr();
        //2.2异常小平台
        $m_hotel = new \Common\Model\HotelModel();
        $where = '';
        $where = "  a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) and b.mac_addr !='' and b.mac_addr !='000000000000'";
        
        if($city_id) $where .=" and a.area_id=".$city_id;
        $hotel_all_num = $m_hotel->getHotelCountNums($where);
        $diif_count = $hotel_all_num - count($normal_small_plat_num);
        $diif_count = $diif_count>=0 ? $diif_count : 0;
        $data['list']['heart']['small_plat_not_normal_num'] = $diif_count;
        
        //正常酒楼 、异常酒楼
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        //$where = " a.id not in(7,53)  and a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) and b.mac_addr !='' and b.mac_addr !='000000000000'";
        $where = "  a.state=1 and a.flag =0 and a.hotel_box_type in($hotel_box_type_str) ";
        
        if($city_id) $where .=" and a.area_id=".$city_id;
        $hotel_list = $m_hotel->getHotelLists($where,'','','a.id');
        
        
        $normal_box_num = 0;
        $not_normal_box_num = 0;
        //print_r($hotel_list);exit;
        $m_black_list = new \Common\Model\BlacklistModel();
        foreach($hotel_list as $key=>$v){
            $flag = 0;
            $where = '';
            $where .=" 1 and room.hotel_id=".$v['id'].' and a.state=1 and a.flag =0 and room.flag=0 and room.state=1';
            $box_list = $m_box->getList( 'a.id, a.mac',$where);
            foreach($box_list as $ks=>$vs){
                $where = '';
                $where .=" 1 and hotel_id=".$v['id']." and type=2 and box_mac='".$vs['mac']."'";
                $where .="  and last_heart_time>='".$start_time."'";

                $b_counts = $m_black_list->countNums(array('box_id'=>$vs['id']));
                $rets  = $m_heart_log->getOnlineHotel($where,'hotel_id');
                if(empty($rets)){
                    if(empty($b_counts)){
                        $not_normal_box_num +=1;
                    }
                }else {
                    if(empty($b_counts)){
                        $normal_box_num +=1;
                    }                    
                }
            }   
        }

        //3.3黑名单
        
        $where = array();
        if($city_id) $where['b.area_id'] = $city_id;
        $black_box_num = $m_black_list->countNums($where);
        $data['list']['heart']['black_box_num'] = $black_box_num;
        //3.1正常机顶盒数量
        $data['list']['heart']['box_normal_num'] = $normal_box_num;
        //3.2异常机顶盒数量
        $data['list']['heart']['box_not_normal_num'] = $not_normal_box_num;

        $data['list']['heart']['remark'] = "在线指10分钟以内;离线指大于十分钟;异常指大于72小时";
        
        //3.1酒楼总数
        $all_hotel_box_type_arr = C('HOTEL_BOX_TYPE');
        $all_hotel_box_type_arr = array_keys($all_hotel_box_type_arr);
        $all_hotel_box_type_str = '';
        $space = '';
        foreach($all_hotel_box_type_arr as $v){
            $all_hotel_box_type_str .= $space . $v;
            $space = ','; 
        }
        $where = array();
        $where['flag'] = 0;
        $where['hotel_box_type'] = array('in',$all_hotel_box_type_str);
        
        if($city_id) $where['area_id'] = $city_id;
        $hotel_all_nums = $m_hotel->getHotelCount($where);
        $data['list']['hotel']['hotel_all_nums'] = $hotel_all_nums;
        
        //3.2正常酒楼
        $where = array();
        $where['flag'] = 0;
        $where['state'] = 1;
        $where['hotel_box_type'] = array('in',$all_hotel_box_type_str);
        if($city_id) $where['area_id'] = $city_id;
        $hotel_all_normal_nums = $m_hotel->getHotelCount($where);
        $data['list']['hotel']['hotel_all_normal_nums'] = $hotel_all_normal_nums;
        
        //3.3冻结酒楼
        $where = array();
        $where['flag'] = 0;
        $where['state'] = 2;
        $where['hotel_box_type'] = array('in',$all_hotel_box_type_str);
        if($city_id) $where['area_id'] = $city_id;
        $hotel_all_freeze_nums = $m_hotel->getHotelCount($where);
        $data['list']['hotel']['hotel_all_freeze_nums'] = $hotel_all_freeze_nums;
        
        $hotel_type_arr = array( array('name'=>'一代','ids'=>'1'),array('name'=>'二代','ids'=>'2'),
                                 array('name'=>'5G','ids'=>'3'),array('name'=>'三代','ids'=>'5,6'));
        
        foreach($hotel_type_arr as $key=> $v){
            $temp = array();
            $temp['name'] = $v['name'];
            //一代酒楼总数
            $where = array();
            $where['flag'] = 0;
            $where['hotel_box_type'] = array('in',$v['ids']);
            if($city_id) $where['area_id'] = $city_id;
            $hotel_all_nums = $m_hotel->getHotelCount($where);
            //$data['list']['hotel'][$key]['hotel_all_nums'] = $f_hotel_all_nums;
            $temp['hotel_all_nums'] = $hotel_all_nums;
            //一代酒楼正常总数
            $where = array();
            $where['flag'] = 0;
            $where['state'] = 1;
            $where['hotel_box_type'] = array('in',$v['ids']);
            if($city_id) $where['area_id'] = $city_id;
            $hotel_all_normal_nums = $m_hotel->getHotelCount($where);
            //$data['list']['hotel'][$key]['hotel_all_normal_nums'] = $f_hotel_all_normal_nums;
            $temp['hotel_all_normal_nums'] = $hotel_all_normal_nums;
            //一代酒楼冻结总数
            $where = array();
            $where['flag'] = 0;
            $where['state'] = 2;
            $where['hotel_box_type'] = array('in',$v['ids']);
            if($city_id) $where['area_id'] = $city_id;
            $hotel_all_freeze_nums = $m_hotel->getHotelCount($where);
            //$data['list']['hotel'][$key]['hotel_all_freeze_nums'] = $f_hotel_all_freeze_nums;
            $temp['hotel_all_freeze_nums'] = $hotel_all_freeze_nums;
            
            $data ['list']['hotel']['list'][] = $temp;
        }
        
        
        
        //4.1小平台总数
        $where = array();
        $where['a.flag'] = 0;
        $where['a.hotel_box_type'] = array('in',array(2,3,6));
        $where['b.mac_addr']  = array(array('neq',''),array('neq','000000000000'));
        //$where['b.mac_addr'] = array('neq','000000000000');
        if($city_id) $where['a.area_id'] = $city_id;
        
        $small_all_normal_nums = $m_hotel->getHotelCountNums($where);
        $data['list']['small']['all_nums'] = $small_all_normal_nums;
        
        //4.2小平台正常总数
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state'] = 1;
        $where['a.hotel_box_type'] = array('in',array(2,3,6));
        $where['b.mac_addr']  = array(array('neq',''),array('neq','000000000000'));
        //$where['b.mac_addr'] = array('neq','000000000000');
        if($city_id) $where['a.area_id'] = $city_id;
        $small_normal_nums = $m_hotel->getHotelCountNums($where);
        $data['list']['small']['normal_nums'] = $small_normal_nums;
        //4.3小平台冻结总数
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state'] = 2;
        $where['a.hotel_box_type'] = array('in',array(2,3,6));
        $where['b.mac_addr']  = array(array('neq',''),array('neq','000000000000'));
        //$where['b.mac_addr'] = array('neq','000000000000');
        if($city_id) $where['a.area_id'] = $city_id;
        $small_freeze_nums = $m_hotel->getHotelCountNums($where);
        $data['list']['small']['freeze_nums'] = $small_freeze_nums;
        
        $m_box = new \Common\Model\BoxModel();
        //5.1机顶盒总数
        $where = array();
        $where['a.flag'] = 0;

        $where['b.flag'] =0 ;
        $where['b.state'] = 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        $where['c.hotel_box_type'] = array('in',$all_hotel_box_type_str);
        if($city_id) $where['c.area_id'] = $city_id;
        $box_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['all_num'] = $box_all_num;
        
        //5.2机顶盒正常
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 1;
        $where['b.flag'] =0 ;
        $where['b.state'] = 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        $where['c.hotel_box_type'] = array('in',$all_hotel_box_type_str);
        if($city_id) $where['c.area_id'] = $city_id;
        $box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['normal_all_num'] = $box_normal_all_num;
        //5.2机顶盒报损
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 3;
        $where['b.flag'] =0 ;
        $where['b.state'] = 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        $where['c.hotel_box_type'] = array('in',$all_hotel_box_type_str);
        if($city_id) $where['c.area_id'] = $city_id;
        $box_break_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['break_all_num'] = $box_break_all_num;
        
        //5.2机顶盒冻结
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 2;
        $where['b.flag'] =0 ;
        $where['b.state'] = 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        $where['c.hotel_box_type'] = array('in',$all_hotel_box_type_str);
        if($city_id) $where['c.area_id'] = $city_id;
        $box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['freeze_all_num'] = $box_freeze_all_num;
        
        
        foreach($hotel_type_arr as $key=>$v){
            $temp = array();
            $temp['name'] = $v['name'];
            //总数
            $where = array();
            $where['a.flag'] = 0;
            
            $where['b.flag'] =0 ;
            $where['b.state'] = 1;
            $where['c.flag'] = 0;
            $where['c.state'] = 1;
            if($city_id) $where['c.area_id'] = $city_id;
            $where['c.hotel_box_type'] = array('in',$v['ids']);
            //$where['d.mac_addr'] =  array('neq','000000000000');
            $box_normal_all_num = $m_box->countBoxNums($where);
            //$data['list']['box']['f_all_num'] = $f_box_normal_all_num;
            $temp['box_all_num'] = $box_normal_all_num;
            //正常
            $where = array();
            $where['a.flag'] = 0;
            $where['a.state']= 1;
            $where['b.flag'] =0 ;
            $where['b.state'] = 1;
            $where['c.flag'] = 0;
            $where['c.state'] = 1;
            
            if($city_id) $where['c.area_id'] = $city_id;
            $where['c.hotel_box_type'] = array('in',$v['ids']);
            //$where['d.mac_addr'] =  array('neq','000000000000');
            $box_normal_all_num = $m_box->countBoxNums($where);
            //$data['list']['box']['f_normal_all_num'] = $f_box_normal_all_num;
            $temp['box_normal_all_num'] = $box_normal_all_num;
            
            //报损
            $where = array();
            $where['a.flag'] = 0;
            $where['a.state']= 3;
            $where['b.flag'] =0 ;
            $where['b.state'] = 1;
            $where['c.flag'] = 0;
            $where['c.state'] = 1;
            if($city_id) $where['c.area_id'] = $city_id;
            $where['c.hotel_box_type'] = array('in',$v['ids']);
            //$where['d.mac_addr'] =  array('neq','000000000000');
            $box_freeze_all_num = $m_box->countBoxNums($where);
            $temp['box_break_all_num'] = $box_freeze_all_num;
            
            //冻结
            $where = array();
            $where['a.flag'] = 0;
            $where['a.state']= 2;
            $where['b.flag'] =0 ;
            $where['b.state'] = 1;
            $where['c.flag'] = 0;
            $where['c.state'] = 1;
            if($city_id) $where['c.area_id'] = $city_id;
            $where['c.hotel_box_type'] = array('in',$v['ids']);
            //$where['d.mac_addr'] =  array('neq','000000000000');
            $box_freeze_all_num = $m_box->countBoxNums($where);
            $temp['box_freeze_all_num'] = $box_freeze_all_num;
            $data['list']['box']['list'][] = $temp;
        }
        
       /*  //一代总数
        $where = array();
        $where['a.flag'] = 0;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 1;
        $f_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['f_all_num'] = $f_box_normal_all_num;
        
        //一代正常
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 1;
        $f_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['f_normal_all_num'] = $f_box_normal_all_num;
        
        //一代报损
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 3;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 1;
        $f_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['f_freeze_all_num'] = $f_box_freeze_all_num;
        //一代冻结
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 2;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 1;
        $f_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['f_freeze_all_num'] = $f_box_freeze_all_num;
        
        //二代总数
        $where = array();
        $where['a.flag'] = 0;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 2;
        $s_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s_all_num'] = $s_box_normal_all_num;
        
        //二代正常
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 2;
        $s_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s_normal_all_num'] = $s_box_normal_all_num;
        
        //二代报损
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 3;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 2;
        $s_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s_freeze_all_num'] = $s_box_freeze_all_num;
        //二代冻结
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 2;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 2;
        $s_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s_freeze_all_num'] = $s_box_freeze_all_num;
        
        //二代5G总数
        $where = array();
        $where['a.flag'] = 0;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 3;
        $s5_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s5_all_num'] = $s5_box_normal_all_num;
        
        //二代5G正常
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 3;
        $s5_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s5_normal_all_num'] = $s5_box_normal_all_num;
        //二代5G报损
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 3;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 3;
        $s5_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s5_freeze_all_num'] = $s5_box_freeze_all_num;
        //二代5G冻结
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 2;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = 3;
        $s5_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['s5_freeze_all_num'] = $s5_box_freeze_all_num;
        
        //三代总数
        $where = array();
        $where['a.flag'] = 0;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = array('in',array(5,6));
        $t_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['t_all_num'] = $t_box_normal_all_num;
        //三代正常
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 1;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = array('in',array(5,6));
        $t_box_normal_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['t_normal_all_num'] = $t_box_normal_all_num;
        //三代报损
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 3;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = array('in',array(5,6));
        $t_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['t_freeze_all_num'] = $t_box_freeze_all_num;
        //三代冻结
        $where = array();
        $where['a.flag'] = 0;
        $where['a.state']= 2;
        $where['c.flag'] = 0;
        $where['c.state'] = 1;
        if($city_id) $where['c.area_id'] = $city_id;
        $where['c.hotel_box_type'] = array('in',array(5,6));
        $t_box_freeze_all_num = $m_box->countBoxNums($where);
        $data['list']['box']['t_freeze_all_num'] = $t_box_freeze_all_num; */
        
        
        
        $this->to_back($data);
    }
}