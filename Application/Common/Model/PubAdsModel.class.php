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
}