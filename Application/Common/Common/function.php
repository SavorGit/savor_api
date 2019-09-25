<?php
use Common\Lib\Crypt3Des;
use Common\Lib\AliyunMsn;

function http_host(){
    $http = 'https://';
    return $http.$_SERVER['HTTP_HOST'];
}

function wx_sec_check($url,$duration=0){
    $img_urls = array();
    $typeinfo = C('RESOURCE_TYPEINFO');
    $name_info = pathinfo($url);
    $surfix = strtolower($name_info['extension']);
    $media_type = $typeinfo[$surfix];
    if($media_type==1){
        $video_duration = intval($duration);
        if($video_duration){
            $video_img_num = array();
            for($i=1;$i<=$video_duration;$i++){
                $video_img_num[]=$i;
            }
            shuffle($video_img_num);
            $img_urls[]=$url."?x-oss-process=video/snapshot,t_{$video_img_num[0]}000,f_jpg,w_450,m_fast";
            $img_urls[]=$url."?x-oss-process=video/snapshot,t_{$video_img_num[1]}000,f_jpg,w_450,m_fast";
            $img_urls[]=$url."?x-oss-process=video/snapshot,t_{$video_img_num[2]}000,f_jpg,w_450,m_fast";
        }
    }else{
        $img_urls = array($url."?x-oss-process=image/resize,p_50/quality,q_70");
    }
    $res = array();
    $config = C('SMALLAPP_CONFIG');
    $filePath = SAVOR_M_TP_PATH.'/Public/content/'.'wx_imgtmp.png';
    foreach ($img_urls as $v){
        $img_url = $v;
        $img = file_get_contents($img_url);
        file_put_contents($filePath, $img);
        $obj = new \CURLFile(realpath($filePath));
        $obj->setMimeType("image/png");
        $file['media'] = $obj;

        $token = getWxAccessToken($config);
        $url = "https://api.weixin.qq.com/wxa/img_sec_check?access_token=$token";

        $data = $file;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $res_data = curl_exec($curl);
        curl_close($curl);
        $data = array('url'=>$img_url,'errcode'=>'','errmsg'=>'');
        if($res_data){
            $res_data = json_decode($res_data,true);
            $data['errcode'] = $res_data['errcode'];
            $data['errmsg'] = $res_data['errmsg'];
        }
        $res[] = $data;
    }
    return $res;
}

function getWxAccessToken($app_config){
    $key_token = $app_config['cache_key'];
    $redis = SavorRedis::getInstance();
    $redis->select(5);
    $token = $redis->get($key_token);
    if(empty($token)){
        $url_access_token = 'https://api.weixin.qq.com/cgi-bin/token';
        $appid = $app_config['appid'];
        $appsecret = $app_config['appsecret'];
        $url = $url_access_token."?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $re = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($re,true);
        if(isset($result['access_token'])){
            $redis->set($key_token,$result['access_token'],360);
            $token = $result['access_token'];
        }
    }
    return $token;
}

function jd_union_api($params,$api,$method='get'){
    $jd_config = C('JD_UNION_CONFIG');
    $all_params = array();
    $all_params['app_key'] = $jd_config['app_key'];
    $all_params['method'] = $api;
    $all_params['param_json'] = json_encode($params);
    $all_params['sign_method'] = 'md5';
    $all_params['timestamp'] = date('Y-m-d H:i:s');
    $all_params['v'] = '1.0';
    ksort($all_params);
    $str = '';
    $appScret = $jd_config['app_secret'];
    foreach ($all_params as $k => $v) $str .= $k . $v;
    $sign = strtoupper(md5($appScret . $str . $appScret));
    $all_params['sign'] = $sign;

    $curl = new \Common\Lib\Curl();
    $api_url = 'https://router.jd.com/api';
    $res_data = array();
    if($method=='get'){
        $url = $api_url.'?'.http_build_query($all_params);
        $res = '';
        $curl::get($url,$res);
        if($res){
            $res = json_decode($res,true);
            foreach ($res as $v){
                if($v['code']){
                    $res_data = $v;
                }else{
                    $res_data = json_decode($v['result'],true);
                }
                break;

            }
        }
    }
    return $res_data;
}

function bonus_random($total,$num,$min,$max){
    $data = array();
    if ($min * $num > $total) {
        return array();
    }
    if($max*$num < $total){
        return array();
    }
    while ($num >= 1) {
        $num--;
        $kmix = max($min, $total - $num * $max);
        $kmax = min($max, $total - $num * $min);
        $kAvg = $total / ($num + 1);
        //获取最大值和最小值的距离之间的最小值
        $kDis = min($kAvg - $kmix, $kmax - $kAvg);
        //获取0到1之间的随机数与距离最小值相乘得出浮动区间，这使得浮动区间不会超出范围
        $r = ((float)(rand(1, 10000) / 10000) - 0.5) * $kDis * 2;
        $k = sprintf("%.2f", $kAvg + $r);
        $total -= $k;
        $data[] = $k;
    }
    shuffle($data);
    return $data;
}

/**
 * 发送主题消息
 * @param $message消息内容
 * @param $type 20 发红包到用户零钱
 * @return Ambigous <boolean, mixed>
 */
function sendTopicMessage($message,$type){
    if(empty($message) || empty($type)){
        return false;
    }
    $all_type = array('20'=>'bonustomoney');
    $accessId = C('OSS_ACCESS_ID');
    $accessKey= C('OSS_ACCESS_KEY');
    $endPoint = C('QUEUE_ENDPOINT');
    $topicName = C('TOPIC_NAME');

    $ali_msn = new AliyunMsn($accessId, $accessKey, $endPoint);
    $mir_time = getmicrotime();
    $serial_num = $mir_time*10000;
    if(!is_array($message)){
        $message = array($message);
    }
    $now_message = array();
    foreach ($message as $v){
        $now_message[] = array('order_id'=>$v,'serial_num'=>"$serial_num");
    }
    $messageBody = base64_encode(json_encode($now_message));
    $messageTag = $all_type[$type];
    $res = $ali_msn->sendTopicMessage($topicName,$messageBody,$messageTag);
    return $res;
}
/**
 * @desc   发送虚拟小平台消息
 * @param  $message消息内容
 * @param  $type
 * @return  
 */
function sedVsTopicMessage($messageBody,$type){
    if(empty($messageBody) || empty($type)){
        return false;
    }
    $accessId = C('OSS_ACCESS_ID');
    $accessKey= C('OSS_ACCESS_KEY');
    $endPoint = C('QUEUE_ENDPOINT');
    $topicName = C('TOPIC_NAME');
    $ali_msn = new AliyunMsn($accessId, $accessKey, $endPoint);
    $res = $ali_msn->sendTopicMessage($topicName,$messageBody,$type);
    return $res;
}

function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
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
function changeTimeType($seconds){
    if ($seconds > 3600){
        $hours = intval($seconds/3600);
        $minutes = $seconds % 3600;
        $time = $hours.":".gmstrftime('%M:%S', $minutes);
    }else{
        $time = gmstrftime('%H:%M:%S', $seconds);
    }
    return $time;
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
function getRandNums($min=0,$max=100,$num=10){
    $numbers = range ($min,$max);
    //shuffle 将数组顺序随即打乱
    shuffle ($numbers);
    //array_slice 取该数组中的某一段
    
    $result = array_slice($numbers,0,$num);
    return $result;
}
