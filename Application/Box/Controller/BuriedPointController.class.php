<?php
namespace Box\Controller;
use \Common\Controller\CommonController as CommonController;

class BuriedPointController extends CommonController{

    function _init_(){
        switch (ACTION_NAME){
            case 'boxNetLogs':
                $this->is_verify = 1;
                $this->valid_fields = array('req_id'=>1001,'forscreen_id'=>1001,'resource_id'=>1001,'box_mac'=>1001,
                    'openid'=>1001,'used_time'=>1001,'is_exist'=>1001,'is_break'=>1002,'receive_nettytime'=>1001);
                break;
            case 'boxReceiveNetty':
                $this->is_verify = 1;
                $this->valid_fields = array('req_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function boxReceiveNetty(){
        $req_id = $this->params['req_id'];

        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $receive_nettytime = getMillisecond();
        $params = array(
            'box_receivetime'=>$receive_nettytime,
        );
        $m_forscreen->recordTrackLog($req_id,$params);
        $time = getMillisecond();
        $res = array('nowtime'=>$time);
        $this->to_back($res);
    }


    /**
     * @desc 机顶盒上报资源下载情况
     */
    public function boxNetLogs(){
        $req_id = $this->params['req_id'];
        $forscreen_id = $this->params['forscreen_id'];
        $resource_id  = $this->params['resource_id'];
        $openid       = $this->params['openid'];
        $box_mac      = $this->params['box_mac'];
        $used_time    = abs($this->params['used_time']);//用时
        $is_exist     = intval($this->params['is_exist']);//是否存在
        $is_break     = $this->params['is_break'];
        $receive_nettytime = $this->params['receive_nettytime'];

        $data = array('forscreen_id'=>$forscreen_id,'resource_id'=>intval($resource_id),'openid'=>$openid,
            'box_mac'=>$box_mac,'is_exist'=>$is_exist,'is_break'=>$is_break);

        switch ($is_exist){
            case 1://资源已存在于机顶盒，不走下载逻辑
                break;
            case 0:
                $data['used_time'] = $used_time;
                $data['box_res_sdown_time'] = $receive_nettytime;
                $data['box_res_edown_time'] = $receive_nettytime + $used_time;
                break;
            case 2://下载失败
                $data['box_res_sdown_time'] = $receive_nettytime;
                $data['box_res_edown_time'] = 0 ;
                break;
        }

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(5);
        $cache_key = C('SAPP_BOX_FORSCREEN_NET').$box_mac;
        $redis->rpush($cache_key, json_encode($data));

        if(isset($data['box_res_sdown_time']) && isset($data['box_res_edown_time'])){
            $box_res_sdown_time = $data['box_res_sdown_time'];
            $box_res_edown_time = $data['box_res_edown_time'];
        }else{
            $box_res_sdown_time = 0;
            $box_res_edown_time = 0;
        }

        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $params = array(
            'box_receivetime'=>$receive_nettytime,
            'box_downstime'=>$box_res_sdown_time,
            'box_downetime'=>$box_res_edown_time,
        );
        $m_forscreen->recordTrackLog($req_id,$params);

        $this->to_back(10000);
    }
    
}