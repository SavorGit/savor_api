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
}
