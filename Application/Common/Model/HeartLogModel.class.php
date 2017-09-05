<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class HeartLogModel extends Model
{
	protected $tableName='heart_log';
	public function truncateTable(){
	    $sql ="TRUNCATE TABLE `savor_heart_log`";
	    return $this->execute($sql);
	}
	// TRUNCATE TABLE
	public function getOnlineHotel($where,$fields = '*'){
	    $result = $this->field($fields)->where($where)->group('hotel_id')->select();
	    /* echo $this->getLastSql();
	    echo "<br>"; */
	    return $result;
	}
}
