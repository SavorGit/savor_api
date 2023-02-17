<?php
namespace Common\Model;
use Think\Model;

class NettyModel extends Model{
    protected $tableName='box';

    public function pushBox($box_mac,$message,$req_id=''){
        if(empty($req_id)){
            $req_id  = getMillisecond();
        }
        $params = array('box_mac'=>$box_mac,'req_id'=>$req_id);
        $post_data = http_build_query($params);
        $balance_url = C('NETTY_BALANCE_URL');//定位接口
        $result = $this->curlPost($balance_url, $post_data);
        $result = json_decode($result,true);
        if(is_array($result)){
            if(empty($message)){
                return $result;
            }
            if($result['code'] ==10000){
                $netty_push_url = 'http://'.$result['result'].'/push/box';
                $req_id  = getMillisecond();
                $box_params = array('box_mac'=>$box_mac,'msg'=>$message,'req_id'=>$req_id,'cmd'=>C('SAPP_CALL_NETY_CMD'));
                $post_data = http_build_query($box_params);
                $ret = $this->curlPost($netty_push_url,$post_data);
                $res_pushbox = json_decode($ret,true);
                if(is_array($res_pushbox)){
                    if($res_pushbox['code']==10000){
                        $ret = $res_pushbox;
                    }else{
                        if($result['code']==10008 && $result['msg']=='请求标识不存在'){
                            $error_code = 91008;
                        }elseif($result['code']==10006 && $result['msg']=='数据推送到机顶盒失败'){
                            $error_code = 91009;
                        }elseif($result['code']==10200 && $result['msg']=='请求标识不存在'){
                            $error_code = 91010;
                        }elseif($result['code']==10200 && $result['msg']=='推送指令不存在'){
                            $error_code = 91011;
                        }elseif($result['code']==10200 && $result['msg']=='要推送的 MAC 地址不存在'){
                            $error_code = 91012;
                        }elseif($result['code']==10201 && $result['msg']=='请输入正确的的 MAC 地址'){
                            $error_code = 91013;
                        }elseif($result['code']==10200 && $result['msg']=='要推送的消息内容不存在'){
                            $error_code = 91014;
                        }elseif($result['code']==10008 && $result['msg']=='无机顶盒注册'){
                            $error_code = 91017;
                        }elseif($result['code']==10008 && $result['msg']=='机顶盒没有注册'){
                            $error_code = 91018;
                        }else{
                            $error_code = 91019;
                        }
                        $ret = array('error_code'=>$error_code,'netty_data'=>$result);
                    }
                }else{
                    $error_code = 91020;//推送接口返回结果异常 超时
                    $ret = array('error_code'=>$error_code,'netty_data'=>$result);
                }
            }else{
                if($result['code']==10200 && $result['msg']=='请求标识不存在'){
                    $error_code = 91002;
                }elseif($result['code']==10200 && $result['msg']=='MAC地址不存在'){
                    $error_code = 91003;
                }elseif($result['code']==10006 && $result['msg']=='定位失败'){
                    $error_code = 91004;
                }elseif($result['code']==10008 && $result['msg']=='该 MAC 地址未注册'){
                    $error_code = 91005;
                }else{
                    $error_code = 91006;
                }
                $ret = array('error_code'=>$error_code,'netty_data'=>$result);
            }
        }else{
            $error_code = 91007;//定位接口返回结果异常 超时
            $ret = array('error_code'=>$error_code,'netty_data'=>$result);
        }
        return $ret;
    }

    public function getPushBox($scope,$box_mac){
        //发送范围 1全网餐厅电视,2当前餐厅所有电视,3当前包间电视
        $hotel_box_type = C('HEART_HOTEL_BOX_TYPE');
        $tmp_box_type = array_keys($hotel_box_type);
        $all_box_type = join(',',$tmp_box_type);

        switch ($scope){
            case 1:
                $sql_box = "SELECT box.mac box_mac FROM savor_box box LEFT JOIN savor_room room ON box.`room_id`=room.`id` LEFT JOIN savor_hotel hotel ON room.`hotel_id`=hotel.`id` WHERE hotel.`state`=1 AND hotel.`flag`=0 AND box.`state`=1 AND box.`flag`=0 AND hotel.`hotel_box_type` IN ($all_box_type)";
                break;
            case 2:
                $sql_hotel = "select hotel.id as hotel_id from savor_box as box left join savor_room as room on box.room_id=room.id left join savor_hotel as hotel on room.hotel_id=hotel.id where box.mac='$box_mac' and box.state=1 and box.flag=0";
                $res_hotel = $this->query($sql_hotel);
                $hotel_id = $res_hotel[0]['hotel_id'];
                $sql_box = "SELECT box.mac box_mac FROM savor_box box LEFT JOIN savor_room room ON box.`room_id`=room.`id` LEFT JOIN savor_hotel hotel ON room.`hotel_id`=hotel.`id` WHERE hotel.`id`=$hotel_id AND hotel.`state`=1 AND hotel.`flag`=0 AND box.`state`=1 AND box.`flag`=0 AND hotel.`hotel_box_type` IN ($all_box_type)";
                break;
            default:
                $sql_box = '';
        }
        $all_box = array();
        if(!empty($sql_box)){
            $res_box = $this->query($sql_box);
            foreach ($res_box as $v){
                if($v['box_mac']==$box_mac){
                    continue;
                }
                $all_box[] = $v['box_mac'];
            }
        }
        return $all_box;
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