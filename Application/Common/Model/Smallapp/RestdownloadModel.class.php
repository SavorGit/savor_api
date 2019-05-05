<?php
/**
 * @desc 小程序用户
 */
namespace Common\Model\Smallapp;
use Think\Model;

class RestdownloadModel extends Model
{
	protected $tableName='smallapp_rest_download';
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	         ->join('savor_media media on a.media_id=media.id','left')
	         ->where($where)
	         ->field($fields)
	         ->order($order)
	         ->limit($limit)
	         ->select();
	    return $data;
	}
}