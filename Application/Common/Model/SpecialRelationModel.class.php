<?php
namespace Common\Model;
use Think\Model;

class SpecialRelationModel extends Model{
	protected $tableName = 'special_relation';
	
	public function getInfoBySpecialId($sgid){
	    $data = $this->field('id,sgtype,stext,sarticleid,spictureid,stitle')->where('sgid='.$sgid)->order('id asc')->select();
	    return $data;
	}
}