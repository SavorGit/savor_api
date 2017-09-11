<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HotelErrorReportDetailModel extends Model
{
    protected $tableName='hotel_error_report_detail';
    
    public function addInfo($data,$type=1){
        if($type==1){
            $ret = $this->addinfo($data);
        }else {
            $ret  = $this->addAll($data);
        }
        return $ret;
    }
    public function getList($fileds,$where,$order,$limit){
        $data = $this->field($fileds)->where($where)->order($order)->limit($limit)->select();
        return $data;
    }
}