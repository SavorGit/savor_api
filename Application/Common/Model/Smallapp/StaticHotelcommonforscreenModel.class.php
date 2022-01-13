<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class StaticHotelcommonforscreenModel extends BaseModel{
	protected $tableName='smallapp_static_hotelcommonforscreen';

    public function getDatas($fields,$where,$group){
        $res_data = $this->field($fields)->where($where)->group($group)->select();
        return $res_data;
    }

}