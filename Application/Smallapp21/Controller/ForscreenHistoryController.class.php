<?php
namespace Smallapp21\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class ForscreenHistoryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            
            case 'getList':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'page'=>1001);
                break;
            
        }
        parent::_init_();
    }
    public function getList(){
        $openid   = $this->params['openid'];
        $box_mac  = $this->params['box_mac'];
        $page     = $this->params['page'] ? intval($this->params['page']) :1 ;
        $cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;
        
        $pagesize = 10;
        $redis = SavorRedis::getInstance();
        $redis->select('5');
        $keys = $redis->keys($cache_key);
        $oss_host = 'http://'. C('OSS_HOST').'/';
        if(empty($keys)){
            $data = array();
        }else {
            $data = array();
            $list = $redis->lgetrange($cache_key, 0, -1);
            //$nums = count($list);
            foreach($list as $key=>$v){
                $v = json_decode($v,true);
                $imgs = json_decode($v['imgs']);
                
                $tmp = array();
                if($v['action']==2){
                    $tmp['resource_id'] = $v['resource_id'];
                    $tmp['imgurl']      = $oss_host.$imgs[0]."?x-oss-process=video/snapshot,t_3000,f_jpg,w_90,m_fast";
                    $tmp['res_url']     = $oss_host.$imgs[0];
                    $tmp['res_type']    = 2;
                    $tmp['forscreen_url']= $imgs[0];
                    $imgs_arr = explode('/', $imgs[0]);
                    $tmp['filename'] = $imgs_arr[2];
                    $tmp['duration'] = secToMinSec(intval($v['duration']));
                    $data[$v['forscreen_id']]['res_type'] = 2;
                    $data[$v['forscreen_id']]['forscreen_id'] = $v['forscreen_id'];
                    //$data[$v['forscreen_id']]['res_nums'] = $key;
                    $data[$v['forscreen_id']]['list'][] = $tmp;
                    $data[$v['forscreen_id']]['create_time'] = viewTimes(intval($v['forscreen_id']/1000));
                }else if($v['action']==4){
                    $tmp['resource_id'] = $v['resource_id'];
                    $tmp['imgurl']      = $oss_host.$imgs[0];
                    $tmp['res_url']     = $oss_host.$imgs[0];
                    $tmp['res_type']    = 1;
                    $tmp['forscreen_url']= $imgs[0];
                    $imgs_arr = explode('/', $imgs[0]);
                    $tmp['filename'] = $imgs_arr[2];
                    $data[$v['forscreen_id']]['res_type'] = 1;
                    $data[$v['forscreen_id']]['forscreen_id'] = $v['forscreen_id'];
                    ///$data[$v['forscreen_id']]['res_nums'] = $key;
                    $data[$v['forscreen_id']]['list'][] = $tmp;
                    $data[$v['forscreen_id']]['create_time'] = viewTimes(intval($v['forscreen_id']/1000));
                }  
            }
            foreach($data as $key=>$v){
                $data[$key]['res_nums'] = count($v['list']);
            }
            sortArrByOneField($data, 'forscreen_id',true);
            $data = array_slice($data, 0,$pagesize*$page);
        }
        $this->to_back($data);
    }
}