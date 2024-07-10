<?php
namespace Common\Model\Finance;
use Common\Model\BaseModel;

class GoodsPolicyWodataModel extends BaseModel{
	protected $tableName='finance_goods_policy_wodata';

    public function getGoodsWodata($goods_id,$area_id,$hotel_id,$types){
        $m_goods_policy_hotel = new \Common\Model\Finance\GoodsPolicyHotelModel();
        $res_policy = $m_goods_policy_hotel->getGoodsPolicy($goods_id,$area_id,$hotel_id);
        $policy_id = intval($res_policy[0]['policy_id']);
        $wo_data = array();
        if($policy_id>0){
            $where = array('policy_id'=>$policy_id,'status'=>1);
            if(is_array($types)){
                $where['type'] = array('in',$types);
            }else{
                $where['type'] = $types;
            }
            $wo_data = $this->getALLDataList('id,name,is_required,media_id',$where,'id asc');
        }
        return $wo_data;
    }
}