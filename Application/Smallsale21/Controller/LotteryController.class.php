<?php
namespace Smallsale21\Controller;
use \Common\Controller\CommonController as CommonController;

class LotteryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'startLottery':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'box_mac'=>1001,'syslottery_id'=>1001,
                    'people_num'=>1001,'start_time'=>1002);
                break;
        }
        parent::_init_();
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
        $where = array('hotel_id'=>$hotel_id,'status'=>1,'type'=>2);
        $orderby = 'id desc';
        $fields = 'id as syslottery_id,prize as name';
        $res_data = $m_syslottery->getDataList($fields,$where,$orderby);
        $this->to_back($res_data);
    }

    public function startLottery(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $box_mac = $this->params['box_mac'];
        $syslottery_id = $this->params['syslottery_id'];
        $people_num = $this->params['people_num'];
        $start_time = $this->params['start_time'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_syslottery = new \Common\Model\Smallapp\SyslotteryModel();
        $res_sinfo = $m_syslottery->getInfo(array('id'=>$syslottery_id));
        $now_time = time();
        if(empty($start_time)){
            $start_time = date('Y-m-d H:i:s');
            $end_time = date('Y-m-d H:i:s',$now_time+1800);
            $status = 2;
        }else{
            $start_time = date("Y-m-d $start_time:00");
            $stime = strtotime($start_time);
            if($stime<$now_time){
                $this->to_back(93075);
            }
            $end_time = date('Y-m-d H:i:s',$stime+1800);
            $status = 0;
        }

        $add_activity_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'name'=>'幸运抽奖','prize'=>$res_sinfo['prize'],
            'box_mac'=>$box_mac,'people_num'=>$people_num,'start_time'=>$start_time,'end_time'=>$end_time,
            'type'=>11,'status'=>$status);
        $m_activity = new \Common\Model\Smallapp\ActivityModel();
        $activity_id = $m_activity->add($add_activity_data);

        $m_lotteryprize = new \Common\Model\Smallapp\SyslotteryPrizeModel();
        $res_lottery_prize = $m_lotteryprize->getDataList('*',array('syslottery_id'=>$syslottery_id),'id desc');

        $prize_data = array();
        $is_prizepool = 0;
        foreach ($res_lottery_prize as $pv){
            $prize_data[]=array('activity_id'=>$activity_id,'name'=>$pv['name'],'money'=>$pv['money'],'image_url'=>$pv['image_url'],
                'prizepool_prize_id'=>$pv['prizepool_prize_id'],'probability'=>$pv['probability'],'type'=>$pv['type']
            );
            if($pv['prizepool_prize_id']>0){
                $is_prizepool = 1;
            }
        }
        $m_activityprize = new \Common\Model\Smallapp\ActivityprizeModel();
        $m_activityprize->addAll($prize_data);

        if($is_prizepool==0){
            //分配中奖信息
            $lottery_num = $people_num*2;
            $position = array();
            for($i=1;$i<=$lottery_num;$i++){
                $position[]=$i;
            }
            shuffle($position);
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(1);
            $cache_key = C('SAPP_LUCKYLOTTERY_POSITION').$activity_id;
            $position = array_slice($position,0,$people_num);
            $redis->set($cache_key,json_encode($position),86400);
        }

        $code_url = '';
        if($status==2){
            $m_box = new \Common\Model\BoxModel();
            $where = array('a.mac'=>$box_mac,'a.state'=>1,'a.flag'=>0,'d.id'=>$hotel_id);
            $fileds = 'a.id as box_id,d.name,ext.hotel_cover_media_id';
            $res_box = $m_box->getBoxInfo($fileds,$where);

            $hotel_logo = '';
            if($res_box[0]['hotel_cover_media_id']>0){
                $m_media = new \Common\Model\MediaModel();
                $res_media = $m_media->getMediaInfoById($res_box[0]['hotel_cover_media_id']);
                $hotel_logo = $res_media['oss_addr'];
            }
            $headPic = base64_encode($hotel_logo);
            $host_name = C('HOST_NAME');
            $code_url = $host_name."/Smallapp46/qrcode/getBoxQrcode?box_id={$res_box[0]['box_id']}&box_mac={$box_mac}&data_id={$activity_id}&type=46";
            $message = array('action'=>138,'countdown'=>120,'nickName'=>$res_box[0]['name'],'headPic'=>$headPic,'codeUrl'=>$code_url);
            $m_netty = new \Common\Model\NettyModel();
            $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
            if($res_netty['error_code']){
                $this->to_back($res_netty['error_code']);
            }
        }
        $this->to_back(array('activity_id'=>$activity_id,'qrcode_url'=>$code_url));
    }




}