<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class HotplayModel extends BaseModel{
	protected $tableName='smallapp_hotplay';

	public function getHotplayNum($id,$type,$nums){
        $where = array('type'=>$type,'status'=>1);
        if($type==1){
            $where['forscreen_record_id'] = $id;
        }else{
            $where['data_id'] = $id;
        }
	    $res = $this->getInfo($where);
        if(!empty($res)){
            $nums = $res['init_playnum']+$nums;
        }
        return $nums;
    }
}