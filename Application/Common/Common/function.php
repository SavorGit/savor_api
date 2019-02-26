<?php
use Common\Lib\Crypt3Des;

function http_host(){
    $http = 'https://';
    return $http.$_SERVER['HTTP_HOST'];
}

function gen_request_sign($sign_time)
{
	$key = C('SIGN_KEY');
	$sign = md5($sign_time . $key);
	return array('time' => $sign_time, 'sign' => $sign);
}
/**
 * web页面请求接口数据时生成签名字符串，用于向接口发送请求时签名
 * @param int $sign_time
 * @param string $key
 * @return string
 */
function create_sign($sign_time, $key = 'savor4321abcd1234')
{
	if (empty($sign_time)) {
		$sign_time = time();
	}
	$sign = md5($sign_time . $key);
	return $sign;
}
/**
 * 接口请求参数加密
 * @param str $data
 * @return Ambigous <boolean, mixed>
 */
function encrypt_data($data, $key = '')
{
	if (empty($key)) {
		$key = C('SECRET_KEY');
	}
	$crypt = new Crypt3Des($key);
	$result = $crypt->encrypt($data);
	return $result;
}

/**
 * 接口返回数据解密
 * @param str $data
 * @return Ambigous <boolean, mixed>
 */
function decrypt_data($data, $dejson = true, $key = '')
{
	if (empty($key)) {
		$key = C('SECRET_KEY');
	}
	$crypt = new Crypt3Des($key);
	$result = $crypt->decrypt($data);
	if ($dejson) {
		$res_data = array();
		if ($result) {
			$res_data = json_decode($result, true);
		}
	} else {
		$res_data = $result;
	}
	return $res_data;
}

function get_oss_host(){
	$oss_host = C('CONTENT_HOST');
	return $oss_host;
}
function get_host_name(){
    $oss_host = C('CONTENT_HOST');
    return $oss_host;
}

function create_token($deviceid = '', $user)
{
	$secrt = md5($deviceid . C('SIGN_KEY'));
	$identity = encrypt_data($user);
	return $identity . '_' . $secrt;
}
/**
 *
 * @param  $mobile
 *
 * 手机号验证
 */
function check_mobile($mobile, $pattern = false) {

	if (!$pattern) {
		$pattern = '/(^1[345678]\d{9}$)/';
	}
	$result = preg_match($pattern, $mobile, $match);

	if (empty($match)) {
		return false;
	}
	return true;
}
/**
 * @desc 获取客户端的ip地址
 */
function get_client_ipaddr()
{
	if (!empty($_SERVER ['HTTP_CLIENT_IP']) && filter_valid_ip($_SERVER ['HTTP_CLIENT_IP'])) {
		return $_SERVER ['HTTP_CLIENT_IP'];
	}
	if (!empty($_SERVER ['HTTP_X_FORWARDED_FOR'])) {
		$iplist = explode(',', $_SERVER ['HTTP_X_FORWARDED_FOR']);
		foreach ($iplist as $ip) {
			if (filter_valid_ip($ip)) {
				return $ip;
			}
		}
	}
	if (!empty($_SERVER ['HTTP_X_FORWARDED']) && filter_valid_ip($_SERVER ['HTTP_X_FORWARDED'])) {
		return $_SERVER ['HTTP_X_FORWARDED'];
	}
	if (!empty($_SERVER ['HTTP_X_CLUSTER_CLIENT_IP']) && filter_valid_ip($_SERVER ['HTTP_X_CLUSTER_CLIENT_IP'])) {
		return $_SERVER ['HTTP_X_CLUSTER_CLIENT_IP'];
	}
	if (!empty($_SERVER ['HTTP_FORWARDED_FOR']) && filter_valid_ip($_SERVER ['HTTP_FORWARDED_FOR'])) {
		return $_SERVER ['HTTP_FORWARDED_FOR'];
	}
	if (!empty($_SERVER ['HTTP_FORWARDED']) && filter_valid_ip($_SERVER ['HTTP_FORWARDED'])) {
		return $_SERVER ['HTTP_FORWARDED'];
	}
	return $_SERVER ['REMOTE_ADDR'];
}
/**
 *@desc 验证IP地址有效性
 */
function filter_valid_ip($ip)
{
	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 |
			FILTER_FLAG_IPV6 |
			FILTER_FLAG_NO_PRIV_RANGE |
			FILTER_FLAG_NO_RES_RANGE) === false
	) {
		return false;
	}
	return true;
}
/**
 * @desc 获取客户端类型ID
 */
function getClientId($clientname)
{
	$client_arr = C('CLIENT_NAME_ARR');
	$source_client = strtolower($clientname);
	return $client_arr[$source_client];
}
function getprovinceByip($ip){
    $url = "http://api.map.baidu.com/location/ip?ak=q1pQnjOG28z8xsCaoby2oqLTLaPgelyq&coor=bd09ll&ip=".$ip;
    $result = file_get_contents($url);
    $re = json_decode($result,true);
    
    if($re && $re['status'] == 0){
        $province_name = $re['content']['address_detail']['province'];
        
    }else{
        $province_name = '北京市';
    }
    return $province_name;
}
/**
 * @desc 获取url的文件扩展名
 */
function getExt($url){
    if($url){
        return pathinfo( parse_url($url)['path'] )['extension'];
    }else {
        return '';
    }
}
function sortArrByOneField(&$array, $field, $desc = false){
    $fieldArr = array();
    foreach ($array as $k => $v) {
        $fieldArr[$k] = $v[$field];
    }
    $sort = $desc == false ? SORT_ASC : SORT_DESC;
    array_multisort($fieldArr, $sort, $array);
}
//获取13位时间戳
function getMillisecond() {
    list($t1, $t2) = explode(' ', microtime());
    return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
}
/**
 * @desc 秒转换为分秒
 */
function secToMinSec($secs){
    $secs = intval($secs);
    if($secs<=0){
        return "0″";
    }else if($secs>0 && $secs<=60){
        return $secs."″";
    }else if($secs > 60){
        $min = floor($secs / 60);
        $sec  = $secs % 60;
        if($sec==0){
            return $min."′";
        }else if($sec>0){
            return $min."′".$sec."″";
        }
    }
}
function viewTimes($strtime){
    $now = time();
    $diff_time =  $now-$strtime;
    if($diff_time<=600){
        $view_time = '刚刚';
    }else if($diff_time<3600){
        $d_view = floor($diff_time/60);
        $view_time = $d_view.'分钟前';
    }else if($diff_time<=86400){
        $d_view = floor($diff_time/3600);
        $view_time = $d_view.'小时前';
    }else {
        $view_time = date('n月j日',$strtime);
    }
    return $view_time;
}
function getgeoByloa($lat,$lon){
    $ak = C('BAIDU_GEO_KEY');
    $url = 'http://api.map.baidu.com/geocoder/v2/?location='.$lat.','.$lon.'&output=json&pois=0&ak='.$ak;
    $result = file_get_contents($url);
    $re = json_decode($result,true);
    if($re && $re['status'] == 0){
        return $re['result'];
    }
}
