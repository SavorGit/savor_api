<?php
namespace Small\Controller;
use Think\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;
class PubAdsController extends CommonController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBoxLastPeroid':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
           
           
        }
        parent::_init_();
    }
    /**
     * @desc 获取机顶盒最新广告期号
     */
    public function getBoxLastPeroid(){
        $box_id = $this->params['box_id'];
        $redis = new SavorRedis();
        $redis->select(12);
        $cache_key = C('PROGRAM_ADS_CACHE_PRE').$box_id;
        $ads_list = $redis->get($cache_key);
        if(empty($ads_list)){
            $this->to_back(10000);
        }else {
            $ads_list = json_decode($ads_list,true);
            $peroid = $ads_list['menu_num'];
            $data['peroid'] = $peroid;
            $this->to_back($data);
        }
    }
}