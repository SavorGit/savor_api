<?php
/**
 * @desc 互动投屏广告投到对应的盒子
 */
namespace Common\Model\Smallapp;
use Think\Model;

class ForscreenAdsBoxModel extends Model
{
	protected $tableName='forscreen_ads_box';
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	         ->join('savor_forscreen_ads fads on a.forscreen_ads_id = fads.id','left')
	         ->join('savor_ads ads on fads.ads_id = ads.id','left')
	         ->join('savor_media media on ads.media_id= media.id','left')
	         ->field($fields)
	         ->where($where)
	         ->order($order)
	         ->limit($limit)
	         ->select();
	    return $data;
	}
}