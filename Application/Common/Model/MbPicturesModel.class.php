<?php
namespace Common\Model;
use Think\Model;



/**
 * Class TagModel
 * @package Admin\Model
 */
class MbPicturesModel extends Model{
	protected $tableName='mb_pictures';

	public function getOne($where){
		$ret = $this->where($where)->find();
		return $ret;
	}

	/**
	 * ��ȡ��ǩ�б��б��ҳ��
	 * @access public
	 * @param string $where ɸѡ����
	 * @param string $order ����
	 *  @param integer $start �ڼ�ҳ
	 *  @param integer $size ÿҳ����
	 *  @return array
	 */
	public function getList($where, $order='id desc', $start=0,$size=5){
		$list = $this->where($where)
			->order($order)
			->limit($start,$size)
			->select();
		$count = $this->where($where)->count();
		$objPage = new Page($count,$size);
		$show = $objPage->admin_page();
		$data = array('list'=>$list,'page'=>$show);
		return $data;
	}

	public function saveData($data, $where) {
		$bool = $this->where($where)->save($data);
		return $bool;
	}

	public function addData($data) {
		$result = $this->add($data);
		return $result;
	}


	public function delData($id) {
		$delSql = "DELETE FROM `savor_mb_pictures` WHERE contentid = '{$id}'";
		$result = $this -> execute($delSql);
		return  $result;
	}

	public function delWhereData($where) {
	    $result = $this->where($where)->delete();
		return  $result;
	}

	public function getWhereData($where, $field='') {
		$result = $this->where($where)->field($field)->select();
		return  $result;
	}

	public function getAllList($filed,$where,$order){
		$data = $this->field($filed)->where($where)->order($order)->select();
	    return $data;
	}
}