<?php
/**
 *@author hongwei
 *
 *
 */
namespace Common\Model;
use Think\Model;

class ArticleModel extends Model
{
	protected $tableName='mb_content';

	public function getWhere($where, $field){
		$list = $this->where($where)->field($field)->select();
		return $list;
	}

	public function getArtinfoById($where){
		$sql = "  select mc.order_tag,mc.id artid,m.oss_addr as name,mcat.name as category,mc.index_img_url,mc.title,mc.duration,mc.img_url as imgUrl,mc.content_url as contentUrl,mc.tx_url as videoUrl,mc.share_title as shareTitle,
	           mc.share_content as shareContent,mc.type,mc.content,mc.media_id as mediaId,mc.create_time updateTime,aso.name as sourceName  from  savor_mb_content mc  left join savor_media m on mc.media_id = m.id left  join savor_mb_hot_category as mcat on mc.hot_category_id = mcat.id left join savor_article_source aso on aso.id=mc.source_id where 1=1 $where";
		$result = $this->query($sql);
		return $result[0];
	}

	public function getRecommend($where, $field, $sor_arr){

		foreach($sor_arr as $kv){
			$set_str .= " AND find_in_set($kv, order_tag)";
		}
		$sql =" select $field from savor_mb_content where $where and order_tag !='' $set_str order by savor_mb_content.create_time desc";
		$result = $this -> query($sql);
		return  $result;
	}

	//ɾ�����
	public function delData($id) {
		$delSql = "DELETE FROM `savor_mb_content` WHERE id = '{$id}'";
		$result = $this -> execute($delSql);
		return  $result;
	}

	public function getList($where, $order='id desc', $start=0,$size=20)
	{


		$list = $this->where($where)
			->order($order)
			->limit($start,$size)
			->select();

		return $list;

	}//End Function


	/**
	 * @param $table 表名
	 * @param $field 字段
	 * @param $joina join语句
	 * @param $joinb join语句
	 * @param $where where条件
	 * @param $orders
	 * @param $start
	 * @param $size
     * @return mixed 数组
     */
	public function getCapvideolist($where, $orders, $start, $size)
	{


		$field = 'mco.id id, mco.type,mco.content,mcat.name category, mco.title title,med.oss_addr name, mco.duration duration, mco.img_url imageURL, mco.content_url contentURL, mco.tx_url videoURL, mco.share_title shareTitle, mco.share_content shareContent, mco.create_time createTime,mco.media_id mediaId';
		$table = 'savor_mb_content mco';
		$joina = 'left join savor_mb_category mcat on mco.category_id = mcat.id';
		$joinb = 'left join savor_media med on med.id = mco.media_id';
		$acModel = M();
		$list = $acModel->table($table)->field($field)->join($joina)->join($joinb)->where($where)->order($orders)->limit($start,$size)->select();
		return $list;

	}//End Function


	public function getCapvideotoplist($table, $field, $joina,$joinb, $where, $orders)
	{

		$acModel = M();
		$list = $acModel->table($table)->field($field)->join($joina)->join($joinb)->where($where)->order($orders)->select();
		return $list;

	}//End Function




	public function getOssSize($oss_path) {
		if (empty($oss_path)) {
			return '0';
		}

		$accessKeyId = C('OSS_ACCESS_ID');
		$accessKeySecret = C('OSS_ACCESS_KEY');
		$endpoint = C('OSS_HOST');
		$bucket = C('OSS_BUCKET');
		$aliyun = new \Common\Lib\Aliyun($accessKeyId, $accessKeySecret, $endpoint);
		$aliyun->setBucket($bucket);
		$ossClient = $aliyun->getOssClient();
		$info = $ossClient->getObjectMeta($aliyun->getBucket(), $oss_path);
		//var_dump($info);
		if($info){
			
$byt = $this->byteFormat($info['content-length'],'MB');
		}else{
			$byt = '0';
		}

		return $byt;
	}

	public function byteFormat($bytes, $unit = "", $decimals = 2) {
		$units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);

		$value = 0;
		if ($bytes > 0) {
			// Generate automatic prefix by bytes
			// If wrong prefix given
			if (!array_key_exists($unit, $units)) {
				$pow = floor(log($bytes)/log(1024));
				$unit = array_search($pow, $units);
			}

			// Calculate byte value by prefix
			$value = ($bytes/pow(1024,floor($units[$unit])));
		}

		// If decimals is not numeric or decimals is less than 0
		// then set default value
		if (!is_numeric($decimals) || $decimals < 0) {
			$decimals = 2;
		}

		// Format output
		return sprintf('%.' . $decimals . 'f ', $value);
	}



	public function getTotalSize($result=[]){
		if(!$result || !is_array($result)){
			return [];
		}
		$arrArtId = [];
		$size = 0;

		foreach($result as &$value) {
			$contentid = $value['content_id'];
			$info = $this->where(array('type'=>3))->find($contentid);
			$size+= $info['size'];

		}
		return $size;

	}
	public function changeIdjName($result=[],$cat_arr){
		if(!$result || !is_array($result)){
			return [];
		}
		$arrArtId = [];
		$index = 1;
		foreach($result as &$value) {
			$contentid = $value['content_id'];
			$info = $this->find($contentid);
			$value['media_id'] = $info['media_id'];
			$value['category_id'] = $info['category_id'];
			$value['operators'] = $info['operators'];
			$value['title'] = $info['title'];
			$value['type'] = $info['type'];
			$value['size'] = $info['size'];
			$value['index'] = $index;
			$index++;
		}

		foreach ($result as &$value){
			foreach ($cat_arr as  $row){
				if($value['category_id'] == $row['id']){
					$value['cat_name'] = $row['name'];
				}
			}
		}

		return $result;
	}

	public function  changeCatname($result){
		$catModel = new CategoModel;
		$cat_arr =  $catModel->field('id,name')->select();
		foreach ($result as &$value){
			foreach ($cat_arr as  $row){
				if($value['category_id'] == $row['id']){
					$value['cat_name'] = $row['name'];
				}
			}
		}
		return $result;

	}


	public function getImgRes($path, $old_img) {
		$arr = explode('.', $old_img);
		$img_type = $arr[1];
		$pic = date('Y-m-d').'a_pics'.time().'.'.$img_type;
		$new_img = $path.'/'.$pic;
		$old_img = SITE_TP_PATH.$old_img;
		$res = $this->myCopyFunc($old_img, $new_img);
		if ( $res == 1 ) {
			return array('res'=>1,'pic'=>$pic);
		} else {
			return array('res'=>0);
		}
	}

	/**
	 * @param $res  ??????
	 * @param $des  ??????����
	 */

	public function myCopyFunc($res, $des) {
		if(file_exists($res)) {
			$r_fp=fopen($res,"r");

			$d_fp=fopen($des,"w+");
			//$fres=fread($r_fp,filesize($res));
			//��???��???
			$buffer=1024;
			$fres="";
			while(!feof($r_fp)) {
				$fres=fread($r_fp,$buffer);
				fwrite($d_fp,$fres);
			}
			fclose($r_fp);
			fclose($d_fp);
			return 1;
		} else {
			return 0;
		}
	}
    /**
     * @desc 获取某个分类下的文章数量
     */
	public function getCountByCatid($category_id){
	    $result = $this->where(array('category_id'=>$category_id))->count();
	    return $result;
	}
    /**
     * @desc 获取创富生活列表
     */
	 public function getCateList($where, $orders,  $size)
	{


		$field = 'mco.id artid,mco.sort_num, mco.type,mco.content, mco.title title,med.oss_addr name, 
		          mco.duration duration, mco.img_url imageURL, mco.content_url contentURL, 
		          mco.tx_url videoURL, mco.share_title shareTitle, mco.share_content shareContent, 
		          mco.update_time updateTime,mco.media_id mediaId,ars.name as sourceName,ars.logo';
		$table = 'savor_mb_content mco';
		$joina = ' left join savor_article_source as ars on mco.source_id = ars.id';
		$joinb = 'left join savor_media med on med.id = mco.media_id';
		$acModel = M();
		$list = $acModel->table($table)->field($field)->join($joina)->join($joinb)->where($where)->order($orders)->limit($size)->select();
		return $list;

	}
	/**
	 * @desc 获取专题列表
	 */
	public function getSpecialList($where, $orders,  $size){
	    
	    $sql ="select mco.id artid,mco.sort_num, mco.title title,med.oss_addr name,
		       mco.img_url imageURL, mco.content_url contentURL,
		       mco.tx_url videoURL, mco.share_title shareTitle,
		       mco.update_time updateTime,ars.name as sourceName,ars.logo
	           from savor_mb_content mco
	           left join savor_article_source as ars on mco.source_id = ars.id
	           left join savor_media med on med.id = mco.media_id where  $where order by $orders limit $size";
	    
	    $list = $this->query($sql);
	    return $list;
	}

}//End Class