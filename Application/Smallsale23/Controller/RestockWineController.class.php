<?php
namespace Smallsale23\Controller;
use \Common\Controller\CommonController as CommonController;

class RestockWineController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'config':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'addwine':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'goods_data'=>1001,'delivery_time'=>1001);
                break;
        }
        parent::_init_();
    }

    public function config(){
        $openid = $this->params['openid'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $not_goods_id = array_merge(C('SELL_NOTIN_HOTEL_GOODS'),C('DATA_GOODS_IDS'));
        $m_goods = new \Common\Model\Finance\GoodsModel();
        $gwhere = array('status'=>1);
        $gwhere['brand_id'] = array('not in',C('SELL_NOTIN_HOTEL_BRANDS'));
        $gwhere['id'] = array('not in',$not_goods_id);
        $goods_list = $m_goods->getDataList('id as value,name',$gwhere,'brand_id asc');
        array_unshift($goods_list,array('value'=>0,'name'=>'请选择'));
        $goods_num = array();
        for($i=0;$i<11;$i++){
            $goods_num[]=array('name'=>$i.'瓶','value'=>$i);
        }
        $delivery_time = time()+86400;
        $delivery_date = date('Y-m-d',$delivery_time);
        $delivery_hour = date('H:i',$delivery_time);
        $res_data = array('goods_list'=>$goods_list,'goods_num'=>$goods_num,'delivery_date'=>$delivery_date,
            'delivery_hour'=>$delivery_hour);
        $this->to_back($res_data);
    }

    public function addwine(){
        $openid = $this->params['openid'];
        $goods_data = $this->params['goods_data'];
        $delivery_time = $this->params['delivery_time'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,merchant.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $hotel_id = $res_staff[0]['hotel_id'];
        $m_hotel = new \Common\Model\HotelModel();
        $res_hotel = $m_hotel->getHotelById('hotel.area_id,ext.is_have_group,ext.bd_name',array('hotel.id'=>$hotel_id));
        if($res_hotel['is_have_group']==0){
            $this->to_back(93233);
        }
        if(empty($res_hotel['bd_name'])){
            $this->to_back(93237);
        }
        $json_str = stripslashes(html_entity_decode($goods_data));
        $goods_arr = json_decode($json_str,true);
        $wine_num = 0;
        $wine_data = array();
        $m_price_template_hotel = new \Common\Model\Finance\PriceTemplateHotelModel();
        $wine_money = 0;
        foreach ($goods_arr as $v){
            if($v['id']>0 && $v['num']>0){
                $wine_num+=$v['num'];
                $num = 0;
                if(isset($wine_data[$v['id']])){
                    $num = $wine_data[$v['id']];
                }
                $wine_data[$v['id']]=$v['num']+$num;
                $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($hotel_id,$v['id']);
                $goods_money = $settlement_price*$num;
                $wine_money+=$goods_money;
            }
        }
        if(empty($wine_data)){
            $this->to_back(1001);
        }
        $m_sale = new \Common\Model\Finance\SaleModel();
        $fileds = 'sum(a.settlement_price) as money';
        $where = array('a.hotel_id'=>$hotel_id,'a.ptype'=>0,'a.is_expire'=>1,'record.type'=>7,'record.wo_reason_type'=>1,'record.wo_status'=>2);
        $res_data = $m_sale->getSaleStockRecordList($fileds,$where);
        $cq_money = intval($res_data[0]['money']);
        if($cq_money>0){
            $this->to_back(93234);
        }
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        $hotel_stock = json_decode($res_cache, true);
        $stock_money = 0;
        if(!empty($hotel_stock['goods_list'])) {
            foreach ($hotel_stock['goods_list'] as $v){
                $settlement_price = $m_price_template_hotel->getHotelGoodsPrice($hotel_id,$v['id']);
                $stock_goods_money = $settlement_price*$v['stock_num'];
                $stock_money+=$stock_goods_money;
            }
        }
        $all_money = $wine_money+$stock_money;
        $m_contact = new \Common\Model\Finance\ContractHotelModel();
        $fields = 'contract.id,contract.hotel_quota';
        $where = array('a.hotel_id'=>$hotel_id,'contract.type'=>20,'contract.ctype'=>21,'contract.status'=>1);
        $where['contract.contract_etime'] = array('egt',date('Y-m-d'));
        $res_contact = $m_contact->getContractData($fields,$where,'contract.id desc');
        if(empty($res_contact[0]['hotel_quota'])){
            $this->to_back(93236);
        }
        $hotel_quota = intval($res_contact[0]['hotel_quota']);
        if($all_money>$hotel_quota){
            $this->to_back(93235);
        }

        $ops_staff_id = 999999999;
        $delivery_time = date('Y-m-d H:i:s',strtotime($delivery_time));
        $merchant_staff_id = $res_staff[0]['id'];
        $adata = array('item_id'=>10,'ops_staff_id'=>$ops_staff_id,'hotel_id'=>$hotel_id,'wine_data'=>json_encode($wine_data),
            'merchant_staff_id'=>$merchant_staff_id,'bottle_num'=>$wine_num,'status'=>1,'allot_type'=>1,'delivery_time'=>$delivery_time);
        $m_approval = new \Common\Model\Crm\ApprovalModel();
        $approval_id = $m_approval->add($adata);
        $m_approval_step = new \Common\Model\Crm\ApprovalStepModel();
        $fields = 'id,name,ops_staff_id,step_order,role_type';
        $res_steps = $m_approval_step->getDataList($fields,array('item_id'=>10),'step_order asc');
        $processes_data = array();
        $m_ops_staff = new \Common\Model\Smallapp\OpsstaffModel();
        foreach ($res_steps as $v){
            $is_receive = 0;
            $handle_status = 0;
            if($v['step_order']==1){
                $is_receive = 1;
                $handle_status = 1;
            }
            $ops_staff_id = $v['ops_staff_id'];
            if($ops_staff_id==0){
                $hotel_role_type = $v['role_type'];
                $owhere = array('a.area_id'=>$res_hotel['area_id'],'a.hotel_role_type'=>$hotel_role_type);
                $res_ops = $m_ops_staff->getStaffinfo('a.id',$owhere);
                $ops_staff_id = $res_ops[0]['id'];
            }
            $processes_data[] = array('approval_id'=>$approval_id,'step_id'=>$v['id'],'step_order'=>$v['step_order'],'area_id'=>$res_hotel['area_id'],
                'is_receive'=>$is_receive,'handle_status'=>$handle_status,'ops_staff_id'=>$ops_staff_id);
        }
        $m_approval_process = new \Common\Model\Crm\ApprovalProcessesModel();
        $m_approval_process->addAll($processes_data);

        $this->to_back(array('approval_id'=>$approval_id));
    }

}
