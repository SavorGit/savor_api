<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class GoodsModel extends BaseModel{
	protected $tableName='smallapp_goods';

	public function getAllShopGoods($keywords='',$start,$size){
	    $nowtime = date('Y-m-d H:i:s');
        $all_sql = "(select id,name,price,'0.00' as line_price,'0' as amount,media_id,cover_imgmedia_ids as cover_imgs,type,'0' as gtype,add_time from savor_smallapp_goods 
        where type=10 and status=2 and show_status= 1 and start_time<= '{$nowtime}' and end_time>= '{$nowtime}' order by id desc) union 
        (select id,name,price,line_price,amount,'0' as media_id,cover_imgs,type,gtype,add_time from savor_smallapp_dishgoods where type=22 and status=1 and gtype in(1,2) order by id desc)";
        $where = '';
        if(!empty($keywords))   $where = " where goods.name like '%$keywords%'";
        $sql_count = "select count(*) as num from ({$all_sql}) as goods $where";
        $result = $this->query($sql_count);
        $data = array('total'=>0,'list'=>array());
        if(!empty($result[0]['num'])){
            $data['total'] = $result[0]['num'];
            $limit = '';
            if($start==0 && $size>0){
                $limit = "limit $start,$size";
            }
            $sql = "select * from ({$all_sql}) as goods $where order by goods.add_time desc $limit";
            $data['list'] = $this->query($sql);
        }
        return $data;
    }
}