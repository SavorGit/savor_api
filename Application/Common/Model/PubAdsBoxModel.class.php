<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class PubAdsBoxModel extends Model
{
	protected $tableName='pub_ads_box';
    public function getAdsList($box_id,$location_id,$limit= 2){
        $now_date = date('Y-m-d H:i:s');
        $data = $this->alias('a')
             ->join('savor_pub_ads b on a.pub_ads_id= b.id','left')
             ->join('savor_ads c on b.ads_id=c.id','left')
             ->join('savor_media d on c.media_id=d.id')
             ->field("b.id pub_ads_id,b.create_time,d.id,d.oss_addr AS name,d.md5 AS md5,'easyMd5' AS md5_type,c.name AS chinese_name,
    				 'ads' AS `type`,
				     d.oss_addr AS oss_path,
				     d.duration AS duration,
				     d.surfix AS suffix,b.start_date,b.end_date,a.location_id,c.is_sapp_qrcode,c.resource_type media_type")
		    ->where('a.box_id='.$box_id." and b.end_date>'".$now_date.  "' and location_id=".$location_id." and b.state=1 and c.state=1 and d.oss_addr is not null")
		    ->order('b.start_date asc')
		    ->limit($limit)
			->select();
        //echo $this->getLastSql();exit;
        return $data;	
    }
    public function updateInfo($where,$data){
        $ret = $this->where($where)->save($data);
        return $ret;
    }
    public function getBoxAdsList($fields,$box_id,$order,$group){
        $now_date = date('Y-m-d H:i:s');
        $data = $this->alias('a')
        ->join('savor_pub_ads b on a.pub_ads_id= b.id','left')
        ->join('savor_ads c on b.ads_id=c.id','left')
        ->join('savor_media d on c.media_id=d.id')
        ->field($fields)
        				     ->where('a.box_id='.$box_id." and b.end_date>'".$now_date.  "'  and b.state=1 and c.state=1 and d.oss_addr is not null")
        				     ->order($order)
        				     ->group($group)
        				     ->select();
        //echo $this->getLastSql();exit;
        return $data;
    }
}