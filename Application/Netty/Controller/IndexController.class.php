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
        }
        parent::_init_();
    }
    public function index(){
        
        $box_mac = $this->params['box_mac'];
        $req_id  = getMillisecond();
        $data['box_mac'] = $box_mac;
        $data['req_id']  = $req_id;
        $post_data = http_build_query($data);
        //echo $post_data;exit;
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
                $map['msg']     = $this->params['msg'];
                $map['req_id']  = $req_id;
                $post_data = http_build_query($map);
                
                $ret = $this->curlPost($netty_push_url,$post_data);
                if($ret){
                    $this->to_back(json_decode($ret));
                }else {
                    $this->to_back(90109);
                }
            }else {
                $this->to_back(90109);
            }
        }else {
            $this->to_back(90109);
        } 
    }
    
    private function curlPost($url = '',  $post_data = '')
    {   
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
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