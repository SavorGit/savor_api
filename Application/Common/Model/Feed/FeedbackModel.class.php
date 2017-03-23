<?php
namespace Common\Model\Feed;
use Think\Model;

/**
 * Class FeedbackModel
 * @package Common\Model\Feedback
 */
class FeedbackModel extends Model{
	/**
	 * @var string
     */
	protected $tableName = 'mb_feedback';

	/**
	 * @param $data 数组
	 * @return mixed 布尔值
     */
	public function addData($data) {
		$result = $this->add($data);
		return $result;
	}
}