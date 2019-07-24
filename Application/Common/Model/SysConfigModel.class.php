<?php
namespace Common\Model;
use Think\Model;

class SysConfigModel extends Model{
	protected $tableName = 'sys_config';

    public function getAllconfig(){
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(12);
        $cache_key = 'system_config';
        $res_config = $redis->get($cache_key);
        if(!empty($res_config)){
            $res_config = json_decode($res_config,true);
        }else{
            $where = array('status'=>1);
            $res_config = $this->where($where)->select();
            $redis->set($cache_key,json_encode($res_config));
        }
        $sysconfig = array();
        foreach ($res_config as $v){
            $sysconfig[$v['config_key']] = $v['config_value'];
        }
        return $sysconfig;
    }

	public function getInfo($where){
	    if($where){
	        $where =" config_key in(".$where.") and status=1";
	    }
	    $result = $this->where($where)->select();
	    return $result;
	}

	public function getloadInfo($hotelid){
		$sql = "SELECT
        media.id AS id,
        media.oss_addr AS name,
        media.md5 AS md5,
        'fullMd5' AS md5_type,
        'load' AS type,
        media.oss_addr AS oss_path,
        media.duration AS duration,
        media.surfix AS suffix,
        0 AS sortNum,
        media.name AS chinese_name,
        media.id AS version
        FROM savor_sys_config config
        LEFT JOIN savor_media media on media.id=config.config_value
        where
            config.config_key='system_loading_image'
            and status=1";
		$result = $this->query($sql);
		return $result;
	}

    public function getOne($config_key){
                $ret = $this->where("config_key='".$config_key."'")->find();
                return $ret;
    }

}