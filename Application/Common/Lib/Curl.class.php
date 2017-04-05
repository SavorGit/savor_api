<?php
/**
 * Curl请求类
 *
 */
namespace Common\Lib;
use Common\Lib\RecordLog;
class Curl {
    
	/*
	* GET请求
	* @params string $url 请求网址加参数
	* @params array or string $re 返回结果
	* @params int $timeout 超时时间
	* @return array or boolean 成功返回数组,失败返回false
	*
	*/
	public static function get($url, &$re, $timeout = 20) {
        $start_time = microtime(true);
		($timeout<10) && $timeout = 10;
		$re = false;
		$retry = 1;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if($GLOBALS['HEADERINFO']){//如设置访问团车接口traceinfo
		    $header = $GLOBALS['HEADERINFO'];
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置HTTP头
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if (defined('CURLOPT_SSL_VERIFYHOST')) {
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		} else {
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		if (defined('CURLOPT_CNSSL_VERIFYPEER')) {
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		
		$return = '';
		for($i=1;$i<=$retry;$i++) {
			if($re !== false) break;
			$re = curl_exec($ch);
            $end_time = microtime(true);
            RecordLog::add_curl_log($ch, $url, $start_time, $end_time,'get');
			if ( is_string($re) && strlen($re) ) {
				curl_close($ch);
				$return = 'info';
			} else {
				if($i == $retry) {
					$curl_error = curl_error($ch);
					if (class_exists('RecordLog', false)) {
						RecordLog::addLog('请求接口：'.$url.',错误信息：'.$curl_error.serialize(curl_getinfo($ch)), '', 'error');
					} 
					curl_close($ch);
				}
			}

			if($return=='info') return true;
		}
		return false;
	}

	/*
	* POST请求
	* @params string $url 请求网址
	* @params array $data 请求参数
	* @params array or string $result 返回结果
	* @params int $timeout 超时时间
	* @return array or boolean 成功返回数组,失败返回false
	*
	*/
	public static function post($url, $data, &$result, $timeout = 20) {
        $start_time = microtime(true);
		($timeout<10) && $timeout = 10;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if($GLOBALS['HEADERINFO']){//如设置访问团车接口traceinfo
		    $header = $GLOBALS['HEADERINFO'];
		    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置HTTP头
		}else{
		    curl_setopt($ch, CURLOPT_HEADER, 0); //过滤HTTP头
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		//SSL HTTPS支持
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		if (defined('CURLOPT_SSL_VERIFYHOST')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		if(defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')){
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}
		$result = curl_exec($ch);
        $end_time = microtime(true);
        RecordLog::add_curl_log($ch, $url,$start_time, $end_time, $data, 'post');
		if (is_string($result) && strlen($result)) {
			$return = 'info';
		} else {
			$curl_error = curl_error($ch);
			if (class_exists('RecordLog', false)) {
				RecordLog::addLog('请求接口：'.$url.',错误信息：'.$curl_error.serialize(curl_getinfo($ch)), '', 'error');
			}
			$return = 'error';
		}
		curl_close($ch);
		if($return=='error') {
			return false;
		}
		return true;
	}

	/*
	* Socket请求
	* @params string $url 请求网址
	* @params string $data 请求参数
	* @params array or string $result 返回结果
	* @params int $timeout 超时时间
	* @return string or boolean 成功返回数组,失败返回false
	*
	*/
	function socketPost($url, $data, $timeout = 30)
    {
        $data = urlencode($data);
        $URL_Info = parse_url($url);
        if (empty($URL_Info["port"]))
            $port = 80;
        else
            $port = $URL_Info["port"];
        if (($fsock = fsockopen($URL_Info["host"], $port, $errno, $errstr, $timeout)) <
            0)
            return "建立通讯连接失败";
        $in = "POST " . $URL_Info["path"] . " HTTP/1.0\r\n";
        $in .= "Accept: */*\r\n";
        $in .= "Host: " . $URL_Info["host"] . "\r\n";
        $in .= "Content-type: text/plain\r\n";
        $in .= "Content-Length: " . strlen($data) . "\r\n";
        $in .= "Connection: Close\r\n\r\n";
        $in .= $data . "\r\n\r\n";

        if (!@fwrite($fsock, $in, strlen($in))) {
            fclose($fsock);
            return "发送报文失败";
        }
        $out = "";
        while ($buff = fgets($fsock, 2048)) {
            $out .= $buff;
        }
        fclose($fsock);
        $pos = strpos($out, "\r\n\r\n");
        $head = substr($out, 0, $pos); //http head
        $status = substr($head, 0, strpos($head, "\r\n")); //http status line
        $status_arr = explode(" ", $status, 3);
        if ($status_arr[1] == 200) {
            $body = substr($out, $pos + 4, strlen($out) - ($pos + 4)); //page body
            $body = urldecode($body);
        } else {
            return "http " . $status_arr[1];
        }
        return $body;
    }
    /**
     * 以xml方式发送请求,从GoodsModel移动过来,然后再补充日志功能 xiechengwei 2013-11-11
     * @param type $url
     * @param type $xml_data
     * @param type $timeout
     * @return type
     */
    public static function curl_post_xml($url, $xml_data, $timeout = 1) {
        $response = '';
        $start_time = microtime(true);
        //定义HTTP头信息
        $header[] = "Content-Type: application/xml";
        $header[] = "User-Agent: TUANCHE Client 1.1";
        $header[] = "Connection: keep-alive";
        $header[] = "Content-length: " . strlen($xml_data);

        $ch = curl_init();                                //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);              //设置链接
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);    //设置HTTP头
        curl_setopt($ch, CURLOPT_POST, 1);                //设置为POST方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);  //POST数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);      //设置是否返回信息
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);      //超时时间

        $response = curl_exec($ch);                       //接收返回信息
        $end_time = microtime(true);
        RecordLog::add_curl_log($ch, $url, $start_time, $end_time,'post');
        if (!$response) {
            $curl_error = curl_error($ch);
            if (class_exists('RecordLog', false)) {
                RecordLog::addLog('请求接口：' . $url . ',错误信息：' . $curl_error . ',请求参数:'.  $xml_data, '', 'error');
            }
        }
        curl_close($ch);                                 //关闭curl链接
        RecordLog::addLog('请求接口：' . $url . ',返回状态：' . $curl_error . ',返回结果:'.  $response, '', 'debug');
        return $response;
    }
    

}
?>