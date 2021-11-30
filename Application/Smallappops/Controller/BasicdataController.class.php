<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class BasicdataController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'hotel':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'versionupgrade':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;
            case 'resourceupdate':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'area_id'=>1001,'staff_id'=>1001);
                break;

        }
        parent::_init_();
    }
    public function hotel(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if($area_id==0 || ($area_id>0 && $staff_id==0)){
            $cache_key = C('SAPP_OPS').'stat:hotel:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:hotel:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:hotel:staff:'.$staff_id;
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('酒楼数：当前范围下所有正常状态的酒楼数量', '在线：15分钟内有心跳', '24h内开机：24小时内有心跳', '7日失联：7日内无心跳', '30日失联：30日内无心跳');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    public function versionupgrade(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if($area_id==0 || ($area_id>0 && $staff_id==0)){
            $cache_key = C('SAPP_OPS').'stat:versionup:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:versionup:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:versionup:staff:'.$staff_id;
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('小平台：已升级数量/未升级数量', '机顶盒：已升级数量/未升级数量');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    public function resourceupdate(){
        $openid = $this->params['openid'];
        $area_id = intval($this->params['area_id']);
        $staff_id = intval($this->params['staff_id']);

        $m_staff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_staff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $type = $this->check_permission($res_staff,$area_id,$staff_id);
        if($type==0){
            $this->to_back(1001);
        }
        $redis = new \Common\Lib\SavorRedis();
        $redis->select(22);
        if($area_id==0 || ($area_id>0 && $staff_id==0)){
            $cache_key = C('SAPP_OPS').'stat:resourceup:area:'.$area_id;
        }elseif($area_id>0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:resourceup:area_staff:'.$res_staff['sysuser_id'].':'.$area_id;
        }elseif($area_id==0 && $staff_id>0){
            $cache_key = C('SAPP_OPS').'stat:resourceup:staff:'.$staff_id;
        }else{
            $cache_key = '';
        }
        $res_data = array();
        if(!empty($cache_key)) {
            $desc = array('通过各类型资源的期号进行对比，期号最新的为已更新。其余为未更新');
            $res_cache_data = $redis->get($cache_key);
            $res_data = json_decode($res_cache_data, true);
            $res_data['desc'] = $desc;
        }
        $this->to_back($res_data);
    }

    private function check_permission($staff_info,$area_id,$staff_id){
        $permission = json_decode($staff_info['permission'],true);
        switch ($permission['hotel_info']['type']) {
            case 1:
                $type = 1;
                break;
            case 2:
                if(!in_array($area_id,$permission['hotel_info']['area_ids'])){
                    $this->to_back(1001);
                }
                $type = 2;
                break;
            case 3:
                if($staff_id!=$staff_info['id']){
                    $this->to_back(1001);
                }
                $type = 3;
                break;
            default:
                $type = 0;
        }
        return $type;
    }

}