<?php
/**
 * @desc 互动投屏广告
 */
namespace Common\Model\Smallapp;
use Think\Model;

class ForscreenAdsModel extends Model
{
	protected $tableName='forscreen_ads';
    public function getOne($fields,$where,$order){
        $data = $this->field($fields)->where($where)->order($order)->find();
        return $data;
    }
}