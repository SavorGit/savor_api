<?php
/**
 * @desc 小程序用户
 */
namespace Common\Model\Smallapp;
use Think\Model;

class PublicModel extends Model
{
	protected $tableName='smallapp_public';
	
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join('savor_box box on a.box_mac=box.mac','left')
	                 ->join('savor_room room on room.id=box.room_id','left')
	                 ->join('savor_hotel hotel on room.hotel_id=hotel.id','left')
	                 ->join('savor_smallapp_user user on a.openid=user.openid','left')
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->limit($limit)
	                 ->select();
	    return $data;
	}

    public function getPublicList($fields,$where,$order,$limit){
        $data = $this->alias('a')
            ->join('savor_smallapp_user user on a.openid=user.openid','left')
            ->field($fields)
            ->where($where)
            ->order($order)
            ->limit($limit)
            ->select();
        return $data;
    }

    public function getPublicinfo($fields,$where){
        $data = $this->alias('a')
            ->join('savor_smallapp_user user on a.openid=user.openid','left')
            ->field($fields)
            ->where($where)
            ->select();
        return $data;
    }
	
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
	public function countWhere($where){
	    $nums = $this->alias('a')
	         ->join('savor_box box on a.box_mac=box.mac','left')
	         ->join('savor_room room on room.id=box.room_id','left')
	         ->join('savor_hotel hotel on room.hotel_id=hotel.id','left')
	         ->join('savor_smallapp_user user on a.openid=user.openid')
	         ->where($where)
	         ->count();
	    return $nums;
	}

    public function getFindnums($openid,$res_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();

        $map = array('openid'=>$openid,'res_id'=>$res_id,'type'=>$type,'status'=>1);
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $is_collect = 0;
        }else {
            $is_collect = 1;
        }
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $collect_num = $m_collect->countNum($map);
        $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
        $collect_num = $collect_num+intval($ret['nums']);
        //分享个数
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $share_num = $m_share->countNum($map);

        $data = array('is_collect'=>$is_collect,'collect_num'=>$collect_num,'share_num'=>$share_num);
        return $data;
    }
}