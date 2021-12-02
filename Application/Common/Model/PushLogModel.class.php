<?php
/**
 * @desc   推送日志
 * @author zhang.yingtao 
 * @since  2018-02-01
 */
namespace Common\Model;
use Think\Model;
use Common\Lib\UmengNotice;
class PushLogModel extends Model{
	protected $tableName='push_log';
	
	public function addInfo($data,$type=1){
	    if($type==1){
	        return $this->add($data);
	    }else {
	        return $this->addAll($data);
	    }
	}
	
	/**
	 * @desc 推送客户端数据
	 * @param $display_type 必填, 消息类型: notification(通知), message(消息)
	 * @param $device_type  客户端类型   3：安卓  4：ios
	 * @param $type listcast-列播(要求不超过500个device_token)
	 * @param $option_name app客户端  (运维端:optionclient)
	 * @param $after_open 点击"通知"的后续行为，默认为打开app
	 * @param $device_tokens  设备token
	 * @param $production_mode 可选, 正式/测试模式。默认为true
	 * @param $custom   当display_type=message时, 必填
	 *                  当display_type=notification且after_open=go_custom时, 必填
	 用户自定义内容, 可以为字符串或者JSON格式。
	 * @param $extra   可选, JSON格式, 用户自定义key-value。只对"通知"
	 */
	public function uPushData($display_type,$device_type = "3",$type='listcast',$option_name,$after_open,
	    $device_tokens = '',$ticker,$title,$text,$production_mode = 'false',
	    $custom = array(),$extra,$alert){
	        $obj = new UmengNotice();
	        
	        $pam['device_tokens'] = $device_tokens;
	        $pam['time'] = time();
	        $pam['ticker'] = $ticker;
	        $pam['title'] = $title;
	        $pam['text'] = $text;
	        $pam['after_open'] = $after_open;
	        $pam['production_mode'] = $production_mode;
	        $pam['display_type']    = $display_type;
	        if(!empty($custom)){
	            $pam['custom'] = json_encode($custom);
	        }
	        if(!empty($extra)){
	            $pam['extra'] = $extra;
	        }
	        if($device_type==3){
	            if(empty($custom)){
	                $pam['custom'] = array('type'=>$type);
	            }
	            $listcast = $obj->umeng_android($type);
	            //设置属于哪个app
	            $config_parm = $option_name;
	            $listcast->setParam($config_parm);
	            $listcast->sendAndroidListcast($pam);
	        }else if($device_type ==4){
	            if(!empty($alert)){
	                $pam['alert'] = $alert;
	            }
	            $pam['badge'] = 0;
	            $pam['sound'] = 'chime';
	            $listcast = $obj->umeng_ios($type);
	            //设置属于哪个app
	            $config_parm = $option_name;
	            $listcast->setParam($config_parm);
	            $listcast->sendIOSListcast($pam);
	        }
	}
}