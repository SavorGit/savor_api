<?php
namespace Common\Controller;
use Think\Controller;
use Common\Lib\UmengNotice;
import("Common.Lib.ApiResp","",".class.php");
class BaseController extends Controller {
    protected $is_verify = 1;//检验接口 0不校验 1校验
    protected $is_login = 0;
    protected $is_des = 0;
    protected $is_head = 1;
    protected $is_js =0;
    protected $params = array();
    protected $valid_fields = array(); //数据有效性验证(必参)
    protected $traceinfo = array();
    
    public function __construct(){
        parent::__construct();
        $this->_init_();
    }
    
	/*
	* 初始化请求数据
	*
	*/
	protected function _init_() {
	    $this->check_sign();
	    
		$params = file_get_contents('php://input');
		if($_SERVER['HTTP_DES']=='true'){
		    $this->is_des = 1;
		    $input_params = decrypt_data($params);
		}else{
		    $input_params = json_decode($params,true);
		}
		if(empty($input_params))  $input_params=array();
	    $this->params = array_merge($input_params,$_GET,$_POST);
	    if(isset($this->params['token']) && $this->params['token']=='null')    $this->params['token']='';
	    if(isset($this->params['is_head']) && $this->params['is_head'] ==0)    $this->is_head = 0;
	    if(isset($this->params['is_js']) && $this->params['is_js'] ==1)        $this->is_js = 1;
	    $this->forbid();

		if($this->is_verify){
		    if(empty($this->params)){
		        $this->to_back(1001);
		    }else{
		        if(!empty($this->valid_fields)){
		            foreach ($this->valid_fields as $key=>$value){
		                $tv = trim($this->params[$key]);
		                $this->params[$key] = addslashes($tv);
		                if($value == 1001){//验证参数不能为空
		                    if(empty($tv) && "0" != strval($tv)){
		                        $this->to_back($value);
		                    }
		                }
		            }
		        } 
		    }
		}
		return true;
	}

	/**
	 * 校验签名
	 */
	protected function check_sign() {


		if (isset($_GET['time']) && isset($_GET['sign'])){
	        $sign_time = $_GET['time'];
	        $sign = $_GET['sign'];
	        $compare = gen_request_sign($sign_time);
	        if (empty($sign) || $compare['sign'] != $sign) {
	            $this->to_back(1007);
	        }
	        unset($compare);
	    }else{
	        $this->to_back(1007);
	    }
	    return true;
	}
	
	private function forbid(){
	    $http_traceinfo = $_SERVER['HTTP_TRACEINFO'];
	    if(!empty($http_traceinfo)){
	        $http_traceinfo = explode(';', $http_traceinfo);
	        foreach ($http_traceinfo as $v){
	            $info = explode('=', $v);
	            $this->traceinfo[$info[0]] = $info[1];
	        }
	        $this->traceinfo['language'] = !empty($_SERVER['HTTP_SAVOR_LANGUAGE'])?$_SERVER['HTTP_SAVOR_LANGUAGE']:'zh-cn';
	    }
	    
	    if($this->is_head){
	        if(empty($this->traceinfo)){
	            $this->to_back(1003);
	        }
	    }
	    return true;
	}
	
	
	/**
	 * @param  $data
	 * @param  $type 1为明文json 2为加密
	 * @param $rep   替换提示字符串
	 */
	public function to_back($data,$type=1,$rep = '') {
	    $apiResp = new \ApiResp();
	    $errorinfo = C('errorinfo');
	    if(is_numeric($data)){
	        $resp_msg = $errorinfo[$data];
	        $resp_code = $data;
	        $resp_result = new \stdClass();
	    }elseif(is_object($data)){
	        $resp_code = $apiResp->code;
	        $resp_msg = $errorinfo[$apiResp->code];
	        $resp_result = $data;
	    }elseif(is_array($data)){
	        $resp_code = $apiResp->code;
	        $resp_msg = $errorinfo[$apiResp->code];
	        $resp_result = !empty($data)?$data:new \stdClass();
	    }
	    
	    $apiResp->code = $resp_code;
	    if(!empty($rep)){
	        $msg = L("$resp_msg");
	        $msg = str_replace('#', $rep, $msg);
	        $apiResp->msg = $msg;
	    }else {
	        $apiResp->msg = L("$resp_msg");
	    }
	    
	    $apiResp->result = $resp_result;
	    if($this->is_js ==1){
	        $result = "h5turbine(".json_encode($resp_result).")";
	        echo $result;
	        exit;
	    }else {
	        $result = json_encode($apiResp);
	    }
	    
	    if($type == 2){
	        $this->is_des = 1;   
	    }else{
	        $this->is_des = 0;
	    }
	    
	    if($this->is_des){
	        $encry = encrypt_data($result);
	        header('des:true');
	        echo $encry;
	    }else{
	        echo $result;
	    }
	    exit;
	}

	/**
	 *@desc 获取阿里云资源全路径
	 */
	public function getOssAddr($url){
	    $oss_host = 'http://'.C('OSS_HOST').'/';
	    return  $oss_host.$url;
	}
	/**
	 * @desc 获取内容完整URL
	 */
	public function getContentUrl($url){
	    $content_host = C('CONTENT_HOST');
	    return $content_host.$url;
	}
	public function getOssAddrByMediaId($id){
	    $m_media = new \Common\Model\MediaModel();
	    $info = $m_media->getMediaInfoById($id);
	    return $info['oss_addr'];
	}
	/**
	 * @desc 获取网络版酒楼类型ID字符串 例如 2,3,6
	 */
	public function getNetHotelTypeStr(){
	    $hotel_box_type_arr = C('HEART_HOTEL_BOX_TYPE');
	    $hotel_box_type_arr = array_keys($hotel_box_type_arr);
	    $space = '';
	    $hotel_box_type_str = '';
	    foreach($hotel_box_type_arr as $key=>$v){
	        $hotel_box_type_str .= $space .$v;
	        $space = ',';
	    }
	    return $hotel_box_type_str;
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
    public function pushData($display_type,$device_type = "3",$type='listcast',$option_name,$after_open,
                             $device_tokens = '',$ticker,$title,$text,$production_mode = 'false',
                             $custom = array(),$extra,$alert){
        $obj = new UmengNotice();
        
        //$pam['device_tokens'] = 'AqWNvmADF_1bqndJXoPF6ZqPBSNz--iRzfGQMy-E_n9P,AtBHUz8wGEqACpVAX8iZ5m1O-HkiWvqFviS09x8aYd6A';
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
    /**
     * @desc 发送短信
     */
    public function sendToUcPa($info,$param,$type=1,$msg_type=2){
        $to = $info['tel'];
        $bool = true;
        $ucconfig = C('SMS_CONFIG');
        $options['accountsid'] = $ucconfig['accountsid'];
        $options['token'] = $ucconfig['token'];
        if($type==6){
            $templateId = $ucconfig['option_repair_done_templateid'];
        }
    
        $ucpass= new \Common\Lib\Ucpaas($options);
        $appId = $ucconfig['appid'];
        $sjson = $ucpass->templateSMS($appId,$to,$templateId,$param);
        $sjson = json_decode($sjson,true);
        $code = $sjson['resp']['respCode'];
        if($code === '000000') {
            $data = array();
            $data['type'] = $type;
            $data['status'] = 1;
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['update_time'] = date('Y-m-d H:i:s');
            $data['url'] = $param;
            $data['tel'] = $to;
            $data['resp_code'] = $code;
            $data['msg_type'] = $msg_type;
            $m_account_sms_log =  new \Common\Model\AccountMsgLogModel();
            $m_account_sms_log->addData($data);
            return true;
        }else{
            return false;
        }
    }
    
	public function __destruct(){

        
    }
}