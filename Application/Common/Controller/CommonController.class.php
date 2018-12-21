<?php
namespace Common\Controller;
use Think\Controller;
import("Common.Lib.ApiResp","",".class.php");
class CommonController extends Controller {
    protected $is_verify = 1;//检验接口 0不校验 1校验
    protected $is_login = 0;
    protected $is_des = 0;
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
	    if(isset($this->params['is_js']) && $this->params['is_js'] ==1)        $this->is_js = 1;
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
	public function getParentAreaid($area_id){
	    switch ($area_id){
	        case '1':
	            $parent_id = 35;
	            break;
	        case '9':
	            $parent_id = 107;
	            break;
	        default:
	            $parent_id = $area_id;
	    }
	    return $parent_id;
	}



	public function __destruct(){

        
    }
}