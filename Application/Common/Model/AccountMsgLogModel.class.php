<?php
/**
 *酒店model
 *@author  hongwei <[<email address>]>
 * 
 */
namespace Common\Model;

use Think\Model;

class AccountMsgLogModel extends Model{
	protected $tableName = 'account_msg_log';

	public function getOne($map = array(),$order){
		if(!empty($map)){
			$result = $this->where($map)
				->order($order)
				->limit(0,1)
				->find();
			return $result;
		}
	}


	public function addData($data) {
		$bool = $this->add($data);
		return $bool;
	}



}
