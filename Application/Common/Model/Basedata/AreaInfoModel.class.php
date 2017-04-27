<?php
namespace Common\Model\Basedata;
use Think\Model;

class AreaInfoModel extends Model{
	protected $tableName = 'area_info';
	/**
	 * @desc 根据名称获取城市信息
	 */
	public function getInfoByName($name){
	    return $this->field('id,region_name')->where(array('region_name'=>$name))->find();
	}
}