<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */

namespace Common\Model;
use Think\Model;

/**
 * Class ApiModel
 * @package Common\Model
 */
class ApiModel extends Model{

	protected $tableName = 'mb_content';


	/**
	 * getproInfo 取出酒楼所对应节目单的所有节目
	 * @access public
	 * @param $hotelid 酒楼id
	 * @param $menuid  酒楼对应菜单id
	 * @return mixed
	 */
	public function getproInfo($menuid){
		$field = "media.id AS id,
				SUBSTR(media.oss_addr,LENGTH(media.oss_addr) - LOCATE('/', REVERSE(media.oss_addr)) + 2) AS name,
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

		$sql .= "  FROM savor_menu_item item
        LEFT JOIN savor_ads ads on ads.id = item.ads_id
        LEFT JOIN savor_media media on media.id = ads.media_id
        where
            ads.state=1
            and item.menu_id=$menuid
            and ads.type = 2
            and media.oss_addr is not null";

		$result = $this->query($sql);
		return $result;

	}

	public function getvodPeriod(){
		$sql = "SELECT period FROM savor_mb_period ORDER BY update_time DESC limit 1";
		$result = $this->query($sql);
		return $result;
	}

	public function getvodInfo(){
		$sql = "SELECT
		media.id AS id,
        SUBSTR(media.oss_addr,LENGTH(media.oss_addr) - LOCATE('/', REVERSE(media.oss_addr)) + 2) AS name,
        con.vod_md5 AS md5,
        case con.type
        when 3 then 'easyMd5'
        else 'fullMd5' END md5_type,
        'vod' AS type,
        media.oss_addr AS oss_path,
        con.duration AS duration,
        'mp4' AS suffix,
        home.sort_num AS sortNum,
        con.title AS chinese_name
        FROM savor_mb_home home
        LEFT JOIN savor_mb_content con on home.content_id=con.id
        LEFT JOIN savor_media media on media.id = con.media_id
        where
            home.state=1
            and con.state=2
            and con.type=3
            and home.is_demand=1
            and media.oss_addr is not null
            and (((con.bespeak=1 or con.bespeak=2) and con.bespeak_time > NOW()) or con.bespeak=0 or con.bespeak is NULL)
        ";
		$result = $this->query($sql);
		return $result;
	}


	public function getlogoInfo($hotelid){
		$sql = "SELECT
        media.id AS id,
        SUBSTR(media.oss_addr,LENGTH(media.oss_addr) - LOCATE('/', REVERSE(media.oss_addr)) + 2) AS name,
        media.md5 AS md5,
        'fullMd5' AS md5_type,
        'logo' AS type,
        media.oss_addr AS oss_path,
        media.duration AS duration,
        media.surfix AS suffix,
        0 AS sortNum,
        media.name AS chinese_name,
        media.id AS version
        FROM savor_hotel hotel
        LEFT JOIN savor_media media on media.id=hotel.media_id
        where
            hotel.id={$hotelid}";
		$result = $this->query($sql);
		return $result;
	}

	public function getloadInfo($hotelid){
		$sql = "SELECT
        media.id AS id,
        SUBSTR(media.oss_addr,LENGTH(media.oss_addr) - LOCATE('/', REVERSE(media.oss_addr)) + 2) AS name,
        media.md5 AS md5,
        'fullMd5' AS md5_type,
        'load' AS type,
        media.oss_addr AS oss_path,
        media.duration AS duration,
        media.surfix AS suffix,
        0 AS sortNum,
        media.name AS chinese_name,
        media.id AS version
        FROM savor_sys_config config
        LEFT JOIN savor_media media on media.id=config.config_value
        where
            config.config_key='system_loading_image'
            and status=1";
		$result = $this->query($sql);
		return $result;
	}
	/**
	 * getadvInfo 取出酒楼所对应节目单的所有宣传片
	 * @access public
	 * @param $hotelid 酒楼id
	 * @param $menuid  酒楼对应菜单id
	 * @return mixed
     */
    public function getadvInfo($hotelid, $menuid){
		$field = "media.id AS id,
				SUBSTR(media.oss_addr,LENGTH(media.oss_addr) - LOCATE('/', REVERSE(media.oss_addr)) + 2) AS name,
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
	 * getadsInfo 取出酒楼所对应节目单的所有ads
	 * @access public
	 * @param $hotelid 酒楼id
	 * @param $menuid  酒楼对应菜单id
	 * @return mixed
	 */
	public function getadsInfo($menuid){
		$field = "media.id AS id,
				SUBSTR(media.oss_addr,LENGTH(media.oss_addr) - LOCATE('/', REVERSE(media.oss_addr)) + 2) AS name,
				media.md5 AS md5,
				'easyMd5' AS md5Type,
				case ads.type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type,
				media.oss_addr AS ossPath,
				media.duration AS duration,
				media.surfix AS suffix,
				item.sort_num AS sortNum,
				item.ads_name AS chineseName";
		$sql = "select ".$field;

		$sql .= " FROM savor_menu_item item
        LEFT JOIN savor_ads ads on ads.id = item.ads_id
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
	 * getadsPeriod 获取酒楼广告期号拿最新的一条
	 * @access public
	 * @param $hotelid
	 * @return array
     */
    public function getadsPeriod($hotelid){
		$sql = "select
        menu_hotel.id AS menuHotelId,
        menu_hotel.menu_id AS menuId,
        CONCAT(DATE_FORMAT(menu_hotel.update_time,'%m%d%H%i'),
		  DATE_FORMAT(list.update_time,'%m%d%H%i')) AS period,
        menu_hotel.pub_time AS pubTime
        FROM savor_menu_hotel menu_hotel
        LEFT JOIN savor_menu_list list on menu_hotel.menu_id=list.id
        where menu_hotel.hotel_id = $hotelid
        ORDER BY menu_hotel.update_time desc,menu_hotel.id desc limit 1";
		$result = $this->query($sql);
		return $result;
	}

	/**
	 * @desc 非酒店环境下拉
	 * @param $createTime  home表创建时间
	 * @param $type        类型1：下拉加载  2：上拉加载
	 * $param $limit       展示条数        
	 */
	public function getVodList($createTime,$type=1,$limit= 20,$env=0){
	    if(!empty($env)){
	        if($type ==2 && !empty($createTime)){
	            $createTime = date('Y-m-d H:i:s',$createTime);
	            $where .= " and mh.create_time>'".$createTime."'";
	            
	        }
	        $where .= " and mc.media_id >0 and mh.is_demand=1";
	        $order =" mh.create_time asc";
	    }else {
	        if($type ==1 && !empty($createTime))
	        {
	            $createTime = date('Y-m-d H:i:s',$createTime);
	            $where .= " and mh.create_time>'".$createTime."'";
	        }else if($type ==2 && !empty($createTime)){
	            $where .= " and mh.sort_num>'".$createTime."'";
	        }
	        $order= " mh.sort_num asc";
	    }
	    
	   
	    /* if(!empty($env)){
	        $where .= " and mc.media_id >0 and mh.is_demand=1";
	    } */
		$now_date = date('Y-m-d H:i:s',time());
	    $sql ="select mc.id,mcat.name as category,mc.title,m.oss_addr as name,mc.duration,mc.img_url as imgUrl,mc.content_url as contentUrl,
	           mh.is_demand as canPlay,mc.tx_url as videoUrl,mc.share_title as shareTitle,
	           mc.share_content as shareContent,mh.create_time as createTime ,mc.type,mc.content,mc.media_id as mediaId,mh.sort_num as sort_num
	           from savor_mb_home as mh
	           left join savor_mb_content as mc on mh.content_id=mc.id
	           left join savor_mb_category as mcat on mc.category_id = mcat.id
	           left join savor_media as m on mc.media_id=m.id
	           
	           where 1=1 $where  and mc.bespeak_time<'".$now_date."' and mh.state=1 order by $order limit $limit";
	    $result = $this->query($sql);
	    return $result;
	}


	/**
	 * @desc 酒店环境下拉宣传片获取
	 * @param $hotel_id  酒店id
	 */
	public function getHotelList($hotel_id){
		$where .= ' AND ads.state=1 AND ads.type=3 AND ads.hotel_id = '.$hotel_id;
		$sql = "select ads.id,ads.name as title, media.oss_addr as name, ads.img_url imageURL, ads.duration duration from savor_ads ads  LEFT JOIN savor_media media on media.id = ads.media_id where 1=1 $where";

		$result = $this->query($sql);
		return $result;
	}
}