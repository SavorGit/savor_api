<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class PubRtbAdsModel extends Model
{
	protected $tableName='pub_rtbads';
	public function getAdsList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join('savor_pub_rtbads_hotel h on a.id=h.pub_ads_id')
        	         ->join('savor_ads b on a.ads_id=b.id','left')
        	         ->join('savor_media c on b.media_id = c.id')
        	         ->field($fields)
        	         ->where($where)
        	         ->order($order)
        	         ->limit($limit)
        	         ->select();
	    return $data;
	}
}