<?php
/**
 *酒店model
 *@author  hongwei <[<email address>]>
 * 
 */
namespace Common\Model;
use Think\Model;

class MediaModel extends Model{

	public function getList($where, $order='id desc', $start=0,$size=5){
	    $list = $this->where($where)
					  ->order($order)
					  ->limit($start,$size)
					  ->select();
		$count = $this->where($where)->count();
		if($count){
		    $image_host = 'http://'.C('OSS_HOST').'/';
		    foreach ($list as $k=>$v){
		        $list[$k]['oss_addr'] = $image_host.$v['oss_addr'];
		    }
		}
		$objPage = new Page($count,$size);		  
		$show = $objPage->admin_page();
		$data = array('list'=>$list,'page'=>$show);
        return $data;
	}


	public function getWhere($where, $field){
		$list = $this->where($where)->field($field)->select();

		return $list;
	}


	public function getMediaInfoById($media_id){
	    $oss_host = 'http://'.C('OSS_HOST').'/';
	    $vinfo = $this->find($media_id);
	    if($vinfo){
	        $vinfo['oss_addr'] = $oss_host.$vinfo['oss_addr'];
	    }
	    return $vinfo;
	}
	
	public function getMediaInfoByName($name){
	    
	}


}
