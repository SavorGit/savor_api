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