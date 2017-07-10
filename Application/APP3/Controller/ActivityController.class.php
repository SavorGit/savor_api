<?php
/**
 * @desc 活动接口
 * @author zhang.yingtao
 * @since  2017-07-07
 */
namespace APP3\Controller;
use Think\Controller;
use Common\Controller\CommonController;
class ActivityController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'smashEgg':
                $this->is_verify = 1;
                $this->valid_fields = array('hotelId'=>'1001');
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 砸蛋活动
     */
    public function smashEgg(){
        $hotel_id = $this->params['hotelId'];
        //抽奖banner图开始
        $m_box_award = new \Common\Model\BoxAwardModel();
        $now_date = date('Y-m-d');
        $hotelAwardCount = $m_box_award->countBoxAward($hotel_id,$now_date);
        
        if($hotelAwardCount>0){
            $m_sys_config = new \Common\Model\SysConfigModel();
            $configs = $m_sys_config->getInfo("'system_award_time'");
            if(!empty($configs)){
                $award_time = json_decode($configs[0]['config_value'],true);
            
                $now_time = date('H:i');
                $m_media = new \Common\Model\MediaModel();
                $m_award_log = new \Common\Model\AwardLogModel();
                foreach($award_time as $v){
            
                    if($now_time>=$v['start_time'] && $now_time<=$v['end_time']){
                        $awardList = $m_box_award->getAwardInfoByHotelid($hotel_id,$now_date);
                        if(!empty($awardList)){
                            $award_arr = array();
                            //$mediainfo = $m_sys_config->getInfo("'system_award_banner'");
                            //if(!empty($mediainfo)){
                            //$media_id = $mediainfo[0]['config_value'];
                            
                            //$marr = $m_media->getMediaInfoById($media_id);
                            //if(!empty($marr)){
                                //$award_arr['imageURL'] = $marr['oss_addr'];
                            $award_arr['award_start_time'] = $v['start_time'];
                            $award_arr['award_end_time']   = $v['end_time'];
                            $traceinfo = $this->traceinfo;
                            
                            $ret = $m_award_log->countAwardLog($traceinfo['deviceid'],$now_date);
                               
                            $all_lottery_num = C('ALL_LOTTERY_NUMBER');
                            $remain_lottery_num = $all_lottery_num-$ret;
                            $award_arr['lottery_num'] = $remain_lottery_num<0 ? 0 :$remain_lottery_num;
           
                            if($award_arr){
                                $data['award'] = $award_arr;
                            }
            
                        }
                        break;
                    }
                }
            }
        }
        //抽奖banner图结束
        $this->to_back($data);
    }
}