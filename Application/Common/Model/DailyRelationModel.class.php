<?php
/**
 * @desc 每日知享文章详情
 * @author zhang.yingtao
 * @since  2017-09-18
 */
namespace Common\Model;
use Think\Model;

class DailyRelationModel extends Model{
	protected $tableName = 'daily_relation';
	
	public function getListByDailyid($fields,$dailyid){
	    $data = $this->field($fields)->where('dailyid='.$dailyid)->select();
	    return $data;
	}
}