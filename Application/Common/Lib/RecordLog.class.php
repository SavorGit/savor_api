<?php
namespace Common\Lib;
class RecordLog {
    
	/**
     * 写日志
     * @param string $content 日志内容
     * @return string 成功返回字符串
     */
	public static function addLog($content, $module = '', $logtype = ''){
		$module = empty($module) ? (MODULE_NAME.'/'.ACTION_NAME) : $module;
		$module = empty($logtype) ? $module : $module.'/'.$logtype;
		$queryFile = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : join(" ",$_SERVER['argv']);
		$content = date("Y-m-d H:i:s").'[api_url]'.$queryFile.'[client_ip]'.get_client_ipaddr().'[content]'.$content."\n";
		$log_file_name = C('REPORT_LOG_PATH').'error_report_'.date("Ymd").".log";
		@file_put_contents($log_file_name, $content, FILE_APPEND);
		return true;
	}
	
    /**
     * curl请求日志
     * @param obj $ch
     * @param string $url
     * @param array $post_data
     * @param string $method
     * @return string 成功返回字符串
     */
    public static function add_curl_log($ch, $url, $start_time, $end_time, $post_data=array(),$method='get'){
        $calc_time = $end_time - $start_time;
        $url_info = parse_url($url);
        if(is_string($post_data)){
            $post_data = json_decode($post_data,true);
            if(!is_array($post_data)){
                parse_str($post_data, $post_data);
            }
        }
        if(!empty($url_info['query'])){
            parse_str($url_info['query'], $arr);
            if(!empty($arr)){
                $post_data = array_merge($post_data, $arr);
            }
        }
        $http_info = is_resource($ch) ? curl_getinfo($ch) : array();
        $path = $url_info['path'];
        if(!empty($path) && strpos($path, 'get_num') !== false){
            $url_info['path'] = substr($path, 0, strpos($path, 'get_num') + strlen('get_num'));
            $post_data []  = $url;
        }
        $url = 'http://'.$url_info['host'].$url_info['path'];
        $data ['url'] = $url;
        $data ['params'] = json_encode($post_data);
        $data ['sys_model'] = MODULE_NAME.'/'.ACTION_NAME;
        $data ['method'] = $method;
        $data ['http_code'] = $http_info['http_code'];
        $data ['start_time'] = $start_time;
        $data ['end_time'] = $end_time;
        $data ['total_time'] = round($calc_time, 4);
        $data ['client_ip'] = get_client_ipaddr();
        $data ['server_ip'] = $_SERVER['SERVER_ADDR'];
            
        $log_time = date("Y-m-d H:i:s");
        $content = $log_time;
        foreach($data as $k=>$v){
            $content.= "[$k]".$v;
        }
		$content .= "\n";
		$log_file_name = C('REPORT_LOG_PATH').'success_report_'.date("Ymd").".log";
		file_put_contents($log_file_name, $content, FILE_APPEND);
        return true;
    }
}
?>