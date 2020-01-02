<?php
/**
 * @desc 小程序投屏日志记录
 */
namespace Common\Model\Smallapp;
use Think\Model;

class ForscreenRecordModel extends Model{

	protected $tableName='smallapp_forscreen_record';

    public function getWhere($fields,$where,$order,$limit,$group){
        $data = $this->field($fields)->where($where)->order($order)->group($group)->limit($limit)->select();
        return $data;
    }

    public function getWheredata($fields,$where,$order){
        $data = $this->field($fields)->where($where)->order($order)->select();
        return $data;
    }

    public function getWhereCount($where){
        $count = $this->where($where)->count();
        return $count;
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

	public function getInfo($where){
        $res = $this->where($where)->find();
        return $res;
    }

    public function recordTrackLog($serial,$params){
        $all_data = array(
           'oss_stime'=>'oss上传开始时间',
           'oss_etime'=>'oss上传结束时间',

           'php_position_nettystime'=>'php请求定位netty接口开始时间',
           'php_position_nettyetime'=>'php请求定位netty接口结束时间',
           'netty_position_url'=>'netty定位接口',
           'netty_position_result'=>'netty定位接口返回数据',

           'php_request_nettytime'=>'PHP请求netty时间',
           'netty_url'=>'php请求netty接口',
           'netty_result'=>'netty返回数据',
           'netty_receive_phptime'=>'netty接收php请求时间',
           'netty_pushbox_time'=>'netty推送盒子时间',

           'box_receivetime'=>'盒子接收netty时间',
           'box_downstime'=>'盒子下载开始时间',
           'box_downetime'=>'盒子下载完成时间',
        );
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(5);
        $cache_key = C('SAPP_FORSCREENTRACK').$serial;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $data = json_decode($res_cache,true);
        }else{
            $data = array();
        }
        foreach ($all_data as $k=>$v){
            if(isset($params[$k])){
                $data[$k]=$params[$k];
            }
        }
        $redis->set($cache_key,json_encode($data),86400);
    }

}