<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class StatDataController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'sale':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'source'=>1001,'start_date'=>1002,'end_date'=>1002);
                break;
            case 'user':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'start_date'=>1002,'end_date'=>1002);
                break;
            case 'dataCenter':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'start_date'=>1002,'end_date'=>1002,);
                break;
            case 'salesellwine':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'source'=>1001,'day'=>1002,'start_date'=>1002,'end_date'=>1002);
                break;
            case 'docketdate':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'report':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'start_date'=>1001,'end_date'=>1001);
                break;
            case 'statements':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'start_date'=>1001,'end_date'=>1001,'type'=>1001);
                break;
            case 'downloadstatements':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'start_date'=>1001,'end_date'=>1001,'type'=>1001);
                break;
            case 'taskdata':
                $this->valid_fields = array('openid'=>1001,'task_id'=>1002,'area_id'=>1002,'staff_id'=>1002,'stat_date'=>1002,'version'=>1002);
                break;
        }
        parent::_init_();
    }

    public function sale(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $source = intval($this->params['source']);//来源 1酒楼详情页 2酒楼数据概况页
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if(empty($start_date) || empty($end_date)){
            $start_date = date('Y-m-d',strtotime('-7 days'));
            $end_date = date('Y-m-d',strtotime('-1 days'));
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $m_staff = new \Common\Model\Integral\StaffModel();
        $staff_fields = 'a.id,a.level,merchant.name as mgr_name,user.openid,user.avatarUrl,user.nickName';
        $staff_where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
        $res_staff = $m_staff->getMerchantStaff($staff_fields,$staff_where);
        $staff_num = count($res_staff);
        $manager_name = '';
        $all_staff = array();
        foreach ($res_staff as $v){
            if($v['level']==1 && empty($manager_name)){
                $manager_name = $v['nickName'];
            }
            $all_staff[$v['openid']] = $v;
        }
        if(empty($manager_name)){
            if(!empty($res_staff)){
                $manager_name = $res_staff[0]['mgr_name'];
            }else{
                $m_merchant = new \Common\Model\Integral\MerchantModel();
                $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
                if(!empty($res_merchant)){
                    $manager_name = $res_merchant['name'];
                }
            }
        }
        $remain_integral = 0;
        if($source==1){
            $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
            $res_remain = $m_userintegral->getRemainIntegral(0,0,$hotel_id);
            if(!empty($res_remain)){
                $remain_integral = $res_remain['total_integral'];
            }
        }
        $m_statichotelstaffdata = new \Common\Model\Smallapp\StaticHotelstaffdataModel();
        $res_staticdata = $m_statichotelstaffdata->getStaffData(0,0,$hotel_id,$start_time,$end_time);

        $range_sdate = C('OPS_STAT_DATE');
        $range_smdate = date('Y-m-d',strtotime('-30day'));
        if($range_smdate>=$range_sdate){
            $range_sdate = $range_smdate;
        }
        $date_range = array($range_sdate,date('Y-m-d',strtotime('-1day')));
        $stat_range_str= '(近七天,数据更新至'.date('Y/m/d',strtotime('-1 day')).')';
        $stat_update_str = '数据更新至'.date('Y/m/d',strtotime('-1 day'));
        $res_data = array('manager_name'=>$manager_name,'staff_num'=>$staff_num,'get_integral'=>$res_staticdata['get_integral'],
            'remain_integral'=>$remain_integral,'money'=>$res_staticdata['money'],'forscreen_num'=>$res_staticdata['forscreen_num'],
            'pub_num'=>$res_staticdata['pub_num'],'welcome_num'=>$res_staticdata['welcome_num'],'birthday_num'=>$res_staticdata['birthday_num'],
            'signin_num'=>$res_staticdata['signin_num'],'task_data'=>$res_staticdata['task_data'],
            'date_range'=>$date_range,'stat_range_str'=>$stat_range_str,'stat_update_str'=>$stat_update_str,
        );
        $staff_list = array();
        if($source==2){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_finance_stockrecord = new \Common\Model\Finance\StockRecordModel();
            $res_sell = $m_finance_stockrecord->getStaticData(0,0,$hotel_id,$start_time,$end_time,'a.op_openid');
            $sell_openids = array();
            foreach ($res_sell as $v){
                $sell_openids[$v['op_openid']] = array('brand_num'=>intval($v['brand_num']),'series_num'=>intval($v['series_num']),'sell_num'=>intval($v['sell_num']));
            }
            $res_stat_staff = $m_statichotelstaffdata->getHotelStaffData($hotel_id,$start_time,$end_time);
            foreach ($res_stat_staff as $v){
                $brand_num = $series_num = $sell_num = 0;
                if(isset($sell_openids[$v['openid']])){
                    $brand_num = $sell_openids[$v['openid']]['brand_num'];
                    $series_num = $sell_openids[$v['openid']]['series_num'];
                    $sell_num = $sell_openids[$v['openid']]['sell_num'];
                }
                $v['brand_num'] = $brand_num;
                $v['series_num'] = $series_num;
                $v['sell_num'] = $sell_num;
                if(isset($all_staff[$v['openid']])){
                    $avatarUrl = $all_staff[$v['openid']]['avatarUrl'];
                    $nickName = $all_staff[$v['openid']]['nickName'];
                }else{
                    $res_user = $m_user->getOne('id,avatarUrl,nickName',array('openid'=>$v['openid']),'id desc');
                    $avatarUrl = $res_user['avatarUrl'];
                    $nickName = $res_user['nickName'];
                }
                $v['avatarUrl'] = $avatarUrl;
                $v['nickName'] = $nickName;
                $staff_list[]=$v;
            }
        }
        $res_data['staff_list'] = $staff_list;
        $this->to_back($res_data);
    }

    public function salesellwine(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $source = intval($this->params['source']);//来源 1酒楼详情页 2酒楼数据概况页
        $day = intval($this->params['day']);//1今日,2近7天,3本月
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $data_goods_ids =C('DATA_GOODS_IDS');
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if($day>0){
            switch ($day){
                case 1:
                    $start_date = date('Y-m-d');
                    break;
                case 2:
                    $start_date = date('Y-m-d',strtotime('-6day'));
                    break;
                case 3:
                case 7:
                    $start_date = date('Y-m-01');
                    break;
            }
            $end_date = date('Y-m-d');
        }else{
            if(empty($start_date) || empty($end_date)){
                $start_date = date('Y-m-d',strtotime('-6 days'));
                $end_date = date('Y-m-d');
            }
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $m_finance_stockrecord = new \Common\Model\Finance\StockRecordModel();
        $res_sell = $m_finance_stockrecord->getStaticData(0,0,$hotel_id,$start_time,$end_time);
        $sell_num = intval($res_sell[0]['sell_num']);
        $res_approved_sell = $m_finance_stockrecord->getStaticData(0,0,$hotel_id,$start_time,$end_time,'',1);
        $sell_num1 = $res_approved_sell[0]['sell_num'];
        $sell_num = $sell_num - $sell_num1;

        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_saledata = $m_sale->getStaticSaleData(0,0,$hotel_id,$start_time,$end_time);
        $date_range = array(date('Y-m-d',strtotime('-6 days')),date('Y-m-d'));
        $stat_range_str= '(近七天,数据更新至'.date('Y/m/d').')';
        $stat_update_str = '数据更新至'.date('Y/m/d');
        $res_lastsale = $m_sale->getALLDataList('id,add_time',array('hotel_id'=>$hotel_id,'goods_id'=>array('not in',$data_goods_ids)),'id desc','0,1','');
        $sell_wine_str = '';
        if(!empty($res_lastsale[0]['add_time']) && $res_lastsale[0]['add_time']<date('Y-m-d 00:00:00')){
            $no_sell_day = round((time()-strtotime($res_lastsale[0]['add_time']))/86400);
            $sell_wine_str = $no_sell_day.'日内未售酒';
        }
        $res_data = array(
            'brand_num'=>intval($res_sell[0]['brand_num']),'series_num'=>intval($res_sell[0]['series_num']),'sell_num'=>$sell_num,'sell_num1'=>$sell_num1,
            'sale_money'=>$res_saledata['sale_money'],'qk_money'=>$res_saledata['qk_money'],'cqqk_money'=>$res_saledata['cqqk_money'],
            'date_range'=>$date_range,'stat_range_str'=>$stat_range_str,'stat_update_str'=>$stat_update_str,'sell_wine_str'=>$sell_wine_str,
        );
        $staff_list = array();
        if($source==2){
            $res_sell = $m_finance_stockrecord->getStaticData(0,0,$hotel_id,$start_time,$end_time,'a.op_openid');
            $sell_openids = array();
            foreach ($res_sell as $v){
                $sell_openids[$v['op_openid']] = array('brand_num'=>intval($v['brand_num']),'series_num'=>intval($v['series_num']),'sell_num'=>intval($v['sell_num']));
            }
            $m_staff = new \Common\Model\Integral\StaffModel();
            $staff_fields = 'a.id,a.level,user.openid,user.avatarUrl,user.nickName';
            $staff_where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
            $res_stat_staff = $m_staff->getMerchantStaff($staff_fields,$staff_where);
            foreach ($res_stat_staff as $v){
                $brand_num = $series_num = $sell_num = 0;
                if(isset($sell_openids[$v['openid']])){
                    $brand_num = $sell_openids[$v['openid']]['brand_num'];
                    $series_num = $sell_openids[$v['openid']]['series_num'];
                    $sell_num = $sell_openids[$v['openid']]['sell_num'];
                }
                $v['brand_num'] = $brand_num;
                $v['series_num'] = $series_num;
                $v['sell_num'] = $sell_num;
                $v['avatarUrl'] = $v['avatarUrl'];
                $v['nickName'] = $v['nickName'];
                $staff_list[]=$v;
            }
        }
        $res_data['staff_list'] = $staff_list;
        $this->to_back($res_data);
    }

    public function user(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        if(empty($start_date) || empty($end_date)){
            $start_date = date('Y-m-d',strtotime('-7 days'));
            $end_date = date('Y-m-d',strtotime('-1 days'));
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $fields = 'count(DISTINCT forscreen_id) as num';
        $where = array('hotel_id'=>$hotel_id,'small_app_id'=>array('in','1,2'),'mobile_brand'=>array('neq','devtools'));
        $where['create_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $forscreen_data = $m_forscreen->getWhere($fields,$where, '', '','');
        $hotel_forscreen_nums = intval($forscreen_data[0]['num']);
        $fields = 'count(DISTINCT openid) as num';
        $forscreen_data = $m_forscreen->getWhere($fields, $where, '', '', '');
        $hotel_forscreen_user_nums = intval($forscreen_data[0]['num']);
        $m_box = new \Common\Model\BoxModel();
        $fields = 'a.id box_id,a.name box_name,a.mac as box_mac';
        $box_list = $m_box->getBoxListByHotelid($fields,$hotel_id);
        foreach($box_list as $key=>$v){
            $fields = 'count(DISTINCT forscreen_id) as forscreen_num,count(DISTINCT openid) as user_num';
            $where = array('hotel_id'=>$hotel_id,'box_id'=>$v['box_id'],'small_app_id'=>array('in','1,2'),'mobile_brand'=>array('neq','devtools'));
            $where['create_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $forscreenbox_data = $m_forscreen->getWhere($fields, $where, '', '', '');
            $box_list[$key]['box_forscreen_num'] = intval($forscreenbox_data[0]['forscreen_num']);
            $box_list[$key]['box_forscreen_user_num'] = intval($forscreenbox_data[0]['user_num']);
        }
        $date_range = array(date('Y-m-d',strtotime('-30day')),date('Y-m-d',strtotime('-1day')));

        $res_data = array('hotel_forscreen_nums'=>$hotel_forscreen_nums,'hotel_forscreen_user_nums'=>$hotel_forscreen_user_nums,
            'box_list'=>$box_list,'start_date'=>$start_date,'end_date'=>$end_date,'date_range'=>$date_range);
        $res_data['stat_range_str']= '(近七天,数据更新至'.date('Y/m/d',strtotime($end_time)).')';
        $res_data['stat_update_str'] = '数据更新至'.date('Y/m/d',strtotime('-1 day'));
        $this->to_back($res_data);
    }

    public function dataCenter(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $start_time = !empty($start_date)? $start_date.' 00:00:00' : date('Y-m-d 00:00:00',strtotime('-7 days'));
        $end_time   = !empty($end_date)? $end_date.' 23:59:59' : date('Y-m-d 23:59:59',strtotime('-1 days'));
        if($start_time>$end_time){
            $this->to_back(93075);
        }
        $data = [];
        //销售端数据开始
        //开通人数
        $m_merchart = new \Common\Model\Integral\StaffModel();
        $fields = 'a.id,a.parent_id,user.nickName';
        $where  = [];
        $where['merchant.hotel_id'] = $hotel_id;
        $where['a.status']            = 1;
        $ret = $m_merchart->getMerchantStaff($fields,$where);
        $staff_num = count($ret);
        //管理员
        $manager_name = '';
        foreach($ret as $key=>$v){
            if($v['parent_id']==0){
                $manager_name = $v['nickName'];
                break;
            }
        }

        //投宣传片、投生日歌
        $forscreen_adv_num = $forscreen_happy_num = 0;
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $fields = 'id,forscreen_char';
        $where  = [];
        $where['small_app_id']  = 5;
        $where['hotel_id']      = $hotel_id;
        $where['action']        = 5;
        //$where['forscreen_char']= array('neq','Happy Birthday');
        $where['create_time']   = array(array('egt',$start_time),array('elt',$end_time));
        $ret = $m_forscreen->field($fields)->where($where)->select();
        foreach($ret as $key=>$v){
            if($v['forscreen_char']=='Happy Birthday'){
                $forscreen_happy_num ++;
            }else {
                $forscreen_adv_num ++;
            }
        }
        //投屏图片视频文件次数
        $fields = 'id';
        $where  = [];
        $where['small_app_id']  = 5;
        $where['hotel_id']      = $hotel_id;
        $where['action']        = array('in','2,4,30,31');
        $where['create_time']   = array(array('egt',$start_time),array('elt',$end_time));

        $ret = $m_forscreen->field($fields)->where($where)->group('forscreen_id')->select();
        $forscreen_nums = count($ret);

        //投欢迎词次数
        $fields = 'id';
        $where  = [];
        $where['small_app_id']  = 5;
        $where['hotel_id']      = $hotel_id;
        $where['action']        = array('in','41');
        $where['create_time']   = array(array('egt',$start_time),array('elt',$end_time));

        $ret = $m_forscreen->field($fields)->where($where)->group('forscreen_id')->select();
        $forscreen_welcome_nums = count($ret);

        //签到次数
        $m_user_sigin = new \Common\Model\Smallapp\UserSigninModel();
        $fields = 'a.id';
        $where = [];
        $where['m.hotel_id'] = $hotel_id;
        $where['a.add_time']        = array(array('egt',$start_time),array('elt',$end_time));
        $ret = $m_user_sigin->alias('a')
            ->join('savor_integral_merchant_staff as s on a.openid=s.openid','left')
            ->join('savor_integral_merchant as m on s.merchant_id = m.id','left')
            ->field($fields)
            ->where($where)
            ->select();
        $sigin_nums = count($ret);
        //发送邀请函
        //$m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $m_invite = new \Common\Model\Smallapp\InvitationModel();

        $fields = 'id';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $ret  = $m_invite->field($fields)->where($where)->select();
        $send_welcome_nums = count($ret);

        //售酒数量
        $m_record = new \Common\Model\Finance\StockRecordModel();
        $rfileds = 'sum(a.total_amount) as total_amount,a.type';
        $rwhere['stock.hotel_id'] = $hotel_id;
        $rwhere['a.type'] = 7;
        $rwhere['a.wo_status'] = array('in',array(2));
        $rwhere['a.add_time']  = array(array('egt',$start_time),array('elt',$end_time));
        $res_worecord = $m_record->getStockRecordList($rfileds,$rwhere,'a.id desc','','');
        $sale_wine_nums = abs($res_worecord[0]['total_amount']);
        //获得积分
        $m_userintegral_record = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $fields = 'sum(integral) as total_amount';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['status']   = 1;
        $where['type']   = array('neq',4);
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $res_record = $m_userintegral_record->getDataList($fields,$where,'id desc',0,0);
        $integral_nums = intval($res_record[0]['total_amount']);
        //提现金额
        $m_exchange = new \Common\Model\Smallapp\ExchangeModel();
        $fields = 'sum(total_fee) as total_fee';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['status']   = 21;
        //$where['audit_status'] = 1;
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $ret = $m_exchange->field($fields)->where($where)->find();
        $exchange_fee = intval($ret['total_fee']);
        //销售端数据结束
        $sale_info['staff_num']    = $staff_num;
        $sale_info['manager_name'] = $manager_name;
        $sale_info['forscreen_adv_num'] = $forscreen_adv_num;
        $sale_info['forscreen_happy_num'] = $forscreen_happy_num;
        $sale_info['forscreen_nums'] = $forscreen_nums;
        $sale_info['forscreen_welcome_nums'] = $forscreen_welcome_nums;
        $sale_info['sigin_nums']   = $sigin_nums;
        $sale_info['send_welcome_nums'] = $send_welcome_nums;
        $sale_info['sale_wine_nums']  = $sale_wine_nums;
        $sale_info['integral_nums']   = $integral_nums;
        $sale_info['exchange_fee']    = $exchange_fee;


        //用户端数据开始
        //获取当前酒楼的投屏总次数
        $fields = 'id';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['small_app_id']  = array('in','1,2');
        $where['create_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $where['openid']   = array('neq','ofYZG4yZJHaV2h3lJHG5wOB9MzxE');
        $group  = 'forscreen_id' ;
        $forscreen_data = $m_forscreen->getWhere($fields, $where, '', '', $group);
        $hotel_forscreen_nums = count($forscreen_data);   //酒楼投屏总次数

        //当前酒楼的投屏总人数
        $fields = 'id';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['small_app_id']  = array('in','1,2');
        $where['create_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $where['openid']   = array('neq','ofYZG4yZJHaV2h3lJHG5wOB9MzxE');
        $group  = 'openid' ;
        $forscreen_data = $m_forscreen->getWhere($fields, $where, '', '', $group);
        $hotel_forscreen_user_nums = count($forscreen_data);   //酒楼投屏总人数

        //获取当前酒楼的包间
        $m_box = new \Common\Model\BoxModel();
        $fields = 'a.id box_id,a.name box_name';
        $box_list = $m_box->getBoxListByHotelid($fields,$hotel_id);
        //酒楼销售酒

        foreach($box_list as $key=>$v){
            //机顶盒的投屏次数
            $fields = 'id';
            $where  = [];
            $where['hotel_id'] = $hotel_id;
            $where['box_id']   = $v['box_id'];
            $where['small_app_id']  = array('in','1,2');
            $where['create_time'] = array(array('egt',$start_time),array('elt',$end_time));

            $where['openid']   = array('neq','ofYZG4yZJHaV2h3lJHG5wOB9MzxE');
            $group  = 'forscreen_id' ;
            $forscreen_data = $m_forscreen->getWhere($fields, $where, '', '', $group);
            $box_list[$key]['box_forscreen_num'] = count($forscreen_data);

            //机顶盒的投屏人数
            $fields = 'id';
            $where  = [];
            $where['hotel_id'] = $hotel_id;
            $where['box_id']   = $v['box_id'];
            $where['small_app_id']  = array('in','1,2');
            $where['create_time'] = array(array('egt',$start_time),array('elt',$end_time));

            $where['openid']   = array('neq','ofYZG4yZJHaV2h3lJHG5wOB9MzxE');
            $group  = 'openid' ;
            $forscreen_data = $m_forscreen->getWhere($fields, $where, '', '', $group);
            $box_list[$key]['box_forscreen_user_num'] = count($forscreen_data);
        }
        $sapp_info['hotel_forscreen_nums'] = $hotel_forscreen_nums;
        $sapp_info['hotel_forscreen_user_nums'] = $hotel_forscreen_user_nums;
        $sapp_info['box_list'] = $box_list;
        $data['sale_info'] = $sale_info;
        $data['sapp_info'] = $sapp_info;
        $data['stat_range_str']= '(近七天,数据更新至'.date('Y/m/d',strtotime($end_time)).')';
        $data['stat_update_str'] = '数据更新至'.date('Y/m/d',strtotime('-1 day'));
        $data['start_date'] = substr($start_time, 0,10);
        $data['end_date']   = substr($end_time,0,10);
        $data['select_start_date'] = '2019-12-31';
        $data['select_end_date']   = date('Y-m-d',strtotime('-1 day'));
        $this->to_back($data);
    }

    public function docketdate(){
        $openid   = $this->params['openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $data = array(
            'report'=>array('start_time'=>'2022-10','end_time'=>date('Y-m'),'desc'=>C('REPORT_DESC')),
            'statements'=>array('start_time'=>'2022-10-01','end_time'=>date('Y-m-d'),'desc'=>C('STATEMENTS_DESC'))
        );
        $this->to_back($data);
    }

    public function report(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $data_goods_ids =C('DATA_GOODS_IDS');
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $start_time = date('Y-m-01 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-31 23:59:59',strtotime($end_date));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.goods_id'=>array('not in',$data_goods_ids));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.settlement_price) as sale_money,count(a.id) as sale_num,sum(a.sale_price) as hotel_sale_money,hotel.name as hotel_name';
        $res_saledata = $m_sale->getSaleStockRecordList($fileds,$where);
        if(empty($res_saledata[0]['sale_num'])){
            $this->to_back(93107);
        }
        $sale_money=$res_saledata[0]['sale_money'];
        $sale_num=$res_saledata[0]['sale_num'];
        $sale_profit=$res_saledata[0]['hotel_sale_money']-$res_saledata[0]['sale_money']+$res_saledata[0]['hotel_sale_money']*0.1;

        $stock_num = 0;
        $stock_money = 0;
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        if(!empty($res_cache)){
            $hotel_stock = json_decode($res_cache,true);
            $m_price_template_hotel = new \Common\Model\Finance\PriceTemplateHotelModel();
            foreach ($hotel_stock['goods_list'] as $v){
                $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($hotel_id,$v['id']);
                $stock_num+=$v['stock_num'];
                $stock_money+=$v['stock_num']*$settlement_price;
            }
        }

        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.ptype'=>array('in','0,2'),'a.goods_id'=>array('not in',$data_goods_ids));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.settlement_price) as sale_money,a.ptype';
        $res_qksaledata = $m_sale->getSaleStockRecordList($fileds,$where,'a.ptype');
        $qk_money = $bf_qk_money = 0;
        if(!empty($res_qksaledata)){
            foreach ($res_qksaledata as $v){
                if($v['ptype']==0){
                    $qk_money = $v['sale_money'];
                }elseif($v['ptype']==2){
                    $bf_qk_money = $v['sale_money'];
                }
            }
        }
        if($bf_qk_money>0){
            $m_salepayrecord = new \Common\Model\Finance\SalePaymentRecordModel();
            $where = array('sale.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'sale.ptype'=>2,'sale.goods_id'=>array('not in',$data_goods_ids));
            $where['sale.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $fileds = 'sum(a.pay_money) as has_pay_money';
            $res_payrecord = $m_salepayrecord->getSalePaymentRecordList($fileds,$where);
            $has_pay_money = intval($res_payrecord[0]['has_pay_money']);
            $qk_money = $qk_money+($bf_qk_money-$has_pay_money);
        }

        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.is_expire'=>1,'a.ptype'=>array('in','0,2'),'a.goods_id'=>array('not in',$data_goods_ids));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.settlement_price) as sale_money,a.ptype';
        $res_cqqksaledata = $m_sale->getSaleStockRecordList($fileds,$where,'a.ptype');
        $cqqk_money = $bf_cqqk_money = 0;
        if(!empty($res_cqqksaledata)){
            foreach ($res_cqqksaledata as $v){
                if($v['ptype']==0){
                    $cqqk_money = $v['sale_money'];
                }elseif($v['ptype']==2){
                    $bf_cqqk_money = $v['sale_money'];
                }
            }
        }
        if($bf_cqqk_money>0){
            $m_salepayrecord = new \Common\Model\Finance\SalePaymentRecordModel();
            $where = array('sale.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'sale.is_expire'=>1,'sale.ptype'=>2,'sale.goods_id'=>array('not in',$data_goods_ids));
            $where['sale.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $fileds = 'sum(a.pay_money) as has_pay_money';
            $res_payrecord = $m_salepayrecord->getSalePaymentRecordList($fileds,$where);
            $has_pay_money = intval($res_payrecord[0]['has_pay_money']);
            $cqqk_money = $cqqk_money+($bf_cqqk_money-$has_pay_money);
        }
        
        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.goods_id'=>array('not in',$data_goods_ids));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'count(a.id) as num,a.settlement_price,a.goods_id,goods.name as goods_name';
        $res_detaildata = $m_sale->getSaleStockRecordList($fileds,$where,'a.goods_id,a.settlement_price');
        $datalist = array();
        foreach ($res_detaildata as $v){
            $price = intval($v['settlement_price']);
            $datalist[]=array('goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'num'=>$v['num'].'瓶',
                'price'=>$price.'元');
        }

        $sub_title = date('Y年m月',strtotime($start_time));
        if($start_date!=$end_date){
            $sub_title.='-'.date('Y年m月',strtotime($end_time));
        }
        $res_data = array('title'=>'财务报告','sub_title'=>$sub_title,
            'sale'=>array('money'=>$sale_money.'元','num'=>$sale_num.'瓶','sale_profit'=>$sale_profit.'元'),
            'stock'=>array('money'=>$stock_money.'元','num'=>$stock_num.'瓶'),
            'arrears'=>array('qk_money'=>$qk_money.'元','cqqk_money'=>$cqqk_money.'元'),
            'hotel_name'=>$res_saledata[0]['hotel_name'],'date'=>date('Y年m月d日'),
            'detail'=>$datalist
        );
        $this->to_back($res_data);
    }


    public function statements(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $type = $this->params['type'];//1全部销售 2未付款
        $data_goods_id = C('DATA_GOODS_IDS');
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));
        $m_sale = new \Common\Model\Finance\SaleModel();
        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.goods_id'=>array('not in',$data_goods_id));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.settlement_price) as sale_money,count(a.id) as sale_num,hotel.name as hotel_name';
        $res_saledata = $m_sale->getSaleStockRecordList($fileds,$where);
        if(empty($res_saledata[0]['sale_num'])){
            $this->to_back(93107);
        }
        $sale_money=$res_saledata[0]['sale_money'];
        $sale_num=$res_saledata[0]['sale_num'];

        $m_salepayrecord = new \Common\Model\Finance\SalePaymentRecordModel();
        $where = array('sale.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'sale.goods_id'=>array('not in',$data_goods_id));
        $where['sale.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.pay_money) as has_pay_money';
        $res_payrecord = $m_salepayrecord->getSalePaymentRecordList($fileds,$where);
        $sale_has_pay_money = intval($res_payrecord[0]['has_pay_money']);

        $stock_num = 0;
        $stock_money = 0;
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        if(!empty($res_cache)){
            $hotel_stock = json_decode($res_cache,true);
            $m_price_template_hotel = new \Common\Model\Finance\PriceTemplateHotelModel();
            foreach ($hotel_stock['goods_list'] as $v){
                $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($hotel_id,$v['id']);
                $stock_num+=$v['stock_num'];
                $stock_money+=$v['stock_num']*$settlement_price;
            }
        }

        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.ptype'=>array('in','0,2'),'a.goods_id'=>array('not in',$data_goods_id));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.settlement_price) as sale_money,a.ptype';
        $res_qksaledata = $m_sale->getSaleStockRecordList($fileds,$where,'a.ptype');
        $qk_money = $bf_qk_money = 0;
        if(!empty($res_qksaledata)){
            foreach ($res_qksaledata as $v){
                if($v['ptype']==0){
                    $qk_money = $v['sale_money'];
                }elseif($v['ptype']==2){
                    $bf_qk_money = $v['sale_money'];
                }
            }
        }
        if($bf_qk_money>0){
            $m_salepayrecord = new \Common\Model\Finance\SalePaymentRecordModel();
            $where = array('sale.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'sale.ptype'=>2,'sale.goods_id'=>array('not in',$data_goods_id));
            $where['sale.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $fileds = 'sum(a.pay_money) as has_pay_money';
            $res_payrecord = $m_salepayrecord->getSalePaymentRecordList($fileds,$where);
            $has_pay_money = intval($res_payrecord[0]['has_pay_money']);
            $qk_money = $qk_money+($bf_qk_money-$has_pay_money);
        }

        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.is_expire'=>1,'a.ptype'=>array('in','0,2'),'a.goods_id'=>array('not in',$data_goods_id));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $fileds = 'sum(a.settlement_price) as sale_money,a.ptype';
        $res_cqqksaledata = $m_sale->getSaleStockRecordList($fileds,$where,'a.ptype');
        $cqqk_money = $bf_cqqk_money = 0;
        if(!empty($res_cqqksaledata)){
            foreach ($res_cqqksaledata as $v){
                if($v['ptype']==0){
                    $cqqk_money = $v['sale_money'];
                }elseif($v['ptype']==2){
                    $bf_cqqk_money = $v['sale_money'];
                }
            }
        }
        if($bf_cqqk_money>0){
            $m_salepayrecord = new \Common\Model\Finance\SalePaymentRecordModel();
            $where = array('sale.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'sale.is_expire'=>1,'sale.ptype'=>2,'sale.goods_id'=>array('not in',$data_goods_id));
            $where['sale.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $fileds = 'sum(a.pay_money) as has_pay_money';
            $res_payrecord = $m_salepayrecord->getSalePaymentRecordList($fileds,$where);
            $has_pay_money = intval($res_payrecord[0]['has_pay_money']);
            $cqqk_money = $cqqk_money+($bf_cqqk_money-$has_pay_money);
        }

        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1,'a.goods_id'=>array('not in',$data_goods_id));
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        if($type==2){
            $where['a.ptype'] = array('in','0,2');
        }
        $datalist = array();
        $detail_url ='';
        $fileds = 'a.id,a.settlement_price,a.goods_id,goods.name as goods_name,record.add_time,a.status,user.nickName,user.avatarUrl';
        $res_detaildata = $m_sale->getSaleStockRecordList($fileds,$where);
        $d_num = 0;
        foreach ($res_detaildata as $v){
            $d_num++;
            $price = intval($v['settlement_price']);
            $add_time = date('Y.m.d H:i',strtotime($v['add_time']));
            if($v['status']==2){
                $status = '';
            }else{
                $status = '未结算';
            }
            $datalist[]=array('sale_id'=>$v['id'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'num'=>'1瓶',
                'price'=>$price.'元','status'=>$status,'add_time'=>$add_time,'nickName'=>$v['nickName'],'avatarUrl'=>$v['avatarUrl']);
        }
        if($d_num>20){
            $datalist = array();
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];
            $detail_url = $host_name."/smallappops/statData/downloadstatements?openid=$openid&hotel_id=$hotel_id&type=$type&start_date=$start_date&end_date=$end_date";
        }
        $sub_title = date('Y年m月d日',strtotime($start_time));
        if($start_date!=$end_date){
            $sub_title.='-'.date('Y年m月d日',strtotime($end_time));
        }
        $res_data = array('title'=>'对账单','sub_title'=>$sub_title,
            'sale'=>array('money'=>$sale_money.'元','num'=>$sale_num.'瓶','has_pay_money'=>$sale_has_pay_money.'元'),
            'stock'=>array('money'=>$stock_money.'元','num'=>$stock_num.'瓶'),
            'arrears'=>array('qk_money'=>$qk_money.'元','cqqk_money'=>$cqqk_money.'元'),
            'hotel_name'=>$res_saledata[0]['hotel_name'],'date'=>date('Y年m月d日'),
            'detail'=>$datalist,'detail_url'=>$detail_url
        );
        $this->to_back($res_data);
    }

    public function downloadstatements(){
        $openid   = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $start_date = $this->params['start_date'];
        $end_date   = $this->params['end_date'];
        $type = $this->params['type'];//1全部销售 2未付款
        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59',strtotime($end_date));

        $where = array('a.hotel_id'=>$hotel_id,'record.wo_reason_type'=>1);
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        if($type==2){
            $where['a.ptype'] = array('in','0,2');
        }
        $datalist = array();
        $fileds = 'a.id,a.settlement_price,a.goods_id,goods.name as goods_name,record.add_time,a.status,
        hotel.name as hotel_name,user.nickName,user.avatarUrl';
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_detaildata = $m_sale->getSaleStockRecordList($fileds,$where);
        foreach ($res_detaildata as $v){
            $price = intval($v['settlement_price']);
            $add_time = date('Y.m.d H:i',strtotime($v['add_time']));
            if($v['status']==2){
                $status = '';
            }else{
                $status = '未结算';
            }
            $datalist[]=array('sale_id'=>$v['id'],'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],'num'=>'1瓶',
                'price'=>$price.'元','status'=>$status,'add_time'=>$add_time,'nickName'=>$v['nickName'],'avatarUrl'=>$v['avatarUrl'],
                'hotel_name'=>$v['hotel_name']);
        }
        $cell = array(
            array('add_time','核销时间'),
            array('nickName','核销人名称'),
            array('goods_name','酒水名称'),
            array('num','酒水数量'),
            array('price','结算价'),
            array('status','结算状态'),
            array('hotel_name','餐厅名称'),
        );
        $filename = '对账单明细';
        $this->exportToExcel($cell,$datalist,$filename);

    }

    public function taskdata(){
        $openid = $this->params['openid'];
        $task_id = $this->params['task_id'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $stat_date = $this->params['stat_date'];
        $version = $this->params['version'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $hotel_role_type = $res_staff['hotel_role_type'];//酒楼角色类型1全国,2城市,3个人,4城市和个人,5全国财务,6城市财务
        $permission = json_decode($res_staff['permission'],true);
        $where = array();
        if($task_id>0){
            $where['a.task_id'] = $task_id;
        }
        if(in_array($hotel_role_type,array(2,4,6))){
            $where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
        }elseif($hotel_role_type==3){
            $where['a.residenter_id'] = $res_staff['sysuser_id'];
        }
        if($area_id>0){
            $where['hotel.area_id'] = $area_id;
        }
        if($staff_id>0){
            if($staff_id==99999){
                $where['a.residenter_id'] = 0;
            }else{
                $res_residenter = $m_opsstaff->getInfo(array('id'=>$staff_id));
                $where['a.residenter_id'] = $res_residenter['sysuser_id'];
            }
        }
        if(empty($stat_date)){
            $stat_date = date('Y-m');
        }
        $start_time = $stat_date.'-01 00:00:00';
        $end_time = $stat_date.'-31 23:59:59';
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        if($version>='1.0.21'){
            $where['ext.is_salehotel'] = 1;
            $where['a.off_state'] = 1;
        }

        $m_crmtask_record = new \Common\Model\Crm\TaskRecordModel();
        $fileds = 'count(DISTINCT a.hotel_id) as hotel_num,count(a.id) as release_num';
        $res_task = $m_crmtask_record->getTaskRecords($fileds,$where);
        $hotel_num = intval($res_task[0]['hotel_num']);
        $release_num = intval($res_task[0]['release_num']);

        $where['a.status'] = 3;
        $res_task = $m_crmtask_record->getTaskRecords('count(a.id) as num',$where);
        $all_finish_num = intval($res_task[0]['num']);

        $fileds = 'count(a.id) as num,a.handle_status';
        unset($where['a.status']);
        $where['a.handle_status'] = array('gt',0);
        $res_task = $m_crmtask_record->getTaskRecords($fileds,$where,'','','a.handle_status');
        $handle_num = $refuse_num = 0;
        foreach ($res_task as $v){
            if($v['handle_status']==1){
                $refuse_num = intval($v['num']);
            }
            if($v['handle_status']==2){
                $handle_num = intval($v['num']);
            }
        }
        $where['a.status'] = 3;
        $res_task = $m_crmtask_record->getTaskRecords('count(a.id) as num',$where);
        $finish_num = intval($res_task[0]['num']);

        $where['a.status'] = 2;
        $where['a.is_trigger'] = 1;
        if($version>='1.0.21'){
            $where['a.status'] = array('neq',3);
            $where['a.handle_status'] = array('in','0,2');
            $where['a.add_time'] = array('elt',$end_time);
            $where['a.finish_task_record_id'] = 0;
            $where['task.type'] = array('neq',7);
            if(isset($where['a.residenter_id'])){
                $residenter_id = $where['a.residenter_id'];
                unset($where['a.residenter_id']);
                $res_task = $m_crmtask_record->getTaskRecords('max(a.id) as last_id',$where,'','','a.hotel_id,a.task_id');
                $overdue_not_finish_num = 0;
                if(!empty($res_task)){
                    $all_overdue_not_ids = array();
                    foreach ($res_task as $v){
                        $all_overdue_not_ids[]=$v['last_id'];
                    }
                    $where = array('a.residenter_id'=>$residenter_id,'a.id'=>array('in',$all_overdue_not_ids));
                    $res_task = $m_crmtask_record->getTaskRecords('count(a.id) as num',$where,'','','');
                    $overdue_not_finish_num = intval($res_task[0]['num']);
                }
            }else{
                $res_task = $m_crmtask_record->getTaskRecords('max(a.id) as last_id',$where,'','','a.hotel_id,a.task_id');
                $overdue_not_finish_num = count($res_task);
            }
        }else{
            $res_task = $m_crmtask_record->getTaskRecords('count(a.id) as num',$where);
            $overdue_not_finish_num = intval($res_task[0]['num']);
        }

        $desc = C('TASKDATA_DESC');
        $hotel_task_order_desc = C('HOTEL_TASK_ORDER_DESC');
        $res_data = array('hotel_num'=>$hotel_num,'release_num'=>$release_num,'handle_num'=>$handle_num,'all_finish_num'=>$all_finish_num,
            'finish_num'=>$finish_num,'overdue_not_finish_num'=>$overdue_not_finish_num,'refuse_num'=>$refuse_num,'desc'=>$desc,'hotel_task_order_desc'=>$hotel_task_order_desc);
        $this->to_back($res_data);
    }
}