<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class HomeModel extends Model{
	protected $tableName='mb_home';


	public function getvodInfo(){
		$sql = "SELECT
		media.id AS id,
        media.oss_addr AS name,
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
            and (((con.bespeak=1 or con.bespeak=2) and 1=1) or con.bespeak=0 or con.bespeak is NULL)
        ";
		$result = $this->query($sql);
		return $result;
	}


}
