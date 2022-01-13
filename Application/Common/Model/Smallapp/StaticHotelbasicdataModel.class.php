<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class StaticHotelbasicdataModel extends BaseModel{
	protected $tableName='smallapp_static_hotelbasicdata';

    public function getDatas($fields,$where,$group){
        $res_data = $this->field($fields)->where($where)->group($group)->select();
        return $res_data;
    }

    public function getDates($start,$end,$type=1){
        $all_dates = array();
        $dt_start = strtotime($start);
        $dt_end = strtotime($end);
        while ($dt_start<=$dt_end){
            switch ($type){
                case 1:
                    $now_date = date('Y-m-d',$dt_start);
                    break;
                case 2:
                    $now_date = date('Ymd',$dt_start);
                    break;
                default:
                    $now_date = date('Y-m-d',$dt_start);
            }
            $all_dates[]=$now_date;
            $dt_start=strtotime('+1 day',$dt_start);
        }
        return $all_dates;
    }

}