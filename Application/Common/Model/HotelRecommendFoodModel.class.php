<?php
/**
 * @desc 餐厅端推荐菜
 * @author zhang.yingtao
 * @since 20171204
 */
namespace Common\Model;
use Think\Model;

class HotelRecommendFoodModel extends Model
{
	protected $tableName='hotel_recommend_food';
	public function getHotelList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	         ->join('savor_media b on a.big_media_id=b.id')
	         ->field($fields)
	         ->where($where)
	         ->order($order)
	         ->limit($limit)
	         ->select();
	    return $data;
	}
    public function getHotelListOne($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	         ->join('savor_media b on a.media_id=b.id')
	         ->field($fields)
	         ->where($where)
	         ->order($order)
	         ->limit($limit)
	         ->select();
	    return $data;
	}
}