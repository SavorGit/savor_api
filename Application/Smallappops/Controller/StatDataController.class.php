<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class StatDataController extends CommonController{
    
    function _init_() {
        switch(ACTION_NAME) {
            case 'dataCenter':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'start_date'=>1000,'end_date'=>1000,);
                break;
        }
        parent::_init_();
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
        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $fields = 'id';
        $where  = [];
        $where['hotel_id'] = $hotel_id;
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $ret  = $m_welcome->field($fields)->where($where)->select();
        $send_welcome_nums = count($ret);
        
        //售酒数量
        $m_record = new \Common\Model\Finance\StockRecordModel();
        $rfileds = 'sum(a.total_amount) as total_amount,a.type';
        $rwhere['stock.hotel_id'] = $hotel_id;
        $rwhere['a.type'] = 7;
        $rwhere['a.wo_status'] = array('in',array(4));
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
    
}