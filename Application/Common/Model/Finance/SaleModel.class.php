<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class SaleModel extends BaseModel{
	protected $tableName='finance_sale';

	public function addsale($stock_record_info,$hotel_id,$sale_openid,$user){
        $m_stock = new \Common\Model\Finance\StockModel();
        $res_stock = $m_stock->getInfo(array('id'=>$stock_record_info['stock_id']));
        if(!empty($res_stock['hotel_id'])){
            $hotel_id = $res_stock['hotel_id'];
        }
        $m_price_template_hotel = new \Common\Model\Finance\PriceTemplateHotelModel();
        $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($hotel_id,$stock_record_info['goods_id'],0);
        $sale_price = 0;
        $goods_settlement_price = $settlement_price;
        if($stock_record_info['wo_reason_type']==1){
            $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
            $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.finance_goods_id'=>$stock_record_info['goods_id'],'g.status'=>1);
            $res_data = $m_hotelgoods->getGoodsList('g.id,g.price,h.hotel_price',$where,'g.id desc',"0,1");
            if(!empty($res_data[0]['price'])){
                $sale_price = $res_data[0]['price'];
            }
        }else{
            $settlement_price = 0;
        }
        $m_goods_avg_price = new \Common\Model\Finance\GoodsAvgpriceModel();
        $res_avg_price = $m_goods_avg_price->getALLDataList('price',array('goods_id'=>$stock_record_info['goods_id']),'id desc','0,1','');
        $now_avg_price = $res_avg_price[0]['price']>0?$res_avg_price[0]['price']:0;

        $m_hotel = new \Common\Model\HotelModel();
        $fields = 'hotel.area_id,ext.maintainer_id,ext.residenter_id';
        $res_ext = $m_hotel->getHotelById($fields,array('hotel.id'=>$hotel_id));

	    $add_data = array('stock_record_id'=>$stock_record_info['id'],'goods_id'=>$stock_record_info['goods_id'],'sale_price'=>$sale_price,'now_avg_price'=>$now_avg_price,
            'idcode'=>$stock_record_info['idcode'],'cost_price'=>abs($stock_record_info['price']),'settlement_price'=>$settlement_price,'goods_settlement_price'=>$goods_settlement_price,
            'hotel_id'=>$hotel_id,'maintainer_id'=>intval($res_ext['maintainer_id']),'residenter_id'=>intval($res_ext['residenter_id']),
            'type'=>1,'area_id'=>intval($res_ext['area_id']));
	    if(!empty($sale_openid)){
	        $add_data['sale_openid'] = $sale_openid;
        }
	    if(!empty($user)){
	        $add_data['guest_openid'] = $user['openid'];
	        $add_data['guest_mobile'] = $user['mobile'];
        }
	    $sale_id = $this->add($add_data);
	    return $sale_id;
    }

    public function getStaticSaleData($area_id,$maintainer_id,$hotel_id,$start_time,$end_time,$group='',$wo_status='',$goods_id='',$ptype=''){
        $fileds = 'sum(a.settlement_price) as sale_money';
        $where = array();
        if($wo_status){
            $where['record.wo_status'] = $wo_status;
        }else{
            $where['record.wo_status'] = array('in','1,2,4');
        }
        if(!empty($goods_id)){
            $where['record.goods_id'] = $goods_id;
        }else{
            $data_goods_ids = C('DATA_GOODS_IDS');
            $where['a.goods_id'] = array('not in',$data_goods_ids);
        }
        if(!empty($ptype) && $ptype<99){
            if($ptype==10){
                $where['a.ptype'] = 0;
            }else{
                $where['a.ptype'] = $ptype;
            }
        }

        if($area_id){
            $where['hotel.area_id'] = $area_id;
        }
        if($maintainer_id){
            $where['a.maintainer_id'] = $maintainer_id;
        }
        if($hotel_id){
            $where['a.hotel_id'] = $hotel_id;
        }
        $where['a.add_time'] = array(array('egt',$start_time),array('elt',$end_time));
        $res_sale = $this->getSaleStockRecordList($fileds,$where);
        $sale_money = abs(intval($res_sale[0]['sale_money']));

        if($where['a.ptype']==1 || in_array($wo_status,array(1,4))){
            $qk_money = 0;
            $cqqk_money = 0;
        }else{
            $qk_money = 0;
            $cqqk_money = 0;
            $start_date = date('Y-m-d',strtotime($start_time));
            $end_date = date('Y-m-d',strtotime($end_time));
            if($start_date==date('Y-m-d') && $end_date==date('Y-m-d')){
                if(!isset($where['a.ptype'])){
                    $where['a.ptype'] = array('in','0,2');
                }
                unset($where['a.add_time']);
                $where['record.wo_status'] = 2;
                $res_sale_qk = $this->getSaleStockRecordList('a.id as sale_id,a.settlement_price,a.ptype,a.add_time,a.is_expire',$where);
                if(!empty($res_sale_qk)){
                    $m_sale_payment_record = new \Common\Model\Finance\SalePaymentRecordModel();
                    $expire_time = 7*86400;
                    foreach ($res_sale_qk as $v){
                        if($v['ptype']==0){
                            $now_money = $v['settlement_price'];
                        }else{
                            $res_had_pay = $m_sale_payment_record->getDataList('sum(pay_money) as total_pay_money',array('sale_id'=>$v['sale_id']),'');
                            $had_pay_money = intval($res_had_pay[0]['total_pay_money']);
                            $now_money = $v['settlement_price']-$had_pay_money;
                        }
                        $qk_money+=$now_money;

                        /*
                        $sale_time = strtotime($v['add_time']);
                        $now_time = time();
                        if($now_time-$sale_time>=$expire_time){
                            $cqqk_money+=$now_money;
                        }
                        */
                        if($v['is_expire']==1){
                            $cqqk_money+=$now_money;
                        }
                    }
                }
                $qk_money = abs($qk_money);
                $cqqk_money = abs($cqqk_money);
            }
        }
        return array('sale_money'=>$sale_money,'qk_money'=>$qk_money,'cqqk_money'=>$cqqk_money);
    }

    public function getSaleStockRecordList($fileds,$where,$group='',$limit='',$orderby=''){
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_stock_record record on a.stock_record_id=record.id','left')
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->join('savor_hotel hotel on a.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->join('savor_smallapp_user user on record.op_openid=user.openid','left')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res_data;
    }

    public function getGroupSaleDatas($fileds,$where,$group='',$limit=''){
        $res_data = $this->alias('a')
            ->field($fileds)
            ->join('savor_finance_goods goods on a.goods_id=goods.id','left')
            ->where($where)
            ->limit($limit)
            ->group($group)
            ->select();
        return $res_data;
    }
}