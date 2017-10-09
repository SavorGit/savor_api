<?php
/**
 * @desc 每日知享文章
 * @author zhang.yingtao
 * @since  2017-09-18
 */
namespace Common\Model;
use Think\Model;

class DailyContentModel extends Model{
	protected $tableName = 'daily_content';
    public function getInfoById($fields,$id){
        $data = $this->alias('a')
                     ->join('savor_article_source b on a.source_id =b.id')
                     ->field($fields)->where('a.id='.$id)->find();
        return $data;
    }
    public function getTodayKeyWords(){
        $now_date = date('Y-m-d H:i:s');
        $sql ="select a.keyword from savor_daily_content a
               left join savor_daily_home b on a.id = b.dailyid
               left join savor_daily_lk c on b.lkid = c.id
               where to_days(c.`bespeak_time`) = to_days(now()) and c.`bespeak_time`<='".$now_date."'
               and c.homestate = 1";
        $data = $this->query($sql);
        return $data;
    }
    public function getList($fields,$where,$order,$limit){
        $sql ="select $fields from savor_daily_content a 
               
               left join savor_daily_home b on a.id = b.dailyid
               left join savor_daily_lk c on b.lkid = c.id
               left join savor_article_source d on a.source_id= d.id
               where $where and c.homestate = 1 order by $order limit $limit";
        $data = $this->query($sql);
        return $data;
    }
    public function getOneById($fields,$id){
        $data = $this->field($fields)->where('id='.$id.' and state =1')->find();
        return $data;
    }
    public function getDetailById($fields,$id){
        $data =  $this->alias('a')
                      ->join('savor_daily_relation b on b.dailyid=a.id','left')
                      ->join('savor_article_source c on c.id=a.source_id','left')
                      ->join('savor_daily_home d on a.id=d.dailyid','left')
                      ->join('savor_daily_lk e on d.lkid = e.id')
                      ->field($fields)
                      ->where('a.id='.$id.' and state=1')
                      ->select();
        return $data;
    }
}