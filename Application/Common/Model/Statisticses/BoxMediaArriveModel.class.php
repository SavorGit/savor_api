<?php
namespace Common\Model\Statisticses;
use Think\Model;

/**
 * Class FeedbackModel
 * @package Common\Model\Feedback
 */
class BoxMediaArriveModel extends Model{
	protected $connection = 'DB_STATIS';

	protected $tablePrefix = 'statistics_';

    protected $tableName='box_media_arrive';
    
    public function getCount($where){
        $nums = $this->where($where)->count();
        return $nums;
    }
}