<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class AdsModel extends Model
{
	protected $tableName='ads';

	public function getWhere($where, $field){
		$list = $this->where($where)->field($field)->select();
		return $list;
	}

	/**
	 * getadvInfo ȡ����¥���Ӧ��Ŀ����������Ƭ
	 * @access public
	 * @param $hotelid ��¥id
	 * @param $menuid  ��¥��Ӧ�˵�id
	 * @return mixed
	 */
	public function getadvInfo($hotelid, $menuid){
		$field = "media.id AS id,
				media.oss_addr AS name,
				media.md5 AS md5,
				'easyMd5' AS md5_type,
				case ads.type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type,
				media.oss_addr AS oss_path,
				media.duration AS duration,
				media.surfix AS suffix,
				item.sort_num AS sortNum,
				item.ads_name AS chinese_name";
		$sql = "select ".$field;

		$sql .= " FROM savor_ads ads
        LEFT JOIN savor_menu_item item on ads.name like CONCAT('%',item.ads_name,'%')
        LEFT JOIN savor_media media on media.id = ads.media_id
        where ads.type=3
            and ads.hotel_id={$hotelid}
            and (item.ads_id is null or item.ads_id=0)
            and ads.state=1
            and item.menu_id={$menuid}

            and media.oss_addr is not null";

		$result = $this->query($sql);
		return $result;

	}

	/**
	 * getadsInfo ȡ����¥���Ӧ��Ŀ��������ads
	 * @access public
	 * @param $hotelid ��¥id
	 * @param $menuid  ��¥��Ӧ�˵�id
	 * @return mixed
	 */
	public function getadsInfo($menuid){
		$field = "media.id AS id,
				media.oss_addr AS name,
				media.md5 AS md5,
				'easyMd5' AS md5_type,
				case ads.type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type,
				media.oss_addr AS oss_path,
				media.duration AS duration,
				media.surfix AS suffix,
				item.sort_num AS `order`,
				item.ads_name AS chinese_name";
		$sql = "select ".$field;

		$sql .= "  FROM savor_ads ads
        LEFT JOIN savor_menu_item item on ads.id = item.ads_id
        LEFT JOIN savor_media media on media.id = ads.media_id
        where
            ads.state=1
            and item.menu_id={$menuid}
            and ads.type = 1
            and media.oss_addr is not null";
		$result = $this->query($sql);
		return $result;

	}


	/**
	 * getproInfo ȡ����¥���Ӧ��Ŀ�������н�Ŀ
	 * @access public
	 * @param $hotelid ��¥id
	 * @param $menuid  ��¥��Ӧ�˵�id
	 * @return mixed
	 */
	public function getproInfo($menuid){
		$field = "media.id AS id,
				media.oss_addr AS name,
				media.md5 AS md5,
				'easyMd5' AS md5_type,
				case ads.type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type,
				media.oss_addr AS oss_path,
				media.duration AS duration,
				media.surfix AS suffix,
				item.sort_num AS sortNum,
				item.ads_name AS chinese_name";
		$sql = "select ".$field;

		$sql .= "  FROM savor_ads ads LEFT JOIN savor_menu_item item
          on ads.id = item.ads_id
        LEFT JOIN savor_media media on media.id = ads.media_id
        where
            ads.state=1
            and item.menu_id=$menuid
            and ads.type = 2
            and media.oss_addr is not null";

		$result = $this->query($sql);
		return $result;

	}
}