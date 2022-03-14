<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class SyslotteryModel extends BaseModel{
	protected $tableName='smallapp_syslottery';

	public function send_common_lottery($hotel_id,$mac,$activity_id,$user_barrage){
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
	    $res_activity = $m_activity->getInfo(array('id'=>$activity_id));
        $where = array('hotel_id'=>$hotel_id,'status'=>1,'type'=>3);
        $orderby = 'id desc';
        $fields = 'id as syslottery_id,prize as name';
        $res_data = $this->getDataList($fields,$where,$orderby,0,1);
        if($res_data['total']){
            $m_box = new \Common\Model\BoxModel();
            $where = array('a.state'=>1,'a.flag'=>0,'d.id'=>$hotel_id);
            $fileds = 'a.id as box_id,a.mac,d.name,ext.hotel_cover_media_id';
            $res_box = $m_box->getBoxInfo($fileds,$where);
            $m_netty = new \Common\Model\NettyModel();
            foreach ($res_box as $v) {
                $box_mac = $v['mac'];
                if($box_mac==$mac){
                    continue;
                }
                $message = array('action'=>122,'userBarrages'=>array($user_barrage));
                $m_netty->pushBox($box_mac,json_encode($message));
            }

            $now_syslottery_id = $res_data['list'][0]['syslottery_id'];
            $m_lotteryprize = new \Common\Model\Smallapp\SyslotteryPrizeModel();
            $res_lottery_prize = $m_lotteryprize->getDataList('*',array('syslottery_id'=>$now_syslottery_id),'id desc');
            $prize_data = array();
            foreach ($res_lottery_prize as $pv){
                $prize_data[]=array('name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                    'probability'=>$pv['probability'],'type'=>$pv['type']
                );
            }
            $people_num = $res_activity['people_num'];
            $prize = $res_data['list'][0]['name'];
            $start_time = date('Y-m-d H:i:s');
            $now_time = time();
            $end_time = date('Y-m-d H:i:s',$now_time+1800);
            $status = 2;

            $m_activityprize = new \Common\Model\Smallapp\ActivityprizeModel();
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(1);
            $common_key = C('SAPP_LUCKYLOTTERY_SENDCOMMON').$activity_id;
            $res_cache = $redis->get($common_key);
            $send_boxs = array();
            if(!empty($res_cache)){
                $send_boxs = json_decode($res_cache,true);
            }
            foreach ($res_box as $v){
                $box_mac = $v['mac'];
                if(in_array($box_mac,$send_boxs) || $box_mac==$mac){
                    continue;
                }
                $add_activity_data = array('hotel_id'=>$hotel_id,'openid'=>$res_activity['openid'],'name'=>'幸运抽奖','prize'=>$prize,
                    'box_mac'=>$box_mac,'people_num'=>$people_num,'start_time'=>$start_time,'end_time'=>$end_time,
                    'type'=>12,'status'=>$status);
                $now_activity_id = $m_activity->add($add_activity_data);

                $all_prize_data = array();
                foreach ($prize_data as $pv){
                    $all_prize_data[]=array('activity_id'=>$now_activity_id,'name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                        'probability'=>$pv['probability'],'type'=>$pv['type']
                    );
                }
                $m_activityprize->addAll($all_prize_data);

                //分配中奖信息
                $lottery_num = $people_num*2;
                $position = array();
                for($i=1;$i<=$lottery_num;$i++){
                    $position[]=$i;
                }
                shuffle($position);
                $cache_key = C('SAPP_LUCKYLOTTERY_POSITION').$now_activity_id;
                $position = array_slice($position,0,$people_num);
                $redis->set($cache_key,json_encode($position),86400);

                $hotel_logo = '';
                if($v['hotel_cover_media_id']>0){
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($v['hotel_cover_media_id']);
                    $hotel_logo = $res_media['oss_addr'];
                }
                $headPic = base64_encode($hotel_logo);
                $host_name = C('HOST_NAME');
                $code_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_id={$v['box_id']}&box_mac={$box_mac}&data_id={$now_activity_id}&type=46";
                $message = array('action'=>138,'countdown'=>120,'nickName'=>$v['name'],'headPic'=>$headPic,'codeUrl'=>$code_url);
                $m_netty->pushBox($box_mac,json_encode($message));

                $send_boxs[]=$box_mac;
            }
            $redis->set($common_key,json_encode($send_boxs),86400);
        }
    }
}