<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class SaleModel extends BaseModel{
	protected $tableName='finance_sale';

	public function addsale($stock_record_info,$hotel_id,$sale_openid,$user){
	    /*
	    $index_voucher_no = 10001;
	    $res_data = $this->getALLDataList('id,jd_voucher_no',array(),'id desc','0,1','');
	    if(!empty($res_data[0]['jd_voucher_no'])){
	        $jd_voucher_no = $res_data[0]['jd_voucher_no']+1;
        }else{
            $jd_voucher_no = $index_voucher_no;
        }
	    */
        $jd_voucher_no = 0;
        $m_price_template_hotel = new \Common\Model\Finance\PriceTemplateHotelModel();
        $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($hotel_id,$stock_record_info['goods_id'],0);

        $m_hotel_ext = new \Common\Model\HotelExtModel();
        $res_ext = $m_hotel_ext->getOnerow(array('hotel_id'=>$hotel_id));

	    $add_data = array('stock_record_id'=>$stock_record_info['id'],'goods_id'=>$stock_record_info['goods_id'],
            'idcode'=>$stock_record_info['idcode'],'cost_price'=>abs($stock_record_info['price']),'settlement_price'=>$settlement_price,
            'hotel_id'=>$hotel_id,'maintainer_id'=>intval($res_ext['maintainer_id']),'type'=>1,'jd_voucher_no'=>$jd_voucher_no);
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
}