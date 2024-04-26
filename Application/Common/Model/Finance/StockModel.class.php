<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class StockModel extends BaseModel{
	protected $tableName='finance_stock';

    public function checkHotelThreshold($hotel_id,$is_check=0){
        if($is_check==0){
            return 1;
        }
        $m_sale = new \Common\Model\Finance\SaleModel();
        $fileds = 'sum(a.settlement_price) as money';
        $where = array('a.hotel_id'=>$hotel_id,'a.ptype'=>0,'record.type'=>7,'record.wo_reason_type'=>1,'record.wo_status'=>2);
        $res_data = $m_sale->getSaleStockRecordList($fileds,$where);
        $ys_money = intval($res_data[0]['money']);

        $where['a.is_expire'] = 1;
        $res_data = $m_sale->getSaleStockRecordList($fileds,$where);
        $cq_money = intval($res_data[0]['money']);

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $cache_key = C('FINANCE_HOTELSTOCK');
        $hotel_cache_key = $cache_key.":$hotel_id";
        $res_hotel_stock = $redis->get($hotel_cache_key);
        $stock_num = 0;
        if(!empty($res_hotel_stock)){
            $hotel_stock = json_decode($res_hotel_stock,true);
            foreach ($hotel_stock['goods_list'] as $v){
                $stock_num+=$v['stock_num'];
            }
        }
        $m_sys_config = new \Common\Model\SysConfigModel();
        $res_config = $m_sys_config->getAllconfig();
        $sale_ys_money = $res_config['sale_ys_money'];
        $sale_cq_money = $res_config['sale_cq_money'];
        $hotel_stock_num = $res_config['hotel_stock_num'];
        $is_out = 1;
        if($ys_money>=$sale_ys_money || $cq_money>=$sale_cq_money || $stock_num>=$hotel_stock_num){
            $is_out = 0;
        }
        return $is_out;
    }

    public function createOut($res_approval){
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getOneById('name,area_id',$res_approval['hotel_id']);
        $area_id = $res_hotel['area_id'];
        $stock_serial_number_prefix = array(
            '1'=>array('in'=>'BJRK','out'=>'BJCK'),
            '9'=>array('in'=>'SHRK','out'=>'SHCK'),
            '236'=>array('in'=>'GZRK','out'=>'GZCK'),
            '246'=>array('in'=>'SZRK','out'=>'SZCK'),
            '248'=>array('in'=>'FSRK','out'=>'FSCK'),
        );
        $nowdate = date('Ymd',strtotime($res_approval['delivery_time']));
        $field = 'count(id) as num';
        $where = array('type'=>20,'area_id'=>$area_id,'DATE_FORMAT(add_time, "%Y%m%d")'=>$nowdate);
        $res_stock = $this->getALLDataList($field,$where,'',"0,1",'');
        if($res_stock[0]['num']>0){
            $number = $res_stock[0]['num']+1;
        }else{
            $number = 1;
        }
        $num_str = str_pad($number,3,'0',STR_PAD_LEFT);
        $serial_number = $stock_serial_number_prefix[$area_id]['out'].$nowdate.$num_str;
        $name = $res_hotel['name'].$res_approval['bottle_num'].'瓶';
        $io_date = date('Y-m-d',strtotime($res_approval['delivery_time']));
        $m_department_user = new \Common\Model\Finance\DepartmentUserModel();
        $res_duser = $m_department_user->getInfo(array('sys_user_id'=>$res_approval['now_staff_sysuser_id'],'status'=>1));
        $department_id = intval($res_duser['department_id']);
        $department_user_id = intval($res_duser['id']);
        $add_data = array('serial_number'=>$serial_number,'name'=>$name,'io_type'=>22,'use_type'=>1,'io_date'=>$io_date,
            'department_id'=>$department_id,'department_user_id'=>$department_user_id,'amount'=>$res_approval['bottle_num'],'total_fee'=>0,
            'area_id'=>$area_id,'hotel_id'=>$res_approval['hotel_id'],'type'=>20,'sysuser_id'=>0
        );
        $stock_id = $this->add($add_data);

        $m_goods = new \Common\Model\Finance\GoodsModel();
        $m_unit = new \Common\Model\Finance\UnitModel();
        $stock_details = array();
        $goods_data = json_decode($res_approval['wine_data'],true);
        foreach ($goods_data as $k=>$v){
            $res_goods = $m_goods->getInfo(array('id'=>$k));
            $res_unit = $m_unit->getInfo(array('category_id'=>$res_goods['category_id'],'type'=>1,'convert_type'=>1));
            $unit_id = intval($res_unit['id']);
            $stock_details[] = array('stock_id'=>$stock_id,'goods_id'=>$k,'unit_id'=>$unit_id,
                'stock_amount'=>$v,'stock_total_amount'=>$v,'status'=>1);
        }
        $m_stock_detail = new \Common\Model\Finance\StockDetailModel();
        $m_stock_detail->addAll($stock_details);
        return $stock_id;
    }
}