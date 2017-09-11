<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class TvModel extends Model{
    protected $tableName  ='tv';
	public function getList($where, $field){
		 $list = $this->field($field)->where($where)
					  ->select();
        return $list;
	}

	public function isTvInfo($field,$where,$start=false,$size){
		//savor_tv
		if( $start ) {
			$sql ="select $field from savor_tv as tv
	        left join savor_box as b on b.id=tv.box_id
	        left join savor_room as r on r.id=b.room_id
	        left join savor_hotel as h on h.id=r.hotel_id
	        where ".$where.' and tv.flag=0 and b.flag=0 and
	        r.flag=0 limit '.$start.','.$size;
		} else {
			$sql ="select $field from savor_tv as tv
		    left join savor_box as b on b.id=tv.box_id
		    left join savor_room as r on r.id=b.room_id
		    left join savor_hotel as h on h.id=r.hotel_id
		    where ".$where.' and tv.flag=0 and b.flag=0 and
		    r.flag=0 ';
		}
		$result = $this->query($sql);
		$data = array('list'=>$result);
		return $data;
	}


	public function changeBoxTv($result=[]){

		$box_stet = array(
			1=>'正常',
			2=>'冻结',
			3=>'报损',
		);

		if(!$result || !is_array($result)){
			return [];
		}
		//$boxId = [];
		$boxModel = new BoxModel;
		foreach ($result as $key=> $value){
			foreach ($box_stet as $tvkey=>$tvvalue){
				if($value['bstate'] == $tvkey) {
					$result[$key]['bstate']  = $tvvalue;
				}
			}
		}
		/*
		$filter       = [];
		$filter['id'] = ['IN',$boxId];

		$arrBox = $boxModel->getAll('id,name',$filter);
		foreach ($result as &$value){
			foreach ($arrBox as  $row){
				if($value['box_id'] == $row['id']){
					$value['box_name'] = $row['name'];
				}
			}
		}*/
		return $result;
	}

	


	
}
