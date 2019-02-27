<?php
namespace Common\Model;
use Think\Model;

class NettyModel extends Model{
    protected $tableName='box';

    public function pushBox($box_mac,$message){
        $req_id  = getMillisecond();
        $params = array('box_mac'=>$box_mac,'req_id'=>$req_id);
        $post_data = http_build_query($params);
        $balance_url = C('NETTY_BALANCE_URL');
        $result = $this->curlPost($balance_url, $post_data);
        $result = json_decode($result,true);
        if(is_array($result) && $result['code'] ==10000){
            $netty_push_url = 'http://'.$result['result'].'/push/box';
            $req_id  = getMillisecond();
            $box_params = array('box_mac'=>$box_mac,'msg'=>$message,'req_id'=>$req_id,'cmd'=>C('SAPP_CALL_NETY_CMD'));
            $post_data = http_build_query($box_params);
            $ret = $this->curlPost($netty_push_url,$post_data);
            $ret = json_decode($ret);
        }else{
            $ret = array('error_code'=>90109,'netty_data'=>$result);
        }
        return $ret;
    }

    private function curlPost($url,$post_data){
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
        if($err){
            return $err;
        }else{
            return $response;
        }
    }
}