<?php
/**
 *@author zhang.yingtao
 *
 *
 */
namespace Common\Model;
use Think\Model;

class ProgramMenuListModel extends Model
{
	protected $tableName='programmenu_list';
    public function getOne($field,$where){
        $data = $this->field()->where($where)->find();
        return $data;
    }
    public function updateInfo($where,$data){
        $ret = $this->where($where)->save($data);
        return $ret;
    }
}