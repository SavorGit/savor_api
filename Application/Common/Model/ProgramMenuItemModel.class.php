<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class ProgramMenuItemModel extends Model
{
	protected $tableName='programmenu_item';


	public function getList($fields,$where,$order,$limit,$group=''){
        $res = $this->alias('a')
                    ->join('savor_ads ads on a.ads_id = ads.id','left')
                    ->join('savor_media media on ads.media_id =media.id ','left')
                    ->field($fields)
                    ->where($where)
                    ->order($order)
                    ->group($group)
                    ->limit($limit)
                    ->select();
        return $res;
    }

	/**
	 * @获取节目单节目数据
	 */
	public function getMenuInfo($menuid){
	    $field = "media.id AS id,
				media.oss_addr AS name,
				media.md5 AS md5,
				'easyMd5' AS md5_type,
				case item.type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type,
				media.oss_addr AS oss_path,
				media.duration AS duration,
				media.surfix AS suffix,
				item.sort_num AS sortNum,
				item.ads_name AS chinese_name,
	            ads.is_sapp_qrcode,ads.resource_type media_type";
		$sql = "select ".$field;

		$sql .= "  FROM savor_ads ads LEFT JOIN savor_programmenu_item item
          on ads.id = item.ads_id
        LEFT JOIN savor_media media on media.id = ads.media_id
        where
            ads.state=1
            and item.menu_id=$menuid
            and item.type = 2
            and media.oss_addr is not null";
		$result = $this->query($sql);
		return $result;
	}
	/**
	 * @desc 获取节目单中的广告
	 */
	public function getMenuAds($menuid){
	    $sql ="SELECT `ads_name` AS `chinese_name` ,  `location_id`,`sort_num` AS `order`,
	           case type
			   when 1 then 'ads'
			   when 2 then 'pro'
			   when 3 then 'adv'
               when 4 then 'shopgoods'
               when 5 then 'poly'
               when 6 then 'actgoods'
               when 7 then 'selectcontent'
               when 8 then 'life'
			   END AS type 
	           FROM savor_programmenu_item WHERE menu_id=$menuid and  type in(1,3,4,5,6,7,8)";
	    $result = $this->query($sql);
	    return $result;
	}
	/**
	 * @desc 获取节目单的宣传片数据
	 */
	public function getadvInfo($hotelid,$menuid){
	    $field = "media.id AS id,
				media.oss_addr AS name,
				media.md5 AS md5,
				'easyMd5' AS md5_type,
				case item.type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type,
				media.oss_addr AS oss_path,
				media.duration AS duration,
				media.surfix AS suffix,
				item.sort_num AS sortNum,
				item.ads_name AS chinese_name,
	            ads.update_time,
	            ads.is_sapp_qrcode,ads.resource_type media_type";
	    $sql = "select ".$field;
	    
	    $sql .= " FROM savor_ads ads
	    LEFT JOIN savor_programmenu_item item on ads.name like CONCAT('%',item.ads_name,'%')
	    LEFT JOIN savor_media media on media.id = ads.media_id
	    where item.type=3
	    and ads.hotel_id={$hotelid}
	    and (item.ads_id is null or item.ads_id=0)
	    and ads.state=1
	    and item.menu_id={$menuid}
	    and media.oss_addr is not null order by item.sort_num asc";
	    $result = $this->query($sql);
	    return $result;
	}
	public function getwhere($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join('savor_programmenu_list list on a.menu_id= list.id')
            	     ->field($fields)->where($where)
	                 ->order($order)
	                 ->limit($limit)
	                 ->select();
	    return $data;
	}

    public function getData($fields,$where,$order){
        $data = $this->field($fields)->where($where)->order($order)->select();
        return $data;
    }
}