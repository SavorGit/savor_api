<?php
namespace H5\Controller;
use Think\Controller;
header("access-control-allow-headers:Accept,Authorization,Cache-Control,Content-Type,DNT,If-Modified-Since,Keep-Alive,Origin,User-Agent,X-Mx-ReqToken,X-Requested-With");
header("access-control-allow-methods: GET, POST, PUT, DELETE, HEAD, OPTIONS");
header("access-control-allow-credentials: true");
header("access-control-allow-origin: *");
header('X-Powered-By: WAF/2.0');

class RddataController extends Controller {

    public function hotel(){
        $page = I('page',1,'intval');
        $pagesize = 10;
        $area_id = I('area_id',0,'intval');

        $m_statichotel = new \Common\Model\Smallapp\StaticHoteldataModel();
        $sdate = date('Ymd',strtotime('-1 day'));
        $where = array('date'=>$sdate);
        if($area_id){
            $where['area_id'] = $area_id;
        }
        $start = ($page-1)*$pagesize;
        $res_data = $m_statichotel->getDataList('*',$where,'zxrate desc',$start,$pagesize);
        $datalist = array();
        if(!empty($res_data['list'])){
            $begin_lastweek_time = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
            $end_lastweek_time = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
            $begin_lastweek_date = date('Y-m-d',$begin_lastweek_time);
            $end_lastweek_date = date('Y-m-d',$end_lastweek_time);
            $begin_lastweek_timediff = mktime(0,0,0,date('m'),date('d')-date('w')+1-14,date('Y'));
            $end_lastweek_timediff = mktime(23,59,59,date('m'),date('d')-date('w')+7-14,date('Y'));
            $begin_lastweek_datediff = date('Y-m-d',$begin_lastweek_timediff);
            $end_lastweek_datedfii = date('Y-m-d',$end_lastweek_timediff);
            $lastweek_dates = $m_statichotel->getDates($begin_lastweek_date,$end_lastweek_date,2);
            $lastweek_dates_diff = $m_statichotel->getDates($begin_lastweek_datediff,$end_lastweek_datedfii,2);

            $begin_lastmonth_date = date('Y-m-01',strtotime('-1 month'));
            $end_lastmonth_date = date('Y-m-31',strtotime('-1 month'));
            $begin_lastmonth_datediff = date('Y-m-01',strtotime('-2 month'));
            $end_lastmonth_datediff = date('Y-m-31',strtotime('-2 month'));
            $lastmonth_dates = $m_statichotel->getDates($begin_lastmonth_date,$end_lastmonth_date,2);
            $lastmonth_dates_diff = $m_statichotel->getDates($begin_lastmonth_datediff,$end_lastmonth_datediff,2);

            foreach ($res_data['list'] as $v){
                $hotel_id = $v['hotel_id'];
                $anteayer = date('Ymd',strtotime('-2 day'));
                if($v['train_date']=='0000-00-00'){
                    $v['train_date'] = '';
                }
                $dinfo = array('hotel_name'=>$v['hotel_name'],'area_name'=>$v['area_name'],'hotel_level'=>intval($v['hotel_level']),'maintainer'=>$v['maintainer'],
                    'tech_maintainer'=>$v['tech_maintainer'],'trainer'=>$v['trainer'],'train_date'=>$v['train_date']);
                $condition = array('date'=>$anteayer,'hotel_id'=>$hotel_id);
                $diff_data = $m_statichotel->getInfo($condition);
                $dinfo['yesterday'] = $this->diff_data($v,$diff_data);

                $last_week_where = array('date'=>array('in',$lastweek_dates),'hotel_id'=>$hotel_id);
                $fields = 'avg(box_num) as box_num,avg(faultbox_num) as faultbox_num,avg(normalbox_num) as normalbox_num,avg(fault_rate) as fault_rate,
                avg(lunch_rate) as lunch_rate,avg(dinner_rate) as dinner_rate,avg(zxrate) as zxrate,avg(fjrate) as fjrate,sum(scancode_num) as scancode_num,
                sum(interact_standard_num+interact_mini_num+interact_sale_num) as interact_num,sum(interact_standard_num) as interact_standard_num,sum(interact_mini_num) as interact_mini_num,
                sum(interact_sale_num) as interact_sale_num';
                $res_week = $m_statichotel->getDataList($fields,$last_week_where,'');
                $raw_data_week = $res_week[0];

                $last_week_diffwhere = array('date'=>array('in',$lastweek_dates_diff),'hotel_id'=>$hotel_id);
                $fields = 'avg(box_num) as box_num,avg(faultbox_num) as faultbox_num,avg(normalbox_num) as normalbox_num,avg(fault_rate) as fault_rate,
                avg(lunch_rate) as lunch_rate,avg(dinner_rate) as dinner_rate,avg(zxrate) as zxrate,avg(fjrate) as fjrate,sum(scancode_num) as scancode_num,
                sum(interact_standard_num+interact_mini_num+interact_sale_num) as interact_num,sum(interact_standard_num) as interact_standard_num,sum(interact_mini_num) as interact_mini_num,
                sum(interact_sale_num) as interact_sale_num';
                $res_week = $m_statichotel->getDataList($fields,$last_week_diffwhere,'');
                $diff_data_week = $res_week[0];

                $dinfo['last_week'] = $this->diff_data($raw_data_week,$diff_data_week);

                $last_month_where = array('date'=>array('in',$lastmonth_dates),'hotel_id'=>$hotel_id);
                $fields = 'avg(box_num) as box_num,avg(faultbox_num) as faultbox_num,avg(normalbox_num) as normalbox_num,avg(fault_rate) as fault_rate,
                avg(lunch_rate) as lunch_rate,avg(dinner_rate) as dinner_rate,avg(zxrate) as zxrate,avg(fjrate) as fjrate,sum(scancode_num) as scancode_num,
                sum(interact_standard_num+interact_mini_num+interact_sale_num) as interact_num,sum(interact_standard_num) as interact_standard_num,sum(interact_mini_num) as interact_mini_num,
                sum(interact_sale_num) as interact_sale_num';
                $res_week = $m_statichotel->getDataList($fields,$last_month_where,'');
                $raw_data_month = $res_week[0];

                $last_month_diffwhere = array('date'=>array('in',$lastmonth_dates_diff),'hotel_id'=>$hotel_id);
                $fields = 'avg(box_num) as box_num,avg(faultbox_num) as faultbox_num,avg(normalbox_num) as normalbox_num,avg(fault_rate) as fault_rate,
                avg(lunch_rate) as lunch_rate,avg(dinner_rate) as dinner_rate,avg(zxrate) as zxrate,avg(fjrate) as fjrate,sum(scancode_num) as scancode_num,
                sum(interact_standard_num+interact_mini_num+interact_sale_num) as interact_num,sum(interact_standard_num) as interact_standard_num,sum(interact_mini_num) as interact_mini_num,
                sum(interact_sale_num) as interact_sale_num';
                $res_week = $m_statichotel->getDataList($fields,$last_month_diffwhere,'');
                $diff_data_month = $res_week[0];

                $dinfo['last_month'] = $this->diff_data($raw_data_month,$diff_data_month);

                $datalist[] = $dinfo;
            }
        }
        $total_page = ceil($res_data['total']/10);
        $res = array('datalist'=>$datalist,'total_page'=>$total_page);
        $this->ajaxReturn($res,'jsonp');
        echo json_encode($res);
        exit;
    }

    public function interactnum(){
        $area_id = I('area_id',0,'intval');
        $m_statichotel = new \Common\Model\Smallapp\StaticHoteldataModel();

        $begin_lastweek_time = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
        $end_lastweek_time = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
        $begin_lastweek_date = date('Y-m-d',$begin_lastweek_time);
        $end_lastweek_date = date('Y-m-d',$end_lastweek_time);
        $lastweek_dates = $m_statichotel->getDates($begin_lastweek_date,$end_lastweek_date,2);
        $last_week_where = array('date'=>array('in',$lastweek_dates));
        if($area_id){
            $last_week_where['area_id'] = $area_id;
        }
        $fields = 'sum(interact_num) as interact_num';
        $res_week = $m_statichotel->getDataList($fields,$last_week_where,'');
        $last_week = $res_week[0];

        $beginweek = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
        $begin_week_date = date('Y-m-d',$beginweek);
        $end_week_date = date('Y-m-d',strtotime('-1 day'));

        if($begin_week_date>$end_week_date){
            $week = array('interact_num'=>0);
        }else{
            $week_dates = $m_statichotel->getDates($begin_week_date,$end_week_date,2);
            $week_where = array('date'=>array('in',$week_dates));
            if($area_id){
                $week_where['area_id'] = $area_id;
            }
            $fields = 'sum(interact_num) as interact_num';
            $res_week = $m_statichotel->getDataList($fields,$week_where,'');
            $week = $res_week[0];
        }
        $week_interact_num_color = 0;
        if($week['interact_num']>$last_week['interact_num']){
            $week_interact_num_color = 1;
        }elseif($week['interact_num']<$last_week['interact_num']){
            $week_interact_num_color = 2;
        }else{
            $week_interact_num_color = 0;
        }

        $begin_lastmonth_date = date('Y-m-01',strtotime('-1 month'));
        $end_lastmonth_date = date('Y-m-31',strtotime('-1 month'));
        $last_month_dates = $m_statichotel->getDates($begin_lastmonth_date,$end_lastmonth_date,2);
        $last_month_where = array('date'=>array('in',$last_month_dates));
        if($area_id){
            $last_month_where['area_id'] = $area_id;
        }
        $fields = 'sum(interact_num) as interact_num';
        $res_month = $m_statichotel->getDataList($fields,$last_month_where,'');
        $last_month = $res_month[0];

        $begin_month_date = date('Y-m-01');
        $end_month_date = date('Y-m-d',strtotime('-1 day'));
        $month_dates = $m_statichotel->getDates($begin_month_date,$end_month_date,2);
        $month_where = array('date'=>array('in',$month_dates));
        if($area_id){
            $month_where['area_id'] = $area_id;
        }
        $fields = 'sum(interact_num) as interact_num';
        $res_month = $m_statichotel->getDataList($fields,$month_where,'');
        $month = $res_month[0];

        if($month['interact_num']>$last_month['interact_num']){
            $month_interact_num_color = 1;
        }elseif($month['interact_num']<$last_month['interact_num']){
            $month_interact_num_color = 2;
        }else{
            $month_interact_num_color = 0;
        }
        $now_date = date('Y-m-d');
        $data = array('now_date'=>$now_date,'month_num'=>$month['interact_num'],'month_num_color'=>$month_interact_num_color,'lastmonth_num'=>$last_month['interact_num'],
            'week_num'=>$week['interact_num'],'week_num_color'=>$week_interact_num_color,'lastweek_num'=>$last_week['interact_num'],'time_bucket'=>array());

        $begin_date = date('Y-m-d',strtotime('-14 day'));
        $end_date = date('Y-m-d',strtotime('-1 day'));
        $dates = $m_statichotel->getDates($begin_date,$end_date,2);
        $mdates = array();
        foreach ($dates as $v){
            $mdates[]=date('n-j',strtotime($v));
        }
        $data['time_bucket']['time'] = $mdates;

        $where = array("date"=>array('in',$dates));
        if($area_id){
            $where['area_id'] = $area_id;
        }
        $fields = "sum(interact_num) as interact_num,sum(interact_sale_num) as sale_num,
        sum(interact_standard_num+interact_mini_num+interact_game_num) as user_num,date";
        $res_data = $m_statichotel->getDatas($fields,$where,'date');
        foreach ($res_data as $v){
            $data['time_bucket']['data'][] = intval($v['interact_num']);
            $data['time_bucket']['data_user'][] = intval($v['user_num']);
            $data['time_bucket']['data_sale'][] = intval($v['sale_num']);
        }
        $this->ajaxReturn($data,'jsonp');
        echo json_encode($data);
        exit;
    }

    public function bootrate(){
        $area_id = I('area_id',0,'intval');
        $m_statichotel = new \Common\Model\Smallapp\StaticHoteldataModel();

        $fields = "avg(zxrate) as zxrate,area_id,area_name";
        $begin_date = date('Y-m-d',strtotime('-7 day'));
        $end_date = date('Y-m-d',strtotime('-1 day'));
        $dates = $m_statichotel->getDates($begin_date,$end_date,2);
        $where = array("date"=>array('in',$dates));
        if($area_id){
            $where['area_id'] = $area_id;
        }
        $res_data = $m_statichotel->getDatas($fields,$where,'area_id');
        $online_rate = 0;
        $area_names = array();
        $area_data = array();
        foreach ($res_data as $v){
            $online_rate+=$v['zxrate'];
            $area_names[]=$v['area_name'];
            $rate = sprintf("%.2f",$v['zxrate']);
            $rate = $rate*100;
            $area_data[] = $rate;
        }
        $area_num = count($res_data);
        $tmp_online_rate = sprintf("%.2f",$online_rate/$area_num);
        $online_rate = $tmp_online_rate*100;
        $no_online_rate = 100-$online_rate;
        $res_data = array(
            'boot'=>array('names'=>array('在线','未在线'),'data'=>array($online_rate,$no_online_rate)),
            'area'=>array('names'=>$area_names,'data'=>$area_data),
        );
        $this->ajaxReturn($res_data,'jsonp');
        echo json_encode($res_data);
        exit;
    }

    public function cebbank(){
        $page = intval($_REQUEST['page']);
        $page_size = 10;
        $sort_type = intval($_REQUEST['sort_type']);//1正序 2倒序
        if(empty($page)){
            $page = 1;
        }
        if(empty($sort_type)){
            $sort_type = 1;
        }
        $bank_data = array ( 0 => array ( 'jyrq' => '2022-03-08', 'jysj' => '11:30:22', 'jffse' => '', 'dffse' => '7,500,000.00', 'zhye' => '7,501,622.28', 'dfzh' => '110935301410101', 'dfmc' => '北京美丽赢家健康管理有限公司', 'pzh' => '060', 'lsh' => '901k86001660', 'zy' => '借款转收款行(中行集中汇出)', 'sort_num' => 2, ), 1 => array ( 'jyrq' => '2022-03-09', 'jysj' => '10:24:11', 'jffse' => '7,500,000.00', 'dffse' => '', 'zhye' => '1,622.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901301004109', 'zy' => '还款', 'sort_num' => 3, ), 2 => array ( 'jyrq' => '2022-03-09', 'jysj' => '11:28:57', 'jffse' => '', 'dffse' => '16,000,000.00', 'zhye' => '16,001,622.28', 'dfzh' => '110935301410101', 'dfmc' => '北京美丽赢家健康管理有限公司', 'pzh' => '060', 'lsh' => '901k86001645', 'zy' => '借款转收款行(中行集中汇出)', 'sort_num' => 4, ), 3 => array ( 'jyrq' => '2022-03-09', 'jysj' => '15:58:43', 'jffse' => '16,000,000.00', 'dffse' => '', 'zhye' => '1,622.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901313017340', 'zy' => '还款', 'sort_num' => 5, ), 4 => array ( 'jyrq' => '2022-03-09', 'jysj' => '16:08:42', 'jffse' => '50.00', 'dffse' => '', 'zhye' => '1,572.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901318017718', 'zy' => '对公网银年服务费', 'sort_num' => 6, ), 5 => array ( 'jyrq' => '2022-03-09', 'jysj' => '16:08:42', 'jffse' => '50.00', 'dffse' => '', 'zhye' => '1,522.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901303017798', 'zy' => '网银证书服务费', 'sort_num' => 7, ), 6 => array ( 'jyrq' => '2022-03-09', 'jysj' => '16:08:43', 'jffse' => '50.00', 'dffse' => '', 'zhye' => '1,472.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901313017919', 'zy' => '网银证书服务费', 'sort_num' => 8, ), 7 => array ( 'jyrq' => '2022-03-11', 'jysj' => '12:30:17', 'jffse' => '', 'dffse' => '5,000.00', 'zhye' => '6,472.28', 'dfzh' => '110936502710201', 'dfmc' => '北京热点投屏科技发展有限公司', 'pzh' => '060', 'lsh' => '901l15001023', 'zy' => '借款', 'sort_num' => 9, ), 8 => array ( 'jyrq' => '2022-03-11', 'jysj' => '15:50:57', 'jffse' => '', 'dffse' => '1,100,000.00', 'zhye' => '1,106,472.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k89003581', 'zy' => '借款', 'sort_num' => 10, ), 9 => array ( 'jyrq' => '2022-03-11', 'jysj' => '16:00:55', 'jffse' => '', 'dffse' => '400,000.00', 'zhye' => '1,506,472.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k98001704', 'zy' => '借款', 'sort_num' => 11, ), 10 => array ( 'jyrq' => '2022-03-11', 'jysj' => '16:18:52', 'jffse' => '', 'dffse' => '4,850,000.00', 'zhye' => '6,356,472.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k86003913', 'zy' => '借款', 'sort_num' => 12, ), 11 => array ( 'jyrq' => '2022-03-14', 'jysj' => '12:17:43', 'jffse' => '', 'dffse' => '8,645,000.00', 'zhye' => '15,001,472.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k86002573', 'zy' => '借款', 'sort_num' => 13, ), 12 => array ( 'jyrq' => '2022-03-14', 'jysj' => '14:19:32', 'jffse' => '15,000,000.00', 'dffse' => '', 'zhye' => '1,472.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901313011222', 'zy' => '还款', 'sort_num' => 14, ), 13 => array ( 'jyrq' => '2022-03-14', 'jysj' => '15:56:41', 'jffse' => '', 'dffse' => '3,185,000.00', 'zhye' => '3,186,472.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k90004357', 'zy' => '借款', 'sort_num' => 15, ), 14 => array ( 'jyrq' => '2022-03-14', 'jysj' => '16:21:33', 'jffse' => '3,185,000.00', 'dffse' => '', 'zhye' => '1,472.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901309017941', 'zy' => '还款', 'sort_num' => 16, ), 15 => array ( 'jyrq' => '2022-03-15', 'jysj' => '10:19:20', 'jffse' => '', 'dffse' => '9,500,000.00', 'zhye' => '9,501,472.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k85001186', 'zy' => '借款', 'sort_num' => 17, ), 16 => array ( 'jyrq' => '2022-03-15', 'jysj' => '10:59:03', 'jffse' => '190.00', 'dffse' => '', 'zhye' => '9,501,282.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901304008184', 'zy' => '网银电子汇划费', 'sort_num' => 18, ), 17 => array ( 'jyrq' => '2022-03-15', 'jysj' => '10:59:03', 'jffse' => '9,500,000.00', 'dffse' => '', 'zhye' => '1,282.28', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901304008184', 'zy' => '还款', 'sort_num' => 19, ), 18 => array ( 'jyrq' => '2022-03-16', 'jysj' => '12:32:33', 'jffse' => '', 'dffse' => '7,710,000.00', 'zhye' => '7,711,282.28', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k85002309', 'zy' => '借款', 'sort_num' => 20, ), 19 => array ( 'jyrq' => '2022-03-16', 'jysj' => '13:20:14', 'jffse' => '154.20', 'dffse' => '', 'zhye' => '7,711,128.08', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901308009448', 'zy' => '网银电子汇划费', 'sort_num' => 21, ), 20 => array ( 'jyrq' => '2022-03-16', 'jysj' => '13:20:14', 'jffse' => '7,710,000.00', 'dffse' => '', 'zhye' => '1,128.08', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901308009448', 'zy' => '还款', 'sort_num' => 22, ), 21 => array ( 'jyrq' => '2022-03-17', 'jysj' => '10:25:47', 'jffse' => '', 'dffse' => '6,400,000.00', 'zhye' => '6,401,128.08', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k92001535', 'zy' => '借款', 'sort_num' => 23, ), 22 => array ( 'jyrq' => '2022-03-17', 'jysj' => '13:39:34', 'jffse' => '128.00', 'dffse' => '', 'zhye' => '6,401,000.08', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901300009059', 'zy' => '网银电子汇划费', 'sort_num' => 24, ), 23 => array ( 'jyrq' => '2022-03-17', 'jysj' => '13:39:34', 'jffse' => '6,400,000.00', 'dffse' => '', 'zhye' => '1,000.08', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901300009059', 'zy' => '还款', 'sort_num' => 25, ), 24 => array ( 'jyrq' => '2022-03-21', 'jysj' => '02:52:06', 'jffse' => '', 'dffse' => '864.15', 'zhye' => '1,864.23', 'dfzh' => '35610105570000001', 'dfmc' => '应付单位活期存款利息', 'pzh' => '', 'lsh' => '993561000010', 'zy' => '结息', 'sort_num' => 26, ), 25 => array ( 'jyrq' => '2022-03-21', 'jysj' => '11:39:50', 'jffse' => '', 'dffse' => '8,205,000.00', 'zhye' => '8,206,864.23', 'dfzh' => '9550880222181000137', 'dfmc' => '中税国际控股（北京）有限公司', 'pzh' => '060', 'lsh' => '901k90002008', 'zy' => '借款', 'sort_num' => 27, ), 26 => array ( 'jyrq' => '2022-03-21', 'jysj' => '12:49:18', 'jffse' => '164.10', 'dffse' => '', 'zhye' => '8,206,700.13', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901310008537', 'zy' => '网银电子汇划费', 'sort_num' => 28, ), 27 => array ( 'jyrq' => '2022-03-21', 'jysj' => '12:49:18', 'jffse' => '8,205,000.00', 'dffse' => '', 'zhye' => '1,700.13', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901310008537', 'zy' => '还款', 'sort_num' => 29, ), );

        if($sort_type==2){
           sortArrByOneField($bank_data,'sort_num',true);
        }
        $offset = ($page-1)*$page_size;
        $data_list = array_slice($bank_data,$offset,$page_size);
        $res_data = array('zh'=>'35610180808217773','cxsj'=>date('Y-m-d H:i:s'),
            'jffsje'=>'73500786.30','dffsje'=>'73500864.15','jfbs'=>15,'dfbs'=>13,
            'total_num'=>count($bank_data),'datalist'=>$data_list
        );
        $this->ajaxReturn($res_data,'jsonp');
    }


    private function diff_data($raw_data,$diff_data){
        $all_keys = array(
            'box_num'=>array('name'=>'版位数','type'=>1),
            'faultbox_num'=>array('name'=>'故障屏','type'=>1),
            'normalbox_num'=>array('name'=>'正常屏','type'=>1),
            'fault_rate'=>array('name'=>'故障占比','type'=>2),
            'lunch_rate'=>array('name'=>'午饭在线率','type'=>2),
            'dinner_rate'=>array('name'=>'晚饭在线率','type'=>2),
            'zxrate'=>array('name'=>'平均在线率','type'=>2),
            'fjrate'=>array('name'=>'饭局转化率','type'=>2),
            'scancode_num'=>array('name'=>'扫码数','type'=>1),
            'interact_num'=>array('name'=>'互动总数','type'=>1),
            'interact_standard_num'=>array('name'=>'标准互动数','type'=>1),
            'interact_mini_num'=>array('name'=>'极简互动数','type'=>1),
            'interact_sale_num'=>array('name'=>'销售互动数','type'=>1),
        );
        $data = array();
        foreach ($all_keys as $k=>$v){
            $dvalue = 0;
            if($raw_data[$k]>0){
                if($v['type']==2){
                    $dvalue = sprintf("%.2f",$raw_data[$k]);
                    $dvalue = ($dvalue*100).'%';
                }else{
                    $dvalue = intval($raw_data[$k]);
                }
            }
            $data["$k"] = $dvalue;
            if($raw_data[$k]>$diff_data[$k]){
                $color = 1;
            }elseif($raw_data[$k]<$diff_data[$k]){
                $color = 2;
            }else{
                $color = 0;
            }
            $color_name = $k.'_color';
            $data["$color_name"] = $color;//0正常 1绿色 2红色
        }
        return $data;
    }

}