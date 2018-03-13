<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model\Oss;
use Think\Model;

class OssBoxModel extends Model
{
	protected $connection = 'DB_OSS';

	protected $tablePrefix = "oss_";

	protected $tableName='box_log';


	public function __consruct($name){
		parent::__construct();
		$this->tableName = $name;
	}

	public function getLastTime($mac) {

		$sql = "SELECT MAX(create_time) lastma FROM
 		 	oss_box_log where box_mac = '".$mac."'";
		$result = $this->query($sql);
		return $result;
	}


}//End Class