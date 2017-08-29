<?php
/**
 * @desc 心跳上报历史统计数据表
 * @since 20170815
 * @author zhang.yingtao
 */
namespace Common\Model;
use Think\Model;

class HeartAllLogModel extends Model
{
	protected $tableName='heart_all_log';
	public function getOne($mac,$type,$date){
	    $where = array();
	    $where['mac'] = $mac;
	    $where['type']= $type;
	    $where['date']= $date;
	    $info = $this->where($where)->find();
	    return $info;
	}
	public function addInfo($data){
	    if(!empty($data)){
	        $ret = $this->add($data);
	    }else{
	        $ret = false;
	    }
	    return $ret;
	}
	public function updateInfo($mac,$type,$date,$filed){
	    $sql ="update savor_heart_all_log set `$filed` = `$filed`+1 
	           where `date`={$date} and  `mac`='{$mac}' and `type`={$type}";
	    return $this->execute($sql);
	}
}