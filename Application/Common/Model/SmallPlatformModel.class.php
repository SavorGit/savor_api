<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class SmallPlatformModel extends Model
{
    protected $tableName='small_platform';
    public function addInfo($data){
        return $this->add($data);
    }
}