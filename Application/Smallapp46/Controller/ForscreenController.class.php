<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ForscreenController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'collectforscreen':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'type'=>1001);
                break;
            case 'helpimage':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001);
                break;
        }
        parent::_init_();
    }


    public function collectforscreen(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];//1视频 2图片
        $forscreen_openids = C('COLLECT_FORSCREEN_OPENIDS');
        if(!array_key_exists($openid,$forscreen_openids)){
            $this->to_back(array());
        }
        $now_timestamps = getMillisecond();
        $serial_number = "Y_{$openid}_{$now_timestamps}";

        $message_data = array('openid'=>$openid,'forscreen_char'=>'','mobile_brand'=>'HUAWEI','mobile_model'=>'ANA-AN00','serial_number'=>$serial_number,
            'forscreen_id'=>$now_timestamps,'box_mac'=>$box_mac,'resource_id'=>$now_timestamps,'res_sup_time'=>$now_timestamps,'res_eup_time'=>$now_timestamps,
            'create_time'=>date('Y-m-d H:i:s')
            );
        if($type==1){
            $message_data['action']=2;
            $message_data['resource_type']=2;
            $message_data['resource_size']=78299193;
            $message_data['imgs']='["media/resource/exSsMBwBaG.MOV"]';

            $netty_data = array('action'=>2,'resource_type'=>2,'url'=>'media/resource/exSsMBwBaG.MOV','filename'=>"$now_timestamps.mp4",
                'openid'=>$openid,'video_id'=>$now_timestamps,'forscreen_id'=>$now_timestamps
            );
        }else{
            $message_data['action']=4;
            $message_data['resource_type']=1;
            $message_data['resource_size']=239687;
            $message_data['imgs']='["forscreen/resource/1597891208968.jpg"]';

            $img = array('url'=>'forscreen/resource/1597891208968.jpg','filename'=>"$now_timestamps.jpg",'order'=>0,'img_id'=>$now_timestamps,'resource_size'=>239687);
            $netty_data = array('action'=>4,'resource_type'=>1,'openid'=>$openid,'forscreen_id'=>$now_timestamps);
            $netty_data['img_list'] = array($img);
        }

        $netty_url = 'https://mobile.littlehotspot.com/Netty/index/pushnetty';
        $msg = json_encode($netty_data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $netty_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array('box_mac'=>$box_mac,'msg'=>"$msg"),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response,true);
        if(is_array($res) && isset($res['code'])){
            $cache_key = 'smallapp:forscreen:'.$box_mac;
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(5);
            $redis->rpush($cache_key, json_encode($message_data));
        }
        $this->to_back($res);
    }

    public function helpimage(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $is_show = C('FORSCREEN_GUIDE_IMAGE_SWITCH');
        if($is_show==1){
            $netty_data = array('action'=>150);
            $m_netty = new \Common\Model\NettyModel();
            $res = $m_netty->pushBox($box_mac,json_encode($netty_data));
            $this->to_back($res);
        }else{
            $this->to_back(array());
        }

    }

}
