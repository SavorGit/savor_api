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
        $sdate = $_REQUEST['sdate'];
        $edate = $_REQUEST['edate'];
        $page = intval($_REQUEST['page']);
        $page_size = 10;
        $sort_type = intval($_REQUEST['sort_type']);//1正序 2倒序
        if(empty($page)){
            $page = 1;
        }
        if(empty($sort_type)){
            $sort_type = 1;
        }
        $bank_data = array ( 0 => array ( 'jyrq' => '2021-05-27', 'jysj' => '09:47:29', 'jffse' => '', 'dffse' => 1000, 'zhye' => '1000.00', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '060', 'lsh' => '901a57003817', 'zy' => '网银跨行汇款', 'sort_num' => 2, ), 1 => array ( 'jyrq' => '2021-05-27', 'jysj' => '11:52:39', 'jffse' => '', 'dffse' => 8000000, 'zhye' => '8001000.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '060', 'lsh' => '901k84002357', 'zy' => '跨行转账', 'sort_num' => 3, ), 2 => array ( 'jyrq' => '2021-05-27', 'jysj' => '12:15:09', 'jffse' => 3000000, 'dffse' => '', 'zhye' => '5001000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901302009577', 'zy' => '往来款', 'sort_num' => 4, ), 3 => array ( 'jyrq' => '2021-05-27', 'jysj' => '13:56:02', 'jffse' => 2000000, 'dffse' => '', 'zhye' => '3001000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901312011924', 'zy' => '往来款', 'sort_num' => 5, ), 4 => array ( 'jyrq' => '2021-05-27', 'jysj' => '16:04:13', 'jffse' => 3000000, 'dffse' => '', 'zhye' => '1000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901301019198', 'zy' => '往来款', 'sort_num' => 6, ), 5 => array ( 'jyrq' => '2021-05-28', 'jysj' => '09:26:52', 'jffse' => '', 'dffse' => 20000000, 'zhye' => '20001000.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '060', 'lsh' => '901k87000525', 'zy' => '跨行转账', 'sort_num' => 7, ), 6 => array ( 'jyrq' => '2021-05-28', 'jysj' => '10:53:53', 'jffse' => 5000000, 'dffse' => '', 'zhye' => '15001000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901305006603', 'zy' => '还款', 'sort_num' => 8, ), 7 => array ( 'jyrq' => '2021-05-28', 'jysj' => '12:01:39', 'jffse' => 5000000, 'dffse' => '', 'zhye' => '10001000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901318010132', 'zy' => '还款', 'sort_num' => 9, ), 8 => array ( 'jyrq' => '2021-05-28', 'jysj' => '13:55:33', 'jffse' => 5000000, 'dffse' => '', 'zhye' => '5001000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901315013067', 'zy' => '还款', 'sort_num' => 10, ), 9 => array ( 'jyrq' => '2021-05-28', 'jysj' => '16:03:06', 'jffse' => 5000000, 'dffse' => '', 'zhye' => '1000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901315021595', 'zy' => '还款', 'sort_num' => 11, ), 10 => array ( 'jyrq' => '2021-06-01', 'jysj' => '09:54:43', 'jffse' => '', 'dffse' => 13015000, 'zhye' => '13016000.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '060', 'lsh' => '901k88000883', 'zy' => '跨行转账', 'sort_num' => 12, ), 11 => array ( 'jyrq' => '2021-06-01', 'jysj' => '16:41:48', 'jffse' => 5340000, 'dffse' => '', 'zhye' => '7676000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901311020112', 'zy' => '还款', 'sort_num' => 13, ), 12 => array ( 'jyrq' => '2021-06-01', 'jysj' => '17:12:21', 'jffse' => 3965000, 'dffse' => '', 'zhye' => '3711000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901313021542', 'zy' => '还款', 'sort_num' => 14, ), 13 => array ( 'jyrq' => '2021-06-02', 'jysj' => '10:14:18', 'jffse' => 3710000, 'dffse' => '', 'zhye' => '1000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901308004083', 'zy' => '还款', 'sort_num' => 15, ), 14 => array ( 'jyrq' => '2021-06-04', 'jysj' => '14:05:40', 'jffse' => '', 'dffse' => 30000000, 'zhye' => '30001000.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901315012319', 'zy' => '往来款', 'sort_num' => 16, ), 15 => array ( 'jyrq' => '2021-06-04', 'jysj' => '15:22:36', 'jffse' => 200, 'dffse' => '', 'zhye' => '30000800.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901311016576', 'zy' => '网银电子汇划费', 'sort_num' => 17, ), 16 => array ( 'jyrq' => '2021-06-04', 'jysj' => '15:22:36', 'jffse' => 30000000, 'dffse' => '', 'zhye' => '800.00', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901311016576', 'zy' => '刘磊出资款', 'sort_num' => 18, ), 17 => array ( 'jyrq' => '2021-06-07', 'jysj' => '10:38:52', 'jffse' => '', 'dffse' => 20000000, 'zhye' => '20000800.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901299005034', 'zy' => '往来', 'sort_num' => 19, ), 18 => array ( 'jyrq' => '2021-06-07', 'jysj' => '16:08:26', 'jffse' => 200, 'dffse' => '', 'zhye' => '20000600.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901310018965', 'zy' => '网银电子汇划费', 'sort_num' => 20, ), 19 => array ( 'jyrq' => '2021-06-07', 'jysj' => '16:08:26', 'jffse' => 15000000, 'dffse' => '', 'zhye' => '5000600.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901310018965', 'zy' => '还款', 'sort_num' => 21, ), 20 => array ( 'jyrq' => '2021-06-08', 'jysj' => '09:40:40', 'jffse' => 100, 'dffse' => '', 'zhye' => '5000500.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901312002628', 'zy' => '网银电子汇划费', 'sort_num' => 22, ), 21 => array ( 'jyrq' => '2021-06-08', 'jysj' => '09:40:40', 'jffse' => 5000000, 'dffse' => '', 'zhye' => '500.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901312002628', 'zy' => '还款', 'sort_num' => 23, ), 22 => array ( 'jyrq' => '2021-06-08', 'jysj' => '11:29:12', 'jffse' => '', 'dffse' => 20000000, 'zhye' => '20000500.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901309008185', 'zy' => '往来', 'sort_num' => 24, ), 23 => array ( 'jyrq' => '2021-06-09', 'jysj' => '09:35:06', 'jffse' => 60, 'dffse' => '', 'zhye' => '20000440.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901201001268', 'zy' => '网银电子汇划费', 'sort_num' => 25, ), 24 => array ( 'jyrq' => '2021-06-09', 'jysj' => '09:35:06', 'jffse' => 3000000, 'dffse' => '', 'zhye' => '17000440.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901201001268', 'zy' => '还款', 'sort_num' => 26, ), 25 => array ( 'jyrq' => '2021-06-09', 'jysj' => '10:03:41', 'jffse' => '', 'dffse' => 20000000, 'zhye' => '37000440.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901318003412', 'zy' => '往来', 'sort_num' => 27, ), 26 => array ( 'jyrq' => '2021-06-09', 'jysj' => '11:04:05', 'jffse' => 200, 'dffse' => '', 'zhye' => '37000240.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901311006642', 'zy' => '网银电子汇划费', 'sort_num' => 28, ), 27 => array ( 'jyrq' => '2021-06-09', 'jysj' => '11:04:05', 'jffse' => 17000000, 'dffse' => '', 'zhye' => '20000240.00', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901311006642', 'zy' => '还款', 'sort_num' => 29, ), 28 => array ( 'jyrq' => '2021-06-09', 'jysj' => '14:35:16', 'jffse' => 200, 'dffse' => '', 'zhye' => '20000040.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901311013275', 'zy' => '网银电子汇划费', 'sort_num' => 30, ), 29 => array ( 'jyrq' => '2021-06-09', 'jysj' => '14:35:16', 'jffse' => 11610958.9, 'dffse' => '', 'zhye' => '8389081.10', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901311013275', 'zy' => '还款', 'sort_num' => 31, ), 30 => array ( 'jyrq' => '2021-06-10', 'jysj' => '08:40:17', 'jffse' => '', 'dffse' => 1000, 'zhye' => '8390081.10', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '060', 'lsh' => '901345003177', 'zy' => '网银跨行汇款', 'sort_num' => 32, ), 31 => array ( 'jyrq' => '2021-06-10', 'jysj' => '09:09:42', 'jffse' => '', 'dffse' => 51610958.899999999, 'zhye' => '60001040.00', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901313001438', 'zy' => '往来', 'sort_num' => 33, ), 32 => array ( 'jyrq' => '2021-06-10', 'jysj' => '12:45:38', 'jffse' => 200, 'dffse' => '', 'zhye' => '60000840.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901318012847', 'zy' => '网银电子汇划费', 'sort_num' => 34, ), 33 => array ( 'jyrq' => '2021-06-10', 'jysj' => '12:45:38', 'jffse' => 35000000, 'dffse' => '', 'zhye' => '25000840.00', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901318012847', 'zy' => '出资款', 'sort_num' => 35, ), 34 => array ( 'jyrq' => '2021-06-10', 'jysj' => '14:13:55', 'jffse' => 200, 'dffse' => '', 'zhye' => '25000640.00', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901314016287', 'zy' => '网银电子汇划费', 'sort_num' => 36, ), 35 => array ( 'jyrq' => '2021-06-10', 'jysj' => '14:13:55', 'jffse' => 25000000, 'dffse' => '', 'zhye' => '640.00', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901314016287', 'zy' => '出资款', 'sort_num' => 37, ), 36 => array ( 'jyrq' => '2021-06-21', 'jysj' => '02:30:36', 'jffse' => '', 'dffse' => 309.31, 'zhye' => '949.31', 'dfzh' => '35610105570000001', 'dfmc' => '应付单位活期存款利息', 'pzh' => '', 'lsh' => '993561000008', 'zy' => '结息', 'sort_num' => 38, ), 37 => array ( 'jyrq' => '2021-07-27', 'jysj' => '16:55:45', 'jffse' => '', 'dffse' => 37600000, 'zhye' => '37600949.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901309023855', 'zy' => '借款', 'sort_num' => 39, ), 38 => array ( 'jyrq' => '2021-07-28', 'jysj' => '09:35:26', 'jffse' => 200, 'dffse' => '', 'zhye' => '37600749.31', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901138000065', 'zy' => '网银电子汇划费', 'sort_num' => 40, ), 39 => array ( 'jyrq' => '2021-07-28', 'jysj' => '09:35:26', 'jffse' => 21500000, 'dffse' => '', 'zhye' => '16100749.31', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901138000065', 'zy' => '出资款', 'sort_num' => 41, ), 40 => array ( 'jyrq' => '2021-07-28', 'jysj' => '13:59:03', 'jffse' => 200, 'dffse' => '', 'zhye' => '16100549.31', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901317011717', 'zy' => '网银电子汇划费', 'sort_num' => 42, ), 41 => array ( 'jyrq' => '2021-07-28', 'jysj' => '13:59:03', 'jffse' => 16100000, 'dffse' => '', 'zhye' => '549.31', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901317011717', 'zy' => '出资款', 'sort_num' => 43, ), 42 => array ( 'jyrq' => '2021-08-03', 'jysj' => '15:26:59', 'jffse' => '', 'dffse' => 38400000, 'zhye' => '38400549.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901318015302', 'zy' => '往来', 'sort_num' => 44, ), 43 => array ( 'jyrq' => '2021-08-03', 'jysj' => '16:33:26', 'jffse' => 200, 'dffse' => '', 'zhye' => '38400349.31', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901299020073', 'zy' => '网银电子汇划费', 'sort_num' => 45, ), 44 => array ( 'jyrq' => '2021-08-03', 'jysj' => '16:33:26', 'jffse' => 38400000, 'dffse' => '', 'zhye' => '349.31', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901299020073', 'zy' => '出资款', 'sort_num' => 46, ), 45 => array ( 'jyrq' => '2021-08-04', 'jysj' => '14:51:53', 'jffse' => '', 'dffse' => 1000000, 'zhye' => '1000349.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901304012399', 'zy' => '往来', 'sort_num' => 47, ), 46 => array ( 'jyrq' => '2021-08-04', 'jysj' => '15:21:18', 'jffse' => 20, 'dffse' => '', 'zhye' => '1000329.31', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901309014026', 'zy' => '网银电子汇划费', 'sort_num' => 48, ), 47 => array ( 'jyrq' => '2021-08-04', 'jysj' => '15:21:18', 'jffse' => 1000000, 'dffse' => '', 'zhye' => '329.31', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901309014026', 'zy' => '出资款', 'sort_num' => 49, ), 48 => array ( 'jyrq' => '2021-08-05', 'jysj' => '11:48:01', 'jffse' => '', 'dffse' => 3000000, 'zhye' => '3000329.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '', 'lsh' => '901312008209', 'zy' => '往来', 'sort_num' => 50, ), 49 => array ( 'jyrq' => '2021-08-05', 'jysj' => '12:00:18', 'jffse' => 60, 'dffse' => '', 'zhye' => '3000269.31', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901316008854', 'zy' => '网银电子汇划费', 'sort_num' => 51, ), 50 => array ( 'jyrq' => '2021-08-05', 'jysj' => '12:00:18', 'jffse' => 3000000, 'dffse' => '', 'zhye' => '269.31', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业(有限合伙)', 'pzh' => '', 'lsh' => '901316008854', 'zy' => '出资款', 'sort_num' => 52, ), 51 => array ( 'jyrq' => '2021-08-05', 'jysj' => '17:35:49', 'jffse' => '', 'dffse' => 1000, 'zhye' => '1269.31', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '060', 'lsh' => '901f03022358', 'zy' => '网银跨行汇款', 'sort_num' => 53, ), 52 => array ( 'jyrq' => '2021-08-09', 'jysj' => '10:29:15', 'jffse' => '', 'dffse' => 62300000, 'zhye' => '62301269.31', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业（有限合伙）', 'pzh' => '060', 'lsh' => '901k84001502', 'zy' => '返还款项', 'sort_num' => 54, ), 53 => array ( 'jyrq' => '2021-08-09', 'jysj' => '15:19:05', 'jffse' => 26850000, 'dffse' => '', 'zhye' => '35451269.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901300015836', 'zy' => '返还款项', 'sort_num' => 55, ), 54 => array ( 'jyrq' => '2021-08-09', 'jysj' => '15:56:01', 'jffse' => 18350000, 'dffse' => '', 'zhye' => '17101269.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901314018200', 'zy' => '返还款项', 'sort_num' => 56, ), 55 => array ( 'jyrq' => '2021-08-10', 'jysj' => '10:48:24', 'jffse' => 17100000, 'dffse' => '', 'zhye' => '1269.31', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901307007090', 'zy' => '返还款项', 'sort_num' => 57, ), 56 => array ( 'jyrq' => '2021-09-21', 'jysj' => '02:33:35', 'jffse' => '', 'dffse' => 456.66000000000003, 'zhye' => '1725.97', 'dfzh' => '35610105570000001', 'dfmc' => '应付单位活期存款利息', 'pzh' => '', 'lsh' => '993561000009', 'zy' => '结息', 'sort_num' => 58, ), 57 => array ( 'jyrq' => '2021-12-21', 'jysj' => '02:48:25', 'jffse' => '', 'dffse' => 1.3100000000000001, 'zhye' => '1727.28', 'dfzh' => '35610105570000001', 'dfmc' => '应付单位活期存款利息', 'pzh' => '', 'lsh' => '993561000011', 'zy' => '结息', 'sort_num' => 59, ), 58 => array ( 'jyrq' => '2021-12-23', 'jysj' => '10:37:31', 'jffse' => '', 'dffse' => 29328978.620000001, 'zhye' => '29330705.90', 'dfzh' => '44050164004500002636', 'dfmc' => '珠海中合荣创投资发展合伙企业（有限合伙）', 'pzh' => '060', 'lsh' => '901k90001906', 'zy' => '退本金', 'sort_num' => 60, ), 59 => array ( 'jyrq' => '2021-12-24', 'jysj' => '12:01:15', 'jffse' => 15000000, 'dffse' => '', 'zhye' => '14330705.90', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901300011508', 'zy' => '还款－备注：还款', 'sort_num' => 61, ), 60 => array ( 'jyrq' => '2021-12-27', 'jysj' => '09:43:45', 'jffse' => 10000000, 'dffse' => '', 'zhye' => '4330705.90', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901307002704', 'zy' => '还款－备注：还款', 'sort_num' => 62, ), 61 => array ( 'jyrq' => '2021-12-27', 'jysj' => '15:52:04', 'jffse' => '', 'dffse' => 172000, 'zhye' => '4502705.90', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '060', 'lsh' => '901l05001978', 'zy' => '跨行转账', 'sort_num' => 63, ), 62 => array ( 'jyrq' => '2021-12-28', 'jysj' => '11:11:17', 'jffse' => 90, 'dffse' => '', 'zhye' => '4502615.90', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901314008599', 'zy' => '网银电子汇划费', 'sort_num' => 64, ), 63 => array ( 'jyrq' => '2021-12-28', 'jysj' => '11:11:17', 'jffse' => 4500000, 'dffse' => '', 'zhye' => '2615.90', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901314008599', 'zy' => '还款', 'sort_num' => 65, ), 64 => array ( 'jyrq' => '2021-12-29', 'jysj' => '16:36:59', 'jffse' => '', 'dffse' => 171021.38, 'zhye' => '173637.28', 'dfzh' => '749769074530', 'dfmc' => '九知信息咨询管理（深圳）有限公司', 'pzh' => '060', 'lsh' => '901k86005291', 'zy' => '跨行转账', 'sort_num' => 66, ), 65 => array ( 'jyrq' => '2021-12-30', 'jysj' => '14:59:34', 'jffse' => 172000, 'dffse' => '', 'zhye' => '1637.28', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '', 'lsh' => '901311021016', 'zy' => '还款', 'sort_num' => 67, ), 66 => array ( 'jyrq' => '2021-12-30', 'jysj' => '14:59:34', 'jffse' => 15, 'dffse' => '', 'zhye' => '1622.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901311021016', 'zy' => '网银电子汇划费', 'sort_num' => 68, ), 67 => array ( 'jyrq' => '2022-03-08', 'jysj' => '11:30:22', 'jffse' => '', 'dffse' => 7500000, 'zhye' => '7501622.28', 'dfzh' => '110935301410101', 'dfmc' => '北京美丽赢家健康管理有限公司', 'pzh' => '060', 'lsh' => '901k86001660', 'zy' => '借款转收款行(中行集中汇出)', 'sort_num' => 69, ), 68 => array ( 'jyrq' => '2022-03-09', 'jysj' => '10:24:11', 'jffse' => 7500000, 'dffse' => '', 'zhye' => '1622.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901301004109', 'zy' => '还款', 'sort_num' => 70, ), 69 => array ( 'jyrq' => '2022-03-09', 'jysj' => '11:28:57', 'jffse' => '', 'dffse' => 16000000, 'zhye' => '16001622.28', 'dfzh' => '110935301410101', 'dfmc' => '北京美丽赢家健康管理有限公司', 'pzh' => '060', 'lsh' => '901k86001645', 'zy' => '借款转收款行(中行集中汇出)', 'sort_num' => 71, ), 70 => array ( 'jyrq' => '2022-03-09', 'jysj' => '15:58:43', 'jffse' => 16000000, 'dffse' => '', 'zhye' => '1622.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901313017340', 'zy' => '还款', 'sort_num' => 72, ), 71 => array ( 'jyrq' => '2022-03-09', 'jysj' => '16:08:42', 'jffse' => 50, 'dffse' => '', 'zhye' => '1572.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901318017718', 'zy' => '对公网银年服务费', 'sort_num' => 73, ), 72 => array ( 'jyrq' => '2022-03-09', 'jysj' => '16:08:42', 'jffse' => 50, 'dffse' => '', 'zhye' => '1522.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901303017798', 'zy' => '网银证书服务费', 'sort_num' => 74, ), 73 => array ( 'jyrq' => '2022-03-09', 'jysj' => '16:08:43', 'jffse' => 50, 'dffse' => '', 'zhye' => '1472.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901313017919', 'zy' => '网银证书服务费', 'sort_num' => 75, ), 74 => array ( 'jyrq' => '2022-03-11', 'jysj' => '12:30:17', 'jffse' => '', 'dffse' => 5000, 'zhye' => '6472.28', 'dfzh' => '110936502710201', 'dfmc' => '北京热点投屏科技发展有限公司', 'pzh' => '060', 'lsh' => '901l15001023', 'zy' => '借款', 'sort_num' => 76, ), 75 => array ( 'jyrq' => '2022-03-11', 'jysj' => '15:50:57', 'jffse' => '', 'dffse' => 1100000, 'zhye' => '1106472.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k89003581', 'zy' => '借款', 'sort_num' => 77, ), 76 => array ( 'jyrq' => '2022-03-11', 'jysj' => '16:00:55', 'jffse' => '', 'dffse' => 400000, 'zhye' => '1506472.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k98001704', 'zy' => '借款', 'sort_num' => 78, ), 77 => array ( 'jyrq' => '2022-03-11', 'jysj' => '16:18:52', 'jffse' => '', 'dffse' => 4850000, 'zhye' => '6356472.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k86003913', 'zy' => '借款', 'sort_num' => 79, ), 78 => array ( 'jyrq' => '2022-03-14', 'jysj' => '12:17:43', 'jffse' => '', 'dffse' => 8645000, 'zhye' => '15001472.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k86002573', 'zy' => '借款', 'sort_num' => 80, ), 79 => array ( 'jyrq' => '2022-03-14', 'jysj' => '14:19:32', 'jffse' => 15000000, 'dffse' => '', 'zhye' => '1472.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901313011222', 'zy' => '还款', 'sort_num' => 81, ), 80 => array ( 'jyrq' => '2022-03-14', 'jysj' => '15:56:41', 'jffse' => '', 'dffse' => 3185000, 'zhye' => '3186472.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k90004357', 'zy' => '借款', 'sort_num' => 82, ), 81 => array ( 'jyrq' => '2022-03-14', 'jysj' => '16:21:33', 'jffse' => 3185000, 'dffse' => '', 'zhye' => '1472.28', 'dfzh' => '38910188000406370', 'dfmc' => '深圳中航金鼎股份公司', 'pzh' => '85099', 'lsh' => '901309017941', 'zy' => '还款', 'sort_num' => 83, ), 82 => array ( 'jyrq' => '2022-03-15', 'jysj' => '10:19:20', 'jffse' => '', 'dffse' => 9500000, 'zhye' => '9501472.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k85001186', 'zy' => '借款', 'sort_num' => 84, ), 83 => array ( 'jyrq' => '2022-03-15', 'jysj' => '10:59:03', 'jffse' => 190, 'dffse' => '', 'zhye' => '9501282.28', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901304008184', 'zy' => '网银电子汇划费', 'sort_num' => 85, ), 84 => array ( 'jyrq' => '2022-03-15', 'jysj' => '10:59:03', 'jffse' => 9500000, 'dffse' => '', 'zhye' => '1282.28', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901304008184', 'zy' => '还款', 'sort_num' => 86, ), 85 => array ( 'jyrq' => '2022-03-16', 'jysj' => '12:32:33', 'jffse' => '', 'dffse' => 7710000, 'zhye' => '7711282.28', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k85002309', 'zy' => '借款', 'sort_num' => 87, ), 86 => array ( 'jyrq' => '2022-03-16', 'jysj' => '13:20:14', 'jffse' => 154.19999999999999, 'dffse' => '', 'zhye' => '7711128.08', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901308009448', 'zy' => '网银电子汇划费', 'sort_num' => 88, ), 87 => array ( 'jyrq' => '2022-03-16', 'jysj' => '13:20:14', 'jffse' => 7710000, 'dffse' => '', 'zhye' => '1128.08', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901308009448', 'zy' => '还款', 'sort_num' => 89, ), 88 => array ( 'jyrq' => '2022-03-17', 'jysj' => '10:25:47', 'jffse' => '', 'dffse' => 6400000, 'zhye' => '6401128.08', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k92001535', 'zy' => '借款', 'sort_num' => 90, ), 89 => array ( 'jyrq' => '2022-03-17', 'jysj' => '13:39:34', 'jffse' => 128, 'dffse' => '', 'zhye' => '6401000.08', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901300009059', 'zy' => '网银电子汇划费', 'sort_num' => 91, ), 90 => array ( 'jyrq' => '2022-03-17', 'jysj' => '13:39:34', 'jffse' => 6400000, 'dffse' => '', 'zhye' => '1000.08', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901300009059', 'zy' => '还款', 'sort_num' => 92, ), 91 => array ( 'jyrq' => '2022-03-21', 'jysj' => '02:52:06', 'jffse' => '', 'dffse' => 864.14999999999998, 'zhye' => '1864.23', 'dfzh' => '35610105570000001', 'dfmc' => '应付单位活期存款利息', 'pzh' => '', 'lsh' => '993561000010', 'zy' => '结息', 'sort_num' => 93, ), 92 => array ( 'jyrq' => '2022-03-21', 'jysj' => '11:39:50', 'jffse' => '', 'dffse' => 8205000, 'zhye' => '8206864.23', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k90002008', 'zy' => '借款', 'sort_num' => 94, ), 93 => array ( 'jyrq' => '2022-03-21', 'jysj' => '12:49:18', 'jffse' => 164.09999999999999, 'dffse' => '', 'zhye' => '8206700.13', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901310008537', 'zy' => '网银电子汇划费', 'sort_num' => 95, ), 94 => array ( 'jyrq' => '2022-03-21', 'jysj' => '12:49:18', 'jffse' => 8205000, 'dffse' => '', 'zhye' => '1700.13', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901310008537', 'zy' => '还款', 'sort_num' => 96, ), 95 => array ( 'jyrq' => '2022-03-22', 'jysj' => '11:13:21', 'jffse' => '', 'dffse' => 170000, 'zhye' => '171700.13', 'dfzh' => '5240111049171717', 'dfmc' => '刘磊', 'pzh' => '060', 'lsh' => '901f04008368', 'zy' => '网银跨行汇款出借', 'sort_num' => 97, ), 96 => array ( 'jyrq' => '2022-03-25', 'jysj' => '14:00:03', 'jffse' => 153701.92000000001, 'dffse' => '', 'zhye' => '17998.21', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '', 'lsh' => '901307011167', 'zy' => '利息', 'sort_num' => 98, ), 97 => array ( 'jyrq' => '2022-03-25', 'jysj' => '14:00:03', 'jffse' => 15, 'dffse' => '', 'zhye' => '17983.21', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901307011167', 'zy' => '网银电子汇划费', 'sort_num' => 99, ), 98 => array ( 'jyrq' => '2022-04-06', 'jysj' => '16:18:12', 'jffse' => '', 'dffse' => 2238700, 'zhye' => '2256683.21', 'dfzh' => '9550880222181000000', 'dfmc' => '中税国际控股(北京)有限公司', 'pzh' => '060', 'lsh' => '901k86003559', 'zy' => '借款', 'sort_num' => 100, ), 99 => array ( 'jyrq' => '2022-04-06', 'jysj' => '16:28:39', 'jffse' => 41.700000000000003, 'dffse' => '', 'zhye' => '2256641.51', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901303015596', 'zy' => '网银电子汇划费', 'sort_num' => 101, ), 100 => array ( 'jyrq' => '2022-04-06', 'jysj' => '16:28:39', 'jffse' => 2085000, 'dffse' => '', 'zhye' => '171641.51', 'dfzh' => '743268931238', 'dfmc' => '深圳鲁宁实业有限公司', 'pzh' => '', 'lsh' => '901303015596', 'zy' => '还款', 'sort_num' => 102, ), 101 => array ( 'jyrq' => '2022-04-06', 'jysj' => '18:05:24', 'jffse' => 170000, 'dffse' => '', 'zhye' => '1641.51', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '', 'lsh' => '901306018942', 'zy' => '往来款', 'sort_num' => 103, ), 102 => array ( 'jyrq' => '2022-04-06', 'jysj' => '18:05:24', 'jffse' => 15, 'dffse' => '', 'zhye' => '1626.51', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901306018942', 'zy' => '网银电子汇划费', 'sort_num' => 104, ), 103 => array ( 'jyrq' => '2022-05-09', 'jysj' => '18:40:11', 'jffse' => '', 'dffse' => 250000, 'zhye' => '251626.51', 'dfzh' => '0200228109200088684', 'dfmc' => '寻味空间信息技术（北京）有限公司', 'pzh' => '060', 'lsh' => '901m55002069', 'zy' => '跨行转账', 'sort_num' => 105, ), 104 => array ( 'jyrq' => '2022-05-09', 'jysj' => '18:41:36', 'jffse' => 233013.70000000001, 'dffse' => '', 'zhye' => '18612.81', 'dfzh' => '110935301410101', 'dfmc' => '北京美丽赢家健康管理有限公司', 'pzh' => '', 'lsh' => '901311021437', 'zy' => '利息', 'sort_num' => 106, ), 105 => array ( 'jyrq' => '2022-05-09', 'jysj' => '18:41:36', 'jffse' => 15, 'dffse' => '', 'zhye' => '18597.81', 'dfzh' => '35610123580000001', 'dfmc' => '网上银行手续费收入', 'pzh' => '', 'lsh' => '901311021437', 'zy' => '网银电子汇划费', 'sort_num' => 107, ), );


        if(!empty($sdate) && !empty($edate)){
            $sdate = date('Y-m-d',strtotime($sdate));
            $edate = date('Y-m-d',strtotime($edate));
            if($edate>=$sdate){
                $filter_data = array();
                foreach ($bank_data as $v){
                    if($v['jyrq']>=$sdate && $v['jyrq']<=$edate){
                        $filter_data[]=$v;
                    }
                }
                $bank_data = $filter_data;
            }
        }
        if($sort_type==2){
           sortArrByOneField($bank_data,'sort_num',true);
        }
        $jffsje = $dffsje = 0;
        $jfbs = $dfbs = 0;
        foreach ($bank_data as $k=>$v){
            if(!empty($v['dffse'])){
                $dffsje+=$v['dffse'];
                $dfbs++;
            }
            if(!empty($v['jffse'])){
                $jffsje+=$v['jffse'];
                $jfbs++;
            }

            if(!empty($v['jffse'])){
                $bank_data[$k]['jffse'] = number_format($v['jffse'],2);
            }
            if(!empty($v['dffse'])){
                $bank_data[$k]['dffse'] = number_format($v['dffse'],2);
            }
            if(!empty($v['zhye'])){
                $bank_data[$k]['zhye'] = number_format($v['zhye'],2);
            }
        }
        $jffsje = sprintf("%.2f",$jffsje);
        $dffsje = sprintf("%.2f",$dffsje);

        $offset = ($page-1)*$page_size;
        $data_list = array_slice($bank_data,$offset,$page_size);
        $res_data = array('zh'=>'35610180808217773','cxsj'=>date('Y-m-d H:i:s'),
            'jffsje'=>$jffsje,'dffsje'=>$dffsje,'jfbs'=>$jfbs,'dfbs'=>$dfbs,
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