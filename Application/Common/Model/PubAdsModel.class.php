<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;

use Think\Model;

class PubAdsModel extends Model
{
	protected $tableName='pub_ads';
	public function getPubAdsInfoByid($field, $where,$order,$limit) {
	    $list = $this->alias('pads')
	    ->where($where)
	    ->join('savor_ads ads ON pads.ads_id = ads.id')
	    ->join('savor_media  med ON med.id = ads.media_id')
	    ->field($field)
	    ->find();
	    return $list;
	}
	public function countNums($where){
	    $nums = $this->alias('a')
	    ->where($where)
	    ->count();
	    return $nums;
	}
	public function getPubAdsList($field, $where,$order,$limit) {
	    $list = $this->alias('a')
	    ->where($where)
	    ->join('savor_ads ads ON a.ads_id = ads.id')
	    ->join('savor_media  med ON med.id = ads.media_id')
	    ->field($field)
	    ->select();
	    return $list;
	}
	public function getPubAdsInfo($fields,$where){
	    $data = $this->alias('a')
	                 ->join('savor_ads ads ON a.ads_id = ads.id','left')
	                 ->join('savor_media  med ON med.id = ads.media_id','left')
	                 ->join('savor_media  mda on a.cover_img_media_id= mda.id','left')
	                 ->field($fields)
	                 ->where($where)
	                 ->find();
	    return $data;
	}
}