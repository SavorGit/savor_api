<?php
namespace Netty\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class IndexController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'Index':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'msg'=>1001);
                break;
            case 'pushnetty':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001,'msg'=>1001);
                break;
        }
        parent::_init_();
    }

    public function pushnetty(){
        $box_mac = $this->params['box_mac'];
        $msg = $this->params['msg'];
        $jsonStr= stripslashes(html_entity_decode($msg));
        $message = json_decode($jsonStr,true);
        if(!is_array($message) || empty($message)){

            $log_content = date("Y-m-d H:i:s").'[box_mac]'.$box_mac.'[msg]'.$msg.'[decode]error'."\n";
            $log_file_name = APP_PATH.'Runtime/Logs/'.'netty_'.date("Ymd").".log";
            @file_put_contents($log_file_name, $log_content, FILE_APPEND);

            $this->to_back(91001);
        }

        $log_content = date("Y-m-d H:i:s").'[box_mac]'.$box_mac.'[msg]'.$msg."\n";
        $log_file_name = APP_PATH.'Runtime/Logs/'.'netty_'.date("Ymd").".log";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);

        if(isset($message['openid'])){
            $m_user = new \Common\Model\Smallapp\UserModel();
            $res = $m_user->getOne('id',array('openid'=>$message['openid'],'status'=>1),'id desc');
            if(empty($res)){
                $this->to_back(92010);
            }
        }

        if(isset($message['avatarUrl'])){
            $head_pic = '';
            if(!empty($message['avatarUrl'])){
                $head_pic = base64_encode($message['avatarUrl']);
            }
            $message['headPic'] = $head_pic;
        }

        //判断盒子是否为4G
        $m_box = new \Common\Model\BoxModel();
        $forscreen_info = $m_box->checkForscreenTypeByMac($box_mac);
        if(!empty($forscreen_info) && isset($forscreen_info['box_id'])) {
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(15);
            $cache_key = 'savor_box_'.$forscreen_info['box_id'];
            $redis_box_info = $redis->get($cache_key);
            $box_info = json_decode($redis_box_info, true);
            if(!empty($box_info) && $box_info['is_4g']==1 && !empty($message['avatarUrl'])){
                $message['avatarUrl'] = '';
            }
        }
        //end

        $action = $message['action'];
        switch ($action){
            case 2://发现投视频
            case 5://点播官方视频
            case 6://生日歌点播
            case 12://发现点播视频
            case 13://点播商城商品
                $req_id = forscreen_serial($message['openid'],$message['forscreen_id'],$message['url']);
                break;
            case 4://多图投屏
                if(isset($message['img_list']) && !empty($message['img_list'])){
                    $req_id = forscreen_serial($message['openid'],$message['forscreen_id']);
                }else{
                    $req_id = forscreen_serial($message['openid'],$message['forscreen_id'],$message['url']);
                }
                break;
            case 7://投文件图片
                if(isset($message['resource_id'])){
                    $req_id = forscreen_serial($message['openid'],$message['resource_id'],$message['url']);
                }else{
                    $req_id = forscreen_serial($message['openid'],$message['forscreen_id'],$message['url']);
                }
                break;
            case 9://呼码
            case 10://投照片图集
            case 11://发现点播图片
                $req_id = forscreen_serial($message['openid'],$message['forscreen_id']);
                break;
            default:
                $req_id = getMillisecond();
        }
        $netty_position_num = 3;
        $netty_num = 3;

        $netty_data = array('box_mac'=>$box_mac,'req_id'=>$req_id);
        $post_data = http_build_query($netty_data);
        $nettyBalanceURL = C('NETTY_BALANCE_URL');

        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $position_result = array();
        for ($i=1;$i<=$netty_position_num;$i++){
            $netty_position_stime = getMillisecond();
            $result = $this->curlPost($nettyBalanceURL, $post_data);
            $netty_position_etime = getMillisecond();
            $position_result = json_decode($result,true);
            $params = array(
                'action'=>$action,
                'oss_stime'=>$message['res_sup_time'],
                'oss_etime'=>$message['res_eup_time'],
                'position_nettystime'=>$netty_position_stime,
                'position_nettyetime'=>$netty_position_etime,
                'netty_position_url'=>$nettyBalanceURL.'?'.$post_data,
                'netty_position_result'=>$position_result,
                'netty_position_num'=>$i
            );
            $m_forscreen->recordTrackLog($req_id,$params);
            if($position_result && $position_result['code']==10000){
                break;
            }else{
                usleep(50000);
            }
        }

        if(empty($position_result)){
            $this->to_back(91007);
        }
        if($position_result['code']!=10000){
            if($position_result['code']==10200 && $position_result['msg']=='请求标识不存在'){
                $error_code = 91002;
            }elseif($position_result['code']==10200 && $position_result['msg']=='MAC地址不存在'){
                $error_code = 91003;
            }elseif($position_result['code']==10006 && $position_result['msg']=='定位失败'){
                $error_code = 91004;
            }elseif($position_result['code']==10008 && $position_result['msg']=='该 MAC 地址未注册'){
                $error_code = 91005;
            }else{
                $error_code = 91006;
            }
            $this->to_back($error_code);
        }

        $host_name = C('HOST_NAME');
        $callback = $host_name.'/h5/notify/netty';

        $cmd_command = C('SAPP_CALL_NETY_CMD');
        $message['req_id'] = $req_id;
        unset($message['res_sup_time'],$message['res_eup_time']);

        $push_data = array('box_mac'=>$box_mac,'cmd'=>$cmd_command,'msg'=>json_encode($message),'req_id'=>$req_id,'callback'=>$callback);
        $post_data = http_build_query($push_data);

        $ret = array();
        $netty_result = array();
        for ($i=1;$i<=$netty_num;$i++){
            $request_time = getMillisecond();
            $netty_push_url = 'http://'.$position_result['result'].'/push/box';
            $ret = $this->curlPost($netty_push_url,$post_data);
            $netty_result = json_decode($ret,true);
            $params = array(
                'request_nettytime'=>$request_time,
                'netty_url'=>$netty_push_url.'?'.$post_data,
                'netty_result'=>$netty_result,
                'netty_num'=>$i,
            );
            $m_forscreen->recordTrackLog($req_id,$params);
            if($netty_result && $netty_result['code']==10000){
                break;
            }else{
                usleep(50000);
            }
        }
        if(empty($netty_result)){
            $this->to_back(91020);
        }
        if($netty_result['code']!=10000){
            if($netty_result['code']==10008 && $netty_result['msg']=='请求标识不存在'){
                $error_code = 91008;
            }elseif($netty_result['code']==10006 && $netty_result['msg']=='数据推送到机顶盒失败'){
                $error_code = 91009;
            }elseif($netty_result['code']==10200 && $netty_result['msg']=='请求标识不存在'){
                $error_code = 91010;
            }elseif($netty_result['code']==10200 && $netty_result['msg']=='推送指令不存在'){
                $error_code = 91011;
            }elseif($netty_result['code']==10200 && $netty_result['msg']=='要推送的 MAC 地址不存在'){
                $error_code = 91012;
            }elseif($netty_result['code']==10201 && $netty_result['msg']=='请输入正确的的 MAC 地址'){
                $error_code = 91013;
            }elseif($netty_result['code']==10200 && $netty_result['msg']=='要推送的消息内容不存在'){
                $error_code = 91014;
            }elseif($netty_result['code']==10008 && $netty_result['msg']=='无机顶盒注册'){
                $error_code = 91017;
            }elseif($netty_result['code']==10008 && $netty_result['msg']=='机顶盒没有注册'){
                $error_code = 91018;
            }else{
                $error_code = 91019;
            }
            $this->to_back($error_code);
        }
        $res = json_decode($ret,true);
        $this->to_back($res);
    }


    public function index(){
        $box_mac = $this->params['box_mac'];
        $is_js   = $this->params['is_js'];
        $req_id  = getMillisecond();
        $data['box_mac'] = $box_mac;
        $data['req_id']  = $req_id;
        $post_data = http_build_query($data);
        $nettyBalanceURL = C('NETTY_BALANCE_URL');
        $result = $this->curlPost($nettyBalanceURL, $post_data);
        
        if($result){
            $result = json_decode($result,true);
            if($result['code'] ==10000){
                $netty_push_url = 'http://'.$result['result'].'/push/box';
                $req_id  = getMillisecond();
                $map = array();
                $map['box_mac'] = $box_mac;
                $map['cmd']     = C('SAPP_CALL_NETY_CMD');
                $is_foul = 0;  //是否鉴黄 
                if($is_js==1){
                    $map['msg']     = urldecode($this->params['msg']);
                }else {
                    $map['msg']     = $this->params['msg'];
//                    $msg = json_decode($map['msg'],true);
//                    if($msg['action']==4 || $msg['action']==2){
//                        $rt = wx_sec_check('http://'.C('OSS_HOST').'/'.$msg['url'],10);
//                        foreach($rt as $key=>$v){
//                            if($v['errcode']==87014){
//                                $is_foul=1;
//                                break;
//                            }
//                        }
//                    }
                }
                if($is_foul){
                    $this->to_back(90108);
                }else {
                    $map['req_id']  = $req_id;
                    $post_data = http_build_query($map);
                    
                    $ret = $this->curlPost($netty_push_url,$post_data);
                    if($ret){
                        $this->to_back(json_decode($ret));
                    }else {
                        $this->to_back(90109);
                    } 
                }
                
            }else {
                $this->to_back(90109);
            }
        }else {
            $this->to_back(90109);
        } 
    }
    
    private function curlPost($url = '',  $post_data = ''){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 10,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $post_data,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/x-www-form-urlencoded",
          ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
          return 0;
        } else {
            
          return $response;
        }      
    }
}