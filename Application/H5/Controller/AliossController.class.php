<?php
/**
 *OSS相关
 * 
 */
namespace H5\Controller;
use Think\Controller;


class AliossController extends Controller{
	 

    /**
     * 获取OSS资源上传的配置初始化参数
     *
     * @return [type] [description]
     */
    public function getOssParams(){
        $id = C('OSS_ACCESS_ID');
        $key = C('OSS_ACCESS_KEY');
        $host = 'https://'.C('OSS_HOST');
        $host_name = 'https://'.$_SERVER['HTTP_HOST'];

        $callbackUrl = $host_name.'/h5/'.C('OSS_SYNC_CALLBACK_URL');
        $callback_param = array(
            'callbackUrl'=>$callbackUrl,
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType'=>"application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->_gmt_iso8601($end);
    
        $rand = rand(10,99);
        	
        //资源空间的目录前缀
        $dir_addr = C('OSS_FORSCREEN_ADDR_PATH');
        $dir = $dir_addr.'/';
    
        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;
    
        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;
        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));
    
        $response              = array();
        $response['accessid']  = $id;
        $response['host']      = $host;
        $response['policy']    = $base64_policy;
        $response['signature'] = $signature;
        $response['expire']    = $end;
        $response['callback']  = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir']       = $dir;
        echo json_encode($response);
        exit;
    }
    
	 /**
	  * OSS异步回调
	  */
	 public function syncNotify(){
	 	// 1.获取OSS的签名header和公钥url header
		$authorizationBase64 = "";
		$pubKeyUrlBase64 = "";
		/*
		 * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
		 * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
		    RewriteEngine On
		    RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
		 * */
		if(isset($_SERVER['HTTP_AUTHORIZATION'])){
		    $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
		}
		if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])){
		    $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
		}

		if ($authorizationBase64 == '' || $pubKeyUrlBase64 == ''){
		    exit();
		}
		// 2.获取OSS的签名
		$authorization = base64_decode($authorizationBase64);
		// 3.获取公钥
		$pubKeyUrl = base64_decode($pubKeyUrlBase64);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		$pubKey = curl_exec($ch);
		if($pubKey == ""){
		    //header("http/1.1 403 Forbidden");
		    exit();
		}

		// 4.获取回调body
		$body = file_get_contents('php://input');
		// 5.拼接待签名字符串
		$authStr = '';
		$path = $_SERVER['REQUEST_URI'];
		$pos = strpos($path, '?');
		if ($pos === false){
		    $authStr = urldecode($path)."\n".$body;
		}else{
		    $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
		}

		// 6.验证签名
		$ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
		if($ok == 1){
		    parse_str($body,$bodyinfo);
		    $size = $bodyinfo['size'];
		    header("Content-Type: application/json");
		    $data = array("Status"=>"Ok",'size'=>$size);
		    echo json_encode($data);
		}else{
		    //header("http/1.1 403 Forbidden");
		    exit();
		}
	 }
	 
	 private function _gmt_iso8601($time){
	     $dtStr = date("c", $time);
	     $mydatetime = new \DateTime($dtStr);
	     $expiration = $mydatetime->format(\DateTime::ISO8601);
	     $pos = strpos($expiration, '+');
	     $expiration = substr($expiration, 0, $pos);
	     return $expiration."Z";
	 }
}
