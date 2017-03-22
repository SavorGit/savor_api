<?php
namespace Common\Model\Basedata;
use Think\Model;

class CategoryModel extends Model{
	protected $tableName = 'mb_category';
	/**
	 * @desc 获取全部区域信息
	 */
	public function getAllCategory(){
		$data = $this->where('state=1')->select();
		return $data;
	}
}