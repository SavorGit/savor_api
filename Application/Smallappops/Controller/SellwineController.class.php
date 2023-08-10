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
                    'hotel_id'=>1002,'day'=>1002,'sdate'=>1002,'edate'=>1002,'sell_openid'=>1002,
                    'version'=>1002,'goods_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'statdata':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001,'area_id'=>1002,
                    'staff_id'=>1002,'hotel_id'=>1002,'sell_openid'=>1002,'status'=>1002,'ptype'=>1002,'goods_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'datalist':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001,'page'=>1001,
                    'area_id'=>1002,'staff_id'=>1002,'hotel_id'=>1002,
                    'sell_openid'=>1002,'status'=>1002,'ptype'=>1002,'goods_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'hotelcontactlist':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001,'contact_id'=>1002,'hotel_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'mysalefilter':
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'mysale':
                $this->valid_fields = array('openid'=>1001,'month'=>1001,'status'=>1001,'page'=>1001,'hotel_name'=>1002);
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
        $hotel_id = intval($this->params['hotel_id']);
        $goods_id = intval($this->params['goods_id']);
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];
        $sell_openid = $this->params['sell_openid'];
        $version = $this->params['version'];

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $m_staff->checkStaffpermission($res_staff,$area_id,$staff_id);
        if($type==1001){
            $this->to_back(1001);
        }
        $is_data = 1;
        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $merchant_where = array('m.status'=>1,'hotel.state'=>1,'hotel.flag'=>0);
        $test_hotels = C('TEST_HOTEL');
        $merchant_where['hotel.id'] = array('not in',$test_hotels);
        if($day>0){
            $now_date = date('Y-m-d');
            switch ($day){
                case 1:
                    $start_date = $now_date;
                    break;
                case 2:
                    $start_date = date('Y-m-d',strtotime('-6day'));
                    break;
                case 3:
                    $start_date = date('Y-m-01');
                    break;
                default:
                    $start_date = $now_date;
            }
            $end_date = $now_date;
            $res_staff = $m_staff->getInfo(array('id'=>$staff_id));
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
        }else{
            $start_date = $sdate;
            $end_date = $edate;
            $permission = json_decode($res_staff['permission'],true);
            if($permission['hotel_info']['type']==2){
                $merchant_where['hotel.area_id'] = array('in',$permission['hotel_info']['area_ids']);
            }
            if($permission['hotel_info']['type']==3){
                $merchant_where['ext.maintainer_id'] = $res_staff['sysuser_id'];
            }
        }

        $all_stock_status = C('STOCK_AUDIT_STATUS');
        $stock_status = array(array('name'=>'全部核销状态','status'=>0));
        foreach ($all_stock_status as $k=>$v){
            if($k!=3){
                $stock_status[]=array('name'=>$v,'status'=>$k);
            }
        }
        $all_pay_types = C('STOCK_PAY_TYPES');
        $stock_pay_types = array(array('name'=>'全部收款状态','ptype'=>99));
        foreach ($all_pay_types as $k=>$v){
            $stock_pay_types[]=array('name'=>$v,'ptype'=>$k);
        }

        $hotel_list = array(array('hotel_id'=>0,'hotel_name'=>'全部餐厅','is_check'=>0));
        $staff_list = array(array('openid'=>'','nickName'=>'全部销售经理','staff_id'=>0,'level'=>0,'is_check'=>0));
        if($is_data){
            if(!empty($sell_openid)){
                $m_hotelstaff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_hotelstaff->getMerchantStaff('merchant.hotel_id',array('a.openid'=>$sell_openid,'a.status'=>1,'merchant.status'=>1));
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
        $m_goods = new \Common\Model\Finance\GoodsModel();
        $gwhere = array('status'=>1,'brand_id'=>array('not in','11,12,13,14'));
        $res_goods = $m_goods->getDataList('id,name',$gwhere,'brand_id asc');
        $goods_list = array(array('goods_id'=>0,'goods_name'=>'全部酒水','is_check'=>0));
        foreach ($res_goods as $v){
            $is_check = 0;
            if($v['id']==$goods_id){
                $is_check = 1;
            }
            $goods_list[]=array('goods_id'=>$v['id'],'goods_name'=>$v['name'],'is_check'=>$is_check);
        }

        $sell_date = '2022-05-19 08:40:10';
        $range_end_date = date('Y-m-d');
        $date_range = array(date('Y-m-d',strtotime($sell_date)),$range_end_date);
        $res_data = array('start_date'=>$start_date,'end_date'=>$end_date,'date_range'=>$date_range,
            'hotel_list'=>$hotel_list,'staff_list'=>$staff_list,'stock_status'=>$stock_status,
            'stock_pay_types'=>$stock_pay_types,'goods_list'=>$goods_list);
        $this->to_back($res_data);
    }

    public function statdata(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $sdate = $this->params['sdate'];
        $edata = $this->params['edate'];
        $hotel_id = $this->params['hotel_id'];
        $sell_openid = $this->params['sell_openid'];
        $status = $this->params['status'];
        $ptype = intval($this->params['ptype']);
        $goods_id = intval($this->params['goods_id']);

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $start_time = date('Y-m-d 00:00:00',strtotime($sdate));
        $end_time = date('Y-m-d 23:59:59',strtotime($edata));
        $static_maintainer_id = $static_area_id = $static_hotel_id = 0;
        if($hotel_id>0){
            $static_hotel_id = $hotel_id;
        }else{
            if($staff_id>0){
                $res_staff = $m_opsstaff->getInfo(array('id'=>$staff_id));
            }
            $type = $m_opsstaff->checkStaffpermission($res_staff,$area_id,$staff_id);
            if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
                if($area_id>0){
                    $static_area_id = $area_id;
                }
            }elseif($area_id>0 && $staff_id>0){
                $static_maintainer_id = $res_staff['sysuser_id'];
                $static_area_id = $area_id;
            }elseif($area_id==0 && $staff_id>0){
                $static_maintainer_id = $res_staff['sysuser_id'];
            }
        }

        $m_finance_stockrecord = new \Common\Model\Finance\StockRecordModel();
        $res_sell = $m_finance_stockrecord->getStaticData($static_area_id,$static_maintainer_id,$static_hotel_id,$start_time,$end_time,'',$status,$goods_id,$ptype);
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_saledata = $m_sale->getStaticSaleData($static_area_id,$static_maintainer_id,$static_hotel_id,$start_time,$end_time,'',$status,$goods_id,$ptype);
        $res_data = array('brand_num'=>intval($res_sell[0]['brand_num']),'series_num'=>intval($res_sell[0]['series_num']),'sell_num'=>intval($res_sell[0]['sell_num']),
            'sale_money'=>$res_saledata['sale_money'],'qk_money'=>$res_saledata['qk_money'],'cqqk_money'=>$res_saledata['cqqk_money']);
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
        $ptype = intval($this->params['ptype']);
        $goods_id = intval($this->params['goods_id']);
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
        if(!empty($goods_id)){
            $where['a.goods_id'] = $goods_id;
        }
        if(!empty($ptype) && $ptype<99){
            if($ptype==10){
                $where['sale.ptype'] = 0;
            }else{
                $where['sale.ptype'] = $ptype;
            }
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($sdate));
        $end_time = date('Y-m-d 23:59:59',strtotime($edata));
        $where['a.add_time']   = array(array('egt',$start_time),array('elt',$end_time));
        if(!empty($sell_openid)){
            $where['a.op_openid'] = $sell_openid;
        }elseif($hotel_id>0){
            $where['stock.hotel_id'] = $hotel_id;
        }else{
            if($staff_id>0){
                $res_staff = $m_opsstaff->getInfo(array('id'=>$staff_id));
            }
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

        $fields = 'a.idcode,a.add_time,a.wo_time,a.wo_status as status,a.wo_reason_type as reason_type,
        a.op_openid,hotel.name as hotel_name,hotel.id as hotel_id,sale.ptype,sale.settlement_price,ext.residenter_id';
        $res_records = $m_stock_record->getHotelStaffRecordList($fields,$where,$order,$limit);
        $data_list = array();
        if(!empty($res_records)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_usercoupon = new \Common\Model\Smallapp\UserCouponModel();
            $m_sysuser = new \Common\Model\SysUserModel();
            $all_reasons = C('STOCK_REASON');
            $all_status = C('STOCK_AUDIT_STATUS');
            $all_pay_types = C('STOCK_PAY_TYPES');
            $fileds = 'a.idcode,a.price,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
            spec.name as spec_name,unit.name as unit_name,a.wo_status as status,a.add_time';
            foreach ($res_records as $v){
                if($v['wo_time']=='0000-00-00 00:00:00'){
                    $add_time = $v['add_time'];
                }else{
                    $add_time = $v['wo_time'];
                }
                $res_user = $m_user->getOne('*',array('openid'=>$v['op_openid']),'id desc');
                $nickName = $res_user['nickName'];
                $avatarUrl = $res_user['avatarUrl'];
                $reason = '';
                if(isset($all_reasons[$v['reason_type']])){
                    $reason = $all_reasons[$v['reason_type']]['name'];
                }
                $where = array('a.idcode'=>$v['idcode'],'a.type'=>7);
                $res_goods = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1','');

                $res_goods[0]['price'] = intval($v['settlement_price']);
                $res_coupon = $m_usercoupon->getUsercouponDatas('a.id,coupon.name,a.money,a.use_time',array('a.idcode'=>$v['idcode'],'ustatus'=>2),'a.id desc','0,1');
                $ptype_str='';
                if($v['reason_type']==1){
                    if($v['ptype']==0){
                        $ptype_str = $all_pay_types[10];
                    }else{
                        $ptype_str = $all_pay_types[$v['ptype']];
                    }
                }
                $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$v['residenter_id']));
                $hotel_name = $v['hotel_name'];
                if(!empty($res_sysuser)){
                    $hotel_name = $res_sysuser['remark'].'：'.$hotel_name;
                }
                $data_list[]=array('nickName'=>$nickName,'avatarUrl'=>$avatarUrl,'reason'=>$reason,'status'=>$v['status'],'status_str'=>$all_status[$v['status']],
                    'ptype'=>$v['ptype'],'ptype_str'=>$ptype_str,
                    'num'=>count($res_goods),'add_time'=>$add_time,'goods'=>$res_goods,'coupon'=>$res_coupon,'hotel_name'=>$hotel_name,'hotel_id'=>$v['hotel_id']);
            }
        }
        $this->to_back($data_list);
    }

    public function hotelcontactlist(){
        $openid = $this->params['openid'];
        $contact_id = intval($this->params['contact_id']);
        $hotel_id = $this->params['hotel_id'];
        $type = $this->params['type'];//类型1酒楼 2个人
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
        $where = array('a.type'=>7,'a.wo_status'=>array('in',array(1,2,4)));
        $is_query = 0;
        $sell_openid = '';
        if($type==1){
            if(empty($hotel_id)){
                $this->to_back(1001);
            }
            $where['stock.hotel_id'] = $hotel_id;
            $is_query = 1;
        }else{
            if(empty($contact_id)){
                $this->to_back(1001);
            }
            $m_crmuser = new \Common\Model\Crm\ContactModel;
            $res_info = $m_crmuser->getInfo(array('id'=>$contact_id));
            if(!empty($res_info['openid'])){
                $sell_openid = $res_info['openid'];
                $where['a.op_openid'] = $sell_openid;
                $is_query = 1;
            }
        }
        $data_list = array();
        $sell_num = $brand_num = $series_num = 0;
        if($is_query){
            if($type==1){
                $sell_openid = '';
            }else{
                $hotel_id = 0;
            }
            $res_sell = $m_stock_record->getHotelStaffStaticData($hotel_id,$sell_openid);
            $sell_num = intval($res_sell[0]['sell_num']);
            $brand_num = intval($res_sell[0]['brand_num']);
            $series_num = intval($res_sell[0]['series_num']);

            $fields = 'a.idcode,a.add_time,a.wo_time,a.wo_status as status,a.wo_reason_type as reason_type,a.op_openid,hotel.id as hotel_id';
            $res_records = $m_stock_record->getHotelStaffRecordList($fields,$where,$order,$limit);
            if(!empty($res_records)){
                $m_user = new \Common\Model\Smallapp\UserModel();
                $m_usercoupon = new \Common\Model\Smallapp\UserCouponModel();
                $m_price_template_hotel = new \Common\Model\Finance\PriceTemplateHotelModel();
                $all_reasons = C('STOCK_REASON');
                $all_status = C('STOCK_AUDIT_STATUS');
                $fileds = 'a.idcode,a.price,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
                spec.name as spec_name,unit.name as unit_name,a.wo_status as status,a.add_time';
                foreach ($res_records as $v){
                    if($v['wo_time']=='0000-00-00 00:00:00'){
                        $add_time = $v['add_time'];
                    }else{
                        $add_time = $v['wo_time'];
                    }
                    $res_user = $m_user->getOne('*',array('openid'=>$v['op_openid']),'id desc');
                    $nickName = $res_user['nickName'];
                    $avatarUrl = $res_user['avatarUrl'];
                    $reason = '';
                    if(isset($all_reasons[$v['reason_type']])){
                        $reason = $all_reasons[$v['reason_type']]['name'];
                    }
                    $where = array('a.idcode'=>$v['idcode'],'a.type'=>7,'a.dstatus'=>1);
                    $res_goods = $m_stock_record->getStockRecordList($fileds,$where,'a.id asc','','a.idcode');
                    $price = abs($res_goods[0]['price']);
                    $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($v['hotel_id'],$res_goods[0]['goods_id'],1);
                    if($settlement_price>0){
                        $price = intval($settlement_price);
                    }
                    $res_goods[0]['price'] = $price;

                    $res_coupon = $m_usercoupon->getUsercouponDatas('a.id,coupon.name,a.money,a.use_time',array('a.idcode'=>$v['idcode'],'ustatus'=>2),'a.id desc','0,1');

                    $data_list[]=array('nickName'=>$nickName,'avatarUrl'=>$avatarUrl,'reason'=>$reason,'status'=>$v['status'],'status_str'=>$all_status[$v['status']],
                        'num'=>count($res_goods),'add_time'=>$add_time,'goods'=>$res_goods,'coupon'=>$res_coupon);
                }
            }
        }
        $res_data = array('sell_num'=>$sell_num,'brand_num'=>$brand_num,'series_num'=>$series_num,'datalist'=>$data_list);
        $this->to_back($res_data);
    }

    public function mysalefilter(){
        $openid = $this->params['openid'];

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $months = array();
        $now_month = date('n');
        for($i=$now_month;$i>0;$i--){
            if($i==$now_month){
                $name = '当月';
            }else{
                $name = $i.'月';
            }
            $m_value = str_pad($i,2,0,STR_PAD_LEFT);
            $months[]=array('name'=>$name,'value'=>date("Y-$m_value"));
        }
        $sale_status = array(
            array('name'=>'全部','status'=>0),
            array('name'=>'已收款','status'=>1),
            array('name'=>'未收款','status'=>2),

        );
        $this->to_back(array('month'=>$months,'all_sale_status'=>$sale_status));
    }

    public function mysale(){
        $openid = $this->params['openid'];
        $month = $this->params['month'];
        $status = intval($this->params['status']);//1已收款 2未收款
        $hotel_name = trim($this->params['hotel_name']);
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $residenter_id = $res_staff['sysuser_id'];
        $where = array('a.residenter_id'=>$residenter_id,'record.type'=>7,'record.wo_reason_type'=>1,'record.wo_status'=>2);
        if($status){
            if($status==1){
                $where['a.ptype'] = 1;
            }elseif($status==2){
                $where['a.ptype'] = array('in','0,2');
            }
        }
        if(!empty($hotel_name)){
            $where['hotel.name'] = array('like',"%$hotel_name%");
        }
        $start_time = date("$month-01 00:00:00");
        $end_time = date("$month-31 23:59:59");
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $m_sale = new \Common\Model\Finance\SaleModel();
        $m_sale_record = new \Common\Model\Finance\SalePaymentRecordModel();
        $fields = 'a.hotel_id,hotel.name as hotel_name,count(a.id) as num,sum(a.settlement_price) as sale_money,GROUP_CONCAT(a.id) as sale_ids';
        $res_sale = $m_sale->getSaleStockRecordList($fields,$where,'a.hotel_id',$limit);
        $datalist = array();
        foreach ($res_sale as $v){
            $ys_money = 0;
            $payfields = 'sum(pay_money) as has_pay_money';
            $res_pay_money = $m_sale_record->getALLDataList($payfields,array('sale_id'=>array('in',$v['sale_ids'])),'','','');
            if($res_pay_money[0]['has_pay_money']>0){
                $ys_money = $v['sale_money']-$res_pay_money[0]['has_pay_money'];
            }
            $datalist[]=array('hotel_id'=>$v['hotel_id'],'hotel_name'=>$v['hotel_name'],'num'=>$v['num'],
                'sale_money'=>$v['sale_money'],'ys_money'=>$ys_money);
        }
        $this->to_back(array('datalist'=>$datalist));
    }
}
