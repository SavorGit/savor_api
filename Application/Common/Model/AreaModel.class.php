<?php
/**
 * Created by PhpStorm.
 * User: baiyutao
 * Date: 2017/5/16
 * Time: 13:54
 */
namespace Common\Model;
use Think\Model;

class AreaModel extends Model
{
	protected $tableName='area_info';
	/**
	 * 
	 * 
	 * @return [type] [description]
	 */
	public function getAllArea()
	{
		return $this->limit(20)->where('is_in_hotel=1')->select();

	}



	/**
	 * [areaIdToAareName description]
	 * @param  array  $result [description]
	 * @return [type]         [description]
	 */
	public function areaIdToAareName($result=[])
	{
		if(!$result || !is_array($result))
		{
			return [];
		}

		$area = $this->getAllArea();
		foreach ($result as &$value) 
		{
			foreach($area as $row)
			{	
				if($value['area_id'] == $row['id'])
				{
					$value['area_name'] = $row['region_name'];
				}
					
			}

		}
		return $result;

	}//End Function




}//End Class