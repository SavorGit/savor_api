<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class PubRtbtagModel extends Model
{
    protected $tableName='pub_rtbtag';
    public function getlist($fields,$where,$order,$limit){
        $data = $this->alias('a')
             ->join('savor_rtbtaglist b  on a.tagid= b.id')
             ->field($fields)
             ->where($where)
             ->order($order)
             ->limit($limit)
             ->select();
        return $data;
    }
}