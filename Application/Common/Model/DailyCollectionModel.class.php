<?php
namespace Common\Model;
use Think\Model;

class DailyCollectionModel extends Model{
	protected $tableName = 'daily_collection';
	public function getInfo($fields,$where){
	    $data = $this->field($fields)->where($where)->find();
	    return $data;
	}
	public function addinfo($data){
	    $ret = $this->add($data);
	    return $ret;
	}
	public function editinfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	         ->join('savor_daily_content b on a.dailyid = b.id','left')
	         ->join('savor_article_source c on b.source_id = c.id','left')
	         ->join('savor_daily_home d on d.dailyid = b.id','left')
	         ->join('savor_daily_lk e on d.lkid= e.id')
	         ->field($fields)
	         ->where($where)
	         ->order($order)
	         ->limit($limit)
	         ->select();
        return $data;
	}
}