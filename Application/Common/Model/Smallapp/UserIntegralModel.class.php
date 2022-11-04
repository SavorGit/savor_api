<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UserIntegralModel extends BaseModel{
	protected $tableName='smallapp_user_integral';

    public function getRemainIntegral($area_id,$maintainer_id){
        $where = array('staff.status'=>1,'merchant.status'=>1);
        $freeze_where = array();
        if($area_id){
            $where['hotel.area_id'] = $area_id;
            $freeze_where['a.area_id'] = $area_id;
        }
        if($maintainer_id){
            $where['ext.maintainer_id'] = $maintainer_id;
            $freeze_where['ext.maintainer_id'] = $maintainer_id;
        }
        $end_time = date('Y-m-d 23:59:59',strtotime('-1day'));
        $where['a.update_time'] = array('elt',$end_time);
        $freeze_where['a.add_time'] = array('elt',$end_time);
        $fields = 'sum(a.integral) as total_integral';
        $res_integral = $this->alias('a')
            ->join('savor_integral_merchant_staff staff on a.openid=staff.openid','left')
            ->join('savor_integral_merchant merchant on staff.merchant_id=merchant.id','left')
            ->join('savor_hotel hotel on merchant.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->field($fields)
            ->where($where)
            ->select();
        $integral = 0;
        if(!empty($res_integral)){
            $integral = intval($res_integral[0]['total_integral']);
        }
        $freeze_integral = 0;
        $m_integralrecord = new \Common\Model\Smallapp\UserIntegralrecordModel();
        $res_freeze = $m_integralrecord->getRecordlist($fields,$freeze_where,'a.id desc');
        if(!empty($res_freeze)){
            $freeze_integral = intval($res_freeze[0]['total_integral']);
        }
        $total_integral = $integral + $freeze_integral;
        return array('integral'=>$integral,'freeze_integral'=>$freeze_integral,'total_integral'=>$total_integral);
    }
}