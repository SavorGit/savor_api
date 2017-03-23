<?php
namespace Common\Model;
use Think\Model;

/**
 * Class FeedbackModel
 * @package Common\Model\Feedback
 */
class ClientstartModel extends Model{
	/**
	 * @var string
     */
	protected $tableName = 'client_start';

	/**
	 * @param $data 数组
	 * @return mixed 布尔值
     */
	public function getPr($id){
		if ($id) {
			$res = $this->find($id);
			return $res;
		}

	}



	public function getOne($where, $field){
		$list = $this->where($where)->field($field)
			->find();
		return $list;
	}
}