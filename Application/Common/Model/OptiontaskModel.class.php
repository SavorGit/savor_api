<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;

use Think\Model;

class OptiontaskModel extends Model
{
	protected $tableName='option_task';

	public function getTaskInfoByUserid($fields, $where){
		$data = $this->field($fields)->where($where)->find();
		return $data;
	}

	public function saveData($dat, $where) {
		$ret = $this->where($where)->save($dat);
		return $ret;
	}


	public function addData($data, $type) {
		if($type == 1) {
			$result = $this->add($data);
			return $this->getLastInsID();
		} else {
			$result = $this->addAll($data);
		    return $result;
		}
		
	}
	public function getList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	                 ->join(' savor_area_info area on a.task_area = area.id','left')
	                 ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	                 ->join(' savor_sysuser user on a.publish_user_id=user.id','left')
	                 ->join('savor_sysuser appuser on a.appoint_user_id = appuser.id','left')
	                 ->join('savor_sysuser exeuser on a.exe_user_id = exeuser.id','left')  
	                 ->field($fields)
	                 ->where($where)
	                 ->order($order)
	                 ->limit($limit)
	                 ->select();
	    return $data;
	}
	/**
	 * @desc 获取任务列表（多人指派改造后）
	 */
	public function getMultList($fields,$where,$order,$limit){
	    $data = $this->alias('a')
	    ->join(' savor_area_info area on a.task_area = area.id','left')
	    ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	    ->join(' savor_sysuser user on a.publish_user_id=user.id','left')
	    ->join('savor_sysuser appuser on a.appoint_user_id = appuser.id','left')
	    //->join('savor_sysuser exeuser on a.exe_user_id = exeuser.id','left')
	    ->field($fields)
	    ->where($where)
	    ->order($order)
	    ->limit($limit)
	    ->select();
	    $m_sysuser = new \Common\Model\SysUserModel();
	    foreach($data as $key=>$val){
	        $space = '';
	        if($val['exe_user_id']){
	            $where = array();
	            $where['id'] = array('in',$val['exe_user_id']);
	            $where['status'] = 1;
	            $exe_users = $m_sysuser->getUserInfo($where,'remark',2);
	            foreach($exe_users as $k=>$v){
	                $data[$key]['exeuser'] .= $space . $v['remark'];
	                $space = ',';
	            }
	        }
	    }
	    
	    return $data;
	}
	
	
	public function getInfo($fields,$where){
	    $data = $this->alias('a')
	                 ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	                 ->join('savor_area_info area on a.task_area=area.id','left')
	                 ->join(' savor_sysuser user on a.publish_user_id=user.id','left')
	                 ->join('savor_sysuser appuser on a.appoint_user_id = appuser.id','left')
	                 ->join('savor_sysuser exeuser on a.exe_user_id = exeuser.id','left')
	                 ->field($fields)->where($where)->find();
	    return $data;
	}
	public function getMultInfo($fields,$where){
	    $data = $this->alias('a')
	                 ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	                 ->join('savor_area_info area on a.task_area=area.id','left')
	                 ->join(' savor_sysuser user on a.publish_user_id=user.id','left')
	                 ->join('savor_sysuser appuser on a.appoint_user_id = appuser.id','left')
	                 
	                 ->field($fields)->where($where)->find();
	    $m_sysuser = new \Common\Model\SysUserModel();
	    $where = array();
	    $where['id'] = array('in',$data['exe_user_id']);
	    $where['status'] = 1;
	    $exe_users = $m_sysuser->getUserInfo($where,'remark',2);
	    foreach($exe_users as $k=>$v){
	        $data['exeuser'] .= $space . $v['remark'];
	        $space = ',';
	    }
	    return $data;
	}
	public function updateInfo($where,$data){
	    $ret = $this->where($where)->save($data);
	    return $ret;
	}
	public function getdatas($fields,$where){
	    $data = $this->alias('a')
	    ->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
	    ->field($fields)->where($where)->select();
	    return $data;
	}
	public function countTaskNums($where){
	    $nums = $this->where($where)->count();
	    return $nums;
	}

	public function getOpdatas($fields,$where,$group){
		$data = $this->alias('a')
			->join('savor_hotel hotel on a.hotel_id = hotel.id','left')
			->group($group)
			->field($fields)->where($where)->select();
		return $data;
	}
}