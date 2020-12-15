<?php
use Common\Lib\Crypt3Des;
use Common\Lib\AliyunMsn;
use Common\Lib\SavorRedis;

function http_host(){
    $http = 'https://';
    return $http.$_SERVER['HTTP_HOST'];
}

function check_phone_os(){
    $otype = 1;//1Android 2ios
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/IOS/i', $ua) || preg_match('/iphone/i', $ua) || preg_match('/ipad/i', $ua) || preg_match('/ipod/i', $ua)){
        $otype = 2;
    }
    return $otype;
}

function text_substr($str, $num){
    $intro = '';
    if($str){
        $intro_length = mb_strlen($str,'utf-8');
        if($intro_length <= $num){
            $intro = $str;
        }else{
            $strArr = array('。','！','？', '~', '，');
            $intro = mb_substr($str, 0, $num, 'utf-8');
            $last_str  = mb_substr($intro, -1, 1, 'utf-8');
            if(!in_array($last_str, $strArr)){
                $intro = $intro.'...';
            }
        }
    }
    return $intro;
}

function rad($d){
    return $d * 0.017453292519943; //$d * 3.1415926535898 / 180.0;
}

function mile($dist){
    return $dist * 0.0006214;
}

/**
 * @param 起点维度 $lat1
 * @param 起点经度 $lng1
 * @param 终点维度 $lat2
 * @param 终点经度 $lng2
 * @param 1米 2英里 $type
 * @return number
 */
function geo_distance($lat1, $lng1, $lat2, $lng2, $type = 1){
    $r = 6378137; // m 为单位
    $radLat1 = rad($lat1);
    $radLat2 = rad($lat2);
    $a = $radLat1 - $radLat2;
    $b = rad($lng1) - rad($lng2);
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $s = $s * $r;
    $distance = round($s * 10000) / 10000;
    if ($type == 2) {
        $distance = mile($distance);
    }
    return $distance;
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
    $redis  =  \Common\Lib\SavorRedis::getInstance();
    $redis->select(12);
    $cache_key = 'system_config';
    $res_config = $redis->get($cache_key);
    $all_config = json_decode($res_config,true);
    $jd_config = array();
    foreach ($all_config as $v){
        if($v['config_key']=='jd_union_smallapp'){
            $jd_config = json_decode($v['config_value'],true);
            break;
        }
    }
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

function forscreen_serial($openid,$forscreen_id,$oss_addr=''){
    $md5_str = $openid.$forscreen_id;
    if(!empty($oss_addr)){
        $addr_info = parse_url($oss_addr);
        if(strpos($addr_info['path'],'/')===0){
            $path = substr($addr_info['path'],1);
        }else{
            $path = $addr_info['path'];
        }
        $md5_str.=$path;
    }
    $serial = md5($md5_str);
    return $serial;
}

function isMobile($mobile) {
    return preg_match("/^1[3456789]\d{9}$/", $mobile);
}

function isEmail($vStr){
    $vLength = strlen($vStr);
    if($vLength < 3 || $vLength > 50){
        return false;
    }else{
        return preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $vStr);
    }
}


//获取首字母
function getFirstCharter($str){
    if(empty($str)){return '';}
    $fchar=ord($str{0});
    if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
    $s1=iconv('UTF-8','gb2312',$str);
    $s2=iconv('gb2312','UTF-8',$s1);
    $s=$s2==$str?$s1:$str;
    $asc=ord($s{0})*256+ord($s{1})-65536;
    if($asc =='-9004') return 'M';
    if($asc =='-6993' || $asc=='-5734') return 'L';
    if($asc =='-7431' || $asc=='-5714') return 'Y';
    if($asc =='-5711') return 'X';
    if($asc =='-2072') return 'Q';
    if($asc =='-4189') return 'Z';
    if($asc>=-20319&&$asc<=-20284) return 'A';
    if($asc>=-20283&&$asc<=-19776) return 'B';
    if($asc>=-19775&&$asc<=-19219) return 'C';
    if($asc>=-19218&&$asc<=-18711) return 'D';
    if($asc>=-18710&&$asc<=-18527) return 'E';
    if($asc>=-18526&&$asc<=-18240) return 'F';
    if($asc>=-18239&&$asc<=-17923) return 'G';
    if($asc>=-17922&&$asc<=-17418) return 'H';
    if($asc>=-17417&&$asc<=-16475) return 'J';
    if($asc>=-16474&&$asc<=-16213) return 'K';
    if($asc>=-16212&&$asc<=-15641) return 'L';
    if($asc>=-15640&&$asc<=-15166) return 'M';
    if($asc>=-15165&&$asc<=-14923) return 'N';
    if($asc>=-14922&&$asc<=-14915) return 'O';
    if($asc>=-14914&&$asc<=-14631) return 'P';
    if($asc>=-14630&&$asc<=-14150) return 'Q';
    if($asc>=-14149&&$asc<=-14091) return 'R';
    if($asc>=-14090&&$asc<=-13319) return 'S';
    if($asc>=-13318&&$asc<=-12839) return 'T';
    if($asc>=-12838&&$asc<=-12557) return 'W';
    if($asc>=-12556&&$asc<=-11848) return 'X';
    if($asc>=-11847&&$asc<=-11056) return 'Y';
    if($asc>=-11055&&$asc<=-10247) return 'Z';
    return null;
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
    $all_type = array('20'=>'bonustomoney','30'=>'rewardmoney');
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
		$pattern = '/(^1[3456789]\d{9}$)/';
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
function getgeoByTc($lat,$lon){
    $ak = C('BAIDU_GEO_KEY');
    $url = 'http://api.map.baidu.com/geoconv/v1/?coords='.$lon.','.$lat.'&from=1&to=5&ak='.$ak;
    
    $result = file_get_contents($url);
    $re = json_decode($result,true);
    if($re && $re['status'] == 0){
        return $re['result'];
    }
}

function getGDgeocodeByAddress($address){
    $key = C('GAODE_KEY');
    $url = "https://restapi.amap.com/v3/geocode/geo?address=$address&output=json&key=$key";
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL=>$url,
        CURLOPT_TIMEOUT=>2,
        CURLOPT_HEADER=>0,
        CURLOPT_RETURNTRANSFER=>1,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $res = json_decode($response,true);
    $data = array();
    if(is_array($res) && $res['status']==1 && $res['infocode']==10000){
        if(!empty($res['geocodes'][0]['location'])){
            $location_arr = explode(',',$res['geocodes'][0]['location']);
            $data['lng'] = $location_arr[0];//经度
            $data['lat'] = $location_arr[1];//维度
        }
    }
    return $data;
}

function getExpress($comcode,$num){
    $config = C('KUAIDI_100');
    $param = array (
        'com'=>$comcode,'num'=>$num,'phone'=>'',
        'from'=>'','to'=>'','resultv2' => '1'
    );
    //请求参数
    $post_data = array();
    $post_data["customer"] = $config['customer'];
    $post_data["param"] = json_encode($param);
    $sign = md5($post_data["param"].$config['key'].$post_data["customer"]);
    $post_data["sign"] = strtoupper($sign);

    $url = 'http://poll.kuaidi100.com/poll/query.do';
    $params = "";
    foreach ($post_data as $k=>$v) {
        $params .= "$k=".urlencode($v)."&";
    }
    $post_data = substr($params, 0, -1);
    //发送post请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    $data = str_replace("\"", '"', $result);
    $data = json_decode($data,true);
    return $data;
}

function getRandNums($min=0,$max=100,$num=10){
    $numbers = range ($min,$max);
    //shuffle 将数组顺序随即打乱
    shuffle ($numbers);
    //array_slice 取该数组中的某一段
    
    $result = array_slice($numbers,0,$num);
    return $result;
}

function getDistance($lat1, $lng1, $lat2, $lng2, $miles = true)
{
 $pi80 = M_PI / 180;
 $lat1 *= $pi80;
 $lng1 *= $pi80;
 $lat2 *= $pi80;
 $lng2 *= $pi80;
 $r = 6372.797; // mean radius of Earth in km
 $dlat = $lat2 - $lat1;
 $dlng = $lng2 - $lng1;
 $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
 $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
 $km = $r * $c;
 $dis = round(($miles ? ($km * 0.621371192) : $km),2);
 return $dis;
 if($dis<1){
     return ($dis*1000).'m';
 } else {
     return $dis.'km';
 }
}
