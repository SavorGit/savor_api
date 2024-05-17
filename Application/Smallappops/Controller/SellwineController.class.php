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
                    'staff_id'=>1002,'hotel_id'=>1002,'sell_openid'=>1002,'status'=>1002,'ptype'=>1002,'goods_id'=>1002,
                    'type'=>1002);
                $this->is_verify = 1;
                break;
            case 'datalist':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001,'page'=>1001,
                    'area_id'=>1002,'staff_id'=>1002,'hotel_id'=>1002,'status'=>1002,'recycle_status'=>1002,
                    'ptype'=>1002,'goods_id'=>1002,'type'=>1002);
                $this->is_verify = 1;
                break;
            case 'auditWriteoff':
                $this->valid_fields = array('openid'=>1001,'stock_record_id'=>1001,'status'=>1001,'reason'=>1002);
                $this->is_verify = 1;
                break;
            case 'salelist':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001,'page'=>1001,
                    'area_id'=>1002,'staff_id'=>1002,'hotel_id'=>1002,'status'=>1002,'recycle_status'=>1002,
                    'ptype'=>1002,'goods_id'=>1002,'type'=>1002);
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
                $this->valid_fields = array('openid'=>1001,'month'=>1001,'status'=>1001,'page'=>1001,'hotel_name'=>1002,'version'=>1002);
                $this->is_verify = 1;
                break;
            case 'groupby':
                $this->valid_fields = array('openid'=>1001,'sdate'=>1001,'edate'=>1001,'page'=>1001);
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
        $data_goods_ids = C('DATA_GOODS_IDS');
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
        $stock_status = array(array('name'=>'售酒核销状态','status'=>0));
        foreach ($all_stock_status as $k=>$v){
            if($k!=4){
                $stock_status[]=array('name'=>$v,'status'=>$k);
            }
        }
        $all_stock_recycle_status = C('STOCK_RECYCLE_STATUS');
        $recycle_status = array(array('name'=>'开瓶奖励状态','recycle_status'=>0));
        foreach ($all_stock_recycle_status as $k=>$v){
            $recycle_status[]=array('name'=>$v,'recycle_status'=>$k);
        }
        $all_pay_types = C('STOCK_PAY_TYPES');
        $stock_pay_types = array(array('name'=>'全部收款状态','ptype'=>99));
        foreach ($all_pay_types as $k=>$v){
            $stock_pay_types[]=array('name'=>$v,'ptype'=>$k);
        }
        $all_sale_types = C('STOCK_SALE_TYPES');
        $stock_sale_types = array(array('name'=>'全部销售类型','type'=>0));
        foreach ($all_sale_types as $k=>$v){
            $stock_sale_types[]=array('name'=>$v,'type'=>$k);
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
        $gwhere = array('status'=>1,'brand_id'=>array('not in','11,12,13,14'),'id'=>array('not in',$data_goods_ids));
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
            'hotel_list'=>$hotel_list,'staff_list'=>$staff_list,'stock_status'=>$stock_status,'recycle_status'=>$recycle_status,
            'stock_pay_types'=>$stock_pay_types,'stock_sale_types'=>$stock_sale_types,'goods_list'=>$goods_list);
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
        $recycle_status = $this->params['recycle_status'];
        $ptype = intval($this->params['ptype']);
        $param_type = intval($this->params['type']);
        $goods_id = intval($this->params['goods_id']);
        $data_goods_ids = C('DATA_GOODS_IDS');

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
        $res_sell = $m_finance_stockrecord->getStaticData($static_area_id,$static_maintainer_id,$static_hotel_id,$start_time,$end_time,'',$status,$goods_id,$ptype,$recycle_status);
        $sell_num = $res_sell[0]['sell_num'];
        switch ($status){
            case 0:
                $res_approved_sell = $m_finance_stockrecord->getStaticData($static_area_id,$static_maintainer_id,$static_hotel_id,$start_time,$end_time,'',1,$goods_id,$ptype,$recycle_status);
                $sell_num1 = $res_approved_sell[0]['sell_num'];
                $sell_num = $sell_num - $sell_num1;
                break;
            case 1:
            case 3:
                $sell_num1 = 0;
                break;
            case 2:
                $res_approved_sell = $m_finance_stockrecord->getStaticData($static_area_id,$static_maintainer_id,$static_hotel_id,$start_time,$end_time,'',1,$goods_id,$ptype,$recycle_status);
                $sell_num1 = $res_approved_sell[0]['sell_num'];
                break;
            default:
                $sell_num1 = 0;
        }
        $m_sale = new \Common\Model\Finance\SaleModel();
        $res_saledata = $m_sale->getStaticSaleData($static_area_id,$static_maintainer_id,$static_hotel_id,$start_time,$end_time,'',$status,$goods_id,$ptype,$recycle_status);

        $res_groupdata = array();
        if($param_type==0 || $param_type==4){
            $gfields = 'count(DISTINCT goods.series_id) as groupby_series_num,sum(a.num) as groupby_num,sum(a.settlement_price) as groupby_money';
            $gwhere = array('a.type'=>4,'a.add_time'=>array(array('egt',$start_time),array('elt',$end_time)));
            if($static_area_id){
                $gwhere['a.area_id'] = $static_area_id;
            }
            if($static_maintainer_id){
                $gwhere['a.maintainer_id'] = $static_maintainer_id;
            }
            $res_groupdata = $m_sale->getGroupSaleDatas($gfields,$gwhere);
        }

        $res_data = array('brand_num'=>intval($res_sell[0]['brand_num']),'series_num'=>intval($res_sell[0]['series_num']),'sell_num'=>$sell_num,'sell_num1'=>$sell_num1,
            'sale_money'=>$res_saledata['sale_money'],'groupby_series_num'=>intval($res_groupdata[0]['groupby_series_num']),
            'groupby_num'=>intval($res_groupdata[0]['groupby_num']),'groupby_money'=>intval($res_groupdata[0]['groupby_money']),
            'qk_money'=>$res_saledata['qk_money'],'cqqk_money'=>$res_saledata['cqqk_money']);
        $this->to_back($res_data);
    }

    public function salelist(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $sdate = $this->params['sdate'];
        $edata = $this->params['edate'];
        $hotel_id = $this->params['hotel_id'];
        $ptype = intval($this->params['ptype']);
        $params_type = intval($this->params['type']);
        $goods_id = intval($this->params['goods_id']);
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }

        $data_goods_ids = C('DATA_GOODS_IDS');
        $where = array();
        if(!empty($goods_id)){
            $where['goods_id'] = $goods_id;
        }else {
            $where['goods_id'] = array('not in',$data_goods_ids);
        }
        if(!empty($ptype) && $ptype<99){
            if($ptype==10){
                $where['ptype'] = 0;
            }else{
                $where['ptype'] = $ptype;
            }
        }
        if($params_type){
            $where['type'] = $params_type;
        }else{
            $where['type'] = array('in','1,4');
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($sdate));
        $end_time = date('Y-m-d 23:59:59',strtotime($edata));
        $where['add_time']   = array(array('egt',$start_time),array('elt',$end_time));
        if($hotel_id>0){
            $where['hotel_id'] = $hotel_id;
        }else{
            if($staff_id>0){
                $res_staff = $m_opsstaff->getInfo(array('id'=>$staff_id));
            }
            $type = $m_opsstaff->checkStaffpermission($res_staff,$area_id,$staff_id);
            if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
                if($area_id>0){
                    $where['area_id'] = $area_id;
                }
            }elseif($area_id>0 && $staff_id>0){
                $where['maintainer_id'] = $res_staff['sysuser_id'];
                $where['area_id'] = $area_id;
            }elseif($area_id==0 && $staff_id>0){
                $where['maintainer_id'] = $res_staff['sysuser_id'];
            }
        }

        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $order = 'id desc';
        $m_sale = new \Common\Model\Finance\SaleModel();
        $fields = 'idcode,add_time,hotel_id,ptype,type,settlement_price,residenter_id,sale_openid,order_id,num';
        $res_sale = $m_sale->getALLDataList($fields,$where,$order,$limit);
        $data_list = array();
        if(!empty($res_sale)){
            $m_stock_record = new \Common\Model\Finance\StockRecordModel();
            $m_user = new \Common\Model\Smallapp\UserModel();
            $m_usercoupon = new \Common\Model\Smallapp\UserCouponModel();
            $m_sysuser = new \Common\Model\SysUserModel();
            $m_hotel = new \Common\Model\HotelModel();
            $m_order = new \Common\Model\Smallapp\OrderModel();
            $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
            $all_reasons = C('STOCK_REASON');
            $all_status = C('STOCK_AUDIT_STATUS');
            $all_pay_types = C('STOCK_PAY_TYPES');
            $fileds = 'a.idcode,a.price,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,
            spec.name as spec_name,unit.name as unit_name,a.wo_status as status,a.wo_reason_type,a.add_time,a.wo_time';
            foreach ($res_sale as $v){
                if($v['type']==1){
                    $where = array('a.idcode'=>$v['idcode'],'a.type'=>7);
                    $res_goods = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1','');
                    $res_goods[0]['price'] = intval($v['settlement_price']);

                    $add_time = $v['add_time'];
                    $reason_type = $res_goods[0]['wo_reason_type'];
                    $status = $res_goods[0]['status'];

                    $res_user = $m_user->getOne('*',array('openid'=>$v['sale_openid']),'id desc');
                    $nickName = $res_user['nickName'];
                    $avatarUrl = $res_user['avatarUrl'];
                    $reason = '';
                    if(isset($all_reasons[$reason_type])){
                        $reason = $all_reasons[$reason_type]['name'];
                    }
                    $res_coupon = $m_usercoupon->getUsercouponDatas('a.id,coupon.name,a.money,a.use_time',array('a.idcode'=>$v['idcode'],'ustatus'=>2),'a.id desc','0,1');
                    $ptype_str='';
                    if($reason_type==1){
                        if($v['ptype']==0){
                            $ptype_str = $all_pay_types[10];
                        }else{
                            $ptype_str = $all_pay_types[$v['ptype']];
                        }
                    }
                    $res_hotel = $m_hotel->getOneById('name',$v['hotel_id']);
                    $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$v['residenter_id']));
                    $hotel_name = '';
                    if(!empty($res_sysuser) && !empty($res_hotel)){
                        $hotel_name = $res_sysuser['remark'].'：'.$res_hotel['name'];
                    }

                    $info = array('nickName'=>$nickName,'avatarUrl'=>$avatarUrl,'reason'=>$reason,'status'=>$status,'status_str'=>$all_status[$status],
                        'ptype'=>$v['ptype'],'ptype_str'=>$ptype_str,'type'=>$v['type'],
                        'num'=>count($res_goods),'add_time'=>$add_time,'goods'=>$res_goods,'coupon'=>$res_coupon,'hotel_name'=>$hotel_name,'hotel_id'=>$v['hotel_id']);
                }else{
                    $order_id = $v['order_id'];
                    $gofields = 'a.id,a.openid,a.goods_id,a.amount,a.total_fee,a.add_time,user.nickName,user.avatarUrl,fg.id as goods_id,fg.name as goods_name';
                    $gowhere = array('a.id'=>$order_id);
                    $res_order = $m_order->getGroupbyOrders($gofields,$gowhere);
                    $res_settlement = $m_ordersettlement->getOrdersettlement('a.money,duser.name,duser.level',array('a.order_id'=>$order_id));

                    $info = array('nickName'=>$res_order[0]['nickName'],'avatarUrl'=>$res_order[0]['avatarUrl'],'type'=>$v['type'],
                        'num'=>$v['num'],'total_fee'=>intval($res_order[0]['total_fee']),'add_time'=>$res_order[0]['add_time'],
                        'goods'=>array(array('goods_id'=>$res_order[0]['goods_id'],'goods_name'=>$res_order[0]['goods_name'])),
                        'settlement'=>$res_settlement
                    );
                }

                $data_list[]=$info;
            }
        }
        $this->to_back($data_list);
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);
        $sdate = $this->params['sdate'];
        $edata = $this->params['edate'];
        $hotel_id = $this->params['hotel_id'];
        $ptype = intval($this->params['ptype']);
        $params_type = intval($this->params['type']);
        $goods_id = intval($this->params['goods_id']);
        $page = intval($this->params['page']);
        $status = intval($this->params['status']);
        $recycle_status = intval($this->params['recycle_status']);
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_sysuid = $res_staff['sysuser_id'];
        $data_goods_ids = C('DATA_GOODS_IDS');
        $where = array();
        if(!empty($goods_id)){
            $where['a.goods_id'] = $goods_id;
        }else {
            $where['a.goods_id'] = array('not in',$data_goods_ids);
        }
        if(!empty($ptype) && $ptype<99){
            if($ptype==10){
                $where['a.ptype'] = 0;
            }else{
                $where['a.ptype'] = $ptype;
            }
        }
        if($params_type){
            $where['a.type'] = $params_type;
        }else{
            $where['a.type'] = array('in','1,4');
        }
        $start_time = date('Y-m-d 00:00:00',strtotime($sdate));
        $end_time = date('Y-m-d 23:59:59',strtotime($edata));
        $where['a.add_time']   = array(array('egt',$start_time),array('elt',$end_time));
        if($hotel_id>0){
            $where['a.hotel_id'] = $hotel_id;
        }else{
            if($staff_id>0){
                $res_staff = $m_opsstaff->getInfo(array('id'=>$staff_id));
            }
            $type = $m_opsstaff->checkStaffpermission($res_staff,$area_id,$staff_id);
            if(in_array($type,array(1,2,4)) && ($area_id==0 || ($area_id>0 && $staff_id==0))){
                if($area_id>0){
                    $where['a.area_id'] = $area_id;
                }
            }elseif($area_id>0 && $staff_id>0){
                $where['a.maintainer_id'] = $res_staff['sysuser_id'];
                $where['a.area_id'] = $area_id;
            }elseif($area_id==0 && $staff_id>0){
                $where['a.maintainer_id'] = $res_staff['sysuser_id'];
            }
        }
        if($status){
            $where['record.wo_status'] = $status;
        }
        if($recycle_status){
            $where['record.recycle_status'] = $recycle_status;
        }

        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $m_sale = new \Common\Model\Finance\SaleModel();
        $fields = 'a.stock_record_id,a.idcode,a.add_time,a.hotel_id,a.ptype,a.type,a.settlement_price,a.residenter_id,a.sale_openid,a.order_id,a.num,
        record.price,record.wo_data_imgs,record.reason,goods.id as goods_id,goods.name as goods_name,cate.name as cate_name,spec.name as spec_name,unit.name as unit_name,
        record.wo_status,record.recycle_status,record.wo_reason_type,record.wo_time,user.nickName,user.avatarUrl,hotel.name as hotel_name,ext.residenter_id as now_residenter_id';
        $res_salerecord = $m_sale->getSaleStockRecordList($fields,$where,'',$limit,'a.id desc');
        $data_list = array();
        if(!empty($res_salerecord)){
            $m_usercoupon = new \Common\Model\Smallapp\UserCouponModel();
            $m_sysuser = new \Common\Model\SysUserModel();
            $m_order = new \Common\Model\Smallapp\OrderModel();
            $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
            $all_reasons = C('STOCK_REASON');
            $all_status = C('STOCK_AUDIT_STATUS');
            $all_pay_types = C('STOCK_PAY_TYPES');
            $all_stock_recycle_status = C('STOCK_RECYCLE_STATUS');
            $oss_host = get_oss_host();
            foreach ($res_salerecord as $v){
                if($v['type']==1){
                    $res_goods = array(
                        array('idcode'=>$v['idcode'],'price'=>intval($v['settlement_price']),'goods_id'=>$v['goods_id'],'goods_name'=>$v['goods_name'],
                            'cate_name'=>$v['cate_name'],'spec_name'=>$v['spec_name'],'unit_name'=>$v['unit_name'],'status'=>$v['wo_status'],
                            'wo_reason_type'=>$v['wo_reason_type'],'add_time'=>$v['add_time'],'wo_time'=>$v['wo_time'],'reason'=>$v['reason']
                            )
                    );
                    $status = $v['wo_status'];
                    $status_str = '售酒奖励'.$all_status[$status];
                    $recycle_status = $v['recycle_status'];
                    $nickName = $v['nickName'];
                    $avatarUrl = $v['avatarUrl'];
                    $reason = '';
                    $reason_type = $v['wo_reason_type'];
                    if(isset($all_reasons[$reason_type])){
                        $reason = $all_reasons[$reason_type]['name'];
                    }
                    $res_coupon = $m_usercoupon->getUsercouponDatas('a.id,coupon.name,a.money,a.use_time',array('a.idcode'=>$v['idcode'],'ustatus'=>2),'a.id desc','0,1');
                    $ptype_str='';
                    if($reason_type==1){
                        if($v['ptype']==0){
                            $ptype_str = $all_pay_types[10];
                        }else{
                            $ptype_str = $all_pay_types[$v['ptype']];
                        }
                    }
                    $res_sysuser = $m_sysuser->getUserInfo(array('id'=>$v['residenter_id']));
                    $hotel_name = $v['hotel_name'];
                    if(!empty($res_sysuser) && !empty($hotel_name)){
                        $hotel_name = $res_sysuser['remark'].'：'.$hotel_name;
                    }
                    $sell_audit_button = 0;
                    $recycle_status_str = '';
                    if($status==1){
                        if($ops_staff_sysuid==$v['now_residenter_id']){
                            $sell_audit_button = 1;
                        }
                    }elseif($status==2){
                        $recycle_status_str = '开瓶奖励'.$all_stock_recycle_status[$recycle_status];
                    }
                    $wo_data_imgs = array();
                    if(!empty($v['wo_data_imgs'])){
                        $wo_data_imgs_arr = explode(',',$v['wo_data_imgs']);
                        foreach ($wo_data_imgs_arr as $imv){
                            if(!empty($imv)){
                                $wo_data_imgs[]=$oss_host.$imv;
                            }
                        }
                    }
                    $info = array('nickName'=>$nickName,'avatarUrl'=>$avatarUrl,'reason'=>$reason,'status'=>$status,'status_str'=>$status_str,
                        'ptype'=>$v['ptype'],'ptype_str'=>$ptype_str,'type'=>$v['type'],'num'=>$v['num'],'add_time'=>$v['add_time'],'stock_record_id'=>$v['stock_record_id'],
                        'goods'=>$res_goods,'coupon'=>$res_coupon,'hotel_name'=>$hotel_name,'hotel_id'=>$v['hotel_id'],'sell_audit_button'=>$sell_audit_button,
                        'recycle_status'=>$recycle_status,'recycle_status_str'=>$recycle_status_str,'wo_data_imgs'=>$wo_data_imgs,'wo_reason_type'=>$v['wo_reason_type']);
                }else{
                    $order_id = $v['order_id'];
                    $gofields = 'a.id,a.openid,a.goods_id,a.amount,a.total_fee,a.add_time,user.nickName,user.avatarUrl,fg.id as goods_id,fg.name as goods_name';
                    $gowhere = array('a.id'=>$order_id);
                    $res_order = $m_order->getGroupbyOrders($gofields,$gowhere);
                    $res_settlement = $m_ordersettlement->getOrdersettlement('a.money,duser.name,duser.level',array('a.order_id'=>$order_id));

                    $info = array('nickName'=>$res_order[0]['nickName'],'avatarUrl'=>$res_order[0]['avatarUrl'],'type'=>$v['type'],
                        'num'=>$v['num'],'total_fee'=>intval($res_order[0]['total_fee']),'add_time'=>$res_order[0]['add_time'],
                        'goods'=>array(array('goods_id'=>$res_order[0]['goods_id'],'goods_name'=>$res_order[0]['goods_name'])),
                        'settlement'=>$res_settlement
                    );
                }

                $data_list[]=$info;
            }
        }
        $this->to_back($data_list);
    }

    public function auditWriteoff(){
        $openid = $this->params['openid'];
        $stock_record_id = intval($this->params['stock_record_id']);
        $status = intval($this->params['status']);//2审核通过、3审核不通过
        $reason = trim($this->params['reason']);

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $ops_staff_sysuid = $res_staff['sysuser_id'];
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fields = 'a.id,a.wo_status,sale.hotel_id,ext.residenter_id';
        $res_record = $m_stock_record->getHotelStaffRecordList($fields,array('a.id'=>$stock_record_id),'','');
        $message = '';
        if(!empty($res_record[0]['id']) && $res_record[0]['wo_status']==1 && $res_record[0]['residenter_id']==$ops_staff_sysuid){
            $hotel_id = $res_record[0]['hotel_id'];

            $up_record = array('wo_status'=>$status,'update_time'=>date('Y-m-d H:i:s'));
            $m_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
            $rwhere = array('jdorder_id'=>$stock_record_id,'type'=>17,'status'=>2);
            $res_recordinfo = $m_integralrecord->getALLDataList('id,openid,integral,hotel_id',$rwhere,'id desc','0,2','');
            if($status==2){
                $up_record['wo_time']=date('Y-m-d H:i:s');
                $m_stock_record->updateData(array('id'=>$stock_record_id),$up_record);
                if(!empty($res_recordinfo[0]['id'])){
                    $where = array('hotel_id'=>$hotel_id,'status'=>1);
                    $m_merchant = new \Common\Model\Integral\MerchantModel();
                    $res_merchant = $m_merchant->getInfo($where);
                    $is_integral = $res_merchant['is_integral'];
                    $m_userintegral = new \Common\Model\Smallapp\UserIntegralModel();
                    foreach ($res_recordinfo as $rv){
                        $record_id = $rv['id'];
                        $m_integralrecord->updateData(array('id'=>$record_id),array('status'=>1,'integral_time'=>date('Y-m-d H:i:s')));
                        $now_integral = $rv['integral'];
                        if($is_integral==1){
                            $res_integral = $m_userintegral->getInfo(array('openid'=>$rv['openid']));
                            if(!empty($res_integral)){
                                $userintegral = $res_integral['integral']+$now_integral;
                                $m_userintegral->updateData(array('id'=>$res_integral['id']),array('integral'=>$userintegral,'update_time'=>date('Y-m-d H:i:s')));
                            }else{
                                $m_userintegral->add(array('openid'=>$rv['openid'],'integral'=>$now_integral));
                            }
                        }else{
                            $where = array('id'=>$res_merchant['id']);
                            $m_merchant->where($where)->setInc('integral',$now_integral);
                        }
                    }
                    $message = '审核通过';
                }
            }elseif($status==3){
                if(!empty($reason)){
                    $up_record['reason'] = $reason;
                }
                $m_stock_record->updateData(array('id'=>$stock_record_id),$up_record);
                if(!empty($res_recordinfo[0]['id'])){
                    $del_record_ids = array();
                    foreach ($res_recordinfo as $rv){
                        $del_record_ids[] = $rv['id'];
                    }
                    $m_integralrecord->delData(array('id'=>array('in',$del_record_ids)));
                }
                $message = '审核不通过';
            }
        }
        $this->to_back(array('message'=>$message));
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
        $data_goods_ids =C('DATA_GOODS_IDS');
        $where = array('a.type'=>7,'a.wo_status'=>array('in',array(1,2,4)),'a.goods_id'=>array('not in',$data_goods_ids));
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
        $version = $this->params['version'];
        $data_goods_id = C('DATA_GOODS_IDS');
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $residenter_id = $res_staff['sysuser_id'];
        $where = array('a.residenter_id'=>$residenter_id,'a.type'=>1,'record.type'=>7,'record.wo_reason_type'=>1,'record.wo_status'=>2,'a.goods_id'=>array('not in',$data_goods_id));
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
        $end_time = date("Y-m-t 23:59:59", strtotime($start_time));
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
                'sale_money'=>$v['sale_money'],'ys_money'=>$ys_money,'type'=>1);
        }
        $group_data = array('num'=>0);
        if($version>='1.0.21' && $page==1){
            $swhere = array('type'=>4,'maintainer_id'=>$residenter_id);
            $swhere['add_time'] = array(array('egt',$start_time),array('elt',$end_time));
            $sfields = 'sum(num) as total_num,sum(settlement_price) as sale_money';
            $res_group = $m_sale->getALLDataList($sfields,$swhere,'','','');
            if(!empty($res_group[0]['total_num'])){
                $group_data = array('hotel_id'=>0,'hotel_name'=>'团购售卖','num'=>$res_group[0]['total_num'],
                    'sale_money'=>$res_group[0]['sale_money'],'ys_money'=>0,'type'=>4);
            }
        }
        $this->to_back(array('datalist'=>$datalist,'group_data'=>$group_data,'sdate'=>date('Y-m-d',strtotime($start_time)),'edate'=>date('Y-m-d',strtotime($end_time))));
    }

    public function groupby(){
        $openid = $this->params['openid'];
        $sdate = $this->params['sdate'];
        $edate = $this->params['edate'];
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_opsstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opsstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $residenter_id = $res_staff['sysuser_id'];

        $start_time = "$sdate 00:00:00";
        $end_time = "$edate 23:59:59";
        $swhere = array('a.type'=>4,'a.maintainer_id'=>$residenter_id);
        $swhere['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $offset = ($page-1)*$pagesize;
        $limit = "$offset,$pagesize";
        $sfields = 'a.order_id,o.goods_id,o.amount,o.otype,o.total_fee,o.status,o.contact,o.buy_type,o.sale_uid,o.add_time';
        $m_sale = new \Common\Model\Finance\SaleModel();
        $datalist = $m_sale->alias('a')
            ->field($sfields)
            ->join('savor_smallapp_order o on a.order_id=o.id','left')
            ->where($swhere)
            ->order('a.id desc')
            ->limit($limit)
            ->select();
        if(!empty($datalist)){
            $oss_host = get_oss_host();
            $all_status = C('ORDER_STATUS');
            $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            foreach($datalist as $k=>$v){
                $datalist[$k]['type'] = $v['otype'];
                $datalist[$k]['status_str'] = $all_status[$v['status']];
                $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));

                $order_id = $v['order_id'];
                $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.gtype,goods.attr_name,goods.parent_id,
                goods.model_media_id,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
                $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                $goods = array();
                foreach ($res_goods as $gv){
                    $goods_name = $gv['goods_name'];
                    $cover_imgs_info = explode(',',$gv['cover_imgs']);
                    $img = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    $ginfo = array('id'=>$gv['goods_id'],'name'=>$goods_name,'price'=>$gv['price'],'amount'=>$gv['amount'],
                        'status'=>$gv['status'],'img'=>$img);
                    $goods[]=$ginfo;
                }
                $datalist[$k]['goods'] = $goods;
                $datalist[$k]['price'] = $goods[0]['price'];

                $stfields = 'a.money,duser.name,duser.level,duser.sysuser_id,user.nickName,user.avatarUrl';
                $res_settlement = $m_ordersettlement->getOrdersettlement($stfields,array('a.order_id'=>$order_id));
                $income_money = 0;
                $distribution = array('money'=>0);
                foreach ($res_settlement as $sv){
                    if($sv['sysuser_id']==$residenter_id){
                        $income_money = $sv['money'];
                    }else{
                        $distribution['money'] = $sv['money'];
                        $distribution['nickName'] = $sv['nickName'];
                        $distribution['avatarUrl'] = $sv['avatarUrl'];
                    }
                }
                $datalist[$k]['income_money'] = $income_money;
                $datalist[$k]['distribution'] = $distribution;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

}
