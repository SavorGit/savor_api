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
}