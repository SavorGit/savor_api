<?php
namespace Common\Model;
use Think\Model;

class DownloadCountModel extends Model{
	protected $tableName = 'download_count';
	
	/**
	 * @desc 下载统计数据入库
	 */
	public function record($data){
	    return $this->add($data);
	}
	/**
	 * @desc 根据条件获取下载数据
	 */
    public function getInfo($field ='*',$where,$order,$limit){
	    $result = $this->field($field)->where($where)->order($order)->limit($limit)->select();
	    return $result;
	}  
}