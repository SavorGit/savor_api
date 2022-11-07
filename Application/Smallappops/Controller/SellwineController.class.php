<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class SellwineController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'filter':
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001,
                    'day'=>1002,'sdate'=>1002,'edate'=>1002,'sell_openid'=>1002);
                $this->is_verify = 1;
                break;
            case 'datalist':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001,'page'=>1001,
                    'area_id'=>1002,'staff_id'=>1002,'hotel_id'=>1002,
                    'sell_openid'=>1002,'status'=>1002);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function filter(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $day = intval($this->params['day']);
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];
        $sell_openid = $this->params['sell_openid'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $m_staff->checkStaffpermission($res_staff,$area_id,$staff_id);
        if($type==1001){
            $this->to_back(1001);
        }
        if($day>0){
            $start_date = date('Y-m-d',strtotime('-1day'));
            switch ($day){
                case 1:
                    $start_date = date('Y-m-d',strtotime('-1day'));
                    break;
                case 2:
                    $start_date = date('Y-m-d',strtotime('-6day'));
                    break;
                case 3:
                    $start_date = date('Y-m-01');
                    break;
            }
            $end_date = date('Y-m-d',strtotime('-1day'));
        }else{
            $start_date = $sdate;
            $end_date = $edate;
        }

        $end_time = date('Y-m-d 23:59:59',strtotime('-1day'));

        $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $merchant_where = array('m.status'=>1,'hotel.state'=>1,'hotel.flag'=>0);
        $merchant_where['m.add_time'] = array('elt',$end_time);
        $is_data = 1;
        if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
            if($area_id>0){
                $merchant_where['hotel.area_id'] = $area_id;
            }
        }elseif($area_id>0 && $staff_id>0){
            $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            $merchant_where['hotel.area_id'] = $area_id;
        }elseif($area_id==0 && $staff_id>0){
            $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
        }else{
            $is_data = 0;
        }
        $all_stock_status = C('STOCK_AUDIT_STATUS');
        $stock_status = array(array('name'=>'全部核销状态','status'=>0));
        foreach ($all_stock_status as $k=>$v){
            if($k!=3){
                $stock_status[]=array('name'=>$v,'status'=>$k);
            }
        }
        $hotel_list = array(array('hotel_id'=>0,'hotel_name'=>'全部餐厅','is_check'=>0));
        $staff_list = array(array('openid'=>'','nickName'=>'全部销售经理','staff_id'=>0,'level'=>0,'is_check'=>0));
        if($is_data){
            $hotel_id = 0;
            if(!empty($sell_openid)){
                $m_hotelstaff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_hotelstaff->getMerchantStaff('hotel.id as hotel_id',array('a.openid'=>$sell_openid,'a.status'=>1,'merchant.status'=>1));
                $hotel_id = $res_staff[0]['hotel_id'];
            }
            $merchant_fields = 'hotel.id as hotel_id,hotel.name as hotel_name';
            $res_hotel_list = $m_merchant->getMerchantInfo($merchant_fields,$merchant_where);
            foreach ($res_hotel_list as $k=>$v){
                $is_check = 0;
                if($v['hotel_id']==$hotel_id){
                    $is_check = 1;
                }
                $res_hotel_list[$k]['is_check'] = $is_check;
            }
            $hotel_list = array_merge($hotel_list,$res_hotel_list);
            if($hotel_id){
                $m_staff = new \Common\Model\Integral\StaffModel();
                $fileds = 'a.id as staff_id,a.level,a.openid,user.nickName';
                $where = array('merchant.hotel_id'=>$hotel_id,'merchant.status'=>1,'a.status'=>1);
                $res_staffs = $m_staff->getMerchantStaff($fileds,$where);
                if(!empty($res_staffs)){
                    foreach ($res_staffs as $v){
                        $is_check = 0;
                        if(!empty($sell_openid) && $sell_openid==$v['openid']){
                            $is_check = 1;
                        }
                        $v['is_check'] = $is_check;
                        $staff_list[] = $v;
                    }
                }
            }

        }
        $date_range = array(date('Y-m-d',strtotime('-30day')),date('Y-m-d',strtotime('-1day')));
        $res_data = array('start_date'=>$start_date,'end_date'=>$end_date,'date_range'=>$date_range,
            'hotel_list'=>$hotel_list,'staff_list'=>$staff_list,'stock_status'=>$stock_status);
        $this->to_back($res_data);
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $sdate = $this->params['sdate'];
        $edata = $this->params['edate'];
        $hotel_id = $this->params['hotel_id'];
        $sell_openid = $this->params['sell_openid'];
        $status = $this->params['status'];
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $offset = ($page-1)*$pagesize;
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $limit = "$offset,$pagesize";
        $order = 'a.id desc';
        $where = array('a.type'=>7);
        if($status){
            $where['a.wo_status'] = $status;
        }else{
            $where['a.wo_status'] = array('in',array(1,2,4));
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($sdate));
        $end_time = date('Y-m-d 23:59:59',strtotime($edata));
        $where['a.add_time']   = array(array('egt',$start_time),array('elt',$end_time));
        if(!empty($sell_openid)){
            $where['a.op_openid'] = $sell_openid;
        }elseif($hotel_id>0){
            $where['stock.hotel_id'] = $hotel_id;
        }else{
            $res_staff = $m_opsstaff->getInfo(array('id'=>$staff_id));
            $type = $m_opsstaff->checkStaffpermission($res_staff,$area_id,$staff_id);
            if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
                if($area_id>0){
                    $where['hotel.area_id'] = $area_id;
                }
            }elseif($area_id>0 && $staff_id>0){
                $where['ext.maintainer_id'] = $res_staff['sysuser_id'];
                $where['hotel.area_id'] = $area_id;
            }elseif($area_id==0 && $staff_id>0){
                $where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            }
        }

        $fields = 'a.idcode,a.add_time,a.wo_status as status,a.wo_reason_type as reason_type,a.op_openid';
        $res_records = $m_stock_record->getHotelStaffRecordList($fields,$where,$order,$limit);
        $data_list = array();
        if(!empty($res_records)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_usercoupon = new \Common\Model\Smallapp\UserCouponModel();
            $all_reasons = C('STOCK_REASON');
            $all_status = C('STOCK_AUDIT_STATUS');
            $fileds = 'a.idcode,a.price,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
            spec.name as spec_name,unit.name as unit_name,a.wo_status as status,a.add_time';
            foreach ($res_records as $v){
                $res_user = $m_user->getOne('*',array('openid'=>$v['op_openid']),'id desc');
                $nickName = $res_user['nickName'];
                $avatarUrl = $res_user['avatarUrl'];
                $reason = '';
                if(isset($all_reasons[$v['reason_type']])){
                    $reason = $all_reasons[$v['reason_type']]['name'];
                }
                $where = array('a.idcode'=>$v['idcode'],'a.type'=>7);
                $res_goods = $m_stock_record->getStockRecordList($fileds,$where,'a.id asc','','');
                $res_goods[0]['price'] = abs($res_goods[0]['price']);
                $res_coupon = $m_usercoupon->getUsercouponDatas('a.id,coupon.name,a.money,a.use_time',array('a.idcode'=>$v['idcode'],'ustatus'=>2),'a.id desc','0,1');

                $data_list[]=array('nickName'=>$nickName,'avatarUrl'=>$avatarUrl,'reason'=>$reason,'status'=>$v['status'],'status_str'=>$all_status[$v['status']],
                    'num'=>count($res_goods),'add_time'=>$v['add_time'],'goods'=>$res_goods,'coupon'=>$res_coupon);
            }
        }
        $this->to_back($data_list);
    }

}
