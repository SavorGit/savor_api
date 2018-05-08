<?php
/**
 * @desc   聚屏广告
 * @since  20180411
 * @author zhang.yingtao
 */
namespace Common\Model;
use Think\Model;

class PubPolyAdsModel extends Model
{
	protected $tableName='pub_poly_ads';
	public function getList($fields,$where, $order='id desc', $limit){
	    $data = $this->alias('a')
    	    ->join('savor_ads ads on a.ads_id=ads.id','left')
    	    ->join('savor_media media on ads.media_id=media.id','left')
    	    ->field($fields)
    	    ->where($where)
    	    ->order($order)
    	    ->limit($limit)
    	    ->select();
	    
	    return $data;
	}
}