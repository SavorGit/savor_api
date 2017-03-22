<?php
namespace Common\Model\Basedata;
use Think\Model;

class RegionModel extends Model{
	protected $tableName = 'region';
	/**
	 * @desc 获取全部区域信息
	 */
	public function getAllRegion(){
		$data = $this->limit(10)->select();
		return $data;
	}
}