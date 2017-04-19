<?php
namespace Common\Controller;
use Think\Controller;
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
	 */
	public function to_back($data,$type=1) {
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
	    $apiResp->msg = L("$resp_msg");
	    $apiResp->result = $resp_result;
	    if($this->is_js ==1){
	        $result = "h5turbine(".json_encode($apiResp).")";
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
	    $oss_host = 'http://'.C('OSS_BUCKET').'.'.C('OSS_HOST').'/';
	    return  $oss_host.$url;
	}
	/**
	 * @desc 获取内容完整URL
	 */
	public function getContentUrl($url){
	    $content_host = C('CONTENT_HOST');
	    return $content_host.$url;
	}
	



	public function __destruct(){

        
    }
}