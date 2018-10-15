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
	/**
	 * @desc 获取所有投屏点播内容列表
	 */
	public function getAllDemandList($order = 'mh.sort_num asc',$limit = ''){
	    $now_date = date('Y-m-d H:i:s',time());
	    $sql ="select mc.id artid,mc.title,m.oss_addr as name,mc.duration,mc.img_url as imgUrl,
	           mc.content_url as contentUrl,mh.is_demand as canPlay,mc.tx_url as videoUrl,
	           mc.share_title as shareTitle,mc.share_content as shareContent,mh.create_time as createTime, mh.update_time as updateTime,
	           mc.type,mc.content,mc.media_id as mediaId,mh.sort_num as sort_num,sc.name as sourceName,sc.logo
	    from savor_mb_home as mh
	    left join savor_mb_content as mc on mh.content_id=mc.id
	    left join savor_media as m on mc.media_id=m.id
	    left join savor_article_source as sc on mc.source_id=sc.id
	    
	    where  mc.media_id >0 and mh.is_demand=1 and  mc.bespeak_time<'".$now_date."' and mh.state=1 order by $order $limit";
	    $data = $this->query($sql);
	    return $data;
	}
	/**
	 * @desc 获取所有投屏点播内容列表个数
	 */
	public function getAllDemandListNums(){
	    $now_date = date('Y-m-d H:i:s',time());
	    $sql ="select COUNT(mc.id) AS nums 
	           from savor_mb_home as mh
	           left join savor_mb_content as mc on mh.content_id=mc.id
               where  mc.media_id >0 and mh.is_demand=1 and  mc.bespeak_time<'".$now_date."' and mh.state=1 ";
	    $data = $this->query($sql);
	    return $data[0]['nums'];
	}
	/**
	 * @desc 根据条件获取点播内容
	 */
	public function getRecmmondDemand($where,$order = 'mh.sort_num asc',$limit=''){
	    $now_date = date('Y-m-d H:i:s',time());
	    $sql ="select mc.id artid,mc.title,m.oss_addr as name,mc.duration,mc.img_url as imgUrl,
	           mc.content_url as contentUrl,mh.is_demand as canPlay,mc.tx_url as videoUrl,
	           mc.share_title as shareTitle,mc.share_content as shareContent,mh.create_time as createTime, mh.update_time as updateTime,
	           mc.type,mc.content,mc.media_id as mediaId,mh.sort_num as sort_num,sc.name as sourceName,sc.logo
	    from savor_mb_home as mh
	    left join savor_mb_content as mc on mh.content_id=mc.id
	    left join savor_media as m on mc.media_id=m.id
	    left join savor_article_source as sc on mc.source_id=sc.id
	  
	    where  mc.media_id >0 and mh.is_demand=1 and  mc.bespeak_time<'".$now_date."' and mh.state=1 $where order by $order $limit";
	    $data = $this->query($sql);
	    return $data;
	    
	}
	public function getWhere($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join('savor_mb_content as content on a.content_id= content.id','left')
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->limit($limit)
	                 ->select();
	   return $data;
	}
}
