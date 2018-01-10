<?php
namespace Common\Model;
use Think\Model;
class BlackListModel extends Model
{

    protected $tableName='black_list';


    public function getList($where,$field, $start=0,$size=5){
	    $list = $this->field($field)
		->where($where)
	    ->limit($start,$size)
	    ->select();
 

	    return $list;
	}
	public function getAll($where,$field){
	    $list = $this->field($field)
	    ->where($where)
	    ->select();
	    return $list;
	}
    public function countBlackBoxNum(){
        /* $yestoday_time = strtotime('-1 day');
        $yestoday_start = date('Y-m-d 00:00:00',$yestoday_time);
        $yestoday_end   = date('Y-m-d 23:59:59',$yestoday_time);
        $where = array();
        $where =" create_time >='".$yestoday_start."' and create_time<='".$yestoday_end."'"; */
        
        $nums = $this->where()->count();
        return $nums;
    }
}