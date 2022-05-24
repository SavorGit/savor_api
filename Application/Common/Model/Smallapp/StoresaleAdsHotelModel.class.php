<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class StoresaleAdsHotelModel extends BaseModel {
	protected $tableName='storesale_ads_hotel';

    public function getList($fields,$where,$order,$limit){
        $data = $this->alias('a')
            ->join('savor_storesale_ads sads on a.storesale_ads_id = sads.id','left')
            ->join('savor_ads ads on sads.ads_id = ads.id','left')
            ->join('savor_media media on ads.media_id= media.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }
	
	public function getGoodsList($fields,$where,$order,$limit){
        $data = $this->alias('a')
            ->join('savor_storesale_ads sads on a.storesale_ads_id = sads.id','left')
            ->join('savor_ads ads on sads.ads_id = ads.id','left')
            ->join('savor_media media on ads.media_id= media.id','left')
			->join('savor_smallapp_dishgoods dg on sads.goods_id= dg.id')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

}