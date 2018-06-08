<?php
/**
 * @desc 广告到达情况
 * @since 2018-06-08
 * @author zhang.yingtao
 */
namespace Small\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;

class AdsArriveSummaryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'genHistorySummary':
                $this->is_verify = 1;
                $this->valid_fields=array('date'=>'1001');
                break;
        }
    }
    /**
     * @desc 生成广告到达情况总结历史数据
     */
    public function genHistorySummary(){
        
        $result = array();
        $arrive_date = $this->params['date'];
        //网络机顶盒数
        $hotel_box_type_arr = C('heart_hotel_box_type');
        $hotel_box_type_arr = array_keys($hotel_box_type_arr);
        $space = '';
        $hotel_box_type_str = '';
        foreach($hotel_box_type_arr as $key=>$v){
            $hotel_box_type_str .= $space .$v;
            $space = ',';
        }
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['c.state'] = 1;
        $where['c.flag']  = 0;
        $where['a.state']   = 1;
        $where['a.flag']   = 0;
        $where['c.hotel_box_type'] = array('in',$hotel_box_type_str);
        $net_box_nums = $m_box->countBoxNums($where);
        $result['net_box_nums'] = $net_box_nums;
        //在投广告数
        $m_pub_ads = new \Common\Model\PubAdsModel();
        $where = array();
        $now_date = date('Y-m-d H:i:s');
        $yesterday_end_time = date('Y-m-d 23:59:59',strtotime($arrive_date));
        $yesterday_start_time = date('Y-m-d 00:00:00',strtotime($arrive_date));
        $where['a.start_date'] = array('lt',$yesterday_end_time);
        $where['a.end_date']   = array('gt',$yesterday_start_time);
        $where['a.state']      = array('neq',2);
        //$where['a.id']         = array('not in','115,116,117,118,119,120,121,122');
        $online_ads_nums = $m_pub_ads->countNums($where);
        $result['online_ads_nums'] = $online_ads_nums;
        
        //北上广深数据统计
        $m_statistics_box_media_arrive = new \Common\Model\Statisticses\BoxMediaArriveModel();
        $m_area = new \Common\Model\AreaModel();
        $area_list = $m_area->getHotelAreaList();
        $all_ads_arrive_rate = 0;
        $all_net_box_nums = 0;
        $jisuan_arrive_box_num = 0;
        $jisuan_all_area_pub_box_num = 0;
        
        foreach($area_list as $key=>$v){
            //在投广告个数
            $sql ="SELECT count(distinct pubbox.box_id) boxnum,pubbox.`pub_ads_id`
                   FROM savor_pub_ads_box pubbox
                   LEFT JOIN savor_pub_ads ads ON pubbox.`pub_ads_id`=ads.`id`
                   LEFT JOIN savor_box box ON pubbox.`box_id`=box.`id`
                   LEFT JOIN savor_room room ON box.`room_id`=room.`id`
                   LEFT JOIN savor_hotel hotel ON hotel.`id`=room.`hotel_id`
                   LEFT JOIN savor_area_info AS areainfo ON hotel.`area_id`=areainfo.`id`
                   WHERE ads.start_date<'".$yesterday_end_time."' AND ads.`end_date`>'".$yesterday_start_time."' AND ads.`state`!=2
                   AND hotel.`area_id`=".$v['id']." and hotel.state=1 and hotel.flag=0 and box.state=1
                   and box.flag=0  GROUP by pubbox.`pub_ads_id` ";
            $tmp1 = M()->query($sql);
        
            //在投广告个数
            $sql ="SELECT count(distinct pubbox.box_id) boxnum,pubbox.`pub_ads_id`
                   FROM savor_pub_ads_box_history pubbox
                   LEFT JOIN savor_pub_ads ads ON pubbox.`pub_ads_id`=ads.`id`
                   LEFT JOIN savor_box box ON pubbox.`box_id`=box.`id`
                   LEFT JOIN savor_room room ON box.`room_id`=room.`id`
                   LEFT JOIN savor_hotel hotel ON hotel.`id`=room.`hotel_id`
                   LEFT JOIN savor_area_info AS areainfo ON hotel.`area_id`=areainfo.`id`
                   WHERE ads.start_date<'".$yesterday_end_time."' AND ads.`end_date`>'".$yesterday_start_time."' AND ads.`state`!=2
                   AND hotel.`area_id`=".$v['id']." and hotel.state=1 and hotel.flag=0 and box.state=1
                   and box.flag=0  GROUP by pubbox.`pub_ads_id` ";
            $tmp2 = M()->query($sql);
            if(!empty($tmp1)){
                $tmp = array_merge($tmp1,$tmp2);
            }else {
                $tmp = $tmp2;
            }
            $all_area_pub_box_num = 0;
            foreach($tmp as $kk=>$vv){
                $all_area_pub_box_num +=$vv['boxnum'];
            }
            $area_online_ads_nums = count($tmp);
            $area_list[$key]['area_online_ads_nums'] = $area_online_ads_nums;
            //网络机顶盒数
            $where = array();
            $where['hotel.state']   = 1;
            $where['hotel.flag']    = 0;
            $where['box.state']     = 1;
            $where['box.flag']      = 0;
            $where['hotel.area_id'] = $v['id'];
            $where['hotel.id'] = array('not in',array(7,53,791,747,508));
            $where['hotel.hotel_box_type'] = array('in',$hotel_box_type_str);
        
            $area_net_box_nums = $m_box->countNums($where);
            $all_net_box_nums +=$area_net_box_nums;
            $area_list[$key]['area_net_box_nums'] = $area_net_box_nums;
            //广告到达率
            
            $where = array();
            $where['area_id'] = $v['id'];
            $where['media_id'] = array('neq','-10000');
            $arrive_box_num = $m_statistics_box_media_arrive->getCount($where);
        
             
            $jisuan_arrive_box_num +=$arrive_box_num;
            $jisuan_all_area_pub_box_num += $all_area_pub_box_num;
            $ads_arrive_rate = sprintf("%1.2f",$arrive_box_num / $all_area_pub_box_num *100) ;
        
            $all_ads_arrive_rate +=$ads_arrive_rate;
            $area_list[$key]['ads_arrive_rate'] = $ads_arrive_rate;
        }
        $result['area_list'] = $area_list;
        $all_ads_arrive_rate = sprintf("%1.2f",$jisuan_arrive_box_num/$jisuan_all_area_pub_box_num*100);
        $result['all_ads_arrive_rate'] = $all_ads_arrive_rate;
        print_r($result);
    }
    
}
