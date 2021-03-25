<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class LifeAdsHotelModel extends BaseModel {
	protected $tableName='life_ads_hotel';

    public function getList($fields,$where,$order,$limit){
        $data = $this->alias('a')
            ->join('savor_life_ads lads on a.life_ads_id = lads.id','left')
            ->join('savor_ads ads on lads.ads_id = ads.id','left')
            ->join('savor_media media on ads.media_id= media.id','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

}