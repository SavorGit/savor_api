<?php
namespace Common\Model\Smallapp;
use Think\Model;

class CollectModel extends Model{
	protected $tableName='smallapp_collect';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        $ret = $this->add($data);
	        
	    }else {
	        $ret = $this->addAll($data);
	    }
	    return $ret;
	}

	public function updateInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}

	public function getWhere($fields,$where,$order,$limit,$group){
	    $data = $this->field($fields)->where($where)->order($order)->group($group)->limit($limit)->select();
	    return $data;
	}

	public function getOne($fields,$where,$order){
	    $data =  $this->field($fields)->where($where)->order($order)->find();
	    return $data;
	}

	public function countNum($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}

	public function getList($fields,$where,$order,$limit,$group){
	    $data = $this->alias('a')
	                ->join('savor_smallapp_public b on a.res_id = b.forscreen_id','left')
	                ->join('savor_smallapp_user c on b.openid=c.openid','left')
	                ->field($fields)->where($where)
	                ->order($order)->group($group)
	                ->limit($limit)
	                ->select();
	    return $data;
	}

    public function getStore($field,$where,$order,$limit,$group=''){
        $data = $this->alias('a')
            ->join('savor_smallapp_store store on a.res_id=store.id','left')
            ->join('savor_category category on store.category_id=category.id','left')
            ->field($field)
            ->where($where)
            ->order($order)
            ->group($group)
            ->limit($limit)
            ->select();
        return $data;
    }
}