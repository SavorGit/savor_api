<?php
namespace Common\Model;
use Think\Model;

class ContentModel extends Model{
	protected $tableName = 'mb_content';
	/**
	 * @desc 非酒店环境下拉
	 * @param $createTime  home表创建时间
	 * @param $type        类型1：下拉加载  2：上拉加载
	 * $param $limit       展示条数        
	 */
	public function getVodList($createTime,$type=1,$limit= 20,$env=0){
	    if($type ==1 && !empty($createTime))
	    {
	        $createTime = date('Y-m-d H:i:s',$createTime);
	        $where .= " and mh.create_time>'".$createTime."'";   
	    }else if($type ==2 && !empty($createTime)){
	        $createTime = date('Y-m-d H:i:s',$createTime);
	        $where .= " and mh.create_time<'".$createTime."'";   
	    }    
	    if(!empty($env)){
	        $where .= " and mc.media_id >0";
	    }
	    $sql ="select mh.id,mcat.name as category,mc.title,m.oss_addr as name,mc.duration,mc.img_url as imgUrl,mc.content_url as contentUrl,
	           mh.is_demand as canPlay,mc.tx_url as videoUrl,mc.share_title as shareTitle,
	           mc.share_content as shareContent,mh.create_time as createTime ,mc.type,mc.content
	           from savor_mb_home as mh
	           left join savor_mb_content as mc on mh.content_id=mc.id
	           left join savor_mb_category as mcat on mc.category_id = mcat.id
	           left join savor_media as m on mc.media_id=m.id
	           
	           where 1=1 $where order by mh.sort_num asc limit $limit";
	    $result = $this->query($sql);
	    return $result;
	}


	/**
	 * @desc 酒店环境下拉宣传片获取
	 * @param $hotel_id  酒店id
	 */
	public function getHotelList($hotel_id){
		$where .= ' AND ads.state=1 AND ads.type=3 AND ads.hotel_id = '.$hotel_id;
		$sql = "select ads.id, ads.name title, ads.img_url imageURL, ads.duration duration from savor_ads ads  LEFT JOIN savor_media media on media.id = ads.media_id where 1=1 $where";

		$result = $this->query($sql);
		return $result;
	}
}